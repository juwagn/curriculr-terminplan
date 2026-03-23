# Design: Excel-Vorlage Kapazitätserweiterung

**Datum:** 2026-03-23
**Dateien:** `scripts/patch_rows.py` (neu), `scripts/patch_xlsx.py`, `scripts/build_excel_template.py`, Claude Import-Prompt
**Ansatz:** B – Patch bestehende Datei

---

## Probleme

### 1. SW 41 fehlt in der Vorlage
Das Schuljahr 2026/27 hat eine 41. Schulwoche (12.–16.07.2027). `patch_xlsx.py` generiert nur SW 00–40 (`range(1, 41)`), die Terminplan-Grundstruktur endet ebenfalls bei SW 40. Termine der letzten Schulwoche können nicht eingetragen werden.

### 2. Zu wenige Datenzeilen pro Schulwoche
Die aktuelle Vorlage hat 8 Datenzeilen pro Schulwoche. Vollere Wochen (SW 01, SW 03, SW 26 mit 10+ Einzelereignissen) erzwingen Konsolidierungen mehrerer Termine in einer Zeile. Ziel: 15 Datenzeilen pro SW.

### 3. Teilwochen nicht dokumentiert
Wenn eine Schulwoche erst ab Mittwoch beginnt (z. B. nach Pfingstferien), ist unklar wie Montag/Dienstag zu behandeln sind. Weder die Anleitung in der Excel-Datei noch der Claude-Import-Prompt geben Hinweise.

---

## Lösung

### Fix 1 – Neues `patch_rows.py`: Terminplan-Grundstruktur rebuilden

Neues Script mit einer einzigen Verantwortlichkeit: die Terminplan-Zeilenstruktur in der xlsx-Datei schreiben. Läuft einmalig bei Strukturänderungen (nicht bei jedem Schuljahreswechsel).

**Ablauf:**
1. xlsx laden (`website/downloads/Terminplan_Schulwochen_Vorlage.xlsx`)
2. Terminplan-Sheet öffnen
3. Alle Zeilen ab Zeile 2 löschen (Zeile 1 = Header bleibt)
4. Für SW 00–41 (42 Schulwochen): je 1 Header-Zeile (`col A = "SW XX"`) + 15 leere Datenzeilen schreiben
5. Speichern

**Resultierende Struktur:**
```
Zeile 1:        Spalten-Header (unverändert)
Zeilen 2–17:    SW 00 (1 Header + 15 Daten)
Zeilen 18–33:   SW 01
...
Zeilen 658–673: SW 41
```
Gesamt: 1 + 42 × 16 = 673 Zeilen

**Kompatibilität mit nachgelagerten Scripts:**
`patch_xlsx.py` erkennt SW-Header via `col_A.startswith('SW ')` – diese Logik bleibt unverändert und funktioniert mit der neuen Struktur.

### Fix 2 – `patch_xlsx.py`: Auf SW 41 erweitern

Zwei Zeilen ändern:

```python
# Bereinigung Hilfstabelle (Zeile ~133)
# Vorher:
for r in range(HELPER_HEADER_ROW, HELPER_START_ROW + 42):
# Nachher:
for r in range(HELPER_HEADER_ROW, HELPER_START_ROW + 43):

# Hauptschleife (Zeile ~150)
# Vorher:
for sw_num in range(1, 41):
# Nachher:
for sw_num in range(1, 42):
```

Effekt: Ferien-Hilfstabelle erhält Eintrag F56 für SW 41, und die Terminplan-Formeln (Spalten B + D) werden auch für die SW-41-Header-Zeile gesetzt.

### Fix 3 – `build_excel_template.py`: Teilwoche-Hinweis in Anleitung

Neuer `add_warn()`-Block im Anleitung-Tab im bestehenden „Wichtige Hinweise"-Abschnitt (dort sind bereits alle `add_warn()`-Aufrufe gesammelt, ca. Zeile 208–212 – nach den bestehenden Warn-Blöcken anfügen):

> **Teilwochen (z. B. nach Pfingstferien):** Beginnt die Schule nicht am Montag, trägt diese Woche dennoch eine eigene SW-Zeile. Schulfreie Tage zu Beginn (Mo/Di) als eigene Zeile eintragen: Wochentag = `Mo`/`Di`, Kategorie = `Feiertage/Ferien`, Ganztägig = `Ja`. Danach die normalen Schultermine der restlichen Tage.

### Fix 4 – Claude Import-Prompt: Teilwoche-Abschnitt ergänzen

Ergänzung unter Schritt 3 „Termine einordnen", nach der Ferienlogik:

> **Teilwochen** (Schule beginnt nicht am Montag): Jeden schulfreien Tag zu Beginn der Woche als eigene Zeile eintragen: Wochentag = `Mo`/`Di`, Titel = `schulfrei`, Kategorie = `Feiertage/Ferien`, Ganztägig = `Ja`. Danach normal weitermachen mit den Schulterminen der Restwoche.

---

## Ausführungsreihenfolge

```
1. python scripts/patch_rows.py          # einmalig bei Strukturänderung
2. python scripts/patch_xlsx.py          # bei jedem Schuljahreswechsel
3. python scripts/build_excel_template.py  # bei jedem Schuljahreswechsel
```

Schritt 1 muss vor Schritt 2 laufen, da `patch_xlsx.py` die von `patch_rows.py` gesetzten SW-Header-Zeilen voraussetzt.

---

## Betroffene Dateien

| Aktion | Datei |
|--------|-------|
| Neu erstellen | `scripts/patch_rows.py` |
| Ändern (2 Zeilen) | `scripts/patch_xlsx.py` |
| Ändern (1 Block) | `scripts/build_excel_template.py` |
| Ändern (1 Abschnitt) | Claude Import-Prompt (in `prompts/`) |
| Regeneriert durch Scripts | `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` |

## Nicht berührt

- `scripts/recalc.py`
- Plugin-Dateien (`gsh-terminplan.php`, CSS)
- Ferien-Sheet-Struktur (nur Hilfstabelle um 1 Zeile erweitert)
- Styling-Logik in `build_excel_template.py` (funktioniert mit beliebiger Zeilenanzahl)
