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
 * Library of interface functions and constants for mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_simgen\local\generator;

/**
 * Returns the features supported by this module.
 *
 * @param string $feature FEATURE_xx constant
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function simgen_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_INTERACTIVECONTENT;
        default:
            return null;
    }
}

/**
 * File options for the uploaded content files.
 *
 * @return array
 */
function simgen_get_content_file_options(): array {
    return [
        'subdirs' => 0,
        'maxfiles' => 10,
        'maxbytes' => 0,
        'accepted_types' => ['.pdf', '.docx', '.pptx', '.odt', '.odp', '.txt', '.md', '.csv', '.json', '.html', '.htm'],
        'return_types' => FILE_INTERNAL,
    ];
}

/**
 * Copies the editor and file manager values of the form into the instance record.
 *
 * @param stdClass $data form data
 * @return void
 */
function simgen_process_form_data(stdClass $data): void {
    if (isset($data->contenteditor) && is_array($data->contenteditor)) {
        $data->content = $data->contenteditor['text'];
        $data->contentformat = $data->contenteditor['format'];
    }
    $data->durationmin = (int)($data->durationmin ?? 0);
    $data->minheight = max(300, (int)($data->minheight ?? 600));
    $data->timemodified = time();
}

/**
 * Adds a new simulation instance and queues the AI generation.
 *
 * @param stdClass $data form data
 * @param mod_simgen_mod_form|null $mform
 * @return int new instance id
 */
function simgen_add_instance(stdClass $data, $mform = null): int {
    global $DB, $USER;

    simgen_process_form_data($data);
    $data->timecreated = $data->timemodified;
    $data->status = generator::STATUS_NONE;
    $data->publishedversion = 0;
    $data->id = $DB->insert_record('simgen', $data);

    $context = context_module::instance($data->coursemodule);
    if (isset($data->contentfiles)) {
        file_save_draft_area_files($data->contentfiles, $context->id, 'mod_simgen', 'content', 0,
            simgen_get_content_file_options());
    }

    if (!empty($data->generate)
            && !generator::queue($DB->get_record('simgen', ['id' => $data->id], '*', MUST_EXIST), (int)$USER->id)) {
        \core\notification::warning(get_string('error_alreadyrunning', 'mod_simgen'));
    }

    return $data->id;
}

/**
 * Updates an existing simulation instance (and queues a new generation if requested).
 *
 * @param stdClass $data form data
 * @param mod_simgen_mod_form|null $mform
 * @return bool
 */
function simgen_update_instance(stdClass $data, $mform = null): bool {
    global $DB, $USER;

    simgen_process_form_data($data);
    $data->id = $data->instance;
    $DB->update_record('simgen', $data);

    $context = context_module::instance($data->coursemodule);
    if (isset($data->contentfiles)) {
        file_save_draft_area_files($data->contentfiles, $context->id, 'mod_simgen', 'content', 0,
            simgen_get_content_file_options());
    }

    if (!empty($data->generate)
            && !generator::queue($DB->get_record('simgen', ['id' => $data->id], '*', MUST_EXIST), (int)$USER->id)) {
        \core\notification::warning(get_string('error_alreadyrunning', 'mod_simgen'));
    }

    return true;
}

/**
 * Deletes a simulation instance including versions and saved progress.
 *
 * @param int $id instance id
 * @return bool
 */
function simgen_delete_instance($id): bool {
    global $DB;

    if (!$DB->record_exists('simgen', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('simgen_states', ['simgenid' => $id]);
    $DB->delete_records('simgen_conversations', ['simgenid' => $id]);
    $DB->delete_records('simgen_versions', ['simgenid' => $id]);
    $DB->delete_records('simgen', ['id' => $id]);
    return true;
}

/**
 * Adds information for the course page (description, custom completion rules).
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function simgen_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completionfinished';
    if (!$simgen = $DB->get_record('simgen', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $simgen->name;
    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('simgen', $simgen, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionfinished'] = $simgen->completionfinished;
    }
    return $result;
}

/**
 * Marks the activity as viewed and triggers the viewed event.
 *
 * @param stdClass $simgen
 * @param stdClass $course
 * @param cm_info|stdClass $cm
 * @param context_module $context
 * @return void
 */
function simgen_view(stdClass $simgen, stdClass $course, $cm, context_module $context): void {
    $event = \mod_simgen\event\course_module_viewed::create([
        'objectid' => $simgen->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('simgen', $simgen);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Serves the uploaded content files (only for users who can manage the simulation).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not found, does not return if found - just sends the file
 */
function simgen_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE || $filearea !== 'content') {
        return false;
    }
    require_course_login($course, true, $cm);
    require_capability('mod/simgen:manage', $context);

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_simgen', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true, $options);
}

/**
 * Adds the management pages to the activity navigation.
 *
 * @param settings_navigation $settingsnav
 * @param navigation_node|null $simgennode
 * @return void
 */
function simgen_extend_settings_navigation(settings_navigation $settingsnav, ?navigation_node $simgennode = null): void {
    if (!$simgennode || !($cm = $settingsnav->get_page()->cm)) {
        return;
    }
    $context = context_module::instance($cm->id);

    if (has_capability('mod/simgen:manage', $context)) {
        $simgennode->add(get_string('revise', 'mod_simgen'),
            new moodle_url('/mod/simgen/revise.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING, null, 'mod_simgen_revise');
        $simgennode->add(get_string('editcode', 'mod_simgen'),
            new moodle_url('/mod/simgen/editcode.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING, null, 'mod_simgen_editcode');
    }
    if (has_capability('mod/simgen:viewreports', $context)) {
        $simgennode->add(get_string('report', 'mod_simgen'),
            new moodle_url('/mod/simgen/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING, null, 'mod_simgen_report');
    }
}

/**
 * Adds the reset option to the course reset form.
 *
 * @param MoodleQuickForm $mform
 * @return void
 */
function simgen_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'simgenheader', get_string('modulenameplural', 'mod_simgen'));
    $mform->addElement('advcheckbox', 'reset_simgen_states', get_string('resetstates', 'mod_simgen'));
}

/**
 * Default values for the course reset form.
 *
 * @param stdClass $course
 * @return array
 */
function simgen_reset_course_form_defaults($course): array {
    return ['reset_simgen_states' => 1];
}

/**
 * Removes the saved progress of all users when a course is reset.
 *
 * @param stdClass $data the data submitted from the reset course form
 * @return array status array
 */
function simgen_reset_userdata($data): array {
    global $DB;

    $status = [];
    if (!empty($data->reset_simgen_states)) {
        $DB->delete_records_select('simgen_states',
            'simgenid IN (SELECT id FROM {simgen} WHERE course = ?)', [$data->courseid]);
        $DB->delete_records_select('simgen_conversations',
            'simgenid IN (SELECT id FROM {simgen} WHERE course = ?)', [$data->courseid]);
        $status[] = [
            'component' => get_string('modulenameplural', 'mod_simgen'),
            'item' => get_string('resetstates', 'mod_simgen'),
            'error' => false,
        ];
    }
    return $status;
}
