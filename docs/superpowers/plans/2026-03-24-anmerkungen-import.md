# Anmerkungen-Import & Multi-Wochen-Hinweise Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Word-Anmerkungsspalte korrekt in Excel importieren und als Multi-Wochen-Hinweise im Plugin anzeigen.

**Architecture:** Zwei unabhängige Änderungen: (1) Der Konverter bekommt Multi-Wochen-Support via ISO-Datum in Spalte G. (2) Der Import-Prompt wird um einen Anmerkungen-Abschnitt erweitert, der diese korrekt als "Ganze Woche"-Zeilen mit optionalem Enddatum in Spalte G einträgt.

**Tech Stack:** JavaScript (SheetJS, Konverter HTML), Markdown (Import-Prompt)

---

## Dateien

| Datei | Änderung |
|-------|----------|
| `konverter/GSH_Terminplan_Konverter.html` | Zeilen 1252 + 1322: endDate für "Ganze Woche" mit ISO-Datum in Spalte G |
| `prompts/excel-import-prompt.md` | Neuer Abschnitt: Anmerkungen-Spalte aus Word-Datei |

---

### Task 1: Konverter – Multi-Wochen-Support

**Files:**
- Modify: `konverter/GSH_Terminplan_Konverter.html:1252` (neues Format)
- Modify: `konverter/GSH_Terminplan_Konverter.html:1322` (altes Format)

**Hintergrund:**
Aktuell berechnet der Konverter für `Wochentag = "Ganze Woche"` immer `endDate = monday + 4` (Freitag dieser Woche). Das Plugin zeigt dann nur eine Woche in der Hinweise-Spalte. Für Einträge wie `09.02.–20.02. Abi Vorklausuren` soll das Event beide Wochen (SW 25 + SW 26) umfassen.

**Lösung:** Wenn Spalte G ein ISO-Datum (`YYYY-MM-DD`) enthält UND `isGanzWoche = true`, wird dieses als `endDate` verwendet. Sonst: unverändertes Verhalten (`monday + 4`).

- [ ] **Schritt 1: Zeile 1252 ändern (neues SW-Format)**

Datei: `konverter/GSH_Terminplan_Konverter.html`

Alt (Zeile 1252):
```javascript
      const endDate   = isGanzWoche ? addDaysISO(monday, 4) : (endzeit ? eventDate : '');
```

Neu:
```javascript
      const isoEndInG = isGanzWoche && /^\d{4}-\d{2}-\d{2}$/.test(endzeit) ? endzeit : null;
      const endDate   = isGanzWoche ? (isoEndInG || addDaysISO(monday, 4)) : (endzeit ? eventDate : '');
```

- [ ] **Schritt 2: Zeile 1322 ändern (altes SW-Format)**

Alt (Zeile 1322):
```javascript
        const endDate   = isGanzWoche ? addDaysISO(currentMonday, 4) : (endzeit ? eventDate : '');
```

Neu:
```javascript
        const isoEndInG = isGanzWoche && /^\d{4}-\d{2}-\d{2}$/.test(endzeit) ? endzeit : null;
        const endDate   = isGanzWoche ? (isoEndInG || addDaysISO(currentMonday, 4)) : (endzeit ? eventDate : '');
```

- [ ] **Schritt 3: Manuell testen**

Testfall: Excel-Zeile mit `E="Ganze Woche"`, `G="2026-02-20"`, `H="Abi Vorklausuren"`, `J="Ja"`.
Erwartung in der ICS-Datei:
```
DTSTART;VALUE=DATE:20260209
DTEND;VALUE=DATE:20260221   ← 20.02 + 1 Tag (exclusives DTEND)
```
→ Plugin zeigt das Event in SW 25 (09.02.) UND SW 26 (16.02.) in der Hinweise-Spalte.

Testfall ohne ISO-Datum in G: `E="Ganze Woche"`, `G=""` → unverändertes Verhalten (`endDate = monday + 4`). ✓

- [ ] **Schritt 4: Commit**

```bash
git add konverter/GSH_Terminplan_Konverter.html
git commit -m "feat(konverter): Multi-Wochen-Hinweise via ISO-Enddatum in Spalte G"
```

---

### Task 2: Import-Prompt – Anmerkungen-Spalte

**Files:**
- Modify: `prompts/excel-import-prompt.md`

**Hintergrund:**
Die Word-Tabelle hat als letzte Spalte "Anmerkungen". Diese enthält:
- Einfache Hinweise ohne Datum: `Noten eintragen!`, `Start Förderkurse 5/6`
- Hinweise mit Datumsbereich: `09.02.–20.02. Abi Vorklausuren`, `02.02.–13.02. Anmeldung SI/SII`

Beim letzten Import wurden diese komplett ignoriert. Zudem wurden sie fälschlicherweise in Tagesspalten (Mo–Fr) eingefügt statt als eigenständige "Ganze Woche"-Zeilen.

**Lösung:** Neuer Schritt im Import-Prompt nach Schritt 1 (Extraktion), der explizit die Anmerkungen-Spalte behandelt.

- [ ] **Schritt 1: Abschnitt "Anmerkungen extrahieren" in `excel-import-prompt.md` ergänzen**

Neuen Block **nach `### Schritt 1`** und **vor `### Schritt 2`** einfügen:

```markdown
### Schritt 1b – Anmerkungen-Spalte extrahieren

Die Word-Tabelle hat eine letzte Spalte "Anmerkungen". Extrahiere auch diese Einträge
und nummeriere sie im Anschluss an die regulären Termine weiter:

```
#368 | SW 01 | Jg 5+6 ganze Woche 1.-5. Std Unterricht     | (kein Datum)
#369 | SW 01 | Mittagessen ab Donnerstag                    | (kein Datum)
#370 | SW 02 | Klassensprecherwahl                          | (kein Datum)
#371 | SW 25 | 09.02.–20.02. Abi Vorklausuren               | 09.02. – 20.02.
#372 | SW 25 | 02.02.–13.02. Anmeldung SI/SII               | 02.02. – 13.02.
```

**Format-Regeln:**
- Wenn der Text mit einem Datumsbereich beginnt (`DD.MM.–DD.MM.` oder `DD.–DD.MM.`):
  → Datum vom Titeltext abtrennen
  → Spalte G = ISO-Enddatum (`YYYY-MM-DD`)
  → Titel (Spalte H) = nur der Text ohne Datum
- Wenn kein Datum im Text: Spalte G bleibt leer
- Wenn der Eintrag nur für einen Tag gilt (kein Bereich, nur `DD.MM.`):
  → Als normalen Tageseintrag behandeln (nicht als "Ganze Woche")
- Alle Anmerkungen zur **Gesamtzählung addieren** (N = Termine + Anmerkungen)
```

- [ ] **Schritt 2: Abschnitt "Schritt 3 – Termine einordnen" für Anmerkungen ergänzen**

Im bestehenden `### Schritt 3`-Abschnitt folgenden Hinweis ergänzen:

```markdown
**Anmerkungen einordnen:**
- Schulwoche laut Word-Tabelle direkt ablesen (Anmerkung steht in der Zeile der SW)
- Wochentag = `Ganze Woche`, Ganztägig = `Ja`
- Spalte G = ISO-Enddatum (wenn Datumsbereich vorhanden), sonst leer
- Kategorie per Keyword-Matching bestimmen (wie normale Termine)
```

- [ ] **Schritt 3: Schritt 4-Kommentar für Anmerkungen ergänzen**

Im `### Schritt 4`-Code-Block folgenden Kommentar ergänzen (nach der insert_rows-Logik):

```python
# Anmerkungen: Wochentag = 'Ganze Woche', Ganztägig = 'Ja', Spalte G = ISO-Enddatum (wenn vorhanden)
ws.cell(target_row, 5).value  = 'Ganze Woche'   # E
ws.cell(target_row, 7).value  = anmerkung.iso_end or ''  # G (ISO-Enddatum oder leer)
ws.cell(target_row, 8).value  = anmerkung.titel  # H
ws.cell(target_row, 9).value  = anmerkung.kategorie  # I
ws.cell(target_row, 10).value = 'Ja'             # J
```

- [ ] **Schritt 4: Vollständigkeitsprüfung anpassen**

Im `### Schritt 5`-Abschnitt den Hinweis ergänzen:

```markdown
Die Gesamtzahl umfasst Termine UND Anmerkungen. Beides muss eingetragen sein.
Prüfe: Anzahl Zeilen mit `Wochentag = Ganze Woche` entspricht der Anzahl Anmerkungen.
```

- [ ] **Schritt 5: Abschlussbericht-Template anpassen**

Im Abschlussbericht-Block ergänzen:
```
Anmerkungen (Ganze Woche):  XX  (davon X mit Datumsbereich in Spalte G)
```

- [ ] **Schritt 6: Commit**

```bash
git add prompts/excel-import-prompt.md
git commit -m "feat(prompt): Anmerkungen-Spalte aus Word-Datei korrekt importieren"
```

---

## Nach der Implementierung: Excel 2026/27 neu erstellen

1. Neues Claude-Gespräch öffnen
2. `prompts/excel-import-prompt.md` als Prompt einfügen
3. Word-Datei `vorlagen/Jahresterminplan 2627.docx` anhängen
4. Excel-Vorlage `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` anhängen
5. Claude ausführen lassen → `GSH_Terminplan_2026_27.xlsx` herunterladen
6. Im Konverter hochladen und Schritt 3/4 prüfen → ICS exportieren

---

## Workflow-Tipps für Schulleitung (Zukunft)

Für künftige Schuljahre füllt die Schulleitung die Excel direkt:
- **Normale Termine**: Wochentag = Mo/Di/Mi/Do/Fr, Uhrzeit, Titel, Kategorie
- **Ganze-Woche-Hinweise** (aus der alten Anmerkungen-Spalte):
  - Wochentag = `Ganze Woche`, Ganztägig = `Ja`, Titel in Spalte H
  - Mehrteilige Zeiträume: Enddatum in Spalte G (`YYYY-MM-DD`)
  - Kein Datum nötig für einwöchige Hinweise (Spalte G leer lassen)
- **Schuljahreswechsel**: Konverter-Tool → "⚙ Schuljahreswechsel"-Button → neue Vorlage generieren
