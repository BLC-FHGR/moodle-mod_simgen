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
 * Schedule of a version (phases and tasks with minutes), planned by the AI together with the code.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_plan {

    /** @var int maximum number of entries */
    const MAX_ENTRIES = 30;

    /** @var float the plan must cover at least this share of the working time (otherwise: error) */
    const MIN_SHARE = 0.75;

    /** @var float a plan longer than this share of the working time gets a warning */
    const MAX_SHARE = 1.5;

    /**
     * Normalises a schedule from JSON or an array.
     *
     * @param mixed $data JSON string, ['plan' => [...]] or the list itself
     * @return array list of ['phase' => string, 'activity' => string, 'minutes' => int]; [] if invalid
     */
    public static function normalise($data): array {
        if (is_string($data)) {
            $data = json_decode(trim($data), true);
        }
        if (!is_array($data)) {
            return [];
        }
        $list = array_key_exists('plan', $data) ? $data['plan'] : $data;
        if (!is_array($list)) {
            return [];
        }
        $str = fn($value): string => is_scalar($value) ? \core_text::substr(trim((string)$value), 0, 500) : '';
        $plan = [];
        foreach ($list as $entry) {
            if (!is_array($entry) || count($plan) >= self::MAX_ENTRIES) {
                continue;
            }
            $minutes = is_numeric($entry['minutes'] ?? null) ? (int)round((float)$entry['minutes']) : 0;
            $phase = $str($entry['phase'] ?? '');
            $activity = $str($entry['activity'] ?? '');
            if ($minutes <= 0 || $minutes > 240 || ($phase === '' && $activity === '')) {
                continue;
            }
            $plan[] = ['phase' => $phase, 'activity' => $activity, 'minutes' => $minutes];
        }
        return $plan;
    }

    /**
     * Schedule of a version.
     *
     * @param stdClass $version
     * @return array
     */
    public static function from_version(stdClass $version): array {
        return empty($version->timeplan) ? [] : self::normalise((string)$version->timeplan);
    }

    /**
     * Total minutes.
     *
     * @param array $plan
     * @return int
     */
    public static function total(array $plan): int {
        return (int)array_sum(array_column($plan, 'minutes'));
    }

    /**
     * JSON of a schedule (as stored and as shown to the AI).
     *
     * @param array $plan
     * @param bool $pretty
     * @return string
     */
    public static function to_json(array $plan, bool $pretty = false): string {
        return json_encode(['plan' => array_values($plan)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            | ($pretty ? JSON_PRETTY_PRINT : 0));
    }
}
