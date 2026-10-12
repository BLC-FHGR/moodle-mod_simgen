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

namespace mod_simgen\event;

/**
 * A user completed a simulation.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simulation_completed extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'simgen_states';
    }

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_simulation_completed', 'mod_simgen');
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' completed the simulation with course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Related URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/simgen/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Mapping for restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'simgen_states', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
