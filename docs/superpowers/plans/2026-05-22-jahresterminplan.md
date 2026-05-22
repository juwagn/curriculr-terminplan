# Jahresterminplan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jahresansicht hinzufügen — klassisches Raster (Monate Sep–Jul als Spalten, Tage 1–31 als Zeilen) mit Umschaltknopf neben bestehender Quartalsansicht.

**Architecture:** Beide Ansichten werden serverseitig beim Seitenaufruf gerendert; `data-view` am `#gtp`-Wrapper steuert per CSS, welche sichtbar ist. Events im Jahresraster nutzen dieselben `gc-{slug}`-CSS-Klassen und `data-c`-Attribute wie die Quartalsansicht — Filter und Popup-System arbeiten ohne Umbau.

**Tech Stack:** PHP 7.4+, Vanilla JS (kein Build-System), CSS Custom Properties. Kein AJAX, kein npm.

---

## Dateiübersicht

| Datei | Änderung |
|-------|----------|
| `plugin/gsh-terminplan.php` | Neue Funktion `gsh_tp_yearview()` (vor `gsh_tp_css()`), Shortcode-Änderungen (Wrapper-Attr, Quarters-Wrap, Toggle-Button, Yearview-Call), JS `gtpViewToggle()` + `gtpApply()`-Erweiterung, Version-Bump |
| `plugin/assets/css/gsh-terminplan.css` | View-Toggle-CSS, Jahresraster-Tabelle, Event-Chips, Today-Highlighting, Invalid-Zellen, Responsive |

---

## Task 1: CSS für Jahresraster

**Files:**
- Modify: `plugin/assets/css/gsh-terminplan.css` (ans Ende anhängen)

- [ ] **Schritt 1: CSS-Block ans Ende von `gsh-terminplan.css` anhängen**

```css
/* ═══════════════════════════════════════════════
   JAHRESANSICHT
   ═══════════════════════════════════════════════ */

/* View-Toggle: Sichtbarkeit der beiden Ansichten */
#gtp[data-view="quartal"] .gtp-year-wrap    { display: none; }
#gtp[data-view="year"]    .gtp-quarters-wrap { display: none; }
#gtp[data-view="year"]    .gtp-tabs          { display: none; }

/* Toggle-Button */
.gtp-view-toggle {
    display: inline-flex;
    align-items: center;
    gap: .35em;
    padding: .35em .8em;
    border: 1.5px solid var(--gtp-border, #cbd5e1);
    border-radius: 6px;
    background: transparent;
    color: var(--gtp-text, #334155);
    font-size: .82rem;
    font-weight: 500;
    cursor: pointer;
    transition: background .15s, border-color .15s;
    margin-top: .5rem;
    margin-bottom: .5rem;
}
.gtp-view-toggle:hover,
.gtp-view-toggle-on {
    background: var(--gtp-accent-bg, #eff6ff);
    border-color: var(--gtp-accent, #3b82f6);
    color: var(--gtp-accent, #3b82f6);
}

/* Jahresraster-Tabelle */
.gtp-year-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-top: .75rem;
}
.gtp-yr {
    border-collapse: collapse;
    width: 100%;
    font-size: .72rem;
    table-layout: fixed;
}
.gtp-yr-dh {
    width: 1.6rem;
}
.gtp-yr th,
.gtp-yr td {
    border: 1px solid var(--gtp-border, #e2e8f0);
    padding: 2px 3px;
    vertical-align: top;
    min-width: 58px;
}
.gtp-yr-mh {
    background: var(--gtp-head-bg, #f1f5f9);
    font-weight: 700;
    text-align: center;
    font-size: .78rem;
    color: var(--gtp-text-muted, #64748b);
    letter-spacing: .03em;
}
.gtp-yr-dn {
    text-align: right;
    color: var(--gtp-text-muted, #94a3b8);
    font-variant-numeric: tabular-nums;
    font-size: .7rem;
    background: var(--gtp-head-bg, #f8fafc);
    white-space: nowrap;
}

/* Ungültige Zellen (z. B. 31. Feb) */
.gtp-yr-invalid {
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 3px,
        rgba(0,0,0,.04) 3px,
        rgba(0,0,0,.04) 6px
    );
    pointer-events: none;
}

/* Heutige Zeile */
.gtp-yr-today {
    background: var(--gtp-today-row-bg, #fef9c3);
}
.gtp-yr-today .gtp-yr-dn {
    background: var(--gtp-today-dn-bg, #fef08a);
    font-weight: 700;
    color: var(--gtp-text, #334155);
}

/* Event-Chips im Jahresraster */
.gtp-yr-cell {
    min-height: 1.1rem;
}
.gtp-yr-ev {
    display: block;
    border-radius: 3px;
    padding: 1px 4px;
    margin-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    cursor: pointer;
    font-size: .67rem;
    line-height: 1.4;
    /* Farbe kommt von gc-{slug} Klasse (gsh_tp_category_css) */
}
```

- [ ] **Schritt 2: Syntax visuell prüfen**

Öffne `plugin/assets/css/gsh-terminplan.css` im Editor und stelle sicher, dass alle `{`/`}` geschlossen sind.

- [ ] **Schritt 3: Commit**

```bash
git add plugin/assets/css/gsh-terminplan.css
git commit -m "feat(css): add Jahresansicht styles — year grid, event chips, view toggle"
```

---

## Task 2: PHP-Funktion `gsh_tp_yearview()`

**Files:**
- Modify: `plugin/gsh-terminplan.php` — neue Funktion vor `gsh_tp_css()` (vor Zeile 5097) einfügen

- [ ] **Schritt 1: Funktion einfügen**

Direkt **vor** der Zeile `function gsh_tp_css() {` (aktuell Zeile 5097) folgenden Block einfügen:

```php
/**
 * Rendert das Jahresraster (Sep–Jul, 10 Monate × 31 Tage).
 * Nutzt den bestehenden Date-Index; kein eigener iCal-Fetch.
 *
 * @param array  $index    Date-Index aus gsh_tp_build_date_index().
 * @param array  $quartale Array mit 4 Quartalsgrenzen [['start'=>'Y-m-d','end'=>'Y-m-d'], …].
 * @param string $pid      Profil-ID (für zukünftige Erweiterungen, derzeit unused).
 * @return string HTML-String des Jahresrasters.
 */
function gsh_tp_yearview( $index, $quartale, $pid ) {
    if ( empty( $quartale ) || empty( $index ) ) {
        return '';
    }

    // Schuljahr: Sep des Q1-Startjahres bis Jul des Folgejahres
    $start_year = (int) substr( $quartale[0]['start'], 0, 4 );
    $months = array(
        array( 'n' => 9,  'year' => $start_year,     'label' => 'Sep' ),
        array( 'n' => 10, 'year' => $start_year,     'label' => 'Okt' ),
        array( 'n' => 11, 'year' => $start_year,     'label' => 'Nov' ),
        array( 'n' => 12, 'year' => $start_year,     'label' => 'Dez' ),
        array( 'n' => 1,  'year' => $start_year + 1, 'label' => 'Jan' ),
        array( 'n' => 2,  'year' => $start_year + 1, 'label' => 'Feb' ),
        array( 'n' => 3,  'year' => $start_year + 1, 'label' => 'Mär' ),
        array( 'n' => 4,  'year' => $start_year + 1, 'label' => 'Apr' ),
        array( 'n' => 5,  'year' => $start_year + 1, 'label' => 'Mai' ),
        array( 'n' => 6,  'year' => $start_year + 1, 'label' => 'Jun' ),
        array( 'n' => 7,  'year' => $start_year + 1, 'label' => 'Jul' ),
    );

    $today_str = gmdate( 'Y-m-d' );

    // Kategorie-Map für gsh_tp_primary_slug (gecacht per static)
    static $cat_map_yr = null;
    if ( null === $cat_map_yr ) {
        $cat_map_yr = array_column( gsh_tp_get_categories(), null, 'id' );
    }

    $o  = '<div class="gtp-year-wrap" role="region" aria-label="Jahresterminplan">';
    $o .= '<table class="gtp-yr">';

    // Kopfzeile: Monatsnamen
    $o .= '<thead><tr><th class="gtp-yr-dh" scope="col" aria-label="Tag"></th>';
    foreach ( $months as $m ) {
        $o .= '<th class="gtp-yr-mh" scope="col">' . esc_html( $m['label'] ) . '</th>';
    }
    $o .= '</tr></thead>';

    // Zeilen: Tage 1–31
    $o .= '<tbody>';
    for ( $day = 1; $day <= 31; $day++ ) {
        // Prüfen ob mind. ein Monat diesen Tag hat (sonst Zeile überspringen)
        $row_has_valid = false;
        foreach ( $months as $m ) {
            if ( checkdate( $m['n'], $day, $m['year'] ) ) {
                $row_has_valid = true;
                break;
            }
        }

        // Heutige Zeile bestimmen
        $is_today_row = false;
        if ( $row_has_valid ) {
            foreach ( $months as $m ) {
                if ( checkdate( $m['n'], $day, $m['year'] ) ) {
                    $d = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
                    if ( $d === $today_str ) {
                        $is_today_row = true;
                        break;
                    }
                }
            }
        }

        $row_cls = $is_today_row ? ' class="gtp-yr-today"' : '';
        $o .= '<tr' . $row_cls . '>';
        $o .= '<td class="gtp-yr-dn" scope="row">' . $day . '</td>';

        foreach ( $months as $m ) {
            if ( ! checkdate( $m['n'], $day, $m['year'] ) ) {
                $o .= '<td class="gtp-yr-cell gtp-yr-invalid" aria-hidden="true"></td>';
                continue;
            }

            $date_str = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
            $events   = gsh_tp_day_events( $index, $date_str );
            $o       .= '<td class="gtp-yr-cell">';

            foreach ( $events as $ev ) {
                $cat_ids = (array) ( isset( $ev['categories'] ) ? $ev['categories'] : array() );
                $slug    = gsh_tp_primary_slug( $cat_ids, $cat_map_yr );
                $short   = esc_html( mb_substr( wp_strip_all_tags( $ev['summary'] ), 0, 18 ) );

                $o .= '<span class="gtp-yr-ev gc-' . esc_attr( $slug ) . '"'
                    . ' data-c="' . esc_attr( $slug ) . '"'
                    . gsh_tp_event_data_attrs( $ev )
                    . ' onclick="gtpPopupOpen(this)">'
                    . $short . '</span>';
            }

            $o .= '</td>';
        }

        $o .= '</tr>';
    }
    $o .= '</tbody></table></div>';

    return $o;
}
```

- [ ] **Schritt 2: PHP-Syntax prüfen**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 3: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(plugin): add gsh_tp_yearview() — renders Sep–Jul annual grid"
```

---

## Task 3: Shortcode-Änderungen

**Files:**
- Modify: `plugin/gsh-terminplan.php` — 4 Änderungen in `gsh_tp_shortcode()`

### Änderung A: `data-view` am Haupt-Wrapper (Zeile 4309)

- [ ] **Schritt 1: `data-view="quartal"` zum Wrapper hinzufügen**

Zeile 4309, **ersetzen:**
```php
    $o .= '<div class="gtp" id="gtp" data-changes="' . $changes_json . '" data-categories="' . $cats_json . '">';
```
**durch:**
```php
    $o .= '<div class="gtp" id="gtp" data-view="quartal" data-changes="' . $changes_json . '" data-categories="' . $cats_json . '">';
```

### Änderung B: Toggle-Button nach Tabs (nach Zeile 4382)

- [ ] **Schritt 2: Toggle-Button nach dem schließenden `</div>` der `.gtp-tabs` einfügen**

Zeile 4382, **ersetzen:**
```php
    $o .= '</div>';

    // Filter-Buttons (dynamisch aus Kategorie-Einstellungen – v3.15.0: --btn-color)
```
**durch:**
```php
    $o .= '</div>';

    // View-Toggle: Quartalsansicht ↔ Jahresansicht
    $o .= '<button type="button" class="gtp-view-toggle" id="gtp-view-toggle"'
        . ' data-label-quartal="Quartalsansicht"'
        . ' data-label-year="Jahresansicht"'
        . ' onclick="gtpViewToggle(this)">'
        . gsh_tp_icon( 'calendar', '1em', 'gtp-view-toggle-icon' )
        . '<span class="gtp-view-toggle-label">Jahresansicht</span></button>';

    // Filter-Buttons (dynamisch aus Kategorie-Einstellungen – v3.15.0: --btn-color)
```

### Änderung C: Quartals-Panels in `.gtp-quarters-wrap` einschließen (Zeilen 4407–4419)

- [ ] **Schritt 3: Quarters-Loop in Wrapper einschließen**

Zeile 4407, **ersetzen:**
```php
    // Quartalspanels
    for ( $q = 1; $q <= 4; $q++ ) {
        $vis = $q === $aq ? 'block' : 'none';
        $qd  = $grenzen[ $q - 1 ] ?? null;
        if ( ! $qd ) {
            continue;
        }
        $o .= '<div class="gtp-qp" id="gtp-q' . $q . '" style="display:' . $vis . '">';
        $o .= '<h3 class="gtp-qt">' . $qlbl[ $q ] . '</h3>';
        $o .= gsh_tp_table( $date_index, $qd, $sjs );   // Desktop-Tabelle (≥ 768px)
        $o .= gsh_tp_mobile( $date_index, $qd, $sjs );  // Agenda-Ansicht  (< 768px)
        $o .= '</div>';
    }
```
**durch:**
```php
    // Quartalspanels
    $o .= '<div class="gtp-quarters-wrap">';
    for ( $q = 1; $q <= 4; $q++ ) {
        $vis = $q === $aq ? 'block' : 'none';
        $qd  = $grenzen[ $q - 1 ] ?? null;
        if ( ! $qd ) {
            continue;
        }
        $o .= '<div class="gtp-qp" id="gtp-q' . $q . '" style="display:' . $vis . '">';
        $o .= '<h3 class="gtp-qt">' . $qlbl[ $q ] . '</h3>';
        $o .= gsh_tp_table( $date_index, $qd, $sjs );   // Desktop-Tabelle (≥ 768px)
        $o .= gsh_tp_mobile( $date_index, $qd, $sjs );  // Agenda-Ansicht  (< 768px)
        $o .= '</div>';
    }
    $o .= '</div>'; // .gtp-quarters-wrap

    // Jahresansicht
    $o .= gsh_tp_yearview( $date_index, $grenzen, $profile_id );
```

- [ ] **Schritt 4: PHP-Syntax prüfen**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(plugin): wire Jahresansicht into shortcode — wrapper attr, toggle button, yearview call"
```

---

## Task 4: JavaScript — `gtpViewToggle()` + `gtpApply()` erweitern

**Files:**
- Modify: `plugin/gsh-terminplan.php` — Änderungen in `gsh_tp_js()` (ab Zeile 5804)

### Änderung A: `gtpViewToggle()` hinzufügen

- [ ] **Schritt 1: Neue Funktion in `gsh_tp_js()` einfügen**

In der `gsh_tp_js()`-Funktion (Zeile 5804+), **direkt nach dem öffnenden `return <<<JS` Block** oder einem geeigneten JS-Abschnitt, **folgende Funktion einfügen** (vor dem schließenden `JS;`):

Suche nach dem Kommentar- oder Funktionsblock, der andere Toggle-Funktionen enthält (z. B. `gtpFilterToggle`), und füge `gtpViewToggle` als Nachbar ein:

```javascript
/* Schaltet zwischen Quartals- und Jahresansicht um */
function gtpViewToggle(btn){
  var wrap  = document.getElementById("gtp");
  var toYear = wrap ? wrap.dataset.view !== "year" : false;
  if(wrap) wrap.dataset.view = toYear ? "year" : "quartal";
  var label = btn.querySelector(".gtp-view-toggle-label");
  if(label){
    label.textContent = toYear
      ? btn.dataset.labelQuartal
      : btn.dataset.labelYear;
  }
  btn.classList.toggle("gtp-view-toggle-on", toYear);
}
```

### Änderung B: `gtpApply()` um Jahresraster erweitern

- [ ] **Schritt 2: Jahresraster-Selektor in `gtpApply()` ergänzen**

In `gtpApply()` (Zeile 5956), **nach dem Block:**
```javascript
  /* Lange Termine + Frist-Notizen: nur Kategorie-Filter */
  document.querySelectorAll(".gn-long[data-c], .gn[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = !gtpSel[c] ? "" : "none";
  });
```

**diesen Block einfügen** (vor dem `/* Reset-Button zeigen / verstecken */` Teil):
```javascript
  /* Jahresraster-Events: Kategorie-Filter */
  document.querySelectorAll(".gtp-yr-ev[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = !gtpSel[c] ? "" : "none";
  });
```

- [ ] **Schritt 3: PHP-Syntax prüfen**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(plugin): add gtpViewToggle(), extend gtpApply() for year view filter"
```

---

## Task 5: Version Bump 4.1.0 → 4.2.0

**Files:**
- Modify: `plugin/gsh-terminplan.php` — 4 Stellen synchron ändern

- [ ] **Schritt 1: Plugin-Header `Version:` aktualisieren**

Suche nach `Version: 4.1.0` im Plugin-Header-Kommentar (ganz oben in der Datei), ersetze durch `Version: 4.2.0`.

- [ ] **Schritt 2: `GSH_TP_VERSION` Konstante aktualisieren**

Suche nach `define('GSH_TP_VERSION', '4.1.0')` (oder `define( 'GSH_TP_VERSION', '4.1.0' )`), ersetze durch `define( 'GSH_TP_VERSION', '4.2.0' )`.

- [ ] **Schritt 3: `gsh_tp_changelog()` — neuen Eintrag oben einfügen**

In `gsh_tp_changelog()`, **obersten Eintrag** durch folgenden Block **ergänzen** (davor einfügen):

```php
array(
    'version' => '4.2.0',
    'date'    => '2026-05-22',
    'changes' => array(
        'Jahresansicht: klassisches Monats-Tage-Raster (Sep–Jul) als neue Ansicht',
        'Toggle-Button zum Wechseln zwischen Quartals- und Jahresansicht',
        'Kategorie-Filter und Ereignis-Popup funktionieren in Jahresansicht',
    ),
),
```

- [ ] **Schritt 4: Changelog im Plugin-Header-Kommentar ergänzen**

Im Plugin-Header-Kommentar (ganz oben), unter dem `Changelog`-Abschnitt, **oben einfügen**:

```
 * 4.2.0 – Jahresansicht (Sep–Jul Monats-Tage-Raster, Toggle-Button, Filter-Integration)
```

- [ ] **Schritt 5: PHP-Syntax prüfen**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 6: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "chore(plugin): bump version 4.1.0 → 4.2.0, add Jahresansicht changelog entry"
```

---

## Abschließende Verifikation

- [ ] Plugin in WordPress hochladen und aktivieren
- [ ] Terminplan-Seite aufrufen → „Jahresansicht"-Button erscheint unterhalb der Q1–Q4-Tabs
- [ ] Klick auf Button: Q1–Q4-Tabs verschwinden, Jahresraster erscheint; Button-Text wechselt zu „Quartalsansicht"
- [ ] Rückklick: Quartalsansicht erscheint wieder
- [ ] Im Jahresraster: Klick auf Event-Chip öffnet bestehendes Popup mit Details
- [ ] Kategorie-Filter deaktivieren: Events der Kategorie verschwinden auch im Jahresraster
- [ ] Heute-Zeile ist gelblich hervorgehoben
- [ ] Ungültige Zellen (z. B. 31. Sep, Feb) zeigen Schraffur
- [ ] Auf Mobile: Jahresraster horizontal scrollbar
