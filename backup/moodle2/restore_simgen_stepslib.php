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
 * Restore structure of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_simgen\local\generator;

/**
 * Restore structure step.
 */
class restore_simgen_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('simgen', '/activity/simgen'),
            new restore_path_element('simgen_version', '/activity/simgen/versions/version'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('simgen_state', '/activity/simgen/states/state');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the instance.
     *
     * @param array $data
     */
    protected function process_simgen($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->status = generator::STATUS_NONE;
        $data->statusmessage = '';
        $data->genrequested = 0;
        $data->genstarted = 0;
        $data->pendingfeedback = '';
        $data->pendingbaseversion = 0;
        // Remapped in after_execute().
        $data->publishedversion = (int)($data->publishedversion ?? 0);
        $newid = $DB->insert_record('simgen', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restores a version.
     *
     * @param array $data
     */
    protected function process_simgen_version($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->simgenid = $this->get_new_parentid('simgen');
        $data->userid = (int)$this->get_mappingid('user', $data->userid, 0);
        $newid = $DB->insert_record('simgen_versions', $data);
        $this->set_mapping('simgen_version', $oldid, $newid);
    }

    /**
     * Restores the progress of a user.
     *
     * @param array $data
     */
    protected function process_simgen_state($data) {
        global $DB;
        $data = (object)$data;
        $data->simgenid = $this->get_new_parentid('simgen');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }
        $data->versionid = (int)$this->get_mappingid('simgen_version', $data->versionid, 0);
        $DB->insert_record('simgen_states', $data);
    }

    /**
     * Restores files and remaps the published version.
     */
    protected function after_execute() {
        global $DB;
        $this->add_related_files('mod_simgen', 'intro', null);
        $this->add_related_files('mod_simgen', 'content', null);

        $simgenid = $this->task->get_activityid();
        $simgen = $DB->get_record('simgen', ['id' => $simgenid], 'id, publishedversion', MUST_EXIST);
        $published = $simgen->publishedversion
            ? (int)$this->get_mappingid('simgen_version', $simgen->publishedversion, 0) : 0;
        $hasversions = $DB->record_exists('simgen_versions', ['simgenid' => $simgenid]);
        $DB->update_record('simgen', (object)[
            'id' => $simgenid,
            'publishedversion' => $published,
            'status' => $hasversions ? generator::STATUS_READY : generator::STATUS_NONE,
        ]);
    }
}
