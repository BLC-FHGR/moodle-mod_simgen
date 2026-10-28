# Änderungen

## 0.5.1 (2026-10-28)

- Bearbeitungszeit steuern: Der Prompt übersetzt die Bearbeitungszeit in ein verbindliches Zeitbudget (Einstieg, Anzahl Aufträge à Minuten, Abschluss) und verlangt Aktivierung statt Durchklicken («Weiter» erst nach eigener Eingabe, Rückmeldung erst nach der Vorhersage). Der Satz «lieber fokussiert als überladen» entfällt.
- Ablaufplan: Die KI liefert vor dem Code einen Ablaufplan (Phasen und Aufträge mit Minuten). Er wird pro Version gespeichert, in den Versionsdetails angezeigt, bei Überarbeitungen mitgegeben und vom Leitfaden genutzt. Deckt er weniger als 75 % der Bearbeitungszeit ab, greift die automatische Korrektur.
- Gemessene Bearbeitungszeit: Der Player misst die aktive Zeit der Lernenden (Seite sichtbar und Aktivität in den letzten 2 Minuten). Die Auswertung zeigt sie pro Person und vergleicht den Median bis zum Abschluss mit der geplanten Zeit. Ist die Simulation deutlich zu kurz (mind. 3 Abschlüsse), bietet sie «Umfang erweitern» mit vorausgefülltem Überarbeitungsauftrag an.
- Neue Felder `simgen_versions.timeplan`, `simgen_states.activetime` und `completedactivetime` (inkl. Backup/Restore und Privacy-API).

## 0.5.0 (2026-10-28)

- Qualitätsschranke: Jede neue Version wird automatisch geprüft, bevor Lehrpersonen damit arbeiten.
  - Code-Prüfung direkt nach der Generierung (`code_checker`): fehlende `App`, abgeschnittener Code, `localStorage`/Netzwerkzugriffe und externe Ressourcen (in der Sandbox blockiert), doppelte `useSimState`-Schlüssel, unbekannte Gesprächsfiguren, fehlendes `SimBridge.complete` (Fehler bei aktivierter Abschlussbedingung, sonst Hinweis).
  - Test beim ersten Öffnen in der Vorschau: Tritt beim Start ein Fehler auf, wird er gespeichert und angezeigt (Badge «Test bestanden / Fehler / ausstehend»).
  - Automatische Korrektur: Findet die Prüfung oder der Test Fehler in einer KI-Version, überarbeitet die KI sie selbständig (neue Herkunft «Automatische Korrektur»), höchstens so viele Runden hintereinander wie in der neuen Einstellung `autorepair` (Standard 2). Manuell bearbeiteter Code wird nie automatisch verändert.
- Vorkompilierter Code: Besteht eine React-Version den Test, wird der im Browser der Lehrperson kompilierte Code gespeichert. Studierende laden dann kein Babel mehr (~3 MB) und der Code wird nicht bei jedem Aufruf neu übersetzt. Ändert sich der Code oder die Babel-Version, wird wieder aus dem Quelltext kompiliert.
- Neuer Webservice `mod_simgen_report_check`; neue Felder in `simgen` und `simgen_versions` (inkl. Backup/Restore).

## 0.4.1 (2026-10-21)

- Fehler in `db/install.xml` behoben (fehlendes Leerzeichen vor `COMMENT`): Die Neuinstallation brach mit «XML database file errors found» (`ddlxmlfileerror`) ab.

## 0.4.0 (2026-10-19)

- Leitfaden für Lehrpersonen pro Version (Brief → Play → Debrief): Vorbereitung, Sprechskript für die Einführung, Durchführung, Debrief in drei Schritten, fachliche Auflösung, Reflexions- und Evaluationsfragen.
- Eigene KI-Anfrage auf Grundlage von Briefing und Code der Version; wird beim Veröffentlichen automatisch erstellt (Einstellung `autoguide`) und kann jederzeit erstellt, neu erstellt und bearbeitet werden.
- Neue Seite `guide.php` mit Download als HTML (druckbar) und Markdown; Button «Leitfaden» im Verwaltungsbereich.
- Neue Berechtigung `mod/simgen:viewguide`: Nicht bearbeitende Lehrpersonen sehen den Leitfaden der veröffentlichten Version. Studierende sehen ihn nie.
- Prompt-Vorlage für den Leitfaden in den Plugin-Einstellungen anpassbar.

## 0.3.0 (2026-10-14)

- Formeln mit `<Formula tex="…" />` (KaTeX): fehlerhaftes LaTeX wird rot angezeigt und als Fehler gemeldet, statt die Simulation abzubrechen.
- Interaktive mathematische Grafiken mit `<MathBoard>` (JSXGraph).
- KaTeX 0.18.7 und JSXGraph 1.13.3 im Plugin enthalten (kein CDN) und nur geladen, wenn der Code sie verwendet.
- LaTeX-Strings mit einfachen Backslashes werden beim Kompilieren repariert.

## 0.2.2 (2026-10-13)

- Gespräche können enden: eingebautes Signal `beendet` (Verabschiedung, Anliegen erledigt/abgelehnt) und `chat.end()` für Lernende.
- `maxTurns` ist eine Obergrenze, kein Soll.

## 0.2.1 (2026-10-12)

- Warnung für Lehrpersonen, wenn ein benötigter KI-Einsatzzweck nicht verfügbar ist.
- Figuren werden auch über den Namen gefunden; bei nur einer Figur wird diese verwendet.
- Fehler in Gesprächen erscheinen immer als Moodle-Benachrichtigung; fehlgeschlagene Nachrichten bleiben sichtbar.
- Fix: reservierter Methodenname `declare()` in `hook_callbacks`.

## 0.2.0 (2026-10-12)

- Neuer Simulationstyp «Live-Gespräch mit KI-Figuren»: Persona-Prompts bleiben auf dem Server, Verlauf in `simgen_conversations`.
- Kostenbremse: max. Nachrichten, Neustarts und Nachrichtenlänge pro Gespräch.
- Gesprächsprotokolle in der Auswertung; Backup/Restore und Privacy-API erweitert.
- Maximale Bearbeitungszeit im Briefing einstellbar.

## 0.1.0 (2026-10-12)

- Erste Version: Briefing-Maske, Generierung über `local_ai_manager`, Versionen, «Mit KI überarbeiten», «Code bearbeiten», Fortschritt speichern, Abschlussverfolgung, Auswertung, Backup/Restore, Privacy-API.
