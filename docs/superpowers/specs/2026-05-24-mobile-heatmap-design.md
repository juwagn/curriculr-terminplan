# Mobile Jahresansicht — Heatmap-Streifen

**Datum:** 2026-05-24
**Status:** Genehmigt
**Version:** Plugin 4.3.4 → 4.4.0

---

## Überblick

Die bestehende Jahresansicht (10-Spalten-Tabelle) ist auf Mobilgeräten unbrauchbar: zu eng, horizontales Scrollen, Textchips zu klein. Neue mobile Variante: Heatmap-Streifen (ein Streifen pro Monat, eine Kachel pro Tag). Desktop und Tablet behalten die Tabelle als Standard, können aber per Toggle-Button auf Heatmap wechseln.

---

## Entscheidungen

| Frage | Entscheidung | Begründung |
|---|---|---|
| Layout | Heatmap-Streifen | Ganzes Schuljahr ohne Scrollen sichtbar |
| Tap-Verhalten | Inline-Expand unter Monatsstreifen | Kein Overlay, kein Kontextwechsel |
| Multi-Kategorie | Diagonaler CSS-Gradient | Kacheln ~8px → Dots zu klein; Gradient CSS-only |
| Mobile-Scope | ≤768px automatisch | Entspricht bestehendem Breakpoint |
| Desktop/Tablet | Toggle-Button optional | Nutzer kann selbst wählen |

---

## Nicht verändert

- `gsh_tp_parse_events`, `gsh_tp_build_date_index`, `gsh_tp_day_events`
- Bestehende Desktop-Tabelle (`.gtp-yr`) und ihr Rendering in `gsh_tp_yearview()`
- Bestehender Toggle zwischen Quartal- und Jahresansicht

`gsh_tp_yearview()` wird um den Heatmap-Block **erweitert** (neuer Ausgabe-Block am Ende), bestehende Tabellen-Ausgabe unberührt.

---

## HTML-Struktur

`gsh_tp_yearview()` gibt zusätzlich einen `.gtp-yr-heatmap`-Block aus, direkt nach der bestehenden Tabelle im selben `.gtp-year-wrap`:

```
.gtp-year-wrap
  ├── .gtp-yr                          ← Desktop-Standard (unverändert)
  └── .gtp-yr-heatmap                  ← neu
        ├── .gtp-yr-hm-month × 10
        │     ├── .gtp-yr-hm-label     ← "SEP", "OKT", …
        │     ├── .gtp-yr-hm-grid      ← 31 Kacheln
        │     │     └── .gtp-yr-hm-sq[data-date="YYYY-MM-DD"][data-cats="slug1 slug2"]
        │     └── .gtp-yr-hm-expand    ← versteckt, Inline-Detail
        └── .gtp-yr-hm-legend          ← Farb-Legende
```

### Kachel-Attribute

Jede Kachel erhält:
- `data-date="2025-09-10"` — ISO-Datum für JS-Lookup
- `data-cats="konferenzen jahrgang-78"` — Leerzeichen-getrennte Kategorie-Slugs
- `data-valid="0|1"` — 0 für ungültige Daten (31. Feb etc.)

Leere Kacheln (kein Event): keine `data-cats`, neutrale Farbe via CSS.

---

## PHP — Änderungen in `gsh_tp_yearview()`

Neue Schleife nach bestehender Tabellen-Ausgabe. Pro Monat (Sep–Jul):

1. Monats-Header rendern: `<div class="gtp-yr-hm-label">SEP</div>`
2. Grid mit 31 Kacheln, `checkdate()` für Validität
3. Pro gültigem Tag: `gsh_tp_day_events($index, $date_str)` aufrufen
4. Kategorien aller Events sammeln → eindeutige Slugs → `data-cats`
5. Kachel-Klasse: `gtp-yr-hm-sq` + optional `gtp-yr-hm-sq--today` (heutiger Tag)
6. Expand-Container pro Monat: `<div class="gtp-yr-hm-expand" data-month="2025-09" hidden>`
   - Befüllung via JS (aus DOM, kein AJAX)

**Position in Datei:** Sektion 5, unmittelbar nach bestehender Tabellen-Ausgabe in `gsh_tp_yearview()`.

---

## CSS — Änderungen in `gsh-terminplan.css`

### Sichtbarkeit (Breakpoint)

```css
/* Mobile: Heatmap aktiv, Tabelle aus */
@media (max-width: 768px) {
    .gtp-year-wrap .gtp-yr         { display: none; }
    .gtp-yr-heatmap                { display: block; }
}

/* Desktop/Tablet: Tabelle aktiv, Heatmap aus (Default) */
@media (min-width: 769px) {
    .gtp-yr-heatmap                { display: none; }
    .gtp-year-wrap--heatmap .gtp-yr        { display: none; }
    .gtp-year-wrap--heatmap .gtp-yr-heatmap { display: block; }
}
```

### Heatmap-Layout

```css
.gtp-yr-heatmap { padding: .5rem 0; }

.gtp-yr-hm-month { margin-bottom: .6rem; }

.gtp-yr-hm-label {
    font-size: .65rem;
    font-weight: 700;
    color: var(--gtp-text-muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 2px;
}

.gtp-yr-hm-grid {
    display: grid;
    grid-template-columns: repeat(31, 1fr);
    gap: 1px;
}

.gtp-yr-hm-sq {
    aspect-ratio: 1;
    border-radius: 1px;
    background: var(--gtp-border, #e2e8f0);  /* leer */
    cursor: pointer;
    transition: opacity .1s;
}

.gtp-yr-hm-sq[data-valid="0"] {
    background: transparent;
    cursor: default;
    pointer-events: none;
}

.gtp-yr-hm-sq--today {
    outline: 1.5px solid var(--gtp-accent, #0058A0);
    outline-offset: -1px;
}

.gtp-yr-hm-sq--active {
    outline: 2px solid var(--gtp-accent, #0058A0);
    outline-offset: 0;
    border-radius: 2px;
}
```

### Gradient-Farben

Die Kategorie-Farben variieren pro Profil und sind CSS Custom Properties — Gradient muss via JS gebaut werden (nicht rein deklarativ möglich).

Kachel-Farben werden ausschließlich via JS inline gesetzt (kein CSS-Fallback nötig — leere Kacheln ohne `data-cats` behalten die neutrale Border-Farbe).

### Inline-Expand

```css
.gtp-yr-hm-expand {
    margin-top: 4px;
    border-left: 3px solid var(--gtp-accent, #0058A0);
    background: var(--gtp-accent-light, #E6F4FF);
    border-radius: 0 4px 4px 0;
    padding: 6px 10px;
    font-size: .78rem;
}

.gtp-yr-hm-expand-date {
    font-size: .68rem;
    font-weight: 700;
    color: var(--gtp-accent, #0058A0);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
}

.gtp-yr-hm-expand-ev {
    padding: 2px 0;
    color: var(--gtp-text, #334155);
}
```

### Legende

```css
.gtp-yr-hm-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .75rem;
    margin-top: .5rem;
    font-size: .68rem;
    color: var(--gtp-text-muted);
}

.gtp-yr-hm-legend-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 1px;
    margin-right: 3px;
    vertical-align: middle;
}
```

---

## JS — Ergänzung in `gsh_tp_js()`

Neues Script-Block, vollständig in `gsh_tp_js()` integriert (kein separates File).

### Gradient-Färbung

Nach DOM-Ready: alle `.gtp-yr-hm-sq[data-cats]` iterieren, `data-cats` splitten, Farben aus CSS Custom Properties der Kategorien lesen, `linear-gradient` setzen.

```js
// Pseudocode — exakter Code in Implementierung
document.querySelectorAll('.gtp-yr-hm-sq[data-cats]').forEach(sq => {
    const cats = sq.dataset.cats.trim().split(/\s+/);
    if (cats.length === 1) {
        sq.style.background = getCatColor(cats[0]);
    } else {
        const pct = 100 / cats.length;
        const stops = cats.map((c, i) =>
            `${getCatColor(c)} ${i*pct}% ${(i+1)*pct}%`
        ).join(', ');
        sq.style.background = `linear-gradient(135deg, ${stops})`;
    }
});
```

`getCatColor(slug)` liest `getComputedStyle(document.documentElement).getPropertyValue('--gtp-cat-' + slug)` — Farben bereits via `gsh_tp_category_css()` als CSS Custom Properties vorhanden.

### Inline-Expand Toggle

Klick auf `.gtp-yr-hm-sq` mit `data-cats`:

1. Aktiven Expand im selben Monat schließen (falls anderer Tag offen)
2. Falls gleicher Tag: toggle (schließen)
3. Sonst: `data-date` lesen → Events aus DOM-Index holen → HTML in `.gtp-yr-hm-expand` schreiben → `hidden` entfernen → `.gtp-yr-hm-sq--active` setzen

Events-Quelle: DOM-Traversal auf `.gtp-yr td[data-date]`-Kacheln der bestehenden Tabelle (selbe Attribute die der Kategorie-Filter nutzt). Kein AJAX, keine doppelte Datenhaltung.

### Desktop Heatmap-Toggle

Neuer Button neben `.gtp-view-toggle`:

```html
<button class="gtp-heatmap-toggle" data-label-off="☷ Heatmap" data-label-on="⊞ Tabelle">
    ☷ Heatmap
</button>
```

Klick: toggle Klasse `.gtp-year-wrap--heatmap` auf `.gtp-year-wrap`. Button-Label wechseln. Nur sichtbar wenn Jahresansicht aktiv (`data-view="year"`).

---

## Kategorie-Filter-Integration

Bestehender Kategorie-Filter (`.gtp-filter-btn`) blendet bereits Events via JS aus. Heatmap muss auf Filter-Änderungen reagieren:

- Filter-Event (`gtpFilterChange` oder MutationObserver auf aktive Filter-Buttons) → Kacheln ohne sichtbare Kategorien erhalten Klasse `gtp-yr-hm-sq--filtered` → CSS: `opacity: .2`
- Gradient neu berechnen für gefilterte Kacheln nicht nötig — Opacity reicht

---

## Versionierung

- Plugin-Header: `4.3.4` → `4.4.0`
- `define('GSH_TP_VERSION', '4.4.0')`
- Changelog: „Mobile Jahresansicht: Heatmap-Streifen mit Inline-Expand und optionalem Desktop-Toggle"

---

## Nicht in Scope

- Wochentagsanzeige in Kacheln (zu eng bei 31 Spalten)
- Swipe-Geste zum Schließen des Expand (Komplexität/Nutzen gering)
- Persistenz des Desktop-Toggle-Zustands (sessionStorage optional, nicht Pflicht)
- Änderung am PDF-Export
