# Excel-Vorlage Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Zwei Bugs in `build_excel_template.py` beheben: (1) falscher lila Hintergrund im Terminplan-Sheet, (2) abgeschnittener Text im Anleitung-Sheet durch zu kleine feste Zeilenhöhen.

**Architecture:** Alle Änderungen in einer einzigen Datei (`scripts/build_excel_template.py`). Kein Build-System, keine Dependencies außer openpyxl (bereits genutzt). Nach Änderung Skript ausführen → Excel-Datei visuell prüfen.

**Tech Stack:** Python 3, openpyxl

---

## Dateien

| Aktion | Pfad |
|---|---|
| Modify | `scripts/build_excel_template.py` |
| Output | `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` (via Skript) |
| Spec | `docs/superpowers/specs/2026-03-22-excel-vorlage-fixes-design.md` |

---

## Task 1: Fix – Terminplan Header-Erkennung (lila Hintergrund)

**Files:**
- Modify: `scripts/build_excel_template.py:270-283`

**Kontext:** `style_terminplan()` prüft aktuell ob Spalten E und H leer sind, um SW-Header-Zeilen zu erkennen. Da leere Datenzeilen ebenfalls leere E- und H-Spalten haben, werden alle Zeilen lila eingefärbt. Stattdessen: Spalte A prüfen (enthält `"SW XX"` nur bei echten Header-Zeilen).

- [ ] **Step 1: Aktuelle Zeile lokalisieren**

Öffne `scripts/build_excel_template.py`. Suche nach:
```python
is_header = not e_val and not h_val
```
Diese Zeile befindet sich in `style_terminplan()`, ca. Zeile 273.

- [ ] **Step 2: Fix implementieren**

Ersetze diese drei Zeilen:
```python
e_val = ws.cell(row=row_num, column=5).value   # Wochentag
h_val = ws.cell(row=row_num, column=8).value   # Titel
is_header = not e_val and not h_val
```

Durch:
```python
e_val = ws.cell(row=row_num, column=5).value   # Wochentag
h_val = ws.cell(row=row_num, column=8).value   # Titel
# Spalte A enthält "SW XX" nur bei echten Schulwochen-Header-Zeilen
# (gesetzt durch patch_xlsx.py). Leere Datenzeilen haben None in Spalte A.
a_val = ws.cell(row=row_num, column=1).value
is_header = bool(a_val and isinstance(a_val, str) and a_val.startswith('SW '))
```

- [ ] **Step 3: Skript ausführen**

```bash
cd "~/projects/wordpress-plugin-terminplaner"
python scripts/build_excel_template.py
```

Erwartete Ausgabe (kein Fehler):
```
Lade Vorlage: ...
Backup erstellt: ...
Erstelle Anleitung-Sheet ...
Style Terminplan-Sheet ...
...
OK - Vorlage gespeichert: ...
```

- [ ] **Step 4: Visuell prüfen**

Excel-Datei `website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx` öffnen.

Prüfen im Tab "Terminplan":
- ✅ Nur die Zeilen mit Schulwochen-Bezeichnung (z.B. "25.08. – 29.08.2025") haben lila/indigo Hintergrund
- ✅ Leere Datenzeilen darunter haben weißen oder hellgrauen Hintergrund (Zebrastreifen)
- ✅ Keine komplett lila Seite mehr

> **Abhängigkeit prüfen:** Spalte A (ausgeblendet) muss Strings wie `"SW 00"` … `"SW 40"` enthalten. Kurz prüfen: Spalten A–C einblenden, ein paar Header-Zeilen kontrollieren, danach wieder ausblenden. Wenn Spalte A leer ist, funktioniert Fix 1 nicht.

- [ ] **Step 5: Commit**

```bash
git add scripts/build_excel_template.py website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx
git commit -m "fix(excel): Terminplan-Hintergrund – Header-Erkennung auf Spalte-A-Check umstellen"
```

---

## Task 2: Fix – Zeilenhöhen in `make_anleitung()` (Text abgeschnitten)

**Files:**
- Modify: `scripts/build_excel_template.py` (Funktionen `add_blank`, `add_para`, `add_step_row`, `add_warn`, `add_table_row`, Kategorie-Schleife)

**Kontext:** Feste Zeilenhöhen (18–22px) kombiniert mit `wrap_text=True` schneiden langen Text ab. Lösung: `height = None` (Excel berechnet Höhe automatisch beim Öffnen). Leerzeilen bekommen Mindesthöhe 8 damit sie nicht auf 0 kollabieren.

- [ ] **Step 1: `add_blank()` anpassen**

Suche:
```python
def add_blank(n=1):
    nrow(n)
```

Ersetze durch:
```python
def add_blank(n=1):
    for _ in range(n):
        ws.row_dimensions[r[0]].height = 8
        nrow()
```

- [ ] **Step 2: `add_para()` anpassen**

Suche in `add_para()`:
```python
    ws.row_dimensions[r[0]-1].height = 18
```

Ersetze durch:
```python
    ws.row_dimensions[r[0]-1].height = None  # Excel-Autofit
```

Hinweis: `r[0]-1` ist korrekt – `cell()` schreibt in `r[0]`, danach zeigt `r[0]-1` auf genau diese Zeile. `nrow()` wird in `add_para()` nicht aufgerufen.

- [ ] **Step 3: `add_step_row()` anpassen**

Suche in `add_step_row()`:
```python
    ws.row_dimensions[r[0]].height = 22
    nrow()
```

Ersetze durch:
```python
    ws.row_dimensions[r[0]].height = None  # Excel-Autofit
    nrow()
```

- [ ] **Step 4: `add_warn()` anpassen**

Suche in `add_warn()`:
```python
    ws.row_dimensions[r[0]].height = 20
    nrow()
```

Ersetze durch:
```python
    ws.row_dimensions[r[0]].height = None  # Excel-Autofit
    nrow()
```

- [ ] **Step 5: `add_table_row()` anpassen**

Suche in `add_table_row()` (ca. Zeile 132) – erkennbar am `thin_border()`-Aufruf und `vertical='top'`:
```python
        c.border = thin_border()
    ws.row_dimensions[r[0]].height = 18
    nrow()
```

Ersetze (nur diese Stelle in `add_table_row`, nicht die Kategorie-Schleife weiter unten):
```python
        c.border = thin_border()
    ws.row_dimensions[r[0]].height = None  # Excel-Autofit
    nrow()
```

- [ ] **Step 6: Kategorie-Schleife anpassen**

Suche im `for cat, (bg, fg) in CAT_COLORS.items():`-Block am Ende von `make_anleitung()`:
```python
        ws.row_dimensions[r[0]].height = 18
        nrow()
```

Ersetze durch:
```python
        ws.row_dimensions[r[0]].height = None  # Excel-Autofit
        nrow()
```

- [ ] **Step 7: Skript ausführen**

```bash
python scripts/build_excel_template.py
```

Kein Fehler erwartet (gleiche Ausgabe wie Task 1, Step 3).

- [ ] **Step 8: Visuell prüfen**

Excel-Datei öffnen, Tab "Anleitung":
- ✅ Alle Absatz-Texte (`add_para`) vollständig lesbar
- ✅ Alle Schritt-Texte (`add_step_row`) vollständig lesbar, besonders lange Beschreibungen in Spalte C
- ✅ Hinweis-Zeilen (`add_warn`) vollständig sichtbar
- ✅ Tabellen-Zeilen ("Spalten des Terminplans") vollständig sichtbar
- ✅ Kategorie-Zeilen vollständig sichtbar
- ✅ Leerzeilen zwischen Sektionen vorhanden (nicht auf 0 kollabiert)
- ✅ Titel-Zeile und Section-Header weiterhin kompakt (feste Höhen 32/22px bleiben – intentional, diese Zeilen wrappen nie)

- [ ] **Step 9: Commit**

```bash
git add scripts/build_excel_template.py website/downloads/GSH_Terminplan_Schulwochen_Vorlage.xlsx
git commit -m "fix(excel): Anleitung-Sheet – Zeilenhöhen auf Excel-Autofit umstellen"
```

---

## Abschluss-Checkliste

- [ ] `python scripts/build_excel_template.py` läuft ohne Fehler durch
- [ ] Terminplan-Tab: nur SW-Header-Zeilen lila, Datenzeilen Zebrastreifen
- [ ] Anleitung-Tab: kein Text abgeschnitten
- [ ] Beide Fixes in separaten Commits
