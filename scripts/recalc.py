"""
recalc.py – Verifikation der dynamischen Datumsformeln (v2)

Prueft:
  1. Ferien-Blatt: Datumszellen sind echte Datumswerte (keine Textwerte)
  2. Ferien-Hilfstabelle F15:F55: alle 41 Formeln vorhanden und korrekt verkettet
  3. Terminplan Spalte B: Bezug auf Ferien!$F$15 .. $F$55 vorhanden
  4. Terminplan Spalte D: TEXT-Formel vorhanden
  5. Python-seitige Berechnung: alle 41 Schulmontage 2025/26 stimmen

Ausgabe: JSON mit status, details, errors

Ausfuehren: python scripts/recalc.py
"""

import json
import openpyxl
from datetime import date, timedelta

XLSX_PATH        = 'website/downloads/Terminplan_Schulwochen_Vorlage.xlsx'
HELPER_START_ROW = 15   # F15 = SW 00, ..., F55 = SW 40

# Bekannte Sollwerte fuer 2025/26
EXPECTED_2025_26 = {
    0:  date(2025,  8, 18),  1:  date(2025,  8, 25),
    2:  date(2025,  9,  1),  3:  date(2025,  9,  8),
    4:  date(2025,  9, 15),  5:  date(2025,  9, 22),
    6:  date(2025,  9, 29),  7:  date(2025, 10,  6),
    8:  date(2025, 10, 27),  9:  date(2025, 11,  3),
    10: date(2025, 11, 10), 11:  date(2025, 11, 17),
    12: date(2025, 11, 24), 13:  date(2025, 12,  1),
    14: date(2025, 12,  8), 15:  date(2025, 12, 15),
    16: date(2026,  1,  5), 17:  date(2026,  1, 12),
    18: date(2026,  1, 19), 19:  date(2026,  1, 26),
    20: date(2026,  2,  2), 21:  date(2026,  2,  9),
    22: date(2026,  2, 16), 23:  date(2026,  2, 23),
    24: date(2026,  3,  2), 25:  date(2026,  3,  9),
    26: date(2026,  3, 16), 27:  date(2026,  3, 23),
    28: date(2026,  4, 13), 29:  date(2026,  4, 20),
    30: date(2026,  4, 27), 31:  date(2026,  5,  4),
    32: date(2026,  5, 11), 33:  date(2026,  5, 18),
    34: date(2026,  6,  1), 35:  date(2026,  6,  8),
    36: date(2026,  6, 15), 37:  date(2026,  6, 22),
    38: date(2026,  6, 29), 39:  date(2026,  7,  6),
    40: date(2026,  7, 13),
}


def read_date(cell):
    """Gibt date-Objekt oder None zurueck."""
    from datetime import datetime as dt
    v = cell.value
    if isinstance(v, dt):
        return v.date()
    if isinstance(v, date):
        return v
    return None


def compute_school_mondays(first_day, ferien_periods, count=41):
    """
    Repliziert die Excel-Hilfstabellenlogik in Python.
    ferien_periods: Liste von (von, bis) als date-Objekte; None-Paare werden ignoriert.
    """
    mondays = []
    current = first_day
    limit   = 0
    while len(mondays) < count and limit < 200:
        limit += 1
        in_holiday = any(
            von and bis and von <= current <= bis
            for von, bis in ferien_periods
        )
        if not in_holiday:
            mondays.append(current)
        current += timedelta(weeks=1)
    return mondays


# ---------------------------------------------------------------------------
errors  = []
details = []

print(f'Lade: {XLSX_PATH}')
wb   = openpyxl.load_workbook(XLSX_PATH, data_only=False)
ws_f = wb['Ferien']
ws_t = wb['Terminplan']


# === 1. Ferien-Blatt: Datumszellen ===
ferien_check = {
    'B3': 'Herbstferien Von',   'C3': 'Herbstferien Bis',
    'B4': 'Weihnachtsferien Von','C4': 'Weihnachtsferien Bis',
    'B5': 'Osterferien Von',    'C5': 'Osterferien Bis',
    'B10': 'Erster Schultag',
}
ferien_dates = {}
for addr, label in ferien_check.items():
    d = read_date(ws_f[addr])
    if d is None:
        errors.append(f'Ferien!{addr} ({label}): kein Datumswert (Wert={repr(ws_f[addr].value)})')
    else:
        ferien_dates[addr] = d
        details.append(f'Ferien!{addr} ({label}): {d} OK')

# Pfingstferien optional
pf_von = read_date(ws_f['B6'])
pf_bis = read_date(ws_f['C6'])
ferien_dates['B6'] = pf_von
ferien_dates['C6'] = pf_bis
if pf_von is None:
    details.append('Ferien!B6 (Pfingstferien): leer -> wird ignoriert OK')
else:
    details.append(f'Ferien!B6/C6 (Pfingstferien): {pf_von} - {pf_bis} OK')

# A1: Schuljahr-Formel
a1 = ws_f['A1'].value
if isinstance(a1, str) and a1.startswith('=') and 'YEAR' in a1:
    details.append('Ferien!A1: Schuljahr-Formel vorhanden OK')
else:
    errors.append(f'Ferien!A1: Schuljahr-Formel fehlt (Wert={repr(a1)})')


# === 2. Ferien-Hilfstabelle F15:F55 ===
helper_errors = []
for sw_num in range(41):
    row      = HELPER_START_ROW + sw_num
    cell_val = ws_f.cell(row, 6).value  # Spalte F
    if not isinstance(cell_val, str) or not cell_val.startswith('='):
        helper_errors.append(f'Ferien!F{row} (SW {sw_num:02d}): keine Formel')
    elif sw_num == 0 and '$B$10' not in cell_val:
        helper_errors.append(f'Ferien!F{row} (SW 00): $B$10 fehlt')
    elif sw_num > 0 and f'$F${row - 1}' not in cell_val:
        helper_errors.append(
            f'Ferien!F{row} (SW {sw_num:02d}): Verkettung $F${row-1} fehlt'
        )

if helper_errors:
    errors.extend(helper_errors)
else:
    details.append('Ferien-Hilfstabelle F15:F55: alle 41 Formeln korrekt verkettet OK')


# === 3. Terminplan: SW-Header-Zeilen ===
sw_header_rows = []
for row in range(2, ws_t.max_row + 1):
    val_a = ws_t.cell(row, 1).value
    if val_a and isinstance(val_a, str) and val_a.startswith('SW ') and val_a != 'SW-Key':
        try:
            sw_num = int(val_a.split()[1])
            sw_header_rows.append((sw_num, row))
        except (IndexError, ValueError):
            pass

if len(sw_header_rows) != 41:
    errors.append(f'Terminplan: erwartet 41 SW-Zeilen, gefunden {len(sw_header_rows)}')
else:
    details.append('Terminplan: 41 SW-Header-Zeilen gefunden OK')


# === 4. Formelstruktur B + D ===
formula_errors = []
for sw_num, row in sw_header_rows:
    b_val = ws_t.cell(row, 2).value
    d_val = ws_t.cell(row, 4).value
    expected_ref = f'Ferien!$F${HELPER_START_ROW + sw_num}'

    if not isinstance(b_val, str) or not b_val.startswith('='):
        formula_errors.append(f'SW {sw_num:02d} B{row}: keine Formel')
    elif expected_ref not in b_val:
        formula_errors.append(
            f'SW {sw_num:02d} B{row}: Bezug {expected_ref} fehlt (gefunden: {repr(b_val)})'
        )

    if not isinstance(d_val, str) or not d_val.startswith('='):
        formula_errors.append(f'SW {sw_num:02d} D{row}: keine Formel')
    elif 'TEXT' not in d_val:
        formula_errors.append(f'SW {sw_num:02d} D{row}: TEXT fehlt')

if formula_errors:
    errors.extend(formula_errors)
else:
    details.append('Formelstruktur B+D: alle 82 Formeln korrekt OK')


# === 5. Python-Berechnung: Sollwerte 2025/26 ===
if ferien_dates.get('B10'):
    ferien_periods = [
        (ferien_dates.get('B3'), ferien_dates.get('C3')),
        (ferien_dates.get('B4'), ferien_dates.get('C4')),
        (ferien_dates.get('B5'), ferien_dates.get('C5')),
        (ferien_dates.get('B6'), ferien_dates.get('C6')),
    ]
    computed = compute_school_mondays(ferien_dates['B10'], ferien_periods, count=41)

    mismatches = []
    for sw_num, expected in EXPECTED_2025_26.items():
        if sw_num < len(computed):
            got = computed[sw_num]
            if got != expected:
                mismatches.append(f'SW {sw_num:02d}: erwartet {expected}, berechnet {got}')
        else:
            mismatches.append(f'SW {sw_num:02d}: nicht berechnet')

    if mismatches:
        errors.extend(mismatches)
    else:
        details.append(
            f'Python-Berechnung 2025/26: alle 41 Schulmontage stimmen '
            f'({ferien_dates["B10"]} bis {computed[-1]}) OK'
        )

    details.append('Spotchecks (Schulmontage Ferien-Uebergaenge):')
    for i in [0, 7, 8, 15, 16, 27, 28, 33, 34, 40]:
        if i < len(computed):
            details.append(f'  SW {i:02d}: {computed[i]}')


# ---------------------------------------------------------------------------
status = 'success' if not errors else 'error'
result = {
    'status':              status,
    'sw_header_rows_found': len(sw_header_rows),
    'errors':              errors,
    'details':             details,
}
print(json.dumps(result, ensure_ascii=True, indent=2))
