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
 * Backup structure of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure step: instance, versions and (with user data) the saved progress.
 */
class backup_simgen_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $simgen = new backup_nested_element('simgen', ['id'], [
            'name', 'intro', 'introformat', 'subject', 'targetgroup', 'priorknowledge', 'difficulty', 'durationmin',
            'socialform', 'simlanguage', 'learningoutcomes', 'simtype', 'feedbackmode', 'requirements', 'content',
            'contentformat', 'savestate', 'completionfinished', 'minheight', 'publishedversion', 'timecreated',
            'timemodified',
        ]);

        $versions = new backup_nested_element('versions');
        $version = new backup_nested_element('version', ['id'], [
            'versionnum', 'codetype', 'sourcecode', 'css', 'personas', 'prompttext', 'rawresponse', 'revisionnote',
            'origin', 'modelinfo', 'userid', 'timecreated',
        ]);

        $states = new backup_nested_element('states');
        $state = new backup_nested_element('state', ['id'], [
            'userid', 'versionid', 'statedata', 'completed', 'rawscore', 'maxscore', 'summary', 'timecreated',
            'timemodified', 'timecompleted',
        ]);

        $conversations = new backup_nested_element('conversations');
        $conversation = new backup_nested_element('conversation', ['id'], [
            'userid', 'versionid', 'personaid', 'messagedata', 'signaldata', 'turncount', 'resetcount', 'timecreated',
            'timemodified',
        ]);

        $simgen->add_child($versions);
        $versions->add_child($version);
        $simgen->add_child($states);
        $states->add_child($state);
        $simgen->add_child($conversations);
        $conversations->add_child($conversation);

        $simgen->set_source_table('simgen', ['id' => backup::VAR_ACTIVITYID]);
        $version->set_source_table('simgen_versions', ['simgenid' => backup::VAR_PARENTID], 'versionnum ASC');
        if ($userinfo) {
            $state->set_source_table('simgen_states', ['simgenid' => backup::VAR_PARENTID]);
            $conversation->set_source_table('simgen_conversations', ['simgenid' => backup::VAR_PARENTID]);
        }

        $version->annotate_ids('user', 'userid');
        $state->annotate_ids('user', 'userid');
        $conversation->annotate_ids('user', 'userid');

        $simgen->annotate_files('mod_simgen', 'intro', null);
        $simgen->annotate_files('mod_simgen', 'content', null);

        return $this->prepare_activity_structure($simgen);
    }
}
