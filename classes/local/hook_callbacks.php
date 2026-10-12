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

/**
 * Hook callbacks.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Declares the AI purpose used by this plugin (shown to users by local_ai_manager).
     *
     * @param \local_ai_manager\hook\purpose_usage $hook
     * @return void
     */
    public static function handle_purpose_usage(\local_ai_manager\hook\purpose_usage $hook): void {
        $purpose = get_config('mod_simgen', 'purpose') ?: 'singleprompt';
        try {
            $hook->set_component_displayname('mod_simgen', get_string('pluginname', 'mod_simgen'));
            $hook->add_purpose_usage_description($purpose, 'mod_simgen',
                get_string('purposeplacedescription', 'mod_simgen'));
        } catch (\coding_exception $e) {
            // Configured purpose is not installed - nothing to declare.
            debugging('mod_simgen: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
