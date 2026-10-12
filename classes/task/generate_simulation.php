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

namespace mod_simgen\task;

/**
 * Adhoc task generating a simulation in the background.
 *
 * The task runs as the requesting teacher (set_userid), so local_ai_manager applies the
 * teacher's tenant, role and quota.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_simulation extends \core\task\adhoc_task {

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_generate', 'mod_simgen');
    }

    /**
     * Runs the generation. Errors are stored on the instance instead of being thrown,
     * so a failing AI request is not retried automatically (which would consume quota).
     */
    public function execute() {
        $data = $this->get_custom_data();
        \mod_simgen\local\generator::run((int)$data->simgenid, (int)$data->requested);
    }
}
