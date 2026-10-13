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
use mod_simgen\local\conversation;

/**
 * Ends the conversation of the current user with an AI persona (button "end conversation").
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class end_conversation extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'versionid' => new external_value(PARAM_INT, 'Version (only used for teacher previews)', VALUE_DEFAULT, 0),
            'personaid' => new external_value(PARAM_ALPHANUMEXT, 'Persona id'),
        ]);
    }

    /**
     * Ends the conversation.
     *
     * @param int $cmid
     * @param int $versionid
     * @param string $personaid
     * @return array
     */
    public static function execute(int $cmid, int $versionid, string $personaid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'versionid' => $versionid,
            'personaid' => $personaid,
        ]);
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'simgen');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/simgen:converse', $context);

        $simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
        $version = conversation::resolve_version($simgen, $params['versionid'], $context);
        $persona = conversation::get_persona($version, conversation::normalise_id($params['personaid']));
        return conversation::end($simgen, $version, $persona, (int)$USER->id);
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return get_conversation::conversation_structure();
    }
}
