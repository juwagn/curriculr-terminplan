# Feedback v4.16.0 (iPad-Tabelle, Mobile-PDF) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the `[gsh_terminplan]` shortcode show the full scrollable table on tablets (iPad) and produce a usable PDF on touch devices.

**Architecture:** Two independent UX fixes in the WP plugin `curriculr-terminplan`. Item 3 (tablet table) is CSS + a markup wrapper: remove the conflicting stacked-card `@media` block from the enqueued stylesheet, wrap the table in a horizontal-scroll container, and drop the now-redundant `display:block` hack from the inline tablet CSS. Item 2 (mobile PDF) is JS: in `gtpPrint()`, open the generated A4-landscape document in a real new tab on touch devices instead of writing it into a hidden iframe (which iOS Safari mangles). Plus the standard 4-place version bump.

**Tech Stack:** PHP (WordPress plugin), vanilla CSS, vanilla JS. No build system, no npm, no Composer. Validate PHP with `php -l`. No browser test harness — UI behavior is verified manually.

**Note on TDD:** This repo's automated tests (`tests/curriculr/*.php`) cover the data-layer/auth/REST logic only; there is no harness for CSS/JS rendering. The closest "red/green" signal here is `php -l` (syntax) plus explicit manual verification steps. Follow the manual checks exactly — they are the acceptance gate.

**Working directory:** `curriculr-terminplan/` (the WP plugin repo). All paths below are relative to it.

---

### Task 1: Item 3 — Full scrollable table on tablets

Three edits across two files. The enqueued `.css` stacked-card block currently overrides the table on iPad (768–1200px) into hidden-header vertical cards; removing it lets the table render. A scroll wrapper keeps it usable on narrow widths without breaking column alignment.

**Files:**
- Modify: `plugin/assets/css/gsh-terminplan.css` (remove `@media (min-width:769px) and (max-width:1200px)` block, ~lines 650–687)
- Modify: `plugin/gsh-terminplan.php` — `gsh_tp_table()` return (line 5314); inline CSS in `gsh_tp_css()` (base `.gt` area and tablet `@media`, lines ~6262–6271)

- [ ] **Step 1: Remove the stacked-card block from the stylesheet**

In `plugin/assets/css/gsh-terminplan.css`, delete this entire block (it sits between the INTERMEDIATE comment header and the ACCESSIBILITY section):

```css
@media (min-width: 769px) and (max-width: 1200px) {
    .gt {
        display: block;
        width: 100%;
    }

    .gt thead { display: none; }
    .gt tbody { display: block; }

    .gt tr {
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius-card);
        margin-bottom: var(--space-sm);
        padding: var(--space-md);
        background: var(--bg-surface-solid);
        box-shadow: var(--shadow-sm);
        transition: box-shadow var(--transition);
    }

    .gt tr:hover {
        box-shadow: var(--shadow-card);
        background: var(--primary-100);
    }

    .gt td {
        border: none;
        padding: 3px 0;
    }

    .gt-sw tr,
    .gt tr.gt-sw {
        background: var(--primary-100);
        border-color: var(--primary-700);
    }
}
```

Also remove the now-orphaned comment block immediately above it:

```css
/* ═══════════════════════════════════════════════════════════════
   RESPONSIVE — INTERMEDIATE (769px – 1200px)
   Table collapses to stacked cards
   ═══════════════════════════════════════════════════════════════ */
```

- [ ] **Step 2: Wrap the table output in a horizontal-scroll container**

In `plugin/gsh-terminplan.php`, `gsh_tp_table()` ends at line 5313–5314:

```php
    $h .= '</tbody></table>';
    return $h;
}
```

Change the return so the table is wrapped:

```php
    $h .= '</tbody></table>';
    return '<div class="gtp-tbl-scroll">' . $h . '</div>';
}
```

- [ ] **Step 3: Add the scroll-wrapper CSS + drop the `display:block` hack (inline `gsh_tp_css()`)**

In `plugin/gsh-terminplan.php`, the inline tablet block currently reads (lines ~6262–6271):

```css
/* Responsive für Tablets */
@media(min-width:768px) and (max-width:1024px){
  .gtp{padding:1.25rem;border-radius:12px}
  .gt{display:block;overflow-x:auto}
  .gtp-tabs{margin:0 -1.25rem;padding:0 1.25rem;overflow-x:auto;flex-wrap:nowrap}
  .gtp-tab{white-space:nowrap;flex-shrink:0}
  .gtp-hd{flex-direction:column;align-items:flex-start}
  .gtp-filt-wrap{padding:.625rem .875rem}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
}
```

Replace it with (removes the column-breaking `.gt{display:block}`, adds a tablet `min-width` so the table scrolls instead of squishing):

```css
/* Responsive für Tablets */
@media(min-width:768px) and (max-width:1024px){
  .gtp{padding:1.25rem;border-radius:12px}
  .gtp-tbl-scroll .gt{min-width:46rem}
  .gtp-tabs{margin:0 -1.25rem;padding:0 1.25rem;overflow-x:auto;flex-wrap:nowrap}
  .gtp-tab{white-space:nowrap;flex-shrink:0}
  .gtp-hd{flex-direction:column;align-items:flex-start}
  .gtp-filt-wrap{padding:.625rem .875rem}
  .gtp-search{max-width:100%;width:100%}
  .gtp-search-input{width:100%}
}
```

Then add the base scroll-wrapper rule. Find the `.gtp-mob{display:none}` line in `gsh_tp_css()` (it is the line just before the `MOBILE AGENDA` comment, ~line 6101) and insert the wrapper rule immediately before it:

```css
.gtp-tbl-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
/* ── Mobile Agenda: auf Desktop versteckt ── */
.gtp-mob{display:none}
```

(Result: `.gtp-tbl-scroll` scrolls horizontally at any width; at 768–1024px the table keeps a `46rem` min-width so all 7 columns stay aligned and the user scrolls sideways; ≤767px the table is still `display:none` and the agenda shows.)

- [ ] **Step 4: Syntax-check PHP**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 5: Manual verification (browser DevTools, responsive mode)**

Load a page rendering `[gsh_terminplan]` (or open the rendered HTML). Set viewport to **800px wide** (iPad-portrait range):
- Expected: the **full table** with its dark header row (SW / Mo–Fr / Hinweise) is visible, NOT vertical stacked cards. If the table is wider than the viewport it scrolls horizontally inside its container; the rest of the page does not.

Set viewport to **375px** (phone):
- Expected: unchanged — the agenda/card list shows, no table.

Set viewport to **1300px** (desktop):
- Expected: unchanged — full table, no horizontal scroll.

- [ ] **Step 6: Commit**

```bash
git add plugin/assets/css/gsh-terminplan.css plugin/gsh-terminplan.php
git commit -m "$(cat <<'EOF'
fix(display): show full scrollable table on tablets (iPad)

Remove the .css stacked-card @media(769-1200px) block that overrode the
table into hidden-header vertical cards on iPad. Wrap the table in a
.gtp-tbl-scroll container and give it a tablet min-width so all columns
stay aligned and scroll horizontally instead of collapsing.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Item 2 — Usable PDF on phone/iPad (standalone print page)

`gtpPrint()` writes the generated A4-landscape HTML into a hidden 0×0 iframe and calls `iframe.print()`. iOS Safari ignores `@page{size:A4 landscape}` and squeezes the hidden-iframe output into an unreadable portrait preview. Fix: on touch devices, open the same HTML as a real top-level document in a new tab (with a fixed-width viewport so it renders at full landscape width and stays pinch-zoomable), and let the user invoke the browser's native "Als PDF sichern" / share. Desktop keeps the existing iframe auto-print path.

**Files:**
- Modify: `plugin/gsh-terminplan.php` — `gtpPrint()`, insert before the iframe creation (line ~7228, the `var frame=document.getElementById("gtp-print-frame");` line)

- [ ] **Step 1: Insert the touch-device branch**

In `plugin/gsh-terminplan.php`, locate this region inside `gtpPrint()` (after the legend is appended, just before the iframe is fetched/created):

```js
    body+=LEG;
  }

  var frame=document.getElementById("gtp-print-frame");
  if(!frame){
```

Insert the touch branch between the closing `}` and the `var frame` line, so it becomes:

```js
    body+=LEG;
  }

  /* Touch-Geräte (iPad/Handy): Hidden-iframe-Druck wird von iOS Safari
     verstümmelt (ignoriert @page landscape). Stattdessen das fertige
     A4-Querformat-Dokument in einem echten Tab öffnen – zoombar, und der
     Nutzer wählt selbst „Als PDF sichern" / Teilen. */
  var gtpTouch=(window.matchMedia&&window.matchMedia("(pointer:coarse)").matches)||window.innerWidth<=1024;
  if(gtpTouch){
    var fullDoc=[
      '<!DOCTYPE html>','<html lang="de">','<head>',
      '<meta charset="utf-8">',
      '<meta name="viewport" content="width=1100">',
      '<title>'+docTitle+'</title>',
      '<style>'+CSS+'</style>','</head>','<body>',body,'</body>','</html>'
    ].join('');
    var win=window.open("","_blank");
    if(win){
      win.document.open();
      win.document.write(fullDoc);
      win.document.close();
      return;
    }
    /* Popup blockiert → unten auf den iframe-Pfad zurückfallen */
  }

  var frame=document.getElementById("gtp-print-frame");
  if(!frame){
```

(The desktop iframe path below this insertion is unchanged.)

- [ ] **Step 2: Syntax-check PHP**

Run: `php -l plugin/gsh-terminplan.php`
Expected: `No syntax errors detected in plugin/gsh-terminplan.php`

- [ ] **Step 3: Manual verification — desktop unchanged**

In a desktop browser (or DevTools at ≥1300px with a mouse), click a PDF button ("📄 Quartal als PDF").
- Expected: the print dialog opens automatically as before (iframe path), A4 landscape, full table.

- [ ] **Step 4: Manual verification — touch path**

Emulate a touch device: DevTools → device toolbar → iPad (or any pointer:coarse / ≤1024px profile). Click the PDF button.
- Expected: a **new tab** opens showing the full A4-landscape document (header, table, legend) rendered at ~1100px width, zoomable — NOT a squeezed hidden-iframe print. No auto-print dialog. From there the browser's own "Als PDF / Teilen" produces a clean document.

(Optional, if a real iPad is available: confirm the new-tab document is readable and "Als PDF sichern" yields a landscape PDF.)

- [ ] **Step 5: Commit**

```bash
git add plugin/gsh-terminplan.php
git commit -m "$(cat <<'EOF'
fix(pdf): open standalone print page on touch devices

iOS Safari mangles the hidden-iframe print (ignores @page landscape),
making the PDF unusable on iPad/phone. On pointer:coarse / <=1024px,
open the same A4-landscape document in a real tab at fixed 1100px width
so it renders correctly and the user uses native "Als PDF / Teilen".
Desktop keeps the existing iframe auto-print path.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Version bump 4.15.0 → 4.16.0 (4 places + changelog)

Per `CLAUDE.md`, the version lives in 4 places that must stay in sync. Minor bump (new UX behavior).

**Files:**
- Modify: `plugin/gsh-terminplan.php` — header `Version:` (line 6); `define('GSH_TP_VERSION', ...)` (line 545); header changelog block (insert above line 10); `gsh_tp_changelog()` first entry (insert above line 789)

- [ ] **Step 1: Bump the header `Version:` field**

Line 6:

```php
 * Version:     4.15.0
```

→

```php
 * Version:     4.16.0
```

- [ ] **Step 2: Bump the `GSH_TP_VERSION` constant**

Line 545:

```php
define( 'GSH_TP_VERSION',       '4.15.0' );
```

→

```php
define( 'GSH_TP_VERSION',       '4.16.0' );
```

- [ ] **Step 3: Prepend the header-comment changelog entry**

The header changelog starts at line 10 (`* Changelog 4.15.0:`). Insert a new 4.16.0 block immediately before it, after the `* Text Domain: gsh-terminplan` line (line 9):

```php
 * Text Domain: gsh-terminplan
 * Changelog 4.16.0:
 * - [UX] iPad/Tablet zeigt die volle Tabelle (horizontal scrollbar) statt der Mobil-Kartenansicht
 * - [UX] PDF-Export auf Handy/iPad öffnet eine eigene Druckseite (zoombar, native „Als PDF") statt unleserlichem iframe-Druck
 * Changelog 4.15.0:
```

- [ ] **Step 4: Prepend the `gsh_tp_changelog()` array entry**

`gsh_tp_changelog()` (line 787) returns an array whose first element is the 4.15.0 block (lines 789–795). Insert the 4.16.0 block as the new first element, right after `return array(`:

```php
function gsh_tp_changelog() {
    return array(
        array(
            'version'  => '4.16.0',
            'entries'  => array(
                array( 'tag' => 'UX', 'text' => 'iPad/Tablet zeigt die volle Tabelle (horizontal scrollbar) statt der Mobil-Kartenansicht' ),
                array( 'tag' => 'UX', 'text' => 'PDF-Export auf Handy/iPad öffnet eine eigene Druckseite (zoombar, native „Als PDF sichern") statt unleserlichem iframe-Druck' ),
            ),
        ),
        array(
            'version'  => '4.15.0',
```

- [ ] **Step 5: Verify all 4 places report 4.16.0 and none still say 4.15.0**

Run:

```bash
grep -nE "Version:[[:space:]]+4\.16\.0|GSH_TP_VERSION',[[:space:]]+'4\.16\.0|Changelog 4\.16\.0|'version'[[:space:]]+=> '4\.16\.0" plugin/gsh-terminplan.php
```

Expected: 4 matching lines (header Version, constant, header changelog, changelog array).

Then confirm no stray current-version mismatch in the header/define:

```bash
grep -nE "Version:[[:space:]]+4\.15\.0|GSH_TP_VERSION',[[:space:]]+'4\.15\.0" plugin/gsh-terminplan.php
```

Expected: **no output** (both moved to 4.16.0; older `Changelog 4.15.0:` history lines are fine and expected to remain).

- [ ] **Step 6: Syntax-check + commit**

```bash
php -l plugin/gsh-terminplan.php
git add plugin/gsh-terminplan.php
git commit -m "$(cat <<'EOF'
chore: bump version to 4.16.0

iPad full-table display + mobile-PDF standalone page.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
EOF
)"
```

Expected from `php -l`: `No syntax errors detected in plugin/gsh-terminplan.php`

---

### Task 4: Run test suite + build deploy ZIP

Confirm nothing in the data-layer/auth tests broke (none touched, but cheap to verify), then build the upload ZIP per `CLAUDE.md`.

**Files:**
- Create: `curriculr-terminplan-4.16.0.zip` at the workspace root (`../` from the repo)

- [ ] **Step 1: Run the PHP test suite**

```bash
php tests/curriculr/test-auth.php && \
php tests/curriculr/test-guard.php && \
php tests/curriculr/test-ics.php && \
php tests/curriculr/test-stage.php && \
php tests/curriculr/test-revisions.php && \
php tests/curriculr/test-envelope.php && \
php tests/curriculr/test-integration-stubbed.php && \
php tests/curriculr/test-version.php && \
echo "ALL TESTS PASSED"
```

Expected: ends with `ALL TESTS PASSED` (each test exits 0; exit code 1 = failure).

- [ ] **Step 2: Build the deploy ZIP**

```bash
cd plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oE "'[0-9]+\.[0-9]+\.[0-9]+'" | tr -d "'")
echo "Building ZIP for version: $VER"
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
cd ..
```

Expected: `Building ZIP for version: 4.16.0`, and a new `curriculr-terminplan-4.16.0.zip` at the workspace root.

- [ ] **Step 3: Verify ZIP contents**

```bash
unzip -l ../curriculr-terminplan-4.16.0.zip
```

Expected: 4 PHP files at root (`gsh-terminplan.php`, `curriculr-data-layer.php`, `curriculr-auth.php`, `curriculr-guard.php`) + `assets/css/design-tokens.css` + `assets/css/gsh-terminplan.css`.

- [ ] **Step 4: Final manual deploy note (human action — not automated)**

The ZIP is uploaded manually in WP admin (no CI/CD). Leave it at the workspace root for the user to upload. Do not attempt to deploy.

---

## Post-implementation: reply to Item 1 feedback (no code)

After the plan is implemented, the user still owes the feedback-giver a reply for Item 1. Suggested content (German): the Entwurf-Vorschau page is already passwordless — an admin generates a token once in the Kiosk/System tab, and the resulting URL can be shared freely (e.g. via IServ navigation visibility); no password and no admin login are needed to *view* the draft. This is documentation/communication, not a code task.
