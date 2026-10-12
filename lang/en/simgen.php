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
 * English strings of mod_simgen.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['badge_draft'] = 'Draft – not visible to students';
$string['badge_published'] = 'Published';
$string['changerequest'] = 'What should be changed?';
$string['changerequest_help'] = 'Describe as concretely as possible what the AI should change, e.g. "Add a third scenario on supplier failure", "The slider for the interest rate should go up to 15 %", "Feedback is too short". You can also paste an error message.';
$string['changerequest_placeholder'] = 'e.g. Add a progress bar and make the feedback more detailed …';
$string['codetype'] = 'Code type';
$string['codetype_html'] = 'Complete HTML document';
$string['codetype_react'] = 'React component (JSX) + CSS';
$string['completiondetail:finished'] = 'Finish the simulation';
$string['completionfinished'] = 'Require finishing';
$string['completionfinished_help'] = 'The activity is completed as soon as the simulation reports its completion (the generated code calls SimBridge.complete() at the end).';
$string['completionfinished_label'] = 'Students must finish the simulation';
$string['content'] = 'Subject content';
$string['content_help'] = 'Texts, definitions, formulas, cases or data that the simulation must be based on. The more precise the content, the more correct the simulation.';
$string['contentfiles'] = 'Material (upload)';
$string['contentfiles_help'] = 'The text of these files is extracted and sent to the AI: PDF, DOCX, PPTX, ODT, ODP, TXT, MD, CSV, JSON, HTML. Images and scanned PDFs cannot be read. Only teachers can access the files.';
$string['css'] = 'CSS';
$string['difficulty'] = 'Level';
$string['difficulty_advanced'] = 'Advanced (transfer)';
$string['difficulty_intermediate'] = 'Intermediate (application)';
$string['difficulty_intro'] = 'Introductory (basics)';
$string['downloadhtml'] = 'Download as HTML';
$string['durationmin'] = 'Working time (minutes)';
$string['durationmin_help'] = 'Planned working time of the students. The AI adjusts the scope of the simulation to it.';
$string['editbriefing'] = 'Edit briefing';
$string['editcode'] = 'Edit code';
$string['editcode_help'] = 'Changes are saved as a new version (draft). Publish it on the activity page afterwards. Available at runtime: React 18, the hooks as globals, useSimState(key, initial) and SimBridge.';
$string['error_ai'] = 'The AI request failed: {$a}';
$string['error_alreadyrunning'] = 'A generation is already running. Please wait until it has finished.';
$string['error_duration'] = 'Please enter a working time between 1 and 600 minutes.';
$string['error_minheight'] = 'Please enter a height between 300 and 3000 pixels.';
$string['error_noapp'] = 'The AI response does not contain a component named "App". The response may have been cut off (check the context length / output limit of the model).';
$string['error_nocode'] = 'The AI response does not contain a code block. Please try again or use a more capable model.';
$string['error_noversion'] = 'No version of this simulation is available.';
$string['error_outcomes'] = 'Please describe the learning outcomes (at least one sentence).';
$string['error_prefill'] = 'The simulation shows the following error when it runs. Find the cause, fix it and check the remaining code for similar problems:

{$a}';
$string['error_rawresponse'] = 'Beginning of the AI response:';
$string['error_stale'] = 'The generation did not finish (time-out or server restart). Please try again.';
$string['error_statetoolarge'] = 'The progress is too large to be saved (maximum {$a} KB).';
$string['error_unexpected'] = 'Unexpected error: {$a}';
$string['errorreport_fix'] = 'Let the AI fix the error';
$string['errorreport_help'] = 'Only teachers see this note. Students see a short error message inside the simulation.';
$string['errorreport_title'] = 'The simulation reported an error';
$string['event_simulation_completed'] = 'Simulation completed';
$string['event_simulation_generated'] = 'Simulation version generated';
$string['extract_failed'] = 'The text of this file could not be extracted';
$string['feedbackmode'] = 'Feedback';
$string['feedbackmode_end'] = 'Debriefing at the end';
$string['feedbackmode_hints'] = 'Graduated hints on request';
$string['feedbackmode_immediate'] = 'Immediate feedback after each action';
$string['feedbackmode_none'] = 'Exploratory, no evaluative feedback';
$string['form_aihint'] = 'The information below is filled into a prompt template and sent to the AI configured in the AI manager. The simulation is created as a draft; you can test it, revise it and then publish it for students. Do not enter personal data of students.';
$string['generate'] = 'AI generation';
$string['generate_help'] = 'If enabled, the AI generates a new version from the briefing after saving. Generation runs in the background and usually takes 1–5 minutes; you will be notified when it is finished. Existing versions are kept.';
$string['generate_label_new'] = 'Generate the simulation after saving';
$string['generate_label_update'] = 'Generate a new version from the changed briefing after saving';
$string['generatefirst'] = 'Generate simulation now';
$string['generatenow'] = 'Generate directly now';
$string['generation_success'] = 'A new version was generated. Please test it and publish it.';
$string['hdr_content'] = 'Content and material';
$string['hdr_didactics'] = 'Didactic frame';
$string['hdr_outcomes'] = 'Learning outcomes';
$string['hdr_player'] = 'Progress and generation';
$string['hdr_simulation'] = 'Simulation design';
$string['lang_de'] = 'German';
$string['lang_en'] = 'English';
$string['lang_fr'] = 'French';
$string['lang_it'] = 'Italian';
$string['lang_rm'] = 'Romansh';
$string['learningoutcomes'] = 'Learning outcomes';
$string['learningoutcomes_help'] = 'What should students be able to do after the simulation? Use observable verbs (e.g. explain, calculate, decide, justify), one outcome per line. The AI aligns every interaction with these outcomes.';
$string['learningoutcomes_placeholder'] = 'Students are able to …
- explain how … affects …
- make and justify a decision in …';
$string['minheight'] = 'Minimal height (px)';
$string['modulename'] = 'AI simulation';
$string['modulename_help'] = 'The AI simulation activity creates an interactive learning simulation (React) from a didactic briefing (target group, working time, learning outcomes, content) using the AI configured in the AI manager.

Teachers can test, revise (with change requests or automatic error fixing) and edit generated versions and publish one version for students. Students work directly in Moodle; their progress can be saved so they can continue later.';
$string['modulenameplural'] = 'AI simulations';
$string['notavailable'] = 'This simulation is not available yet.';
$string['nopublished_help'] = 'No version has been published yet – students cannot see the simulation. Test the version below and click "Publish".';
$string['notify_error_body'] = 'The generation of the simulation "{$a->name}" failed. Details: {$a->url}';
$string['notify_error_subject'] = 'Generation failed: {$a->name}';
$string['notify_success_body'] = 'A new version of the simulation "{$a->name}" was generated. Test and publish it here: {$a->url}';
$string['notify_success_subject'] = 'Simulation ready: {$a->name}';
$string['messageprovider:generationfinished'] = 'AI simulation generation finished';
$string['origin_ai'] = 'AI generation';
$string['origin_manual'] = 'Manually edited';
$string['origin_revision'] = 'AI revision';
$string['player_close'] = 'Close';
$string['player_compileerror'] = 'The code of the simulation contains a syntax error';
$string['player_completed'] = 'Simulation completed';
$string['player_completedbefore'] = 'Completed';
$string['player_errordetails'] = 'Technical details';
$string['player_errortext'] = 'The simulation cannot be displayed. Please reload it or inform your teacher.';
$string['player_errortextnonfatal'] = 'An error occurred. You can try to continue or reload the simulation.';
$string['player_errortitle'] = 'Error in the simulation';
$string['player_fullscreen'] = 'Full screen';
$string['player_libsfailed'] = 'The libraries of the simulation could not be loaded';
$string['player_loading'] = 'Loading simulation …';
$string['player_noapp'] = 'The simulation does not contain a start component (App)';
$string['player_reload'] = 'Reload';
$string['player_reset'] = 'Start over';
$string['player_resetconfirm'] = 'Your saved progress in this simulation will be deleted and the simulation starts from the beginning. Continue?';
$string['player_resettitle'] = 'Start over';
$string['player_saved'] = 'Saved at {$a}';
$string['player_saveerror'] = 'Progress could not be saved';
$string['player_saving'] = 'Saving …';
$string['pluginadministration'] = 'AI simulation administration';
$string['pluginname'] = 'AI simulation';
$string['preview_badge'] = 'Preview (draft) – progress is not saved';
$string['priorknowledge'] = 'Prior knowledge';
$string['priorknowledge_help'] = 'What do students already know? Which misconceptions are common? This helps the AI to choose the right level and address typical mistakes.';
$string['privacy:metadata:aiprovider'] = 'The briefing entered by the teacher (including extracted material) is sent via the AI manager (local_ai_manager) to the configured AI provider. No data of students is sent.';
$string['privacy:metadata:aiprovider:briefing'] = 'Briefing and material of the teacher';
$string['privacy:metadata:core_files'] = 'Material uploaded by teachers is stored as files.';
$string['privacy:metadata:simgen_states'] = 'Progress and results of users in a simulation.';
$string['privacy:metadata:simgen_states:completed'] = 'Whether the user completed the simulation.';
$string['privacy:metadata:simgen_states:rawscore'] = 'Score reported by the simulation.';
$string['privacy:metadata:simgen_states:statedata'] = 'Saved state of the simulation (inputs, decisions, step).';
$string['privacy:metadata:simgen_states:summary'] = 'Summary of results reported by the simulation.';
$string['privacy:metadata:simgen_states:timemodified'] = 'Time of the last save.';
$string['privacy:metadata:simgen_states:userid'] = 'The user.';
$string['privacy:metadata:simgen_versions'] = 'Generated or edited versions of a simulation.';
$string['privacy:metadata:simgen_versions:revisionnote'] = 'Change request or note of the author.';
$string['privacy:metadata:simgen_versions:timecreated'] = 'Time the version was created.';
$string['privacy:metadata:simgen_versions:userid'] = 'The user who created the version.';
$string['privacy:path:progress'] = 'Progress';
$string['privacy:path:versions'] = 'Versions';
$string['publish'] = 'Publish this version';
$string['published'] = 'Version {$a} is now visible to students.';
$string['publishedinfo'] = 'Students see version {$a}.';
$string['purposeplacedescription'] = 'Generation and revision of interactive learning simulations (activity "AI simulation")';
$string['report'] = 'Results';
$string['report_completed'] = 'Completed';
$string['report_empty'] = 'Nobody has worked on the simulation yet.';
$string['report_lastactivity'] = 'Last activity';
$string['report_score'] = 'Score';
$string['report_started'] = 'Started';
$string['report_status'] = 'Status';
$string['report_summary'] = '{$a->started} participants started the simulation, {$a->completed} completed it ({$a->enrolled} enrolled).';
$string['report_summarycol'] = 'Result summary';
$string['requirements'] = 'Further wishes';
$string['requirements_help'] = 'Anything else the AI should consider: specific scenarios, interaction types (sliders, drag & drop, charts), roles, examples from practice, corporate design colours, things to avoid …';
$string['requirements_placeholder'] = 'e.g. Use an example from tourism in Graubünden. Show the results as a line chart. Max. 5 steps.';
$string['resetstates'] = 'Delete saved progress of all users';
$string['resetuser'] = 'Reset';
$string['resetuser_confirm'] = 'Delete the progress and completion of this participant?';
$string['resetuserdone'] = 'The progress was deleted.';
$string['retry'] = 'Try again';
$string['revise'] = 'Revise with AI';
$string['revise_base'] = 'Version to revise';
$string['revise_fromscratch'] = 'None – generate again from the briefing';
$string['revise_help'] = 'The AI receives the briefing, the code of the selected version and your change request and creates a new version (draft). The current published version remains visible to students until you publish the new one.';
$string['revise_submit'] = 'Create new version';
$string['revision_queued'] = 'The revision has been started. This usually takes 1–5 minutes.';
$string['revisionnote'] = 'Note on this version';
$string['savenewversion'] = 'Save as new version';
$string['savestate'] = 'Save progress';
$string['savestate_help'] = 'If enabled, the progress of each student is saved automatically in Moodle so they can interrupt the simulation and continue later. Recommended for simulations longer than about 15 minutes.';
$string['savestate_label'] = 'Students can interrupt the simulation and continue later';
$string['settings_heading_ai'] = 'AI';
$string['settings_heading_ai_desc'] = 'Requests are sent via the AI manager (local_ai_manager). Assign a capable model to the selected purpose there and raise the request time-out (local_ai_manager | requesttimeout) to at least 300 seconds – generating code takes time.';
$string['settings_heading_prompts'] = 'Prompt templates';
$string['settings_heading_prompts_desc'] = 'Placeholders: {{TITLE}}, {{DESCRIPTION}}, {{SUBJECT}}, {{TARGETGROUP}}, {{PRIORKNOWLEDGE}}, {{DIFFICULTY}}, {{DURATION}}, {{SOCIALFORM}}, {{LANGUAGE}}, {{SIMTYPE}}, {{FEEDBACKMODE}}, {{LEARNINGOUTCOMES}}, {{REQUIREMENTS}}, {{CONTENT}} and {{TECHNICAL_RULES}} (the runtime contract of the player; appended automatically if missing). The revision template additionally knows {{CHANGE_REQUEST}} and {{CURRENT_CODE}}. Empty = built-in default.';
$string['settings_libbabel'] = 'URL of Babel standalone';
$string['settings_libreact'] = 'URL of React (UMD)';
$string['settings_libreactdom'] = 'URL of ReactDOM (UMD)';
$string['settings_maxcontentchars'] = 'Maximum material length (characters)';
$string['settings_maxcontentchars_desc'] = 'Longer material is cut off. Adjust to the context window of the model (rule of thumb: 4 characters ≈ 1 token; the answer additionally needs about 8,000–16,000 tokens).';
$string['settings_maxstatesize'] = 'Maximum progress size (KB)';
$string['settings_maxstatesize_desc'] = 'Maximum size of the saved progress per user and simulation.';
$string['settings_pdftotext'] = 'Path to pdftotext';
$string['settings_pdftotext_desc'] = 'Optional (poppler-utils). Used to extract text from PDF files. Without it, Ghostscript ($CFG->pathtogs) is used if available.';
$string['settings_showdefault'] = 'Show built-in template (copy it into the field to adapt it)';
$string['settings_purpose'] = 'AI purpose';
$string['settings_purpose_desc'] = 'Purpose of the AI manager used for the requests. Recommended: "Single prompt". The model assigned to this purpose in the AI manager is used (e.g. a local model via Ollama or ChatGPT). Teachers need the capability local/ai_manager:use and must be allowed to use this purpose.';
$string['settings_prompt_generate'] = 'Prompt template: generation';
$string['settings_prompt_generate_desc'] = 'Used for the first generation and when generating again from the briefing.';
$string['settings_prompt_revise'] = 'Prompt template: revision';
$string['settings_prompt_revise_desc'] = 'Used when a version is revised with a change request or error message.';
$string['settings_heading_runtime'] = 'Runtime libraries';
$string['settings_heading_runtime_desc'] = 'The simulations run in a sandboxed iframe and load React, ReactDOM and Babel. For data protection you can host the files on your Moodle server (e.g. /mod/simgen/vendor/react.production.min.js); site relative paths are allowed.';
$string['showprompt'] = 'Show prompt sent to the AI';
$string['showresponse'] = 'Show AI response';
$string['simgen:addinstance'] = 'Add a new AI simulation';
$string['simgen:manage'] = 'Generate, revise, edit and publish AI simulations';
$string['simgen:savestate'] = 'Save own progress in AI simulations';
$string['simgen:view'] = 'View AI simulations';
$string['simgen:viewreports'] = 'View progress and results of all participants';
$string['simlanguage'] = 'Language of the simulation';
$string['simtype'] = 'Simulation type';
$string['simtype_auto'] = 'Let the AI choose';
$string['simtype_data'] = 'Data exploration';
$string['simtype_decision'] = 'Decision scenario / case study';
$string['simtype_game'] = 'Business game / strategy (rounds)';
$string['simtype_help'] = '* Parameter exploration: change inputs of a model with sliders and observe the effects.
* Decision scenario: branching case with consequences and debriefing.
* Process training: carry out a procedure step by step.
* Virtual lab: hypothesis, experiment, measurement, evaluation.
* Dialogue / role play: conversation with predefined answer options.
* Business game: several rounds with resources and key figures.
* Data exploration: filter, group and visualise a data set.';
$string['simtype_lab'] = 'Virtual lab / experiment';
$string['simtype_parameter'] = 'Parameter exploration ("what if")';
$string['simtype_process'] = 'Process / procedure training';
$string['simtype_roleplay'] = 'Dialogue / role play';
$string['socialform'] = 'Setting';
$string['socialform_classroom'] = 'Classroom / projector (led by the teacher)';
$string['socialform_flipped'] = 'Preparation for a class session (flipped classroom)';
$string['socialform_group'] = 'Pair or group work on one device';
$string['socialform_individual'] = 'Individual self-study';
$string['sourcecode'] = 'Code';
$string['status_error'] = 'The generation failed.';
$string['status_generating'] = 'The AI is generating the simulation …';
$string['status_generating_help'] = 'This usually takes 1–5 minutes. You can leave the page; you will be notified when it is finished.';
$string['status_none'] = 'No simulation has been generated yet.';
$string['status_pending'] = 'The generation is queued.';
$string['status_pending_help'] = 'It starts with the next cron run. If it takes too long, you can start it directly (the page then loads until the generation is finished).';
$string['subject'] = 'Subject / topic';
$string['subject_help'] = 'Subject and specific topic of the simulation, e.g. "Microeconomics – price elasticity of demand".';
$string['targetgroup'] = 'Target group';
$string['targetgroup_help'] = 'Programme, semester and level, e.g. "Bachelor Tourism, 2nd semester, part-time students with work experience".';
$string['task_generate'] = 'Generate AI simulation';
$string['unpublish'] = 'Unpublish';
$string['unpublished'] = 'The simulation is no longer visible to students.';
$string['version'] = 'Version';
$string['version_created'] = 'Created';
$string['version_model'] = 'Model';
$string['version_note'] = 'Change request / note';
$string['version_origin'] = 'Origin';
$string['versiondetails'] = 'Details of this version';
$string['versionlabel'] = 'Version {$a->num} – {$a->origin} – {$a->time}';
$string['versionsaved'] = 'The new version was saved as a draft.';
$string['versionshort'] = 'Version {$a}';
