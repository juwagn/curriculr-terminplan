# Implementierungs-Prompt: Schuljahreswechsel-Modus im Konverter

Diesen Prompt in ein neues Claude-Gespräch mit dem Projektordner einfügen.

---

## Aufgabe

Erweitere den Konverter (`konverter/Terminplan_Konverter.html`) um einen
**Schuljahreswechsel-Modus**. Ziel: Die Schulleitung kann ohne Python, Terminal
oder Git-Kenntnisse eine neue Schulwochen-Vorlage für das nächste Schuljahr
erstellen – nur mit dem Konverter-Tool im Browser.

## Hintergrund

Die Vorlage `Terminplan_Schulwochen_Vorlage.xlsx` hat folgende Struktur:
- **Terminplan-Sheet**: 673 Zeilen (SW 00–41, je 1 Header + 15 Datenzeilen)
  - Spalte A: SW-Key (`SW 00` … `SW 41`) in Header-Zeilen
  - Spalte B: ISO-Datum des Schulwochenmontags (`YYYY-MM-DD`) in JEDER Zeile
- **Ferien-Sheet**:
  - B3:C7 – Ferienperioden (Von/Bis als Datum)
  - B10 – Erster Schultag (Montag = SW 00)
  - B11 – Erster Unterrichtstag
  - B12 – Letzter Schultag

Bisher wurden Spalte B und das Ferien-Sheet jährlich durch Python-Scripts aktualisiert.
Das soll jetzt der Konverter übernehmen – direkt im Browser via SheetJS.

## Was gebaut werden soll

### UI-Einstiegspunkt

Ein neuer Button **„⚙ Schuljahreswechsel"** im Header des Konverters (rechts neben
dem bestehenden Titel). Klick öffnet ein Modal.

### Modal: Schuljahreswechsel

**Schritt 1 – Vorlage hochladen**
Dropzone zum Hochladen der aktuellen `Terminplan_Schulwochen_Vorlage.xlsx`.
Konverter liest die Datei mit SheetJS (cellDates: true).

**Schritt 2 – Neue Daten eingeben**
Formular mit folgenden Feldern:

| Feld | Pflicht | Beschreibung |
|------|---------|--------------|
| Erster Schultag (SW 00) | Ja | Datum-Input, muss ein Montag sein |
| Erster Unterrichtstag | Nein | Datum-Input |
| Letzter Schultag | Nein | Datum-Input |
| Herbstferien Von | Ja | Datum-Input |
| Herbstferien Bis | Ja | Datum-Input |
| Weihnachtsferien Von | Ja | Datum-Input |
| Weihnachtsferien Bis | Ja | Datum-Input |
| Osterferien Von | Ja | Datum-Input |
| Osterferien Bis | Ja | Datum-Input |
| Pfingstferien Von | Nein | Datum-Input (darf leer bleiben) |
| Pfingstferien Bis | Nein | Datum-Input (darf leer bleiben) |
| Sommerferien Von | Ja | Datum-Input |
| Sommerferien Bis | Ja | Datum-Input |

Validierung: Erster Schultag muss ein Montag sein → Fehlermeldung wenn nicht.

**Schritt 3 – Generieren & Herunterladen**
Button „Neue Vorlage generieren". Konverter:

1. Berechnet 42 Schulmontage (SW 00–41) in JavaScript:
```javascript
function berechneSchulmontage(schuljahresstart, ferienPerioden) {
  const mondays = [];
  let current = new Date(schuljahresstart);
  while (mondays.length < 42) {
    const istFerien = ferienPerioden.some(({von, bis}) =>
      current >= von && current <= bis
    );
    if (!istFerien) mondays.push(new Date(current));
    current.setDate(current.getDate() + 7);
  }
  return mondays; // mondays[0] = SW 00, mondays[41] = SW 41
}
```

2. Aktualisiert das **Ferien-Sheet** der hochgeladenen Datei:
   - B3:C7 mit neuen Feriendaten
   - B10 mit erstem Schultag
   - B11 mit erstem Unterrichtstag (falls angegeben)
   - B12 mit letztem Schultag (falls angegeben)

3. Aktualisiert **Spalte B** im Terminplan-Sheet:
   - Jede Zeile mit A = `SW XX` → `mondays[sw_num].toISOString().slice(0,10)`
   - Jede Datenzeile (A leer) → ISO-Datum des aktuellen Schulmontags (carry-forward)
   - Formeln in Spalte B werden dabei durch echte Textwerte ersetzt

4. Speichert als `Terminplan_Schulwochen_Vorlage_YYYY_YY.xlsx` via
   `XLSX.writeFile(wb, filename)` – alle anderen Sheets (Kategorien, Anleitung)
   und alle Formatierungen bleiben unverändert erhalten.

## Technische Hinweise

- SheetJS ist bereits eingebunden und kann XLSX lesen UND schreiben
- `XLSX.writeFile` wird bereits an zwei Stellen verwendet (Zeilen ~2028, ~2051)
- Das Modal-System existiert bereits im Konverter (CSS-Klassen `.modal`, `.modal.open`)
- Bestehende SheetJS-Optionen: `{ type: 'array', cellDates: true }`
- Beim Schreiben: Zellwerte als String setzen (`ws['B2'].v = '2026-08-24'`),
  Zelltyp auf `s` (string) setzen, damit SheetJS keinen Datumstyp erzwingt

## Erfolgskriterium

Nach dem Download:
- Datei im Konverter hochladen → Terminplan-Sheet wird gefunden
- Termine können importiert werden (Montags-Datum wird erkannt)
- Ferien-Sheet zeigt neue Feriendaten
- Alle anderen Formatierungen, Dropdowns und Sheets unverändert

## Nicht verändern

- Bestehender 4-Schritt-Import-Workflow
- iCal-Export-Logik
- Plugin-Dateien
- CSS außer für neue Modal-Elemente

## Dateien

- `konverter/Terminplan_Konverter.html` – hier wird alles implementiert
- `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` – Beispiel-Upload zum Testen
