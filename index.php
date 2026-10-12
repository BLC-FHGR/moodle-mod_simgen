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
 * Lists all simulations of a course.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$coursecontext = context_course::instance($course->id);
\mod_simgen\event\course_module_instance_list_viewed::create(['context' => $coursecontext])->trigger();

$PAGE->set_url(new moodle_url('/mod/simgen/index.php', ['id' => $course->id]));
$PAGE->set_title(get_string('modulenameplural', 'mod_simgen'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_simgen'));

$simulations = get_all_instances_in_course('simgen', $course);
if (!$simulations) {
    notice(get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_simgen')),
        new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name'), get_string('subject', 'mod_simgen'), get_string('durationmin', 'mod_simgen')];
foreach ($simulations as $simulation) {
    $link = html_writer::link(new moodle_url('/mod/simgen/view.php', ['id' => $simulation->coursemodule]),
        format_string($simulation->name), $simulation->visible ? [] : ['class' => 'dimmed']);
    $table->data[] = [$link, format_string($simulation->subject ?? ''), (int)($simulation->durationmin ?? 0)];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
