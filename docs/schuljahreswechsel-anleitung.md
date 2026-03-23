# Schuljahreswechsel – Schritt-für-Schritt-Anleitung

Dieses Dokument beschreibt alles, was zum Beginn eines neuen Schuljahres zu tun ist.

---

## Voraussetzungen

- Python 3 installiert
- `openpyxl` installiert: `pip install openpyxl`
- Terminal im Projektordner geöffnet (wo diese Datei liegt)

---

## Teil 1 – Excel-Vorlage aktualisieren

### Schritt 1: Feriendaten eintragen

Öffne `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` und wechsle zum **Ferien**-Tab.

Trage die Feriendaten des neuen Schuljahres ein (Format: `TT.MM.JJJJ`):

| Zelle | Inhalt |
|-------|--------|
| B3 | Herbstferien: erster Ferientag (Montag) |
| C3 | Herbstferien: letzter Ferientag (Freitag) |
| B4 | Weihnachtsferien: erster Ferientag |
| C4 | Weihnachtsferien: letzter Ferientag |
| B5 | Osterferien: erster Ferientag |
| C5 | Osterferien: letzter Ferientag |
| B6 | Pfingstferien: erster Ferientag (darf leer bleiben) |
| C6 | Pfingstferien: letzter Ferientag (darf leer bleiben) |
| B7 | Sommerferien: erster Ferientag |
| C7 | Sommerferien: letzter Ferientag |
| B10 | Erster Schultag des neuen Schuljahres (muss ein **Montag** sein) |
| B11 | Letzter Schultag |
| B12 | (optional) weiteres Datum |

**Speichern und schließen**, bevor die Scripts laufen.

---

### Schritt 2: Scripts ausführen

Alle drei Scripts vom Projektordner aus ausführen, **in dieser Reihenfolge**:

```bash
# 1. Nur bei Strukturänderungen (mehr Zeilen pro SW, andere SW-Anzahl etc.)
#    Normalerweise NICHT jedes Jahr nötig – nur wenn die Struktur geändert wurde.
python scripts/patch_rows.py

# 2. Jedes Jahr: Datumsformeln setzen (Schulmontage berechnen)
python scripts/patch_xlsx.py

# 3. Jedes Jahr: Formatierung, Dropdowns, Anleitung-Tab neu schreiben
python scripts/build_excel_template.py
```

> **Wann ist `patch_rows.py` nötig?**
> Nur wenn die Vorlage strukturell verändert wurde (z. B. mehr Zeilen pro Schulwoche, andere Anzahl Schulwochen). Bei normalem Schuljahreswechsel reichen Script 2 + 3.

---

### Schritt 3: Verifikation

```bash
python scripts/recalc.py
```

Das Script prüft, ob alle Schulmontage korrekt berechnet wurden, und gibt eine JSON-Ausgabe. Alle Einträge sollten `"status": "ok"` zeigen.

> **Hinweis:** `recalc.py` enthält aktuell Sollwerte für 2025/26. Bei einem anderen Schuljahr werden die Werte als abweichend gemeldet – das ist erwartet, solange die Formeln korrekt sind. Wichtig ist, dass die Datumsformeln vorhanden sind (kein `null`).

---

### Schritt 4: Datei prüfen

Öffne die fertige Vorlage `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` in Excel:

- [ ] Ferien-Tab: Datumszellen zeigen echte Datumswerte (nicht reine Zahlen)
- [ ] Terminplan-Tab: Spalte B zeigt Datumsangaben für alle SW-Zeilen
- [ ] Terminplan-Tab: Spalte D zeigt Wochenbezeichnungen (z. B. `18.08. – 22.08.2025`)
- [ ] Anleitung-Tab: vorhanden, lesbar, enthält aktuelles Schuljahr
- [ ] Keine doppelten Tabs

---

## Teil 2 – Termine aus Word-Datei importieren

Den Inhalt von `prompts/excel-import-prompt.md` in Claude einfügen, dann die Word-Datei anhängen.

Claude überträgt die Termine strukturiert in die Excel-Vorlage.

**Besonderheit Teilwochen** (z. B. nach Pfingstferien):
Wenn die Schule nicht am Montag beginnt, trägt Claude die schulfreien Tage (Mo/Di) als eigene Zeilen ein: Kategorie = `Feiertage/Ferien`, Ganztägig = `Ja`.

---

## Teil 3 – Konverter-Tool testen

1. Fertige Excel-Datei im Konverter-Tool `konverter/GSH_Terminplan_Konverter.html` öffnen (lokal im Browser)
2. Datei hochladen → Vorschau prüfen
3. Bei Fehlern: Excel-Datei korrigieren, erneut hochladen

---

## Teil 4 – Webseite & Plugin aktualisieren

- `website/index.html`: Versionsnummer und Schuljahresangaben aktualisieren
- Plugin in WordPress hochladen falls neue Plugin-Version vorhanden
- Vorlage unter `website/downloads/` liegt automatisch bereit (wird per Git deployed)

---

## Dateiübersicht

| Datei | Zweck |
|-------|-------|
| `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` | Die fertige Vorlage (wird von Scripts generiert) |
| `scripts/patch_rows.py` | Grundstruktur schreiben (einmalig bei Strukturänderung) |
| `scripts/patch_xlsx.py` | Datumsformeln setzen (jährlich) |
| `scripts/build_excel_template.py` | Formatierung + Anleitung-Tab (jährlich) |
| `scripts/recalc.py` | Verifikation der Datumsformeln |
| `prompts/excel-import-prompt.md` | Claude-Prompt für Termin-Import aus Word |
| `konverter/GSH_Terminplan_Konverter.html` | Standalone Konverter-Tool (Browser) |
| `plugin/gsh-terminplan.php` | WordPress-Plugin |

---

## Häufige Fehler

**Datum wird als Zahl angezeigt (z. B. `46049`)**
→ Excel-Zahlenformat der Zelle manuell auf `TT.MM.JJJJ` setzen, oder Scripts erneut ausführen.

**`patch_xlsx.py` findet keine SW-Header**
→ `patch_rows.py` wurde nicht ausgeführt. Reihenfolge beachten.

**Anleitung-Tab fehlt oder ist doppelt**
→ `build_excel_template.py` erneut ausführen (beseitigt doppelte Tabs automatisch).

**Pfingstferien-Woche fehlt im Terminplan**
→ B6/C6 im Ferien-Tab leer lassen ist korrekt – Pfingstferien sind optional.
