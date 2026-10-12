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
 * Runs a queued generation immediately (without waiting for cron).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\local\generator;

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('mod/simgen:manage', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$url = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);

if ((int)$simgen->status !== generator::STATUS_PENDING
        && !generator::queue($simgen, (int)$USER->id, (string)$simgen->pendingfeedback, (int)$simgen->pendingbaseversion)) {
    // A generation is already running.
    redirect($url, get_string('error_alreadyrunning', 'mod_simgen'), null, \core\output\notification::NOTIFY_WARNING);
}

// The request can take minutes: release the session and keep running if the user leaves the page.
\core\session\manager::write_close();
ignore_user_abort(true);

generator::run((int)$simgen->id);

// No redirect message: the session is already closed. The activity page shows the new version or the error.
redirect($url);
