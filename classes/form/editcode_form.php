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
 * Form to edit the code of a version manually (saved as a new version).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editcode_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $codeattributes = [
            'rows' => 30,
            'cols' => 100,
            'wrap' => 'off',
            'spellcheck' => 'false',
            'class' => 'mod-simgen-code',
        ];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'version');
        $mform->setType('version', PARAM_INT);

        $mform->addElement('static', 'editcodehelp', '', get_string('editcode_help', 'mod_simgen'));

        $mform->addElement('select', 'codetype', get_string('codetype', 'mod_simgen'), [
            'react' => get_string('codetype_react', 'mod_simgen'),
            'html' => get_string('codetype_html', 'mod_simgen'),
        ]);

        $mform->addElement('textarea', 'sourcecode', get_string('sourcecode', 'mod_simgen'), $codeattributes);
        $mform->setType('sourcecode', PARAM_RAW);
        $mform->addRule('sourcecode', null, 'required', null, 'client');

        $mform->addElement('textarea', 'css', get_string('css', 'mod_simgen'), ['rows' => 12] + $codeattributes);
        $mform->setType('css', PARAM_RAW);
        $mform->hideIf('css', 'codetype', 'eq', 'html');

        $mform->addElement('textarea', 'personas', get_string('personas', 'mod_simgen'), ['rows' => 14] + $codeattributes);
        $mform->setType('personas', PARAM_RAW);
        $mform->addHelpButton('personas', 'personas', 'mod_simgen');
        $mform->hideIf('personas', 'codetype', 'eq', 'html');

        $mform->addElement('text', 'revisionnote', get_string('revisionnote', 'mod_simgen'), ['size' => 80]);
        $mform->setType('revisionnote', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savenewversion', 'mod_simgen'));
    }

    /**
     * Validates the persona definitions.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (($data['codetype'] ?? '') === 'react' && trim($data['personas'] ?? '') !== '') {
            try {
                if (!\mod_simgen\local\conversation::normalise_personas($data['personas'])) {
                    $errors['personas'] = get_string('error_nopersonas', 'mod_simgen');
                }
            } catch (\moodle_exception $e) {
                $errors['personas'] = $e->getMessage();
            }
        }
        return $errors;
    }
}
