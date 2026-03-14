<?php
/**
 * Plugin Name: GSH Terminplan Dashboard
 * Plugin URI:  https://gesamtschule-horst.de
 * Description: Interaktive Quartalsuebersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     1.5.0
 * Author:      Gesamtschule Horst
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 *
 * Changelog 1.5:
 * - Auto-Scroll: Beim Laden der Seite wird sanft zur aktuellen Woche gescrollt
 *   (heutiger Tag im oberen Drittel des Viewports).
 * - Floating „Heute"-Button: Schwebt unten rechts, erscheint sobald die heutige
 *   Zeile aus dem Viewport gescrollt ist oder sich im falschen Quartal-Tab befindet.
 *   Klick scrollt sanft zurück und wechselt ggf. automatisch den Tab.
 * - Tab-Wechsel: Wechsel in das Quartal mit dem heutigen Datum scrollt automatisch
 *   zur Heute-Zeile.
 * - Interner Refactor: gtpSwitchTab() (reiner Tab-Wechsel) und gtpTab() (mit Scroll)
 *   sind jetzt getrennt, um Rekursion und Seiteneffekte zu vermeiden.
 *
 * Changelog 1.4:
 * - Filter: Neues Verhalten – erster Klick wechselt in Exklusiv-Modus (nur gewählte
 *   Kategorie sichtbar), weitere Klicks fügen Kategorien hinzu. Reset-Button blendet
 *   alle Termine wieder ein.
 * - Lange Termine (>= 5 Tage / ganze Woche oder länger) erscheinen NUR in der
 *   Hinweise-Spalte, nicht mehr in den einzelnen Tagesspalten. Datumsbereich wird
 *   in der Hinweise-Spalte mit angezeigt.
 * - Ferien-/Urlaubstermine markieren weiterhin Tagesspalten als Ferientag (grau),
 *   auch wenn ihr Text jetzt in der Hinweise-Spalte steht.
 *
 * Changelog 1.3:
 * - Professionelles Frontend-Design: Karten-Layout, moderne Tabs, farbige Kategorie-Filter.
 * - Manuelle Synchronisierung: Button in den Einstellungen leert Cache und ruft sofort neu ab.
 * - Letzter Sync-Zeitstempel wird gespeichert und im Header + Admin angezeigt.
 *
 * Changelog 1.2:
 * - Druckfunktion: Unsichtbarer iframe statt Popup – kein Popup-Blocker-Problem.
 * - Kategorie-Erkennung: Echtes Wortgrenzen-Matching (\b) statt strpos.
 * - Kategorie wird NUR aus dem CATEGORIES-Feld gelesen, NICHT aus dem Termintext.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GSH_TP_VERSION', '1.5.0' );
define( 'GSH_TP_SLUG', 'gsh-terminplan' );
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );

/* ================================================================
   1. ADMIN-EINSTELLUNGEN
   ================================================================ */

add_action( 'admin_menu', 'gsh_tp_admin_menu' );
function gsh_tp_admin_menu() {
    add_options_page(
        'GSH Terminplan',
        'GSH Terminplan',
        'manage_options',
        GSH_TP_SLUG,
        'gsh_tp_settings_page'
    );
}

add_action( 'admin_init', 'gsh_tp_register_settings' );
function gsh_tp_register_settings() {
    register_setting( 'gsh_tp_options', 'gsh_tp_ical_url', array(
        'sanitize_callback' => 'gsh_tp_sanitize_url',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_cache_duration', array(
        'sanitize_callback' => 'absint',
        'default'           => 3600,
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_schuljahr_start', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '2025-08-25',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_quartal_grenzen', array(
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17",
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_kategorie_mapping', array(
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => gsh_tp_default_mapping(),
    ) );
}

/**
 * Standard-Kategorie-Mapping.
 *
 * Format: Stichwort|Farbklasse (pro Zeile). Zeilen mit # sind Kommentare.
 */
function gsh_tp_default_mapping() {
    return implode( "\n", array(
        '# ──── Konferenzen (blau) ────',
        'Konferenz|konferenz',
        'LK|konferenz',
        'FaKo|konferenz',
        'Fachkonferenz|konferenz',
        'TBS|konferenz',
        'Dienstbesprechung|konferenz',
        'DB|konferenz',
        'PK|konferenz',
        'ZK|konferenz',
        'SK|konferenz',
        'SchiLF|konferenz',
        'Pflegschaft|konferenz',
        'Schulkonferenz|konferenz',
        'Teamsitzung|konferenz',
        'Teamtag|konferenz',
        'Teamnachmittag|konferenz',
        'Studientag|konferenz',
        '',
        '# ──── Prüfungen (rot) ────',
        'Prüfung|pruefung',
        'Pruefung|pruefung',
        'ZP|pruefung',
        'Abi|pruefung',
        'Klausur|pruefung',
        'Vera|pruefung',
        'DST|pruefung',
        'ZKL|pruefung',
        'mAP|pruefung',
        'ZAA|pruefung',
        'Sprachstandstest|pruefung',
        'Nachprüfung|pruefung',
        '',
        '# ──── Projekte (grün) ────',
        'Projekt|projekt',
        'ProWo|projekt',
        'Wandertag|projekt',
        'Sportspektakel|projekt',
        'Praktikum|projekt',
        'SV-Fahrt|projekt',
        'Studienfahrt|projekt',
        'Abschlussfahrt|projekt',
        'Fahrtenwoche|projekt',
        'Exkursion|projekt',
        'Turnier|projekt',
        'Olympiade|projekt',
        'Sponsorenlauf|projekt',
        'Friedenstag|projekt',
        'Antirassismustag|projekt',
        'Abifeier|projekt',
        'Entlassfeier|projekt',
        'Horst forscht|projekt',
        '',
        '# ──── Ferien / Frei (grau) ────',
        'Frei|frei',
        'Ferien|frei',
        'Feiertag|frei',
        'Ferientag|frei',
        'Rosenmontag|frei',
        'Weihnacht|frei',
        'Pfingsten|frei',
        'Himmelfahrt|frei',
        'Fronleichnam|frei',
        'Maifeiertag|frei',
        'Tag der Deutschen Einheit|frei',
        '',
        '# ──── Eltern / Beratung (orange) ────',
        'Eltern|eltern',
        'Beratung|eltern',
        'Info-Abend|eltern',
        'Infoabend|eltern',
        'Potenzialanalyse|eltern',
        'KAoA|eltern',
        'Kurswahl|eltern',
        'Anmeldung|eltern',
        'Tag der offenen Tür|eltern',
        'Schnuppertag|eltern',
        'Berufemarkt|eltern',
        'Lernberatungstag|eltern',
        '',
        '# ──── Fristen / Hinweise (gelb) ────',
        'Frist|frist',
        'Noten|frist',
        'Fachkommentar|frist',
        'Berichtszeugnis|frist',
        'Zeugnis|frist',
        'Evaluation|frist',
        'Foerderplaene|frist',
        'Förderpläne|frist',
        'Meldung mAP|frist',
    ) );
}

function gsh_tp_sanitize_url( $url ) {
    $url = esc_url_raw( trim( $url ) );
    if ( empty( $url ) ) {
        return '';
    }
    if ( strpos( $url, 'https://' ) !== 0 ) {
        add_settings_error( 'gsh_tp_ical_url', 'scheme',
            'Die iCal-URL muss HTTPS verwenden.', 'error' );
        return get_option( 'gsh_tp_ical_url', '' );
    }
    return $url;
}

/* ── Admin-Seite ── */

function gsh_tp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Zugriff verweigert.' );
    }

    // Cache leeren
    if ( isset( $_POST['gsh_tp_cc'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cn'] ?? '' ) ), 'gsh_tp_cc' ) ) {
        delete_transient( GSH_TP_CACHE_KEY );
        echo '<div class="notice notice-success"><p>Cache geleert.</p></div>';
    }

    // Kalender manuell synchronisieren
    if ( isset( $_POST['gsh_tp_sync'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_sn'] ?? '' ) ), 'gsh_tp_sync' ) ) {
        delete_transient( GSH_TP_CACHE_KEY );
        $fresh = gsh_tp_fetch_ical();
        if ( $fresh ) {
            echo '<div class="notice notice-success"><p>'
               . '&#10003; Kalender erfolgreich synchronisiert ('
               . esc_html( wp_date( 'd.m.Y, H:i' ) ) . ' Uhr).'
               . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>'
               . '&#10007; Synchronisierung fehlgeschlagen &ndash; '
               . 'bitte die iCal-URL prüfen oder warten, bis der IServ-Server erreichbar ist.'
               . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>GSH Terminplan &ndash; Einstellungen</h1>
        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'gsh_tp_options' ); ?>
            <table class="form-table">

                <tr>
                    <th><label for="gsh_tp_ical_url">iCal-Feed-URL</label></th>
                    <td>
                        <input type="url" id="gsh_tp_ical_url" name="gsh_tp_ical_url"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_ical_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://iserv.example.de/ical/..." />
                        <p class="description">HTTPS-URL des IServ-Kalender-Exports (.ics).</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_cache_duration">Cache-Dauer (Sek.)</label></th>
                    <td>
                        <input type="number" id="gsh_tp_cache_duration" name="gsh_tp_cache_duration"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_cache_duration', 3600 ) ); ?>"
                               min="300" max="86400" />
                        <p class="description">Standard: 3600 (1 Stunde). Min. 300, Max. 86400.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_schuljahr_start">Start Schulwoche 01</label></th>
                    <td>
                        <input type="date" id="gsh_tp_schuljahr_start" name="gsh_tp_schuljahr_start"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_schuljahr_start', '2025-08-25' ) ); ?>" />
                        <p class="description">Erster Montag nach den Sommerferien.</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_quartal_grenzen">Quartalsgrenzen</label></th>
                    <td>
                        <textarea id="gsh_tp_quartal_grenzen" name="gsh_tp_quartal_grenzen"
                                  rows="5" class="large-text"><?php
                            echo esc_textarea( get_option( 'gsh_tp_quartal_grenzen',
                                "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17"
                            ) );
                        ?></textarea>
                        <p class="description">Pro Zeile: Startdatum|Enddatum (JJJJ-MM-TT).</p>
                    </td>
                </tr>

                <tr>
                    <th><label for="gsh_tp_kategorie_mapping">Kategorie-Stichwörter &rarr; Farben</label></th>
                    <td>
                        <textarea id="gsh_tp_kategorie_mapping" name="gsh_tp_kategorie_mapping"
                                  rows="14" class="large-text code" style="font-size:12px"><?php
                            echo esc_textarea( get_option( 'gsh_tp_kategorie_mapping',
                                gsh_tp_default_mapping() ) );
                        ?></textarea>
                        <p class="description">
                            Pro Zeile: <code>Stichwort|farbklasse</code> &ndash;
                            Matching mit <strong>Wortgrenzen</strong>:
                            &bdquo;LK&ldquo; findet &bdquo;LK 3&ldquo; aber NICHT &bdquo;Volk&ldquo;.
                            <br>
                            Geprüft wird <strong>nur das CATEGORIES-Feld</strong> aus IServ,
                            nicht der Termintext. Termine ohne Kategorie werden grau dargestellt.
                        </p>
                        <details style="margin-top:8px">
                            <summary style="cursor:pointer;font-weight:600;color:#1a5276">
                                Verfügbare Farbklassen anzeigen
                            </summary>
                            <table class="widefat" style="max-width:460px;margin-top:8px">
                                <tr><td><b>konferenz</b></td>
                                    <td style="background:#d6eaf8;color:#1a5276;padding:4px 8px">Blau &ndash; Konferenzen, Sitzungen</td></tr>
                                <tr><td><b>pruefung</b></td>
                                    <td style="background:#fadbd8;color:#922b21;padding:4px 8px">Rot &ndash; Prüfungen, Klausuren</td></tr>
                                <tr><td><b>projekt</b></td>
                                    <td style="background:#d5f5e3;color:#1e8449;padding:4px 8px">Grün &ndash; Projekte, Fahrten, Sport</td></tr>
                                <tr><td><b>frei</b></td>
                                    <td style="background:#eaecee;color:#616a6b;padding:4px 8px">Grau &ndash; Ferien, Feiertage</td></tr>
                                <tr><td><b>eltern</b></td>
                                    <td style="background:#fdebd0;color:#b9770e;padding:4px 8px">Orange &ndash; Elternarbeit, Beratung</td></tr>
                                <tr><td><b>frist</b></td>
                                    <td style="background:#fcf3cf;color:#7d6608;padding:4px 8px">Gelb &ndash; Fristen, Hinweise</td></tr>
                            </table>
                        </details>
                    </td>
                </tr>

            </table>
            <?php submit_button( 'Einstellungen speichern' ); ?>
        </form>

        <hr />
        <h2>Kalender-Synchronisation</h2>
        <table class="form-table" style="max-width:700px">
            <tr>
                <th style="width:200px">Letzte Synchronisierung</th>
                <td>
                    <?php
                    $last_sync = get_option( 'gsh_tp_last_sync', '' );
                    if ( $last_sync ) {
                        $dt = new DateTime( $last_sync, new DateTimeZone( 'UTC' ) );
                        $dt->setTimezone( wp_timezone() );
                        echo '<strong>' . esc_html( $dt->format( 'd.m.Y, H:i' ) ) . ' Uhr</strong>';
                    } else {
                        echo '<em style="color:#888">Noch nicht synchronisiert</em>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Cache-Status</th>
                <td>
                    <?php
                    $cached = get_transient( GSH_TP_CACHE_KEY );
                    if ( $cached ) {
                        echo '<span style="color:#1e8449">&#10003; Cache vorhanden &ndash; Kalenderdaten werden aus dem Cache geladen</span>';
                    } else {
                        echo '<span style="color:#888">&#8212; Kein Cache aktiv &ndash; wird beim nächsten Seitenaufruf neu abgerufen</span>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <form method="post" style="margin-top:.75rem">
            <?php wp_nonce_field( 'gsh_tp_sync', 'gsh_tp_sn' ); ?>
            <p>
                <button type="submit" name="gsh_tp_sync" class="button button-primary" style="height:36px;font-size:14px;padding:0 18px">
                    &#8635; Jetzt synchronisieren
                </button>
                <span class="description" style="margin-left:10px;line-height:36px">
                    Leert den Cache und ruft sofort die aktuellen Kalenderdaten vom IServ ab.
                </span>
            </p>
        </form>

        <hr />
        <h2>Cache manuell leeren</h2>
        <form method="post">
            <?php wp_nonce_field( 'gsh_tp_cc', 'gsh_tp_cn' ); ?>
            <p>
                <button type="submit" name="gsh_tp_cc" class="button">Cache leeren</button>
                <span class="description" style="margin-left:10px">
                    Löscht nur den gespeicherten Cache. Die Daten werden beim nächsten Seitenaufruf neu abgerufen.
                </span>
            </p>
        </form>

        <hr />
        <h2>Shortcode-Verwendung</h2>
        <p><code>[gsh_terminplan]</code> &ndash; Zeigt automatisch das aktuelle Quartal</p>
        <p><code>[gsh_terminplan quartal="2"]</code> &ndash; Zeigt ein bestimmtes Quartal (1&ndash;4)</p>
        <p><code>[gsh_terminplan quartal="alle"]</code> &ndash; Alle Quartale mit Tabs</p>
    </div>
    <?php
}

/* ================================================================
   2. iCAL ABRUFEN & PARSEN
   ================================================================ */

function gsh_tp_fetch_ical() {
    $cached = get_transient( GSH_TP_CACHE_KEY );
    if ( false !== $cached ) {
        return $cached;
    }

    $url = get_option( 'gsh_tp_ical_url', '' );
    if ( empty( $url ) ) {
        return '';
    }

    $resp = wp_remote_get( $url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array( 'Accept' => 'text/calendar' ),
    ) );

    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        $bk = get_option( GSH_TP_BACKUP_KEY, '' );
        if ( $bk ) {
            set_transient( GSH_TP_CACHE_KEY, $bk, 600 );
            return $bk;
        }
        return '';
    }

    $body = wp_remote_retrieve_body( $resp );
    if ( strpos( $body, 'BEGIN:VCALENDAR' ) === false ) {
        return get_option( GSH_TP_BACKUP_KEY, '' );
    }

    $dur = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
    set_transient( GSH_TP_CACHE_KEY, $body, $dur );
    update_option( GSH_TP_BACKUP_KEY, $body, false );
    update_option( 'gsh_tp_last_sync', gmdate( 'Y-m-d H:i:s' ) );
    return $body;
}

function gsh_tp_parse_events( $data ) {
    if ( empty( $data ) ) {
        return array();
    }
    preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $data, $m );
    if ( empty( $m[1] ) ) {
        return array();
    }
    $evts = array();
    foreach ( $m[1] as $blk ) {
        $e = gsh_tp_parse_event( $blk );
        if ( $e ) {
            $evts[] = $e;
        }
    }
    usort( $evts, function ( $a, $b ) {
        return strcmp( $a['start'], $b['start'] );
    } );
    return $evts;
}

function gsh_tp_parse_event( $blk ) {
    // Zeilenumbrüche normalisieren, gefaltete Zeilen zusammenführen (RFC 5545)
    $blk   = str_replace( "\r\n", "\n", $blk );
    $blk   = preg_replace( '/\n[ \t]/', '', $blk );
    $props = array();

    foreach ( explode( "\n", trim( $blk ) ) as $ln ) {
        $ln = trim( $ln );
        if ( $ln && preg_match( '/^([A-Z\-]+)(?:;[^:]*)?:(.*)$/s', $ln, $p ) ) {
            $k = strtoupper( $p[1] );
            // CATEGORIES kann mehrfach vorkommen → zusammenführen
            $props[ $k ] = ( isset( $props[ $k ] ) && $k === 'CATEGORIES' )
                ? $props[ $k ] . ',' . $p[2]
                : $p[2];
        }
    }

    if ( empty( $props['DTSTART'] ) || empty( $props['SUMMARY'] ) ) {
        return null;
    }

    // iCal-Escaping rückgängig machen
    $ue = function ( $s ) {
        return str_replace(
            array( '\\n', '\\,', '\\;', '\\\\' ),
            array( "\n", ',', ';', '\\' ),
            $s
        );
    };

    // Datum parsen (VALUE=DATE und VALUE=DATE-TIME)
    $pd = function ( $d ) {
        $c = preg_replace( '/[^0-9T]/', '', $d );
        return strlen( $c ) >= 8
            ? substr( $c, 0, 4 ) . '-' . substr( $c, 4, 2 ) . '-' . substr( $c, 6, 2 )
            : null;
    };

    $start = $pd( $props['DTSTART'] );
    $end   = isset( $props['DTEND'] ) ? $pd( $props['DTEND'] ) : $start;
    if ( ! $start ) {
        return null;
    }
    if ( ! $end ) {
        $end = $start;
    }

    return array(
        'start'      => $start,
        'end'        => $end,
        'summary'    => $ue( $props['SUMMARY'] ),
        'description'=> isset( $props['DESCRIPTION'] ) ? $ue( $props['DESCRIPTION'] ) : '',
        'categories' => isset( $props['CATEGORIES'] ) ? $ue( $props['CATEGORIES'] ) : '',
        'allday'     => strlen( preg_replace( '/[^0-9]/', '', $props['DTSTART'] ) ) === 8,
    );
}

/* ================================================================
   3. SCHULWOCHEN & QUARTALE
   ================================================================ */

function gsh_tp_schulwoche( $date, $start ) {
    $days = (int) ( new DateTime( $start ) )->diff( new DateTime( $date ) )->format( '%r%a' );
    return $days < 0 ? 0 : (int) floor( $days / 7 ) + 1;
}

function gsh_tp_current_q() {
    $t = gmdate( 'Y-m-d' );
    foreach ( gsh_tp_quartale() as $i => $q ) {
        if ( $t >= $q['start'] && $t <= $q['end'] ) {
            return $i + 1;
        }
    }
    return 1;
}

function gsh_tp_quartale() {
    $raw = get_option( 'gsh_tp_quartal_grenzen',
        "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17" );
    $r = array();
    foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $ln ) {
        $p = explode( '|', $ln );
        if ( count( $p ) === 2 ) {
            $r[] = array( 'start' => trim( $p[0] ), 'end' => trim( $p[1] ) );
        }
    }
    while ( count( $r ) < 4 ) {
        $r[] = array( 'start' => '2025-08-25', 'end' => '2026-07-17' );
    }
    return $r;
}

/* ================================================================
   4. KATEGORIE-ERKENNUNG (Wortgrenzen-Matching)
   ================================================================ */

function gsh_tp_cat( $categories ) {
    static $map = null;
    if ( null === $map ) {
        $map = gsh_tp_build_map();
    }

    $cats = trim( $categories );
    if ( $cats === '' ) {
        return 'standard';
    }

    foreach ( $map as $entry ) {
        if ( preg_match( $entry['regex'], $cats ) ) {
            return $entry['cls'];
        }
    }

    return 'standard';
}

function gsh_tp_build_map() {
    $raw = get_option( 'gsh_tp_kategorie_mapping', gsh_tp_default_mapping() );
    $r   = array();

    foreach ( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) as $ln ) {
        if ( $ln === '' || $ln[0] === '#' ) {
            continue;
        }
        $p = explode( '|', $ln );
        if ( count( $p ) !== 2 ) {
            continue;
        }
        $kw  = trim( $p[0] );
        $cls = sanitize_html_class( trim( $p[1] ) );
        if ( $kw === '' || $cls === '' ) {
            continue;
        }
        $escaped = preg_quote( $kw, '/' );
        $r[] = array(
            'regex' => '/\b' . $escaped . '\b/iu',
            'cls'   => $cls,
        );
    }

    return $r;
}

/* ================================================================
   5. SHORTCODE (Hauptausgabe)
   ================================================================ */

add_shortcode( 'gsh_terminplan', 'gsh_tp_shortcode' );

function gsh_tp_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'quartal' => 'auto' ), $atts, 'gsh_terminplan' );

    $data = gsh_tp_fetch_ical();
    if ( ! $data ) {
        return '<div style="padding:1.5rem;background:#fadbd8;border:1px solid #e74c3c;'
             . 'border-radius:8px;color:#922b21;text-align:center">'
             . esc_html__( 'Keine Kalenderdaten verfügbar. Bitte die iCal-URL in den Plugin-Einstellungen prüfen.', 'gsh-terminplan' )
             . '</div>';
    }

    $events  = gsh_tp_parse_events( $data );
    $grenzen = gsh_tp_quartale();
    $sjs     = get_option( 'gsh_tp_schuljahr_start', '2025-08-25' );
    $aq      = ( $atts['quartal'] === 'auto' || $atts['quartal'] === 'alle' )
               ? gsh_tp_current_q()
               : max( 1, min( 4, absint( $atts['quartal'] ) ) );

    $qlbl = array(
        1 => 'Quartal 1 &ndash; August bis Oktober',
        2 => 'Quartal 2 &ndash; November bis Januar',
        3 => 'Quartal 3 &ndash; Februar bis April',
        4 => 'Quartal 4 &ndash; Mai bis Juli',
    );

    // Letzte Sync-Zeit ermitteln
    $last_sync = get_option( 'gsh_tp_last_sync', '' );
    if ( $last_sync ) {
        $dt = new DateTime( $last_sync, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( wp_timezone() );
        $sync_display = $dt->format( 'd.m.Y, H:i' );
    } else {
        $sync_display = wp_date( 'd.m.Y, H:i' );
    }

    // ── Ausgabe zusammenbauen ──
    $o  = gsh_tp_css();
    $o .= '<div class="gtp" id="gtp">';

    // Header
    $o .= '<div class="gtp-hd">';
    $o .= '<div class="gtp-hd-left">';
    $o .= '<h2 class="gtp-t">Jahresterminplan</h2>';
    $o .= '<span class="gtp-subtitle">Schuljahr 2025/26 &mdash; Gesamtschule Horst</span>';
    $o .= '</div>';
    $o .= '<span class="gtp-meta">Aktualisiert: ' . esc_html( $sync_display ) . ' Uhr</span>';
    $o .= '</div>';

    // Tabs
    $o .= '<div class="gtp-tabs" role="tablist">';
    for ( $i = 1; $i <= 4; $i++ ) {
        $on = $i === $aq ? ' gtp-tab-on' : '';
        $o .= '<button type="button" class="gtp-tab' . $on . '" data-q="' . $i
            . '" role="tab" aria-selected="' . ( $i === $aq ? 'true' : 'false' )
            . '" onclick="gtpTab(' . $i . ')">Quartal ' . $i . '</button>';
    }
    $o .= '</div>';

    // Filter-Buttons
    $fc = array(
        'konferenz' => 'Konferenzen',
        'pruefung'  => 'Prüfungen',
        'projekt'   => 'Projekte',
        'frei'      => 'Ferien / Frei',
        'eltern'    => 'Eltern',
        'frist'     => 'Fristen',
        'standard'  => 'Sonstige',
    );
    $o .= '<div class="gtp-filt-wrap">';
    $o .= '<span class="gtp-filt-lbl">Anzeige filtern:</span>';
    $o .= '<div class="gtp-filt">';
    foreach ( $fc as $k => $l ) {
        $o .= '<button type="button" class="gtp-fb gtp-fb-on" data-c="'
            . esc_attr( $k ) . '" onclick="gtpFil(this)">'
            . esc_html( $l ) . '</button>';
    }
    $o .= '<button type="button" id="gtp-reset" class="gtp-reset" onclick="gtpReset()" style="display:none">'
        . '&#10005; Alle anzeigen</button>';
    $o .= '</div></div>';

    // Quartalspanels
    for ( $q = 1; $q <= 4; $q++ ) {
        $vis = $q === $aq ? 'block' : 'none';
        $qd  = $grenzen[ $q - 1 ] ?? null;
        if ( ! $qd ) {
            continue;
        }
        $o .= '<div class="gtp-qp" id="gtp-q' . $q . '" style="display:' . $vis . '">';
        $o .= '<h3 class="gtp-qt">' . $qlbl[ $q ] . '</h3>';
        $o .= gsh_tp_table( $events, $qd, $sjs );
        $o .= '</div>';
    }

    // Footer mit Druckbuttons
    $o .= '<div class="gtp-ft"><div class="gtp-ft-btns">';
    $o .= '<button type="button" class="gtp-btn" onclick="gtpPrint(\'single\')">'
        . 'Quartal drucken</button>';
    $o .= '<button type="button" class="gtp-btn gtp-b2" onclick="gtpPrint(\'all\')">'
        . 'Alle Quartale drucken</button>';
    $o .= '</div>';
    $o .= '<span class="gtp-src">Quelle: IServ-Kalender</span>';
    $o .= '</div>';

    $o .= '<button type="button" id="gtp-heute-btn" onclick="gtpScrollToday()" aria-label="Zur heutigen Woche springen">&#128205; Heute</button>';
    $o .= '</div>'; // .gtp
    $o .= gsh_tp_js();
    return $o;
}

/* ================================================================
   6. TABELLE (eine Quartalstabelle)
   ================================================================ */

/**
 * Gibt die Laufzeit eines Termins in Tagen zurück.
 *
 * Für ganztägige iCal-Termine ist DTEND exklusiv (der Folgetag), deshalb liefert
 * die direkte Datumsdifferenz bereits die korrekte Anzahl ganzer Tage.
 * Beispiel: Montag–Freitag → DTSTART=Mo, DTEND=Sa → diff = 5 Tage → korrekt.
 */
function gsh_tp_event_duration( $ev ) {
    $s = new DateTime( $ev['start'] );
    $e = new DateTime( $ev['end'] );
    return max( 1, (int) $s->diff( $e )->format( '%a' ) );
}

function gsh_tp_table( $events, $qd, $sjs ) {
    $qs  = new DateTime( $qd['start'] );
    $qe  = new DateTime( $qd['end'] );
    $td  = gmdate( 'Y-m-d' );

    // Zur letzten Montag-Position zurückgehen
    $dow = (int) $qs->format( 'N' );
    if ( $dow > 1 ) {
        $qs->modify( '-' . ( $dow - 1 ) . ' days' );
    }

    $h  = '<table class="gt"><thead><tr>';
    $h .= '<th class="gs">SW</th>';
    $h .= '<th>Mo</th><th>Di</th><th>Mi</th><th>Do</th><th>Fr</th>';
    $h .= '<th class="gh">Hinweise</th>';
    $h .= '</tr></thead><tbody>';

    $c   = clone $qs;
    $lim = 50; // Sicherheit gegen Endlosschleifen

    while ( $c <= $qe && $lim-- > 0 ) {
        $sw = gsh_tp_schulwoche( $c->format( 'Y-m-d' ), $sjs );
        $h .= '<tr>';
        $h .= '<td class="gs"><b>' . ( $sw > 0 ? sprintf( '%02d', $sw ) : '–' ) . '</b></td>';

        // ── Vorarbeiten: Lange Termine dieser Woche für die Hinweise-Spalte sammeln ──
        // Ein Termin gilt als "lang", wenn er >= 5 Tage dauert (ganze Woche oder länger).
        $hinweise_keys = array(); // Duplikate über Wochentage hinweg verhindern
        $hinweise_long = array(); // Lange Termine → nur in Hinweise, nicht in Tagesspalte
        $hinweise_frist = array(); // Frist-Termine (kurz) → zusätzlich in Hinweise

        for ( $d = 0; $d < 5; $d++ ) {
            $dy = clone $c;
            $dy->modify( "+{$d} days" );
            $ds = $dy->format( 'Y-m-d' );
            foreach ( gsh_tp_day_events( $events, $ds ) as $ev ) {
                if ( gsh_tp_event_duration( $ev ) >= 5 ) {
                    $key = $ev['start'] . '|' . $ev['summary'];
                    if ( ! isset( $hinweise_keys[ $key ] ) ) {
                        $hinweise_keys[ $key ] = true;
                        $hinweise_long[] = $ev;
                    }
                }
            }
        }

        // ── Tagesspalten rendern ──
        for ( $d = 0; $d < 5; $d++ ) {
            $dy = clone $c;
            $dy->modify( "+{$d} days" );
            $ds = $dy->format( 'Y-m-d' );
            $de = gsh_tp_day_events( $events, $ds );

            $cl = 'gd';
            if ( $ds === $td ) {
                $cl .= ' gt-today';
            }

            // Ferientag erkennen – auch lange Ferien-Termine markieren den Tag grau
            $hol = false;
            foreach ( $de as $ev ) {
                if ( gsh_tp_cat( $ev['categories'] ) === 'frei' ) {
                    $hol = true;
                    break;
                }
            }
            if ( $hol ) {
                $cl .= ' gt-hol';
            }

            $h .= '<td class="' . esc_attr( $cl ) . '">';
            $h .= '<span class="gdl">' . esc_html( $dy->format( 'd.m.' ) ) . '</span>';

            foreach ( $de as $ev ) {
                // Lange Termine (>= 5 Tage) erscheinen nur in der Hinweise-Spalte
                if ( gsh_tp_event_duration( $ev ) >= 5 ) {
                    continue;
                }

                $cc = gsh_tp_cat( $ev['categories'] );
                $tt = $ev['description'] ? $ev['description'] : $ev['summary'];
                $h .= '<div class="ge gc-' . esc_attr( $cc )
                    . '" data-c="' . esc_attr( $cc )
                    . '" title="' . esc_attr( wp_strip_all_tags( $tt ) ) . '">'
                    . esc_html( $ev['summary'] ) . '</div>';

                // Frist-Termine zusätzlich in Hinweise merken
                if ( $cc === 'frist' ) {
                    $key = $ev['start'] . '|' . $ev['summary'];
                    if ( ! isset( $hinweise_keys[ $key ] ) ) {
                        $hinweise_keys[ $key ] = true;
                        $hinweise_frist[] = $ev;
                    }
                }
            }

            $h .= '</td>';
        }

        // ── Hinweise-Spalte rendern ──
        $h .= '<td class="gh gnc">';

        // Lange Termine (mit Kategorie-Farbe und Datumsbereich)
        foreach ( $hinweise_long as $ev ) {
            $cc      = gsh_tp_cat( $ev['categories'] );
            $tt      = $ev['description'] ? $ev['description'] : $ev['summary'];
            $eff_end = new DateTime( $ev['end'] );
            if ( $ev['allday'] && $ev['end'] > $ev['start'] ) {
                $eff_end->modify( '-1 day' );
            }
            $range = ( new DateTime( $ev['start'] ) )->format( 'd.m.' )
                   . '&ndash;' . $eff_end->format( 'd.m.' );
            $h .= '<div class="gn-long gc-' . esc_attr( $cc )
                . '" data-c="' . esc_attr( $cc )
                . '" title="' . esc_attr( wp_strip_all_tags( $tt ) ) . '">'
                . '<span class="gn-range">' . $range . '</span>'
                . esc_html( $ev['summary'] )
                . '</div>';
        }

        // Frist-Notizen
        foreach ( $hinweise_frist as $ev ) {
            $h .= '<div class="gn" data-c="frist">' . esc_html( $ev['summary'] ) . '</div>';
        }

        $h .= '</td>';
        $h .= '</tr>';
        $c->modify( '+7 days' );
    }

    $h .= '</tbody></table>';
    return $h;
}

function gsh_tp_day_events( $events, $date ) {
    $r = array();
    foreach ( $events as $ev ) {
        $end = $ev['end'];
        // Ganztägige Events: DTEND ist exklusiv (der Tag danach)
        if ( $ev['allday'] && $end > $ev['start'] ) {
            $ed = new DateTime( $end );
            $ed->modify( '-1 day' );
            $end = $ed->format( 'Y-m-d' );
        }
        if ( $date >= $ev['start'] && $date <= $end ) {
            $r[] = $ev;
        }
    }
    return $r;
}

/* ================================================================
   7. CSS
   ================================================================ */

function gsh_tp_css() {
    return '<style>
/* ── GSH Terminplan Dashboard v1.4 ── */

/* ── Container ── */
.gtp{
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  max-width:1200px;margin:0 auto;color:#1a1a2e;
  background:#fff;border-radius:12px;
  box-shadow:0 2px 20px rgba(0,0,0,.09);
  padding:1.5rem 1.75rem;
}

/* ── Header ── */
.gtp-hd{
  display:flex;justify-content:space-between;align-items:center;
  flex-wrap:wrap;margin-bottom:1.25rem;padding-bottom:1rem;
  border-bottom:3px solid #1a5276;gap:.75rem;
}
.gtp-hd-left{display:flex;flex-direction:column;gap:.2rem}
.gtp-t{font-size:1.5rem;font-weight:700;color:#1a5276;margin:0;letter-spacing:-.01em}
.gtp-subtitle{font-size:.78rem;color:#7f8c8d;font-weight:400}
.gtp-meta{font-size:.75rem;color:#adb5bd;white-space:nowrap}

/* ── Tabs ── */
.gtp-tabs{
  display:flex;gap:0;margin-bottom:0;
  border-bottom:2px solid #d4e6f1;
}
.gtp-tab{
  padding:.55rem 1.4rem;
  border:2px solid transparent;border-bottom:none;
  border-radius:8px 8px 0 0;background:transparent;
  color:#5d7a8a;font-weight:600;font-size:.88rem;
  cursor:pointer;transition:background .18s,color .18s;
  margin-bottom:-2px;
}
.gtp-tab:hover{background:#f0f7fc;color:#1a5276}
.gtp-tab-on{background:#fff;color:#1a5276;border-color:#d4e6f1;border-bottom-color:#fff}

/* ── Filter ── */
.gtp-filt-wrap{
  margin:1rem 0;padding:.75rem 1rem;
  background:#f8fafc;border-radius:8px;
  border:1px solid #e4ecf3;
}
.gtp-filt-lbl{
  font-size:.7rem;font-weight:700;color:#7f8c8d;
  text-transform:uppercase;letter-spacing:.07em;
  display:block;margin-bottom:.5rem;
}
.gtp-filt{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.gtp-fb{
  padding:4px 13px;border:1.5px solid;border-radius:20px;
  font-size:.76rem;cursor:pointer;font-weight:500;line-height:1.6;
  transition:opacity .18s,filter .18s;
}
.gtp-fb[data-c="konferenz"]{border-color:#2874a6;background:#d6eaf8;color:#1a5276}
.gtp-fb[data-c="pruefung"] {border-color:#c0392b;background:#fadbd8;color:#922b21}
.gtp-fb[data-c="projekt"]  {border-color:#27ae60;background:#d5f5e3;color:#1e8449}
.gtp-fb[data-c="frei"]     {border-color:#95a5a6;background:#eaecee;color:#616a6b}
.gtp-fb[data-c="eltern"]   {border-color:#e67e22;background:#fdebd0;color:#b9770e}
.gtp-fb[data-c="frist"]    {border-color:#d4ac0d;background:#fcf3cf;color:#7d6608}
.gtp-fb[data-c="standard"] {border-color:#566573;background:#f2f3f4;color:#2c3e50}
.gtp-fb-off{opacity:.28;filter:grayscale(.5)}

/* Reset-Button */
.gtp-reset{
  padding:4px 13px;border:1.5px solid #aaa;border-radius:20px;
  font-size:.76rem;cursor:pointer;font-weight:500;line-height:1.6;
  background:#fff;color:#555;transition:all .18s;margin-left:4px;
}
.gtp-reset:hover{background:#eaecee;border-color:#666;color:#333}

/* ── Quartal-Überschrift ── */
.gtp-qt{
  font-size:.92rem;font-weight:700;color:#1a5276;
  margin:1rem 0 .5rem;padding-left:2px;
}

/* ── Tabelle ── */
.gt{width:100%;border-collapse:collapse;font-size:.82rem;table-layout:fixed}
.gt thead th{
  background:#1a5276;color:#fff;padding:8px 6px;
  text-align:center;font-weight:600;font-size:.76rem;
  border:1px solid #15456a;letter-spacing:.02em;
}
.gt tbody tr:hover td{background:rgba(26,82,118,.03)}
.gt tbody td{
  border:1px solid #e8ecef;padding:4px 5px;vertical-align:top;
  transition:background .1s;
}
.gs{width:44px;text-align:center;background:#f7f9fc;font-size:.84rem;font-weight:700;color:#1a5276}
.gh{width:140px;background:#fefdf8}
.gdl{display:block;font-size:.67rem;color:#adb5bd;margin-bottom:2px;letter-spacing:.01em}
.gnc{font-size:.72rem;color:#666}

/* ── Event-Tags (Tagesspalten) ── */
.ge{
  padding:2px 6px;margin:2px 0;border-radius:4px;
  font-size:.73rem;line-height:1.4;
  border-left:3px solid transparent;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  cursor:default;
}
.gc-konferenz{background:#d6eaf8;border-left-color:#2874a6;color:#1a5276}
.gc-pruefung {background:#fadbd8;border-left-color:#c0392b;color:#922b21}
.gc-projekt  {background:#d5f5e3;border-left-color:#27ae60;color:#1e8449}
.gc-frei     {background:#eaecee;border-left-color:#95a5a6;color:#616a6b}
.gc-eltern   {background:#fdebd0;border-left-color:#e67e22;color:#b9770e}
.gc-frist    {background:#fef9e7;border-left-color:#d4ac0d;color:#7d6608}
.gc-standard {background:#f2f3f4;border-left-color:#566573;color:#2c3e50}

/* ── Lange Termine in der Hinweise-Spalte ── */
.gn-long{
  padding:2px 5px;margin:2px 0;border-radius:3px;
  font-size:.72rem;line-height:1.4;
  border-left:3px solid transparent;
  font-weight:500;
  /* gc-XXX-Klassen übernehmen Farbe und Hintergrund */
}
.gn-range{
  display:block;font-size:.65rem;opacity:.7;font-style:normal;
  margin-bottom:1px;
}

/* ── Frist-Notizen in der Hinweise-Spalte ── */
.gn{
  padding:2px 5px;margin:2px 0;
  background:#fef9e7;border-radius:3px;
  font-style:italic;font-size:.72rem;color:#7d6608;
}

/* ── Heute & Ferien ── */
.gt-today{background:#e8f4fd!important;box-shadow:inset 0 0 0 2px #2874a6}
.gt-hol  {background:#f5f5f5!important}

/* ── Footer ── */
.gtp-ft{
  display:flex;justify-content:space-between;align-items:center;
  margin-top:1.25rem;padding-top:.75rem;
  border-top:1px solid #e8ecef;flex-wrap:wrap;gap:.5rem;
}
.gtp-ft-btns{display:flex;gap:.5rem;flex-wrap:wrap}
.gtp-btn{
  padding:7px 16px;background:#1a5276;color:#fff;border:none;
  border-radius:6px;cursor:pointer;font-size:.82rem;font-weight:500;
  transition:background .18s;
}
.gtp-btn:hover{background:#154360}
.gtp-b2{background:#566573}
.gtp-b2:hover{background:#2c3e50}
.gtp-src{font-size:.7rem;color:#adb5bd}

/* ── Druck-iframe ── */
#gtp-print-frame{position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none}

/* ── Floating Heute-Button ── */
#gtp-heute-btn{
  position:fixed;bottom:24px;right:24px;z-index:9999;
  padding:10px 20px;
  background:#1a5276;color:#fff;border:none;
  border-radius:50px;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
  font-size:.88rem;font-weight:600;letter-spacing:.01em;
  box-shadow:0 4px 16px rgba(0,0,0,.28);
  cursor:pointer;
  opacity:0;pointer-events:none;
  transition:opacity .25s,transform .25s;
  transform:translateY(8px);
}
#gtp-heute-btn.gtp-heute-vis{opacity:1;pointer-events:auto;transform:translateY(0)}
#gtp-heute-btn:hover{background:#154360;box-shadow:0 6px 20px rgba(0,0,0,.32)}

/* ── Responsive ── */
@media(max-width:768px){
  .gtp{padding:1rem;border-radius:8px}
  .gt{display:block;overflow-x:auto}
  .gtp-tabs{overflow-x:auto;flex-wrap:nowrap;border-bottom:none}
  .gtp-tab{white-space:nowrap;flex-shrink:0}
  .gtp-hd{flex-direction:column;align-items:flex-start}
  .gtp-filt-wrap{padding:.5rem .75rem}
}
</style>';
}

/* ================================================================
   8. JAVASCRIPT – Tabs, Filter, Druck
   ================================================================ */

function gsh_tp_js() {
    return <<<'JSEOF'
<script>
/* ════════════════════════════════════════════════════════
   TAB-WECHSEL
   ════════════════════════════════════════════════════════ */

/* Interner Wechsel ohne Scroll-Seiteneffekte */
function gtpSwitchTab(q){
  document.querySelectorAll(".gtp-qp").forEach(function(e){e.style.display="none"});
  document.querySelectorAll(".gtp-tab").forEach(function(e){
    e.classList.remove("gtp-tab-on");
    e.setAttribute("aria-selected","false");
  });
  var p=document.getElementById("gtp-q"+q);
  if(p) p.style.display="block";
  var t=document.querySelector('.gtp-tab[data-q="'+q+'"]');
  if(t){t.classList.add("gtp-tab-on");t.setAttribute("aria-selected","true");}
}

/* Öffentlicher Tab-Wechsel: scrollt danach zur Heute-Zeile, wenn vorhanden */
function gtpTab(q){
  gtpSwitchTab(q);
  var p=document.getElementById("gtp-q"+q);
  if(p && p.querySelector(".gt-today")){
    /* Sanft zur Heute-Zeile im neuen Quartal scrollen */
    setTimeout(gtpScrollToday,60);
  }
  /* Heute-Button-Status nach dem Panel-Wechsel neu berechnen */
  setTimeout(gtpUpdateHeuteBtn,80);
}

/* ══════════════════════════════════════════════════════
   FILTER-LOGIK
   ══════════════════════════════════════════════════════
   Zustand "all":    Alle Kategorien sichtbar, alle Buttons aktiv.
   Zustand "select": Nur ausgewählte Kategorien sichtbar.

   Klick im Zustand "all":
     → Wechsel zu "select", nur die geklickte Kategorie ist aktiv.

   Klick im Zustand "select":
     → Aktiv: Kategorie entfernen. Wenn keine mehr aktiv → zurück zu "all".
     → Inaktiv: Kategorie hinzufügen.

   Reset-Button: Zurück zu "all".
   ══════════════════════════════════════════════════════ */

var gtpMode = "all";   // "all" | "select"
var gtpSel  = {};      // { kategorie: true, ... } im Modus "select"

function gtpFil(btn){
  var c = btn.getAttribute("data-c");

  if(gtpMode === "all"){
    /* Erster Klick: Exklusiv-Modus starten */
    gtpMode = "select";
    gtpSel  = {};
    gtpSel[c] = true;
  } else {
    if(gtpSel[c]){
      /* Aktive Kategorie ausschalten */
      delete gtpSel[c];
      if(Object.keys(gtpSel).length === 0){
        gtpMode = "all"; /* Alle deaktiviert → zurück zu "alle anzeigen" */
      }
    } else {
      /* Weitere Kategorie hinzuschalten */
      gtpSel[c] = true;
    }
  }
  gtpApply();
}

function gtpReset(){
  gtpMode = "all";
  gtpSel  = {};
  gtpApply();
}

function gtpApply(){
  var isAll = (gtpMode === "all");

  /* Filter-Buttons aktualisieren */
  document.querySelectorAll(".gtp-fb").forEach(function(btn){
    var c = btn.getAttribute("data-c");
    var active = isAll || !!gtpSel[c];
    btn.classList.toggle("gtp-fb-on",  active);
    btn.classList.toggle("gtp-fb-off", !active);
  });

  /* Termine in Tagesspalten ein-/ausblenden (.ge[data-c]) */
  document.querySelectorAll(".ge[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = (isAll || !!gtpSel[c]) ? "" : "none";
  });

  /* Lange Termine in Hinweise-Spalte ein-/ausblenden (.gn-long[data-c]) */
  document.querySelectorAll(".gn-long[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = (isAll || !!gtpSel[c]) ? "" : "none";
  });

  /* Frist-Notizen in Hinweise-Spalte (.gn[data-c]) */
  document.querySelectorAll(".gn[data-c]").forEach(function(el){
    var c = el.getAttribute("data-c");
    el.style.display = (isAll || !!gtpSel[c]) ? "" : "none";
  });

  /* Reset-Button zeigen / verstecken */
  var resetBtn = document.getElementById("gtp-reset");
  if(resetBtn) resetBtn.style.display = isAll ? "none" : "";
}

/* ════════════════════════════════════════════════════════
   SCROLL ZUR HEUTIGEN ZEILE
   ════════════════════════════════════════════════════════
   - Prüft, ob .gt-today in einem sichtbaren Panel liegt.
   - Falls nicht: wechselt zuerst den Tab (via gtpSwitchTab).
   - Scrollt die Tabellenzeile in das obere Drittel des Viewports.
   ════════════════════════════════════════════════════════ */

function gtpScrollToday(){
  var today=document.querySelector(".gt-today");
  if(!today) return;

  /* Falls heute in einem versteckten Panel liegt: Tab wechseln */
  var panel=today.closest(".gtp-qp");
  if(panel && panel.style.display==="none"){
    var qnum=parseInt(panel.id.replace("gtp-q",""),10);
    if(!isNaN(qnum)) gtpSwitchTab(qnum);
  }

  /* Kurz warten (Reflow nach eventuellem Panel-Wechsel) */
  setTimeout(function(){
    var row=today.closest("tr")||today;
    var top=row.getBoundingClientRect().top+window.pageYOffset;
    var offset=Math.max(0,top-Math.floor(window.innerHeight/3));
    window.scrollTo({top:offset,behavior:"smooth"});
    /* Button-Status nach dem Scroll-Ende aktualisieren */
    setTimeout(gtpUpdateHeuteBtn,420);
  },50);
}

/* ════════════════════════════════════════════════════════
   FLOATING HEUTE-BUTTON: SICHTBARKEIT
   ════════════════════════════════════════════════════════
   Zeigt den Button, wenn .gt-today außerhalb des Viewports
   liegt ODER sich in einem aktuell versteckten Quartal befindet.
   ════════════════════════════════════════════════════════ */

function gtpUpdateHeuteBtn(){
  var today=document.querySelector(".gt-today");
  var btn=document.getElementById("gtp-heute-btn");
  if(!btn) return;

  if(!today){
    btn.classList.remove("gtp-heute-vis");
    return;
  }

  var panel=today.closest(".gtp-qp");
  var inActive=!panel||panel.style.display!=="none";

  if(!inActive){
    /* Heute liegt im falschen Quartal → Button immer zeigen */
    btn.classList.add("gtp-heute-vis");
    return;
  }

  /* Heute im aktiven Panel → Button zeigen wenn außerhalb des Viewports */
  var rect=today.getBoundingClientRect();
  var visible=rect.top<window.innerHeight&&rect.bottom>0;
  btn.classList.toggle("gtp-heute-vis",!visible);
}

window.addEventListener("scroll",gtpUpdateHeuteBtn,{passive:true});
window.addEventListener("resize",gtpUpdateHeuteBtn,{passive:true});

/* ── Auto-Scroll & Button-Init beim Laden ── */
setTimeout(function(){
  gtpScrollToday();
  gtpUpdateHeuteBtn();
},200);

/* ══════════════════════════════════════════════════════
   DRUCKFUNKTION – iframe-basiert
   ══════════════════════════════════════════════════════ */

function gtpPrint(mode){
  var ids;
  if(mode==="all"){
    ids=[1,2,3,4];
  }else{
    var at=document.querySelector(".gtp-tab-on");
    ids=[at ? parseInt(at.getAttribute("data-q")) : 1];
  }

  var CSS=[
    "*{margin:0;padding:0;box-sizing:border-box}",
    "body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1a1a2e;padding:6mm 8mm}",
    ".hdr{text-align:center;margin-bottom:8pt;padding-bottom:5pt;border-bottom:2pt solid #1a5276}",
    ".hdr h2{font-size:13pt;color:#1a5276;margin:0 0 2pt}",
    ".hdr p{font-size:7.5pt;color:#666;margin:0}",
    ".qt{font-size:10pt;color:#1a5276;margin:6pt 0 4pt;font-weight:700}",
    "table{width:100%;border-collapse:collapse;font-size:7pt;table-layout:fixed}",
    "thead th{background:#1a5276;color:#fff;padding:3pt 2pt;font-size:6.5pt;border:.4pt solid #15456a;text-align:center;font-weight:600}",
    "tbody td{border:.4pt solid #ccc;padding:2pt 2pt;vertical-align:top}",
    ".gs{width:28pt;text-align:center;background:#f0f4f8;font-weight:700}",
    ".gh{width:85pt;background:#fefdf5}",
    ".gdl{display:block;font-size:5pt;color:#95a5a6;margin-bottom:1pt}",
    ".ge{font-size:6pt;padding:1pt 2pt;margin:.4pt 0;border-radius:1pt;line-height:1.2;border-left:2pt solid transparent}",
    ".gn-long{font-size:6pt;padding:1pt 2pt;margin:.4pt 0;border-radius:1pt;line-height:1.2;border-left:2pt solid transparent;font-weight:500}",
    ".gn-range{display:block;font-size:5pt;opacity:.7}",
    ".gc-konferenz{background:#d6eaf8;border-left-color:#2874a6;color:#1a5276}",
    ".gc-pruefung{background:#fadbd8;border-left-color:#c0392b;color:#922b21}",
    ".gc-projekt{background:#d5f5e3;border-left-color:#27ae60;color:#1e8449}",
    ".gc-frei{background:#eaecee;border-left-color:#95a5a6;color:#616a6b}",
    ".gc-eltern{background:#fdebd0;border-left-color:#e67e22;color:#b9770e}",
    ".gc-frist{background:#fcf3cf;border-left-color:#f1c40f;color:#7d6608}",
    ".gc-standard{background:#f2f3f4;border-left-color:#566573;color:#2c3e50}",
    ".gt-today{box-shadow:inset 0 0 0 1pt #2874a6;background:#e8f4fd}",
    ".gt-hol{background:#f0f0f0}",
    ".gn{font-size:5pt;background:#fef9e7;padding:1pt 2pt;margin:1pt 0;font-style:italic}",
    ".gnc{font-size:5pt}",
    ".leg{display:flex;gap:10pt;flex-wrap:wrap;margin-top:6pt;padding-top:4pt;border-top:.4pt solid #ccc;font-size:6pt}",
    ".leg span{display:inline-flex;align-items:center;gap:2pt}",
    ".ld{width:7pt;height:7pt;border-radius:1pt;display:inline-block}",
    ".pb{page-break-before:always}",
    "thead th,.gs,.gh,.ge,.gn-long,.gc-konferenz,.gc-pruefung,.gc-projekt,.gc-frei,.gc-eltern,.gc-frist,.gc-standard,.gt-today,.gt-hol,.gn,.ld{-webkit-print-color-adjust:exact;print-color-adjust:exact}",
    "@page{size:A4 landscape;margin:6mm 8mm}",
    "@media print{body{padding:0}}"
  ].join("\n");

  var LEG='<div class="leg">'
    +'<span><span class="ld" style="background:#d6eaf8;border-left:2pt solid #2874a6"></span> Konferenz</span>'
    +'<span><span class="ld" style="background:#fadbd8;border-left:2pt solid #c0392b"></span> Pr\u00fcfung</span>'
    +'<span><span class="ld" style="background:#d5f5e3;border-left:2pt solid #27ae60"></span> Projekt</span>'
    +'<span><span class="ld" style="background:#eaecee;border-left:2pt solid #95a5a6"></span> Ferien</span>'
    +'<span><span class="ld" style="background:#fdebd0;border-left:2pt solid #e67e22"></span> Eltern</span>'
    +'<span><span class="ld" style="background:#fcf3cf;border-left:2pt solid #f1c40f"></span> Frist</span>'
    +'</div>';

  var HDR='<div class="hdr"><h2>Gesamtschule Horst \u2013 Jahresterminplan</h2>'
    +'<p>Schuljahr 2025/26 \u00b7 Stand: '
    +new Date().toLocaleDateString("de-DE")
    +' \u00b7 Quelle: IServ-Kalender</p></div>';

  var body="";
  for(var i=0;i<ids.length;i++){
    var panel=document.getElementById("gtp-q"+ids[i]);
    if(!panel) continue;
    if(i>0) body+='<div class="pb"></div>';
    body+=HDR;
    var tt=panel.querySelector(".gtp-qt");
    if(tt) body+='<div class="qt">'+tt.textContent+'</div>';
    var tb=panel.querySelector("table");
    if(tb) body+=tb.outerHTML;
    body+=LEG;
  }

  var frame=document.getElementById("gtp-print-frame");
  if(!frame){
    frame=document.createElement("iframe");
    frame.id="gtp-print-frame";
    frame.style.cssText="position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none";
    document.body.appendChild(frame);
  }

  var doc=frame.contentDocument || frame.contentWindow.document;
  doc.open();
  doc.write([
    '<!DOCTYPE html>','<html lang="de">','<head>',
    '<meta charset="utf-8">','<title>Terminplan Druck</title>',
    '<style>'+CSS+'</style>','</head>','<body>',body,'</body>','</html>'
  ].join(''));
  doc.close();

  var printed=false;
  function doPrint(){
    if(printed) return;
    printed=true;
    try{
      frame.contentWindow.focus();
      frame.contentWindow.print();
    }catch(e){
      var w=window.open("","_blank");
      if(w){
        w.document.open();
        w.document.write(doc.documentElement.outerHTML);
        w.document.close();
        w.focus();
        w.print();
      }
    }
  }
  frame.onload=doPrint;
  setTimeout(doPrint,500);
}
</script>
JSEOF;
}

/* ================================================================
   9. DEAKTIVIERUNG / DEINSTALLATION
   ================================================================ */

register_deactivation_hook( __FILE__, function () {
    delete_transient( GSH_TP_CACHE_KEY );
} );

register_uninstall_hook( __FILE__, 'gsh_tp_uninstall' );
function gsh_tp_uninstall() {
    foreach ( array(
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_last_sync',
        GSH_TP_BACKUP_KEY,
    ) as $opt ) {
        delete_option( $opt );
    }
    delete_transient( GSH_TP_CACHE_KEY );
}
