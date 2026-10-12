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
 * Transcript of the live conversations of a participant (for teachers).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\local\conversation;

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:viewreports', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$user = core_user::get_user($userid, '*', MUST_EXIST);
$reporturl = new moodle_url('/mod/simgen/report.php', ['id' => $cm->id]);

$PAGE->set_url(new moodle_url('/mod/simgen/conversation.php', ['id' => $cm->id, 'userid' => $userid]));
$PAGE->set_title(get_string('conversation_transcript', 'mod_simgen', fullname($user)));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();
$PAGE->navbar->add(get_string('report', 'mod_simgen'), $reporturl);

$records = $DB->get_records('simgen_conversations', ['simgenid' => $simgen->id, 'userid' => $userid], 'timecreated ASC');
$versions = $DB->get_records('simgen_versions', ['simgenid' => $simgen->id], '', 'id, versionnum, codetype, personas');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('conversation_transcript', 'mod_simgen', fullname($user)));

if (!$records) {
    echo $OUTPUT->notification(get_string('conversation_none', 'mod_simgen'), \core\output\notification::NOTIFY_INFO);
}

foreach ($records as $record) {
    $version = $versions[$record->versionid] ?? null;
    $personas = $version ? conversation::from_version($version) : [];
    $persona = $personas[$record->personaid] ?? null;
    $name = $persona ? $persona['name'] : $record->personaid;

    $heading = s($name) . ($persona && $persona['role'] !== '' ? ' – ' . s($persona['role']) : '');
    if ($version) {
        $heading .= ' ' . html_writer::span('(' . get_string('versionshort', 'mod_simgen', $version->versionnum) . ')',
            'small text-muted');
    }
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', $heading, ['class' => 'h5']);

    $lines = [];
    if ($persona && $persona['opening'] !== '') {
        $lines[] = ['role' => 'persona', 'text' => $persona['opening'], 'time' => 0];
    }
    $stored = json_decode((string)$record->messagedata, true);
    foreach (is_array($stored) ? $stored : [] as $message) {
        $lines[] = $message;
    }
    foreach ($lines as $message) {
        $isuser = ($message['role'] ?? '') === 'user';
        $speaker = $isuser ? fullname($user) : $name;
        $time = !empty($message['time']) ? ' ' . html_writer::span(userdate($message['time'],
            get_string('strftimetime', 'langconfig')), 'small text-muted') : '';
        echo html_writer::div(
            html_writer::tag('strong', s($speaker)) . $time .
            html_writer::div(nl2br(s((string)($message['text'] ?? ''))), 'mod-simgen-message-text'),
            'mod-simgen-message ' . ($isuser ? 'mod-simgen-message-user' : 'mod-simgen-message-persona'));
    }

    $signals = json_decode((string)$record->signaldata, true);
    if (is_array($signals) && $signals) {
        $items = [];
        foreach ($signals as $key => $value) {
            $items[] = s($key) . ': ' . s(is_bool($value) ? ($value ? 'true' : 'false') : (string)$value);
        }
        echo html_writer::div(get_string('conversation_signals', 'mod_simgen') . ' ' . implode(', ', $items),
            'small text-muted mt-2');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::link($reporturl, get_string('back'), ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
