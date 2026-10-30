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
 * Activity page: the simulation for students, plus status, versions and publishing for teachers.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_simgen\external\report_check;
use mod_simgen\local\ai_status;
use mod_simgen\local\code_checker;
use mod_simgen\local\generator;
use mod_simgen\local\guide_generator;
use mod_simgen\local\html_builder;
use mod_simgen\local\prompt_builder;
use mod_simgen\local\time_plan;

$id = required_param('id', PARAM_INT);
$versionid = optional_param('version', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simgen:view', $context);

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$canmanage = has_capability('mod/simgen:manage', $context);
$url = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);

// Teacher actions.
if ($canmanage && $action !== '') {
    require_sesskey();
    if ($action === 'publish') {
        $version = $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id], '*', MUST_EXIST);
        $DB->update_record('simgen', (object)['id' => $simgen->id, 'publishedversion' => $version->id,
            'timemodified' => time()]);
        // The guide describes the finished simulation: create it now if the version has none yet.
        $message = get_string('published', 'mod_simgen', $version->versionnum);
        if (guide_generator::queue_on_publish($version, (int)$USER->id)) {
            $message .= ' ' . get_string('guide_queuedonpublish', 'mod_simgen');
        }
        redirect(new moodle_url($url, ['version' => $version->id]), $message, null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'unpublish') {
        $DB->set_field('simgen', 'publishedversion', 0, ['id' => $simgen->id]);
        redirect(new moodle_url($url, ['version' => $versionid]), get_string('unpublished', 'mod_simgen'), null,
            \core\output\notification::NOTIFY_INFO);
    } else if ($action === 'retry') {
        // Repeat the last request (fresh generation or revision).
        if (!generator::queue($simgen, (int)$USER->id, (string)$simgen->pendingfeedback, (int)$simgen->pendingbaseversion)) {
            redirect($url, get_string('error_alreadyrunning', 'mod_simgen'), null,
                \core\output\notification::NOTIFY_WARNING);
        }
        redirect($url);
    }
}

simgen_view($simgen, $course, $cm, $context);
generator::check_stale($simgen);

$PAGE->set_url($versionid ? new moodle_url($url, ['version' => $versionid]) : $url);
$PAGE->set_title(format_string($simgen->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->add_body_class('mod-simgen-view');

// Which version is shown?
$versions = [];
if ($canmanage) {
    $versions = $DB->get_records('simgen_versions', ['simgenid' => $simgen->id], 'versionnum DESC',
        'id, versionnum, origin, codetype, revisionnote, modelinfo, userid, timecreated');
    if (!$versionid || !isset($versions[$versionid])) {
        $versionid = $versions ? (int)array_key_first($versions) : 0;
    }
} else {
    $versionid = (int)$simgen->publishedversion;
}
$preview = $canmanage && $versionid && $versionid != $simgen->publishedversion;

echo $OUTPUT->header();

// Management panel.
if ($canmanage) {
    $status = (int)$simgen->status;
    $hasversions = !empty($versions);
    $sesskey = sesskey();

    $panel = [
        'cmid' => $cm->id,
        'sesskey' => $sesskey,
        'pending' => $status === generator::STATUS_PENDING,
        'generating' => $status === generator::STATUS_GENERATING,
        'error' => $status === generator::STATUS_ERROR,
        'none' => $status === generator::STATUS_NONE && !$hasversions,
        'statusmessage' => (string)$simgen->statusmessage,
        'generatenowurl' => (new moodle_url('/mod/simgen/generate.php', ['id' => $cm->id, 'sesskey' => $sesskey]))->out(false),
        'retryurl' => (new moodle_url($url, ['action' => 'retry', 'sesskey' => $sesskey]))->out(false),
        'settingsurl' => (new moodle_url('/course/modedit.php', ['update' => $cm->id, 'return' => 1]))->out(false),
        'hasversions' => $hasversions,
        'reviseformurl' => (new moodle_url('/mod/simgen/revise.php'))->out(false),
    ];

    if ($hasversions) {
        $options = [];
        foreach ($versions as $v) {
            $label = get_string('versionlabel', 'mod_simgen', (object)[
                'num' => $v->versionnum,
                'origin' => get_string('origin_' . $v->origin, 'mod_simgen'),
                'time' => userdate($v->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            ]);
            if ($v->id == $simgen->publishedversion) {
                $label .= ' – ' . get_string('badge_published', 'mod_simgen');
            }
            $options[$v->id] = $label;
        }
        $select = new single_select($url, 'version', $options, $versionid, null);
        $select->set_label(get_string('version', 'mod_simgen'));
        $panel['versionselect'] = $OUTPUT->render($select);

        $current = $DB->get_record('simgen_versions', ['id' => $versionid], '*', MUST_EXIST);
        $author = $current->userid ? core_user::get_user($current->userid) : null;
        $panel['version'] = [
            'id' => $current->id,
            'num' => $current->versionnum,
            'origin' => get_string('origin_' . $current->origin, 'mod_simgen'),
            'time' => userdate($current->timecreated),
            'author' => $author ? fullname($author) : '',
            'revisionnote' => (string)$current->revisionnote,
            'modelinfo' => (string)$current->modelinfo,
            'prompt' => (string)$current->prompttext,
            'response' => (string)$current->rawresponse,
            'ispublished' => $current->id == $simgen->publishedversion,
            'personas' => array_values(array_map(fn($p) => [
                'name' => $p['name'],
                'role' => $p['role'],
                'opening' => $p['opening'],
                'prompt' => $p['prompt'],
                'maxturns' => $p['maxturns'],
                'signals' => implode(', ', array_keys($p['signals'])),
            ], \mod_simgen\local\conversation::from_version($current))),
        ];
        $panel['version']['haspersonas'] = !empty($panel['version']['personas']);

        // Automatic checks: start-up check in the preview and findings of the static checks.
        $checkstatus = (int)$current->checkstatus;
        [$checkkey, $checkclass] = [
            report_check::PASSED => ['passed', 'bg-success'],
            report_check::FAILED => ['failed', 'bg-danger'],
        ][$checkstatus] ?? ['pending', 'bg-secondary'];
        $panel['version']['checkbadge'] = get_string('check_badge_' . $checkkey, 'mod_simgen');
        $panel['version']['checkclass'] = $checkclass;
        $panel['version']['checkerror'] = $checkstatus === report_check::FAILED ? (string)$current->checkerror : '';
        $issues = json_decode((string)$current->checkissues, true);
        $panel['version']['checkissues'] = is_array($issues) ? array_values(array_map(fn($issue) => [
            'message' => (string)($issue['message'] ?? ''),
            'iserror' => ($issue['severity'] ?? '') === code_checker::ERROR,
        ], $issues)) : [];
        $panel['version']['hascheckissues'] = !empty($panel['version']['checkissues']);

        // Schedule planned by the AI.
        $plan = time_plan::from_version($current);
        $panel['version']['timeplan'] = $plan;
        $panel['version']['hastimeplan'] = !empty($plan);
        $panel['version']['timeplantotal'] = get_string('timeplan_total', 'mod_simgen', (object)[
            'planned' => time_plan::total($plan),
            'minutes' => prompt_builder::duration_minutes($simgen) ?: '–',
        ]);
        $panel['nopublished'] = empty($simgen->publishedversion);
        $panel['publishedinfo'] = !empty($simgen->publishedversion) && isset($versions[$simgen->publishedversion])
            ? get_string('publishedinfo', 'mod_simgen', $versions[$simgen->publishedversion]->versionnum) : '';
        $versionparams = ['id' => $cm->id, 'version' => $current->id];
        $panel['publishurl'] = (new moodle_url($url, ['action' => 'publish', 'version' => $current->id,
            'sesskey' => $sesskey]))->out(false);
        $panel['unpublishurl'] = (new moodle_url($url, ['action' => 'unpublish', 'version' => $current->id,
            'sesskey' => $sesskey]))->out(false);
        $panel['reviseurl'] = (new moodle_url('/mod/simgen/revise.php', $versionparams))->out(false);
        $panel['editcodeurl'] = (new moodle_url('/mod/simgen/editcode.php', $versionparams))->out(false);
        $panel['downloadurl'] = (new moodle_url('/mod/simgen/sim.php', $versionparams + ['download' => 1]))->out(false);

        // Facilitator guide of this version.
        guide_generator::check_stale($current);
        $guidestatus = (int)$current->guidestatus;
        $panel['guideurl'] = (new moodle_url('/mod/simgen/guide.php', $versionparams))->out(false);
        if (in_array($guidestatus, [generator::STATUS_PENDING, generator::STATUS_GENERATING], true)) {
            $panel['guidebadge'] = get_string('guide_badge_running', 'mod_simgen');
        } else if ($guidestatus === generator::STATUS_ERROR) {
            $panel['guidebadge'] = get_string('guide_badge_error', 'mod_simgen');
        } else if (trim((string)$current->guide) === '') {
            $panel['guidebadge'] = get_string('guide_badge_none', 'mod_simgen');
        }
    }
    $panel['reporturl'] = (new moodle_url('/mod/simgen/report.php', ['id' => $cm->id]))->out(false);

    // Is the AI usable? Generation purpose and - for live conversations - the conversation purpose.
    $purposes = [get_config('mod_simgen', 'purpose') ?: 'singleprompt'];
    $conversationpurpose = get_config('mod_simgen', 'conversationpurpose') ?: 'chat';
    $isconversation = $simgen->simtype === 'conversation' || !empty($panel['version']['haspersonas']);
    if ($isconversation) {
        $purposes[] = $conversationpurpose;
    }
    foreach (ai_status::unavailable_purposes($USER, $context->id, $purposes) as $purpose => $message) {
        $warning = $purpose === '*'
            ? get_string('aiwarning_unavailable', 'mod_simgen', $message)
            : get_string('aiwarning_purpose', 'mod_simgen', (object)[
                'purpose' => ai_status::purpose_name($purpose),
                'message' => $message,
            ]);
        echo $OUTPUT->notification($warning, \core\output\notification::NOTIFY_WARNING);
    }
    if ($isconversation) {
        echo $OUTPUT->notification(get_string('aiwarning_conversationhint', 'mod_simgen',
            ai_status::purpose_name($conversationpurpose)), \core\output\notification::NOTIFY_INFO);
    }

    echo $OUTPUT->render_from_template('mod_simgen/manage', $panel);

    if ($panel['pending'] || $panel['generating']) {
        $since = $panel['generating'] && $simgen->genstarted ? (int)$simgen->genstarted : (int)$simgen->genrequested;
        $PAGE->requires->js_call_amd('mod_simgen/statuspoll', 'init',
            [(int)$cm->id, $status, '[data-region="simgen-elapsed"]', $since]);
    }
}

// Player.
if ($versionid) {
    $cansave = !$preview && !isguestuser() && has_capability('mod/simgen:savestate', $context);
    $completed = $cansave && $DB->record_exists('simgen_states',
        ['simgenid' => $simgen->id, 'userid' => $USER->id, 'completed' => 1]);
    $wrapperid = 'mod-simgen-player-' . $cm->id;
    $srcparams = ['id' => $cm->id];
    if ($canmanage) {
        $srcparams['version'] = $versionid;
    }

    // Live conversations: learners must know that their messages are sent to the AI and visible to teachers.
    $shownversion = $DB->get_record('simgen_versions', ['id' => $versionid],
        'id, codetype, sourcecode, personas, guide, checkstatus, compiled, compiledhash');

    // Teachers' preview checks the version and compiles it for students, as long as that has not happened.
    $check = $canmanage && $shownversion && ((int)$shownversion->checkstatus !== report_check::PASSED
        || ($shownversion->codetype === 'react' && !html_builder::compiled($shownversion)));

    // Teachers without the management panel (e.g. non-editing teachers) reach the guide of the published version here.
    if (!$canmanage && $shownversion && trim((string)$shownversion->guide) !== ''
            && has_capability('mod/simgen:viewguide', $context)) {
        echo html_writer::div(html_writer::link(new moodle_url('/mod/simgen/guide.php', ['id' => $cm->id]),
            get_string('guide_open', 'mod_simgen'), ['class' => 'btn btn-secondary btn-sm']), 'mb-2');
    }

    if ($shownversion && \mod_simgen\local\conversation::from_version($shownversion)) {
        echo $OUTPUT->notification(get_string('conversation_privacynotice', 'mod_simgen'),
            \core\output\notification::NOTIFY_INFO, false);
    }

    echo $OUTPUT->render_from_template('mod_simgen/player', [
        'wrapperid' => $wrapperid,
        'src' => (new moodle_url('/mod/simgen/sim.php', $srcparams))->out(false),
        'title' => format_string($simgen->name),
        'minheight' => (int)$simgen->minheight,
        'sandbox' => html_builder::SANDBOX,
        'showreset' => $cansave && !empty($simgen->savestate),
        'completed' => $completed,
        'preview' => $preview,
    ]);

    $PAGE->requires->js_call_amd('mod_simgen/player', 'init', [[
        'cmid' => (int)$cm->id,
        'wrapperid' => $wrapperid,
        'savestate' => !empty($simgen->savestate),
        'cansave' => $cansave,
        'canmanage' => $canmanage,
        'minheight' => (int)$simgen->minheight,
        'versionid' => (int)$versionid,
        // Offered to the simulation as SimBridge.user.firstname (stays in the sandbox, which cannot send data out).
        'firstname' => isguestuser() ? '' : (string)$USER->firstname,
        'check' => $check,
    ]]);
} else if (!$canmanage) {
    echo $OUTPUT->notification(get_string('notavailable', 'mod_simgen'), \core\output\notification::NOTIFY_INFO);
}

echo $OUTPUT->footer();
