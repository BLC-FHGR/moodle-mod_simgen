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

namespace mod_simgen\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rule: the simulation reported its completion (SimBridge.complete()).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * State of a custom rule.
     *
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        $completed = $DB->record_exists('simgen_states', [
            'simgenid' => $this->cm->instance,
            'userid' => $this->userid,
            'completed' => 1,
        ]);
        return $completed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Defined custom rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionfinished'];
    }

    /**
     * Descriptions of the rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionfinished' => get_string('completiondetail:finished', 'mod_simgen')];
    }

    /**
     * Sort order of the completion conditions.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionfinished'];
    }
}
