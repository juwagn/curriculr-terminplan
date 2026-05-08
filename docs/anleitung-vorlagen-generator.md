# Schulwochen-Vorlagen-Generator - Anleitung für Schulleitungen

> **Ziel:** Eine fertige Excel-Vorlage für das neue Schuljahr erzeugen — mit automatischer Schulwochen-Berechnung, Ferien-Dropdowns und professionellem Layout.

---

## Übersicht

Der Generator erstellt aus einer Basis-Vorlage (`Terminplan_2026_27.xlsx`) eine neue Datei für dein Schuljahr. Du musst nur **Ferien und Eckdaten** eingeben — Excel berechnet den Rest automatisch.

**Was bleibt erhalten:**
- Alle 42 Schulwochen (SW 00–41) mit automatischen Montags-Datumsformeln
- Dropdown-Menüs für Wochentag, Kategorie und "Ganztägig"
- Farbige Formatierung und professionelles Layout
- Blattschutz auf den technischen Spalten
- Vier Sheets: Terminplan, Ferien, Kategorien, Anleitung

---

## Variante A: Online über GitHub (empfohlen, keine Installation)

Diese Variante funktioniert komplett im Browser — du brauchst nichts zu installieren.

### Voraussetzungen
- Ein GitHub-Konto (kostenlos auf [github.com](https://github.com))
- Der Administrator muss dir Zugriff auf das Repository gegeben haben

### Schritt 1: GitHub-Action starten

1. Öffne das Projekt-Repository auf GitHub
2. Klicke oben auf den Reiter **"Actions"**
3. Wähle in der linken Liste **"Schulwochen-Vorlage generieren"**
4. Klicke auf den Dropdown **"Run workflow"**

```
┌─────────────────────────────────────┐
│  Actions  │  ...  │  Settings       │
├─────────────────────────────────────┤
│  🔄 Schulwochen-Vorlage generieren  │
│                                     │
│  ▼ Run workflow                     │
│     ┌──────────────────────────┐   │
│     │ Schuljahr: 2026/27       │   │
│     │ Erster Schultag: ...     │   │
│     └──────────────────────────┘   │
│                                     │
└─────────────────────────────────────┘
```

### Schritt 2: Daten eingeben

Fülle das Formular aus:

| Feld | Beispiel | Erklärung |
|------|----------|-----------|
| **Schuljahr** | `2026/27` | Wird im Dateinamen und in der Anleitung verwendet |
| **Erster Schultag (SW 00)** | `2026-08-24` | Montag der Vorbereitungswoche |
| **Erster Unterrichtstag (SW 01)** | `2026-08-31` | Erster regulärer Unterrichtstag |
| **Letzter Schultag** | `2027-07-16` | Letzter Tag vor den Sommerferien |
| **Herbstferien von/bis** | `2026-10-19` / `2026-10-30` | Falls keine Ferien: leer lassen |
| **Weihnachtsferien von/bis** | `2026-12-22` / `2027-01-03` | |
| **Osterferien von/bis** | `2027-03-22` / `2027-04-02` | |
| **Pfingstferien** | (optional) | Falls dein Bundesland keine hat: leer lassen |
| **Sommerferien von/bis** | `2027-07-19` / `2027-08-31` | |

> **Tipp:** Das Format für alle Daten ist `JJJJ-MM-TT` (Jahr-Monat-Tag).<br>
> Beispiel: `2026-08-24` = 24. August 2026

### Schritt 3: Workflow starten

Klicke auf den grünen Button **"Run workflow"**. Nach etwa 1 Minute ist die Datei fertig.

### Schritt 4: Datei herunterladen

1. Klicke auf den gerade gestarteten Workflow-Lauf
2. Scrolle nach unten zu **"Artifacts"**
3. Klicke auf die Datei `Schulwochen-Vorlage-2026_27.xlsx`
4. Die ZIP-Datei wird heruntergeladen — entpacke sie

---

## Variante B: Lokal auf dem Computer (für IT-Administratoren)

Falls du keinen Zugriff auf GitHub hast oder lieber lokal arbeitest.

### Voraussetzungen

1. **Python 3.10 oder höher** installiert ([python.org](https://python.org))
2. **openpyxl** installiert:
   ```bash
   pip install openpyxl
   ```

### Schritt 1: Interaktiv

Öffne ein Terminal / Command Prompt im Projektordner:

```bash
cd scripts
python generate_school_template.py --interactive
```

Das Skript fragt dich nach allen Daten. Gib sie ein und bestätige mit Enter.

```
==================================================
Schulwochen-Vorlagen-Generator
==================================================

Bitte die Eckdaten fuer das neue Schuljahr eingeben.
Format fuer alle Daten: JJJJ-MM-TT  (z.B. 2026-08-24)

Schuljahr [2026/27]:
Erster Schultag (SW 00) [2026-08-24]:
...
```

### Schritt 2: Mit JSON-Konfiguration

Für wiederholbare Generierung (z.B. jedes Jahr):

1. Kopiere die Beispieldatei:
   ```bash
   cp scripts/ferien_2026_27.json scripts/ferien_2027_28.json
   ```

2. Bearbeite die JSON-Datei mit einem Texteditor:
   ```json
   {
     "schuljahr": "2027/28",
     "sw00": "2027-08-23",
     "sw01": "2027-08-30",
     ...
   }
   ```

3. Generiere:
   ```bash
   cd scripts
   python generate_school_template.py --config ferien_2027_28.json
   ```

---

## Nach der Generierung

### So verwendest du die Vorlage

1. Öffne die Datei in Excel, LibreOffice oder Google Sheets
2. Wechsle zum Tab **"Ferien"** — prüfe, ob die Daten stimmen
3. Wechsle zum Tab **"Terminplan"** — trage deine Termine ein
4. Spalten E–K sind für die Eingabe vorgesehen (A–D sind ausgeblendet)
5. Speichere und importiere die Datei im [Konverter](konverter/Terminplan_Konverter.html)

### Wichtige Hinweise

- **Ferien nur im Ferien-Tab pflegen** — nicht zusätzlich als Termin eintragen, sonst entstehen Dubletten
- **SW 00 und SW 01** sind getrennt: SW 00 = Vorbereitungstage, SW 01 = erster regulärer Unterricht
- **Teilwochen** (schulfreie Tage zu Schuljahresbeginn) als eigene Termine eintragen, Kategorie "Feiertage/Ferien", Ganztägig = Ja
- Die Datei hat **keinen Blattschutz** — sie bleibt bewusst bearbeitbar

---

## Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| "Template nicht gefunden" | Stelle sicher, dass `vorlagen/Terminplan_2026_27.xlsx` existiert |
| "openpyxl ist nicht installiert" | Führe `pip install openpyxl` aus |
| Falsches Datumsformat | Verwende immer `JJJJ-MM-TT`, z.B. `2026-08-24` |
| Excel zeigt `#NAME?` an | Öffne die Datei mit Microsoft Excel (nicht LibreOffice) für volle Formel-Unterstützung |
| Dropdowns fehlen | Die Dropdowns funktionieren nur in Excel, nicht in Google Sheets |

---

## Technische Details (für IT)

Das Skript arbeitet wie folgt:

1. Lädt die Basis-Vorlage `vorlagen/Terminplan_2026_27.xlsx` mit openpyxl
2. Überschreibt **nur** die Zellen im Ferien-Sheet (B3-C7, B10-B12)
3. Aktualisiert den Titel im Anleitungs-Sheet
4. Speichert als neue Datei — alle Formeln, Formatierungen und Data Validations bleiben unverändert

Die Vorlage enthält über 1.000 Excel-Formeln, die automatisch:
- Schulwochen-Montage berechnen (unter Berücksichtigung von Ferien)
- Schulwochen-Anzeigen formatieren (`TT.MM. - TT.MM.JJJJ`)
- Warnungen für Wochen nach dem letzten Schultag anzeigen
