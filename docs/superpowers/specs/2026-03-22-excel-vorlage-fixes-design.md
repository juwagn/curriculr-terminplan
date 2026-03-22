# Design: Excel-Vorlage Fixes – Zeilenhöhen & Terminplan-Farbe

**Datum:** 2026-03-22
**Datei:** `scripts/build_excel_template.py`
**Ansatz:** B – Bug-Fixes + kleine Lesbarkeits-Verbesserungen

---

## Probleme

### 1. Terminplan – kompletter lila Hintergrund
**Ursache:** Die Header-Erkennung in `style_terminplan()` prüft ob Spalten E und H leer sind:
```python
is_header = not e_val and not h_val
```
Da leere Datenzeilen (noch kein Termin eingetragen) ebenfalls leere Spalten E und H haben, werden sie fälschlich als SW-Header-Zeilen erkannt und lila eingefärbt.

### 2. Anleitung-Sheet – Text nicht vollständig lesbar
**Ursache:** `add_para()`, `add_step_row()` und die Kategorie-Zeilen setzen feste Zeilenhöhen (18–22px), obwohl `wrap_text=True` aktiv ist. Längerer Text wird abgeschnitten.

---

## Lösung

### Fix 1 – Header-Erkennung (Zeile 273)

```python
# Vorher:
is_header = not e_val and not h_val

# Nachher:
a_val = ws.cell(row=row_num, column=1).value
is_header = bool(a_val and isinstance(a_val, str) and a_val.startswith('SW '))
```

Spalte A enthält bei echten SW-Header-Zeilen immer `"SW XX"` (wird von `patch_xlsx.py` gesetzt). Leere Datenzeilen haben `None` → werden korrekt als Datenzeilen erkannt und bekommen Zebrastreifen (`C_ZEBRA` / `C_WHITE`).

> **Abhängigkeit:** Die Logik setzt voraus, dass Spalte A der Quelldatei die Strings `"SW 00"` … `"SW 40"` enthält. Bei einem Vorlagenwechsel muss dieser Bezug geprüft werden.

### Fix 2 – Zeilenhöhen in `make_anleitung()`

Alle Stellen mit `wrap_text=True` und fester Höhe werden auf `None` (Excel-Autofit) umgestellt:

| Funktion | Bisher | Nachher | Hinweis |
|---|---|---|---|
| `add_para()` | `height = 18` | `height = None` | Row-Index ist `r[0]-1`: `cell()` schreibt in `r[0]`, danach zeigt `r[0]-1` auf genau diese Zeile. `nrow()` wird in `add_para()` nicht aufgerufen – Index muss so bleiben. |
| `add_step_row()` | `height = 22` | `height = None` | Row-Index ist `r[0]` vor `nrow()` |
| `add_warn()` | `height = 20` | `height = None` | Lange Texte, wrappen ebenfalls |
| `add_table_row()` | `height = 18` | `height = None` | Spalte C (Beschreibung) kann wrappen |
| Kategorie-Schleife | `height = 18` | `height = None` | Ende von `make_anleitung()` |

Feste Höhen bleiben bei einzeiligen Elementen: Titel (32), Section-Header (22), Tabellen-Header (18), Footer – diese wrappen nie.

### Fix 3 – Leerzeilen stabilisieren

`add_blank()` bekommt eine Mindesthöhe von 8, damit Leerzeilen beim Excel-Autofit nicht auf 0 kollabieren:

```python
def add_blank(n=1):
    for _ in range(n):
        ws.row_dimensions[r[0]].height = 8
        nrow()
```

---

## Betroffene Dateien

- `scripts/build_excel_template.py` – alle Änderungen hier
- `website/downloads/Terminplan_Schulwochen_Vorlage.xlsx` – wird durch das Skript neu generiert

## Nicht berührt

- `scripts/patch_xlsx.py`
- `scripts/recalc.py`
- Plugin-Dateien (`gsh-terminplan.php`, CSS)

---

## Ausführung nach Implementierung

```bash
python scripts/build_excel_template.py
```
Dann Excel-Datei öffnen und prüfen:
- Anleitung: alle Texte vollständig lesbar, keine abgeschnittenen Zeilen
- Terminplan: nur SW-Header-Zeilen lila, Datenzeilen Zebrastreifen
