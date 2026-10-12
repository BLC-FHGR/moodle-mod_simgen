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
 * Admin settings of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_simgen\local\html_builder;
use mod_simgen\local\prompt_templates;

if ($ADMIN->fulltree) {
    // AI.
    $settings->add(new admin_setting_heading('mod_simgen/heading_ai',
        get_string('settings_heading_ai', 'mod_simgen'), get_string('settings_heading_ai_desc', 'mod_simgen')));

    $purposes = [];
    foreach (array_keys(core_component::get_plugin_list('aipurpose')) as $purpose) {
        $purposes[$purpose] = get_string('pluginname', 'aipurpose_' . $purpose);
    }
    if (!$purposes) {
        $purposes = ['singleprompt' => 'singleprompt'];
    }
    $settings->add(new admin_setting_configselect('mod_simgen/purpose',
        get_string('settings_purpose', 'mod_simgen'), get_string('settings_purpose_desc', 'mod_simgen'),
        'singleprompt', $purposes));

    $settings->add(new admin_setting_configtext('mod_simgen/maxcontentchars',
        get_string('settings_maxcontentchars', 'mod_simgen'), get_string('settings_maxcontentchars_desc', 'mod_simgen'),
        30000, PARAM_INT));

    $settings->add(new admin_setting_configexecutable('mod_simgen/pathtopdftotext',
        get_string('settings_pdftotext', 'mod_simgen'), get_string('settings_pdftotext_desc', 'mod_simgen'), ''));

    // Prompt templates.
    $settings->add(new admin_setting_heading('mod_simgen/heading_prompts',
        get_string('settings_heading_prompts', 'mod_simgen'), get_string('settings_heading_prompts_desc', 'mod_simgen')));

    // Empty default = built-in template, so improvements of the built-in templates reach existing sites.
    // The built-in template is shown below the field to be copied and adapted.
    $showdefault = function(string $template): string {
        return html_writer::tag('details',
            html_writer::tag('summary', get_string('settings_showdefault', 'mod_simgen'))
            . html_writer::tag('pre', s($template), ['style' => 'white-space: pre-wrap; max-height: 400px; overflow: auto;']));
    };

    $settings->add(new admin_setting_configtextarea('mod_simgen/prompt_generate',
        get_string('settings_prompt_generate', 'mod_simgen'),
        get_string('settings_prompt_generate_desc', 'mod_simgen') . $showdefault(prompt_templates::GENERATE),
        '', PARAM_RAW, 100, 20));

    $settings->add(new admin_setting_configtextarea('mod_simgen/prompt_revise',
        get_string('settings_prompt_revise', 'mod_simgen'),
        get_string('settings_prompt_revise_desc', 'mod_simgen') . $showdefault(prompt_templates::REVISE),
        '', PARAM_RAW, 100, 20));

    // Runtime.
    $settings->add(new admin_setting_heading('mod_simgen/heading_runtime',
        get_string('settings_heading_runtime', 'mod_simgen'), get_string('settings_heading_runtime_desc', 'mod_simgen')));

    $settings->add(new admin_setting_configtext('mod_simgen/libreact',
        get_string('settings_libreact', 'mod_simgen'), '', html_builder::DEFAULT_REACT, PARAM_RAW_TRIMMED, 90));
    $settings->add(new admin_setting_configtext('mod_simgen/libreactdom',
        get_string('settings_libreactdom', 'mod_simgen'), '', html_builder::DEFAULT_REACTDOM, PARAM_RAW_TRIMMED, 90));
    $settings->add(new admin_setting_configtext('mod_simgen/libbabel',
        get_string('settings_libbabel', 'mod_simgen'), '', html_builder::DEFAULT_BABEL, PARAM_RAW_TRIMMED, 90));

    $settings->add(new admin_setting_configtext('mod_simgen/maxstatesize',
        get_string('settings_maxstatesize', 'mod_simgen'), get_string('settings_maxstatesize_desc', 'mod_simgen'),
        512, PARAM_INT));
}
