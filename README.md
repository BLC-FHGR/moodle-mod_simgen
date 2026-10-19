# KI-Simulation (`mod_simgen`)

Moodle-Aktivität, die aus einem didaktischen Briefing von Lehrpersonen eine interaktive Lernsimulation (React) generiert. Die KI-Anfrage läuft über das Plugin [local_ai_manager](https://github.com/mebis-lp/moodle-local_ai_manager). Ob dahinter ein lokales LLM (z. B. Ollama) oder ChatGPT steht, wird dort konfiguriert.

## Ablauf

```
Lehrperson                     Moodle (mod_simgen)                         local_ai_manager → LLM
──────────                     ───────────────────                         ─────────────────────
Briefing-Maske ausfüllen  ──►  Text aus Uploads auslesen (PDF/DOCX/PPTX …)
                               Prompt-Gerüst füllen
                               Ad-hoc-Task (läuft als Lehrperson)  ──────► perform_request()
                               Codeblöcke parsen (CSS + JSX)        ◄────── Antwort
                               neue Version (Entwurf) speichern
Vorschau testen, «Mit KI überarbeiten», «Code bearbeiten», «Veröffentlichen»
                                    │
Studierende ◄──── sandboxed iframe (sim.php) ◄──── veröffentlichte Version
                  SimBridge ⇄ postMessage ⇄ player.js ⇄ Webservices (Fortschritt speichern)
```

## Funktionen

- **Briefing-Maske**: Fach/Thema, Zielgruppe, Vorwissen, Niveau, Bearbeitungszeit, Setting (Einzel/Gruppe/Präsenz/Flipped), Sprache (de/en/fr/it/rm), Lernergebnisse, Simulationstyp, Feedback-Art, weitere Wünsche, fachliche Inhalte (Editor) und Material-Upload.
- **Generierung im Hintergrund** (Ad-hoc-Task) mit Statusanzeige und Benachrichtigung, oder «Jetzt direkt generieren».
- **Versionen**: Jede Generierung, Überarbeitung oder manuelle Änderung ist eine eigene Version. Studierende sehen nur die **veröffentlichte** Version.
- **Mit KI überarbeiten**: Änderungswunsch formulieren. Tritt beim Testen ein Fehler auf, sehen Lehrpersonen den Button **«Fehler von der KI beheben lassen»** (die Fehlermeldung wird automatisch übernommen).
- **Code bearbeiten** direkt in Moodle, **Download** als eigenständige HTML-Datei.
- **Fortschritt speichern**: Studierende können unterbrechen und später weitermachen (`useSimState`).
- **Abschlussverfolgung**: «Ansehen» und «Simulation abgeschlossen» (`SimBridge.complete()`).
- **Auswertung**: Status, Punkte und Ergebniszusammenfassung pro Person, Zurücksetzen.
- Backup/Restore (Kurskopie ins nächste Semester), Kurs-Reset, Privacy-API, Events.
- **Maximale Bearbeitungszeit** (Standard 60 Minuten, einstellbar): verhindert Briefings, die das Modell überfordern.
- **Live-Gespräch mit KI-Figuren** (Simulationstyp, ab 0.2): siehe unten.
- **Formeln und mathematische Grafiken** (ab 0.3): KaTeX und JSXGraph sind im Plugin enthalten, siehe unten.

## Live-Gespräch mit KI-Figuren (ab Version 0.2)

Im Simulationstyp «Live-Gespräch mit KI-Figuren» schreiben Studierende frei, was sie sagen würden, und eine KI antwortet in der Rolle einer Figur (z. B. verärgerter Hotelgast an der Rezeption).

```
Generierung (einmalig, Lehrperson)          Laufzeit (pro Nachricht, Studierende)
──────────────────────────────────          ─────────────────────────────────────
KI erzeugt CSS + JSX + JSON mit Figuren  →  useConversation('gast').send(text)
(Name, Rolle, Persona-Prompt, Eröffnung,     → player.js → Webservice mod_simgen_converse
 max. Nachrichten, Signale)                  → Persona-Prompt + Verlauf aus der DB + Nachricht
Figuren werden serverseitig gespeichert      → local_ai_manager (Einsatzzweck «Gespräche»)
                                             ← Antwort + Signale (z. B. {"stimmung": 4, "geloest": true})
```

- **Persona-Prompts bleiben auf dem Server.** Die Simulation kennt nur ID, Name und Rolle; der Verlauf wird serverseitig gespeichert. Studierende können weder die Rolle umschreiben noch einen falschen Verlauf einschleusen.
- **Signale** (von der KI pro Antwort eingeschätzt, z. B. Stimmung 1–5, Anliegen gelöst) steuern die Simulation: Stimmungsanzeige, nächste Phase, Debriefing.
- **Gesprächsende:** Zusätzlich zu den Signalen der Figur fragt der Server bei jeder Antwort das eingebaute Signal `beendet` ab (Figur verabschiedet sich, lernende Person verabschiedet sich, Anliegen erledigt/abgelehnt). Dann wird `chat.done` true – genau wie beim Erreichen des Nachrichtenlimits. Lernende können ein Gespräch zudem mit `chat.end()` («Gespräch beenden») abschliessen. `maxTurns` ist damit eine Obergrenze, kein Soll.
- **Grenzen (Kostenbremse):** max. Nachrichten pro Gespräch (Standard 20), max. Neustarts pro Gespräch (Standard 3) und max. Nachrichtenlänge (Standard 1000 Zeichen). Damit sind die KI-Anfragen pro Person und Figur auf Nachrichten × (Neustarts + 1) begrenzt; parallele Anfragen (z. B. zwei Tabs) werden gesperrt.
- **Signale sind nicht fälschungssicher:** Lernende können versuchen, die KI zu einer bestimmten Einschätzung zu bewegen. Für Feedback und Steuerung gut geeignet, nicht für summative Bewertung.
- **Neue Version = neues Gespräch:** Gespräche hängen an der Version. Nach dem Veröffentlichen einer neuen Version beginnen Lernende das Gespräch neu.
- **Lehrpersonen** sehen die Figuren inkl. Prompts in den Versionsdetails, können sie unter «Code bearbeiten» anpassen und die Gesprächsprotokolle in der Auswertung lesen.
- **Studierende** sehen einen Hinweis, dass ihre Nachrichten an die KI gehen und für Lehrpersonen einsehbar sind.

**Voraussetzungen im KI-Manager:** Die Gesprächs-Anfragen stellen die **Studierenden**. Deshalb:
1. In den Plugin-Einstellungen den Einsatzzweck für Gespräche wählen (Standard: «Chat»). Empfehlung: ein lokales Modell (z. B. chat.fhgr), damit die Texte der Studierenden im Haus bleiben.
2. Im KI-Manager diesem Einsatzzweck für die Rolle der Studierenden (meist «base role») ein Modell zuweisen und ein sinnvolles Kontingent setzen.
3. Studierende brauchen `local/ai_manager:use`; falls im KI-Manager Nutzungsbedingungen aktiviert sind, müssen sie diese einmal bestätigen.

## Formeln und mathematische Grafiken (ab Version 0.3)

Für Mathematik, Statik, Physik usw. stellt die Runtime zwei Bausteine bereit, die die KI im Prompt erklärt bekommt (`prompt_templates::MATH_RULES`):

- **`<Formula tex="…" />`** setzt LaTeX mit [KaTeX](https://katex.org) (inline, mit `display` als eigener Block). Fehlerhaftes LaTeX lässt die Simulation nicht abstürzen: Die Formel erscheint rot und der Fehler wird gemeldet («Fehler von der KI beheben lassen»).
- **`<MathBoard>`** zeichnet interaktive Grafiken mit [JSXGraph](https://jsxgraph.org) (Funktionsgraphen, Geometrie, Vektoren, ziehbare Punkte). Auf- und Abbau, Neuzeichnen bei Zustandsänderungen und Grössenanpassung übernimmt der Baustein.

Umsetzung:

- Die Bibliotheken liegen in `runtime/lib/` (KaTeX 0.18.7, JSXGraph 1.13.3, siehe `thirdpartylibs.xml`) und werden von Moodle selbst ausgeliefert – kein CDN, keine externen Anfragen. Die KaTeX-Schriften sind als `data:`-URIs ins Stylesheet eingebettet, weil Schriftdateien aus der Sandbox (opaker Origin) sonst an CORS scheitern.
- Geladen werden sie nur, wenn der Code `Formula` bzw. `MathBoard` verwendet (`html_builder::MATH_LIBRARIES`); andere Simulationen bleiben gleich schlank.
- Sprachmodelle schreiben LaTeX in JavaScript-Strings oft mit einfachen Backslashes (`"\frac"` enthält dann ein Steuerzeichen). Beim Kompilieren repariert ein Babel-Plugin solche Strings im Attribut `tex={…}` und in Objekt-Schlüsseln `tex`/`latex`/`formula`; Werte aus anderen Variablen werden zur Laufzeit so gut wie möglich repariert.
- Bibliotheken aktualisieren: neue Version von npm holen, `katex.min.js` und `jsxgraphcore.js` ersetzen, die Schriften aus `katex/dist/fonts/*.woff2` wieder als `data:`-URIs in `katex.min.css` einbetten und `thirdpartylibs.xml` anpassen.

## Installation

1. Plugin nach `<moodle>/mod/simgen` legen (ab Moodle 5.1: `<moodle>/public/mod/simgen`), entweder per Git:
   ```bash
   git clone https://github.com/BLC-FHGR/moodle-mod_simgen.git mod/simgen
   ```
   oder als ZIP über *Website-Administration › Plugins › Plugin installieren* (siehe «Entwicklung» zum Erstellen des ZIPs).
2. Voraussetzung: `local_ai_manager` ist installiert (Abhängigkeit in `version.php`), Moodle ≥ 4.4.
3. *Website-Administration › Mitteilungen* aufrufen und die Installation abschliessen.
4. Cron muss laufen (idealerweise jede Minute), sonst bleiben Generierungen auf «eingeplant» stehen. Lehrpersonen können sie dann über «Jetzt direkt generieren» starten.

## Konfiguration KI-Manager (wichtig)

| Einstellung | Empfehlung |
|---|---|
| Einsatzzweck (`mod_simgen › purpose`) | `singleprompt` (Standard) |
| Modell für diesen Einsatzzweck | Ein starkes Code-Modell: z. B. GPT-4.1/GPT-4o, oder lokal Qwen2.5-Coder 32B, Llama 3.3 70B, Mistral Large. Kleine Modelle (< 14B) liefern oft fehlerhaften Code. |
| `local_ai_manager › requesttimeout` | **mind. 300 Sekunden** (Standard 60 s reicht für Code-Generierung nicht) |
| Ollama: Kontextlänge | Ollama nutzt standardmässig ein kleines Kontextfenster und **schneidet Prompt/Antwort stillschweigend ab**. Setzen: `OLLAMA_CONTEXT_LENGTH=32768` (Umgebungsvariable des Ollama-Servers) oder im Modelfile `PARAMETER num_ctx 32768`. |
| Ausgabelimit (max tokens) | mind. 8000, besser 16000 Tokens |
| Berechtigung | Lehrpersonen brauchen `local/ai_manager:use` und eine Rolle im Tenant, die den Einsatzzweck nutzen darf. |

Hinweis: `local_ai_manager` wandelt die Antwort von `singleprompt`/`chat` in HTML um. Der Parser (`classes/local/response_parser.php`) holt den Code aus den `<pre class="language-…">`-Blöcken zurück und kommt auch mit rohen Markdown-Codeblöcken zurecht.

## Plugin-Einstellungen

*Website-Administration › Plugins › Aktivitäten › KI-Simulation*

- **Prompt-Vorlagen** (das «Prompt-Gerüst») für Generierung und Überarbeitung, mit Platzhaltern wie `{{TARGETGROUP}}`, `{{LEARNINGOUTCOMES}}`, `{{CONTENT}}` usw. Der technische Laufzeit-Vertrag (`{{TECHNICAL_RULES}}`) ist nicht editierbar und wird angehängt, falls er fehlt. Standardvorlagen: `classes/local/prompt_templates.php`.
- **Maximale Materiallänge** (Standard 30 000 Zeichen): an das Kontextfenster des Modells anpassen.
- **pdftotext**-Pfad (optional, sonst Ghostscript `$CFG->pathtogs`) für PDF-Uploads.
- **Laufzeit-Bibliotheken**: React 18.3.1, ReactDOM 18.3.1, Babel standalone 7.26.10 – standardmässig von jsDelivr. KaTeX und JSXGraph sind im Plugin enthalten und brauchen keine Einstellung.

### Bibliotheken lokal hosten (Datenschutz)

Standardmässig laden die Browser der Studierenden React/Babel von `cdn.jsdelivr.net`. Wer das vermeiden will:

1. Die drei Dateien herunterladen und z. B. nach `mod/simgen/vendor/` legen:
   - `https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js` (+ `react.development.js` für bessere Fehlermeldungen in der Lehrpersonen-Vorschau)
   - `https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js` (+ `react-dom.development.js`)
   - `https://cdn.jsdelivr.net/npm/@babel/standalone@7.26.10/babel.min.js`
2. In den Plugin-Einstellungen die URLs auf `/mod/simgen/vendor/…` setzen (relative Pfade sind erlaubt).

## Sicherheit

KI-generierter Code wird **nie** im Moodle-Kontext ausgeführt:

- Auslieferung über `sim.php` mit `Content-Security-Policy: sandbox allow-scripts …` (ohne `allow-same-origin`) **und** `sandbox`-Attribut am iframe → das Dokument hat einen *opaken Origin*: kein Zugriff auf Moodle-Cookies, Session, DOM oder APIs – auch nicht, wenn `sim.php` direkt geöffnet wird.
- `connect-src 'none'`, `form-action 'none'`, Bilder nur `data:`/`blob:` → der Code kann keine Daten nach aussen senden. Stylesheets sind nur inline und von der eigenen Moodle-Adresse erlaubt (für KaTeX/JSXGraph), Schriften nur als `data:`.
- Gespeichert wird ausschliesslich über die Moodle-Seite (`amd/src/player.js`), immer nur für die angemeldete Person und die aktuelle Aktivität, mit Grössenlimit und JSON-Validierung.
- Studierende erhalten nur veröffentlichte Versionen; Entwürfe sieht nur, wer `mod/simgen:manage` hat (RISK_XSS).
- Das Briefing enthält keine Studierendendaten. Hinweis in der Maske: keine Personendaten eingeben.

## Laufzeit-API für Simulationen

In jedem Simulationsdokument verfügbar (`runtime/bridge.js`):

```js
// Wie useState, aber automatisch in Moodle gespeichert (pro Person):
const [step, setStep] = useSimState('step', 0);

// Live-Gespräch mit einer KI-Figur (nur Simulationstyp «Live-Gespräch»):
const chat = useConversation('gast');
// chat.messages, chat.send(text), chat.pending, chat.signals.stimmung, chat.turn, chat.maxTurns,
// chat.done (Limit erreicht ODER Gespräch natürlich beendet), chat.ended, chat.end(),
// chat.error, chat.ready, chat.reset()

SimBridge.complete({ score: 7, maxScore: 10, summary: 'Entscheidungen: …' }); // Abschluss melden
SimBridge.ready.then((state) => { /* gespeicherter Zustand oder null */ });  // v. a. für HTML-Dokumente
SimBridge.set('key', value); SimBridge.get('key', fallback);                  // ohne React

// Formeln (KaTeX) und interaktive Grafiken (JSXGraph):
<Formula tex="M_{max} = \frac{q \cdot l^2}{8}" />                         // inline, mit display als Block
<MathBoard boundingBox={[-5, 5, 5, -5]} height={360} deps={[a]}
  setup={(board) => ({ P: board.create('point', [1, 1]) })}               // einmal: ziehbare Punkte usw.
  draw={(board, { P }) => board.create('functiongraph', [(x) => a * x * x])} /> // bei jeder Änderung von deps
```

Global verfügbar: `React`, `ReactDOM`, `useState`, `useEffect`, `useRef`, `useMemo`, `useCallback`, `useReducer`, `useContext`, `createContext`, `Fragment`, `Formula`, `MathBoard`. Die Komponente muss `App` heissen und wird von der Runtime gerendert. `import`/`export`-Zeilen, die Modelle trotzdem oft schreiben, werden automatisch entfernt.

## Entwicklung

- `amd/build/*.min.js` sind handgeschriebene AMD-Versionen von `amd/src/*.js`. Nach Änderungen an `amd/src` mit Moodles `grunt amd` neu bauen.
- Installations-ZIP aus dem Repo erstellen (Ordner `simgen/` im ZIP, ohne Git-Dateien):
  ```bash
  git archive --prefix=simgen/ -o mod_simgen_0.3.0.zip HEAD
  ```
- Änderungen pro Version stehen in [CHANGES.md](CHANGES.md).
- Datenbank: `simgen` (Briefing, Status), `simgen_versions` (Code, Prompt, Antwort), `simgen_states` (Fortschritt).

## Mögliche Erweiterungen

- Bewertung ins Gradebook übertragen (Achtung: Punkte werden clientseitig gemeldet und sind manipulierbar – eher für formative Zwecke).
- Anzeige direkt auf der Kursseite (wie «Text- und Medienfeld»).
- Bilder im Material über den Einsatzzweck «Bild zu Text» (itt) beschreiben lassen.
- Subresource Integrity (SRI) für CDN-Bibliotheken, PHPUnit-/Behat-Tests.

## Lizenz

© 2026 FH Graubünden. Lizenziert unter der [GNU GPL v3 oder später](LICENSE), wie Moodle selbst. Mitgelieferte Bibliotheken (KaTeX, JSXGraph) stehen unter ihren eigenen Lizenzen, siehe `thirdpartylibs.xml`.
