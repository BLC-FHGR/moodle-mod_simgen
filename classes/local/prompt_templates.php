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
 * Default prompt templates ("Prompt-Gerüst").
 *
 * The didactic templates can be overridden by the admin (Site administration > Plugins > Activity modules >
 * KI-Simulation). The technical rules are not editable because the player depends on them; they are appended
 * automatically if a custom template does not contain the {{TECHNICAL_RULES}} placeholder.
 *
 * @package    mod_simgen
 * @copyright  2026 FH Graubünden
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_templates {

    /** @var string Template for the first generation. */
    const GENERATE = <<<'EOT'
Du bist ein Team aus erfahrener Hochschuldidaktikerin, Instructional Designer und Senior-Frontend-Entwickler (React).
Deine Aufgabe: Entwickle eine interaktive, didaktisch fundierte Lernsimulation für die Hochschullehre, die direkt im Browser läuft.

## Didaktischer Auftrag
- Titel: {{TITLE}}
- Beschreibung der Lehrperson: {{DESCRIPTION}}
- Fach / Thema: {{SUBJECT}}
- Zielgruppe: {{TARGETGROUP}}
- Vorwissen: {{PRIORKNOWLEDGE}}
- Niveau: {{DIFFICULTY}}
- Geplante Bearbeitungszeit: {{DURATION}}
- Setting / Sozialform: {{SOCIALFORM}}
- Sprache aller Texte in der Simulation: {{LANGUAGE}}
- Simulationstyp: {{SIMTYPE}}
- Feedback: {{FEEDBACKMODE}}

### Lernergebnisse («Die Studierenden können …»)
{{LEARNINGOUTCOMES}}

### Weitere Wünsche der Lehrperson
{{REQUIREMENTS}}

### Fachliche Inhalte / Quellenmaterial
Nutze die folgenden Inhalte als fachliche Grundlage. Widersprich ihnen nicht und erfinde keine Fakten. Wo Inhalte fehlen, triff fachlich korrekte, plausible Annahmen und kennzeichne vereinfachende Modellannahmen in der Simulation transparent.
<<<INHALTE
{{CONTENT}}
INHALTE>>>

## Didaktische Anforderungen
1. Constructive Alignment: Jedes Lernergebnis wird durch mindestens eine konkrete Interaktion adressiert.
2. Aufbau: kurzer Einstieg (Ziel, Kontext, Bedienhinweis) → aktive Erkundung bzw. Entscheidungen → Feedback → Abschluss mit Zusammenfassung und 2–3 Reflexionsfragen.
3. Der Umfang passt zur Bearbeitungszeit ({{DURATION}}) – lieber fokussiert als überladen.
4. Die Lernenden handeln aktiv (Parameter verändern, Entscheidungen treffen, Hypothesen prüfen). Keine reine Textpräsentation.
5. Fehler sind Lerngelegenheiten: erklärendes, wertschätzendes Feedback statt nur «falsch».
6. Fachlich korrekt, adressatengerecht, gendergerechte Sprache.
7. Modellgrenzen offenlegen: Wo vereinfacht wird, kurz darauf hinweisen.
8. Zeige den Fortschritt an (z. B. «Schritt 2 von 5»).

## Gestaltung und Barrierefreiheit
- Klares, ruhiges, modernes Design; responsive ab 360 px Breite; Kontraste nach WCAG 2.1 AA.
- Alle Bedienelemente per Tastatur erreichbar, sichtbarer Fokus, sinnvolle Labels und aria-Attribute.
- Informationen nie nur über Farbe vermitteln.
- Grafiken als Inline-SVG oder Canvas. Keine externen Ressourcen (keine Bild-URLs, Webfonts, CDNs oder APIs).

{{TECHNICAL_RULES}}
EOT;

    /** @var string Template for a revision of an existing version. */
    const REVISE = <<<'EOT'
Du bist Senior-Frontend-Entwickler (React) und Hochschuldidaktikerin. Überarbeite die bestehende Lernsimulation gemäss dem Änderungswunsch der Lehrperson.

## Ursprünglicher Auftrag
- Titel: {{TITLE}}
- Fach / Thema: {{SUBJECT}}
- Zielgruppe: {{TARGETGROUP}} (Niveau: {{DIFFICULTY}})
- Bearbeitungszeit: {{DURATION}}
- Sprache: {{LANGUAGE}}
- Simulationstyp: {{SIMTYPE}}
- Feedback: {{FEEDBACKMODE}}
- Lernergebnisse:
{{LEARNINGOUTCOMES}}

### Fachliche Inhalte
<<<INHALTE
{{CONTENT}}
INHALTE>>>

## Änderungswunsch bzw. Fehlermeldung
{{CHANGE_REQUEST}}

## Aktueller Code
{{CURRENT_CODE}}

## Vorgehen
- Setze den Änderungswunsch vollständig um und behalte alles bei, was bereits gut funktioniert.
- Wenn eine Fehlermeldung angegeben ist: Finde die Ursache und behebe sie; prüfe den restlichen Code auf ähnliche Fehler.
- Gib immer den VOLLSTÄNDIGEN überarbeiteten Code zurück – keine Ausschnitte, keine Diffs, keine Auslassungen wie «…».

{{TECHNICAL_RULES}}
EOT;

    /** @var string Runtime contract for React simulations (not editable). */
    const TECHNICAL_REACT = <<<'EOT'
## Technische Rahmenbedingungen (zwingend einhalten)
- Laufzeitumgebung: React 18 (globale Objekte `React` und `ReactDOM`) und Babel für JSX sind bereits geladen. Es gibt KEINEN Build-Schritt: keine import-/export-Anweisungen, kein npm, kein TypeScript.
- Global verfügbar: useState, useEffect, useRef, useMemo, useCallback, useReducer, useContext, createContext, Fragment.
- Definiere eine Hauptkomponente `function App() { … }`. Rendere sie NICHT selbst (kein ReactDOM.createRoot) – das übernimmt die Umgebung.
- Keine weiteren Bibliotheken (kein Tailwind, kein Chart.js, kein D3, keine Icon-Fonts). Diagramme selbst mit SVG oder <canvas> zeichnen.
- Kein fetch/XMLHttpRequest/WebSocket, kein localStorage/sessionStorage, keine Cookies – die Sandbox blockiert all das.
- Kein alert()/prompt(): Rückmeldungen direkt in der Oberfläche anzeigen.
- Styling ausschliesslich im separaten CSS-Block; alle Klassennamen mit dem Präfix `sim-`. Kein CSS-Framework.
- Die Simulation läuft in einem iframe in Moodle, dessen Höhe automatisch mit dem Inhalt wächst: keine Layouts mit 100vh/position: fixed über die ganze Höhe.
{{SAVESTATE_RULES}}
- Abschluss: Sobald die Lernenden die Simulation inhaltlich abgeschlossen haben, rufe einmalig `SimBridge.complete({ score, maxScore, summary })` auf. score und maxScore sind optionale Zahlen; summary ist ein kurzer Text mit den wichtigsten Ergebnissen bzw. Entscheidungen der lernenden Person (für die Lehrperson).
- Robustheit: Der Code muss ohne Fehler laufen. Prüfe alle Variablennamen, schliesse alle JSX-Tags, verwende eindeutige `key`-Props in Listen, keine Platzhalter wie «// TODO» oder «…».

## Ausgabeformat (strikt einhalten)
Antworte ausschliesslich mit genau zwei Markdown-Codeblöcken in dieser Reihenfolge und ohne weiteren Text davor oder danach:
1. Ein Block mit der Sprache `css`, der das gesamte CSS enthält.
2. Ein Block mit der Sprache `jsx`, der den gesamten React-Code inklusive `function App()` enthält.
EOT;

    /** @var string Runtime contract for complete HTML documents (only used when revising HTML versions). */
    const TECHNICAL_HTML = <<<'EOT'
## Technische Rahmenbedingungen (zwingend einhalten)
- Die Simulation ist ein vollständiges, eigenständiges HTML-Dokument mit Inline-CSS und Inline-JavaScript.
- Keine externen Ressourcen (keine CDNs, Webfonts, Bild-URLs oder APIs), kein fetch/XMLHttpRequest, kein localStorage.
- Das globale Objekt `SimBridge` ist verfügbar: `SimBridge.ready` (Promise, liefert den gespeicherten Zustand als Objekt oder null), `SimBridge.set(key, value)` zum Speichern, `SimBridge.get(key, fallback)` zum Lesen und `SimBridge.complete({ score, maxScore, summary })` beim inhaltlichen Abschluss.
- Die Simulation läuft in einem iframe, dessen Höhe automatisch mit dem Inhalt wächst: keine Layouts mit 100vh.

## Ausgabeformat (strikt einhalten)
Antworte ausschliesslich mit genau einem Markdown-Codeblock mit der Sprache `html`, der das vollständige Dokument enthält, ohne weiteren Text davor oder danach.
EOT;

    /** @var string Instructions when progress is saved. */
    const SAVESTATE_ON = <<<'EOT'
- Fortschritt speichern: Verwende für jeden Zustand, der bei einem späteren Besuch wiederhergestellt werden soll (aktueller Schritt, Eingaben, Entscheidungen, Ergebnisse), den globalen Hook `useSimState(key, initialValue)` statt useState. Er funktioniert exakt wie useState, speichert aber automatisch in Moodle. Beispiel: `const [step, setStep] = useSimState('step', 0);` Jeder key darf nur einmal verwendet werden. Speichere nur JSON-serialisierbare Daten (keine Funktionen, keine DOM-Elemente, keine Date-Objekte). Biete einen Button «Neu beginnen», der alle diese Zustände auf ihre Anfangswerte zurücksetzt.
EOT;

    /** @var string Instructions when progress is not saved. */
    const SAVESTATE_OFF = <<<'EOT'
- Der Fortschritt muss nicht gespeichert werden; verwende normales useState.
EOT;
}
