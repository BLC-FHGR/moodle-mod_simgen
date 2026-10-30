<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_simgen\local;

use context_module;
use moodle_url;
use stdClass;

/**
 * Generates the facilitator guide of a version (brief, play, debrief) via local_ai_manager.
 *
 * The guide is a separate AI request after the code exists: it describes the actual simulation, does not compete
 * with the code for the output limit and can be renewed without touching the code. It is created automatically when
 * a version is published (setting autoguide) or on request of the teacher. The status fields live on the version and
 * use the status constants of {@see generator}.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide_generator {

    /**
     * Queues the generation of the guide of a version.
     *
     * @param stdClass $version version record
     * @param int $userid user on whose behalf the AI is called
     * @return bool false if a generation of this guide is currently running (nothing was queued)
     */
    public static function queue(stdClass $version, int $userid): bool {
        global $DB;

        $current = $DB->get_record('simgen_versions', ['id' => $version->id], '*', MUST_EXIST);
        self::check_stale($current);
        if ((int)$current->guidestatus === generator::STATUS_GENERATING) {
            return false;
        }

        $now = time();
        $DB->update_record('simgen_versions', (object)[
            'id' => $version->id,
            'guidestatus' => generator::STATUS_PENDING,
            'guidemessage' => '',
            'guiderequested' => $now,
            'guidestarted' => 0,
        ]);

        $task = new \mod_simgen\task\generate_guide();
        $task->set_userid($userid);
        $task->set_custom_data(['versionid' => (int)$version->id, 'requested' => $now]);
        \core\task\manager::queue_adhoc_task($task);
        return true;
    }

    /**
     * Queues the guide when a version is published and has none yet (admin setting autoguide).
     *
     * @param stdClass $version version record
     * @param int $userid
     * @return bool true if a generation was queued
     */
    public static function queue_on_publish(stdClass $version, int $userid): bool {
        $auto = get_config('mod_simgen', 'autoguide');
        if (($auto !== false && empty($auto)) || (int)$version->guidestatus !== generator::STATUS_NONE
                || trim((string)$version->guide) !== '') {
            return false;
        }
        return self::queue($version, $userid);
    }

    /**
     * Runs a queued generation (from the adhoc task or directly from guide.php).
     *
     * @param int $versionid
     * @param int $requested timestamp of the request; stale requests are ignored
     * @return bool true if the guide was stored
     */
    public static function run(int $versionid, int $requested = 0): bool {
        global $DB;

        $version = $DB->get_record('simgen_versions', ['id' => $versionid]);
        $simgen = $version ? $DB->get_record('simgen', ['id' => $version->simgenid]) : null;
        $cm = $simgen ? get_coursemodule_from_instance('simgen', $simgen->id, 0, false, IGNORE_MISSING) : null;
        if (!$cm) {
            return false;
        }
        $context = context_module::instance($cm->id);

        // Claim the request under a lock (protects against parallel runs of the task and "create now").
        $lockfactory = \core\lock\lock_config::get_lock_factory('mod_simgen');
        if (!$lock = $lockfactory->get_lock('guide_' . $versionid, 0)) {
            return false;
        }
        try {
            $version = $DB->get_record('simgen_versions', ['id' => $versionid]);
            if (!$version || (int)$version->guidestatus !== generator::STATUS_PENDING
                    || ($requested && (int)$version->guiderequested !== $requested)) {
                // Nothing to do, already running or superseded by a newer request.
                return false;
            }
            $DB->update_record('simgen_versions', (object)[
                'id' => $version->id,
                'guidestatus' => generator::STATUS_GENERATING,
                'guidestarted' => time(),
            ]);
        } finally {
            $lock->release();
        }

        \core_php_time_limit::raise(generator::STALE_AFTER);

        $response = '';
        try {
            $prompt = prompt_builder::build_guide_prompt($simgen, $context, $version);

            $purpose = get_config('mod_simgen', 'purpose') ?: 'singleprompt';
            $manager = new \local_ai_manager\manager($purpose);
            $result = $manager->perform_request($prompt, 'mod_simgen', $context->id);

            if ($result->get_code() !== 200) {
                $message = $result->get_errormessage();
                if ($result->get_debuginfo() !== '' && debugging('', DEBUG_DEVELOPER)) {
                    $message .= ' (' . $result->get_debuginfo() . ')';
                }
                throw new \moodle_exception('error_ai', 'mod_simgen', '', $message);
            }

            $response = $result->get_content();
            $guide = response_parser::parse_guide($response);

            if (!self::owns_request($version)) {
                // Superseded by a newer request or a manual guide (e.g. after this run was considered stale).
                return false;
            }
            $DB->update_record('simgen_versions', (object)[
                'id' => $version->id,
                'guide' => $guide,
                'guideorigin' => 'ai',
                'guidemodelinfo' => \core_text::substr($result->get_modelinfo(), 0, 255),
                'guidestatus' => generator::STATUS_READY,
                'guidemessage' => '',
                'guidestarted' => 0,
                'guidemodified' => time(),
            ]);

            self::notify($simgen, $cm, $version, true);
            return true;

        } catch (\Throwable $e) {
            $message = $e instanceof \moodle_exception ? $e->getMessage() : get_string('error_unexpected', 'mod_simgen',
                $e->getMessage());
            if ($response !== '') {
                $message .= "\n\n" . get_string('error_rawresponse', 'mod_simgen') . "\n"
                    . \core_text::substr(trim(strip_tags($response)), 0, 2000);
            }
            if (!self::owns_request($version)) {
                return false;
            }
            // An existing guide stays available; only the status shows the failed renewal.
            $DB->update_record('simgen_versions', (object)[
                'id' => $version->id,
                'guidestatus' => generator::STATUS_ERROR,
                'guidemessage' => $message,
                'guidestarted' => 0,
            ]);
            self::notify($simgen, $cm, $version, false);
            return false;
        }
    }

    /**
     * Stores a guide edited by the teacher.
     *
     * @param stdClass $version
     * @param string $guide Markdown
     * @return void
     */
    public static function save_manual(stdClass $version, string $guide): void {
        global $DB;
        $DB->update_record('simgen_versions', (object)[
            'id' => $version->id,
            'guide' => trim($guide),
            'guideorigin' => 'manual',
            'guidestatus' => trim($guide) === '' ? generator::STATUS_NONE : generator::STATUS_READY,
            'guidemessage' => '',
            'guidemodified' => time(),
        ]);
    }

    /**
     * May a run still store its result? Not if a newer request was queued or the teacher saved a guide after the
     * run claimed its request.
     *
     * A run that was marked as stale (status error) but finishes after all may still store its result.
     *
     * @param stdClass $version version record as claimed by the run
     * @return bool
     */
    public static function owns_request(stdClass $version): bool {
        global $DB;
        $current = $DB->get_record('simgen_versions', ['id' => $version->id], 'id, guidestatus, guiderequested');
        return $current && (int)$current->guiderequested === (int)$version->guiderequested
            && in_array((int)$current->guidestatus, [generator::STATUS_GENERATING, generator::STATUS_ERROR], true);
    }

    /**
     * Marks guide generations that died (e.g. PHP timeout) as failed.
     *
     * @param stdClass $version version record, updated in place
     * @return void
     */
    public static function check_stale(stdClass $version): void {
        global $DB;
        if ((int)$version->guidestatus === generator::STATUS_GENERATING && !empty($version->guidestarted)
                && $version->guidestarted < time() - generator::STALE_AFTER) {
            $version->guidestatus = generator::STATUS_ERROR;
            $version->guidemessage = get_string('error_stale', 'mod_simgen');
            $version->guidestarted = 0;
            $DB->update_record('simgen_versions', (object)[
                'id' => $version->id,
                'guidestatus' => $version->guidestatus,
                'guidemessage' => $version->guidemessage,
                'guidestarted' => 0,
            ]);
        }
    }

    /**
     * Sends a notification to the user who requested the guide.
     *
     * @param stdClass $simgen
     * @param stdClass $cm
     * @param stdClass $version
     * @param bool $success
     * @return void
     */
    private static function notify(stdClass $simgen, stdClass $cm, stdClass $version, bool $success): void {
        global $USER;

        if (empty($USER->id) || isguestuser($USER) || !CLI_SCRIPT) {
            // Only notify for background runs; interactive runs show the result directly.
            return;
        }
        try {
            $url = new moodle_url('/mod/simgen/guide.php', ['id' => $cm->id, 'version' => $version->id]);
            $a = (object)['name' => format_string($simgen->name), 'url' => $url->out(false),
                'num' => $version->versionnum];

            $message = new \core\message\message();
            $message->component = 'mod_simgen';
            $message->name = 'generationfinished';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $USER;
            $message->subject = get_string($success ? 'guide_notify_success_subject' : 'guide_notify_error_subject',
                'mod_simgen', $a);
            $message->fullmessage = get_string($success ? 'guide_notify_success_body' : 'guide_notify_error_body',
                'mod_simgen', $a);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = $url->out(false);
            $message->contexturlname = format_string($simgen->name);
            $message->courseid = $simgen->course;
            message_send($message);
        } catch (\Throwable $e) {
            debugging('mod_simgen: notification failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
