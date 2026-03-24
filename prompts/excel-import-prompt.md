# Claude Import-Prompt: Word-Datei → Excel-Vorlage

Diesen Prompt in ein neues Claude-Gespräch einfügen und beide Dateien anhängen.

---

Ich habe eine Word-Datei mit der Terminplanung für das Schuljahr und möchte alle
Termine in die Excel-Vorlage `GSH_Terminplan_Schulwochen_Vorlage.xlsx` übertragen.
Bitte lies beide Dateien und fülle die Excel-Vorlage korrekt aus.

**Wichtig: Vollständigkeit hat oberste Priorität. Kein Termin darf verloren gehen.**
**Das gilt auch für die Anmerkungen-Spalte (letzte Spalte der Word-Tabelle).**

## Struktur der Excel-Vorlage

**Sheet „Terminplan"** – Spalten:

| Spalte | Inhalt | Hinweis |
|--------|--------|---------|
| A | SW-Key | `SW 00` … `SW 41` – nie überschreiben |
| **B** | **Montag-ISO** | ISO-Datum des Montags (`YYYY-MM-DD`, echter Textwert). In JEDE Zeile schreiben (SW-Header UND Datenzeilen). Kein `=Ferien!…`-Formelverweis. |
| C | (intern) | nie beschreiben |
| D | Schulwoche | nie beschreiben |
| **E** | **Wochentag** | `Mo` / `Di` / `Mi` / `Do` / `Fr` / `Ganze Woche` |
| **F** | **Uhrzeit** | `08:30` – leer wenn ganztägig |
| **G** | **Endzeit / Enddatum** | `14:30` für Uhrzeiten – oder ISO-Datum (`YYYY-MM-DD`) als Enddatum für mehrteilige Ganze-Woche-Einträge |
| **H** | **Titel / Veranstaltung** | Freitext |
| **I** | **Kategorie** | exakt einer der 7 Werte (s. u.) |
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

---

## Vorgehen

### Schritt 1 – Word-Datei analysieren

Die Word-Tabelle hat folgende Spalten: **SW-Nr., Schulwoche, Montag, Dienstag, Mittwoch, Donnerstag, Freitag, Anmerkungen**.

**Schritt 1a – Tageseinträge extrahieren (Spalten Montag–Freitag):**

Extrahiere alle Termine aus den Tagesspalten und nummeriere sie fortlaufend:

```
#001 | 25.08.2026 | Mi | Stehkaffee für alle LuL           | Konferenzen/DB
#002 | 26.08.2026 | Do | Einführungstag EF                  | Oberstufe
...
```

Gib die vollständige Liste aus.

**Schritt 1b – Anmerkungen-Spalte extrahieren (letzte Spalte der Word-Tabelle):**

Extrahiere ALLE Einträge aus der Anmerkungen-Spalte und nummeriere sie im Anschluss weiter:

```
#NNN | SW 01 | Jg 5+6 ganze Woche 1.-5. Std Unterricht
#NNN | SW 01 | Mittagessen ab Donnerstag
#NNN | SW 02 | 12.-13.09. Rosch Haschana (jüd. Feiertag)
#NNN | SW 25 | 09.02.–20.02. Abi Vorklausuren
#NNN | SW 25 | 02.02.–13.02. Anmeldung SI/SII
```

Analysiere jeden Anmerkungseintrag:
- Beginnt der Text mit einem **Datumsbereich** (`DD.MM.–DD.MM.` oder `DD.–DD.MM.` oder `DD.MM.-DD.MM.`)?
  → Ja: Datum vom Titeltext abtrennen. Merke: Startdatum, Enddatum, Titeltext ohne Datum.
  → Nein: Eintrag gehört zur gesamten SW (kein Datum, nur Text).
- Ist es ein **Einzeldatum** (`DD.MM.`)?
  → Als normalen Tageseintrag behandeln (nicht als "Ganze Woche").

**Gesamtzählung = Tageseinträge + Anmerkungseinträge.** Diese Zahl ist deine Kontrollgröße.

### Schritt 2 – Schulmontage berechnen

**Lies Spalte B der Vorlage NICHT** – dort stehen Excel-Formeln, die Python nicht auswerten kann.
Berechne stattdessen alle Schulmontage selbst:

```python
from datetime import date, timedelta
import openpyxl

wb = openpyxl.load_workbook('GSH_Terminplan_Schulwochen_Vorlage.xlsx')
ws_ferien = wb['Ferien']

# Erster Schultag (SW 00) aus B10
schuljahr_start = ws_ferien.cell(10, 2).value
if hasattr(schuljahr_start, 'date'):
    schuljahr_start = schuljahr_start.date()

# Ferienperioden aus B3:C7
ferien = []
for r in range(3, 8):
    von = ws_ferien.cell(r, 2).value
    bis = ws_ferien.cell(r, 3).value
    if von and bis:
        ferien.append((von.date() if hasattr(von,'date') else von,
                       bis.date() if hasattr(bis,'date') else bis))

def ist_ferien(monday):
    return any(von <= monday <= bis for von, bis in ferien)

# 42 Schulmontage (SW 00–41) berechnen
mondays = []
current = schuljahr_start
while len(mondays) < 42:
    if not ist_ferien(current):
        mondays.append(current)
    current += timedelta(weeks=1)

# Ergebnis: mondays[0] = SW 00, mondays[1] = SW 01, ...
```

Gib aus: `SW 00 = YYYY-MM-DD … SW 41 = YYYY-MM-DD`

### Schritt 3 – Termine einordnen

Ordne jeden nummerierten Termin der richtigen Schulwoche zu:

```python
def finde_schulwoche(termin_datum, mondays):
    for i, monday in enumerate(mondays):
        if monday <= termin_datum <= monday + timedelta(days=6):
            return i, monday
    return None, None  # außerhalb des Schuljahres
```

- Wochentag: `termin_datum.weekday()` → 0=Mo, 1=Di, 2=Mi, 3=Do, 4=Fr
- Ferien/Feiertage ganzer Wochen: Wochentag = `Ganze Woche`, Ganztägig = `Ja`
- Termine außerhalb aller Schulwochen: merken für den Abschlussbericht

**Teilwochen** (Schule beginnt nicht am Montag, z. B. nach Pfingstferien):
Jeden schulfreien Wochentag am Wochenanfang als eigene Zeile:
Wochentag = `Mo` / `Di`, Titel = `schulfrei`, Kategorie = `Feiertage/Ferien`, Ganztägig = `Ja`.

**Anmerkungen einordnen:**
- Die SW ergibt sich direkt aus der Tabellenzeile der Word-Datei (Anmerkung steht in der Zeile der SW)
- Wochentag = `Ganze Woche`, Ganztägig = `Ja`
- Titel (Spalte H) = Titeltext **ohne** Datumspräfix
- **Spalte G**: ISO-Enddatum (`YYYY-MM-DD`) wenn der Anmerkungseintrag einen Datumsbereich enthielt; sonst leer
  - Beispiel: `09.02.–20.02. Abi Vorklausuren` → Spalte G = `2027-02-20`, Spalte H = `Abi Vorklausuren`
  - Wenn Enddatum in einem anderen Schuljahr-Jahr liegt: Jahr korrekt ermitteln (nicht immer 2026!)
- Kategorie per Keyword-Matching bestimmen (wie normale Termine)
- Anmerkungen mit Datumsbereich: Schulwoche des **Startdatums** für die Einordnung verwenden

### Schritt 4 – Eintragen

Für jede Schulwoche die freien Datenzeilen (A leer, H leer) füllen:

```python
ws = wb['Terminplan']

# SW-Header-Zeilen und freie Datenzeilen pro SW aufbauen
sw_rows       = {}  # sw_num -> [freie Zeilennummern]
sw_last_row   = {}  # sw_num -> letzte Datenzeile des SW-Blocks
current_sw    = None
free_rows     = []
last_row_seen = None

for row in range(2, ws.max_row + 1):
    a = ws.cell(row, 1).value
    h = ws.cell(row, 8).value
    if a and str(a).startswith('SW '):
        if current_sw is not None:
            sw_rows[current_sw]     = free_rows
            sw_last_row[current_sw] = last_row_seen
        current_sw    = int(str(a).split()[1])
        free_rows     = []
        last_row_seen = row
    elif current_sw is not None:
        last_row_seen = row
        if not h:
            free_rows.append(row)
if current_sw is not None:
    sw_rows[current_sw]     = free_rows
    sw_last_row[current_sw] = last_row_seen

# Eintragen – bei vollem SW Zeile einfügen statt Termin weglassen
nicht_eingetragen = []
rows_inserted     = 0  # Offset durch eingefügte Zeilen mitführen

for termin in termine:
    sw_num, monday = finde_schulwoche(termin.datum, mondays)
    if sw_num is None:
        nicht_eingetragen.append((termin.nr, termin.titel, 'außerhalb Schuljahr'))
        continue
    if not sw_rows.get(sw_num):
        # Neue Zeile nach dem letzten Datenblock dieses SW einfügen
        insert_at = sw_last_row[sw_num] + 1
        ws.insert_rows(insert_at)
        rows_inserted += 1
        # Alle nachfolgenden SW-Referenzen um 1 verschieben
        for n in range(sw_num + 1, 42):
            if n in sw_last_row:
                sw_last_row[n] += 1
            sw_rows[n] = [r + 1 for r in sw_rows.get(n, [])]
        sw_last_row[sw_num] = insert_at
        sw_rows[sw_num]     = [insert_at]
    target_row = sw_rows[sw_num].pop(0)
    ws.cell(target_row, 2).value  = monday.isoformat()   # B
    ws.cell(target_row, 5).value  = termin.wochentag     # E
    ws.cell(target_row, 6).value  = termin.uhrzeit       # F
    ws.cell(target_row, 7).value  = termin.endzeit       # G  (Uhrzeit ODER ISO-Enddatum für Ganze-Woche-Einträge)
    ws.cell(target_row, 8).value  = termin.titel         # H
    ws.cell(target_row, 9).value  = termin.kategorie     # I
    ws.cell(target_row, 10).value = termin.ganztaegig    # J
    ws.cell(target_row, 11).value = termin.anmerkung     # K
```

**Spalten A, C, D niemals beschreiben.**

### Schritt 5 – Vollständigkeitsprüfung

```python
# Prüfen: wie viele Zeilen mit Titel wurden tatsächlich geschrieben?
eingetragen = sum(1 for row in range(2, ws.max_row+1) if ws.cell(row,8).value)

# Zusatz: Ganze-Woche-Einträge zählen (sollte = Anzahl Anmerkungen sein)
ganze_woche = sum(1 for row in range(2, ws.max_row+1)
                  if ws.cell(row,5).value == 'Ganze Woche' and ws.cell(row,8).value)
```

Berichte:
- Tageseinträge aus Word-Datei: **N₁**
- Anmerkungseinträge aus Word-Datei: **N₂**
- Gesamt: **N = N₁ + N₂**
- Davon eingetragen: **M**
- Davon als `Ganze Woche` (Anmerkungen): **G**
- Nicht eingetragen (mit Grund): vollständige Liste

Wenn `M < N`: Untersuche die Differenz und behebe sie bevor du speicherst.

### Schritt 6 – Speichern

```python
wb.save('GSH_Terminplan_2026_27.xlsx')
```

---

## Abschlussbericht (Pflicht)

```
Tageseinträge in Word-Datei:       XXX
Anmerkungseinträge in Word-Datei:   XX
Gesamt:                            XXX
Erfolgreich eingetragen:           XXX  (davon X in eingefügten Zusatzzeilen)
  davon Ganze-Woche (Anmerkungen):  XX  (davon X mit Enddatum in Spalte G)
Nicht eingetragen:                   X
  - #042 | 15.04.2027 | ... | Grund: außerhalb Schuljahr
  - #107 | ...

Kategorien:
  Konferenzen/DB:    XX
  Oberstufe:         XX
  ...
```

---

**Dateien:**
- Word-Datei: `[HIER DATEINAMEN EINFÜGEN]`
- Excel-Vorlage: `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx`
