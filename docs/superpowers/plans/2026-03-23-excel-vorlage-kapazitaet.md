# Excel-Vorlage Kapazitätserweiterung – Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Excel-Vorlage auf SW 00–41 (42 Wochen) und 15 Datenzeilen pro Schulwoche erweitern sowie Teilwoche-Handling dokumentieren.

**Architecture:** Neues `patch_rows.py` rebuildet die Terminplan-Grundstruktur (einmalig bei Strukturänderungen). `patch_xlsx.py` erhält 2-Zeilen-Fix für SW 41. `build_excel_template.py` bekommt einen neuen Hinweis-Block. Ausführungsreihenfolge: patch_rows → patch_xlsx → build_excel_template.

**Tech Stack:** Python 3, openpyxl

---

## Dateien

| Aktion | Pfad |
|--------|------|
| Neu erstellen | `scripts/patch_rows.py` |
| Ändern (2 Zeilen) | `scripts/patch_xlsx.py` |
| Ändern (1 Zeile) | `scripts/build_excel_template.py` |
| Neu erstellen | `prompts/excel-import-prompt.md` |
| Spec | `docs/superpowers/specs/2026-03-23-excel-vorlage-kapazitaet-design.md` |

---

## Task 1: `patch_rows.py` erstellen

**Files:**
- Erstellen: `scripts/patch_rows.py`

Dieses Script öffnet die xlsx, löscht alle Terminplan-Zeilen ab Zeile 2 und schreibt eine neue Grundstruktur: SW 00–41 (42 Wochen) × (1 Header-Zeile + 15 Datenzeilen) = 673 Zeilen gesamt. `patch_xlsx.py` erkennt die SW-Header anschließend via `col_A.startswith('SW ')` – unverändert.

- [ ] **Step 1: `patch_rows.py` erstellen**

Datei `scripts/patch_rows.py` anlegen:

```python
# -*- coding: utf-8 -*-
"""
patch_rows.py
Baut die Terminplan-Grundstruktur neu auf:
- SW 00-41 (42 Schulwochen) x 16 Zeilen (1 Header + 15 Datenzeilen)
- Gesamt: 1 Kopfzeile + 672 Folgezeilen = 673 Zeilen

Muss vor patch_xlsx.py und build_excel_template.py ausgefuehrt werden.
Bei Strukturaenderungen (andere Zeilenanzahl, mehr SW) neu ausfuehren.
"""

from pathlib import Path
import openpyxl

BASE = Path(__file__).resolve().parent.parent
XLSX = BASE / 'website' / 'downloads' / 'GSH_Terminplan_Schulwochen_Vorlage.xlsx'

ROWS_PER_SW = 15   # Datenzeilen pro Schulwoche (ohne SW-Header-Zeile)
SW_COUNT    = 42   # SW 00-41


def rebuild_terminplan(ws):
    """Loescht Terminplan-Inhalt ab Zeile 2 und schreibt neue SW-Struktur."""
    # Alle Zeilen ab Zeile 2 loeschen (Zeile 1 = Spalten-Header bleibt)
    if ws.max_row > 1:
        ws.delete_rows(2, ws.max_row - 1)

    # SW-Header-Zeilen schreiben; Datenzeilen bleiben leer
    current_row = 2
    for sw_num in range(SW_COUNT):
        ws.cell(row=current_row, column=1).value = f'SW {sw_num:02d}'
        current_row += ROWS_PER_SW + 1   # 1 Header + 15 Daten -> naechster Header

    # Letzte Datenzeile mit leerem String schreiben damit max_row = 673 ist.
    # openpyxl zaehlt nur Zeilen mit Zellinhalten; patch_xlsx.py und
    # build_excel_template.py nutzen max_row fuer Dropdown-Ranges und Styling.
    last_data_row = 1 + SW_COUNT * (ROWS_PER_SW + 1)   # = 673
    ws.cell(row=last_data_row, column=4).value = ''


print(f'Lade: {XLSX}')
wb = openpyxl.load_workbook(XLSX)

ws_t = wb['Terminplan']
rebuild_terminplan(ws_t)

last_sw_header_row = 2 + (SW_COUNT - 1) * (ROWS_PER_SW + 1)   # = 658
print(f'  SW 00: Zeile 2')
print(f'  SW 41: Zeile {last_sw_header_row}')
print(f'  max_row: {ws_t.max_row}')

wb.save(XLSX)
print(f'Gespeichert: {XLSX}')
print('Weiter mit: python scripts/patch_xlsx.py')
```

- [ ] **Step 2: Script ausführen**

```bash
cd "~/projects/wordpress-plugin-terminplaner"
python scripts/patch_rows.py
```

Erwartete Ausgabe:
```
Lade: ...GSH_Terminplan_Schulwochen_Vorlage.xlsx
  SW 00: Zeile 2
  SW 41: Zeile 658
  max_row: 673
Gespeichert: ...
Weiter mit: python scripts/patch_xlsx.py
```

- [ ] **Step 3: Struktur verifizieren**

```bash
python -c "
import openpyxl
wb = openpyxl.load_workbook('website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx')
ws = wb['Terminplan']
print('max_row:', ws.max_row)
print('Zeile 2 Sp.A:', ws.cell(2,1).value)    # SW 00
print('Zeile 18 Sp.A:', ws.cell(18,1).value)   # SW 01
print('Zeile 658 Sp.A:', ws.cell(658,1).value) # SW 41
print('Zeile 3 Sp.A:', ws.cell(3,1).value)     # None (Datenzeile)
"
```

Erwartete Ausgabe:
```
max_row: 673
Zeile 2 Sp.A: SW 00
Zeile 18 Sp.A: SW 01
Zeile 658 Sp.A: SW 41
Zeile 3 Sp.A: None
```

- [ ] **Step 4: Commit**

```bash
git add scripts/patch_rows.py
git commit -m "feat(excel): patch_rows.py – Terminplan-Struktur SW 00-41, 15 Zeilen/SW"
```

---

## Task 2: `patch_xlsx.py` auf SW 41 erweitern

**Files:**
- Ändern: `scripts/patch_xlsx.py:133` und `scripts/patch_xlsx.py:150`

Zwei Zeilen ändern, damit die Ferien-Hilfstabelle F56 (SW 41) erhält und die Terminplan-Formeln auch für die neue SW-41-Header-Zeile gesetzt werden.

- [ ] **Step 1: Zeile 133 anpassen**

In `scripts/patch_xlsx.py` Zeile 133:

Vorher:
```python
for r in range(HELPER_HEADER_ROW, HELPER_START_ROW + 42):
```

Nachher:
```python
for r in range(HELPER_HEADER_ROW, HELPER_START_ROW + 43):
```

- [ ] **Step 2: Zeile 150 anpassen**

In `scripts/patch_xlsx.py` Zeile 150:

Vorher:
```python
for sw_num in range(1, 41):
```

Nachher:
```python
for sw_num in range(1, 42):
```

- [ ] **Step 3: Print-Statement dynamisch machen (Zeilen 192–193)**

In `scripts/patch_xlsx.py` Zeilen 192–193:

Vorher:
```python
print(f'  Terminplan: Formeln B+D für SW 00 (Z.{sw_header_rows[0][1]}) '
      f'bis SW 40 (Z.{sw_header_rows[-1][1]}) gesetzt')
```

Nachher:
```python
print(f'  Terminplan: Formeln B+D fuer SW {sw_header_rows[0][0]:02d} (Z.{sw_header_rows[0][1]}) '
      f'bis SW {sw_header_rows[-1][0]:02d} (Z.{sw_header_rows[-1][1]}) gesetzt')
```

- [ ] **Step 4: Script ausführen**

```bash
python scripts/patch_xlsx.py
```

Erwartete Ausgabe:
```
Lade: ...
  Ferien-Blatt: Datumswerte, A1-Formel, Hilfstabelle F15:F56 gesetzt
  Terminplan: 42 SW-Header-Zeilen gefunden
  Terminplan: Formeln B+D fuer SW 00 (Z.2) bis SW 41 (Z.658) gesetzt
Gespeichert: ...
```

- [ ] **Step 5: Ferien-Hilfstabelle verifizieren**

```bash
python -c "
import openpyxl
wb = openpyxl.load_workbook('website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx')
ws = wb['Ferien']
print('F15 Label (SW 00):', ws.cell(15,5).value)
print('F55 Label (SW 40):', ws.cell(55,5).value)
print('F56 Label (SW 41):', ws.cell(56,5).value)
print('F57 (leer):', ws.cell(57,5).value)
ws_t = wb['Terminplan']
print('Terminplan B658 (SW-41-Formel):', ws_t.cell(658,2).value)
"
```

Erwartete Ausgabe:
```
F15 Label (SW 00): SW 00
F55 Label (SW 40): SW 40
F56 Label (SW 41): SW 41
F57 (leer): None
Terminplan B658 (SW-41-Formel): =Ferien!$F$56
```

- [ ] **Step 6: Commit**

```bash
git add scripts/patch_xlsx.py
git commit -m "fix(excel): patch_xlsx.py – SW-Bereich auf SW 00-41 erweitern"
```

---

## Task 3: Teilwoche-Hinweis in Anleitung-Tab

**Files:**
- Ändern: `scripts/build_excel_template.py:212` (neue Zeile nach Zeile 212 einfügen)

Der „Wichtige Hinweise"-Abschnitt (Zeilen 207–213) enthält 5 `add_warn()`-Aufrufe. Einen sechsten anfügen, der Teilwochen erklärt.

- [ ] **Step 1: Neue Zeile in `build_excel_template.py` einfügen**

Nach Zeile 212 (nach dem letzten bestehenden `add_warn()`-Aufruf, vor `add_blank()`) einfügen:

```python
    add_warn('>> Teilwochen (z. B. nach Pfingstferien): Beginnt die Schule nicht am Mo,'
             ' trage schulfreie Tage (Mo/Di) als eigene Zeile ein:'
             ' Wochentag = Mo/Di, Kategorie = Feiertage/Ferien, Ganztaegig = Ja.'
             ' Danach die normalen Schultermine der Restwoche eintragen.')
```

Der Block sieht danach so aus (Zeilen 207–214):

```python
    add_section('Wichtige Hinweise')
    add_warn('>> Graue Header-Zeilen markieren den Wochenbeginn - dort NICHTS eintragen!')
    add_warn('>> Spalten A, B, C sind ausgeblendet (technische Daten) - nicht einblenden.')
    add_warn('>> Spalte D wird automatisch berechnet - nicht bearbeiten.')
    add_warn('>> Ferientermine zuerst im Tab "Ferien" eintragen - danach passen sich alle Daten an.')
    add_warn('>> Aenderungen im laufenden Schuljahr: direkt im IServ-Kalender bearbeiten.')
    add_warn('>> Teilwochen (z. B. nach Pfingstferien): Beginnt die Schule nicht am Mo,'
             ' trage schulfreie Tage (Mo/Di) als eigene Zeile ein:'
             ' Wochentag = Mo/Di, Kategorie = Feiertage/Ferien, Ganztaegig = Ja.'
             ' Danach die normalen Schultermine der Restwoche eintragen.')
    add_blank()
```

- [ ] **Step 2: Script ausführen**

```bash
python scripts/build_excel_template.py
```

Keine Fehler erwartet (gleiche Ausgabe wie bisher).

- [ ] **Step 3: Anleitung-Tab verifizieren**

Excel-Datei öffnen → Tab „Anleitung":
- ✅ Abschnitt „Wichtige Hinweise" enthält jetzt 6 Hinweis-Zeilen
- ✅ Letzter Hinweis erklärt Teilwochen (Pfingstferien-Beispiel, Mo/Di-Eintrag)
- ✅ Alle Texte vollständig lesbar (nicht abgeschnitten)

- [ ] **Step 4: Commit**

```bash
git add scripts/build_excel_template.py
git commit -m "feat(excel): Anleitung – Teilwoche-Hinweis in Wichtige-Hinweise ergänzen"
```

---

## Task 4: Claude Import-Prompt aktualisieren

**Files:**
- Erstellen: `prompts/excel-import-prompt.md`

Der Prompt aus der vorherigen Session wird als Datei gespeichert und um den Teilwoche-Abschnitt erweitert.

- [ ] **Step 1: `prompts/excel-import-prompt.md` erstellen**

Datei mit folgendem Inhalt anlegen (Schritt 3 enthält den neuen Teilwoche-Abschnitt):

````markdown
# Claude Import-Prompt: Word-Datei → Excel-Vorlage

Diesen Prompt in ein neues Claude-Gespräch einfügen und die Word-Datei anhängen
(oder den Dateinamen im letzten Abschnitt anpassen).

---

Ich habe eine Word-Datei mit der Terminplanung für das Schuljahr und möchte alle
Termine in die Excel-Vorlage `GSH_Terminplan_Schulwochen_Vorlage.xlsx` übertragen.
Bitte lies beide Dateien und fülle die Excel-Vorlage korrekt aus.

## Aufgabe

1. **Word-Datei lesen** – extrahiere alle Termine mit Datum, Titel und ggf. Uhrzeit
2. **Excel-Vorlage füllen** – trage jeden Termin in die richtige Schulwoche ein

## Struktur der Excel-Vorlage

**Sheet „Terminplan"** – Spalten (A–C sind ausgeblendet, nicht anfassen):

| Spalte | Inhalt | Werte |
|--------|--------|-------|
| A | SW-Key (ausgeblendet) | `SW 00` … `SW 41` |
| B | Datum Mo (ausgeblendet) | Montag der Schulwoche |
| C | (ausgeblendet) | – |
| D | Schulwoche | – |
| **E** | **Wochentag** | Dropdown: `Mo` / `Di` / `Mi` / `Do` / `Fr` / `Ganze Woche` |
| **F** | **Uhrzeit** | Format: `08:30` – leer lassen wenn ganztägig |
| **G** | **Endzeit** | Format: `14:30` – leer lassen wenn ganztägig |
| **H** | **Titel / Veranstaltung** | Freitext |
| **I** | **Kategorie** | Dropdown – exakt einer der 7 Werte (s. u.) |
| **J** | **Ganztägig** | `Ja` oder `Nein` |
| **K** | **Anmerkung** | Freitext (optional) |

**Erlaubte Kategorien (Spalte I) – exakt so schreiben:**
- `Jahrgang 5/6` — Einschulung, Sprachstandstest, Neue 5er
- `Jahrgang 7/8` — Potenzialanalyse, WP-Wahl, KAoA
- `Jahrgang 9/10` — Betriebspraktikum, ZP 10, Abschlussfahrt
- `Oberstufe` — Abitur, EF/Q1/Q2-Termine
- `Inklusion` — IFöE, AL SuS, Inklusionsteam
- `Feiertage/Ferien` — Ferien, Feiertage, schulfreie Tage
- `Konferenzen/DB` — Lehrerkonferenz, FaKo, Teamgespräche

## Vorgehen

**Schritt 1 – Word-Datei analysieren:**
Liste alle Termine im Format:
```
Datum | Titel | Uhrzeit (falls vorhanden) | geschätzte Kategorie
```

**Schritt 2 – Excel-Struktur verstehen:**
Öffne die Excel-Vorlage mit openpyxl und lies:
- Spalte A: identifiziere SW-Header-Zeilen (`a_val.startswith('SW ')`)
- Spalte B (versteckt): Montags-Datum der jeweiligen Schulwoche
- Notiere: Zeile → SW-Key → Montags-Datum → erste freie Datenzeile darunter

**Schritt 3 – Termine einordnen:**
Ordne jeden Termin der richtigen Schulwoche zu:
- Termin-Datum liegt in der Woche `[Montag, Montag+6]` → diese Schulwoche
- Wochentag: `datetime.weekday()` → 0=Mo, 1=Di, 2=Mi, 3=Do, 4=Fr
- Bei Ferien/Feiertagen die gesamte Woche mit `Ganze Woche` eintragen

**Teilwochen** (Schule beginnt nicht am Montag, z. B. nach Pfingstferien):
Jeden schulfreien Tag zu Beginn der Woche als eigene Zeile eintragen:
Wochentag = `Mo` / `Di`, Titel = `schulfrei`, Kategorie = `Feiertage/Ferien`,
Ganztägig = `Ja`. Danach normal weitermachen mit den Schulterminen der Restwoche.

**Schritt 4 – Eintragen:**
Für jeden Termin die nächste freie Datenzeile in der Zielschulwoche füllen:
```python
ws.cell(row=target_row, column=5).value  = wochentag   # E
ws.cell(row=target_row, column=6).value  = uhrzeit     # F (oder None)
ws.cell(row=target_row, column=7).value  = endzeit     # G (oder None)
ws.cell(row=target_row, column=8).value  = titel       # H
ws.cell(row=target_row, column=9).value  = kategorie   # I
ws.cell(row=target_row, column=10).value = ganztaegig  # J ("Ja"/"Nein")
```

**Wichtig:**
- SW-Header-Zeilen (Spalte A = `SW XX`) nie überschreiben
- Nur Spalten E–K schreiben, nie A–D
- Ferien müssen trotzdem im Terminplan-Sheet eingetragen werden (für den Export)
- Wenn mehr Termine als freie Zeilen: sag mir welche Termine nicht passen

**Schritt 5 – Speichern & Bericht:**
Speichere als `GSH_Terminplan_SCHULJAHR.xlsx` und liste:
- Anzahl eingetragener Termine
- Zugeordnete Kategorien (zur Kontrolle)
- Nicht zuordenbare Termine (falls vorhanden)

---

**Dateien:**
- Word-Datei: `[HIER DATEINAMEN EINFÜGEN]`
- Excel-Vorlage: `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx`
````

- [ ] **Step 2: Commit**

```bash
git add prompts/excel-import-prompt.md
git commit -m "docs(prompts): Excel-Import-Prompt als Datei – inkl. Teilwoche-Abschnitt"
```

---

## Task 5: Abschluss – xlsx regenerieren und pushen

**Files:**
- Output: `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx`

- [ ] **Step 1: Alle drei Scripts in Reihenfolge ausführen**

```bash
python scripts/patch_rows.py
python scripts/patch_xlsx.py
python scripts/build_excel_template.py
```

Alle drei ohne Fehler erwartet.

- [ ] **Step 2: Excel-Datei visuell prüfen**

`website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` öffnen:

- ✅ Tab „Terminplan": SW 41 vorhanden (ganz unten)
- ✅ Jede Schulwoche hat 15 leere Datenzeilen (statt 8)
- ✅ SW-Header-Zeilen sind lila/indigo hervorgehoben
- ✅ Dropdowns in Spalte E (Wochentag) und I (Kategorie) funktionieren
- ✅ Tab „Ferien": Hilfstabelle reicht bis F56 (SW 41)
- ✅ Tab „Anleitung": 6 Hinweis-Zeilen im Abschnitt „Wichtige Hinweise"

- [ ] **Step 3: xlsx committen und pushen**

```bash
git add website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx
git commit -m "feat(excel): Vorlage auf SW 00-41, 15 Zeilen/SW erweitert"
git push origin main
```

---

## Abschluss-Checkliste

- [ ] `patch_rows.py` läuft fehlerfrei, max_row = 673
- [ ] `patch_xlsx.py` setzt Formeln für SW 00–41 (Zeilen 2–658)
- [ ] `build_excel_template.py` läuft fehlerfrei
- [ ] Anleitung-Tab: 6. Hinweis (Teilwochen) vorhanden
- [ ] xlsx: SW 41 sichtbar, 15 Datenzeilen pro SW
- [ ] Import-Prompt-Datei vorhanden unter `prompts/excel-import-prompt.md`
- [ ] Alle Commits gepusht
