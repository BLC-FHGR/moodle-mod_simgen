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
 * Overview of the progress and results of the participants.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$resetuser = optional_param('resetuser', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:viewreports', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$url = new moodle_url('/mod/simgen/report.php', ['id' => $cm->id]);

if ($resetuser) {
    require_sesskey();
    require_capability('mod/simgen:manage', $context);
    $DB->delete_records('simgen_states', ['simgenid' => $simgen->id, 'userid' => $resetuser]);
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm)) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $resetuser);
    }
    redirect($url, get_string('resetuserdone', 'mod_simgen'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('report', 'mod_simgen') . ': ' . format_string($simgen->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();

$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$records = $DB->get_records_sql(
    "SELECT s.*, {$userfields}
       FROM {simgen_states} s
       JOIN {user} u ON u.id = s.userid
      WHERE s.simgenid = :simgenid AND u.deleted = 0
   ORDER BY s.completed DESC, s.timemodified DESC",
    ['simgenid' => $simgen->id]);

$enrolled = count_enrolled_users($context, 'mod/simgen:savestate', 0, true);
$started = count($records);
$completedcount = count(array_filter($records, fn($r) => !empty($r->completed)));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_simgen') . ': ' . format_string($simgen->name));

echo html_writer::tag('p', get_string('report_summary', 'mod_simgen', (object)[
    'started' => $started,
    'completed' => $completedcount,
    'enrolled' => $enrolled,
]));

if (!$records) {
    echo $OUTPUT->notification(get_string('report_empty', 'mod_simgen'), \core\output\notification::NOTIFY_INFO);
} else {
    $canmanage = has_capability('mod/simgen:manage', $context);
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('report_status', 'mod_simgen'),
        get_string('report_score', 'mod_simgen'),
        get_string('report_summarycol', 'mod_simgen'),
        get_string('report_lastactivity', 'mod_simgen'),
        '',
    ];
    $table->attributes['class'] = 'generaltable mod-simgen-report';
    foreach ($records as $r) {
        $status = $r->completed
            ? html_writer::span(get_string('report_completed', 'mod_simgen'), 'badge badge-success bg-success')
            : html_writer::span(get_string('report_started', 'mod_simgen'), 'badge badge-secondary bg-secondary');
        $score = '';
        if ($r->rawscore !== null) {
            $score = format_float($r->rawscore, 2, true, true);
            if ($r->maxscore !== null) {
                $score .= ' / ' . format_float($r->maxscore, 2, true, true);
            }
        }
        $reset = '';
        if ($canmanage) {
            $reseturl = new moodle_url($url, ['resetuser' => $r->userid, 'sesskey' => sesskey()]);
            $reset = html_writer::link($reseturl, get_string('resetuser', 'mod_simgen'), [
                'class' => 'btn btn-sm btn-outline-secondary',
                'data-confirmation' => 'modal',
                'data-confirmation-title-str' => '["resetuser", "mod_simgen"]',
                'data-confirmation-content-str' => '["resetuser_confirm", "mod_simgen"]',
                'data-confirmation-yes-button-str' => '["resetuser", "mod_simgen"]',
                'data-confirmation-destination' => $reseturl->out(false),
            ]);
        }
        $table->data[] = [
            fullname($r),
            $status,
            $score,
            $r->summary !== null && $r->summary !== '' ? html_writer::tag('div', s($r->summary),
                ['class' => 'mod-simgen-summary']) : '',
            userdate($r->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
            $reset,
        ];
    }
    echo html_writer::table($table);
}

echo html_writer::link(new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]), get_string('back'),
    ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
