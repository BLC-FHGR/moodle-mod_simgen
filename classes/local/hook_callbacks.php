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
        $hook->set_component_displayname('mod_simgen', get_string('pluginname', 'mod_simgen'));
        $usages = [
            get_config('mod_simgen', 'purpose') ?: 'singleprompt' => 'purposeplacedescription',
            get_config('mod_simgen', 'conversationpurpose') ?: 'chat' => 'purposeplacedescription_conversation',
        ];
        if (count($usages) === 1) {
            // Same purpose for both: declare both places.
            $purpose = array_key_first($usages);
            $usages = [$purpose => 'purposeplacedescription'];
            self::declare_usage($hook, $purpose, 'purposeplacedescription_conversation');
        }
        foreach ($usages as $purpose => $stringkey) {
            self::declare_usage($hook, $purpose, $stringkey);
        }
    }

    /**
     * Declares one place where a purpose is used.
     *
     * @param \local_ai_manager\hook\purpose_usage $hook
     * @param string $purpose
     * @param string $stringkey language string describing the place
     * @return void
     */
    private static function declare_usage(\local_ai_manager\hook\purpose_usage $hook, string $purpose, string $stringkey): void {
        try {
            $hook->add_purpose_usage_description($purpose, 'mod_simgen', get_string($stringkey, 'mod_simgen'));
        } catch (\coding_exception $e) {
            // Configured purpose is not installed - nothing to declare.
            debugging('mod_simgen: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
