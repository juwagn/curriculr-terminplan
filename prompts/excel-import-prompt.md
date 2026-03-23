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
