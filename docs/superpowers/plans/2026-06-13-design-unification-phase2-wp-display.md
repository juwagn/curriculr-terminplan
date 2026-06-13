# Design-Vereinheitlichung Phase 2 — WP-Display flach Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Das WP-Display-Frontend (`[gsh_terminplan]`) auf den flachen Planner-Papier-Look (Stil B) umstellen — Glas-Optik raus, Tokens als Planner-Spiegel — und den ZIP-Build so reparieren, dass CSS mitdeployt.

**Architecture:** Reine Skin-Änderung im WP-Plugin. Token-Werte in `design-tokens.css` an die Planner-Tokens angeglichen; drei CSS-Regeln in `gsh-terminplan.css` von Glas/Pille/schwerem Schatten auf flach/eckig/dezent umgestellt. Markup, iCal-Parser, Tabellen-Rendering, PDF-Export bleiben unangetastet. Version-Bump + ZIP-Build inkl. `assets/`.

**Tech Stack:** WordPress-Plugin (PHP), CSS Custom Properties. Kein Build-System. Tests: dependency-freie PHP-Skripte (`php tests/curriculr/test-*.php`).

**Spec:** [docs/superpowers/specs/](../../../curriculr-planner/docs/superpowers/specs/2026-06-13-design-unification-planner-wp.md) (im SPA-Repo)

**Entscheidungen:** Stil B (flach). Token manuell gespiegelt. ZIP soll `assets/` enthalten.

**Constraint (WP-Repo CLAUDE.md):** Nicht anfassen — iCal-Parser, `gsh_tp_build_date_index`, Tabellen-Rendering, PDF-Export, Change-Notification, strukturelle Form von `gsh_tp_js()`/`gsh_tp_css()`. Diese Phase berührt CSS-Dateien + Versions-Strings + Build-Doku.

---

## File Structure

| Datei | Verantwortung | Aktion |
|-------|---------------|--------|
| `plugin/assets/css/design-tokens.css` | Token-Quelle (Spiegel von Planner) | Modify |
| `plugin/assets/css/gsh-terminplan.css` | Display-Skin | Modify (3 Regeln) |
| `plugin/gsh-terminplan.php` | Version (Header + define + Changelog) | Modify |
| `CLAUDE.md` (WP-Repo) | ZIP-Build-Befehl | Modify |
| `../CLAUDE.md` (Workspace-Root) | ZIP-Build-Befehl | Modify |

---

## Task 1: Design-Tokens an Planner angleichen

**Files:**
- Modify: `plugin/assets/css/design-tokens.css`

**Problem:** WP-Tokens divergieren vom Planner: Glas-Flächen (`--bg-surface: rgba(255,255,255,.85)` + `--glass-blur: blur(16px)`), Pillen-Buttons (`--radius-btn: 9999px`), Pillen-Badges (`--radius-badge: 9999px`), schwerer Card-Schatten (`--shadow-card: 0 18px 40px`). Ziel: flache Planner-Werte, plus kanonische `--marine-*`-Namen als Vorwärts-Kompatibilität.

- [ ] **Step 1: Header-Kommentar als Spiegel markieren**

In `plugin/assets/css/design-tokens.css` den Kopf-Kommentar von:
```css
/*
 * Curricu:lr Design Tokens
 * Einzige Quelle aller CSS Custom Properties.
 * Niemals direkt Hex-Werte in anderen CSS-Dateien verwenden.
 */
```
ändern zu:
```css
/*
 * Curricu:lr Design Tokens
 * Einzige Quelle aller CSS Custom Properties.
 * Niemals direkt Hex-Werte in anderen CSS-Dateien verwenden.
 *
 * SPIEGEL von curriculr-planner/src/styles/globals.css (@theme).
 * Bei Änderung von Brand-Tokens (Farben/Radien/Schatten) dort mit-aktualisieren.
 */
```

- [ ] **Step 2: Kanonische marine-* Aliase ergänzen**

Im `:root`-Block direkt nach dem `/* Brand Blues */`-Block (nach `--primary-100: #E6F4FF;`) einfügen:
```css

  /* Kanonische Marine-Skala (Spiegel der Planner-Namen; gleiche Hex wie primary-*) */
  --marine-900: #001F35;
  --marine-800: #00345C; /* = --primary-900 */
  --marine-700: #00467D; /* = --primary-700 */
  --marine-500: #0058A0; /* = --primary-500 */
  --marine-100: #E6F4FF; /* = --primary-100 */
```

- [ ] **Step 3: Flächen solide (Glas raus)**

`--bg-surface` von:
```css
  --bg-surface: rgba(255, 255, 255, 0.85);
```
zu:
```css
  --bg-surface: #FFFFFF;
```

- [ ] **Step 4: Radius flach**

`--radius-btn` von `9999px` auf `10px`, `--radius-badge` von `9999px` auf `3px`:
```css
  --radius-btn: 10px;
```
```css
  --radius-badge: 3px;
```

- [ ] **Step 5: Schatten dezent + Modal-Token ergänzen**

Den `/* Shadows */`-Block von:
```css
  /* Shadows */
  --shadow-card: 0 18px 40px rgba(15, 23, 42, 0.15);
  --shadow-btn: 0 2px 8px rgba(0, 70, 125, 0.25);
  --shadow-focus: 0 0 0 3px rgba(0, 70, 125, 0.25);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
```
zu:
```css
  /* Shadows */
  --shadow-card: 0 1px 2px rgb(0 0 0 / 0.05);
  --shadow-modal: 0 18px 40px rgba(15, 23, 42, 0.15);
  --shadow-btn: 0 2px 8px rgba(0, 70, 125, 0.25);
  --shadow-focus: 0 0 0 3px rgba(0, 70, 125, 0.25);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
```

- [ ] **Step 6: Glas-Token-Shorthand entfernen**

Den kompletten Block am Dateiende entfernen:
```css

  /* Glass card shorthand */
  --glass-bg: var(--bg-surface);
  --glass-border: 1px solid var(--border-subtle);
  --glass-blur: blur(16px);
```
(Die einzige Verwendung dieser Tokens ist `.gtp-card` in `gsh-terminplan.css` und wird in Task 2 entfernt.)

- [ ] **Step 7: Verifizieren — keine Glas-Token-Referenzen mehr**

Run:
```bash
grep -rn "glass-bg\|glass-blur\|glass-border" plugin/assets/css/
```
Expected nach Task 1: nur noch Treffer in `gsh-terminplan.css` (`.gtp-card`), keine in `design-tokens.css`. (Werden in Task 2 entfernt.)

- [ ] **Step 8: Commit**
```bash
git add plugin/assets/css/design-tokens.css
git commit -m "style(tokens): WP-Display-Tokens an Planner angleichen (flach, marine-Aliase)"
```

---

## Task 2: Display-CSS flach (3 Regeln)

**Files:**
- Modify: `plugin/assets/css/gsh-terminplan.css`

**Problem:** Drei Regeln tragen noch den Glas-/Pillen-Look: `.gtp-card` (Glas + Blur), `.gtp-card:hover` (schwerer Schatten + großer Lift), `.gtp-tab[aria-selected]` (blauer Pillen-Fill statt Gelb-Underline).

- [ ] **Step 1: `.gtp-card` flach**

Die Regel von:
```css
.gtp-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);
    border: var(--glass-border);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: var(--space-lg);
    transition: transform var(--transition), box-shadow var(--transition);
}
```
zu:
```css
.gtp-card {
    background: var(--bg-surface-solid);
    border: 1px solid var(--border-strong);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: var(--space-lg);
    transition: transform var(--transition), box-shadow var(--transition);
}
```
(`--bg-surface-solid` wird im Dark-Mode-Block bereits auf `#1e293b` überschrieben — Dark Mode bleibt funktionsfähig.)

- [ ] **Step 2: `.gtp-card:hover` dezent**

Von:
```css
.gtp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
}
```
zu:
```css
.gtp-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
```

- [ ] **Step 3: Aktiver Quartal-Tab — Gelb-Underline, kein Blau-Fill**

Von:
```css
.gtp-tab[aria-selected="true"] {
    color: var(--primary-500, #0058A0);
    border-bottom-color: var(--primary-500, #0058A0);
    background: var(--primary-100, #E6F4FF);
    box-shadow: none;
    font-weight: 700;
}
```
zu:
```css
.gtp-tab[aria-selected="true"] {
    color: var(--primary-900, #00345C);
    border-bottom-color: var(--accent-warning, #FFC857);
    background: transparent;
    box-shadow: none;
    font-weight: 700;
}
```

- [ ] **Step 4: Verifizieren — keine Glas-Referenzen mehr im gesamten CSS**

Run:
```bash
grep -rn "glass-bg\|glass-blur\|glass-border\|backdrop-filter" plugin/assets/css/gsh-terminplan.css
```
Expected: keine Ausgabe (leer).

- [ ] **Step 5: Commit**
```bash
git add plugin/assets/css/gsh-terminplan.css
git commit -m "style(display): flacher Planner-Look — solide Karten, Gelb-Underline-Tabs, dezente Schatten"
```

---

## Task 3: Version-Bump 4.13.0 → 4.14.0

**Files:**
- Modify: `plugin/gsh-terminplan.php` (3 Stellen: Header-Version, define, Changelog-Block + Changelog-Funktion = zusammen die 4 Pflichtstellen)

- [ ] **Step 1: Header-Version (Zeile 6)**

`* Version:     4.13.0` → `* Version:     4.14.0`

- [ ] **Step 2: define (Zeile ~538)**

`define( 'GSH_TP_VERSION',       '4.13.0' );` → `define( 'GSH_TP_VERSION',       '4.14.0' );`

- [ ] **Step 3: Header-Changelog-Block**

Direkt über der Zeile `* Changelog 4.13.0:` einfügen:
```
 * Changelog 4.14.0:
 * - [DESIGN] Display-Frontend auf flaches Planner-Papier-Design vereinheitlicht — Glas-Optik entfernt, solide Karten mit dezentem Schatten, Quartal-Tabs mit Gelb-Akzent-Unterstreichung, eckigere Buttons/Badges; Design-Tokens als Spiegel der Planner-Tokens
 * - [INFRA]  ZIP-Build enthält jetzt assets/ — CSS deployt mit dem Plugin-ZIP
 *
```

- [ ] **Step 4: Changelog-Funktion `gsh_tp_changelog()`**

Im `return array(` von `gsh_tp_changelog()` als ERSTES Array-Element (vor dem `4.13.0`-Eintrag) einfügen:
```php
        array(
            'version'  => '4.14.0',
            'entries'  => array(
                array( 'tag' => 'DESIGN', 'text' => 'Display-Frontend auf flaches Planner-Papier-Design vereinheitlicht — Glas-Optik entfernt, solide Karten, Quartal-Tabs mit Gelb-Akzent-Unterstreichung, eckigere Buttons/Badges' ),
                array( 'tag' => 'INFRA',  'text' => 'ZIP-Build enthält jetzt assets/ — CSS deployt mit dem Plugin-ZIP' ),
            ),
        ),
```

- [ ] **Step 5: PHP-Syntax + Tests**

Run:
```bash
php -l plugin/gsh-terminplan.php
for t in tests/curriculr/test-*.php; do php "$t" || echo "FAIL: $t"; done
```
Expected: „No syntax errors"; alle Tests exit 0 (keine FAIL-Zeile).

- [ ] **Step 6: Commit**
```bash
git add plugin/gsh-terminplan.php
git commit -m "chore: version 4.14.0 — Display-Design-Vereinheitlichung Phase 2"
```

---

## Task 4: ZIP-Build inkl. assets/ + Doku

**Files:**
- Modify: `CLAUDE.md` (WP-Repo) — Build-Befehl
- Modify: `../CLAUDE.md` (Workspace-Root) — Build-Befehl
- Create: `../curriculr-terminplan-4.14.0.zip` (Build-Artefakt, nicht committet)

**Problem:** Bestehender Build (`zip -j … 4 PHP-Dateien`) erzeugt eine flache ZIP ohne `assets/css/`. Eine CSS-Änderung erreicht so nie die Produktion. Fix: PHP-Dateien im ZIP-Root + `assets/`-Verzeichnis mitnehmen.

- [ ] **Step 1: Build-Befehl im WP-Repo-CLAUDE.md aktualisieren**

In `CLAUDE.md` (WP-Repo) den Abschnitt mit dem `zip -j …`-Befehl ersetzen durch:
````markdown
To build a new ZIP (PHP at root + assets/ subdir):
```bash
cd curriculr-terminplan/plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oP "'\K[^']+")
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
```
ZIP-Inhalt: 4 PHP-Dateien im Root + `assets/css/design-tokens.css` + `assets/css/gsh-terminplan.css`.
````

- [ ] **Step 2: Build-Befehl im Workspace-Root-CLAUDE.md aktualisieren**

In `../CLAUDE.md` (Workspace-Root) den „Plugin ZIP Deployment"-Abschnitt anpassen: ZIP-Inhalt ist jetzt nicht mehr flach — 4 PHP-Dateien im Root **plus** `assets/css/` Unterverzeichnis. Den Build-Befehl identisch zu Step 1 ersetzen (statt des alten `zip -j …`).

- [ ] **Step 3: ZIP bauen**

Run:
```bash
cd plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oP "'\K[^']+")
rm -f ../../curriculr-terminplan-$VER.zip
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
cd ..
```

- [ ] **Step 4: ZIP-Inhalt verifizieren**

Run:
```bash
unzip -l ../curriculr-terminplan-4.14.0.zip
```
Expected: enthält `gsh-terminplan.php`, `curriculr-data-layer.php`, `curriculr-auth.php`, `curriculr-guard.php`, `assets/css/design-tokens.css`, `assets/css/gsh-terminplan.css`.

- [ ] **Step 5: Commit (nur CLAUDE.md-Doku; ZIP ist Artefakt)**
```bash
git add CLAUDE.md ../CLAUDE.md
git commit -m "docs: ZIP-Build enthält assets/ (CSS deployt mit dem Plugin)"
```
> Hinweis: Die `.zip` liegt im Workspace-Root (außerhalb des WP-Repos) und ist kein Git-Artefakt dieses Repos — nicht committen, nur als Deploy-Datei erzeugen.

---

## Self-Review

**Spec-Abdeckung (Phase 2):**
- Glas → flach (`bg-surface` solid, `.gtp-card` ohne Blur/Glas) → Task 1 + Task 2 ✓
- Pillen-Buttons → 10px (`--radius-btn`) → Task 1 ✓
- Event-Badges → 3px (`--radius-badge`) → Task 1 ✓
- Schwerer Card-Schatten → dezent (`--shadow-card`), Heavy nur als `--shadow-modal` → Task 1 ✓
- Quartal-Tabs Underline + Gelb → Task 2 Step 3 ✓
- Marine-Aliase + Spiegel-Kommentar → Task 1 ✓
- Version + Changelog → Task 3 ✓
- ZIP inkl. assets/ (Deployment-Gap) → Task 4 ✓

**Platzhalter-Scan:** keine TBD/TODO; alle Edits mit exaktem Vorher/Nachher-Block; Changelog-Inhalte konkret im Plan.

**Konsistenz:** Versionsnummer `4.14.0` identisch über Header/define/Changelog/ZIP-Dateiname. `--shadow-modal` in Task 1 definiert, in keiner Task referenziert (bewusst — Vorrat für Overlays; kein toter Verweis). `.gtp-card` nutzt `--bg-surface-solid` (existiert, Dark-Mode überschreibt es bereits).

**Nicht im Scope:** iCal-Parser, Tabellen-Rendering, PDF, Change-Notification, `gsh_tp_js()`/`gsh_tp_css()`-Struktur, WP-Admin-IA (= Phase 3).
