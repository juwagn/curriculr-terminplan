# Mobile Jahresansicht — Heatmap-Streifen Implementierungsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Jahresansicht auf Mobile (≤768px) durch Heatmap-Streifen ersetzen; Desktop/Tablet bekommt optionalen Toggle.

**Architecture:** Additiv — bestehende Tabelle bleibt unverändert. `gsh_tp_yearview()` gibt zusätzlich einen `.gtp-yr-heatmap`-Block aus. CSS steuert Sichtbarkeit per Breakpoint und Modifier-Klasse. JS malt Kachel-Farben, handhabt Inline-Expand, integriert Filter.

**Tech Stack:** PHP 7.4+, Vanilla JS (ES5-kompatibel), CSS Custom Properties — kein Build-System, kein AJAX.

---

## Dateien

| Datei | Änderung |
|---|---|
| `plugin/gsh-terminplan.php` | `gsh_tp_yearview()` erweitern; Toggle-Button in `gsh_tp_shortcode()`; JS-Block + Filter-Hook in `gsh_tp_js()`; Version bump |
| `plugin/assets/css/gsh-terminplan.css` | Neuer Abschnitt am Ende (Heatmap-Styles) |

---

## Task 1: PHP — Heatmap-Block in `gsh_tp_yearview()`

**Files:**
- Modify: `plugin/gsh-terminplan.php:5271`

- [ ] **Schritt 1: Letzte Zeile von `gsh_tp_yearview()` suchen**

Die Funktion endet derzeit bei ca. Zeile 5271:
```php
    $o .= '</tbody></table></div>';

    return $o;
}
```

- [ ] **Schritt 2: Heatmap-Block einfügen**

Den letzten Block der Funktion ersetzen (ab `$o .= '</tbody></table></div>';`):

```php
    $o .= '</tbody></table>';

    /* Heatmap-Block (Mobile Jahresansicht v4.4.0) */
    $o .= '<div class="gtp-yr-heatmap" role="presentation" aria-hidden="true">';
    foreach ( $months as $m ) {
        $o .= '<div class="gtp-yr-hm-month">';
        $o .= '<div class="gtp-yr-hm-label">' . esc_html( strtoupper( $m['label'] ) ) . '</div>';
        $o .= '<div class="gtp-yr-hm-grid">';
        for ( $day = 1; $day <= 31; $day++ ) {
            if ( ! checkdate( $m['n'], $day, $m['year'] ) ) {
                $o .= '<div class="gtp-yr-hm-sq" data-valid="0" aria-hidden="true"></div>';
                continue;
            }
            $date_str = sprintf( '%04d-%02d-%02d', $m['year'], $m['n'], $day );
            $evs      = gsh_tp_day_events( $index, $date_str );
            $slugs    = array();
            foreach ( $evs as $ev ) {
                $cids = (array) ( $ev['categories'] ?? array() );
                foreach ( $cids as $cid ) {
                    if ( isset( $cat_map_yr[ $cid ] ) ) {
                        $slugs[ $cat_map_yr[ $cid ]['slug'] ] = true;
                    }
                }
            }
            $today_cls = ( $date_str === $today_str ) ? ' gtp-yr-hm-sq--today' : '';
            $cats_attr = ! empty( $slugs )
                ? ' data-cats="' . esc_attr( implode( ' ', array_keys( $slugs ) ) ) . '"'
                : '';
            $o .= '<div class="gtp-yr-hm-sq' . $today_cls . '"'
                . ' data-valid="1"'
                . ' data-date="' . esc_attr( $date_str ) . '"'
                . $cats_attr . '></div>';
        }
        $o .= '</div>'; /* .gtp-yr-hm-grid */
        $o .= '<div class="gtp-yr-hm-expand" hidden></div>';
        $o .= '</div>'; /* .gtp-yr-hm-month */
    }
    $o .= '<div class="gtp-yr-hm-legend">';
    foreach ( gsh_tp_get_categories() as $cat ) {
        $color = $cat['color'] ?? '#94a3b8';
        $o    .= '<span class="gtp-yr-hm-legend-item">'
            . '<span class="gtp-yr-hm-legend-dot" style="background:' . esc_attr( $color ) . '"></span>'
            . esc_html( $cat['label'] )
            . '</span>';
    }
    $o .= '</div>'; /* .gtp-yr-hm-legend */
    $o .= '</div>'; /* .gtp-yr-heatmap */

    $o .= '</div>'; /* .gtp-year-wrap */
    return $o;
}
```

- [ ] **Schritt 3: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(plugin): Heatmap-Block in gsh_tp_yearview() (v4.4.0 prep)"
```

---

## Task 2: PHP — Desktop-Toggle-Button in `gsh_tp_shortcode()`

**Files:**
- Modify: `plugin/gsh-terminplan.php:4460`

- [ ] **Schritt 1: Stelle finden**

Zeile ~4460 — nach dem bestehenden `gtp-view-toggle`-Button:
```php
        . '<span class="gtp-view-toggle-label">Jahresansicht</span></button>';
    $o .= '</div>'; // .gtp-tabs
```

- [ ] **Schritt 2: Heatmap-Toggle-Button einfügen**

Direkt nach der Zeile mit `Jahresansicht</span></button>';` und VOR `$o .= '</div>'; // .gtp-tabs`:

```php
    $o .= '<button type="button" class="gtp-view-toggle" id="gtp-heatmap-toggle"'
        . ' onclick="gtpHeatmapToggle(this)">'
        . gsh_tp_icon( 'grid', '1em', 'gtp-view-toggle-icon' )
        . '<span class="gtp-view-toggle-label">Heatmap</span></button>';
```

Hinweis: Falls `'grid'` kein registrierter Icon-Name in `gsh_tp_icon()` ist, stattdessen `'calendar'` verwenden (bereits registriert).

- [ ] **Schritt 3: Icon-Name prüfen**

Im `$paths`-Array von `gsh_tp_icon()` suchen (ca. Zeile 2300–2370):
```bash
grep -n "'grid'\|\"grid\"" plugin/gsh-terminplan.php | head -5
```

Falls kein `grid`-Eintrag: Button-Code ändern auf `gsh_tp_icon( 'calendar', '1em', 'gtp-view-toggle-icon' )`.

- [ ] **Schritt 4: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(plugin): Heatmap-Toggle-Button in Jahresansicht-Header"
```

---

## Task 3: CSS — Heatmap-Styles

**Files:**
- Modify: `plugin/assets/css/gsh-terminplan.css` (ans Ende anfügen)

- [ ] **Schritt 1: CSS-Block ans Ende anfügen**

Direkt nach der letzten Zeile (`.gtp-yr-ev { ... }`) einfügen:

```css
/* ═══════════════════════════════════════════════════════════════
   HEATMAP — Mobile Jahresansicht (v4.4.0)
   ═══════════════════════════════════════════════════════════════ */

/* Mobile: Heatmap aktiv, Tabelle aus */
@media (max-width: 768px) {
    .gtp-year-wrap .gtp-yr { display: none; }
    .gtp-yr-heatmap        { display: block; }
}

/* Desktop/Tablet: Tabelle aktiv, Heatmap aus (Default) */
@media (min-width: 769px) {
    .gtp-yr-heatmap                               { display: none; }
    .gtp-year-wrap--heatmap .gtp-yr               { display: none; }
    .gtp-year-wrap--heatmap .gtp-yr-heatmap       { display: block; }
}

/* Desktop-Toggle nur sichtbar in Jahresansicht */
#gtp:not([data-view="year"]) #gtp-heatmap-toggle { display: none; }

/* Layout */
.gtp-yr-heatmap { padding: .5rem 0; }

.gtp-yr-hm-month { margin-bottom: .6rem; }

.gtp-yr-hm-label {
    font-size: .65rem;
    font-weight: 700;
    color: var(--gtp-text-muted, #64748b);
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
    background: var(--gtp-border, #e2e8f0);
    cursor: default;
    transition: opacity .1s;
}

.gtp-yr-hm-sq[data-cats] { cursor: pointer; }

.gtp-yr-hm-sq[data-valid="0"] {
    background: transparent;
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

.gtp-yr-hm-sq--filtered { opacity: .2; }

/* Inline-Expand */
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
    border-radius: 3px;
    padding: 2px 6px;
    margin-bottom: 2px;
    font-size: .78rem;
}

/* Legende */
.gtp-yr-hm-legend {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .75rem;
    margin-top: .5rem;
    font-size: .68rem;
    color: var(--gtp-text-muted, #64748b);
}

.gtp-yr-hm-legend-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.gtp-yr-hm-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 1px;
    flex-shrink: 0;
}
```

- [ ] **Schritt 2: Commit**

```bash
git add plugin/assets/css/gsh-terminplan.css
git commit -m "feat(css): Heatmap-Styles für mobile Jahresansicht"
```

---

## Task 4: JS — Heatmap-Funktionen in `gsh_tp_js()`

**Files:**
- Modify: `plugin/gsh-terminplan.php` — Heredoc in `gsh_tp_js()`, nach `gtpViewToggle` (ca. Zeile 6222)

- [ ] **Schritt 1: Einfügestelle finden**

In `gsh_tp_js()` nach der schließenden `}` von `gtpViewToggle` (ca. Zeile 6222):
```js
  btn.classList.toggle("gtp-view-toggle-on", toYear);
}
```

- [ ] **Schritt 2: Heatmap-JS-Block einfügen**

Direkt nach dieser `}` einfügen (innerhalb des Heredocs, also kein PHP-Escaping nötig):

```js
/* ════════════════════════════════════════════════════════
   HEATMAP (Mobile Jahresansicht – v4.4.0)
   ════════════════════════════════════════════════════════ */

function gtpHmColor(slug){
  return getComputedStyle(document.documentElement)
    .getPropertyValue("--c-"+slug+"-bd").trim()||"#94a3b8";
}

function gtpHmPaintTiles(){
  document.querySelectorAll(".gtp-yr-hm-sq[data-cats]").forEach(function(sq){
    var cats=sq.dataset.cats.trim().split(/\s+/);
    if(cats.length===1){
      sq.style.background=gtpHmColor(cats[0]);
    }else{
      var pct=100/cats.length;
      var stops=cats.map(function(c,i){
        return gtpHmColor(c)+" "+(i*pct)+"% "+((i+1)*pct)+"%";
      }).join(",");
      sq.style.background="linear-gradient(135deg,"+stops+")";
    }
  });
}

function gtpHmApplyFilter(){
  document.querySelectorAll(".gtp-yr-hm-sq[data-cats]").forEach(function(sq){
    var cats=sq.dataset.cats.trim().split(/\s+/);
    var anyVisible=cats.some(function(c){return !gtpSel[c];});
    sq.classList.toggle("gtp-yr-hm-sq--filtered",!anyVisible);
  });
}

function gtpHmExpand(sq){
  var date=sq.dataset.date;
  if(!date)return;
  var month=sq.closest(".gtp-yr-hm-month");
  var expand=month?month.querySelector(".gtp-yr-hm-expand"):null;
  if(!expand)return;
  var isActive=sq.classList.contains("gtp-yr-hm-sq--active");
  month.querySelectorAll(".gtp-yr-hm-sq--active").forEach(function(s){
    s.classList.remove("gtp-yr-hm-sq--active");
  });
  expand.hidden=true;
  expand.innerHTML="";
  if(isActive)return;
  var evArr=[];
  document.querySelectorAll(".gtp-yr-ev").forEach(function(ev){
    if((ev.getAttribute("data-date")||"").slice(0,10)===date)evArr.push(ev);
  });
  if(!evArr.length)return;
  var d=new Date(date+"T00:00:00");
  var wdays=["So","Mo","Di","Mi","Do","Fr","Sa"];
  var mons=["Jan","Feb","Mär","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"];
  var label=wdays[d.getDay()]+", "+d.getDate()+". "+mons[d.getMonth()]+" "+d.getFullYear();
  var html="<div class=\"gtp-yr-hm-expand-date\">"+label+"</div>";
  evArr.forEach(function(ev){
    var c=ev.getAttribute("data-c")||"";
    if(gtpSel[c])return;
    var summary=ev.getAttribute("data-summary")||ev.textContent.trim();
    html+="<div class=\"gtp-yr-hm-expand-ev gc-"+c+"\">"+summary+"</div>";
  });
  expand.innerHTML=html;
  expand.hidden=false;
  sq.classList.add("gtp-yr-hm-sq--active");
}

function gtpHeatmapToggle(btn){
  var yearWrap=document.querySelector(".gtp-year-wrap");
  if(!yearWrap)return;
  var isHeatmap=yearWrap.classList.toggle("gtp-year-wrap--heatmap");
  var label=btn.querySelector(".gtp-view-toggle-label");
  if(label)label.textContent=isHeatmap?"Tabelle":"Heatmap";
}

document.addEventListener("click",function(e){
  var sq=e.target.closest(".gtp-yr-hm-sq");
  if(sq&&sq.dataset.valid!=="0"&&sq.dataset.cats){
    gtpHmExpand(sq);
  }
});
```

- [ ] **Schritt 3: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 4: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(js): Heatmap-Funktionen (paint, expand, filter, toggle)"
```

---

## Task 5: JS — Filter-Integration + DOMContentLoaded-Init

**Files:**
- Modify: `plugin/gsh-terminplan.php` — `gtpApply()` + DOMContentLoaded-Handler

**Zwei separate Stellen:**

### 5a: `gtpApplyFilter()` in `gtpApply()` einhängen

- [ ] **Schritt 1: Ende von `gtpApply()` finden** (ca. Zeile 6308):

```js
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = hiddenCount > 0 ? "" : "none";
}
```

- [ ] **Schritt 2: `gtpHmApplyFilter()` vor der schließenden `}` einfügen:**

```js
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = hiddenCount > 0 ? "" : "none";
  /* Heatmap-Kacheln dimmen wenn alle Kategorien gefiltert */
  gtpHmApplyFilter();
}
```

### 5b: `gtpHmPaintTiles()` im DOMContentLoaded-Handler aufrufen

- [ ] **Schritt 3: DOMContentLoaded-Handler finden** (ca. Zeile 6510):

```js
document.addEventListener("DOMContentLoaded",function(){
  gtpChangesInit();
  /* Filter-Zustand aus localStorage wiederherstellen */
```

- [ ] **Schritt 4: `gtpHmPaintTiles()` direkt nach `gtpChangesInit()` einfügen:**

```js
document.addEventListener("DOMContentLoaded",function(){
  gtpChangesInit();
  gtpHmPaintTiles();
  /* Filter-Zustand aus localStorage wiederherstellen */
  try{
    var saved = localStorage.getItem("gtp_filters");
    if(saved){ gtpSel = JSON.parse(saved); gtpApply(); }
  }catch(e){}
});
```

- [ ] **Schritt 5: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Schritt 6: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(js): Heatmap Filter-Integration + DOMContentLoaded-Init"
```

---

## Task 6: Version bump

**Files:**
- Modify: `plugin/gsh-terminplan.php` — Plugin-Header, `define()`, Changelog

- [ ] **Schritt 1: Plugin-Header-Version ändern**

Im Plugin-Header (Zeile ~5–10):
```
Version: 4.3.4
```
→
```
Version: 4.4.0
```

- [ ] **Schritt 2: Konstante ändern**

```php
define('GSH_TP_VERSION', '4.3.4')
```
→
```php
define('GSH_TP_VERSION', '4.4.0')
```

- [ ] **Schritt 3: Changelog-Eintrag oben in `gsh_tp_changelog()` einfügen**

```php
array(
    'version' => '4.4.0',
    'date'    => '2026-05-24',
    'changes' => array(
        'Mobile Jahresansicht: Heatmap-Streifen mit Inline-Expand',
        'Desktop/Tablet: optionaler Heatmap-Toggle-Button',
        'Kategorie-Filter filtert Heatmap-Kacheln (Opacity)',
    ),
),
```

- [ ] **Schritt 4: Changelog im Plugin-Header-Kommentar (oben in der Datei) ergänzen**

Zeile suchen wo `4.3.4` aufgeführt ist, darüber einfügen:
```
 * v4.4.0 – Mobile Heatmap-Streifen-Jahresansicht
```

- [ ] **Schritt 5: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

- [ ] **Schritt 6: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "chore(plugin): v4.4.0 — Mobile Heatmap-Jahresansicht"
```

---

## Task 7: Manuelle Verifikation

- [ ] **Plugin in WordPress hochladen** (beide Dateien: `gsh-terminplan.php` + `gsh-terminplan.css`)

- [ ] **Mobile (≤768px) prüfen:**
  - Jahresansicht öffnen → Heatmap sichtbar, Tabelle ausgeblendet
  - Kacheln ohne Events: neutrale Farbe
  - Kacheln mit 1 Kategorie: Kategorie-Farbe
  - Kacheln mit 2+ Kategorien: Gradient
  - Heutiger Tag: blauer Outline
  - Tippen auf leere Kachel: nichts passiert
  - Tippen auf Kachel mit Events: Inline-Expand öffnet sich
  - Tippen auf dieselbe Kachel: Expand schließt sich
  - Kategorie-Filter aktivieren → Kacheln ohne sichtbare Kategorie dimmen auf 20%

- [ ] **Desktop (>768px) prüfen:**
  - Heatmap unsichtbar, Tabelle wie vorher
  - Jahresansicht-Button klicken → neuer „Heatmap"-Button erscheint
  - „Heatmap" klicken → Tabelle aus, Heatmap ein, Button-Label → „Tabelle"
  - „Tabelle" klicken → zurück zur Tabelle

- [ ] **Revert-Test:**
  - CSS-Heatmap-Block entfernen → Tabelle wieder sichtbar auf Mobile ✓
