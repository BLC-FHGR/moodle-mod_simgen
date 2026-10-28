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

namespace mod_simgen\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Saves the progress (and optionally the completion) of the current user.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_state extends external_api {

    /** @var int default maximum state size in KB */
    const DEFAULT_MAX_KB = 512;

    /** @var int maximum active working time added by one call in seconds */
    const MAX_ACTIVE_STEP = 900;

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'state' => new external_value(PARAM_RAW, 'JSON encoded state', VALUE_DEFAULT, ''),
            'completed' => new external_value(PARAM_BOOL, 'Mark the simulation as completed', VALUE_DEFAULT, false),
            'score' => new external_value(PARAM_FLOAT, 'Score reported by the simulation', VALUE_DEFAULT, null,
                NULL_ALLOWED),
            'maxscore' => new external_value(PARAM_FLOAT, 'Maximum score', VALUE_DEFAULT, null, NULL_ALLOWED),
            'summary' => new external_value(PARAM_TEXT, 'Result summary', VALUE_DEFAULT, ''),
            'activeseconds' => new external_value(PARAM_INT, 'Active working time since the last call (seconds)',
                VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Saves the state.
     *
     * @param int $cmid
     * @param string $state
     * @param bool $completed
     * @param float|null $score
     * @param float|null $maxscore
     * @param string $summary
     * @param int $activeseconds
     * @return array
     */
    public static function execute(int $cmid, string $state = '', bool $completed = false, ?float $score = null,
            ?float $maxscore = null, string $summary = '', int $activeseconds = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'state' => $state,
            'completed' => $completed,
            'score' => $score,
            'maxscore' => $maxscore,
            'summary' => $summary,
            'activeseconds' => $activeseconds,
        ]);
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'simgen');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/simgen:view', $context);
        require_capability('mod/simgen:savestate', $context);

        $simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
        $now = time();

        $statedata = null;
        if ($params['state'] !== '' && !empty($simgen->savestate)) {
            $maxkb = (int)get_config('mod_simgen', 'maxstatesize') ?: self::DEFAULT_MAX_KB;
            if (strlen($params['state']) > $maxkb * 1024) {
                throw new \moodle_exception('error_statetoolarge', 'mod_simgen', '', $maxkb);
            }
            json_decode($params['state']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \invalid_parameter_exception('state is not valid JSON');
            }
            $statedata = $params['state'];
        }

        $record = $DB->get_record('simgen_states', ['simgenid' => $simgen->id, 'userid' => $USER->id]);
        $newlycompleted = $params['completed'] && (!$record || empty($record->completed));

        if (!$record) {
            $record = (object)[
                'simgenid' => $simgen->id,
                'userid' => $USER->id,
                'versionid' => (int)$simgen->publishedversion,
                'statedata' => $statedata,
                'completed' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
                'timecompleted' => 0,
            ];
            try {
                $record->id = $DB->insert_record('simgen_states', $record);
            } catch (\dml_write_exception $e) {
                // A parallel request (e.g. beacon on page leave) created the record in the meantime.
                $record = $DB->get_record('simgen_states', ['simgenid' => $simgen->id, 'userid' => $USER->id],
                    '*', MUST_EXIST);
            }
        }

        $update = (object)['id' => $record->id, 'timemodified' => $now, 'versionid' => (int)$simgen->publishedversion];
        if ($statedata !== null) {
            $update->statedata = $statedata;
        }
        if ($params['completed']) {
            $update->completed = 1;
            $update->timecompleted = $now;
            // Scores are reported by the client: clamp them to the column range (number(15,5)).
            $update->rawscore = self::clamp($params['score']);
            $update->maxscore = self::clamp($params['maxscore']);
            $update->summary = \core_text::substr($params['summary'], 0, 4000);
        }
        $DB->update_record('simgen_states', $update);

        // Active working time, measured by the player (atomic, parallel calls like beacons must not lose time;
        // a single call never adds more than MAX_ACTIVE_STEP).
        $seconds = max(0, min(self::MAX_ACTIVE_STEP, (int)$params['activeseconds']));
        if ($seconds) {
            $DB->execute('UPDATE {simgen_states} SET activetime = activetime + ? WHERE id = ?', [$seconds, $record->id]);
        }
        if ($newlycompleted) {
            // Time until the first completion (comparable with the planned working time).
            $DB->execute('UPDATE {simgen_states} SET completedactivetime = activetime WHERE id = ?', [$record->id]);
        }

        if ($newlycompleted) {
            $completion = new \completion_info($course);
            if ($completion->is_enabled($cm) && !empty($simgen->completionfinished)) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }
            \mod_simgen\event\simulation_completed::create([
                'objectid' => $record->id,
                'context' => $context,
                'relateduserid' => $USER->id,
            ])->trigger();
        }

        return ['status' => true, 'timemodified' => $now];
    }

    /**
     * Clamps a reported score to the range of the database column.
     *
     * @param float|null $value
     * @return float|null
     */
    private static function clamp(?float $value): ?float {
        if ($value === null || !is_finite($value)) {
            return null;
        }
        return max(-9999999999.0, min(9999999999.0, round($value, 5)));
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success'),
            'timemodified' => new external_value(PARAM_INT, 'Time of the save'),
        ]);
    }
}
