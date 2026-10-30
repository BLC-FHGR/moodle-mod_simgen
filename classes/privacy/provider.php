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

namespace mod_simgen\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider of mod_simgen.
 *
 * Stored personal data: the progress/results of users (simgen_states) and the author of versions (simgen_versions).
 * The prompt sent to the AI contains only the briefing of the teacher, no student data; the request itself is
 * handled and logged by local_ai_manager.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('simgen_states', [
            'userid' => 'privacy:metadata:simgen_states:userid',
            'versionid' => 'privacy:metadata:simgen_states:versionid',
            'statedata' => 'privacy:metadata:simgen_states:statedata',
            'completed' => 'privacy:metadata:simgen_states:completed',
            'rawscore' => 'privacy:metadata:simgen_states:rawscore',
            'maxscore' => 'privacy:metadata:simgen_states:maxscore',
            'summary' => 'privacy:metadata:simgen_states:summary',
            'timecreated' => 'privacy:metadata:simgen_states:timecreated',
            'timemodified' => 'privacy:metadata:simgen_states:timemodified',
            'timecompleted' => 'privacy:metadata:simgen_states:timecompleted',
            'activetime' => 'privacy:metadata:simgen_states:activetime',
            'completedactivetime' => 'privacy:metadata:simgen_states:completedactivetime',
        ], 'privacy:metadata:simgen_states');

        $collection->add_database_table('simgen_versions', [
            'userid' => 'privacy:metadata:simgen_versions:userid',
            'revisionnote' => 'privacy:metadata:simgen_versions:revisionnote',
            'timecreated' => 'privacy:metadata:simgen_versions:timecreated',
        ], 'privacy:metadata:simgen_versions');

        $collection->add_database_table('simgen_conversations', [
            'userid' => 'privacy:metadata:simgen_conversations:userid',
            'versionid' => 'privacy:metadata:simgen_conversations:versionid',
            'personaid' => 'privacy:metadata:simgen_conversations:personaid',
            'messagedata' => 'privacy:metadata:simgen_conversations:messagedata',
            'signaldata' => 'privacy:metadata:simgen_conversations:signaldata',
            'turncount' => 'privacy:metadata:simgen_conversations:turncount',
            'resetcount' => 'privacy:metadata:simgen_conversations:resetcount',
            'timecreated' => 'privacy:metadata:simgen_conversations:timecreated',
            'timemodified' => 'privacy:metadata:simgen_conversations:timemodified',
        ], 'privacy:metadata:simgen_conversations');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        $collection->add_external_location_link('aiprovider', [
            'briefing' => 'privacy:metadata:aiprovider:briefing',
            'conversation' => 'privacy:metadata:aiprovider:conversation',
        ], 'privacy:metadata:aiprovider');
        return $collection;
    }

    /**
     * Contexts containing data of a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'simgen'
                  JOIN {simgen} s ON s.id = cm.instance
             LEFT JOIN {simgen_states} st ON st.simgenid = s.id AND st.userid = :userid1
             LEFT JOIN {simgen_versions} v ON v.simgenid = s.id AND v.userid = :userid2
             LEFT JOIN {simgen_conversations} c ON c.simgenid = s.id AND c.userid = :userid3
                 WHERE st.id IS NOT NULL OR v.id IS NOT NULL OR c.id IS NOT NULL";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $params = ['cmid' => $context->instanceid, 'modname' => 'simgen'];
        $base = "FROM {course_modules} cm
                 JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 JOIN {simgen} s ON s.id = cm.instance";
        $userlist->add_from_sql('userid', "SELECT st.userid {$base} JOIN {simgen_states} st ON st.simgenid = s.id
            WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT v.userid {$base} JOIN {simgen_versions} v ON v.simgenid = s.id
            WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT c.userid {$base} JOIN {simgen_conversations} c ON c.simgenid = s.id
            WHERE cm.id = :cmid", $params);
    }

    /**
     * Exports the data of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('simgen', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $state = $DB->get_record('simgen_states', ['simgenid' => $cm->instance, 'userid' => $userid]);
            if ($state) {
                writer::with_context($context)->export_data([get_string('privacy:path:progress', 'mod_simgen')],
                    (object)[
                        'state' => $state->statedata,
                        'completed' => transform::yesno($state->completed),
                        'score' => $state->rawscore,
                        'maxscore' => $state->maxscore,
                        'summary' => $state->summary,
                        'timecreated' => transform::datetime($state->timecreated),
                        'timemodified' => transform::datetime($state->timemodified),
                        'timecompleted' => $state->timecompleted ? transform::datetime($state->timecompleted) : '-',
                        'activetime' => format_time((int)$state->activetime),
                        'completedactivetime' => $state->completedactivetime
                            ? format_time((int)$state->completedactivetime) : '-',
                    ]);
            }
            $versions = $DB->get_records('simgen_versions', ['simgenid' => $cm->instance, 'userid' => $userid],
                'versionnum ASC', 'id, versionnum, origin, revisionnote, timecreated');
            if ($versions) {
                $data = [];
                foreach ($versions as $v) {
                    $data[] = [
                        'version' => $v->versionnum,
                        'origin' => $v->origin,
                        'note' => $v->revisionnote,
                        'timecreated' => transform::datetime($v->timecreated),
                    ];
                }
                writer::with_context($context)->export_data([get_string('privacy:path:versions', 'mod_simgen')],
                    (object)['versions' => $data]);
            }
            $conversations = $DB->get_records('simgen_conversations', ['simgenid' => $cm->instance, 'userid' => $userid],
                'timecreated ASC');
            if ($conversations) {
                $data = [];
                foreach ($conversations as $c) {
                    $messages = json_decode((string)$c->messagedata, true);
                    $data[] = [
                        'persona' => $c->personaid,
                        'messages' => is_array($messages) ? array_map(fn($m) => [
                            'role' => $m['role'] ?? '',
                            'text' => $m['text'] ?? '',
                            'time' => !empty($m['time']) ? transform::datetime($m['time']) : '',
                        ], $messages) : [],
                        'signals' => json_decode((string)$c->signaldata, true),
                        'timemodified' => transform::datetime($c->timemodified),
                    ];
                }
                writer::with_context($context)->export_data([get_string('privacy:path:conversations', 'mod_simgen')],
                    (object)['conversations' => $data]);
            }
        }
    }

    /**
     * Deletes the data of all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('simgen', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('simgen_states', ['simgenid' => $cm->instance]);
        $DB->delete_records('simgen_conversations', ['simgenid' => $cm->instance]);
        $DB->set_field('simgen_versions', 'userid', 0, ['simgenid' => $cm->instance]);
    }

    /**
     * Deletes the data of a user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('simgen', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('simgen_states', ['simgenid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('simgen_conversations', ['simgenid' => $cm->instance, 'userid' => $userid]);
            // Versions are course content: keep them, but remove the author.
            $DB->set_field('simgen_versions', 'userid', 0, ['simgenid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * Deletes the data of several users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('simgen', $context->instanceid);
        if (!$cm || !$userids = $userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['simgenid'] = $cm->instance;
        $DB->delete_records_select('simgen_states', "simgenid = :simgenid AND userid {$insql}", $params);
        $DB->delete_records_select('simgen_conversations', "simgenid = :simgenid AND userid {$insql}", $params);
        $DB->set_field_select('simgen_versions', 'userid', 0, "simgenid = :simgenid AND userid {$insql}", $params);
    }
}
