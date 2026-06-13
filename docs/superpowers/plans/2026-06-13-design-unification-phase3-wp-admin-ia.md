# Design-Vereinheitlichung Phase 3 — WP-Admin-IA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Die WP-Admin-Einstellungsseite neu organisieren — Schuljahr-Profil per Dropdown statt per Tab, funktionale Tabs sauber getrennt, die „Resterampe" `render_system_tab` in drei kohärente Tabs gesplittet, POST-Handler aus der Hauptfunktion extrahiert.

**Architecture:** `gsh_tp_settings_page()` ist heute eine ~374-Zeilen-Funktion mit 11 inline-POST-Handlern, einer Tab-Liste (pro Profil ein Tab + 4 Funktions-Tabs) und einem Content-Dispatch. Tab-Inhalte sind bereits in `gsh_tp_render_*_tab()`-Funktionen ausgelagert. Diese Phase: (1) `render_system_tab` in drei Funktionen splitten, (2) Nav auf feste Funktions-Tabs + Profil-Dropdown umbauen, (3) POST-Handler extrahieren, (4) Release.

**Tech Stack:** WordPress-Plugin (PHP), WP Settings/Admin UI (`nav-tab-wrapper`, `form-table`). Tests: dependency-freie PHP-Skripte. Kein UI-Test — Review + `php -l` + Test-Suite sind das Sicherheitsnetz.

**Spec:** [docs/superpowers/specs/](../../../curriculr-planner/docs/superpowers/specs/2026-06-13-design-unification-planner-wp.md) (SPA-Repo)

**Entscheidungen:** Option A (Dropdown). Profil-Config bleibt EIN Tab (eine Form, ein Speichern) — KEIN Split in Kalender-Quelle/Quartale (würde Teil-Speichern-Falle erzeugen). Finale Tabs: **Schuljahr-Profil / Kategorien / Curriculr-Sync / Kiosk / System & Logs** + Profil-Dropdown.

**Constraint (WP-Repo CLAUDE.md):** Nicht anfassen — iCal-Parser, `gsh_tp_build_date_index`, Tabellen-Rendering, PDF-Export, Change-Notification, `gsh_tp_js()`/`gsh_tp_css()`-Struktur. Diese Phase berührt nur die Admin-Settings-Funktionen + Version.

---

## File Structure

| Datei | Verantwortung | Aktion |
|-------|---------------|--------|
| `plugin/gsh-terminplan.php` | Admin-Settings-Page + Render-Funktionen + POST-Handler | Modify |

Alle Änderungen in dieser einen Datei (Admin-Bereich). Neue Funktionen werden neben den bestehenden `gsh_tp_render_*`-Funktionen platziert.

---

## Task 1: render_system_tab in drei Tabs splitten

**Files:** Modify `plugin/gsh-terminplan.php`

**Problem:** `gsh_tp_render_system_tab()` (Zeilen 3525–3829) bündelt 6 unzusammenhängende globale Sektionen. Aufteilen in drei kohärente Funktionen. Reine Code-Verschiebung — KEINE Logik-/Markup-Änderung an den verschobenen Sektionen.

**Sektions-Grenzen (aktuell, innerhalb 3525–3829):**
- Curriculr Planner-Sync: 3527–3588 (Form, `save_curriculr`)
- IServ-SSO: 3592–3628
- Datenschutz & Transparenz: 3630–3652
- Entwurf-Vorschau: 3656–3726 (Form, `save_draft`)
- IServ-Einbettung Kiosk: 3730–3814 (Form, `save_kiosk`)
- Shortcode-Verwendung: 3816–3823

- [ ] **Step 1: `gsh_tp_render_sync_tab()` anlegen**

Neue Funktion (direkt vor `gsh_tp_render_system_tab`): enthält den **Curriculr Planner-Sync**-Block (aktuell 3527–3588, inkl. `<h2>Curriculr Planner-Sync</h2>` bis `</form>` nach dem `submit_button('Curriculr-Sync speichern')`). Inhalt unverändert übernehmen.
```php
function gsh_tp_render_sync_tab() {
    ?>
    <!-- Curriculr Planner-Sync Block (verbatim aus altem render_system_tab) -->
    <?php
}
```

- [ ] **Step 2: `gsh_tp_render_kiosk_tab()` anlegen**

Neue Funktion: enthält **Entwurf-Vorschau** (3656–3726) + **IServ-Einbettung Kiosk** (3730–3814), inkl. der `<hr ... />` dazwischen (3728). Beide Formulare (`save_draft`, `save_kiosk`) verbatim übernehmen.

- [ ] **Step 3: `gsh_tp_render_system_tab()` auf System & Logs reduzieren**

Den Funktionskörper von `gsh_tp_render_system_tab()` ersetzen durch: **IServ-SSO** (3592–3628) + **Datenschutz** (3630–3652) + **Shortcode-Verwendung** (3816–3823) — verbatim — und am Ende die bestehenden Log-Tabs inline einbinden:
```php
    echo '<hr style="margin:24px 0" />';
    echo '<h2>Sync-Verlauf</h2>';
    gsh_tp_render_sync_log_tab();
    echo '<hr style="margin:24px 0" />';
    echo '<h2>Feedback-Log</h2>';
    gsh_tp_render_feedback_log_tab();
```
(Die Curriculr-Sync-, Entwurf- und Kiosk-Sektionen werden hier ENTFERNT, da sie nun in `render_sync_tab`/`render_kiosk_tab` leben.)

- [ ] **Step 4: Syntax prüfen**

Run: `php -l plugin/gsh-terminplan.php`
Expected: „No syntax errors detected".

- [ ] **Step 5: Verifizieren — Sektionen genau einmal vorhanden**

Run:
```bash
grep -c "Curriculr Planner-Sync\|Entwurf-Vorschau (Schulleitungsteam)\|IServ-Einbettung (Kiosk-Modus)\|IServ-SSO (Mehrbenutzer\|Shortcode-Verwendung" plugin/gsh-terminplan.php
```
Expected: jede Überschrift genau einmal (keine Duplikate durch versehentliches Kopieren statt Verschieben).

- [ ] **Step 6: Commit**
```bash
git add plugin/gsh-terminplan.php
git commit -m "refactor(admin): render_system_tab in sync/kiosk/system-Tabs splitten"
```

---

## Task 2: Nav auf Dropdown + feste Funktions-Tabs umbauen

**Files:** Modify `plugin/gsh-terminplan.php` (in `gsh_tp_settings_page()`, Bereich Tab-Aufbau 2836–2850 + Nav 2945–2962 + Dispatch 2964–2976)

**Problem:** Heute: ein Tab pro Profil + Funktions-Tabs gemischt. Ziel: feste Funktions-Tabs; Profil per Dropdown gewählt.

- [ ] **Step 1: Tab-Liste auf feste Funktions-Tabs umstellen**

Den Block „Tabs aufbauen" (2836–2850) ersetzen durch:
```php
    // ── Tabs (fest, funktional) ──
    $tabs = array(
        '_profile'    => 'Schuljahr-Profil',
        '_kategorien' => 'Kategorien',
        '_sync'       => 'Curriculr-Sync',
        '_kiosk'      => 'Kiosk',
        '_system'     => 'System &amp; Logs',
    );

    // Aktiver Tab (Whitelist gegen $tabs)
    $active_tab = sanitize_key( $_GET['tab'] ?? '' );
    if ( ! array_key_exists( $active_tab, $tabs ) ) {
        $active_tab = '_profile';
    }

    // Gewähltes Profil für den Profil-Tab (Default: aktives Profil)
    $sel_profile = sanitize_key( $_GET['profile'] ?? '' );
    $profile_ids = array_column( $profiles, 'id' );
    if ( ! in_array( $sel_profile, $profile_ids, true ) ) {
        $sel_profile = gsh_tp_active_profile_id();
    }
```

- [ ] **Step 2: Nav-Markup umbauen**

Die `<nav class="nav-tab-wrapper">` (2945–2962) ersetzen durch eine Version, die nur die festen Tabs rendert (kein „+ Neues Schuljahr" mehr in der Nav — das wandert in den Profil-Tab-Kopf):
```php
        <nav class="nav-tab-wrapper" style="margin-bottom:1.5rem">
            <?php foreach ( $tabs as $id => $label ) : ?>
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=gsh-terminplan&tab=' . rawurlencode( $id ) ) ); ?>"
                   class="nav-tab <?php echo ( $active_tab === $id ) ? 'nav-tab-active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </nav>
```

- [ ] **Step 3: Content-Dispatch umbauen**

Den Dispatch (2964–2976) ersetzen durch:
```php
        <?php
        if ( '_kategorien' === $active_tab ) {
            gsh_tp_render_kategorien_tab();
        } elseif ( '_sync' === $active_tab ) {
            gsh_tp_render_sync_tab();
        } elseif ( '_kiosk' === $active_tab ) {
            gsh_tp_render_kiosk_tab();
        } elseif ( '_system' === $active_tab ) {
            gsh_tp_render_system_tab();
        } else { // _profile
            gsh_tp_render_profile_chooser( $profiles, $sel_profile );
            gsh_tp_render_profile_tab( $sel_profile );
        }
        ?>
```

- [ ] **Step 4: Profil-Auswahl-Leiste `gsh_tp_render_profile_chooser()` anlegen**

Neue Funktion (neben den anderen Render-Funktionen). Dropdown zum Wechseln + „Neues Schuljahr"-Button (übernimmt das `gsh_tp_new_profile`-Formular aus der alten Nav, Zeilen 2952–2961):
```php
function gsh_tp_render_profile_chooser( $profiles, $sel_profile ) {
    ?>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
        <form method="get" style="margin:0;display:flex;align-items:center;gap:6px">
            <input type="hidden" name="page" value="gsh-terminplan" />
            <input type="hidden" name="tab" value="_profile" />
            <label for="gsh_tp_profile_sel" style="font-weight:600">Schuljahr:</label>
            <select id="gsh_tp_profile_sel" name="profile" onchange="this.form.submit()">
                <?php foreach ( $profiles as $p ) :
                    $suffix = ! empty( $p['is_draft'] ) ? ' (Entwurf)' : ( ! empty( $p['is_active'] ) ? ' (aktiv)' : '' );
                ?>
                    <option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( $sel_profile, $p['id'] ); ?>>
                        <?php echo esc_html( $p['label'] . $suffix ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php if ( count( $profiles ) < 5 ) : ?>
            <form method="post" style="margin:0;display:inline">
                <?php wp_nonce_field( 'gsh_tp_new_profile', 'gsh_tp_np_n' ); ?>
                <button type="submit" name="gsh_tp_new_profile" value="1" class="button"
                        style="color:#27ae60;border-color:#27ae60"
                        onclick="return confirm('Neues Schuljahr-Profil anlegen?')">
                    + Neues Schuljahr
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
```

- [ ] **Step 5: Syntax + Tests**

Run:
```bash
php -l plugin/gsh-terminplan.php
for t in tests/curriculr/test-*.php; do php "$t" >/dev/null 2>&1 || echo "FAIL: $t"; done
```
Expected: „No syntax errors"; keine FAIL.

- [ ] **Step 6: Commit**
```bash
git add plugin/gsh-terminplan.php
git commit -m "feat(admin): Profil per Dropdown statt Tabs, feste Funktions-Tabs"
```

---

## Task 3: POST-Handler extrahieren

**Files:** Modify `plugin/gsh-terminplan.php` (POST-Handler-Block 2617–2834 in `gsh_tp_settings_page()`)

**Problem:** 11 inline `if ( isset( $_POST[...] ) ) {...}`-Blöcke blähen `gsh_tp_settings_page()` auf. Extrahieren in benannte Handler + Dispatcher. **Reiner Move — identische Logik.** Achtung: einige Handler lesen/mutieren die lokale `$profiles`-Variable → per Referenz übergeben.

- [ ] **Step 1: Handler-Funktionen anlegen**

Für jeden der 11 Blöcke eine Funktion `gsh_tp_handle_<name>( &$profiles )` direkt vor `gsh_tp_settings_page()`. Body verbatim aus dem jeweiligen `if`-Block übernehmen (nur den Rumpf, ohne das `if ( isset(...) )`). Handler, die `$profiles` nicht nutzen, dürfen den Parameter trotzdem führen (einheitliche Signatur). Mapping POST-Key → Funktion:
| POST-Key | Funktion |
|----------|----------|
| `gsh_tp_new_profile` | `gsh_tp_handle_new_profile` |
| `gsh_tp_save_profile` | `gsh_tp_handle_save_profile` |
| `gsh_tp_activate_profile` | `gsh_tp_handle_activate_profile` |
| `gsh_tp_save_draft` | `gsh_tp_handle_save_draft` |
| `gsh_tp_save_kiosk` | `gsh_tp_handle_save_kiosk` |
| `gsh_tp_save_curriculr` | `gsh_tp_handle_save_curriculr` |
| `gsh_tp_delete_profile` | `gsh_tp_handle_delete_profile` |
| `gsh_tp_sync` | `gsh_tp_handle_sync` |
| `gsh_tp_cc` | `gsh_tp_handle_clear_cache` |
| `gsh_tp_clear_feedback_log` | `gsh_tp_handle_clear_feedback_log` |
| `gsh_tp_clear_logs` | `gsh_tp_handle_clear_logs` |

- [ ] **Step 2: Dispatcher anlegen**
```php
function gsh_tp_settings_handle_post( &$profiles ) {
    $map = array(
        'gsh_tp_new_profile'        => 'gsh_tp_handle_new_profile',
        'gsh_tp_save_profile'       => 'gsh_tp_handle_save_profile',
        'gsh_tp_activate_profile'   => 'gsh_tp_handle_activate_profile',
        'gsh_tp_save_draft'         => 'gsh_tp_handle_save_draft',
        'gsh_tp_save_kiosk'         => 'gsh_tp_handle_save_kiosk',
        'gsh_tp_save_curriculr'     => 'gsh_tp_handle_save_curriculr',
        'gsh_tp_delete_profile'     => 'gsh_tp_handle_delete_profile',
        'gsh_tp_sync'               => 'gsh_tp_handle_sync',
        'gsh_tp_cc'                 => 'gsh_tp_handle_clear_cache',
        'gsh_tp_clear_feedback_log' => 'gsh_tp_handle_clear_feedback_log',
        'gsh_tp_clear_logs'         => 'gsh_tp_handle_clear_logs',
    );
    foreach ( $map as $key => $fn ) {
        if ( isset( $_POST[ $key ] ) ) {
            $fn( $profiles );
        }
    }
}
```

- [ ] **Step 3: Aufruf in settings_page einsetzen**

Den gesamten alten POST-Handler-Block (2617–2834) in `gsh_tp_settings_page()` ersetzen durch einen einzigen Aufruf nach dem Laden von `$profiles`:
```php
    gsh_tp_settings_handle_post( $profiles );
```

- [ ] **Step 4: Syntax + Tests + verifizieren keine Logik-Lücke**

Run:
```bash
php -l plugin/gsh-terminplan.php
for t in tests/curriculr/test-*.php; do php "$t" >/dev/null 2>&1 || echo "FAIL: $t"; done
grep -c "function gsh_tp_handle_" plugin/gsh-terminplan.php
```
Expected: „No syntax errors"; keine FAIL; Handler-Count = 11.

- [ ] **Step 5: Commit**
```bash
git add plugin/gsh-terminplan.php
git commit -m "refactor(admin): POST-Handler aus settings_page in benannte Funktionen + Dispatcher"
```

---

## Task 4: Version 4.14.0 → 4.15.0 + ZIP

**Files:** Modify `plugin/gsh-terminplan.php`; Build-Artefakt `../curriculr-terminplan-4.15.0.zip`

- [ ] **Step 1: Version (4 Stellen)**
- Header `* Version:     4.14.0` → `4.15.0`
- `define( 'GSH_TP_VERSION',       '4.14.0' )` → `4.15.0`
- Header-Changelog-Block über `* Changelog 4.14.0:` einfügen:
```
 * Changelog 4.15.0:
 * - [UX] Admin-Einstellungen neu organisiert — Schuljahr-Profil per Dropdown statt Tab, funktionale Tabs (Schuljahr-Profil/Kategorien/Curriculr-Sync/Kiosk/System & Logs); POST-Handler in benannte Funktionen extrahiert
 *
```
- `gsh_tp_changelog()` als erstes Array-Element:
```php
        array(
            'version'  => '4.15.0',
            'entries'  => array(
                array( 'tag' => 'UX', 'text' => 'Admin-Einstellungen neu organisiert — Schuljahr-Profil per Dropdown statt Tab, funktionale Tabs sauber getrennt; POST-Handler extrahiert' ),
            ),
        ),
```

- [ ] **Step 2: Syntax + Tests**
```bash
php -l plugin/gsh-terminplan.php
for t in tests/curriculr/test-*.php; do php "$t" >/dev/null 2>&1 || echo "FAIL: $t"; done
grep -c "4.15.0" plugin/gsh-terminplan.php
```
Expected: „No syntax errors"; keine FAIL; `4.15.0` in 4 Stellen.

- [ ] **Step 3: ZIP bauen (inkl. assets/, wie in Phase 2 etabliert)**
```bash
cd plugin
VER=$(grep "define.*GSH_TP_VERSION" gsh-terminplan.php | grep -oP "'\K[^']+")
rm -f ../../curriculr-terminplan-$VER.zip
zip ../../curriculr-terminplan-$VER.zip gsh-terminplan.php curriculr-data-layer.php curriculr-auth.php curriculr-guard.php
zip -r ../../curriculr-terminplan-$VER.zip assets/
cd ..
unzip -l ../curriculr-terminplan-4.15.0.zip | grep -E "assets/css|\.php"
```
Expected: 4 PHP + assets/css/*.css enthalten.

- [ ] **Step 4: Commit (nur PHP; ZIP = Artefakt, nicht committen)**
```bash
git add plugin/gsh-terminplan.php
git commit -m "chore: version 4.15.0 — Admin-IA-Umbau Phase 3"
```

---

## Self-Review

**Spec-Abdeckung (Phase 3, Option A):**
- Profil per Dropdown statt Tabs → Task 2 ✓
- Funktionale Tabs getrennt (Schuljahr-Profil/Kategorien/Curriculr-Sync/Kiosk/System & Logs) → Task 1 (Split) + Task 2 (Nav) ✓
- Per-Jahr vs. global getrennt → Profil-Tab (per-Jahr, via Dropdown) vs. übrige Tabs (global) ✓
- POST-Handler extrahiert → Task 3 ✓
- Version + ZIP → Task 4 ✓

**Bewusste Abweichung vom Mockup:** Profil-Config bleibt EIN Tab (eine Form) statt Split in Kalender-Quelle/Quartale — vom Nutzer bestätigt (Teil-Speichern-Falle vermeiden). „System & Logs" fasst System-Sektionen + Sync-Verlauf + Feedback-Log zusammen (eigene Top-Level-Log-Tabs entfallen).

**Platzhalter-Scan:** Move-Schritte sind anker-basiert (exakte Zeilen/Überschriften genannt) — bei reinem Code-Move ist Verschieben sicherer als Neu-Eintippen. Neuer Code (Dispatcher, Nav, Chooser, Tab-Liste) vollständig ausgeschrieben.

**Risiko/Konsistenz:** Kein UI-Test vorhanden → jeder Task mit `php -l` + voller Test-Suite + Spec-/Quality-Review abgesichert. Reihenfolge 1→2→3→4: Render-Splits zuerst, dann Nav (nutzt neue Funktionen), dann Handler-Cleanup (unabhängig), dann Release. `gsh_tp_active_profile_id()` und `gsh_tp_get_profiles()` existieren bereits (in der Datei genutzt). Dropdown navigiert per GET (kein neuer Handler).

**Nicht im Scope:** iCal-Parser, Tabellen-Rendering, PDF, Change-Notification, Frontend-CSS (= Phase 2), Profil-Form-Split.
