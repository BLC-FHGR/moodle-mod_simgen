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
 * Edit the code of a simulation manually. Every save creates a new (unpublished) version.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\form\editcode_form;
use mod_simgen\local\code_checker;
use mod_simgen\local\conversation;
use mod_simgen\local\generator;

$id = required_param('id', PARAM_INT);
$versionid = optional_param('version', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:manage', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$viewurl = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);

if ($versionid) {
    $version = $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id], '*', MUST_EXIST);
} else {
    $version = $DB->get_record_sql('SELECT * FROM {simgen_versions} WHERE simgenid = ? ORDER BY versionnum DESC',
        [$simgen->id], IGNORE_MULTIPLE);
}

$PAGE->set_url(new moodle_url('/mod/simgen/editcode.php', ['id' => $cm->id]));
$PAGE->set_title(get_string('editcode', 'mod_simgen') . ': ' . format_string($simgen->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();

$form = new editcode_form();
$form->set_data([
    'id' => $cm->id,
    'version' => $version ? $version->id : 0,
    'codetype' => $version ? $version->codetype : 'react',
    'sourcecode' => $version ? $version->sourcecode : "function App() {\n  return <div className=\"sim-app\">Hallo</div>;\n}\n",
    'css' => $version ? $version->css : '',
    'personas' => $version && ($personas = conversation::from_version($version))
        ? json_encode(['personas' => array_values($personas)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '',
    'revisionnote' => '',
]);

if ($form->is_cancelled()) {
    redirect($viewurl);
} else if ($data = $form->get_data()) {
    $personas = $data->codetype === 'react' ? conversation::normalise_personas((string)$data->personas) : [];
    $newversion = (object)[
        'simgenid' => $simgen->id,
        'versionnum' => generator::next_versionnum((int)$simgen->id),
        'codetype' => $data->codetype === 'html' ? 'html' : 'react',
        'sourcecode' => $data->sourcecode,
        'css' => $data->codetype === 'html' ? '' : (string)$data->css,
        'personas' => $personas ? json_encode(['personas' => array_values($personas)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        'prompttext' => null,
        'rawresponse' => null,
        'revisionnote' => trim((string)$data->revisionnote) ?: get_string('origin_manual', 'mod_simgen'),
        'origin' => 'manual',
        'modelinfo' => null,
        'userid' => $USER->id,
        'timecreated' => time(),
    ];
    // Manual code is never repaired automatically, but the teacher sees the findings of the static checks.
    $issues = code_checker::check($simgen, $newversion);
    $newversion->checkissues = $issues ? json_encode($issues, JSON_UNESCAPED_UNICODE) : null;
    $newid = $DB->insert_record('simgen_versions', $newversion);
    if ((int)$simgen->status === generator::STATUS_NONE) {
        $DB->set_field('simgen', 'status', generator::STATUS_READY, ['id' => $simgen->id]);
    }
    redirect(new moodle_url($viewurl, ['version' => $newid]), get_string('versionsaved', 'mod_simgen'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editcode', 'mod_simgen') . ': ' . format_string($simgen->name));
$form->display();
echo $OUTPUT->footer();
