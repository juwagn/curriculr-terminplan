# Design: Konverter Import-Fix

**Datum:** 2026-03-23
**Dateien:** `konverter/GSH_Terminplan_Konverter.html`, `scripts/build_excel_template.py`, `prompts/excel-import-prompt.md`, `scripts/patch_2026_27.py` (einmalig)

---

## Probleme

### Problem 1 – Konverter liest immer das erste Sheet
`wb.SheetNames[0]` gibt „Anleitung" zurück, nicht „Terminplan". Der Konverter versucht Anleitungstext als Termindaten zu parsen → 0 Termine importiert.

### Problem 2 – Spalte B der SW-Header-Zeilen enthält Formelstrings, keine Datumswerte
`patch_xlsx.py` schreibt Formeln (`=Ferien!$F$15`) in Spalte B. SheetJS kann Excel-Formeln nicht berechnen und liefert den Formelstring zurück. `parseImportDate("=Ferien!$F$15")` ergibt `null` → `currentMonday` bleibt `null` → alle Datenzeilen der Schulwoche werden übersprungen.

Die carry-forward-Logik im Old-Format-Branch des Konverters existiert bereits korrekt. Sie funktioniert aber nur, wenn in Spalte B der SW-Header-Zeile ein tatsächlicher Datumswert steht (ISO-String oder Excel-Serial-Number), nicht ein Formelstring.

### Problem 3 – build_excel_template.py legt „Anleitung" als erstes Sheet an
`make_anleitung()` ruft `wb.create_sheet('Anleitung', 0)` auf → „Anleitung" landet an Position 0. Konverter liest dadurch das falsche Sheet.

### Problem 4 – Import-Prompt: Spalte B wird nicht befüllt
Der Prompt enthält „Nur Spalten E–K schreiben, nie A–D". Claude lässt Spalte B leer → nach Fix 1 wird das richtige Sheet gelesen, aber `currentMonday` bleibt null (Problem 2). Außerdem: Der Prompt gibt keine Anweisung, echte ISO-Datumswerte in Spalte B zu schreiben.

### Problem 5 – Bestehende Datei GSH_Terminplan_2026_27.xlsx ist defekt
SW-Header-Zeilen: B enthält Formelstrings (`=Ferien!$F$15`). Datenzeilen: B = None. Sheet-Reihenfolge: „Anleitung" steht an Position 0. Datei muss einmalig gepatcht werden.

---

## Lösung

### Fix 1 – Konverter: Sheet „Terminplan" by name suchen

```javascript
// Vorher:
const ws = wb.Sheets[wb.SheetNames[0]];

// Nachher:
const sheetName = wb.SheetNames.find(n => n.toLowerCase() === 'terminplan')
                  ?? wb.SheetNames[0];
const ws = wb.Sheets[sheetName];
```

### Fix 2 – kein eigener Fix nötig
Die carry-forward-Logik im Old-Format-Branch des Konverters ist bereits korrekt implementiert. Sie wird automatisch funktionieren, sobald Fix 5 echte ISO-Datumswerte in Spalte B der SW-Header-Zeilen schreibt. Kein Code im Konverter muss geändert werden außer Fix 1.

### Fix 3 – build_excel_template.py: „Terminplan" als erstes Sheet

Nach dem Erstellen aller Sheets die Reihenfolge mit der öffentlichen API setzen (kein `_sheets`-Zugriff):

```python
# Terminplan an erste Position verschieben
desired_order = ['Terminplan', 'Ferien', 'Kategorien', 'Anleitung']
for idx, name in enumerate(desired_order):
    if name in wb.sheetnames:
        wb.move_sheet(name, offset=-(wb.sheetnames.index(name) - idx))
```

`wb.move_sheet()` ist seit openpyxl 3.0.3 verfügbar (public API, stable).

### Fix 4 – Import-Prompt: Spalte B Pflichtanweisung + Widerspruch beheben

Zwei Stellen im Prompt müssen geändert werden:

**Stelle A – Spalten-Tabelle:** Zeile für Spalte B ergänzen:
> **B** | Montag-ISO | 2 | ISO-Datum des Montags der Schulwoche (`YYYY-MM-DD`, Textwert). Kein Formelverweis. In JEDE Zeile (SW-Header und Datenzeilen) eintragen.

**Stelle B – Wichtig-Block:** Die bestehende Einschränkung „Nur Spalten E–K schreiben, nie A–D" ersetzen durch:
> Schreibe **B** mit dem Montag-ISO-Datum (`YYYY-MM-DD`, echter Textwert — kein `=Ferien!…`-Formelverweis), **E–K** mit den Termindaten. Spalten A, C, D niemals beschreiben.

### Fix 5 – Einmaliger Patch: scripts/patch_2026_27.py

Das Script berechnet alle 42 Schulmontage unabhängig in Python (kein `data_only=True` nötig, keine Abhängigkeit von gecachten Excel-Formelwerten):

**Algorithmus:**
1. Workbook laden (`data_only=False`): Ferien-Sheet lesen
2. `schuljahr_start` = B10 (openpyxl liefert datetime-Objekt, weil patch_xlsx.py echte Datumswerte schreibt)
3. Ferien-Perioden aus B3:C7 lesen (ebenfalls datetime-Objekte)
4. 42 Schulmontage berechnen: Ab `schuljahr_start` wochenweise iterieren, Wochen überspringen, deren Montag in einer Ferienperiode liegt (Montag zwischen ferienStart und ferienEnde inkl.)
5. Terminplan-Sheet durchlaufen:
   - Zeile mit A = „SW XX": `sw_num` extrahieren (0–41), `monday = mondays[sw_num]`, ISO-String in B schreiben
   - Datenzeile (A leer): `monday` aus letztem SW-Header vererben, ISO-String in B schreiben
6. Backup: Originaldatei nach `vorlagen/GSH_Terminplan_2026_27.xlsx.bak` kopieren vor dem Überschreiben
7. Speichern als `vorlagen/GSH_Terminplan_2026_27.xlsx`

**Edge cases:**
- Zeilen vor dem ersten SW-Header: `monday` ist None → `currentMonday` ist None → B leer lassen
- Pfingstferien optional (B6/C6 leer): beim Ferien-Einlesen None-Werte ignorieren

---

## Nicht verändert

- iCal-Export-Logik im Konverter
- Plugin-Dateien
- `patch_xlsx.py`, `patch_rows.py`, `recalc.py`

---

## Ausführungsreihenfolge

```
1. Konverter HTML patchen (Fix 1)
2. build_excel_template.py patchen (Fix 3)
3. Import-Prompt patchen (Fix 4)
4. patch_2026_27.py erstellen und ausführen (Fix 5)
5. build_excel_template.py ausführen → neue Vorlage generieren
6. Konverter mit vorhandener Datei testen (manuell)
7. Alles committen + pushen
```
