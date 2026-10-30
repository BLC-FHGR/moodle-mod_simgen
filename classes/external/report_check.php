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
use mod_simgen\local\generator;
use mod_simgen\local\html_builder;

/**
 * Stores the result of the start-up check of a version (teacher preview).
 *
 * If the check passed, the code compiled by the teacher's browser is stored, so students get it without Babel.
 * If the first check of a version written by the AI fails, the AI repairs it automatically.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_check extends external_api {

    /** @var int version not checked yet */
    const UNCHECKED = 0;
    /** @var int started without errors */
    const PASSED = 1;
    /** @var int an error occurred during start-up */
    const FAILED = 2;

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'versionid' => new external_value(PARAM_INT, 'Checked version'),
            'ok' => new external_value(PARAM_BOOL, 'Started without errors'),
            'error' => new external_value(PARAM_RAW, 'First error', VALUE_DEFAULT, ''),
            'compiled' => new external_value(PARAM_RAW, 'Compiled JavaScript (React versions)', VALUE_DEFAULT, ''),
            'appname' => new external_value(PARAM_RAW, 'Name of the app component', VALUE_DEFAULT, 'App'),
            'selfrender' => new external_value(PARAM_BOOL, 'The code renders itself', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Stores the result.
     *
     * @param int $cmid
     * @param int $versionid
     * @param bool $ok
     * @param string $error
     * @param string $compiled
     * @param string $appname
     * @param bool $selfrender
     * @return array
     */
    public static function execute(int $cmid, int $versionid, bool $ok, string $error = '', string $compiled = '',
            string $appname = 'App', bool $selfrender = false): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'versionid', 'ok', 'error',
            'compiled', 'appname', 'selfrender'));
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'simgen');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/simgen:manage', $context);

        $version = $DB->get_record('simgen_versions', ['id' => $params['versionid'], 'simgenid' => $cm->instance],
            '*', MUST_EXIST);
        $firstcheck = (int)$version->checkstatus === self::UNCHECKED;

        $update = (object)['id' => $version->id];
        if ($params['ok']) {
            $update->checkstatus = self::PASSED;
            $update->checkerror = null;
            if ($version->codetype === 'react' && $params['compiled'] !== ''
                    && strlen($params['compiled']) <= html_builder::MAX_COMPILED
                    && html_builder::valid_identifier($params['appname'])) {
                $update->compiled = json_encode(['code' => $params['compiled'], 'app' => $params['appname'],
                    'selfrender' => $params['selfrender']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $update->compiledhash = html_builder::compile_hash($version);
            }
        } else {
            $update->checkstatus = self::FAILED;
            $update->checkerror = \core_text::substr(trim($params['error']), 0, 6000);
            $update->compiled = null;
            $update->compiledhash = null;
        }
        $DB->update_record('simgen_versions', $update);

        // Only the first failure of a version starts a repair (not every reload of the preview).
        $repairqueued = false;
        if (!$params['ok'] && $firstcheck && generator::autorepair_allowed($version)) {
            $simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
            generator::check_stale($simgen);
            // A queued or running request (of the teacher or a repair after the static checks) has priority and
            // must not be replaced.
            if (!in_array((int)$simgen->status, [generator::STATUS_PENDING, generator::STATUS_GENERATING], true)) {
                $repairqueued = generator::queue($simgen, (int)$USER->id,
                    get_string('error_prefill', 'mod_simgen', $update->checkerror), (int)$version->id, true);
            }
        }

        return ['status' => (int)$update->checkstatus, 'repairqueued' => $repairqueued];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'Check status of the version'),
            'repairqueued' => new external_value(PARAM_BOOL, 'An automatic repair was started'),
        ]);
    }
}
