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
 * Deutsche Sprachstrings von mod_simgen (Schweizer Rechtschreibung).
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['badge_draft'] = 'Entwurf – für Studierende nicht sichtbar';
$string['badge_published'] = 'Veröffentlicht';
$string['changerequest'] = 'Was soll geändert werden?';
$string['changerequest_help'] = 'Beschreiben Sie möglichst konkret, was die KI ändern soll, z. B. «Ergänze ein drittes Szenario zum Lieferantenausfall», «Der Schieberegler für den Zinssatz soll bis 15 % gehen», «Das Feedback ist zu knapp». Sie können auch eine Fehlermeldung einfügen.';
$string['changerequest_placeholder'] = 'z. B. Ergänze eine Fortschrittsanzeige und gestalte das Feedback ausführlicher …';
$string['codetype'] = 'Code-Typ';
$string['codetype_html'] = 'Vollständiges HTML-Dokument';
$string['codetype_react'] = 'React-Komponente (JSX) + CSS';
$string['completiondetail:finished'] = 'Simulation abschliessen';
$string['completionfinished'] = 'Abschluss erforderlich';
$string['completionfinished_help'] = 'Die Aktivität gilt als abgeschlossen, sobald die Simulation ihren Abschluss meldet (der generierte Code ruft am Ende SimBridge.complete() auf).';
$string['completionfinished_label'] = 'Studierende müssen die Simulation abschliessen';
$string['content'] = 'Fachliche Inhalte';
$string['content_help'] = 'Texte, Definitionen, Formeln, Fälle oder Daten, auf denen die Simulation beruhen soll. Je präziser die Inhalte, desto fachlich korrekter die Simulation.';
$string['contentfiles'] = 'Material (Upload)';
$string['contentfiles_help'] = 'Der Text dieser Dateien wird ausgelesen und an die KI übergeben: PDF, DOCX, PPTX, ODT, ODP, TXT, MD, CSV, JSON, HTML. Bilder und eingescannte PDFs können nicht gelesen werden. Nur Lehrpersonen haben Zugriff auf die Dateien.';
$string['css'] = 'CSS';
$string['difficulty'] = 'Niveau';
$string['difficulty_advanced'] = 'Fortgeschritten (Transfer)';
$string['difficulty_intermediate'] = 'Vertiefung (Anwendung)';
$string['difficulty_intro'] = 'Einstieg (Grundlagen)';
$string['downloadhtml'] = 'Als HTML herunterladen';
$string['durationmin'] = 'Bearbeitungszeit (Minuten)';
$string['durationmin_help'] = 'Geplante Bearbeitungszeit der Studierenden. Die KI passt den Umfang der Simulation daran an.';
$string['editbriefing'] = 'Briefing bearbeiten';
$string['editcode'] = 'Code bearbeiten';
$string['editcode_help'] = 'Änderungen werden als neue Version (Entwurf) gespeichert. Veröffentlichen Sie sie anschliessend auf der Aktivitätsseite. Zur Laufzeit verfügbar: React 18, die Hooks als globale Funktionen, useSimState(key, initial) und SimBridge.';
$string['error_ai'] = 'Die KI-Anfrage ist fehlgeschlagen: {$a}';
$string['error_alreadyrunning'] = 'Es läuft bereits eine Generierung. Bitte warten Sie, bis sie abgeschlossen ist.';
$string['error_duration'] = 'Bitte geben Sie eine Bearbeitungszeit zwischen 1 und {$a} Minuten an.';
$string['error_minheight'] = 'Bitte geben Sie eine Höhe zwischen 300 und 3000 Pixel an.';
$string['error_noapp'] = 'Die KI-Antwort enthält keine Komponente namens «App». Möglicherweise wurde die Antwort abgeschnitten (Kontextlänge bzw. Ausgabelimit des Modells prüfen).';
$string['error_nocode'] = 'Die KI-Antwort enthält keinen Codeblock. Bitte versuchen Sie es erneut oder verwenden Sie ein leistungsfähigeres Modell.';
$string['error_noversion'] = 'Für diese Simulation ist keine Version verfügbar.';
$string['error_outcomes'] = 'Bitte beschreiben Sie die Lernergebnisse (mindestens einen Satz).';
$string['error_prefill'] = 'Beim Ausführen der Simulation tritt folgender Fehler auf. Finde die Ursache, behebe sie und prüfe den restlichen Code auf ähnliche Probleme:

{$a}';
$string['error_rawresponse'] = 'Beginn der KI-Antwort:';
$string['error_stale'] = 'Die Generierung wurde nicht abgeschlossen (Zeitüberschreitung oder Serverneustart). Bitte versuchen Sie es erneut.';
$string['error_statetoolarge'] = 'Der Fortschritt ist zu gross, um gespeichert zu werden (maximal {$a} KB).';
$string['error_unexpected'] = 'Unerwarteter Fehler: {$a}';
$string['errorreport_fix'] = 'Fehler von der KI beheben lassen';
$string['errorreport_help'] = 'Diesen Hinweis sehen nur Lehrpersonen. Studierende sehen in der Simulation eine kurze Fehlermeldung.';
$string['errorreport_title'] = 'Die Simulation hat einen Fehler gemeldet';
$string['event_simulation_completed'] = 'Simulation abgeschlossen';
$string['event_simulation_generated'] = 'Simulationsversion generiert';
$string['extract_failed'] = 'Der Text dieser Datei konnte nicht ausgelesen werden';
$string['feedbackmode'] = 'Feedback';
$string['feedbackmode_end'] = 'Debriefing am Ende';
$string['feedbackmode_hints'] = 'Gestufte Hinweise auf Anfrage';
$string['feedbackmode_immediate'] = 'Sofortiges Feedback nach jeder Aktion';
$string['feedbackmode_none'] = 'Explorativ, ohne bewertendes Feedback';
$string['form_aihint'] = 'Die folgenden Angaben werden in ein Prompt-Gerüst eingesetzt und an die im KI-Manager konfigurierte KI gesendet. Die Simulation entsteht als Entwurf: Sie können sie testen, überarbeiten und anschliessend für Studierende veröffentlichen. Bitte keine Personendaten von Studierenden eingeben.';
$string['generate'] = 'KI-Generierung';
$string['generate_help'] = 'Wenn aktiviert, generiert die KI nach dem Speichern eine neue Version aus dem Briefing. Die Generierung läuft im Hintergrund und dauert meist 1–5 Minuten; Sie werden benachrichtigt, sobald sie fertig ist. Bestehende Versionen bleiben erhalten.';
$string['generate_label_new'] = 'Simulation nach dem Speichern generieren';
$string['generate_label_update'] = 'Nach dem Speichern eine neue Version aus dem geänderten Briefing generieren';
$string['generatefirst'] = 'Simulation jetzt generieren';
$string['generatenow'] = 'Jetzt direkt generieren';
$string['generation_success'] = 'Eine neue Version wurde generiert. Bitte testen und veröffentlichen Sie sie.';
$string['hdr_content'] = 'Inhalte und Material';
$string['hdr_didactics'] = 'Didaktischer Rahmen';
$string['hdr_outcomes'] = 'Lernergebnisse';
$string['hdr_player'] = 'Fortschritt und Generierung';
$string['hdr_simulation'] = 'Gestaltung der Simulation';
$string['lang_de'] = 'Deutsch';
$string['lang_en'] = 'Englisch';
$string['lang_fr'] = 'Französisch';
$string['lang_it'] = 'Italienisch';
$string['lang_rm'] = 'Rätoromanisch';
$string['learningoutcomes'] = 'Lernergebnisse';
$string['learningoutcomes_help'] = 'Was sollen die Studierenden nach der Simulation können? Verwenden Sie beobachtbare Verben (z. B. erklären, berechnen, entscheiden, begründen), ein Lernergebnis pro Zeile. Die KI richtet jede Interaktion an diesen Lernergebnissen aus.';
$string['learningoutcomes_placeholder'] = 'Die Studierenden können …
- erklären, wie … sich auf … auswirkt
- in … eine Entscheidung treffen und begründen';
$string['messageprovider:generationfinished'] = 'Generierung einer KI-Simulation abgeschlossen';
$string['minheight'] = 'Mindesthöhe (px)';
$string['modulename'] = 'KI-Simulation';
$string['modulename_help'] = 'Mit der Aktivität KI-Simulation erstellen Sie aus einem didaktischen Briefing (Zielgruppe, Bearbeitungszeit, Lernergebnisse, Inhalte) eine interaktive Lernsimulation (React). Dafür wird die im KI-Manager konfigurierte KI verwendet.

Lehrpersonen können generierte Versionen testen, überarbeiten (mit Änderungswünschen oder automatischer Fehlerbehebung) und bearbeiten und eine Version für Studierende veröffentlichen. Studierende arbeiten direkt in Moodle; ihr Fortschritt kann gespeichert werden, damit sie später weitermachen können.';
$string['modulenameplural'] = 'KI-Simulationen';
$string['nopublished_help'] = 'Es ist noch keine Version veröffentlicht – Studierende sehen die Simulation noch nicht. Testen Sie die Version unten und klicken Sie auf «Diese Version veröffentlichen».';
$string['notavailable'] = 'Diese Simulation ist noch nicht verfügbar.';
$string['notify_error_body'] = 'Die Generierung der Simulation «{$a->name}» ist fehlgeschlagen. Details: {$a->url}';
$string['notify_error_subject'] = 'Generierung fehlgeschlagen: {$a->name}';
$string['notify_success_body'] = 'Eine neue Version der Simulation «{$a->name}» wurde generiert. Hier testen und veröffentlichen: {$a->url}';
$string['notify_success_subject'] = 'Simulation bereit: {$a->name}';
$string['origin_ai'] = 'KI-Generierung';
$string['origin_manual'] = 'Manuell bearbeitet';
$string['origin_revision'] = 'KI-Überarbeitung';
$string['player_close'] = 'Schliessen';
$string['player_compileerror'] = 'Der Code der Simulation enthält einen Syntaxfehler';
$string['player_completed'] = 'Simulation abgeschlossen';
$string['player_completedbefore'] = 'Abgeschlossen';
$string['player_errordetails'] = 'Technische Details';
$string['player_errortext'] = 'Die Simulation kann nicht angezeigt werden. Bitte laden Sie sie neu oder informieren Sie Ihre Lehrperson.';
$string['player_errortextnonfatal'] = 'Es ist ein Fehler aufgetreten. Sie können versuchen weiterzuarbeiten oder die Simulation neu laden.';
$string['player_errortitle'] = 'Fehler in der Simulation';
$string['player_fullscreen'] = 'Vollbild';
$string['player_libsfailed'] = 'Die Bibliotheken der Simulation konnten nicht geladen werden';
$string['player_loading'] = 'Simulation wird geladen …';
$string['player_noapp'] = 'Die Simulation enthält keine Startkomponente (App)';
$string['player_reload'] = 'Neu laden';
$string['player_reset'] = 'Neu beginnen';
$string['player_resetconfirm'] = 'Ihr gespeicherter Fortschritt in dieser Simulation wird gelöscht und die Simulation beginnt von vorne. Fortfahren?';
$string['player_resettitle'] = 'Neu beginnen';
$string['player_saved'] = 'Gespeichert um {$a}';
$string['player_saveerror'] = 'Fortschritt konnte nicht gespeichert werden';
$string['player_saving'] = 'Wird gespeichert …';
$string['pluginadministration'] = 'Administration KI-Simulation';
$string['pluginname'] = 'KI-Simulation';
$string['preview_badge'] = 'Vorschau (Entwurf) – Fortschritt wird nicht gespeichert';
$string['priorknowledge'] = 'Vorwissen';
$string['priorknowledge_help'] = 'Was wissen die Studierenden bereits? Welche Fehlvorstellungen sind verbreitet? Das hilft der KI, das passende Niveau zu wählen und typische Fehler aufzugreifen.';
$string['privacy:metadata:aiprovider'] = 'Über den KI-Manager (local_ai_manager) werden an den konfigurierten KI-Anbieter gesendet: das von der Lehrperson eingegebene Briefing (inkl. ausgelesenem Material) und – in Live-Gesprächssimulationen – die Nachrichten der Lernenden an die KI-Figuren samt Gesprächsverlauf.';
$string['privacy:metadata:aiprovider:briefing'] = 'Briefing und Material der Lehrperson';
$string['privacy:metadata:core_files'] = 'Von Lehrpersonen hochgeladenes Material wird als Datei gespeichert.';
$string['privacy:metadata:simgen_states'] = 'Fortschritt und Ergebnisse von Personen in einer Simulation.';
$string['privacy:metadata:simgen_states:completed'] = 'Ob die Person die Simulation abgeschlossen hat.';
$string['privacy:metadata:simgen_states:rawscore'] = 'Von der Simulation gemeldete Punktzahl.';
$string['privacy:metadata:simgen_states:statedata'] = 'Gespeicherter Zustand der Simulation (Eingaben, Entscheidungen, Schritt).';
$string['privacy:metadata:simgen_states:summary'] = 'Von der Simulation gemeldete Ergebniszusammenfassung.';
$string['privacy:metadata:simgen_states:timemodified'] = 'Zeitpunkt der letzten Speicherung.';
$string['privacy:metadata:simgen_states:userid'] = 'Die Person.';
$string['privacy:metadata:simgen_versions'] = 'Generierte oder bearbeitete Versionen einer Simulation.';
$string['privacy:metadata:simgen_versions:revisionnote'] = 'Änderungswunsch oder Notiz der erstellenden Person.';
$string['privacy:metadata:simgen_versions:timecreated'] = 'Zeitpunkt der Erstellung.';
$string['privacy:metadata:simgen_versions:userid'] = 'Die Person, die die Version erstellt hat.';
$string['privacy:path:progress'] = 'Fortschritt';
$string['privacy:path:versions'] = 'Versionen';
$string['publish'] = 'Diese Version veröffentlichen';
$string['published'] = 'Version {$a} ist jetzt für Studierende sichtbar.';
$string['publishedinfo'] = 'Studierende sehen Version {$a}.';
$string['purposeplacedescription'] = 'Generierung und Überarbeitung interaktiver Lernsimulationen (Aktivität «KI-Simulation»)';
$string['report'] = 'Auswertung';
$string['report_completed'] = 'Abgeschlossen';
$string['report_empty'] = 'Bisher hat niemand die Simulation bearbeitet.';
$string['report_lastactivity'] = 'Letzte Aktivität';
$string['report_score'] = 'Punkte';
$string['report_started'] = 'Begonnen';
$string['report_status'] = 'Status';
$string['report_summary'] = '{$a->started} Teilnehmende haben die Simulation begonnen, {$a->completed} abgeschlossen ({$a->enrolled} eingeschrieben).';
$string['report_summarycol'] = 'Ergebniszusammenfassung';
$string['requirements'] = 'Weitere Wünsche';
$string['requirements_help'] = 'Alles, was die KI zusätzlich berücksichtigen soll: konkrete Szenarien, Interaktionsformen (Schieberegler, Drag & Drop, Diagramme), Rollen, Praxisbeispiele, CI-Farben, was vermieden werden soll …';
$string['requirements_placeholder'] = 'z. B. Verwende ein Beispiel aus dem Bündner Tourismus. Zeige die Ergebnisse als Liniendiagramm. Max. 5 Schritte.';
$string['resetstates'] = 'Gespeicherten Fortschritt aller Personen löschen';
$string['resetuser'] = 'Zurücksetzen';
$string['resetuser_confirm'] = 'Fortschritt und Abschluss dieser Person löschen?';
$string['resetuserdone'] = 'Der Fortschritt wurde gelöscht.';
$string['retry'] = 'Erneut versuchen';
$string['revise'] = 'Mit KI überarbeiten';
$string['revise_base'] = 'Zu überarbeitende Version';
$string['revise_fromscratch'] = 'Keine – neu aus dem Briefing generieren';
$string['revise_help'] = 'Die KI erhält das Briefing, den Code der gewählten Version und Ihren Änderungswunsch und erstellt daraus eine neue Version (Entwurf). Die aktuell veröffentlichte Version bleibt für Studierende sichtbar, bis Sie die neue veröffentlichen.';
$string['revise_submit'] = 'Neue Version erstellen';
$string['revision_queued'] = 'Die Überarbeitung wurde gestartet. Das dauert meist 1–5 Minuten.';
$string['revisionnote'] = 'Notiz zu dieser Version';
$string['savenewversion'] = 'Als neue Version speichern';
$string['savestate'] = 'Fortschritt speichern';
$string['savestate_help'] = 'Wenn aktiviert, wird der Fortschritt jeder Person automatisch in Moodle gespeichert. So kann die Simulation unterbrochen und später fortgesetzt werden. Empfohlen für Simulationen ab etwa 15 Minuten.';
$string['savestate_label'] = 'Studierende können unterbrechen und später weitermachen';
$string['settings_heading_ai'] = 'KI';
$string['settings_heading_ai_desc'] = 'Die Anfragen laufen über den KI-Manager (local_ai_manager). Weisen Sie dort dem gewählten Einsatzzweck ein leistungsfähiges Modell zu und erhöhen Sie das Request-Timeout (local_ai_manager | requesttimeout) auf mindestens 300 Sekunden – die Code-Generierung braucht Zeit.';
$string['settings_heading_prompts'] = 'Prompt-Vorlagen';
$string['settings_heading_prompts_desc'] = 'Platzhalter: {{TITLE}}, {{DESCRIPTION}}, {{SUBJECT}}, {{TARGETGROUP}}, {{PRIORKNOWLEDGE}}, {{DIFFICULTY}}, {{DURATION}}, {{SOCIALFORM}}, {{LANGUAGE}}, {{SIMTYPE}}, {{FEEDBACKMODE}}, {{LEARNINGOUTCOMES}}, {{REQUIREMENTS}}, {{CONTENT}} und {{TECHNICAL_RULES}} (Laufzeit-Vertrag des Players; wird automatisch angehängt, falls er fehlt). Die Überarbeitungs-Vorlage kennt zusätzlich {{CHANGE_REQUEST}} und {{CURRENT_CODE}}. Leer = eingebaute Standardvorlage.';
$string['settings_heading_runtime'] = 'Laufzeit-Bibliotheken';
$string['settings_heading_runtime_desc'] = 'Die Simulationen laufen in einem abgeschotteten iframe (Sandbox) und laden React, ReactDOM und Babel. Aus Datenschutzgründen können Sie die Dateien auf dem Moodle-Server ablegen (z. B. /mod/simgen/vendor/react.production.min.js); relative Pfade sind erlaubt.';
$string['settings_libbabel'] = 'URL von Babel standalone';
$string['settings_libreact'] = 'URL von React (UMD)';
$string['settings_libreactdom'] = 'URL von ReactDOM (UMD)';
$string['settings_maxcontentchars'] = 'Maximale Materiallänge (Zeichen)';
$string['settings_maxcontentchars_desc'] = 'Längeres Material wird abgeschnitten. An das Kontextfenster des Modells anpassen (Faustregel: 4 Zeichen ≈ 1 Token; die Antwort benötigt zusätzlich etwa 8000–16000 Tokens).';
$string['settings_maxstatesize'] = 'Maximale Fortschrittsgrösse (KB)';
$string['settings_maxstatesize_desc'] = 'Maximale Grösse des gespeicherten Fortschritts pro Person und Simulation.';
$string['settings_pdftotext'] = 'Pfad zu pdftotext';
$string['settings_pdftotext_desc'] = 'Optional (poppler-utils). Wird verwendet, um Text aus PDF-Dateien auszulesen. Ohne diese Angabe wird – falls vorhanden – Ghostscript ($CFG->pathtogs) verwendet.';
$string['settings_showdefault'] = 'Eingebaute Vorlage anzeigen (zum Anpassen in das Feld kopieren)';
$string['settings_purpose'] = 'KI-Einsatzzweck';
$string['settings_purpose_desc'] = 'Einsatzzweck (Purpose) des KI-Managers, über den die Anfragen laufen. Empfohlen: «Einzelner Prompt» (singleprompt). Verwendet wird das Modell, das diesem Einsatzzweck im KI-Manager zugewiesen ist (z. B. ein lokales Modell via Ollama oder ChatGPT). Lehrpersonen benötigen die Berechtigung local/ai_manager:use und müssen den Einsatzzweck nutzen dürfen.';
$string['settings_prompt_generate'] = 'Prompt-Vorlage: Generierung';
$string['settings_prompt_generate_desc'] = 'Wird für die erste Generierung und für die Neugenerierung aus dem Briefing verwendet.';
$string['settings_prompt_revise'] = 'Prompt-Vorlage: Überarbeitung';
$string['settings_prompt_revise_desc'] = 'Wird verwendet, wenn eine Version mit Änderungswunsch oder Fehlermeldung überarbeitet wird.';
$string['showprompt'] = 'An die KI gesendeten Prompt anzeigen';
$string['showresponse'] = 'KI-Antwort anzeigen';
$string['simgen:addinstance'] = 'Neue KI-Simulation hinzufügen';
$string['simgen:manage'] = 'KI-Simulationen generieren, überarbeiten, bearbeiten und veröffentlichen';
$string['simgen:savestate'] = 'Eigenen Fortschritt in KI-Simulationen speichern';
$string['simgen:view'] = 'KI-Simulationen ansehen';
$string['simgen:viewreports'] = 'Fortschritt und Ergebnisse aller Teilnehmenden ansehen';
$string['simlanguage'] = 'Sprache der Simulation';
$string['simtype'] = 'Simulationstyp';
$string['simtype_auto'] = 'KI wählt passend';
$string['simtype_data'] = 'Datenexploration';
$string['simtype_decision'] = 'Entscheidungsszenario / Fallstudie';
$string['simtype_game'] = 'Planspiel / Strategie (Runden)';
$string['simtype_help'] = '* Parameter-Exploration: Eingangsgrössen eines Modells mit Schiebereglern verändern und Auswirkungen beobachten.
* Entscheidungsszenario: verzweigter Fall mit Konsequenzen und Debriefing.
* Prozesstraining: Ein Verfahren Schritt für Schritt durchführen.
* Virtuelles Labor: Hypothese, Experiment, Messung, Auswertung.
* Dialog / Rollenspiel: Gespräch mit vordefinierten Antwortoptionen.
* Planspiel: mehrere Runden mit Ressourcen und Kennzahlen.
* Datenexploration: einen Datensatz filtern, gruppieren und visualisieren.
* Live-Gespräch: freie Gespräche mit KI-Figuren, deren Antworten zur Laufzeit erzeugt werden (die Nachrichten der Lernenden gehen an die KI).';
$string['simtype_lab'] = 'Virtuelles Labor / Experiment';
$string['simtype_parameter'] = 'Parameter-Exploration («Was wäre, wenn»)';
$string['simtype_process'] = 'Prozess- / Verfahrenstraining';
$string['simtype_roleplay'] = 'Dialog / Rollenspiel';
$string['socialform'] = 'Setting';
$string['socialform_classroom'] = 'Präsenz / Beamer (durch Lehrperson gesteuert)';
$string['socialform_flipped'] = 'Vorbereitung auf die Präsenz (Flipped Classroom)';
$string['socialform_group'] = 'Partner- oder Gruppenarbeit an einem Gerät';
$string['socialform_individual'] = 'Einzelarbeit im Selbststudium';
$string['sourcecode'] = 'Code';
$string['status_error'] = 'Die Generierung ist fehlgeschlagen.';
$string['status_generating'] = 'Die KI generiert die Simulation …';
$string['status_generating_help'] = 'Das dauert meist 1–5 Minuten. Sie können die Seite verlassen; Sie werden benachrichtigt, sobald die Simulation fertig ist.';
$string['status_none'] = 'Es wurde noch keine Simulation generiert.';
$string['status_pending'] = 'Die Generierung ist eingeplant.';
$string['status_pending_help'] = 'Sie startet mit dem nächsten Cron-Lauf. Wenn es zu lange dauert, können Sie sie direkt starten (die Seite lädt dann, bis die Generierung abgeschlossen ist).';
$string['subject'] = 'Fach / Thema';
$string['subject_help'] = 'Fach und konkretes Thema der Simulation, z. B. «Mikroökonomie – Preiselastizität der Nachfrage».';
$string['targetgroup'] = 'Zielgruppe';
$string['targetgroup_help'] = 'Studiengang, Semester und Niveau, z. B. «Bachelor Tourismus, 2. Semester, Teilzeitstudierende mit Berufserfahrung».';
$string['task_generate'] = 'KI-Simulation generieren';
$string['unpublish'] = 'Veröffentlichung zurückziehen';
$string['unpublished'] = 'Die Simulation ist für Studierende nicht mehr sichtbar.';
$string['version'] = 'Version';
$string['version_created'] = 'Erstellt';
$string['version_model'] = 'Modell';
$string['version_note'] = 'Änderungswunsch / Notiz';
$string['version_origin'] = 'Herkunft';
$string['versiondetails'] = 'Details zu dieser Version';
$string['versionlabel'] = 'Version {$a->num} – {$a->origin} – {$a->time}';
$string['versionsaved'] = 'Die neue Version wurde als Entwurf gespeichert.';
$string['versionshort'] = 'Version {$a}';

// Version 0.2: maximale Bearbeitungszeit und Live-Gespräche.
$string['conversation_none'] = 'Keine Gespräche.';
$string['conversation_privacynotice'] = 'In dieser Simulation sprechen Sie mit KI-gesteuerten Figuren. Ihre Nachrichten werden an die KI (über den KI-Manager) übermittelt und sind für Ihre Lehrpersonen einsehbar. Geben Sie keine persönlichen Daten ein.';
$string['conversation_signals'] = 'Letzte Einschätzung:';
$string['conversation_transcript'] = 'Gespräche von {$a}';
$string['durationmin_max'] = 'Bearbeitungszeit (Minuten, max. {$a})';
$string['error_conversationai'] = 'Die KI konnte nicht antworten: {$a}';
$string['error_conversationfinished'] = 'Die maximale Anzahl Nachrichten in diesem Gespräch ist erreicht.';
$string['error_emptymessage'] = 'Bitte geben Sie eine Nachricht ein.';
$string['error_emptyreply'] = 'Die KI hat eine leere Antwort geliefert. Bitte versuchen Sie es erneut.';
$string['error_messagetoolong'] = 'Die Nachricht ist zu lang (maximal {$a} Zeichen).';
$string['error_nopersonas'] = 'Die KI-Antwort enthält keine gültigen Gesprächsfiguren (JSON-Block mit «personas»). Bitte erneut generieren oder unter «Code bearbeiten» ergänzen.';
$string['error_personasjson'] = 'Die Figuren-Definition ist kein gültiges JSON: {$a}';
$string['error_unknownpersona'] = 'Unbekannte Gesprächsfigur: {$a}';
$string['persona_maxturns'] = 'max. {$a} Nachrichten';
$string['persona_signals'] = 'Signale';
$string['personas'] = 'Gesprächsfiguren (JSON)';
$string['personas_help'] = 'Nur für Live-Gesprächssimulationen. JSON in der Form {"personas": [{"id": "gast", "name": "Frau Keller", "role": "Hotelgast", "prompt": "Du bist …", "opening": "Erster Satz", "maxTurns": 8, "signals": {"stimmung": "Zufriedenheit 1–5", "geloest": "true, sobald …"}}]}. Der Prompt bleibt auf dem Server; Lernende sehen ihn nie. Leer lassen für Simulationen ohne KI-Figuren.';
$string['player_conversationtimeout'] = 'Die KI hat nicht rechtzeitig geantwortet. Bitte versuchen Sie es erneut.';
$string['player_conversationunavailable'] = 'Gespräche sind nur innerhalb von Moodle verfügbar.';
$string['player_unknownpersona'] = 'Unbekannte Gesprächsfigur';
$string['privacy:metadata:aiprovider:conversation'] = 'Nachrichten der Lernenden an KI-Figuren und der Gesprächsverlauf';
$string['privacy:metadata:simgen_conversations'] = 'Live-Gespräche von Personen mit KI-Figuren.';
$string['privacy:metadata:simgen_conversations:messagedata'] = 'Nachrichten der Person und Antworten der KI-Figur.';
$string['privacy:metadata:simgen_conversations:signaldata'] = 'Letzte Einschätzung des Gesprächs durch die KI (z. B. Stimmung).';
$string['privacy:metadata:simgen_conversations:timemodified'] = 'Zeitpunkt der letzten Nachricht.';
$string['privacy:metadata:simgen_conversations:userid'] = 'Die Person.';
$string['privacy:path:conversations'] = 'Gespräche';
$string['purposeplacedescription_conversation'] = 'Antworten von KI-Figuren in Live-Gesprächssimulationen (Aktivität «KI-Simulation»); die Nachrichten der Lernenden werden übermittelt';
$string['report_conversationlink'] = 'Anzeigen ({$a} Nachrichten)';
$string['report_conversations'] = 'Gespräche';
$string['settings_conversationpurpose'] = 'KI-Einsatzzweck für Gespräche';
$string['settings_conversationpurpose_desc'] = 'Einsatzzweck des KI-Managers für die Antworten der KI-Figuren zur Laufzeit. Diese Anfragen stellen die Lernenden: Ihre Rolle im KI-Manager braucht Zugriff auf diesen Einsatzzweck (und die Berechtigung local/ai_manager:use). Ein anderer Einsatzzweck als für die Generierung erlaubt z. B. ein lokales Modell für Gespräche und ein leistungsfähigeres Modell für die Generierung.';
$string['settings_heading_conversation'] = 'Live-Gespräche';
$string['settings_heading_conversation_desc'] = 'Einstellungen für den Simulationstyp «Live-Gespräch», in dem Lernende frei mit KI-Figuren sprechen. Die Nachrichten der Lernenden werden an die KI gesendet und gespeichert; Lehrpersonen sehen sie in der Auswertung.';
$string['settings_maxduration'] = 'Maximale Bearbeitungszeit (Minuten)';
$string['settings_maxduration_desc'] = 'Obergrenze für die Bearbeitungszeit im Briefing. Kleinere Modelle bauen nur kleinere Simulationen zuverlässig – 60 Minuten sind für Modelle um 30B Parameter ein guter Wert.';
$string['settings_maxmessagelength'] = 'Maximale Nachrichtenlänge (Zeichen)';
$string['settings_maxmessagelength_desc'] = 'Maximale Länge einer einzelnen Nachricht der Lernenden in einem Gespräch.';
$string['settings_maxturns'] = 'Maximale Nachrichten pro Gespräch';
$string['settings_maxturns_desc'] = 'Obergrenze der Nachrichten von Lernenden pro Gespräch und Figur (die KI schlägt pro Figur eine Anzahl vor, die auf diesen Wert begrenzt wird). Begrenzt Kosten und Last.';
$string['showpersonas'] = 'Gesprächsfiguren anzeigen';
$string['simgen:converse'] = 'Mit KI-Figuren in KI-Simulationen sprechen';
$string['simtype_conversation'] = 'Live-Gespräch mit KI-Figuren';
$string['error_conversationbusy'] = 'Die Figur antwortet noch auf Ihre vorherige Nachricht. Bitte warten Sie einen Moment.';
$string['error_resetlimit'] = 'Dieses Gespräch kann nicht mehr neu begonnen werden.';
$string['settings_maxresets'] = 'Maximale Neustarts pro Gespräch';
$string['settings_maxresets_desc'] = 'Wie oft Lernende ein Gespräch mit einer Figur neu beginnen dürfen. Zusammen mit der maximalen Anzahl Nachrichten begrenzt das die KI-Anfragen pro Person und Figur auf Nachrichten × (Neustarts + 1).';
$string['aiwarning_conversationhint'] = 'Live-Gespräche nutzen den KI-Einsatzzweck «{$a}». Die Anfragen stellen die Lernenden: Stellen Sie im KI-Manager sicher, dass die Rolle der Studierenden diesen Einsatzzweck nutzen darf (Modell zugewiesen, Kontingent > 0).';
$string['aiwarning_purpose'] = 'Der KI-Einsatzzweck «{$a->purpose}» ist für Sie derzeit nicht verfügbar: {$a->message}';
$string['aiwarning_unavailable'] = 'Die KI ist für Sie derzeit nicht verfügbar: {$a}';