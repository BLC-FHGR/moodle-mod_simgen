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
 * Form to edit the facilitator guide of a version (Markdown).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guide_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'version');
        $mform->setType('version', PARAM_INT);
        $mform->addElement('hidden', 'action', 'edit');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('static', 'guideedithelp', '', get_string('guide_edit_help', 'mod_simgen'));

        $mform->addElement('textarea', 'guide', get_string('guide', 'mod_simgen'), [
            'rows' => 30,
            'cols' => 100,
            'class' => 'mod-simgen-code mod-simgen-wrap',
        ]);
        $mform->setType('guide', PARAM_RAW);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
