# SOP – Terminplan Schuljahreswechsel

## Zweck

Diese SOP beschreibt den sicheren Jahresprozess fuer den Terminplan: Excel-Vorlage erzeugen, durch die Schulleitung pflegen lassen, in ICS konvertieren und in IServ importieren.

Ziel: Die Schulleitung kann jedes Jahr Ferien, SW 00, SW 01, letzten Schultag und Termine selbst pflegen. Die IT stellt nur Vorlage, Konverter und Pruefung bereit.

## Rollen

| Rolle | Verantwortung |
|-------|---------------|
| Schulleitung | Ferien, Eckdaten und Schultermine fachlich eintragen und freigeben |
| IT / Schul-IT | Vorlage erzeugen, technische Pruefung ausfuehren, Konverter bereitstellen |
| IServ-Admin | Finale ICS-Datei in IServ importieren |
| Redaktion / Website | Aktuelle Vorlage und Konverter im Downloadbereich bereitstellen |

## Dateien

| Datei | Zweck |
|-------|-------|
| `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` | Bearbeitbare Jahresvorlage |
| `konverter/Terminplan_Konverter.html` | Lokaler Excel-zu-ICS-Konverter |
| `website/downloads/Terminplan_Konverter.html` | Downloadkopie des Konverters |
| `scripts/build_excel_template.py` | Erzeugt die Jahresvorlage neu |
| `scripts/recalc.py` | Prueft Struktur, Formeln und Bearbeitbarkeit |
| `scripts/test_converter_logic.mjs` | Prueft Konverter-Importlogik |

## Grundsatz

- Keine Blattsperre in der Excel-Datei.
- Technische Spalten A-C im Terminplan bleiben ausgeblendet, aber nicht gesperrt.
- Die Schulleitung arbeitet im Tab `Ferien` und im Tab `Terminplan`.
- Ferien werden nur im Tab `Ferien` gepflegt, nicht doppelt als Terminplan-Zeilen.
- SW 00 und SW 01 sind getrennt:
  - SW 00 = erster Schultag, Vorbereitung, Konferenzen, Organisationsstart.
  - SW 01 = erster regulaerer Unterrichtstag.

## Ablauf

### 1. Vorlage neu erzeugen

IT fuehrt im Projektordner aus:

```bash
python scripts/build_excel_template.py
```

Ergebnis:

```text
website/downloads/Terminplan_Schulwochen_Vorlage.xlsx
```

### 2. Technische Vorpruefung

IT fuehrt aus:

```bash
python scripts/recalc.py
```

Erwartung:

```json
{
  "status": "success",
  "errors": []
}
```

Wenn `errors` nicht leer ist: Datei nicht an die Schulleitung geben. Fehler beheben, Vorlage neu erzeugen, erneut pruefen.

### 3. Schulleitung pflegt Eckdaten

Schulleitung oeffnet die Vorlage und bearbeitet Tab `Ferien`.

Pflichtfelder:

| Zelle | Inhalt |
|-------|--------|
| B3:C3 | Herbstferien |
| B4:C4 | Weihnachtsferien |
| B5:C5 | Osterferien |
| B6:C6 | Pfingstferien, optional |
| B7:C7 | Sommerferien |
| B10 | Erster Schultag, SW 00 |
| B11 | Erster Unterrichtstag, SW 01 |
| B12 | Letzter Schultag |

Regel: B10 und B11 muessen Montage sein.

### 4. Schulleitung pflegt Termine

Schulleitung bearbeitet im Tab `Terminplan` nur sichtbare Spalten E-K.

| Spalte | Inhalt |
|--------|--------|
| E | Wochentag |
| F | Startzeit |
| G | Endzeit oder Enddatum bei `Ganze Woche` |
| H | Titel / Veranstaltung |
| I | Kategorie |
| J | Ganztägig |
| K | Anmerkung |

Pflicht fuer jeden Termin:

- Titel in Spalte H.
- Wochentag in Spalte E oder `Ganze Woche`.
- Kategorie nach Moeglichkeit aus Dropdown.

Teilwochen:

- Schulfreie Einzel-Tage als eigene Termine eintragen.
- Kategorie `Feiertage/Ferien`.
- `Ganztägig` = `Ja`.

### 5. Fachliche Freigabe

Schulleitung prueft:

- Ferien stimmen.
- SW 00 und SW 01 stimmen.
- Letzter Schultag stimmt.
- Termine sind vollstaendig.
- Mehrtaegige Termine haben korrektes Enddatum.
- Kategorien sind plausibel.

Erst danach Datei an IT / IServ-Admin geben.

### 6. Konverter pruefen

IT oeffnet:

```text
konverter/Terminplan_Konverter.html
```

Dann:

1. Excel-Datei in Konverter ziehen.
2. Import-Zusammenfassung lesen.
3. Warnungen pruefen.
4. Kalender-Vorschau stichprobenartig pruefen.
5. ICS-Datei herunterladen.

Technische Konvertertests:

```bash
node scripts/test_converter_logic.mjs
```

Erwartung: alle Tests `ok`.

### 7. IServ-Import

IServ-Admin importiert die ICS-Datei in IServ.

Nach Import pruefen:

- Ferien als ganztägige Termine vorhanden.
- Erste Schulwoche und erster Unterrichtstag stimmen.
- Stichproben aus mehreren Quartalen stimmen.
- Mehrtaegige Termine enden am richtigen Datum.
- Keine offensichtlichen Dubletten.

### 8. Website aktualisieren

Wenn Vorlage oder Konverter geaendert wurden:

1. `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` aktualisieren.
2. `website/downloads/Terminplan_Konverter.html` aktualisieren.
3. Changelog im Konverter pruefen.
4. Git-Commit erstellen.

## Fehlerbehandlung

| Problem | Vorgehen |
|---------|----------|
| Excel meldet Blattschutz | Falsche/alte Datei im Umlauf. Neue Vorlage mit `scripts/build_excel_template.py` erzeugen. |
| Datum wird als Zahl angezeigt | Zelle als Datum formatieren oder Vorlage neu erzeugen. |
| Konverter importiert 0 Termine | Sheet `Terminplan`, Titelspalte H und SW-Spalten A/B pruefen. |
| Ferien doppelt im Kalender | Ferien duerfen nicht zusaetzlich im Terminplan eingetragen werden. |
| Kategorie fehlt | Kategorie im Excel-Dropdown korrigieren oder Konverter-Warnung bewusst akzeptieren. |
| SW 01 falsch | `Ferien!B11` pruefen; muss erster Unterrichts-Montag sein. |

## Abschlusskriterien

- `scripts/recalc.py` meldet `status: success`.
- `scripts/test_converter_logic.mjs` meldet alle Tests `ok`.
- Excel-Datei ist ohne Blattschutz bearbeitbar.
- Konverter-Changelog nennt aktuelle Aenderung.
- IServ-Stichproben sind fachlich korrekt.
