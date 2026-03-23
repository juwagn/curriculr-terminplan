# -*- coding: utf-8 -*-
"""
build_excel_template.py
Ueberarbeitet die GSH-Schulwochen-Vorlage:
- Neues "Anleitung"-Sheet (Tab 0)
- Technische Spalten A-C ausblenden
- Dropdowns fuer Wochentag, Kategorie, Ganztaegig
- Styling der SW-Header-Zeilen und Einfrieren
- Kategorien-Sheet mit Named Range
"""

import shutil
from pathlib import Path
from openpyxl import load_workbook
from openpyxl.styles import Font, PatternFill, Alignment, Border, Side
from openpyxl.worksheet.datavalidation import DataValidation
from openpyxl.workbook.defined_name import DefinedName

BASE = Path(__file__).resolve().parent.parent
SRC  = BASE / 'website' / 'downloads' / 'GSH_Terminplan_Schulwochen_Vorlage.xlsx'
OUT  = BASE / 'website' / 'downloads' / 'GSH_Terminplan_Schulwochen_Vorlage.xlsx'
BAK  = BASE / 'website' / 'downloads' / 'GSH_Terminplan_Schulwochen_Vorlage.bak.xlsx'

# Farben
C_INDIGO     = '4F46E5'
C_HEADER_BG  = '1E3A5F'
C_WHITE      = 'FFFFFF'
C_ZEBRA      = 'F8FAFC'
C_GREEN_TAB  = '70AD47'
C_BLUE_TAB   = '2F5496'

# Kategorie-Farben (bg, fg) ohne #
CAT_COLORS = {
    'Jahrgang 5/6':     ('FADBD8', '7B241C'),
    'Jahrgang 7/8':     ('D5F5E3', '1E8449'),
    'Jahrgang 9/10':    ('FDE8D3', '784212'),
    'Oberstufe':        ('D4E6F1', '1A5276'),
    'Inklusion':        ('D1F2EB', '148F77'),
    'Feiertage/Ferien': ('FDEBD0', '784212'),
    'Konferenzen/DB':   ('E8DAEF', '6C3483'),
}

CAT_EXAMPLES = {
    'Jahrgang 5/6':     'Einschulung, Sprachstandstest, Neue 5er',
    'Jahrgang 7/8':     'Potenzialanalyse, WP-Wahl, KAoA',
    'Jahrgang 9/10':    'Betriebspraktikum, ZP 10, Abschlussfahrt',
    'Oberstufe':        'Abitur, EF/Q1/Q2 Termine',
    'Inklusion':        'IFOe, AL SuS, Inklusionsteam',
    'Feiertage/Ferien': 'Ferien, Feiertage, schulfreie Tage',
    'Konferenzen/DB':   'Lehrerkonferenz, FaKo, Teamgespraeche',
}


def thin_border():
    s = Side(style='thin', color='D0D5DD')
    return Border(left=s, right=s, top=s, bottom=s)


def h_fill(hex_color):
    return PatternFill('solid', fgColor=hex_color)


# ---------------------------------------------------------------------------
# Anleitung-Sheet
# ---------------------------------------------------------------------------
def make_anleitung(wb):
    ws = wb.create_sheet('Anleitung', 0)
    ws.sheet_properties.tabColor = C_GREEN_TAB
    ws.sheet_view.showGridLines = False
    ws.column_dimensions['A'].width = 3
    ws.column_dimensions['B'].width = 35
    ws.column_dimensions['C'].width = 58
    ws.column_dimensions['D'].width = 3

    r = [1]  # mutable row counter

    def nrow(n=1):
        r[0] += n

    def cell(col, value='', bold=False, size=11, color='111827',
             bg=None, wrap=False, indent=0, italic=False, align='left'):
        c = ws.cell(row=r[0], column=col, value=value)
        c.font = Font(name='Calibri', size=size, bold=bold,
                      color=color, italic=italic)
        c.alignment = Alignment(horizontal=align, vertical='center',
                                wrap_text=wrap, indent=indent)
        if bg:
            c.fill = PatternFill('solid', fgColor=bg)
        return c

    def add_blank(n=1):
        for _ in range(n):
            ws.row_dimensions[r[0]].height = 8
            nrow()

    def add_title(text):
        cell(2, text, bold=True, size=18, color='1E3A5F')
        ws.row_dimensions[r[0]].height = 32
        nrow()

    def add_section(text):
        cell(2, text, bold=True, size=12, color='4F46E5')
        ws.row_dimensions[r[0]].height = 22
        nrow()

    def add_para(text):
        c = cell(2, text, size=11, color='374151', wrap=True)
        ws.merge_cells(start_row=r[0]-1, start_column=2,
                       end_row=r[0]-1, end_column=3)
        ws.row_dimensions[r[0]-1].height = None  # Excel-Autofit

    def add_step_row(num, title, detail):
        num_c = ws.cell(row=r[0], column=2, value='  ' + str(num) + '.')
        num_c.font = Font(name='Calibri', size=11, bold=True, color='4F46E5')
        num_c.fill = PatternFill('solid', fgColor='EEF2FF')
        num_c.alignment = Alignment(horizontal='left', vertical='center')
        txt = title + ' - ' + detail if detail else title
        txt_c = ws.cell(row=r[0], column=3, value=txt)
        txt_c.font = Font(name='Calibri', size=11, color='374151')
        txt_c.fill = PatternFill('solid', fgColor='EEF2FF')
        txt_c.alignment = Alignment(vertical='center', wrap_text=True)
        ws.row_dimensions[r[0]].height = None  # Excel-Autofit
        nrow()

    def add_table_row(v1, v2, v3, is_header=False):
        bg = C_HEADER_BG if is_header else (C_ZEBRA if r[0] % 2 == 0 else C_WHITE)
        fc = 'FFFFFF' if is_header else '374151'
        for col, val in [(2, v1), (3, v2)]:
            c = ws.cell(row=r[0], column=col, value=val)
            c.font = Font(name='Calibri', size=10, bold=is_header, color=fc)
            c.fill = PatternFill('solid', fgColor=bg)
            c.alignment = Alignment(vertical='top', wrap_text=True)
            c.border = thin_border()
        ws.row_dimensions[r[0]].height = None  # Excel-Autofit
        nrow()

    def add_warn(text):
        c = ws.cell(row=r[0], column=2, value=text)
        c.font = Font(name='Calibri', size=11, color='374151')
        c.alignment = Alignment(vertical='center', wrap_text=True)
        ws.merge_cells(start_row=r[0], start_column=2,
                       end_row=r[0], end_column=3)
        ws.row_dimensions[r[0]].height = None  # Excel-Autofit
        nrow()

    # ---- Inhalt ----
    add_blank()
    add_title('GSH Schuljahreskalender - Anleitung')
    add_blank()

    add_section('Was ist das?')
    add_para('Diese Excel-Datei ist der Schuljahreskalender der Gesamtschule Horst.')
    add_para('Hier traegst du alle Termine ein. Danach konvertierst du die Datei mit')
    add_para('dem Konverter-Tool zu einer ICS-Datei und importierst sie in IServ.')
    add_blank()

    add_section('Gesamter Ablauf (einmal pro Schuljahr)')
    add_step_row(1, 'Ferien eintragen',
                 'Tab "Ferien" oeffnen und Ferientermine fuer das neue Schuljahr eintragen.')
    add_step_row(2, 'Termine eintragen',
                 'Tab "Terminplan" oeffnen und alle Schultermine eintragen.')
    add_step_row(3, 'Konverter oeffnen',
                 'Datei GSH_Terminplan_Konverter.html im Browser oeffnen (Chrome empfohlen).')
    add_step_row(4, 'Excel importieren',
                 'Diese Excel-Datei in das Konverter-Tool ziehen.')
    add_step_row(5, 'ICS herunterladen',
                 'Auf "ICS herunterladen" klicken. Datei wird im Download-Ordner gespeichert.')
    add_step_row(6, 'ICS in IServ importieren',
                 'In IServ -> Kalender -> Einstellungen -> Importieren -> ICS-Datei auswaehlen.')
    add_blank()

    add_section('So traegst du Termine ein (Tab "Terminplan")')
    add_step_row(1, 'Schulwoche finden',
                 'Spalte D zeigt die Woche (z.B. "25.08. - 29.08.2025"). Richtige Woche suchen.')
    add_step_row(2, 'Wochentag waehlen',
                 'In Spalte E klicken - Dropdown erscheint (Mo / Di / Mi / Do / Fr / Ganze Woche).')
    add_step_row(3, 'Uhrzeit eintragen',
                 'Start- und Endzeit in Spalte F/G eintragen (Format: 08:30). Leer = ganztaegig.')
    add_step_row(4, 'Titel eintragen',
                 'Bezeichnung des Termins in Spalte H eintragen (Pflichtfeld).')
    add_step_row(5, 'Kategorie waehlen',
                 'In Spalte I klicken - Dropdown mit allen Kategorien erscheint.')
    add_step_row(6, 'Ganztaegig setzen',
                 'In Spalte J "Ja" waehlen, wenn der Termin den ganzen Tag dauert.')
    add_blank()

    add_section('Spalten des Terminplans')
    add_table_row('Spalte', 'Bedeutung und Beispiel', '', is_header=True)
    add_table_row('D  Schulwoche',
                  'Wird automatisch berechnet - NICHT bearbeiten! (z.B. "25.08. - 29.08.2025")', '')
    add_table_row('E  Wochentag',
                  'Dropdown: Mo / Di / Mi / Do / Fr / Ganze Woche', '')
    add_table_row('F  Uhrzeit',
                  'Startzeit (Format: 09:30). Leer lassen wenn ganztaegig.', '')
    add_table_row('G  Endzeit',
                  'Endzeit (Format: 11:00). Optional.', '')
    add_table_row('H  Titel',
                  'Bezeichnung des Termins - Pflichtfeld! (z.B. "Lehrerkonferenz")', '')
    add_table_row('I  Kategorie',
                  'Dropdown: eine der 7 Kategorien auswaehlen', '')
    add_table_row('J  Ganztaegig',
                  'Dropdown: "Ja" wenn kein fester Zeitraum', '')
    add_table_row('K  Anmerkung',
                  'Optionaler Hinweistext (z.B. "Bitte alle Lehrkraefte")', '')
    add_blank()

    add_section('Wichtige Hinweise')
    add_warn('>> Graue Header-Zeilen markieren den Wochenbeginn - dort NICHTS eintragen!')
    add_warn('>> Spalten A, B, C sind ausgeblendet (technische Daten) - nicht einblenden.')
    add_warn('>> Spalte D wird automatisch berechnet - nicht bearbeiten.')
    add_warn('>> Ferientermine zuerst im Tab "Ferien" eintragen - danach passen sich alle Daten an.')
    add_warn('>> Aenderungen im laufenden Schuljahr: direkt im IServ-Kalender bearbeiten.')
    add_blank()

    add_section('Verfuegbare Kategorien')
    for cat, (bg, fg) in CAT_COLORS.items():
        ex = CAT_EXAMPLES.get(cat, '')
        c2 = ws.cell(row=r[0], column=2, value=cat)
        c2.font = Font(name='Calibri', size=10, bold=True, color=fg)
        c2.fill = PatternFill('solid', fgColor=bg)
        c2.border = thin_border()
        c3 = ws.cell(row=r[0], column=3, value=ex)
        c3.font = Font(name='Calibri', size=10, color='374151')
        c3.fill = PatternFill('solid', fgColor='FFFFFF')
        c3.border = thin_border()
        ws.row_dimensions[r[0]].height = None  # Excel-Autofit
        nrow()

    add_blank(2)
    footer = ws.cell(row=r[0], column=2,
                     value='Bei Fragen: IT-Beauftragter der Gesamtschule Horst')
    footer.font = Font(name='Calibri', size=10, italic=True, color='9CA3AF')
    ws.merge_cells(start_row=r[0], start_column=2,
                   end_row=r[0], end_column=3)

    return ws


# ---------------------------------------------------------------------------
# Terminplan-Sheet
# ---------------------------------------------------------------------------
def style_terminplan(wb):
    ws = wb['Terminplan']
    ws.sheet_properties.tabColor = C_BLUE_TAB
    max_row = ws.max_row

    # Spalten A-C ausblenden
    for col_letter in ('A', 'B', 'C'):
        ws.column_dimensions[col_letter].hidden = True

    # Spaltenbreiten sichtbarer Spalten
    for col, w in [('D', 24), ('E', 14), ('F', 9), ('G', 9),
                   ('H', 40), ('I', 18), ('J', 10), ('K', 28)]:
        ws.column_dimensions[col].width = w

    # Kopfzeile Zeile 1
    labels = {4: 'Schulwoche', 5: 'Wochentag', 6: 'Uhrzeit',
              7: 'Endzeit', 8: 'Titel / Veranstaltung',
              9: 'Kategorie', 10: 'Ganztaegig', 11: 'Anmerkung'}
    for col_idx, label in labels.items():
        c = ws.cell(row=1, column=col_idx, value=label)
        c.font = Font(name='Calibri', size=11, bold=True, color='FFFFFF')
        c.fill = h_fill(C_HEADER_BG)
        c.alignment = Alignment(horizontal='center', vertical='center')
        c.border = thin_border()
    ws.row_dimensions[1].height = 22

    # Einfrieren bei E2 (Zeile 1 + Spalten A-D fixiert)
    ws.freeze_panes = 'E2'

    # Zeilen stylen
    for row_num in range(2, max_row + 1):
        # Spalte A enthält "SW XX" nur bei echten Schulwochen-Header-Zeilen
        # (gesetzt durch patch_xlsx.py). Leere Datenzeilen haben None in Spalte A.
        a_val = ws.cell(row=row_num, column=1).value
        is_header = bool(a_val and isinstance(a_val, str) and a_val.startswith('SW '))

        for col in range(4, 12):
            c = ws.cell(row=row_num, column=col)
            if is_header:
                c.font = Font(name='Calibri', size=10,
                              bold=(col == 4), color='FFFFFF')
                c.fill = h_fill(C_INDIGO)
                c.alignment = Alignment(horizontal='left', vertical='center',
                                        indent=(1 if col == 4 else 0))
            else:
                bg = C_ZEBRA if row_num % 2 == 0 else C_WHITE
                c.fill = h_fill(bg)
                c.font = Font(name='Calibri', size=11, color='111827')
                c.alignment = Alignment(vertical='center',
                                        wrap_text=(col == 8))
            c.border = thin_border()
        ws.row_dimensions[row_num].height = 18

    # Dropdown Wochentag (E)
    dv_wt = DataValidation(
        type='list',
        formula1='"Mo,Di,Mi,Do,Fr,Ganze Woche"',
        allow_blank=True,
        showErrorMessage=True,
        error='Bitte Wochentag aus Dropdown waehlen.',
        errorTitle='Ungueltige Eingabe',
    )
    dv_wt.sqref = 'E2:E' + str(max_row)
    ws.add_data_validation(dv_wt)

    # Dropdown Ganztaegig (J)
    dv_gt = DataValidation(
        type='list', formula1='"Ja,Nein"',
        allow_blank=True, showErrorMessage=False,
    )
    dv_gt.sqref = 'J2:J' + str(max_row)
    ws.add_data_validation(dv_gt)

    # Dropdown Kategorie (I) - referenziert Named Range
    dv_cat = DataValidation(
        type='list', formula1='KategorienListe',
        allow_blank=True, showErrorMessage=False,
    )
    dv_cat.sqref = 'I2:I' + str(max_row)
    ws.add_data_validation(dv_cat)

    return ws


# ---------------------------------------------------------------------------
# Ferien-Sheet
# ---------------------------------------------------------------------------
def style_ferien(wb):
    ws = wb['Ferien']
    ws.sheet_properties.tabColor = 'ED7D31'

    for col, w in [('A', 26), ('B', 16), ('C', 16), ('D', 30)]:
        ws.column_dimensions[col].width = w

    # Zeile 2 = Spaltenkopf
    for col in range(1, 5):
        c = ws.cell(row=2, column=col)
        c.font = Font(name='Calibri', size=11, bold=True, color='FFFFFF')
        c.fill = h_fill(C_HEADER_BG)
        c.alignment = Alignment(horizontal='center', vertical='center')
        c.border = thin_border()
    ws.row_dimensions[2].height = 20

    # Datenzeilen 3-7
    for row_num in range(3, 8):
        bg = C_ZEBRA if row_num % 2 == 0 else C_WHITE
        for col in range(1, 5):
            c = ws.cell(row=row_num, column=col)
            if c.value is None:
                continue
            c.font = Font(name='Calibri', size=11, color='111827')
            c.fill = h_fill(bg)
            c.alignment = Alignment(vertical='center')
            c.border = thin_border()
        ws.row_dimensions[row_num].height = 18

    # Hinweis-Zeile
    c = ws.cell(row=9, column=1,
                value='Schuljahres-Eckdaten (automatisch berechnet - nicht aendern)')
    c.font = Font(name='Calibri', size=10, italic=True, color='4F46E5')

    return ws


# ---------------------------------------------------------------------------
# Kategorien-Sheet
# ---------------------------------------------------------------------------
def style_kategorien(wb):
    ws = wb['Kategorien']
    ws.sheet_properties.tabColor = '7030A0'

    for col, w in [('A', 22), ('B', 14), ('C', 55)]:
        ws.column_dimensions[col].width = w

    # Zeile 1: Titel
    c1 = ws.cell(row=1, column=1)
    c1.font = Font(name='Calibri', size=13, bold=True, color='1E3A5F')
    ws.row_dimensions[1].height = 22

    # Zeile 2: Header
    for col in range(1, 4):
        c = ws.cell(row=2, column=col)
        c.font = Font(name='Calibri', size=11, bold=True, color='FFFFFF')
        c.fill = h_fill(C_HEADER_BG)
        c.alignment = Alignment(horizontal='center', vertical='center')
        c.border = thin_border()
    ws.row_dimensions[2].height = 20

    # Kategoriezeilen (Zeile 3+)
    cat_last_row = 2
    for row_num in range(3, ws.max_row + 1):
        cat_name = ws.cell(row=row_num, column=1).value
        if not cat_name:
            continue
        cat_last_row = row_num
        bg, fg = CAT_COLORS.get(str(cat_name), ('F3F4F6', '111827'))
        for col in range(1, 4):
            c = ws.cell(row=row_num, column=col)
            c.fill = h_fill(bg)
            c.font = Font(name='Calibri', size=11, bold=(col == 1), color=fg)
            c.alignment = Alignment(vertical='center', wrap_text=(col == 3))
            c.border = thin_border()
        ws.row_dimensions[row_num].height = 18

    # Named Range fuer Kategorie-Dropdown im Terminplan
    dn = DefinedName('KategorienListe',
                     attr_text='Kategorien!$A$3:$A$' + str(cat_last_row))
    wb.defined_names['KategorienListe'] = dn

    return ws


# ---------------------------------------------------------------------------
def main():
    print('Lade Vorlage:', SRC)
    assert SRC.exists(), 'Quelldatei nicht gefunden: ' + str(SRC)

    shutil.copy(SRC, BAK)
    print('Backup erstellt:', BAK)

    wb = load_workbook(str(SRC))  # data_only=False -> Formeln bleiben erhalten

    print('Erstelle Anleitung-Sheet ...')
    make_anleitung(wb)

    print('Style Terminplan-Sheet ...')
    style_terminplan(wb)

    print('Style Ferien-Sheet ...')
    style_ferien(wb)

    print('Style Kategorien-Sheet + Named Range ...')
    style_kategorien(wb)

    wb.save(str(OUT))
    print('\n OK - Vorlage gespeichert:', OUT)
    print('Bitte in Excel oeffnen und pruefen:')
    print('  - Tab "Anleitung" ist das erste Tab (gruen)')
    print('  - Terminplan: Spalten A-C ausgeblendet, Dropdowns in E/I/J')
    print('  - Ferien: Header-Zeile farbig')
    print('  - Kategorien: farbige Zeilen')


if __name__ == '__main__':
    main()
