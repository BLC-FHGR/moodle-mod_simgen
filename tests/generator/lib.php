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
 * Data generator of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simgen_generator extends testing_module_generator {

    /**
     * Creates an instance (without starting an AI generation).
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array)$record + [
            'subject' => 'Test',
            'durationmin' => 20,
            'savestate' => 1,
            'generate' => 0,
        ];
        return parent::create_instance($record, (array)$options);
    }

    /**
     * Creates a version of a simulation.
     *
     * @param stdClass $simgen instance record
     * @param array $record fields of the version
     * @return stdClass
     */
    public function create_version(stdClass $simgen, array $record = []): stdClass {
        global $DB;
        $version = (object)($record + [
            'simgenid' => $simgen->id,
            'versionnum' => \mod_simgen\local\generator::next_versionnum((int)$simgen->id),
            'codetype' => 'react',
            'sourcecode' => 'function App() { return null; }',
            'css' => '',
            'origin' => 'manual',
            'userid' => 0,
            'timecreated' => time(),
        ]);
        $version->id = $DB->insert_record('simgen_versions', $version);
        return $version;
    }
}
