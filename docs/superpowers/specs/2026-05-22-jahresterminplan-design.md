# Jahresterminplan — Design-Spec

**Datum:** 2026-05-22  
**Status:** Genehmigt  
**Version:** Plugin 4.1.x → 4.2.0

---

## Überblick

Neues Feature: Jahresansicht im bestehenden Terminplan-Shortcode. Klassisches Jahresraster (Monate als Spalten, Tage als Zeilen) neben bestehender Quartalsansicht. Umschaltung per Button. Zielgruppe: Kollegium möchte gesamtes Schuljahr auf einen Blick sehen.

---

## Anforderungen

- Jahresraster zeigt Schuljahr Sep–Jul (10 Monate)
- Events erkennbar: farbige Chips mit Kurztext, Hover-Tooltip mit Details
- Kategorie-Filter der Quartalsansicht gelten auch für Jahresraster
- Umschaltknopf zwischen Quartals- und Jahresansicht (kein neuer Shortcode)
- Bestehende geschützte Bereiche (Parser, Date-Index, Tabellen-Rendering) bleiben unverändert
- Kein AJAX, kein Build-System — vollständig serverseitig gerendert
- Alles CSS in `gsh-terminplan.css`, kein Inline-CSS

---

## Architektur

### Rendering-Strategie

Beide Ansichten werden beim Seitenaufruf serverseitig gerendert und liegen im DOM. CSS-Klasse am Wrapper steuert Sichtbarkeit — kein Nachladen.

```
[gsh_tp_shortcode]
  └─ .gtp-wrap[data-view="quartal"|"year"]
       ├─ .gtp-quarters-wrap  ← bestehend (Q1–Q4 Tabs + Tabellen)
       └─ .gtp-year-wrap      ← neu (Jahresraster)
```

### Datenpfad

`gsh_tp_fetch_ical()` → `gsh_tp_build_date_index()` → `$index` → `gsh_tp_day_events($index, $date)` (bereits vorhanden) → `gsh_tp_yearview()` nutzt `$index` direkt, kein eigener Fetch.

---

## PHP

### Neue Funktion: `gsh_tp_yearview($index, $quartale, $pid)`

**Position:** Sektion 5 (Shortcode-Bereich), nach bestehenden Render-Funktionen.

**Logik:**
1. Schuljahresbeginn aus `$quartale[0]['start']` ableiten → erster September des Schuljahres
2. Monatsspalten-Array aufbauen: Sep, Okt, Nov, Dez, Jan, Feb, Mär, Apr, Mai, Jun, Jul (10 Monate)
3. Tabelle rendern: Kopfzeile mit Monatsnamen, Zeilen 1–31
4. Pro Zelle: Datum validieren mit `checkdate()` — ungültige Daten (z.B. 31. Feb) erhalten Klasse `gtp-yr-invalid`
5. Gültige Zellen: `gsh_tp_day_events($index, $date_str)` aufrufen, Events als `<span class="gtp-yr-ev">` rendern
6. Event-Attribute via `gsh_tp_event_data_attrs()` (bereits vorhanden): `data-c`, `data-summary`, `data-date`, `data-allday`
7. Kurztext: erste 15 Zeichen von `data-summary` (PHP `mb_substr`)
8. Aktuelle Tagzeile: CSS-Klasse `gtp-yr-today` wenn `$row_day == gmdate('j') && $col_month == gmdate('n')`

**Rückgabe:** `<div class="gtp-year-wrap"><table class="gtp-yr">…</table></div>`

### Änderung: `gsh_tp_shortcode()`

- Bestehenden Quartals-Render-Output in `<div class="gtp-quarters-wrap">` einwickeln + `gsh_tp_yearview()` daneben einfügen
- Wrapper `.gtp-wrap` erhält `data-view="quartal"` als Default
- Toggle-Button im Header-Bereich einfügen (nach Schuljahr-Switcher):

```html
<button class="gtp-view-btn" onclick="gtpViewToggle(this)"
        data-label-quartal="Quartalsansicht"
        data-label-year="Jahresansicht">
    Jahresansicht
</button>
```

### Nicht verändert

`gsh_tp_parse_events`, `gsh_tp_parse_event`, `gsh_tp_build_date_index`, `gsh_tp_day_events`, `gsh_tp_table`, `gsh_tp_mobile`, `gsh_tp_fetch_ical`, `gsh_tp_fetch_sync`

---

## JavaScript (in `gsh_tp_js()`)

### Neue Funktion: `gtpViewToggle(btn)`

```js
function gtpViewToggle(btn) {
    const wrap = btn.closest('.gtp-wrap');
    const toYear = wrap.dataset.view !== 'year';
    wrap.dataset.view = toYear ? 'year' : 'quartal';
    btn.textContent = toYear
        ? btn.dataset.labelQuartal
        : btn.dataset.labelYear;
}
```

### Änderung: `gtpFil(el)`

Bestehender Selektor für Event-Elemente um `.gtp-yr-ev[data-c]` erweitern — eine Zeile Änderung. Filter-State bleibt beim View-Wechsel erhalten.

---

## CSS (in `gsh-terminplan.css`)

### View-Toggle

```css
.gtp-wrap[data-view="quartal"] .gtp-year-wrap { display: none; }
.gtp-wrap[data-view="year"] .gtp-quarters-wrap { display: none; }
```

### Toggle-Button

Selbes Styling wie `.gtp-sj-btn` (Schuljahr-Switcher). Aktiver Zustand via `[data-view="year"] .gtp-view-btn` → `.gtp-view-on`-Variante.

### Jahresraster-Tabelle

```css
.gtp-yr { border-collapse: collapse; width: 100%; font-size: 0.72rem; }
.gtp-yr th { /* Monatskopf: Kategorie-Hintergrundfarbe via CSS-Variablen */ }
.gtp-yr td { vertical-align: top; min-width: 60px; padding: 2px 3px; }
.gtp-yr td.gtp-yr-invalid { background: repeating-linear-gradient(45deg, transparent, transparent 3px, rgba(0,0,0,.04) 3px, rgba(0,0,0,.04) 6px); pointer-events: none; }
.gtp-yr tr.gtp-yr-today td { background: var(--gtp-today-bg); }
```

### Event-Chips

```css
.gtp-yr-ev {
    display: block;
    border-radius: 3px;
    padding: 1px 4px;
    margin-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    cursor: default;
    font-size: 0.68rem;
    position: relative;
}
```

### Tooltip (CSS-only)

```css
.gtp-yr-ev:hover::after {
    content: attr(data-summary) "\A" attr(data-date);
    white-space: pre;
    position: absolute;
    left: 0; top: 100%;
    z-index: 100;
    background: var(--gtp-tooltip-bg, #333);
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    pointer-events: none;
    min-width: 160px;
}
```

### Responsiv

```css
.gtp-year-wrap { overflow-x: auto; }
```

Unter 768 px horizontales Scrollen. Kein separates Mobile-Layout — Jahresraster ist desktop-orientiert.

---

## Versioning

Minor-Bump: `4.1.0 → 4.2.0`

Alle 4 Stellen synchron:
1. Plugin-Header `Version:`
2. `define('GSH_TP_VERSION', ...)`
3. `gsh_tp_changelog()` — neuer Eintrag
4. Changelog im Plugin-Header-Kommentar

---

## Nicht in Scope

- Druckoptimierung / PDF-Export der Jahresansicht
- Admin-Einstellung zum Aktivieren/Deaktivieren der Jahresansicht
- Mobile-spezifisches Jahresraster-Layout
- Jahresansicht im Draft-Kiosk (kann später ergänzt werden)
