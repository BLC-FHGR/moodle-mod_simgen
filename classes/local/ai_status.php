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

use stdClass;

/**
 * Checks whether the AI purposes used by the plugin are available (via local_ai_manager).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_status {

    /**
     * Purposes that are not available for a user, with the reason given by local_ai_manager.
     *
     * @param stdClass $user
     * @param int $contextid
     * @param string[] $purposes
     * @return array purpose => error message ('*' = AI not available at all)
     */
    public static function unavailable_purposes(stdClass $user, int $contextid, array $purposes): array {
        $class = '\local_ai_manager\ai_manager_utils';
        if (!class_exists($class) || !method_exists($class, 'get_ai_config')) {
            return [];
        }
        try {
            $config = $class::get_ai_config($user, $contextid, null, array_values(array_unique($purposes)));
        } catch (\Throwable $e) {
            debugging('mod_simgen: get_ai_config failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return [];
        }

        $result = [];
        if (($config['availability']['available'] ?? 'available') !== 'available') {
            $result['*'] = (string)($config['availability']['errormessage'] ?? '');
        }
        foreach ($config['purposes'] ?? [] as $entry) {
            if (!empty($entry['purpose']) && ($entry['available'] ?? 'available') !== 'available') {
                $result[$entry['purpose']] = (string)($entry['errormessage'] ?? '');
            }
        }
        return $result;
    }

    /**
     * Display name of a purpose.
     *
     * @param string $purpose
     * @return string
     */
    public static function purpose_name(string $purpose): string {
        if (get_string_manager()->string_exists('pluginname', 'aipurpose_' . $purpose)) {
            return get_string('pluginname', 'aipurpose_' . $purpose);
        }
        return $purpose;
    }
}
