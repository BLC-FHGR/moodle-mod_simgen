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
 * Generates simulations via local_ai_manager.
 *
 * Flow: queue() stores the request and queues an adhoc task (running as the requesting teacher, so quota, tenant
 * and role of local_ai_manager apply). run() builds the prompt, calls the AI, parses the answer and stores a new
 * version. New versions are drafts until the teacher publishes them.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator {

    /** @var int nothing generated yet */
    const STATUS_NONE = 0;
    /** @var int generation queued */
    const STATUS_PENDING = 1;
    /** @var int generation running */
    const STATUS_GENERATING = 2;
    /** @var int last generation successful */
    const STATUS_READY = 3;
    /** @var int last generation failed */
    const STATUS_ERROR = 4;

    /** @var int seconds after which a running generation is considered dead */
    const STALE_AFTER = 1200;

    /**
     * Queues a (re-)generation.
     *
     * @param stdClass $simgen instance record
     * @param int $userid user on whose behalf the AI is called
     * @param string $changerequest change request (revision) or empty for a fresh generation
     * @param int $baseversionid version to revise, 0 = generate from the briefing
     * @param bool $autorepair the revision is an automatic repair after a failed check (origin "autorepair")
     * @return bool false if a generation is currently running (nothing was queued)
     */
    public static function queue(stdClass $simgen, int $userid, string $changerequest = '', int $baseversionid = 0,
            bool $autorepair = false): bool {
        global $DB;

        // Never start a second AI request while one is running (costs quota twice, versions could collide).
        $current = $DB->get_record('simgen', ['id' => $simgen->id], '*', MUST_EXIST);
        self::check_stale($current);
        if ((int)$current->status === self::STATUS_GENERATING) {
            return false;
        }
        self::schedule((int)$simgen->id, $userid, $changerequest, $baseversionid, $autorepair);
        return true;
    }

    /**
     * Sets the request on the instance and queues the adhoc task (without checking the status).
     *
     * @param int $simgenid
     * @param int $userid
     * @param string $changerequest
     * @param int $baseversionid
     * @param bool $autorepair
     */
    private static function schedule(int $simgenid, int $userid, string $changerequest, int $baseversionid,
            bool $autorepair): void {
        global $DB;

        $now = time();
        $DB->update_record('simgen', (object)[
            'id' => $simgenid,
            'status' => self::STATUS_PENDING,
            'statusmessage' => '',
            'genrequested' => $now,
            'genstarted' => 0,
            'pendingfeedback' => $changerequest,
            'pendingbaseversion' => $baseversionid,
            'pendingautorepair' => $autorepair && $baseversionid ? 1 : 0,
        ]);

        $task = new \mod_simgen\task\generate_simulation();
        $task->set_userid($userid);
        $task->set_custom_data(['simgenid' => $simgenid, 'requested' => $now]);
        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Maximum number of automatic repair rounds in a row (admin setting, 0 = off).
     *
     * @return int
     */
    public static function max_autorepairs(): int {
        $value = get_config('mod_simgen', 'autorepair');
        return $value === false ? 2 : max(0, min(5, (int)$value));
    }

    /**
     * May the AI repair this version automatically?
     *
     * Only the newest version, only versions written by the AI (manual code stays as the teacher wrote it) and only
     * a limited number of rounds in a row (the counter restarts with every request of a teacher).
     *
     * @param stdClass $version
     * @return bool
     */
    public static function autorepair_allowed(stdClass $version): bool {
        global $DB;

        $max = self::max_autorepairs();
        if (!$max || !in_array($version->origin, ['ai', 'revision', 'autorepair'], true)) {
            return false;
        }
        $latest = $DB->get_records('simgen_versions', ['simgenid' => $version->simgenid], 'versionnum DESC',
            'id, origin', 0, $max + 1);
        if ((int)array_key_first($latest) !== (int)$version->id) {
            return false;
        }
        $rounds = 0;
        foreach ($latest as $v) {
            if ($v->origin !== 'autorepair') {
                break;
            }
            $rounds++;
        }
        return $rounds < $max;
    }

    /**
     * Runs a queued generation (from the adhoc task or directly from generate.php).
     *
     * @param int $simgenid
     * @param int $requested timestamp of the request; stale requests are ignored
     * @return bool true if a new version was created
     */
    public static function run(int $simgenid, int $requested = 0): bool {
        global $DB, $USER;

        // Resolve the course module before claiming, so a missing module cannot leave the status on "generating".
        $cm = get_coursemodule_from_instance('simgen', $simgenid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return false;
        }
        $context = context_module::instance($cm->id);

        // Claim the request under a lock (protects against parallel runs of the task and "generate now").
        $lockfactory = \core\lock\lock_config::get_lock_factory('mod_simgen');
        if (!$lock = $lockfactory->get_lock('generate_' . $simgenid, 0)) {
            return false;
        }
        try {
            $simgen = $DB->get_record('simgen', ['id' => $simgenid]);
            if (!$simgen || (int)$simgen->status !== self::STATUS_PENDING
                    || ($requested && (int)$simgen->genrequested !== $requested)) {
                // Nothing to do, already running or superseded by a newer request.
                return false;
            }
            $simgen->status = self::STATUS_GENERATING;
            $simgen->genstarted = time();
            $DB->update_record('simgen', (object)[
                'id' => $simgen->id,
                'status' => $simgen->status,
                'genstarted' => $simgen->genstarted,
            ]);
        } finally {
            $lock->release();
        }

        \core_php_time_limit::raise(self::STALE_AFTER);

        $prompt = '';
        $response = '';
        try {
            $baseversion = null;
            if (!empty($simgen->pendingbaseversion)) {
                $baseversion = $DB->get_record('simgen_versions',
                    ['id' => $simgen->pendingbaseversion, 'simgenid' => $simgen->id]);
            }
            if ($baseversion) {
                $prompt = prompt_builder::build_revision_prompt($simgen, $context, $baseversion,
                    (string)$simgen->pendingfeedback);
                $origin = !empty($simgen->pendingautorepair) ? 'autorepair' : 'revision';
            } else {
                $prompt = prompt_builder::build_generate_prompt($simgen, $context);
                $origin = 'ai';
            }

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
            $parsed = response_parser::parse($response);

            // Live conversations: validate and store the AI personas.
            $personas = self::personas_from_response($parsed, $simgen, $baseversion ?: null);

            $version = (object)[
                'simgenid' => $simgen->id,
                'versionnum' => self::next_versionnum((int)$simgen->id),
                'codetype' => $parsed['codetype'],
                'sourcecode' => $parsed['sourcecode'],
                'css' => $parsed['css'],
                'personas' => $personas ? json_encode(['personas' => array_values($personas)],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                // A revision without a new schedule keeps the schedule of its base version.
                'timeplan' => $parsed['timeplan'] !== '' ? $parsed['timeplan'] : ($baseversion->timeplan ?? null),
                'prompttext' => $prompt,
                'rawresponse' => $response,
                'revisionnote' => (string)$simgen->pendingfeedback,
                'origin' => $origin,
                'modelinfo' => \core_text::substr($result->get_modelinfo(), 0, 255),
                'userid' => $USER->id ?? 0,
                'timecreated' => time(),
            ];
            // Static checks before anybody sees the version.
            $issues = code_checker::check($simgen, $version);
            $version->checkissues = $issues ? json_encode($issues, JSON_UNESCAPED_UNICODE) : null;
            $version->id = $DB->insert_record('simgen_versions', $version);

            \mod_simgen\event\simulation_generated::create([
                'objectid' => $version->id,
                'context' => $context,
                'other' => ['versionnum' => $version->versionnum],
            ])->trigger();

            $errors = code_checker::errors($issues);
            if ($errors && self::autorepair_allowed($version)) {
                // Let the AI fix the errors right away; the teacher is notified once the repaired version is ready.
                self::schedule((int)$simgen->id, (int)($USER->id ?? 0), code_checker::repair_request($errors),
                    (int)$version->id, true);
                return true;
            }

            $DB->update_record('simgen', (object)[
                'id' => $simgen->id,
                'status' => self::STATUS_READY,
                'statusmessage' => '',
                'pendingfeedback' => '',
                'pendingbaseversion' => 0,
                'pendingautorepair' => 0,
                'genstarted' => 0,
                'timemodified' => time(),
            ]);

            self::notify($simgen, $cm, true, '');
            return true;

        } catch (\Throwable $e) {
            $message = $e instanceof \moodle_exception ? $e->getMessage() : get_string('error_unexpected', 'mod_simgen',
                $e->getMessage());
            // Keep the response for debugging if parsing failed.
            if ($response !== '') {
                $message .= "\n\n" . get_string('error_rawresponse', 'mod_simgen') . "\n"
                    . \core_text::substr(trim(strip_tags($response)), 0, 2000);
            }
            $DB->update_record('simgen', (object)[
                'id' => $simgen->id,
                'status' => self::STATUS_ERROR,
                'statusmessage' => $message,
                'genstarted' => 0,
            ]);
            self::notify($simgen, $cm, false, $message);
            return false;
        }
    }

    /**
     * Personas of a new version from the parsed AI response.
     *
     * A revision without a persona block keeps the personas of its base version (models often only return the
     * changed code, e.g. in an automatic repair).
     *
     * @param array $parsed result of response_parser::parse()
     * @param stdClass $simgen instance record
     * @param stdClass|null $baseversion revised version, null for a fresh generation
     * @return array personas keyed by id (empty if the simulation has no live conversations)
     * @throws \moodle_exception if a conversation simulation ends up without personas
     */
    public static function personas_from_response(array $parsed, stdClass $simgen, ?stdClass $baseversion): array {
        if ($parsed['codetype'] !== 'react' || !prompt_builder::is_conversation($simgen, $baseversion)) {
            return [];
        }
        if (trim($parsed['personas']) === '' && $baseversion) {
            $personas = conversation::from_version($baseversion);
        } else {
            $personas = conversation::normalise_personas($parsed['personas']);
        }
        if (!$personas) {
            throw new \moodle_exception('error_nopersonas', 'mod_simgen');
        }
        return $personas;
    }

    /**
     * Next free version number of a simulation.
     *
     * @param int $simgenid
     * @return int
     */
    public static function next_versionnum(int $simgenid): int {
        global $DB;
        return (int)$DB->get_field_sql('SELECT MAX(versionnum) FROM {simgen_versions} WHERE simgenid = ?', [$simgenid]) + 1;
    }

    /**
     * Marks generations that died (e.g. PHP timeout) as failed.
     *
     * @param stdClass $simgen instance record, updated in place
     * @return void
     */
    public static function check_stale(stdClass $simgen): void {
        global $DB;
        if ((int)$simgen->status === self::STATUS_GENERATING && !empty($simgen->genstarted)
                && $simgen->genstarted < time() - self::STALE_AFTER) {
            $simgen->status = self::STATUS_ERROR;
            $simgen->statusmessage = get_string('error_stale', 'mod_simgen');
            $simgen->genstarted = 0;
            $DB->update_record('simgen', (object)[
                'id' => $simgen->id,
                'status' => $simgen->status,
                'statusmessage' => $simgen->statusmessage,
                'genstarted' => 0,
            ]);
        }
    }

    /**
     * Sends a notification to the user who requested the generation.
     *
     * @param stdClass $simgen
     * @param stdClass $cm
     * @param bool $success
     * @param string $errormessage
     * @return void
     */
    private static function notify(stdClass $simgen, stdClass $cm, bool $success, string $errormessage): void {
        global $USER;

        if (empty($USER->id) || isguestuser($USER) || !CLI_SCRIPT) {
            // Only notify for background runs; interactive runs show the result directly.
            return;
        }
        try {
            $url = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);
            $a = (object)['name' => format_string($simgen->name), 'url' => $url->out(false)];

            $message = new \core\message\message();
            $message->component = 'mod_simgen';
            $message->name = 'generationfinished';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $USER;
            $message->subject = get_string($success ? 'notify_success_subject' : 'notify_error_subject', 'mod_simgen', $a);
            $message->fullmessage = get_string($success ? 'notify_success_body' : 'notify_error_body', 'mod_simgen', $a);
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
