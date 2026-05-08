# Schuljahreswechsel – Schritt-für-Schritt-Anleitung

Dieses Dokument beschreibt alles, was zum Beginn eines neuen Schuljahres zu tun ist. Die verbindliche Betriebsanweisung steht in `docs/sop-terminplan-schuljahr.md`.

---

## Voraussetzungen

- Python 3 installiert
- `openpyxl` installiert: `pip install openpyxl`
- Terminal im Projektordner geöffnet (wo diese Datei liegt)

---

## Teil 1 – Excel-Vorlage aktualisieren

### Schritt 1: Vorlage neu generieren

Die Jahresvorlage wird ab jetzt aus **einem** Script frisch erzeugt:

```bash
python scripts/build_excel_template.py
```

Ergebnis:

```text
website/downloads/Terminplan_Schulwochen_Vorlage.xlsx
```

Die Datei hat keinen Blattschutz. Technische Spalten A-C im Terminplan sind nur ausgeblendet.

---

### Schritt 2: Ferien & Eckdaten eintragen

Öffne `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` und wechsle zum Tab **Ferien**.

Trage dort die Daten des Schuljahres ein:

| Zelle | Inhalt |
|-------|--------|
| B3 | Herbstferien: erster Ferientag |
| C3 | Herbstferien: letzter Ferientag |
| B4 | Weihnachtsferien: erster Ferientag |
| C4 | Weihnachtsferien: letzter Ferientag |
| B5 | Osterferien: erster Ferientag |
| C5 | Osterferien: letzter Ferientag |
| B6 | Pfingstferien: erster Ferientag, optional |
| C6 | Pfingstferien: letzter Ferientag, optional |
| B7 | Sommerferien: erster Ferientag |
| C7 | Sommerferien: letzter Ferientag |
| B10 | Erster Schultag, SW 00 |
| B11 | Erster Unterrichtstag, SW 01 |
| B12 | Letzter Schultag |

**Wichtig:** SW 00 und SW 01 sind bewusst getrennt. SW 00 kann Vorbereitungstage enthalten, SW 01 ist erster regulärer Unterricht.

---

### Schritt 3: Verifikation

```bash
python scripts/recalc.py
```

Das Script prüft:

- keine Blattsperren
- echte Datumswerte
- SW 00 aus B10
- SW 01 aus B11
- Ferien-Skip in Folgewochen
- 42 SW-Header im Terminplan
- keine offensichtlichen Formel-Fehler

---

### Schritt 4: Datei prüfen

Öffne die fertige Vorlage in Excel:

- [ ] Tab Ferien: Eingabezellen B3:C7 und B10:B12 sind bearbeitbar
- [ ] Tab Terminplan: sichtbare Spalten E-K sind bearbeitbar
- [ ] Technische Spalten A-C sind ausgeblendet
- [ ] Spalte D zeigt Wochenbezeichnungen
- [ ] Keine Schutzmeldung beim Bearbeiten

## Teil 2 – Termine aus Word-Datei importieren

Den Inhalt von `prompts/excel-import-prompt.md` in Claude einfügen, dann die Word-Datei anhängen.

Claude überträgt die Termine strukturiert in die Excel-Vorlage.

**Besonderheit Teilwochen** (z. B. nach Pfingstferien):
Wenn die Schule nicht am Montag beginnt, trägt Claude die schulfreien Tage (Mo/Di) als eigene Zeilen ein: Kategorie = `Feiertage/Ferien`, Ganztägig = `Ja`.

---

## Teil 3 – Konverter-Tool testen

1. Fertige Excel-Datei im Konverter-Tool `konverter/Terminplan_Konverter.html` öffnen (lokal im Browser)
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
| `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` | Die fertige Vorlage (wird von Scripts generiert) |
| `scripts/patch_rows.py` | Grundstruktur schreiben (einmalig bei Strukturänderung) |
| `scripts/patch_xlsx.py` | Datumsformeln setzen (jährlich) |
| `scripts/build_excel_template.py` | Formatierung + Anleitung-Tab (jährlich) |
| `scripts/recalc.py` | Verifikation der Datumsformeln |
| `prompts/excel-import-prompt.md` | Claude-Prompt für Termin-Import aus Word |
| `konverter/Terminplan_Konverter.html` | Standalone Konverter-Tool (Browser) |
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
