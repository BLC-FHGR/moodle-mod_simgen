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
$string['error_duration'] = 'Please enter a working time between 1 and {$a} minutes.';
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
$string['error_versionchanged'] = 'This simulation has been updated in the meantime. Please reload the page. Until then, your progress is not saved and conversations are not available.';
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
$string['privacy:metadata:aiprovider'] = 'Data sent via the AI manager (local_ai_manager) to the configured AI provider: the briefing entered by the teacher (including extracted material) and - in live conversation simulations - the messages learners write to the AI personas, together with the conversation history.';
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
* Data exploration: filter, group and visualise a data set.
* Live conversation: free conversations with AI personas whose answers are generated at runtime (learners\' messages are sent to the AI).';
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

// Version 0.2: maximum working time and live conversations.
$string['conversation_none'] = 'No conversations.';
$string['conversation_privacynotice'] = 'In this simulation you talk to AI-controlled characters. Your messages are sent to the AI (via the AI manager) and can be viewed by your teachers. Do not enter any personal data.';
$string['conversation_signals'] = 'Last assessment:';
$string['conversation_transcript'] = 'Conversations of {$a}';
$string['durationmin_max'] = 'Working time (minutes, max. {$a})';
$string['error_conversationai'] = 'The AI could not answer: {$a}';
$string['error_conversationfinished'] = 'The maximum number of messages in this conversation has been reached.';
$string['error_emptymessage'] = 'Please enter a message.';
$string['error_emptyreply'] = 'The AI returned an empty answer. Please try again.';
$string['error_messagetoolong'] = 'The message is too long (maximum {$a} characters).';
$string['error_nopersonas'] = 'The AI response does not contain valid conversation characters (JSON block with "personas"). Please generate again or add them under "Edit code".';
$string['error_personasjson'] = 'The character definitions are not valid JSON: {$a}';
$string['error_unknownpersona'] = 'Unknown conversation character: {$a}';
$string['persona_maxturns'] = 'max. {$a} messages';
$string['persona_signals'] = 'signals';
$string['personas'] = 'Conversation characters (JSON)';
$string['personas_help'] = 'Only for live conversation simulations. JSON of the form {"personas": [{"id": "guest", "name": "Ms Keller", "role": "Hotel guest", "prompt": "You are ...", "opening": "First sentence", "maxTurns": 8, "signals": {"mood": "Satisfaction 1-5", "solved": "true when ..."}}]}. The prompt stays on the server; learners never see it. Leave empty for simulations without AI characters.';
$string['player_conversationtimeout'] = 'The AI did not answer in time. Please try again.';
$string['player_conversationunavailable'] = 'Conversations are only available inside Moodle.';
$string['player_unknownpersona'] = 'Unknown conversation character';
$string['privacy:metadata:aiprovider:conversation'] = 'Messages of learners to AI personas and the conversation history';
$string['privacy:metadata:simgen_conversations'] = 'Live conversations of users with AI personas.';
$string['privacy:metadata:simgen_conversations:messagedata'] = 'Messages of the user and answers of the AI persona.';
$string['privacy:metadata:simgen_conversations:signaldata'] = 'Latest assessment of the conversation by the AI (e.g. mood).';
$string['privacy:metadata:simgen_conversations:timemodified'] = 'Time of the last message.';
$string['privacy:metadata:simgen_conversations:userid'] = 'The user.';
$string['privacy:path:conversations'] = 'Conversations';
$string['purposeplacedescription_conversation'] = 'Answers of AI characters in live conversation simulations (activity "AI simulation"); learners\' messages are sent';
$string['report_conversationlink'] = 'Show ({$a} messages)';
$string['report_conversations'] = 'Conversations';
$string['settings_conversationpurpose'] = 'AI purpose for conversations';
$string['settings_conversationpurpose_desc'] = 'Purpose of the AI manager used for the answers of AI characters at runtime. These requests are made by the learners: their role in the AI manager needs access to this purpose (and the capability local/ai_manager:use). Using a different purpose than for the generation allows e.g. a local model for conversations and a more capable model for generating.';
$string['settings_heading_conversation'] = 'Live conversations';
$string['settings_heading_conversation_desc'] = 'Settings for the simulation type "Live conversation", in which learners talk freely to AI characters. Learners\' messages are sent to the AI and stored; teachers can view them in the results.';
$string['settings_maxduration'] = 'Maximum working time (minutes)';
$string['settings_maxduration_desc'] = 'Upper limit for the working time in the briefing. Smaller models can only build smaller simulations reliably – 60 minutes is a good limit for models around 30B parameters.';
$string['settings_maxmessagelength'] = 'Maximum message length (characters)';
$string['settings_maxmessagelength_desc'] = 'Maximum length of a single learner message in a conversation.';
$string['settings_maxturns'] = 'Maximum messages per conversation';
$string['settings_maxturns_desc'] = 'Upper limit of learner messages per conversation and character (the AI suggests a number per character, which is capped to this value). Limits cost and load (see also the maximum number of restarts).';
$string['showpersonas'] = 'Show conversation characters';
$string['simgen:converse'] = 'Talk to AI characters in AI simulations';
$string['simtype_conversation'] = 'Live conversation with AI characters';
$string['error_conversationbusy'] = 'The character is still answering your previous message. Please wait a moment.';
$string['error_resetlimit'] = 'This conversation cannot be restarted any more.';
$string['settings_maxresets'] = 'Maximum restarts per conversation';
$string['settings_maxresets_desc'] = 'How often a learner may restart a conversation with a character ("Start again"). Together with the maximum number of messages this limits the AI requests per learner and character to messages × (restarts + 1).';
$string['aiwarning_conversationhint'] = 'Live conversations use the AI purpose "{$a}". The requests are made by the learners: make sure in the AI manager that the role of the students may use this purpose (model assigned, quota > 0).';
$string['aiwarning_purpose'] = 'The AI purpose "{$a->purpose}" is currently not available for you: {$a->message}';
$string['aiwarning_unavailable'] = 'The AI is currently not available for you: {$a}';

// Version 0.4: facilitator guide for teachers.
$string['error_noguide'] = 'The AI response does not contain a guide. Please create it again.';
$string['guide'] = 'Facilitator guide';
$string['guide_badge_error'] = 'error';
$string['guide_badge_none'] = 'none yet';
$string['guide_badge_running'] = 'in progress';
$string['guide_create'] = 'Create guide';
$string['guide_createnow'] = 'Create now';
$string['guide_downloadhtml'] = 'Download (HTML, printable)';
$string['guide_downloadmd'] = 'Download (Markdown)';
$string['guide_edit'] = 'Edit guide';
$string['guide_edit_help'] = 'The guide is written in Markdown (# heading, - list, **bold**, > quote, tables with |). Your version is kept until you choose "Create again".';
$string['guide_intro'] = 'The guide supports you in using this version following brief → play → debrief: preparation, introduction, facilitation, debrief and subject background. It is only visible to teachers.';
$string['guide_none'] = 'There is no guide for this simulation yet.';
$string['guide_none_draft'] = 'This version has no guide yet. It is created automatically when you publish the version. If you want to prepare beforehand, you can create it now.';
$string['guide_none_published'] = 'This version has no guide yet.';
$string['guide_noversion'] = 'There is no version with a guide yet.';
$string['guide_notify_error_body'] = 'The guide for version {$a->num} of the simulation "{$a->name}" could not be created. Details: {$a->url}';
$string['guide_notify_error_subject'] = 'Guide failed: {$a->name}';
$string['guide_notify_success_body'] = 'The guide for version {$a->num} of the simulation "{$a->name}" is ready: {$a->url}';
$string['guide_notify_success_subject'] = 'Guide ready: {$a->name}';
$string['guide_open'] = 'Facilitator guide for teachers';
$string['guide_origin_ai'] = 'Created with AI on {$a}';
$string['guide_origin_manual'] = 'Manually edited on {$a}';
$string['guide_queued'] = 'The guide is being created.';
$string['guide_queuedonpublish'] = 'The facilitator guide for teachers is being created now.';
$string['guide_recreate'] = 'Create again';
$string['guide_recreate_confirm'] = 'Create the guide again with AI? The current guide will be replaced.';
$string['guide_recreate_confirm_manual'] = 'Create the guide again with AI? Your manual changes will be lost.';
$string['guide_saved'] = 'The guide has been saved.';
$string['guide_status_error'] = 'The guide could not be created.';
$string['guide_status_generating'] = 'The guide is being created …';
$string['guide_status_generating_help'] = 'This usually takes 1–3 minutes. You can leave the page; you will be notified when the guide is ready.';
$string['guide_status_pending'] = 'The creation of the guide is queued.';
$string['settings_autoguide'] = 'Create guide on publishing';
$string['settings_autoguide_desc'] = 'Automatically creates the facilitator guide (brief, play, debrief) when a version is published and has no guide yet. This is an additional AI request using the generation purpose. Teachers can create the guide themselves at any time, also without this setting.';
$string['settings_prompt_guide'] = 'Prompt template: facilitator guide';
$string['settings_prompt_guide_desc'] = 'Used for the facilitator guide. Knows the same placeholders as the generation plus {{CURRENT_CODE}} (code of the version); {{TECHNICAL_RULES}} is not used here. The answer should consist of a single Markdown code block.';
$string['simgen:viewguide'] = 'View the facilitator guide';
$string['task_generateguide'] = 'Create the guide of an AI simulation';
// Quality gate (0.5).
$string['autorepair_static'] = 'The automatic check of the code found the following errors. Fix them completely without changing working parts and check the rest of the code for similar problems:
{$a}';
$string['check_badge_failed'] = 'Test: error';
$string['check_badge_help'] = 'Automatic test: when the version is first opened in the preview, it is checked whether the simulation starts without errors.';
$string['check_badge_passed'] = 'Test passed';
$string['check_badge_pending'] = 'Test pending';
$string['check_duplicatekeys'] = 'useSimState uses the same key more than once ({$a}); the states overwrite each other. Every key may only be used once.';
$string['check_empty'] = 'The response contains no code.';
$string['check_error'] = 'Error during the test';
$string['check_external'] = 'The code loads external resources (images, scripts, fonts or stylesheets from a URL). They are blocked in the sandbox; use inline SVG, canvas or data: URIs.';
$string['check_issues_title'] = 'Findings of the automatic check';
$string['check_network'] = 'The code tries to load or send data over the network ({$a}). This is blocked in the sandbox; all data must be contained in the code.';
$string['check_noapp'] = 'There is no component "App". Define the main component as function App() { … }.';
$string['check_nocomplete'] = 'The simulation never reports completion (SimBridge.complete is missing). Call SimBridge.complete({ score, maxScore, summary }) at the end.';
$string['check_repairqueued'] = 'An error occurred when the simulation started. The AI is now fixing it automatically.';
$string['check_severity_error'] = 'Error';
$string['check_storage'] = 'The code uses {$a}. This is not available in the sandbox and crashes the simulation; use useSimState or SimBridge.set/get.';
$string['check_truncated'] = 'The code seems to end in the middle of the program (output limit of the model reached?). Return the complete code and be more concise, e.g. with less text and reusable components.';
$string['check_unknownpersona'] = 'useConversation uses personas that are not defined: {$a}. Only use the ids of the persona JSON block.';
$string['origin_autorepair'] = 'Automatic fix';
$string['settings_autorepair'] = 'Automatic repair rounds';
$string['settings_autorepair_desc'] = 'If the automatic check finds errors in a version created by the AI (code check right after the generation or test when first opened in the preview), the AI fixes them on its own - at most this many rounds in a row. Every round is an additional AI request. Manually edited code is never changed automatically.';

// Working time (0.5.1).
$string['check_noplan'] = 'The response contains no schedule; it is not checked whether the simulation fills the working time of {$a->minutes} minutes.';
$string['check_planlong'] = 'The schedule covers {$a->planned} minutes, clearly more than the working time of {$a->minutes} minutes.';
$string['check_planshort'] = 'The schedule only covers {$a->planned} of {$a->minutes} minutes of working time. Extend the simulation with further tasks in which the learners are active (predict, calculate, decide and justify) instead of adding text, and update the schedule.';
$string['privacy:metadata:simgen_states:activetime'] = 'Active working time (measured while the page is visible and the person was active)';
$string['report_activetime'] = 'Active time';
$string['report_activetime_total'] = 'Total, incl. time after completion';
$string['report_extend'] = 'Extend scope';
$string['report_extend_request'] = 'The students only need a median of {$a->median} instead of the planned {$a->planned} minutes for this simulation (measured active time until completion, {$a->count} persons). Extend the simulation so that it fills the working time: additional or deeper tasks in which the learners are active (predict, calculate, set parameters to reach a goal, decide and justify), "Next" only after an own input. Keep everything that works well and update the schedule.';
$string['report_minutes'] = '{$a} min';
$string['report_time'] = 'Working time: planned {$a->planned} min, measured median {$a->median} min (active time until completion, {$a->count} persons).';
$string['report_time_short'] = 'The simulation is clearly shorter than planned.';
$string['timeplan'] = 'Schedule';
$string['timeplan_activity'] = 'Activity';
$string['timeplan_minutes'] = 'Min.';
$string['timeplan_phase'] = 'Phase';
$string['timeplan_total'] = '{$a->planned} of {$a->minutes} min';
