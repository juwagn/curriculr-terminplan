# Draft-Kiosk (Entwurf-Vorschau) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne, damit das Schulleitungsteam Entwürfe ohne WordPress-Login einsehen kann.

**Architecture:** Neuer separater `gsh_tp_draft_kiosk_token` in der WP-Options-DB. Neues Page-Template `page-terminplan-entwurf.php` validiert Token via `gsh_tp_check_draft_kiosk_access()`, setzt einen Request-Kontext via statische Variable in `gsh_tp_draft_kiosk_context()`, und ruft dann `do_shortcode('[gsh_terminplan schuljahr="entwurf"]')` auf — der Shortcode überspringt den Admin-Check wenn der Kontext gesetzt ist.

**Tech Stack:** PHP 8.0+, WordPress Options API, WordPress Transients API, WordPress Page Templates

---

## Dateien

| Datei | Aktion |
|---|---|
| `plugin/gsh-terminplan.php` | Modify — Option registrieren, neue Funktionen, Shortcode-Guards, Admin-UI, Uninstall-Cleanup, Version, Changelog |
| `plugin/page-terminplan-entwurf.php` | Create — Page-Template (User kopiert in aktives Theme) |

Kein CSS nötig — `.gtp-draft-banner` existiert bereits in `plugin/assets/css/gsh-terminplan.css:404`.

---

## Task 1: `gsh_tp_draft_kiosk_token` Option registrieren

**Files:**
- Modify: `plugin/gsh-terminplan.php:1801`

- [ ] **Schritt 1: Option nach `gsh_tp_kiosk_token` registrieren**

In `plugin/gsh-terminplan.php` nach Zeile 1801 einfügen (direkt nach dem `register_setting`-Block für `gsh_tp_kiosk_token`):

```php
    register_setting( 'gsh_tp_options', 'gsh_tp_draft_kiosk_token', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
```

Der Block ab Zeile 1798 sieht danach so aus:

```php
    register_setting( 'gsh_tp_options', 'gsh_tp_kiosk_token', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_draft_kiosk_token', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_iserv_domain', array(
```

- [ ] **Schritt 2: Option in Uninstall-Cleanup ergänzen**

In `plugin/gsh-terminplan.php` bei der `gsh_tp_uninstall()`-Funktion (Zeile ~7187). Die bestehende Array-Zeile:

```php
        'gsh_tp_kiosk_token',
```

Ergänzen zu:

```php
        'gsh_tp_kiosk_token',
        'gsh_tp_draft_kiosk_token',
```

- [ ] **Schritt 3: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

---

## Task 2: Neue Funktionen `gsh_tp_draft_kiosk_context()` und `gsh_tp_check_draft_kiosk_access()`

**Files:**
- Modify: `plugin/gsh-terminplan.php:1395` (nach `gsh_tp_check_kiosk_access()`)

- [ ] **Schritt 1: Zwei neue Funktionen nach `gsh_tp_check_kiosk_access()` einfügen**

`gsh_tp_check_kiosk_access()` endet bei ca. Zeile 1395 mit `}`. Direkt danach einfügen:

```php

/**
 * Liefert und setzt den Draft-Kiosk-Anfrage-Kontext.
 *
 * Einzelne Funktion mit statischer Variable — verhindert das Zustandsteilungs-
 * Problem zwischen zwei separaten Funktionen. Nach erfolgreichem Token-Check
 * auf true setzen, damit gsh_tp_shortcode() den Admin-Check überspringt.
 *
 * @since 4.1.0
 * @param bool $set true = Kontext aktivieren, false (Standard) = nur abfragen.
 * @return bool     Ob der Draft-Kiosk-Kontext aktiv ist.
 */
function gsh_tp_draft_kiosk_context( bool $set = false ): bool {
    static $active = false;
    if ( $set ) {
        $active = true;
    }
    return $active;
}

/**
 * Prüft den Entwurf-Kiosk-Zugriff per Token mit IP-basiertem Rate-Limiting.
 *
 * Vergleicht den übergebenen Token timing-sicher (hash_equals) mit dem gespeicherten
 * Entwurf-Token. Verhindert Brute-Force: Nach 10 Fehlversuchen von derselben IP
 * innerhalb einer Stunde wird der Zugriff blockiert.
 *
 * Aufruf aus dem Page-Template page-terminplan-entwurf.php:
 *   $token = sanitize_text_field( $_GET['token'] ?? '' );
 *   if ( ! gsh_tp_check_draft_kiosk_access( $token ) ) { status_header( 403 ); exit; }
 *
 * @since 4.1.0
 * @param  string $token Der vom Nutzer übergebene Token (?token= URL-Parameter).
 * @return bool          true bei gültigem Token, false bei falschem Token oder Rate-Limit.
 */
function gsh_tp_check_draft_kiosk_access( string $token ): bool {
    $saved = get_option( 'gsh_tp_draft_kiosk_token', '' );
    if ( empty( $saved ) ) {
        return false;
    }

    $ip       = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
    $rate_key = 'gsh_tp_draft_rl_' . md5( $ip );
    $attempts = (int) get_transient( $rate_key );
    if ( $attempts >= 10 ) {
        return false;
    }

    if ( ! hash_equals( $saved, $token ) ) {
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );
        return false;
    }

    gsh_tp_draft_kiosk_context( true );
    return true;
}
```

- [ ] **Schritt 2: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

---

## Task 3: Shortcode-Guards erweitern

**Files:**
- Modify: `plugin/gsh-terminplan.php:4060` und `plugin/gsh-terminplan.php:4091`

- [ ] **Schritt 1: Ersten Guard erweitern (Zeile 4060)**

Bestehender Code (Zeile 4058–4064):

```php
    if ( 'entwurf' === $atts['schuljahr'] ) {
        // Entwurf-Modus: nur für Admins sichtbar
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
                 . 'border-radius:8px;color:#475569;text-align:center">'
                 . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
        }
```

Ändern zu:

```php
    if ( 'entwurf' === $atts['schuljahr'] ) {
        // Entwurf-Modus: nur für Admins oder validierten Draft-Kiosk-Zugang sichtbar
        if ( ! current_user_can( 'manage_options' ) && ! gsh_tp_draft_kiosk_context() ) {
            return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
                 . 'border-radius:8px;color:#475569;text-align:center">'
                 . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
        }
```

- [ ] **Schritt 2: Zweiten Guard erweitern (Zeile 4091)**

Bestehender Code (Zeile 4090–4095):

```php
    // Entwurf-Modus: Entwürfe ohne schuljahr="entwurf" nur für Admins
    if ( ! empty( $profile['is_draft'] ) && ! current_user_can( 'manage_options' ) ) {
        return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
             . 'border-radius:8px;color:#475569;text-align:center">'
             . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
    }
```

Ändern zu:

```php
    // Entwurf-Modus: Entwürfe ohne schuljahr="entwurf" nur für Admins oder Draft-Kiosk
    if ( ! empty( $profile['is_draft'] ) && ! current_user_can( 'manage_options' ) && ! gsh_tp_draft_kiosk_context() ) {
        return '<div style="padding:1.5rem;background:#f1f5f9;border:1px solid #94a3b8;'
             . 'border-radius:8px;color:#475569;text-align:center">'
             . gsh_tp_icon( 'lock' ) . ' Dieser Terminplan ist noch nicht freigegeben.</div>';
    }
```

- [ ] **Schritt 3: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

---

## Task 4: Admin-UI — Entwurf-Vorschau-Sektion im System-Tab

**Files:**
- Modify: `plugin/gsh-terminplan.php:3239` (in `gsh_tp_render_system_tab()`)

- [ ] **Schritt 1: Neue Sektion vor dem IServ-Block einfügen**

In `gsh_tp_render_system_tab()` (Zeile ~3237). Die bestehende Zeile:

```php
    <h2>IServ-Einbettung (Kiosk-Modus)</h2>
```

Davor einfügen (also der gesamte neue Block kommt vor `<h2>IServ-Einbettung (Kiosk-Modus)</h2>`):

```php
    <h2>Entwurf-Vorschau (Schulleitungsteam)</h2>
    <div style="background:#eaf2f8;border-left:4px solid #2874a6;padding:12px 16px;margin-bottom:16px;border-radius:4px;">
        <strong>Was ist die Entwurf-Vorschau?</strong><br>
        Erm&ouml;glicht dem Schulleitungsteam, Entwurfs-Terminpl&auml;ne vorab einzusehen &ndash; ohne WordPress-Login.
        Teilt einfach den generierten Link.
    </div>
    <table class="form-table">
        <tr>
            <th><label for="gsh_tp_draft_kiosk_token">Entwurf-Token</label></th>
            <td>
                <input type="text" id="gsh_tp_draft_kiosk_token" name="gsh_tp_draft_kiosk_token"
                       value="<?php echo esc_attr( get_option( 'gsh_tp_draft_kiosk_token', '' ) ); ?>"
                       class="regular-text" autocomplete="off"
                       placeholder="mind. 20 Zeichen" />
                <button type="button" class="button" style="margin-left:6px"
                        onclick="if(!confirm('Token wird ersetzt. Alte Entwurf-Links funktionieren nicht mehr.'))return;document.getElementById('gsh_tp_draft_kiosk_token').value=Array.from(crypto.getRandomValues(new Uint8Array(24)),function(b){return b.toString(36);}).join('').slice(0,32);">
                    <?php echo gsh_tp_icon( 'dice' ); ?> Zuf&auml;lligen Token erzeugen
                </button>
                <p class="description">Geheimer Token f&uuml;r den Zugang zur Entwurf-Vorschau. Mind. 20 Zeichen empfohlen.</p>
                <?php
                $draft_token = get_option( 'gsh_tp_draft_kiosk_token', '' );
                if ( empty( $draft_token ) ) {
                    echo '<p style="color:#c0392b;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Kein Token gesetzt</strong> &ndash; '
                       . 'bitte einen Token generieren um die Vorschau zu aktivieren.</p>';
                } elseif ( strlen( $draft_token ) < 20 ) {
                    echo '<p style="color:#e67e22;margin-top:6px"><strong>' . gsh_tp_icon( 'alert-triangle' ) . ' Token zu kurz</strong> &ndash; '
                       . 'aus Sicherheitsgr&uuml;nden mind. 20 Zeichen verwenden.</p>';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>Vorschau-URL</th>
            <td>
                <?php
                $draft_token  = get_option( 'gsh_tp_draft_kiosk_token', '' );
                $draft_pages  = get_pages( array(
                    'meta_key'   => '_wp_page_template',
                    'meta_value' => 'page-terminplan-entwurf.php',
                ) );
                $has_draft_profile = false;
                foreach ( gsh_tp_get_profiles() as $p ) {
                    if ( ! empty( $p['is_draft'] ) ) {
                        $has_draft_profile = true;
                        break;
                    }
                }
                $missing = array();
                if ( empty( $draft_token ) ) {
                    $missing[] = 'Entwurf-Token';
                }
                if ( ! $has_draft_profile ) {
                    $missing[] = 'Profil mit Status &bdquo;Entwurf&ldquo;';
                }
                if ( empty( $draft_pages ) ) {
                    $missing[] = 'Seite mit Vorlage <code>page-terminplan-entwurf.php</code>';
                }
                if ( empty( $missing ) ) {
                    $draft_url = trailingslashit( get_permalink( $draft_pages[0]->ID ) ) . '?token=' . urlencode( $draft_token );
                    echo '<code style="display:block;padding:6px 10px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;word-break:break-all">'
                       . esc_html( $draft_url ) . '</code>';
                    echo '<a href="' . esc_url( $draft_url ) . '" target="_blank" rel="noopener" style="display:inline-block;margin-top:6px">'
                       . gsh_tp_icon( 'link' ) . ' Vorschau testen</a>';
                } else {
                    echo '<p style="color:#888;margin:0">' . gsh_tp_icon( 'alert-triangle' ) . ' Noch nicht verf&uuml;gbar &ndash; folgendes fehlt: '
                       . implode( ', ', $missing ) . '.</p>';
                }
                ?>
            </td>
        </tr>
    </table>
    <hr style="margin:24px 0" />

```

- [ ] **Schritt 2: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

---

## Task 5: Page-Template `page-terminplan-entwurf.php` erstellen

**Files:**
- Create: `plugin/page-terminplan-entwurf.php`

> **Hinweis für die Einrichtung:** Diese Datei muss in den Ordner des aktiven WordPress-Themes kopiert werden (z.B. `wp-content/themes/dein-theme/page-terminplan-entwurf.php`). Danach im WordPress-Backend eine neue Seite anlegen und die Vorlage „Terminplan Entwurf-Vorschau" auswählen.

- [ ] **Schritt 1: Template-Datei anlegen**

Datei `plugin/page-terminplan-entwurf.php` erstellen:

```php
<?php
/**
 * Template Name: Terminplan Entwurf-Vorschau
 *
 * Passwortloser Entwurf-Vorschau-Zugang für das Schulleitungsteam per Token-URL.
 * Token wird via gsh_tp_check_draft_kiosk_access() validiert (timing-sicher,
 * mit IP-basiertem Rate-Limiting).
 *
 * Einrichtung:
 *   1. Diese Datei in den aktiven Theme-Ordner kopieren.
 *   2. Im WordPress-Backend eine neue Seite anlegen.
 *   3. Als Seitenvorlage „Terminplan Entwurf-Vorschau" wählen.
 *   4. Im Plugin-Admin (Kiosk & System) einen Entwurf-Token generieren.
 *   5. Die angezeigte URL an das Schulleitungsteam weitergeben.
 *
 * @since 4.1.0
 */

// Zugriff ohne WordPress verweigern
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Token aus URL prüfen — vor jedem weiteren Output
$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
if ( ! function_exists( 'gsh_tp_check_draft_kiosk_access' ) || ! gsh_tp_check_draft_kiosk_access( $token ) ) {
    status_header( 403 );
    nocache_headers();
    exit;
}

nocache_headers();
get_header();
?>

<main style="max-width:1200px;margin:0 auto;padding:1rem">
    <div class="gtp-draft-banner" style="margin-bottom:1.5rem;border-radius:6px;padding:0.75rem 1.25rem;display:flex;align-items:center;gap:0.5rem">
        <?php echo gsh_tp_icon( 'alert-triangle' ); ?>
        <span><strong>Entwurf</strong> &ndash; dieser Terminplan ist noch nicht beschlossen.
        Bitte &Auml;nderungsw&uuml;nsche direkt an die Schulleitung melden.</span>
    </div>

    <?php echo do_shortcode( '[gsh_terminplan schuljahr="entwurf"]' ); ?>
</main>

<?php
get_footer();
```

- [ ] **Schritt 2: PHP-Syntaxprüfung der Template-Datei**

```bash
php -l plugin/page-terminplan-entwurf.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/page-terminplan-entwurf.php`

---

## Task 6: Version bump + Changelog

**Files:**
- Modify: `plugin/gsh-terminplan.php:1` (Header), `plugin/gsh-terminplan.php` (define + changelog-Funktion)

> Alle vier Stellen müssen synchron auf `4.1.0` gesetzt werden. Aktuell: `4.0.0`.

- [ ] **Schritt 1: Plugin-Header aktualisieren (Zeile 6)**

```
 * Version:     4.0.0
```
→
```
 * Version:     4.1.0
```

- [ ] **Schritt 2: `define('GSH_TP_VERSION', ...)` aktualisieren**

Suchen (grep):
```bash
grep -n "define.*GSH_TP_VERSION" plugin/gsh-terminplan.php
```

Den gefundenen Wert von `'4.0.0'` auf `'4.1.0'` ändern.

- [ ] **Schritt 3: Changelog im Plugin-Header ergänzen**

Direkt nach `* Changelog 4.0.0:` einen neuen Block einfügen:

```
 * Changelog 4.1.0:
 * - [FEATURE] Entwurf-Kiosk: Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne (Schulleitungsteam)
 * - [SECURITY] gsh_tp_check_draft_kiosk_access(): timing-sicherer Vergleich + Rate-Limiting (10/h/IP)
 * - [UX] Admin: Entwurf-Vorschau-Sektion im Kiosk & System Tab mit Token-Generator und URL-Anzeige
 *
```

- [ ] **Schritt 4: Changelog in `gsh_tp_changelog()` ergänzen**

Die Funktion `gsh_tp_changelog()` enthält ein Array mit Einträgen. Einen neuen Block ganz oben (vor dem `4.0.0`-Block) einfügen:

Suche nach dem Beginn des `4.0.0`-Blocks in der Changelog-Funktion:
```bash
grep -n "4\.0\.0" plugin/gsh-terminplan.php | grep -v "^\s*//"
```

Vor dem `4.0.0`-Array-Block einfügen:

```php
            array(
                'version'  => '4.1.0',
                'entries'  => array(
                    array( 'tag' => 'FEATURE',  'text' => 'Entwurf-Kiosk: Token-gesicherte Vorschau-Seite für Entwurfs-Terminpläne (Schulleitungsteam ohne WP-Login)' ),
                    array( 'tag' => 'SECURITY', 'text' => 'gsh_tp_check_draft_kiosk_access(): Timing-sicherer Token-Vergleich (hash_equals) + IP-Rate-Limiting (10/h)' ),
                    array( 'tag' => 'UX',       'text' => 'Admin Kiosk & System Tab: Entwurf-Vorschau-Sektion mit Token-Generator und automatischer URL-Anzeige' ),
                ),
            ),
```

- [ ] **Schritt 5: PHP-Syntaxprüfung**

```bash
php -l plugin/gsh-terminplan.php
```

Erwartete Ausgabe: `No syntax errors detected in plugin/gsh-terminplan.php`

---

## Task 7: Finaler Commit

- [ ] **Schritt 1: Syntaxprüfung beider Dateien**

```bash
php -l plugin/gsh-terminplan.php && php -l plugin/page-terminplan-entwurf.php
```

Erwartete Ausgabe:
```
No syntax errors detected in plugin/gsh-terminplan.php
No syntax errors detected in plugin/page-terminplan-entwurf.php
```

- [ ] **Schritt 2: Änderungen committen**

```bash
git add plugin/gsh-terminplan.php plugin/page-terminplan-entwurf.php
git commit -m "feat(plugin): add draft kiosk preview page for school leadership team (v4.1.0)"
```

---

## Einrichtungsanleitung (für Admin nach Deployment)

Nach dem Upload des Plugins:

1. `plugin/page-terminplan-entwurf.php` in den aktiven Theme-Ordner kopieren (z.B. `wp-content/themes/twentytwentyfour/`)
2. Im WP-Backend unter **Seiten → Neu**: Neue Seite anlegen, Vorlage „Terminplan Entwurf-Vorschau" wählen, Seite veröffentlichen
3. Im Plugin-Admin unter **Kiosk & System**: Entwurf-Token generieren, Einstellungen speichern
4. Sicherstellen, dass mind. ein Profil mit Status „Entwurf" existiert (Schuljahr-Tab → Beschlossen/Entwurf)
5. Die angezeigte Vorschau-URL an das Schulleitungsteam senden
