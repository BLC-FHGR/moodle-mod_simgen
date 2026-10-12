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
 * Let the AI revise a version (change request or error message) or generate it again from the briefing.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\form\revise_form;
use mod_simgen\local\generator;

$id = required_param('id', PARAM_INT);
$versionid = optional_param('version', 0, PARAM_INT);
$prefill = optional_param('prefill', '', PARAM_RAW);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:manage', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$viewurl = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);

$PAGE->set_url(new moodle_url('/mod/simgen/revise.php', ['id' => $cm->id]));
$PAGE->set_title(get_string('revise', 'mod_simgen') . ': ' . format_string($simgen->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();

$versions = [];
foreach ($DB->get_records('simgen_versions', ['simgenid' => $simgen->id], 'versionnum DESC', 'id, versionnum, origin')
        as $v) {
    $versions[$v->id] = get_string('versionshort', 'mod_simgen', $v->versionnum) . ' ('
        . get_string('origin_' . $v->origin, 'mod_simgen') . ')';
}
if (!$versionid || !isset($versions[$versionid])) {
    $versionid = $versions ? (int)array_key_first($versions) : 0;
}

$form = new revise_form(null, ['versions' => $versions]);
$form->set_data([
    'id' => $cm->id,
    'version' => $versionid,
    'changerequest' => $prefill,
]);

if ($form->is_cancelled()) {
    redirect($viewurl);
} else if ($data = $form->get_data()) {
    if (in_array((int)$simgen->status, [generator::STATUS_PENDING, generator::STATUS_GENERATING], true)) {
        redirect($viewurl, get_string('error_alreadyrunning', 'mod_simgen'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
    $baseversion = isset($versions[$data->version]) ? (int)$data->version : 0;
    generator::queue($simgen, (int)$USER->id, trim((string)$data->changerequest), $baseversion);
    redirect($viewurl, get_string('revision_queued', 'mod_simgen'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('revise', 'mod_simgen') . ': ' . format_string($simgen->name));
$form->display();
echo $OUTPUT->footer();
