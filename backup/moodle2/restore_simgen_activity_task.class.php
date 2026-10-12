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

/**
 * Restore task of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simgen/backup/moodle2/restore_simgen_stepslib.php');

/**
 * Restore task of mod_simgen.
 */
class restore_simgen_activity_task extends restore_activity_task {

    /**
     * No specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines the restore steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_simgen_activity_structure_step('simgen_structure', 'simgen.xml'));
    }

    /**
     * Contents to decode.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('simgen', ['intro', 'content'], 'simgen')];
    }

    /**
     * Decoding rules for links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('SIMGENVIEWBYID', '/mod/simgen/view.php?id=$1', 'course_module'),
            new restore_decode_rule('SIMGENINDEX', '/mod/simgen/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Log rules (legacy logs are not used).
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Course log rules (legacy logs are not used).
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
