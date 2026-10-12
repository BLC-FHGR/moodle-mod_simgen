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

namespace mod_simgen\local;

/**
 * Option lists of the briefing form and their descriptions for the prompt.
 *
 * The prompt descriptions are intentionally independent of the UI language: they are written in the
 * language of the prompt template (German) and describe the didactic intention for the LLM.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class options {

    /** @var array simulation types => description for the prompt */
    const SIMTYPES = [
        'auto' => 'Wähle selbst den Simulationstyp, der die Lernergebnisse am besten unterstützt, und begründe die Wahl '
            . 'im Einstiegstext der Simulation in einem Satz.',
        'parameter' => 'Parameter-Exploration («Was-wäre-wenn»): Ein fachlich korrektes Modell, dessen Eingangsgrössen die '
            . 'Lernenden über Schieberegler/Eingabefelder verändern. Auswirkungen werden live visualisiert (SVG-Diagramm, '
            . 'Kennzahlen). Ergänze gezielte Erkundungsaufträge («Was passiert, wenn …?») mit Vorhersage und Auflösung.',
        'decision' => 'Entscheidungs- und Fallszenario: Eine realistische Situation mit mehreren Entscheidungspunkten. '
            . 'Jede Entscheidung hat nachvollziehbare Konsequenzen, die den weiteren Verlauf verändern (Verzweigung). '
            . 'Am Ende ein Debriefing, das die Entscheidungen mit den Lernergebnissen verknüpft.',
        'process' => 'Prozess- und Verfahrenstraining: Die Lernenden führen einen fachlichen Ablauf Schritt für Schritt '
            . 'durch (richtige Reihenfolge, richtige Werkzeuge/Parameter). Fehler werden erkannt und erklärt.',
        'lab' => 'Virtuelles Labor / Experiment: Hypothese formulieren → Versuch aufbauen/Parameter wählen → Messwerte '
            . 'erheben (mit realistischer Streuung) → auswerten und Hypothese prüfen.',
        'roleplay' => 'Dialog- und Rollensimulation: Ein Gespräch mit einer fiktiven Person (z. B. Kundin, Patient, '
            . 'Mitarbeitende) mit vordefinierten Antwortoptionen. Die Reaktionen der Figur hängen von der Wahl ab. '
            . 'Keine KI zur Laufzeit – alle Dialogverläufe sind im Code hinterlegt.',
        'game' => 'Planspiel / Strategiesimulation: Mehrere Runden, in denen die Lernenden Ressourcen einsetzen und '
            . 'Kennzahlen beeinflussen. Zielkonflikte sichtbar machen, Rundenauswertung und Gesamtauswertung.',
        'data' => 'Datenexploration: Ein realistischer, im Code erzeugter Datensatz, den die Lernenden filtern, gruppieren '
            . 'und visualisieren, um Fragestellungen zu beantworten und Fehlschlüsse zu erkennen.',
    ];

    /** @var array feedback modes => description for the prompt */
    const FEEDBACKMODES = [
        'immediate' => 'Sofortiges, erklärendes Feedback nach jeder relevanten Aktion.',
        'hints' => 'Gestuftes Feedback: Lernende können Hinweise anfordern (vom allgemeinen Tipp bis zur Lösung); '
            . 'nach dem Abschluss einer Aufgabe ausführliches Feedback.',
        'end' => 'Kein Feedback während der Bearbeitung; ausführliches Debriefing am Ende mit Bezug auf jede Entscheidung.',
        'none' => 'Exploratives Setting ohne bewertendes Feedback; stattdessen Beobachtungsaufträge und Reflexionsfragen.',
    ];

    /** @var array social forms => description for the prompt */
    const SOCIALFORMS = [
        'individual' => 'Einzelarbeit im Selbststudium (asynchron, ohne Betreuung).',
        'group' => 'Partner- oder Gruppenarbeit an einem gemeinsamen Gerät; fördere Diskussion (z. B. «Diskutiert '
            . 'zuerst, dann entscheidet»).',
        'classroom' => 'Einsatz in der Präsenz/im Plenum, gesteuert durch die Lehrperson am Beamer: grosse Schrift, '
            . 'klare Visualisierung, Pausenpunkte für Diskussionen.',
        'flipped' => 'Vorbereitung vor einer Präsenzveranstaltung (Flipped Classroom): am Ende Ergebnisse/Fragen '
            . 'festhalten, die in die Präsenz mitgenommen werden.',
    ];

    /** @var array difficulty => description for the prompt */
    const DIFFICULTIES = [
        'intro' => 'Einstieg: Grundbegriffe, viel Orientierung und Unterstützung (Scaffolding).',
        'intermediate' => 'Vertiefung: Anwendung des Wissens mit moderater Unterstützung.',
        'advanced' => 'Fortgeschritten: Transfer auf komplexe, offene Situationen, wenig Unterstützung.',
    ];

    /** @var array language codes => language name for the prompt */
    const LANGUAGES = [
        'de' => 'Deutsch (Schweizer Rechtschreibung, «ss» statt «ß»)',
        'en' => 'Englisch',
        'fr' => 'Französisch',
        'it' => 'Italienisch',
        'rm' => 'Rätoromanisch (Rumantsch Grischun)',
    ];

    /**
     * Simulation types for the form.
     *
     * @return array
     */
    public static function simtypes(): array {
        return self::localise(array_keys(self::SIMTYPES), 'simtype_');
    }

    /**
     * Feedback modes for the form.
     *
     * @return array
     */
    public static function feedbackmodes(): array {
        return self::localise(array_keys(self::FEEDBACKMODES), 'feedbackmode_');
    }

    /**
     * Social forms for the form.
     *
     * @return array
     */
    public static function socialforms(): array {
        return self::localise(array_keys(self::SOCIALFORMS), 'socialform_');
    }

    /**
     * Difficulties for the form.
     *
     * @return array
     */
    public static function difficulties(): array {
        return self::localise(array_keys(self::DIFFICULTIES), 'difficulty_');
    }

    /**
     * Languages for the form.
     *
     * @return array
     */
    public static function languages(): array {
        return self::localise(array_keys(self::LANGUAGES), 'lang_');
    }

    /**
     * Builds a localised option list.
     *
     * @param array $keys
     * @param string $prefix language string prefix
     * @return array
     */
    private static function localise(array $keys, string $prefix): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = get_string($prefix . $key, 'mod_simgen');
        }
        return $result;
    }
}
