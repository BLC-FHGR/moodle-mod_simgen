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
 * Facilitator guide of a version (brief, play, debrief) for teachers.
 *
 * Teachers with mod/simgen:manage see the guide of every version and can create, renew and edit it. Teachers with
 * mod/simgen:viewguide only see the guide of the published version. The guide contains the solution and is never
 * delivered to students (not part of sim.php or the HTML download of the simulation).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_simgen\form\guide_form;
use mod_simgen\local\generator;
use mod_simgen\local\guide_generator;

$id = required_param('id', PARAM_INT);
$versionid = optional_param('version', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simgen');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
$canmanage = has_capability('mod/simgen:manage', $context);
if (!$canmanage) {
    require_capability('mod/simgen:viewguide', $context);
}

$simgen = $DB->get_record('simgen', ['id' => $cm->instance], '*', MUST_EXIST);
$viewurl = new moodle_url('/mod/simgen/view.php', ['id' => $cm->id]);

// Which version? Teachers who cannot manage only see the published version.
if (!$canmanage || !$versionid) {
    $versionid = (int)$simgen->publishedversion;
}
$version = $versionid ? $DB->get_record('simgen_versions', ['id' => $versionid, 'simgenid' => $simgen->id]) : null;
if (!$version && $canmanage) {
    $version = $DB->get_record_sql('SELECT * FROM {simgen_versions} WHERE simgenid = ? ORDER BY versionnum DESC',
        [$simgen->id], IGNORE_MULTIPLE);
}
if (!$version) {
    redirect($viewurl, get_string('guide_noversion', 'mod_simgen'), null, \core\output\notification::NOTIFY_INFO);
}
guide_generator::check_stale($version);

$url = new moodle_url('/mod/simgen/guide.php', ['id' => $cm->id, 'version' => $version->id]);
$hasguide = trim((string)$version->guide) !== '';
$filename = clean_filename(get_string('guide', 'mod_simgen') . ' ' . format_string($simgen->name, true, ['escape' => false])
    . ' v' . $version->versionnum);

// Downloads (all viewers of the guide).
if ($action === 'download' && $hasguide) {
    send_file($version->guide, $filename . '.md', 0, 0, true, true, 'text/markdown');
} else if ($action === 'downloadhtml' && $hasguide) {
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . s($filename) . '</title>'
        . '<style>body{font-family:sans-serif;max-width:50rem;margin:2rem auto;line-height:1.5}'
        . 'table{border-collapse:collapse}th,td{border:1px solid #ccc;padding:.25rem .5rem;vertical-align:top}'
        . 'blockquote{border-left:3px solid #0f6cbf;margin-left:0;padding-left:.75rem;color:#444}</style></head><body>'
        . format_text($version->guide, FORMAT_MARKDOWN, ['context' => $context]) . '</body></html>';
    send_file($html, $filename . '.html', 0, 0, true, true, 'text/html');
}

// Teacher actions.
if ($canmanage && in_array($action, ['create', 'createnow'], true)) {
    require_sesskey();
    $running = (int)$version->guidestatus === generator::STATUS_GENERATING;
    if ($action === 'create') {
        if (!guide_generator::queue($version, (int)$USER->id)) {
            redirect($url, get_string('error_alreadyrunning', 'mod_simgen'), null, \core\output\notification::NOTIFY_WARNING);
        }
        redirect($url, get_string('guide_queued', 'mod_simgen'), null, \core\output\notification::NOTIFY_INFO);
    }
    // Run immediately (without waiting for cron).
    if ((int)$version->guidestatus !== generator::STATUS_PENDING
            && ($running || !guide_generator::queue($version, (int)$USER->id))) {
        redirect($url, get_string('error_alreadyrunning', 'mod_simgen'), null, \core\output\notification::NOTIFY_WARNING);
    }
    // The request can take minutes: release the session and keep running if the user leaves the page.
    \core\session\manager::write_close();
    ignore_user_abort(true);
    guide_generator::run((int)$version->id);
    // No redirect message: the session is already closed. The page shows the guide or the error.
    redirect($url);
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('guide', 'mod_simgen') . ': ' . format_string($simgen->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();
$PAGE->add_body_class('mod-simgen-guidepage');

// Edit the guide.
if ($canmanage && $action === 'edit') {
    $form = new guide_form($url);
    $form->set_data(['id' => $cm->id, 'version' => $version->id, 'guide' => (string)$version->guide]);
    if ($form->is_cancelled()) {
        redirect($url);
    } else if ($data = $form->get_data()) {
        guide_generator::save_manual($version, (string)$data->guide);
        redirect($url, get_string('guide_saved', 'mod_simgen'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('guide_edit', 'mod_simgen') . ': ' . format_string($simgen->name));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('guide', 'mod_simgen') . ': ' . format_string($simgen->name));

$ispublished = $version->id == $simgen->publishedversion;
$status = (int)$version->guidestatus;
$sesskey = sesskey();

// Version and its state.
$versioninfo = html_writer::span(get_string('versionshort', 'mod_simgen', $version->versionnum), 'font-weight-bold fw-bold')
    . ' ' . ($ispublished
        ? html_writer::span(get_string('badge_published', 'mod_simgen'), 'badge badge-success bg-success')
        : html_writer::span(get_string('badge_draft', 'mod_simgen'), 'badge badge-warning bg-warning text-dark'));
if ($canmanage) {
    $versions = $DB->get_records('simgen_versions', ['simgenid' => $simgen->id], 'versionnum DESC',
        'id, versionnum, guidestatus');
    if (count($versions) > 1) {
        $options = [];
        foreach ($versions as $v) {
            $options[$v->id] = get_string('versionshort', 'mod_simgen', $v->versionnum)
                . ($v->id == $simgen->publishedversion ? ' – ' . get_string('badge_published', 'mod_simgen') : '');
        }
        $select = new single_select(new moodle_url('/mod/simgen/guide.php', ['id' => $cm->id]), 'version', $options,
            $version->id, null);
        $select->set_label(get_string('version', 'mod_simgen'), ['class' => 'sr-only visually-hidden']);
        $versioninfo .= ' ' . $OUTPUT->render($select);
    }
}
echo html_writer::div($versioninfo, 'mod-simgen-versionbar mb-2 mod-simgen-noprint');
echo html_writer::tag('p', get_string('guide_intro', 'mod_simgen'), ['class' => 'text-muted mod-simgen-noprint']);

// Generation status.
$createurl = new moodle_url($url, ['action' => 'create', 'sesskey' => $sesskey]);
$createnowurl = new moodle_url($url, ['action' => 'createnow', 'sesskey' => $sesskey]);
if ($status === generator::STATUS_PENDING) {
    $content = html_writer::tag('strong', get_string('guide_status_pending', 'mod_simgen'))
        . html_writer::span('', 'mod-simgen-elapsed', ['data-region' => 'simgen-elapsed'])
        . html_writer::tag('p', get_string('status_pending_help', 'mod_simgen'), ['class' => 'mb-2']);
    if ($canmanage) {
        $content .= html_writer::link($createnowurl, get_string('guide_createnow', 'mod_simgen'),
            ['class' => 'btn btn-secondary btn-sm']);
    }
    echo html_writer::div($content, 'alert alert-info mod-simgen-noprint', ['role' => 'status']);
} else if ($status === generator::STATUS_GENERATING) {
    echo html_writer::div(
        html_writer::span('', 'spinner-border spinner-border-sm', ['aria-hidden' => 'true']) . ' '
        . html_writer::tag('strong', get_string('guide_status_generating', 'mod_simgen'))
        . html_writer::span('', 'mod-simgen-elapsed', ['data-region' => 'simgen-elapsed'])
        . html_writer::tag('p', get_string('guide_status_generating_help', 'mod_simgen'), ['class' => 'mb-0']),
        'alert alert-info mod-simgen-noprint', ['role' => 'status']);
} else if ($status === generator::STATUS_ERROR && $canmanage) {
    echo html_writer::div(
        html_writer::tag('strong', get_string('guide_status_error', 'mod_simgen'))
        . html_writer::tag('pre', s((string)$version->guidemessage), ['class' => 'mod-simgen-pre mt-2 mb-2'])
        . html_writer::link($createurl, get_string('retry', 'mod_simgen'), ['class' => 'btn btn-primary btn-sm']),
        'alert alert-danger mod-simgen-noprint', ['role' => 'alert']);
}
$running = in_array($status, [generator::STATUS_PENDING, generator::STATUS_GENERATING], true);
if ($running && $canmanage) {
    $since = $status === generator::STATUS_GENERATING && $version->guidestarted
        ? (int)$version->guidestarted : (int)$version->guiderequested;
    $PAGE->requires->js_call_amd('mod_simgen/statuspoll', 'init',
        [(int)$cm->id, $status, '[data-region="simgen-elapsed"]', $since, (int)$version->id]);
}

if (!$hasguide) {
    if (!$running) {
        if ($canmanage) {
            echo html_writer::div(
                html_writer::tag('p', get_string($ispublished ? 'guide_none_published' : 'guide_none_draft',
                    'mod_simgen'))
                . html_writer::link($createurl, get_string('guide_create', 'mod_simgen'),
                    ['class' => 'btn btn-primary btn-sm']),
                'alert alert-secondary');
        } else {
            echo $OUTPUT->notification(get_string('guide_none', 'mod_simgen'), \core\output\notification::NOTIFY_INFO);
        }
    }
} else {
    // Origin of the guide.
    $modified = $version->guidemodified ? userdate($version->guidemodified) : '';
    $origin = $version->guideorigin === 'manual'
        ? get_string('guide_origin_manual', 'mod_simgen', $modified)
        : get_string('guide_origin_ai', 'mod_simgen', $modified)
            . ($version->guidemodelinfo ? ' (' . s($version->guidemodelinfo) . ')' : '');
    echo html_writer::tag('p', $origin, ['class' => 'small text-muted mod-simgen-noprint']);

    // Actions.
    $actions = [];
    if ($canmanage && !$running) {
        $actions[] = html_writer::link(new moodle_url($url, ['action' => 'edit']), get_string('guide_edit', 'mod_simgen'),
            ['class' => 'btn btn-secondary btn-sm']);
        $confirm = $version->guideorigin === 'manual' ? 'guide_recreate_confirm_manual' : 'guide_recreate_confirm';
        $actions[] = html_writer::link($createurl, get_string('guide_recreate', 'mod_simgen'), [
            'class' => 'btn btn-outline-secondary btn-sm',
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => '["guide_recreate", "mod_simgen"]',
            'data-confirmation-content-str' => '["' . $confirm . '", "mod_simgen"]',
            'data-confirmation-yes-button-str' => '["guide_recreate", "mod_simgen"]',
            'data-confirmation-destination' => $createurl->out(false),
        ]);
    }
    $actions[] = html_writer::link(new moodle_url($url, ['action' => 'downloadhtml']),
        get_string('guide_downloadhtml', 'mod_simgen'), ['class' => 'btn btn-outline-secondary btn-sm']);
    $actions[] = html_writer::link(new moodle_url($url, ['action' => 'download']),
        get_string('guide_downloadmd', 'mod_simgen'), ['class' => 'btn btn-outline-secondary btn-sm']);
    echo html_writer::div(implode(' ', $actions), 'mod-simgen-actions mb-3 mod-simgen-noprint');

    echo html_writer::div(format_text($version->guide, FORMAT_MARKDOWN, ['context' => $context]),
        'mod-simgen-guide card card-body');
}

echo html_writer::link($viewurl, get_string('back'), ['class' => 'btn btn-secondary mt-3 mod-simgen-noprint']);
echo $OUTPUT->footer();
