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
use mod_simgen\local\guide_generator;

/**
 * Returns the generation status of a simulation and of a guide (polled by the teacher view and the guide page).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_status extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'versionid' => new external_value(PARAM_INT, 'Version whose guide status is returned', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns the status.
     *
     * @param int $cmid
     * @param int $versionid
     * @return array
     */
    public static function execute(int $cmid, int $versionid = 0): array {
        global $DB;

        ['cmid' => $cmid, 'versionid' => $versionid] = self::validate_parameters(self::execute_parameters(),
            ['cmid' => $cmid, 'versionid' => $versionid]);
        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'simgen');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/simgen:manage', $context);

        $simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
        generator::check_stale($simgen);

        $guidestatus = 0;
        if ($versionid) {
            $version = $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id], '*',
                MUST_EXIST);
            guide_generator::check_stale($version);
            $guidestatus = (int)$version->guidestatus;
        }

        return ['status' => (int)$simgen->status, 'guidestatus' => $guidestatus];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'Generation status'),
            'guidestatus' => new external_value(PARAM_INT, 'Generation status of the guide of the version (0 = none)'),
        ]);
    }
}
