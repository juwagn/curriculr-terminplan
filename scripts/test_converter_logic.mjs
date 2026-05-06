import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const html = fs.readFileSync('konverter/Terminplan_Konverter.html', 'utf8');
const js = html.match(/<script>\s*\/\/ ═+[\s\S]*?<\/script>/)?.[0]
  .replace(/^<script>/, '')
  .replace(/<\/script>$/, '');

if (!js) {
  throw new Error('Konverter-Script nicht gefunden');
}

function sliceByMarkers(start, end) {
  const a = js.indexOf(start);
  const b = js.indexOf(end);
  if (a === -1 || b === -1 || b <= a) {
    throw new Error(`Marker nicht gefunden: ${start} -> ${end}`);
  }
  return js.slice(a, b);
}

const code = [
  sliceByMarkers('const VERSION', '// ═══════════════════════════════════════════════════════════\n// ROW MANAGEMENT'),
  sliceByMarkers('// ═══════════════════════════════════════════════════════════\n// ICS GENERATION', '// ═══════════════════════════════════════════════════════════\n// EXCEL EXPORT'),
].join('\n');

function makeElement() {
  return {
    value: '',
    textContent: '',
    innerHTML: '',
    style: {},
    className: '',
    children: [],
    classList: { add() {}, remove() {}, toggle() {} },
    addEventListener() {},
    appendChild() {},
    querySelectorAll() { return []; },
  };
}

function loadContext() {
  const addedRows = [];
  const elements = new Map();
  let dateTick = 0;
  class StableTestDate extends Date {
    constructor(...args) {
      if (args.length) {
        super(...args);
      } else {
        super(Date.UTC(2026, 0, 1, 8, dateTick++, 0));
      }
    }
    static now() {
      return Date.UTC(2026, 0, 1, 8, dateTick++, 0);
    }
    static parse(value) { return Date.parse(value); }
    static UTC(...args) { return Date.UTC(...args); }
  }
  const context = {
    console,
    Date: StableTestDate,
    window: { addEventListener() {} },
    document: {
      head: makeElement(),
      createElement: makeElement,
      getElementById(id) {
        if (!elements.has(id)) elements.set(id, makeElement());
        return elements.get(id);
      },
      querySelectorAll() { return []; },
    },
    TextEncoder,
    clearAllSilent() { addedRows.length = 0; },
    addRow(row) { addedRows.push(row); },
    validateRows() {},
    updateStats() {},
    getValidRows() { return addedRows; },
    esc(value) { return String(value).replace(/"/g, '&quot;').replace(/</g, '&lt;'); },
    __addedRows: addedRows,
  };
  vm.createContext(context);
  vm.runInContext(code, context);
  return context;
}

function test(name, fn) {
  try {
    fn();
    console.log(`ok - ${name}`);
  } catch (error) {
    console.error(`not ok - ${name}`);
    console.error(error.stack || error.message);
    process.exitCode = 1;
  }
}

test('importiert neues SW-Key-Format auch wenn Ereigniszeilen SW-Key in Spalte A tragen', () => {
  const ctx = loadContext();
  const result = ctx.importSWFormat([
    ['SW-Key', 'Montag-ISO', 'SW', 'Schulwoche', 'Wochentag', 'Uhrzeit', 'Endzeit', 'Titel / Veranstaltung', 'Kategorie', 'Ganztaegig', 'Anmerkung'],
    ['SW 01', '2026-08-31', null, '31.08. - 04.09.2026', 'Mi', '09:00', '', 'Stehkaffee fuer alle LuL', 'Konferenzen/DB', 'Nein', ''],
  ], []);

  assert.equal(result.imported, 1);
  assert.equal(ctx.__addedRows.length, 1);
  assert.equal(ctx.__addedRows[0].start, '2026-09-02');
  assert.equal(ctx.__addedRows[0].title, 'Stehkaffee fuer alle LuL');
});

test('erhaelt ISO-Enddatum fuer mehrtaegige Ganze-Woche-Eintraege', () => {
  const ctx = loadContext();
  ctx.importSWFormat([
    ['SW-Key', 'Montag-ISO', 'SW', 'Schulwoche', 'Wochentag', 'Uhrzeit', 'Endzeit', 'Titel / Veranstaltung', 'Kategorie', 'Ganztaegig', 'Anmerkung'],
    ['SW 25', '2027-02-08', null, '08.02. - 12.02.2027', 'Ganze Woche', '', '2027-02-20', 'Abi Vorklausuren', 'Oberstufe', 'Ja', ''],
  ], []);

  assert.equal(ctx.__addedRows[0].start, '2027-02-08');
  assert.equal(ctx.__addedRows[0].end, '2027-02-20');
  assert.equal(ctx.__addedRows[0].endTime, '');
});

test('unbekannte Kategorien werden nicht still auf die letzte Kategorie gemappt', () => {
  const ctx = loadContext();
  assert.equal(ctx.matchCategory('Unzuordenbar'), '');
});

test('ICS-UID bleibt fuer denselben Termin zwischen zwei Exports stabil', () => {
  const ctx = loadContext();
  ctx.__addedRows.push({
    title: 'Lehrerkonferenz',
    start: '2026-09-01',
    startTime: '09:00',
    end: '',
    endTime: '10:00',
    cat: 'Konferenzen/DB',
    note: '',
    allday: false,
  });

  const first = ctx.buildICS().match(/^UID:(.+)$/m)?.[1];
  const second = ctx.buildICS().match(/^UID:(.+)$/m)?.[1];

  assert.ok(first);
  assert.equal(first, second);
});

test('gefaltete ICS-Zeilen ueberschreiten 75 UTF-8-Oktette je Segment nicht', () => {
  const ctx = loadContext();
  const folded = ctx.foldLine('SUMMARY:' + 'Ä'.repeat(60));
  for (const segment of folded.split('\r\n')) {
    const physical = segment.startsWith(' ') ? segment.slice(1) : segment;
    assert.ok(new TextEncoder().encode(physical).length <= 75, physical);
  }
});

test('importiert neue bearbeitbare SW00/SW01-Vorlage mit Formelverweisen in Spalte B', () => {
  const ctx = loadContext();
  const ferienRows = [
    ['Schuljahr 2026/27 - Ferien & Eckdaten'],
    ['Bezeichnung', 'Von (Datum)', 'Bis (Datum)', 'Hinweis'],
    ['Herbstferien', '2026-10-19', '2026-10-30', 'Pflichtfeld'],
    ['Weihnachtsferien', '2026-12-22', '2027-01-03', 'Pflichtfeld'],
    ['Osterferien', '2027-03-22', '2027-04-02', 'Pflichtfeld'],
    ['Pfingstferien', '', '', 'Optional leer lassen'],
    ['Sommerferien', '2027-07-19', '2027-08-31', 'Pflichtfeld'],
    [],
    ['Schuljahres-Eckdaten'],
    ['Erster Schultag (SW 00)', '2026-08-24', '', ''],
    ['Erster Unterrichtstag (SW 01)', '2026-09-07', '', ''],
    ['Letzter Schultag', '2027-07-16', '', ''],
  ];
  const result = ctx.importSWFormat([
    ['SW-Key', 'Montag-ISO', 'SW', 'Schulwoche', 'Wochentag', 'Uhrzeit', 'Endzeit', 'Titel / Veranstaltung', 'Kategorie', 'Ganztaegig', 'Anmerkung'],
    ['SW 00', '=Ferien!$F$15', 'SW 00', '=TEXT(B2,"TT.MM.")', '', '', '', '', '', '', ''],
    ['', '=Ferien!$F$15', 'SW 00', '', 'Mi', '09:00', '', 'Schulleitung', 'Konferenzen/DB', 'Nein', ''],
    ['SW 01', '=Ferien!$F$16', 'SW 01', '=TEXT(B18,"TT.MM.")', '', '', '', '', '', '', ''],
    ['', '=Ferien!$F$16', 'SW 01', '', 'Mo', '', '', 'FaKo-Tag', 'Konferenzen/DB', 'Ja', ''],
  ], ferienRows);

  assert.equal(result.imported, 6);
  assert.equal(ctx.__addedRows[0].start, '2026-08-26');
  assert.equal(ctx.__addedRows[0].title, 'Schulleitung');
  assert.equal(ctx.__addedRows[1].start, '2026-09-07');
  assert.equal(ctx.__addedRows[1].allday, true);
});

