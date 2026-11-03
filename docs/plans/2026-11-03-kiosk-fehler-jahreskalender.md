# Plan: Kiosk-Fehler beheben und Jahreskalender einbauen

Status: umgesetzt in Version 4.45.0 (nicht committet). Abweichung: Der Jahreskalender ist ein Wandplaner (12 Monate als Spalten, Tage 1–31 als Zeilen) nach der Vorlage „Jahresplaner 2026“ des Auftraggebers, nicht 12 Monatsblöcke. Der Druck bleibt eine Seite A4 quer. Zweig: `fix/kiosk-raster-jahreskalender`.
Version: 4.45.0 (neue Funktion → MINOR; die Fehlerbehebungen laufen im selben Release).

## Befund (Ursachen geprüft im Code, Stand 4.44.0)

| # | Symptom | Ursache | Ort |
|---|---------|---------|-----|
| 1 | Raster: Termintext ragt aus der Kachel | Die ältere Regel `.ge-title { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }` gilt am Titel-`span` selbst. Das Redesign setzt `white-space: normal` nur am `.ge`, nicht am `.ge-title`. Mit `display:inline` und `nowrap` bricht der Titel nie um und läuft über die Kachel. Die mobile Liste setzt den Reset schon (`gsh-terminplan.css:1402`), das Raster nicht. | `gsh-terminplan.css:675`, `:1237` |
| 2 | Quartal-Punkt verzerrt, „aktuell“ liegt auf blauem Oval | Die Inline-Regel `.gtp-tab-now { width:6px; height:6px; border-radius:50%; background:accent; margin-left:5px; opacity:.85 }` (Altbestand) gilt weiter. Das Redesign macht aus dem Punkt ein Wort („· aktuell“), setzt aber Breite, Höhe, Hintergrund und Radius nicht zurück. | `gsh-terminplan.php:9092`, `gsh-terminplan.css:1005` |
| 3 | Jahresansicht ist nur ein Raster | `gsh_tp_yearview()` baut auf dem Desktop die Matrix Monate × Tage 1–31 (`.gtp-ym`). Das ist die Heatmap-Optik, kein Kalender. Mobil gibt es dagegen schon einen Monatskalender (`.gtp-yc`). | `gsh-terminplan.php:8875-8906` |
| 4 | Jahreskalender nicht druckbar | `gtpPrint()` druckt nur Quartalstabellen (`#gtp-q1..4 table`). Für die Jahresansicht gibt es keinen Druckpfad. | `gsh-terminplan.php:10576` |
| 5 | Tage in der Quartalsübersicht (rechts, Liste) nicht wählbar | `gsh_tp_minimap()` rendert die Tageszellen als `<span class="gtp-mini-c">`. Nur die SW-Beschriftung ist ein Knopf (`gtpMiniGo`). | `gsh-terminplan.php:8465-8499` |

Hinweis zu Punkt 3: Das Changelog von 4.44.0 sagt, die Heatmap sei ersetzt. Das stimmt nur für die Punkte-Darstellung. Die Matrix bleibt schwer lesbar.

## Maßnahmen

### 1. Raster-Text (Fehler)
`gsh-terminplan.css`, Block „Raster (Tabelle)“ im Desktop-Query:
```css
#gtp .gt .ge-title { white-space: normal; overflow: visible; text-overflow: clip; overflow-wrap: anywhere; }
#gtp .gt .ge-time  { white-space: nowrap; }
```
Prüfen bei 1024, 1280 und 1920 px mit langen Titeln („Planungsgespräch QA (nur mit JO, SW, SI)“).

### 2. Quartal-Punkt (Fehler) — Entscheidung: Kennzeichen „aktuell“ als kleine Pille
Die Altregel in `gsh_tp_css()` gehört zu den Bereichen „Structural shape“ und bleibt unangetastet. Das Redesign-CSS ersetzt sie vollständig:
```css
#gtp .gtp-tab-now { display: inline-block; width: auto; height: auto; margin: 0 0 0 var(--cl-space-2); padding: 0 var(--cl-space-2); border-radius: 999px; background: var(--cl-marine-100); color: var(--cl-marine-800); font: var(--cl-text-small); font-weight: 500; opacity: 1; }
#gtp .gtp-tab-now::before { content: none; }
```
Begründung (UX): Ein Wort in einer Pille ist ohne Erklärung lesbar, besteht den Kontrast (Marine 800 auf Marine 100) und trägt die Information nicht nur über Form oder Farbe. Gleiche Pille wie „Aktuelle Woche“ in der Liste, also ein Muster im ganzen Produkt. Sie steht in der Titelzeile („Quartal 2 [aktuell]“), nicht in der Datumszeile, und bleibt am aktiven Reiter sichtbar. Dafür wandert das `<span>` im Markup von `.gtp-tab-sub` nach `.gtp-tab-t` (`gsh-terminplan.php:7291-7295`). Die Regel `.gtp-tab:not(.gtp-tab-on) .gtp-tab-now` entfällt.

### 3. Quartalsübersicht mit wählbaren Tagen (neue Funktion) — Entscheidung: Mo bis So
- `gsh_tp_minimap()`: sieben Tagesspalten (Mo–So) statt fünf. Samstag und Sonntag sind grau (`--gtp-wknd`), wie im Jahreskalender. Die Seitenleiste hat 296 px; 28 px SW-Spalte plus sieben Zellen à etwa 32 px passen. Die Zellen sind nicht wie bisher 20 px, sondern mindestens 32 × 32 px (Tipp-Ziel; auf Touch-Geräten 44 px über unsichtbare Fläche).
- Tageszelle wird `<button class="gtp-mini-c" data-d="YYYY-MM-DD" onclick="gtpMiniDay(this)">` mit `aria-label` („Di, 01.12., 2 Termine“). Die Termin-Punkte und die Ferien-Schraffur bleiben. Die Tageszahl steht klein in der Zelle, damit die Übersicht wie ein Kalender liest.
- Neue Funktion `gtpMiniDay(btn)` neben `gtpMiniGo`: Quartal wechseln, aus der Jahresansicht zurück zur Liste, vergangene Woche aufklappen, zum Tag scrollen, Tag kurz hervorheben.
  - Liste, Mo–Fr: Ziel `.gtp-mob-day[data-d]`. Ohne Termin gibt es dort keine Zeile; dann Sprung zur Wochenzeile.
  - Raster, Mo–Fr: Ziel `td.gd`. Dafür `data-d` an die Tageszellen in `gsh_tp_table()` hängen (kleine Ergänzung, keine Änderung am Parser).
  - Sa/So: Es gibt in Liste und Raster keine Tagesspalte. Ziel ist die Woche; der Sprung hebt dort den Anmerkungen-Block hervor (`.gn-annotation` bzw. `.gtp-mob-annotation`). Die Wochenend-Texte bleiben reiner Text aus der Datenschicht, damit sich `curriculr-data-layer.php` und dessen Tests nicht ändern.
- Gewählter Tag bekommt `.gtp-mini-sel` (Marine-Rahmen, ohne Animation). Heute behält `.gtp-mini-now`.

**Geprüft: Wochenend-Termine (Tag der offenen Tür).**
- Sie erscheinen in der Anmerkungen-Spalte als Text „Sa 26.09.: Titel, 10.00–14.00 Uhr“ (`gsh_tp_curriculr_weekend_notes()`, `curriculr-data-layer.php:504`, Test `test-upcoming.php:35`). Das stimmt also.
- Die Quelle ist das Planner-Dokument (`gsh_tp_profile_curriculr_doc`), nicht der ICS-Feed. Hat ein Profil kein Planner-Dokument, erscheinen Wochenend-Termine dort nicht. Der Jahreskalender und die Übersicht lesen den ICS-Index und zeigen sie in jedem Fall.
- Es zählt nur der Beginn: Ein Termin, der am Samstag beginnt, steht dort. Ein Termin, der freitags beginnt und am Sonntag endet, steht am Freitag. Das ist richtig.
- Die Anmerkung ist reiner Text: keine Kategoriefarbe, kein Klick auf das Detailfenster. Das bleibt in diesem Plan unverändert (keine Nebenbei-Umbauten); als Folgeaufgabe vermerkt.

### 4. Jahreskalender (Ersatz der Matrix auf dem Desktop)
Zwölf Monatsblöcke (Aug bis Jul) im Raster 4 × 3 (Tablet 3 × 4, schmal 2 × 6), jeder Monat als Kalender Mo–So:
- Kopf je Monat: Monatsname und Jahr.
- Spalte links: Schulwoche (SW 00, 01 …), das Erkennungszeichen der Familie.
- Tageszelle: Zahl; Termintage mit Punkten in Kategoriefarben (maximal 3, wie bisher); Ferien und Feiertage schraffiert (`--gtp-hatch`); Wochenende grau (`--gtp-wknd`); heute mit Marine-Rahmen. Termintage tragen `aria-label` mit Anzahl, die Legende bleibt.
- Klick oder Tastatur auf einen Tag: vorhandener Popover (`gtpYearSelect`, `gtpYearFill`) mit Terminliste und „In der Liste öffnen“.
- Kategorie-Filter wirkt weiter (`gtpYearApplyFilter`).
- Mobil bleibt der vorhandene Monatskalender `.gtp-yc`.
- Gelöscht (nicht nur versteckt): `.gtp-ym*` Markup, CSS (`gsh-terminplan.css:1244-1262`) und die Desktop-Zweige in `gtpToday()` und `gtpYearSelect()`.
- Technik: serverseitig in `gsh_tp_yearview()`. Die Tagesdaten (`data-days`) bleiben ein JSON-Attribut mit `esc_attr( wp_json_encode() )`; der Client füllt nur per `textContent`.
- **Entscheidung Dateiaufteilung:** `gsh-terminplan.php` hat 11 775 Zeilen (Regel: über 500 teilen). Der neue Renderer kommt in eine eigene Datei `plugin/curriculr-year-calendar.php` mit einer reinen Funktion `gsh_tp_year_calendar_html( $index, $quartale, $cm, $col )`; `gsh_tp_yearview()` bleibt als dünner Aufrufer. Das hält die Hauptdatei kleiner und den Renderer einzeln testbar. Das Risiko liegt im manuellen ZIP-Bau: Fehlt die Datei im ZIP, bricht das Plugin beim Laden. Dagegen:
  1. Der Bau-Befehl in beiden CLAUDE.md nimmt künftig alle Plugin-Dateien (`zip ../../curriculr-terminplan-$VER.zip *.php` statt Einzelliste). Neue Dateien fehlen dann nie wieder.
  2. Neuer Test `tests/curriculr/test-zip-contents.php`: liest alle `require_once` aus `gsh-terminplan.php` und prüft, dass jede Datei existiert.
  3. Laden mit `file_exists`-Wache und Admin-Hinweis statt Absturz ist bewusst nicht vorgesehen: Ein fehlendes Teil soll beim Test auffallen, nicht still zur halben Anzeige führen.
- Wochenenden: Im Jahreskalender sind Sa und So eigene Spalten. Ein Wochenend-Termin zeigt Punkte und ist anklickbar, der Popover nennt ihn mit Datum.

### 5. Jahreskalender drucken
- Menü **Exportieren** bekommt **Jahreskalender drucken**.
- `gtpPrint("year", titel)` nutzt den bestehenden iframe-Weg und die Touch-Variante. Neuer Zweig baut aus dem Jahreskalender ein A4-Querformat-Dokument: 12 Monate 4 × 3 auf einer Seite, Legende, Kopf mit Schuljahr, Fuß „Stand: TT.MM.JJJJ · Curricu:lr Terminplan 4.45.0“ (Familienregel Druckfuß).
- Druck-CSS: `break-inside: avoid` und `page-break-inside: avoid`, `print-color-adjust: exact`. Ferien-Schraffur und Termintage bleiben in Graustufen erkennbar (Termintag zusätzlich fett).
- Fehler am Rand: Der PDF-Kopf und die Dateititel sind fest auf „2025 / 26“ gesetzt (`gsh-terminplan.php:10700`, `:11237`, `:11252`). Das Bild zeigt 2026/27. Für den neuen Druck wird das Schuljahr aus den Daten gelesen. Die Quartals-PDFs ziehen mit, weil es derselbe Fehler ist.

## Zusammenspiel Planner und Terminplan (geprüft vor dem Bau)
Kette: Planner `PUT /curriculr/v1/doc/{sj}` → `wp_curriculr_docs` → `gsh_tp_curriculr_after_put()` → ICS-Cache je Kalender (`gsh_tp_curriculr_build_ics`) → `gsh_tp_parse_events` → `gsh_tp_build_date_index` → Liste, Raster, Jahr, Übersicht. Anmerkungen und Wochenend-Texte kommen direkt aus dem Dokument (`gsh_tp_curriculr_display_notes`).
- Ausgangslage vor dem Bau: alle PHP-Dateien `php -l` ohne Fehler, alle 29 Tests in `tests/curriculr/` grün.
- Kettenprüfung mit Demo-Dokument (Elternabend, Samstag „Tag der offenen Tür“, Sonntag, Ferien über Jahreswechsel): ICS enthält alle vier Termine, Index und Wochenend-Texte stimmen („Sa 21.11.: Tag der offenen Tür, 10.00–14.00 Uhr“), Raster zeigt zwei Anmerkungen, Jahresansicht führt den Samstag, Ferien sind markiert.
- Dieser Plan ändert nur die Darstellung. REST-Routen, Datenbank, ICS-Feed, Token und Dokumentformat bleiben unverändert. Eine SPA-Version ist daher nicht anzuheben.
- Nach dem Bau erneut: alle Tests, dazu ein neuer Test, der ein Planner-Dokument durch die gesamte Kette schickt (Ansichten zeigen Samstag und Ferien).
- Für den Betrieb nach dem Upload: Im Planner einmal **Senden** (Push) und prüfen, dass Kiosk und Jahreskalender denselben Stand zeigen.

## Reihenfolge (je Schritt eigener Commit, `php -l` und Tests danach)
1. Test zuerst, `tests/curriculr/test-desktop-views.php` erweitern:
   - Minimap: fünf Buttons je Woche mit `data-d`.
   - Jahreskalender: 12 Monatsblöcke, SW-Spalte, Tage mit `data-d`, Ferientag markiert. Der Test auf `gtp-ym-mo` (Zeile 138) wird ersetzt.
   - CSS-Datei enthält die Reset-Regeln aus Punkt 1 und 2 (String-Prüfung).
2. Fehler 1 und 2 (nur CSS).
3. Minimap-Tage (Markup, JS, CSS, `data-d` im Raster).
4. Jahreskalender (Markup, CSS, JS, Matrix entfernen).
5. Druck (`gtpPrint`-Zweig, Menüpunkt, Schuljahr-Titel).
6. Doku: `docs/anleitung/terminplan-ansichten.md`, Kurzanleitung prüfen, `CHANGELOG.md` unter `[Unreleased]`, `gsh_tp_changelog()` und die vier Versionsstellen auf 4.45.0.
7. Am Bildschirm prüfen: Kiosk-Seite in Chrome, Firefox, Safari bei 1024, 1440 und 1920 px, hell und dunkel, per Tastatur (Tab, Enter, Esc), Druckvorschau.

## Fertig, wenn
- `php -l` für alle Plugin-Dateien und alle Tests in `tests/curriculr/` grün.
- Raster: kein Text außerhalb der Kachel bei 1024–1920 px; ein Titel mit 60 Zeichen bricht um.
- Reiter: kein Oval, „aktuell“ als Text, Kontrast mindestens 4,5 : 1.
- Quartalsübersicht: jeder Tag per Maus und Tastatur wählbar; Sprung landet im richtigen Quartal und Tag, auch bei vergangenen Wochen.
- Jahr: 12 Monate sichtbar, SW-Spalte, Ferien erkennbar, Popover und „In der Liste öffnen“ funktionieren, Druck ergibt eine A4-Seite quer ohne Abschneiden.
- Screenshots nur mit Demo-Daten („Muster-Gesamtschule“).

## Sicherheit und Datenschutz
- Datenklasse bleibt 1 (Termine, Gruppen). Keine neuen Daten, Verbindungen oder Speicher. Die Jahresansicht liest dieselben, schon ausgelieferten Termine.
- Neue Ausgaben nur mit `esc_html` und `esc_attr`; JS setzt Termintexte nur mit `textContent`. Das Druckdokument übernimmt Texte nur über die vorhandenen Escape-Helfer (`gtpEsc`).
- Keine externen Schriften oder CDNs. Farben nur über `var(--cl-*)`.
- Kiosk-Zugang (Token, Rate-Limit, `frame-ancestors`) bleibt unberührt. Der Kiosk-Token steht in der URL. Der Druckzweig für Touch (neuer Tab) darf die URL nicht in das neue Dokument schreiben. Das wird beim Bau geprüft.
- Es ändert sich keine Login-, Rechte- oder Server-Exportlogik. Der Druck ist ein reiner Client-Export. Im PR trotzdem erwähnen.

## Entscheidungen (freigegeben am 2026-11-03)
1. Quartal-Merkmal: Pille „aktuell“ in der Titelzeile (siehe Maßnahme 2).
2. Jahreskalender: eine Seite A4 quer. Das Layout ist vektorbasiert und skaliert verlustfrei auf A3; der Druck-CSS arbeitet mit `mm`/`pt` und `@page { size: A4 landscape }`, der Benutzer wählt A3 im Druckdialog („An Papier anpassen“). Mindestschrift 7 pt bei A4, damit es auch verkleinert lesbar bleibt.
3. Quartalsübersicht mit Samstag und Sonntag (siehe Maßnahme 3).
4. Eigene Datei `curriculr-year-calendar.php`, abgesichert durch ZIP-Glob und Test (siehe Maßnahme 4).

## Folgeaufgaben (nicht Teil dieses Plans)
- Wochenend-Termine als Karten mit Kategorie und Detailfenster statt als Anmerkungstext.
- Wochenend-Termine auch für Profile ohne Planner-Dokument in die Anmerkungen-Spalte aus dem ICS-Index übernehmen (mit Doppelt-Schutz gegen das Planner-Dokument).
