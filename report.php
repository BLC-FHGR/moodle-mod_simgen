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
 * Overview of the progress, results and conversations of the participants.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\local\prompt_builder;
use mod_simgen\local\time_plan;

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
    $DB->delete_records('simgen_conversations', ['simgenid' => $simgen->id, 'userid' => $resetuser]);
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

// Progress records and conversations per user.
$states = [];
foreach ($DB->get_records('simgen_states', ['simgenid' => $simgen->id]) as $state) {
    $states[$state->userid] = $state;
}
$conversations = $DB->get_records_sql(
    'SELECT userid, COUNT(id) AS conversations, SUM(turncount) AS messages, MAX(timemodified) AS lastmodified
       FROM {simgen_conversations}
      WHERE simgenid = ?
   GROUP BY userid', [$simgen->id]);

$userids = array_unique(array_merge(array_keys($states), array_keys($conversations)));
$users = [];
if ($userids) {
    $fields = 'id, deleted, ' . implode(', ', \core_user\fields::get_name_fields());
    $users = $DB->get_records_list('user', 'id', $userids, '', $fields);
}

// Test runs of teachers (incl. draft previews) are not shown.
$managers = get_users_by_capability($context, 'mod/simgen:manage', 'u.id');

$rows = [];
foreach ($users as $user) {
    if (!empty($user->deleted) || isset($managers[$user->id])) {
        continue;
    }
    $state = $states[$user->id] ?? null;
    $conv = $conversations[$user->id] ?? null;
    $rows[] = (object)[
        'user' => $user,
        'state' => $state,
        'conv' => $conv,
        'completed' => $state && !empty($state->completed),
        'lastactivity' => max($state ? (int)$state->timemodified : 0, $conv ? (int)$conv->lastmodified : 0),
    ];
}
usort($rows, fn($a, $b) => [$b->completed, $b->lastactivity] <=> [$a->completed, $a->lastactivity]);

$enrolled = count_enrolled_users($context, 'mod/simgen:view', 0, true);
$completedcount = count(array_filter($rows, fn($r) => $r->completed));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report', 'mod_simgen') . ': ' . format_string($simgen->name));

echo html_writer::tag('p', get_string('report_summary', 'mod_simgen', (object)[
    'started' => count($rows),
    'completed' => $completedcount,
    'enrolled' => $enrolled,
]));

// Working time: planned (briefing) vs. measured active time until the first completion.
$planned = prompt_builder::duration_minutes($simgen);
$times = [];
foreach ($rows as $row) {
    if ($row->completed && !empty($row->state->completedactivetime)) {
        $times[] = (int)$row->state->completedactivetime;
    }
}
if ($times) {
    sort($times);
    $count = count($times);
    $median = $count % 2 ? $times[intdiv($count, 2)] : ($times[$count / 2 - 1] + $times[$count / 2]) / 2;
    $medianminutes = max(1, (int)round($median / 60));
    $info = get_string('report_time', 'mod_simgen', (object)[
        'planned' => $planned ?: '–',
        'median' => $medianminutes,
        'count' => $count,
    ]);
    // Clearly too short with enough data (at least 3 completions): offer the teacher to extend the published version.
    if ($planned && $count >= 3 && $medianminutes < $planned * time_plan::MIN_SHARE
            && has_capability('mod/simgen:manage', $context) && $simgen->publishedversion) {
        $extendurl = new moodle_url('/mod/simgen/revise.php', [
            'id' => $cm->id,
            'version' => $simgen->publishedversion,
            'prefill' => get_string('report_extend_request', 'mod_simgen', (object)[
                'planned' => $planned,
                'median' => $medianminutes,
                'count' => $count,
            ]),
        ]);
        echo $OUTPUT->notification($info . ' ' . get_string('report_time_short', 'mod_simgen') . ' '
            . html_writer::link($extendurl, get_string('report_extend', 'mod_simgen'), ['class' => 'btn btn-sm btn-primary']),
            \core\output\notification::NOTIFY_WARNING, false);
    } else {
        echo html_writer::tag('p', $info);
    }
}

if (!$rows) {
    echo $OUTPUT->notification(get_string('report_empty', 'mod_simgen'), \core\output\notification::NOTIFY_INFO);
} else {
    $canmanage = has_capability('mod/simgen:manage', $context);
    $hasconversations = !empty($conversations);

    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('report_status', 'mod_simgen'),
        get_string('report_score', 'mod_simgen'),
        get_string('report_summarycol', 'mod_simgen'),
        get_string('report_activetime', 'mod_simgen'),
    ];
    if ($hasconversations) {
        $table->head[] = get_string('report_conversations', 'mod_simgen');
    }
    $table->head[] = get_string('report_lastactivity', 'mod_simgen');
    $table->head[] = '';
    $table->attributes['class'] = 'generaltable mod-simgen-report';

    foreach ($rows as $row) {
        $state = $row->state;
        $status = $row->completed
            ? html_writer::span(get_string('report_completed', 'mod_simgen'), 'badge badge-success bg-success')
            : html_writer::span(get_string('report_started', 'mod_simgen'), 'badge badge-secondary bg-secondary');

        $score = '';
        if ($state && $state->rawscore !== null) {
            $score = format_float($state->rawscore, 2, true, true);
            if ($state->maxscore !== null) {
                $score .= ' / ' . format_float($state->maxscore, 2, true, true);
            }
        }
        $summary = $state && $state->summary !== null && $state->summary !== ''
            ? html_writer::tag('div', s($state->summary), ['class' => 'mod-simgen-summary']) : '';

        $reset = '';
        if ($canmanage) {
            $reseturl = new moodle_url($url, ['resetuser' => $row->user->id, 'sesskey' => sesskey()]);
            $reset = html_writer::link($reseturl, get_string('resetuser', 'mod_simgen'), [
                'class' => 'btn btn-sm btn-outline-secondary',
                'data-confirmation' => 'modal',
                'data-confirmation-title-str' => '["resetuser", "mod_simgen"]',
                'data-confirmation-content-str' => '["resetuser_confirm", "mod_simgen"]',
                'data-confirmation-yes-button-str' => '["resetuser", "mod_simgen"]',
                'data-confirmation-destination' => $reseturl->out(false),
            ]);
        }

        // Active time in minutes; until the first completion if completed (that is what the plan is compared with).
        $activetime = '';
        if ($state && !empty($state->activetime)) {
            $activetime = get_string('report_minutes', 'mod_simgen', max(1, (int)round($state->activetime / 60)));
            if (!empty($state->completedactivetime) && $state->completedactivetime != $state->activetime) {
                $activetime = get_string('report_minutes', 'mod_simgen',
                    max(1, (int)round($state->completedactivetime / 60))) . ' '
                    . html_writer::span('(' . $activetime . ')', 'text-muted small',
                        ['title' => get_string('report_activetime_total', 'mod_simgen')]);
            }
        }

        $cells = [fullname($row->user), $status, $score, $summary, $activetime];
        if ($hasconversations) {
            $cells[] = $row->conv
                ? html_writer::link(new moodle_url('/mod/simgen/conversation.php', ['id' => $cm->id, 'userid' => $row->user->id]),
                    get_string('report_conversationlink', 'mod_simgen', (int)$row->conv->messages))
                : '';
        }
        $cells[] = $row->lastactivity ? userdate($row->lastactivity, get_string('strftimedatetimeshort', 'langconfig')) : '';
        $cells[] = $reset;
        $table->data[] = $cells;
    }
    echo html_writer::table($table);
}

echo html_writer::link(new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]), get_string('back'),
    ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
