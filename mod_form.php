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
 * Activity settings form: the briefing mask for teachers.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/simgen/lib.php');

use mod_simgen\local\options;
use mod_simgen\local\prompt_builder;

/**
 * Settings form of mod_simgen.
 */
class mod_simgen_mod_form extends moodleform_mod {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $isnew = empty($this->_instance);

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('static', 'aihint', '', get_string('form_aihint', 'mod_simgen'));

        // Didactic frame.
        $mform->addElement('header', 'hdr_didactics', get_string('hdr_didactics', 'mod_simgen'));
        $mform->setExpanded('hdr_didactics');

        $mform->addElement('text', 'subject', get_string('subject', 'mod_simgen'), ['size' => 64]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('subject', 'subject', 'mod_simgen');

        $mform->addElement('textarea', 'targetgroup', get_string('targetgroup', 'mod_simgen'), ['rows' => 2, 'cols' => 60]);
        $mform->setType('targetgroup', PARAM_TEXT);
        $mform->addRule('targetgroup', null, 'required', null, 'client');
        $mform->addHelpButton('targetgroup', 'targetgroup', 'mod_simgen');

        $mform->addElement('textarea', 'priorknowledge', get_string('priorknowledge', 'mod_simgen'),
            ['rows' => 2, 'cols' => 60]);
        $mform->setType('priorknowledge', PARAM_TEXT);
        $mform->addHelpButton('priorknowledge', 'priorknowledge', 'mod_simgen');

        $mform->addElement('select', 'difficulty', get_string('difficulty', 'mod_simgen'), options::difficulties());
        $mform->setDefault('difficulty', 'intermediate');

        $maxduration = prompt_builder::max_duration();
        $mform->addElement('text', 'durationmin', get_string('durationmin_max', 'mod_simgen', $maxduration),
            ['size' => 5]);
        $mform->setType('durationmin', PARAM_INT);
        $mform->setDefault('durationmin', min(20, $maxduration));
        $mform->addRule('durationmin', null, 'required', null, 'client');
        $mform->addHelpButton('durationmin', 'durationmin', 'mod_simgen');

        $mform->addElement('select', 'socialform', get_string('socialform', 'mod_simgen'), options::socialforms());
        $mform->setDefault('socialform', 'individual');

        $mform->addElement('select', 'simlanguage', get_string('simlanguage', 'mod_simgen'), options::languages());
        $mform->setDefault('simlanguage', 'de');

        // Learning outcomes.
        $mform->addElement('header', 'hdr_outcomes', get_string('hdr_outcomes', 'mod_simgen'));
        $mform->setExpanded('hdr_outcomes');
        $mform->addElement('textarea', 'learningoutcomes', get_string('learningoutcomes', 'mod_simgen'),
            ['rows' => 5, 'cols' => 60, 'placeholder' => get_string('learningoutcomes_placeholder', 'mod_simgen')]);
        $mform->setType('learningoutcomes', PARAM_TEXT);
        $mform->addRule('learningoutcomes', null, 'required', null, 'client');
        $mform->addHelpButton('learningoutcomes', 'learningoutcomes', 'mod_simgen');

        // Simulation design.
        $mform->addElement('header', 'hdr_simulation', get_string('hdr_simulation', 'mod_simgen'));
        $mform->setExpanded('hdr_simulation');

        $mform->addElement('select', 'simtype', get_string('simtype', 'mod_simgen'), options::simtypes());
        $mform->setDefault('simtype', 'auto');
        $mform->addHelpButton('simtype', 'simtype', 'mod_simgen');

        $mform->addElement('select', 'feedbackmode', get_string('feedbackmode', 'mod_simgen'), options::feedbackmodes());
        $mform->setDefault('feedbackmode', 'immediate');

        $mform->addElement('textarea', 'requirements', get_string('requirements', 'mod_simgen'),
            ['rows' => 4, 'cols' => 60, 'placeholder' => get_string('requirements_placeholder', 'mod_simgen')]);
        $mform->setType('requirements', PARAM_TEXT);
        $mform->addHelpButton('requirements', 'requirements', 'mod_simgen');

        // Content.
        $mform->addElement('header', 'hdr_content', get_string('hdr_content', 'mod_simgen'));
        $mform->setExpanded('hdr_content');
        $mform->addElement('editor', 'contenteditor', get_string('content', 'mod_simgen'), ['rows' => 10],
            ['maxfiles' => 0, 'noclean' => false, 'context' => $this->context]);
        $mform->setType('contenteditor', PARAM_RAW);
        $mform->addHelpButton('contenteditor', 'content', 'mod_simgen');

        $mform->addElement('filemanager', 'contentfiles', get_string('contentfiles', 'mod_simgen'), null,
            simgen_get_content_file_options());
        $mform->addHelpButton('contentfiles', 'contentfiles', 'mod_simgen');

        // Player, progress and generation.
        $mform->addElement('header', 'hdr_player', get_string('hdr_player', 'mod_simgen'));

        $mform->addElement('advcheckbox', 'savestate', get_string('savestate', 'mod_simgen'),
            get_string('savestate_label', 'mod_simgen'));
        $mform->setDefault('savestate', 1);
        $mform->addHelpButton('savestate', 'savestate', 'mod_simgen');

        $mform->addElement('text', 'minheight', get_string('minheight', 'mod_simgen'), ['size' => 5]);
        $mform->setType('minheight', PARAM_INT);
        $mform->setDefault('minheight', 600);
        $mform->setAdvanced('minheight');

        $mform->addElement('advcheckbox', 'generate', get_string('generate', 'mod_simgen'),
            get_string($isnew ? 'generate_label_new' : 'generate_label_update', 'mod_simgen'));
        $mform->setDefault('generate', $isnew ? 1 : 0);
        $mform->addHelpButton('generate', 'generate', 'mod_simgen');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepares editor and file manager defaults.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        $defaultvalues['contenteditor'] = [
            'text' => $defaultvalues['content'] ?? '',
            'format' => $defaultvalues['contentformat'] ?? FORMAT_HTML,
        ];

        $draftitemid = file_get_submitted_draft_itemid('contentfiles');
        $contextid = !empty($this->_cm) ? context_module::instance($this->_cm->id)->id : null;
        file_prepare_draft_area($draftitemid, $contextid, 'mod_simgen', 'content', 0, simgen_get_content_file_options());
        $defaultvalues['contentfiles'] = $draftitemid;
    }

    /**
     * Validates the briefing.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $maxduration = prompt_builder::max_duration();
        if (!isset($data['durationmin']) || $data['durationmin'] < 1 || $data['durationmin'] > $maxduration) {
            $errors['durationmin'] = get_string('error_duration', 'mod_simgen', $maxduration);
        }
        if (core_text::strlen(trim($data['learningoutcomes'] ?? '')) < 10) {
            $errors['learningoutcomes'] = get_string('error_outcomes', 'mod_simgen');
        }
        if (isset($data['minheight']) && ($data['minheight'] < 300 || $data['minheight'] > 3000)) {
            $errors['minheight'] = get_string('error_minheight', 'mod_simgen');
        }
        return $errors;
    }

    /**
     * Adds the custom completion rule.
     *
     * @return array names of the added elements
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $name = 'completionfinished' . $this->get_suffix();
        $mform->addElement('advcheckbox', $name, get_string('completionfinished', 'mod_simgen'),
            get_string('completionfinished_label', 'mod_simgen'));
        $mform->addHelpButton($name, 'completionfinished', 'mod_simgen');
        return [$name];
    }

    /**
     * Whether the custom completion rule is enabled.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionfinished' . $this->get_suffix()]);
    }

    /**
     * Resets the custom completion rule when automatic completion is switched off.
     *
     * @param stdClass $data
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? COMPLETION_TRACKING_NONE;
            if ($completion != COMPLETION_TRACKING_AUTOMATIC) {
                $data->{'completionfinished' . $suffix} = 0;
            }
        }
    }
}
