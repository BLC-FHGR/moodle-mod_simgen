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

namespace mod_simgen\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for an AI revision of a version.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class revise_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $versions = $this->_customdata['versions'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('static', 'revisehelp', '', get_string('revise_help', 'mod_simgen'));

        $options = [0 => get_string('revise_fromscratch', 'mod_simgen')] + $versions;
        $mform->addElement('select', 'version', get_string('revise_base', 'mod_simgen'), $options);
        $mform->setType('version', PARAM_INT);

        $mform->addElement('textarea', 'changerequest', get_string('changerequest', 'mod_simgen'),
            ['rows' => 10, 'cols' => 80, 'placeholder' => get_string('changerequest_placeholder', 'mod_simgen')]);
        $mform->setType('changerequest', PARAM_RAW);
        $mform->addHelpButton('changerequest', 'changerequest', 'mod_simgen');

        $this->add_action_buttons(true, get_string('revise_submit', 'mod_simgen'));
    }

    /**
     * Validation: a revision needs a change request.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['version']) && trim($data['changerequest'] ?? '') === '') {
            $errors['changerequest'] = get_string('required');
        }
        return $errors;
    }
}
