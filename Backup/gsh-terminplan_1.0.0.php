<?php
/**
 * Plugin Name: GSH Terminplan Dashboard
 * Plugin URI:  https://gesamtschule-horst.de
 * Description: Interaktive Quartalsübersicht des Schuljahresterminplans aus dem IServ-Kalender (iCal-Feed).
 * Version:     1.0.1
 * Author:      Gesamtschule Horst
 * License:     GPL v2 or later
 * Text Domain: gsh-terminplan
 *
 * Sicherheitshinweise:
 * - iCal-URL wird serverseitig gespeichert und nie an den Client übertragen
 * - Alle Ausgaben werden mit esc_html() / esc_attr() escaped
 * - Nonce-Prüfung für alle Admin-Formulare
 * - Capability-Checks für Einstellungsseite
 * - Transient-Cache verhindert übermäßige externe Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direktzugriff verhindern
}

// ============================================================
// KONSTANTEN
// ============================================================

define( 'GSH_TP_VERSION', '1.0.1' );
define( 'GSH_TP_SLUG', 'gsh-terminplan' );
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );

// ============================================================
// 1. ADMIN-EINSTELLUNGEN
// ============================================================

/**
 * Registriert die Einstellungsseite im WordPress-Admin.
 */
add_action( 'admin_menu', 'gsh_tp_admin_menu' );
function gsh_tp_admin_menu() {
    add_options_page(
        __( 'GSH Terminplan', 'gsh-terminplan' ),
        __( 'GSH Terminplan', 'gsh-terminplan' ),
        'manage_options',
        GSH_TP_SLUG,
        'gsh_tp_settings_page'
    );
}

/**
 * Registriert die Plugin-Optionen mit Sanitization-Callbacks.
 */
add_action( 'admin_init', 'gsh_tp_register_settings' );
function gsh_tp_register_settings() {
    register_setting( 'gsh_tp_options', 'gsh_tp_ical_url', array(
        'type'              => 'string',
        'sanitize_callback' => 'gsh_tp_sanitize_url',
        'default'           => '',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_cache_duration', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 3600,
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_schuljahr_start', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '2025-08-25',
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_quartal_grenzen', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17",
    ) );
    register_setting( 'gsh_tp_options', 'gsh_tp_kategorie_mapping', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => gsh_tp_default_mapping(),
    ) );
}

/**
 * Standard-Kategorie-Mapping.
 * Format pro Zeile: Stichwort|Kategorie-Klasse
 * Die Klasse bestimmt die Farbe im Dashboard.
 */
function gsh_tp_default_mapping() {
    return "konferenz|konferenz\nlk|konferenz\nfako|konferenz\nfachkonferenz|konferenz\ntbs|konferenz\ndb|konferenz\npk|konferenz\nzk|konferenz\nsk|konferenz\nschilf|konferenz\npflegschaft|konferenz\n"
         . "prüfung|pruefung\npruefung|pruefung\nzp|pruefung\nabi|pruefung\nklausur|pruefung\nvera|pruefung\nle|pruefung\nnachprüfung|pruefung\ndst|pruefung\nzkl|pruefung\nmap|pruefung\n"
         . "projekt|projekt\nprowo|projekt\nwandertag|projekt\nsport|projekt\nbp|projekt\npraktikum|projekt\nfahrt|projekt\nexkursion|projekt\nturnier|projekt\nolympiade|projekt\naktion|projekt\nmarathon|projekt\n"
         . "frei|frei\nferien|frei\nfeiertag|frei\nferientag|frei\nrosenmontag|frei\nramadan|frei\nostern|frei\nweihnacht|frei\npfingsten|frei\nhimmelfahrt|frei\nfronleichnam|frei\nnewroz|frei\nopferfest|frei\nrosch|frei\nchanukka|frei\n"
         . "eltern|eltern\nberatung|eltern\ninfo-abend|eltern\ninfoabend|eltern\npotenzial|eltern\nkaoa|eltern\nkurswahl|eltern\nanmeldung|eltern\ntür|eltern\nschnupper|eltern\nberufemarkt|eltern\n"
         . "frist|frist\nnoten|frist\nwebuntis|frist\nfachkommentar|frist\nberichtszeugnis|frist\nevaluation|frist\nförderpläne|frist\nzeugnis|frist\nende|frist";
}

/**
 * Sanitize-Callback für die iCal-URL.
 * Erlaubt nur HTTPS-URLs mit .ics-Endung oder CalDAV-Pfade.
 */
function gsh_tp_sanitize_url( $url ) {
    $url = esc_url_raw( trim( $url ) );
    if ( empty( $url ) ) {
        return '';
    }
    // Nur HTTPS erlauben (Sicherheit)
    if ( strpos( $url, 'https://' ) !== 0 ) {
        add_settings_error(
            'gsh_tp_ical_url',
            'invalid_scheme',
            __( 'Die iCal-URL muss HTTPS verwenden.', 'gsh-terminplan' ),
            'error'
        );
        return get_option( 'gsh_tp_ical_url', '' );
    }
    return $url;
}

/**
 * Rendert die Einstellungsseite.
 */
function gsh_tp_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Zugriff verweigert.', 'gsh-terminplan' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'GSH Terminplan – Einstellungen', 'gsh-terminplan' ); ?></h1>

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'gsh_tp_options' );
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gsh_tp_ical_url">
                            <?php esc_html_e( 'iCal-Feed-URL', 'gsh-terminplan' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url" id="gsh_tp_ical_url" name="gsh_tp_ical_url"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_ical_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://iserv.example.de/ical/..." />
                        <p class="description">
                            <?php esc_html_e( 'Die HTTPS-URL des IServ-Kalender-Exports (.ics).', 'gsh-terminplan' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gsh_tp_cache_duration">
                            <?php esc_html_e( 'Cache-Dauer (Sekunden)', 'gsh-terminplan' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="gsh_tp_cache_duration" name="gsh_tp_cache_duration"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_cache_duration', 3600 ) ); ?>"
                               min="300" max="86400" />
                        <p class="description">
                            <?php esc_html_e( 'Wie lange die Daten gecacht werden (min. 300s = 5 Min, max. 86400s = 24 Std).', 'gsh-terminplan' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gsh_tp_schuljahr_start">
                            <?php esc_html_e( 'Start Schulwoche 01', 'gsh-terminplan' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="date" id="gsh_tp_schuljahr_start" name="gsh_tp_schuljahr_start"
                               value="<?php echo esc_attr( get_option( 'gsh_tp_schuljahr_start', '2025-08-25' ) ); ?>" />
                        <p class="description">
                            <?php esc_html_e( 'Montag der ersten Schulwoche (nach den Sommerferien).', 'gsh-terminplan' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gsh_tp_quartal_grenzen">
                            <?php esc_html_e( 'Quartalsgrenzen', 'gsh-terminplan' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="gsh_tp_quartal_grenzen" name="gsh_tp_quartal_grenzen"
                                  rows="5" class="large-text"><?php
                            echo esc_textarea( get_option( 'gsh_tp_quartal_grenzen',
                                "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17"
                            ) );
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Pro Zeile ein Quartal: Startdatum|Enddatum (Format: JJJJ-MM-TT).', 'gsh-terminplan' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="gsh_tp_kategorie_mapping">
                            <?php esc_html_e( 'Kategorie-Stichwörter → Farben', 'gsh-terminplan' ); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="gsh_tp_kategorie_mapping" name="gsh_tp_kategorie_mapping"
                                  rows="12" class="large-text code" style="font-size:12px;"><?php
                            echo esc_textarea( get_option( 'gsh_tp_kategorie_mapping', gsh_tp_default_mapping() ) );
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Pro Zeile: stichwort|farbklasse – Das Stichwort wird im Termintext oder Kategoriefeld des IServ-Kalenders gesucht (Groß-/Kleinschreibung egal).', 'gsh-terminplan' ); ?>
                        </p>
                        <details style="margin-top:8px;">
                            <summary style="cursor:pointer;font-weight:600;color:#1a5276;">
                                <?php esc_html_e( '🎨 Verfügbare Farbklassen anzeigen', 'gsh-terminplan' ); ?>
                            </summary>
                            <table class="widefat" style="max-width:500px;margin-top:8px;">
                                <tr><td><strong>konferenz</strong></td><td style="background:#d6eaf8;color:#1a5276;padding:4px 8px;">🔵 Blau – Konferenzen, Sitzungen</td></tr>
                                <tr><td><strong>pruefung</strong></td><td style="background:#fadbd8;color:#922b21;padding:4px 8px;">🔴 Rot – Prüfungen, Klausuren</td></tr>
                                <tr><td><strong>projekt</strong></td><td style="background:#d5f5e3;color:#1e8449;padding:4px 8px;">🟢 Grün – Projekte, Fahrten, Sport</td></tr>
                                <tr><td><strong>frei</strong></td><td style="background:#eaecee;color:#616a6b;padding:4px 8px;">⚪ Grau – Ferien, Feiertage</td></tr>
                                <tr><td><strong>eltern</strong></td><td style="background:#fdebd0;color:#b9770e;padding:4px 8px;">🟠 Orange – Elternarbeit, Beratung</td></tr>
                                <tr><td><strong>frist</strong></td><td style="background:#fcf3cf;color:#7d6608;padding:4px 8px;">🟡 Gelb – Fristen, Hinweise</td></tr>
                            </table>
                        </details>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Einstellungen speichern', 'gsh-terminplan' ) ); ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Cache-Verwaltung', 'gsh-terminplan' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'gsh_tp_clear_cache', 'gsh_tp_cache_nonce' ); ?>
            <p>
                <button type="submit" name="gsh_tp_clear_cache" class="button button-secondary">
                    <?php esc_html_e( 'Cache jetzt leeren', 'gsh-terminplan' ); ?>
                </button>
            </p>
        </form>

        <?php
        // Cache leeren - mit Nonce-Prüfung
        if ( isset( $_POST['gsh_tp_clear_cache'] ) ) {
            if ( ! isset( $_POST['gsh_tp_cache_nonce'] )
                 || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gsh_tp_cache_nonce'] ) ), 'gsh_tp_clear_cache' ) ) {
                wp_die( esc_html__( 'Sicherheitsprüfung fehlgeschlagen.', 'gsh-terminplan' ) );
            }
            delete_transient( GSH_TP_CACHE_KEY );
            echo '<div class="notice notice-success"><p>' .
                 esc_html__( 'Cache wurde geleert. Beim nächsten Seitenaufruf werden die Daten neu abgerufen.', 'gsh-terminplan' ) .
                 '</p></div>';
        }
        ?>

        <hr />
        <h2><?php esc_html_e( 'Shortcode-Verwendung', 'gsh-terminplan' ); ?></h2>
        <p><code>[gsh_terminplan]</code> – <?php esc_html_e( 'Zeigt automatisch das aktuelle Quartal', 'gsh-terminplan' ); ?></p>
        <p><code>[gsh_terminplan quartal="1"]</code> – <?php esc_html_e( 'Zeigt Quartal 1', 'gsh-terminplan' ); ?></p>
        <p><code>[gsh_terminplan quartal="alle"]</code> – <?php esc_html_e( 'Zeigt alle Quartale mit Tab-Navigation', 'gsh-terminplan' ); ?></p>
    </div>
    <?php
}

// ============================================================
// 2. iCAL-DATEN ABRUFEN UND PARSEN
// ============================================================

/**
 * Ruft den iCal-Feed ab. Nutzt WordPress-Transients als Cache.
 * Bei Netzwerkfehler wird auf die letzte gültige Kopie zurückgegriffen.
 *
 * @return string iCal-Rohdaten oder leerer String bei Fehler.
 */
function gsh_tp_fetch_ical() {
    $cached = get_transient( GSH_TP_CACHE_KEY );
    if ( false !== $cached ) {
        return $cached;
    }

    $url = get_option( 'gsh_tp_ical_url', '' );
    if ( empty( $url ) ) {
        return '';
    }

    // Sicherer HTTP-Request mit Timeout
    $response = wp_remote_get( $url, array(
        'timeout'   => 15,
        'sslverify' => true,
        'headers'   => array(
            'Accept' => 'text/calendar',
        ),
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        // Fallback auf letzte gültige Daten
        $backup = get_option( GSH_TP_BACKUP_KEY, '' );
        if ( ! empty( $backup ) ) {
            // Kurzen Cache setzen, damit nicht bei jedem Request erneut versucht wird
            $cache_duration = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
            set_transient( GSH_TP_CACHE_KEY, $backup, min( $cache_duration, 600 ) );
            return $backup;
        }
        return '';
    }

    $body = wp_remote_retrieve_body( $response );

    // Validierung: Muss ein gültiger iCal-String sein
    if ( strpos( $body, 'BEGIN:VCALENDAR' ) === false ) {
        $backup = get_option( GSH_TP_BACKUP_KEY, '' );
        return ! empty( $backup ) ? $backup : '';
    }

    // Cache setzen
    $cache_duration = max( 300, absint( get_option( 'gsh_tp_cache_duration', 3600 ) ) );
    set_transient( GSH_TP_CACHE_KEY, $body, $cache_duration );

    // Backup für Offline-Fallback (in options-Tabelle, persistent)
    update_option( GSH_TP_BACKUP_KEY, $body, false );

    return $body;
}

/**
 * Parst iCal-Rohdaten in ein Array von Events.
 *
 * @param string $ical_data Roher iCal-String.
 * @return array Array von Events mit Schlüsseln: start, end, summary, description, categories, allday.
 */
function gsh_tp_parse_events( $ical_data ) {
    if ( empty( $ical_data ) ) {
        return array();
    }

    $events = array();
    // VEVENT-Blöcke extrahieren
    $pattern = '/BEGIN:VEVENT(.*?)END:VEVENT/s';
    preg_match_all( $pattern, $ical_data, $matches );

    if ( empty( $matches[1] ) ) {
        return array();
    }

    foreach ( $matches[1] as $vevent_block ) {
        $event = gsh_tp_parse_single_event( $vevent_block );
        if ( null !== $event ) {
            $events[] = $event;
        }
    }

    // Nach Startdatum sortieren
    usort( $events, function( $a, $b ) {
        return strcmp( $a['start'], $b['start'] );
    } );

    return $events;
}

/**
 * Parst einen einzelnen VEVENT-Block.
 *
 * @param string $block VEVENT-Inhalt (ohne BEGIN/END).
 * @return array|null Event-Array oder null bei ungültigen Daten.
 */
function gsh_tp_parse_single_event( $block ) {
    // Zeilenumbrüche normalisieren und gefaltete Zeilen zusammenführen (RFC 5545)
    $block = str_replace( "\r\n", "\n", $block );
    $block = preg_replace( '/\n[ \t]/', '', $block );

    $lines  = explode( "\n", trim( $block ) );
    $props  = array();

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( empty( $line ) ) {
            continue;
        }
        // Property und Wert trennen (berücksichtigt Parameter wie DTSTART;VALUE=DATE:...)
        if ( preg_match( '/^([A-Z\-]+)(?:;[^:]*)?:(.*)$/s', $line, $m ) ) {
            $key   = strtoupper( $m[1] );
            $value = $m[2];
            // Mehrere CATEGORIES werden kommasepariert
            if ( isset( $props[ $key ] ) && 'CATEGORIES' === $key ) {
                $props[ $key ] .= ',' . $value;
            } else {
                $props[ $key ] = $value;
            }
        }
    }

    // Pflichtfelder prüfen
    if ( empty( $props['DTSTART'] ) || empty( $props['SUMMARY'] ) ) {
        return null;
    }

    // Datum parsen
    $start  = gsh_tp_parse_date( $props['DTSTART'] );
    $allday = ( strlen( preg_replace( '/[^0-9]/', '', $props['DTSTART'] ) ) === 8 );

    $end = null;
    if ( ! empty( $props['DTEND'] ) ) {
        $end = gsh_tp_parse_date( $props['DTEND'] );
    } elseif ( ! empty( $props['DURATION'] ) ) {
        // Einfache Duration-Unterstützung (P1D, P2D etc.)
        $end = $start; // Fallback
    }

    if ( null === $end ) {
        $end = $start;
    }

    // iCal-Escaping rückgängig machen
    $summary     = gsh_tp_unescape_ical( $props['SUMMARY'] );
    $description = isset( $props['DESCRIPTION'] ) ? gsh_tp_unescape_ical( $props['DESCRIPTION'] ) : '';
    $categories  = isset( $props['CATEGORIES'] ) ? gsh_tp_unescape_ical( $props['CATEGORIES'] ) : '';

    return array(
        'start'       => $start,
        'end'         => $end,
        'summary'     => $summary,
        'description' => $description,
        'categories'  => $categories,
        'allday'      => $allday,
    );
}

/**
 * Parst ein iCal-Datum in das Format Y-m-d.
 */
function gsh_tp_parse_date( $dtstring ) {
    // Nur den Datumsteil verwenden (ggf. mit Zeitzone)
    $clean = preg_replace( '/[^0-9T]/', '', $dtstring );
    // Ganztägig: 20250825
    if ( strlen( $clean ) === 8 ) {
        return substr( $clean, 0, 4 ) . '-' . substr( $clean, 4, 2 ) . '-' . substr( $clean, 6, 2 );
    }
    // Mit Zeit: 20250825T090000
    if ( strlen( $clean ) >= 15 ) {
        return substr( $clean, 0, 4 ) . '-' . substr( $clean, 4, 2 ) . '-' . substr( $clean, 6, 2 );
    }
    return null;
}

/**
 * Entfernt iCal-Escaping (Backslash-Sequenzen).
 */
function gsh_tp_unescape_ical( $text ) {
    $text = str_replace( '\\n', "\n", $text );
    $text = str_replace( '\\,', ',', $text );
    $text = str_replace( '\\;', ';', $text );
    $text = str_replace( '\\\\', '\\', $text );
    return $text;
}

// ============================================================
// 3. SCHULWOCHEN-BERECHNUNG
// ============================================================

/**
 * Berechnet die Schulwochennummer für ein gegebenes Datum.
 *
 * @param string $date      Datum im Format Y-m-d.
 * @param string $sj_start  Start der Schulwoche 01 (Montag, Y-m-d).
 * @return int Schulwochennummer (0 = vor Schuljahresbeginn).
 */
function gsh_tp_get_schulwoche( $date, $sj_start ) {
    $d     = new DateTime( $date );
    $start = new DateTime( $sj_start );
    $diff  = $start->diff( $d );
    $days  = (int) $diff->format( '%r%a' );

    if ( $days < 0 ) {
        return 0;
    }

    return (int) floor( $days / 7 ) + 1;
}

/**
 * Bestimmt das aktuelle Quartal basierend auf den konfigurierten Grenzen.
 *
 * @return int Quartalsnummer (1-4) oder 1 als Fallback.
 */
function gsh_tp_get_current_quartal() {
    $today   = gmdate( 'Y-m-d' );
    $grenzen = gsh_tp_parse_quartal_grenzen();

    foreach ( $grenzen as $idx => $q ) {
        if ( $today >= $q['start'] && $today <= $q['end'] ) {
            return $idx + 1;
        }
    }

    return 1;
}

/**
 * Parst die Quartalsgrenzen aus der Option.
 *
 * @return array Array von Quartalen mit 'start' und 'end'.
 */
function gsh_tp_parse_quartal_grenzen() {
    $raw   = get_option( 'gsh_tp_quartal_grenzen',
        "2025-08-25|2025-10-31\n2025-11-03|2026-02-06\n2026-02-09|2026-05-01\n2026-05-04|2026-07-17"
    );
    $lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
    $result = array();

    foreach ( $lines as $line ) {
        $parts = explode( '|', $line );
        if ( count( $parts ) === 2 ) {
            $result[] = array(
                'start' => sanitize_text_field( trim( $parts[0] ) ),
                'end'   => sanitize_text_field( trim( $parts[1] ) ),
            );
        }
    }

    // Fallback: Wenn weniger als 4 Quartale, mit Defaults auffüllen
    while ( count( $result ) < 4 ) {
        $result[] = array( 'start' => '2025-08-25', 'end' => '2026-07-17' );
    }

    return $result;
}

// ============================================================
// 4. KATEGORIE → FARBE MAPPING (konfigurierbar)
// ============================================================

/**
 * Gibt die CSS-Klasse für einen Termin zurück.
 *
 * Das Mapping funktioniert in zwei Stufen:
 * 1. Zuerst wird das CATEGORIES-Feld aus dem iCal-Event geprüft.
 * 2. Falls keine Kategorie gesetzt ist, wird der Termintext (SUMMARY)
 *    nach konfigurierten Stichwörtern durchsucht.
 *
 * So funktioniert es in IServ:
 * - Beim Erstellen eines Termins in IServ kann ein Textfeld "Kategorie"
 *   befüllt werden (z.B. "Konferenz", "Prüfung", "Projekt").
 * - Dieses Textfeld wird beim iCal-Export als CATEGORIES übertragen.
 * - Wenn in IServ KEINE Kategorie gesetzt wird, greift der Fallback:
 *   Das Plugin durchsucht den Termintext nach Stichwörtern.
 * - Die Stichwörter sind in den Plugin-Einstellungen konfigurierbar.
 * - Die Farben selbst sind fest im CSS definiert – IServ muss KEINE
 *   Farben kennen.
 *
 * @param string $categories Kommaseparierte Kategorien aus dem iCal-Event.
 * @param string $summary    Termintext (SUMMARY) als Fallback.
 * @return string CSS-Klassensuffix.
 */
function gsh_tp_category_class( $categories, $summary = '' ) {
    // Mapping aus den Einstellungen laden (mit Cache)
    static $parsed_mapping = null;
    if ( null === $parsed_mapping ) {
        $parsed_mapping = gsh_tp_parse_kategorie_mapping();
    }

    // 1. Stufe: CATEGORIES-Feld prüfen (höchste Priorität)
    if ( ! empty( $categories ) ) {
        $cats_lower = strtolower( $categories );
        foreach ( $parsed_mapping as $keyword => $class ) {
            if ( strpos( $cats_lower, $keyword ) !== false ) {
                return $class;
            }
        }
    }

    // 2. Stufe: Termintext (SUMMARY) durchsuchen (Fallback)
    if ( ! empty( $summary ) ) {
        $summary_lower = strtolower( $summary );
        foreach ( $parsed_mapping as $keyword => $class ) {
            if ( strpos( $summary_lower, $keyword ) !== false ) {
                return $class;
            }
        }
    }

    return 'standard';
}

/**
 * Parst das Kategorie-Mapping aus den Plugin-Einstellungen.
 *
 * @return array Assoziatives Array: keyword => css-klasse
 */
function gsh_tp_parse_kategorie_mapping() {
    $raw   = get_option( 'gsh_tp_kategorie_mapping', gsh_tp_default_mapping() );
    $lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
    $result = array();

    foreach ( $lines as $line ) {
        // Kommentare ignorieren (Zeilen mit #)
        if ( strpos( $line, '#' ) === 0 ) {
            continue;
        }
        $parts = explode( '|', $line );
        if ( count( $parts ) === 2 ) {
            $keyword = strtolower( trim( $parts[0] ) );
            $class   = sanitize_html_class( trim( $parts[1] ) );
            if ( ! empty( $keyword ) && ! empty( $class ) ) {
                $result[ $keyword ] = $class;
            }
        }
    }

    return $result;
}

// ============================================================
// 5. SHORTCODE: RENDERING
// ============================================================

add_shortcode( 'gsh_terminplan', 'gsh_tp_shortcode' );

/**
 * Hauptfunktion: Rendert das Terminplan-Dashboard.
 *
 * @param array $atts Shortcode-Attribute.
 * @return string HTML-Ausgabe.
 */
function gsh_tp_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'quartal' => 'auto',
    ), $atts, 'gsh_terminplan' );

    // Daten laden und parsen
    $ical_data = gsh_tp_fetch_ical();
    if ( empty( $ical_data ) ) {
        return '<div class="gsh-tp-error">' .
               esc_html__( 'Keine Kalenderdaten verfügbar. Bitte die iCal-URL in den Plugin-Einstellungen prüfen.', 'gsh-terminplan' ) .
               '</div>';
    }

    $events    = gsh_tp_parse_events( $ical_data );
    $grenzen   = gsh_tp_parse_quartal_grenzen();
    $sj_start  = get_option( 'gsh_tp_schuljahr_start', '2025-08-25' );
    $active_q  = ( 'auto' === $atts['quartal'] ) ? gsh_tp_get_current_quartal() : absint( $atts['quartal'] );
    $show_all  = ( 'alle' === $atts['quartal'] );

    if ( $show_all ) {
        $active_q = gsh_tp_get_current_quartal();
    }

    // CSS + JS laden
    $output = gsh_tp_render_styles();
    $output .= gsh_tp_render_scripts();

    // Container
    $output .= '<div class="gsh-tp-dashboard" id="gsh-tp-dashboard">';

    // Druckkopfzeile (nur beim Drucken sichtbar)
    $output .= '<div class="gsh-tp-print-header">';
    $output .= '<h2>' . esc_html__( 'Gesamtschule Horst – Jahresterminplan', 'gsh-terminplan' ) . '</h2>';
    $output .= '<p>' . esc_html__( 'Schuljahr 2025/26', 'gsh-terminplan' );
    $output .= ' · ' . esc_html__( 'Stand: ', 'gsh-terminplan' ) . esc_html( wp_date( 'd.m.Y' ) );
    $output .= ' · ' . esc_html__( 'Quelle: IServ-Kalender', 'gsh-terminplan' ) . '</p>';
    $output .= '</div>';

    // Header mit Generierungszeitpunkt
    $output .= '<div class="gsh-tp-header">';
    $output .= '<h2 class="gsh-tp-title">' . esc_html__( 'Jahresterminplan', 'gsh-terminplan' ) . '</h2>';
    $output .= '<span class="gsh-tp-updated">' .
               esc_html__( 'Aktualisiert: ', 'gsh-terminplan' ) .
               esc_html( wp_date( 'd.m.Y, H:i', time() ) ) . ' Uhr</span>';
    $output .= '</div>';

    // Quartals-Tabs
    if ( $show_all || 'auto' === $atts['quartal'] ) {
        $output .= gsh_tp_render_tabs( $active_q );
    }

    // Filter-Buttons
    $output .= gsh_tp_render_filter_buttons();

    // Quartals-Tabellen
    $quartale_to_render = $show_all ? array( 1, 2, 3, 4 ) : array( $active_q );
    if ( 'auto' === $atts['quartal'] && ! $show_all ) {
        $quartale_to_render = array( 1, 2, 3, 4 );
    }

    foreach ( $quartale_to_render as $q ) {
        $is_active = ( $q === $active_q );
        $display   = $is_active ? 'block' : 'none';
        $q_data    = isset( $grenzen[ $q - 1 ] ) ? $grenzen[ $q - 1 ] : null;

        if ( null === $q_data ) {
            continue;
        }

        $q_labels = array(
            1 => 'Quartal 1 (August – Oktober)',
            2 => 'Quartal 2 (November – Januar)',
            3 => 'Quartal 3 (Februar – April)',
            4 => 'Quartal 4 (Mai – Juli)',
        );

        $output .= '<div class="gsh-tp-quartal" id="gsh-tp-q' . esc_attr( $q ) . '" ';
        $output .= 'style="display:' . esc_attr( $display ) . ';">';
        $output .= '<h3 class="gsh-tp-quartal-title" style="font-size:1rem;color:#1a5276;margin:0 0 .5rem 0;">';
        $output .= esc_html( isset( $q_labels[ $q ] ) ? $q_labels[ $q ] : 'Quartal ' . $q );
        $output .= '</h3>';
        $output .= gsh_tp_render_quartal_table( $events, $q_data, $sj_start, $q );

        // Drucklegende (nur im Druck sichtbar)
        $output .= '<div class="gsh-tp-print-legende" style="display:none;">';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#d6eaf8;border-left:2pt solid #2874a6;"></span> Konferenz</span>';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#fadbd8;border-left:2pt solid #c0392b;"></span> Prüfung</span>';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#d5f5e3;border-left:2pt solid #27ae60;"></span> Projekt</span>';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#eaecee;border-left:2pt solid #95a5a6;"></span> Ferien/Frei</span>';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#fdebd0;border-left:2pt solid #e67e22;"></span> Eltern</span>';
        $output .= '<span><span class="gsh-tp-legende-dot" style="background:#fcf3cf;border-left:2pt solid #f1c40f;"></span> Frist</span>';
        $output .= '</div>';

        $output .= '</div>';
    }

    // Druckbutton – druckt nur das aktuell sichtbare Quartal
    $output .= '<div class="gsh-tp-footer">';
    $output .= '<div class="gsh-tp-footer-left">';
    $output .= '<button type="button" class="gsh-tp-print-btn" onclick="gshTpPrintQuartal()">';
    $output .= esc_html__( '🖨️ Aktuelles Quartal drucken', 'gsh-terminplan' );
    $output .= '</button>';
    $output .= '<button type="button" class="gsh-tp-print-btn gsh-tp-print-btn-all" onclick="gshTpPrintAll()">';
    $output .= esc_html__( '🖨️ Alle Quartale drucken', 'gsh-terminplan' );
    $output .= '</button>';
    $output .= '</div>';
    $output .= '<span class="gsh-tp-source">' .
               esc_html__( 'Datenquelle: IServ-Kalender (automatisch synchronisiert)', 'gsh-terminplan' ) .
               '</span>';
    $output .= '</div>';

    $output .= '</div>'; // .gsh-tp-dashboard

    return $output;
}

/**
 * Rendert die Quartals-Tabs.
 */
function gsh_tp_render_tabs( $active_q ) {
    $labels = array(
        1 => 'Q1 (Aug–Okt)',
        2 => 'Q2 (Nov–Jan)',
        3 => 'Q3 (Feb–Apr)',
        4 => 'Q4 (Mai–Jul)',
    );

    $html = '<div class="gsh-tp-tabs" role="tablist">';
    foreach ( $labels as $num => $label ) {
        $active_class = ( $num === $active_q ) ? ' gsh-tp-tab-active' : '';
        $html .= '<button type="button" class="gsh-tp-tab' . esc_attr( $active_class ) . '" ';
        $html .= 'role="tab" aria-selected="' . ( $num === $active_q ? 'true' : 'false' ) . '" ';
        $html .= 'data-quartal="' . esc_attr( $num ) . '" ';
        $html .= 'onclick="gshTpSwitchTab(' . esc_attr( $num ) . ')">';
        $html .= esc_html( $label );
        $html .= '</button>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Rendert die Kategorie-Filter-Buttons.
 */
function gsh_tp_render_filter_buttons() {
    $categories = array(
        'konferenz' => array( 'label' => 'Konferenzen', 'icon' => '🔵' ),
        'pruefung'  => array( 'label' => 'Prüfungen',   'icon' => '🔴' ),
        'projekt'   => array( 'label' => 'Projekte',     'icon' => '🟢' ),
        'frei'      => array( 'label' => 'Ferien/Frei',  'icon' => '⚪' ),
        'eltern'    => array( 'label' => 'Eltern',       'icon' => '🟠' ),
        'frist'     => array( 'label' => 'Fristen',       'icon' => '🟡' ),
        'standard'  => array( 'label' => 'Sonstige',     'icon' => '⚫' ),
    );

    $html = '<div class="gsh-tp-filters">';
    foreach ( $categories as $key => $cat ) {
        $html .= '<button type="button" class="gsh-tp-filter gsh-tp-filter-active" ';
        $html .= 'data-category="' . esc_attr( $key ) . '" ';
        $html .= 'onclick="gshTpToggleFilter(this, \'' . esc_js( $key ) . '\')">';
        $html .= '<span class="gsh-tp-filter-icon">' . esc_html( $cat['icon'] ) . '</span> ';
        $html .= esc_html( $cat['label'] );
        $html .= '</button>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Rendert die Wochentabelle für ein Quartal.
 */
function gsh_tp_render_quartal_table( $events, $q_data, $sj_start, $q_num ) {
    $q_start = new DateTime( $q_data['start'] );
    $q_end   = new DateTime( $q_data['end'] );
    $today   = gmdate( 'Y-m-d' );

    // Auf Montag der Startwoche runden
    $dow = (int) $q_start->format( 'N' );
    if ( $dow > 1 ) {
        $q_start->modify( '-' . ( $dow - 1 ) . ' days' );
    }

    $html = '<table class="gsh-tp-table">';
    $html .= '<thead><tr>';
    $html .= '<th class="gsh-tp-col-sw">' . esc_html__( 'SW', 'gsh-terminplan' ) . '</th>';
    $html .= '<th>' . esc_html__( 'Montag', 'gsh-terminplan' ) . '</th>';
    $html .= '<th>' . esc_html__( 'Dienstag', 'gsh-terminplan' ) . '</th>';
    $html .= '<th>' . esc_html__( 'Mittwoch', 'gsh-terminplan' ) . '</th>';
    $html .= '<th>' . esc_html__( 'Donnerstag', 'gsh-terminplan' ) . '</th>';
    $html .= '<th>' . esc_html__( 'Freitag', 'gsh-terminplan' ) . '</th>';
    $html .= '<th class="gsh-tp-col-notes">' . esc_html__( 'Hinweise', 'gsh-terminplan' ) . '</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';

    $current_date = clone $q_start;
    $week_limit   = 50; // Sicherheit gegen Endlosschleifen

    while ( $current_date <= $q_end && $week_limit > 0 ) {
        $week_limit--;
        $sw = gsh_tp_get_schulwoche( $current_date->format( 'Y-m-d' ), $sj_start );

        $html .= '<tr class="gsh-tp-week-row">';

        // Schulwochennummer
        $sw_display = ( $sw > 0 ) ? sprintf( '%02d', $sw ) : '–';
        $html .= '<td class="gsh-tp-col-sw"><strong>' . esc_html( $sw_display ) . '</strong></td>';

        // Montag bis Freitag
        $week_notes = array();
        for ( $d = 0; $d < 5; $d++ ) {
            $day_date  = clone $current_date;
            $day_date->modify( '+' . $d . ' days' );
            $day_str   = $day_date->format( 'Y-m-d' );
            $day_label = $day_date->format( 'd.m.' );
            $is_today  = ( $day_str === $today );

            // Events für diesen Tag finden
            $day_events = gsh_tp_get_events_for_date( $events, $day_str );

            $td_classes = array( 'gsh-tp-day' );
            if ( $is_today ) {
                $td_classes[] = 'gsh-tp-today';
            }

            // Prüfen ob Ferientag
            $is_holiday = false;
            foreach ( $day_events as $ev ) {
                if ( gsh_tp_category_class( $ev['categories'], $ev['summary'] ) === 'frei' ) {
                    $is_holiday = true;
                    break;
                }
            }
            if ( $is_holiday ) {
                $td_classes[] = 'gsh-tp-holiday';
            }

            $html .= '<td class="' . esc_attr( implode( ' ', $td_classes ) ) . '">';
            $html .= '<span class="gsh-tp-date-label">' . esc_html( $day_label ) . '</span>';

            foreach ( $day_events as $ev ) {
                $cat_class = gsh_tp_category_class( $ev['categories'], $ev['summary'] );
                $tooltip   = ! empty( $ev['description'] ) ? $ev['description'] : $ev['summary'];
                $html .= '<div class="gsh-tp-event gsh-tp-cat-' . esc_attr( $cat_class ) . '" ';
                $html .= 'data-category="' . esc_attr( $cat_class ) . '" ';
                $html .= 'title="' . esc_attr( wp_strip_all_tags( $tooltip ) ) . '">';
                $html .= esc_html( $ev['summary'] );
                $html .= '</div>';

                // Fristen/Hinweise in die Anmerkungsspalte
                if ( in_array( $cat_class, array( 'frist' ), true ) ) {
                    $week_notes[] = $ev['summary'];
                }
            }

            $html .= '</td>';
        }

        // Anmerkungsspalte
        $html .= '<td class="gsh-tp-col-notes gsh-tp-notes-cell">';
        if ( ! empty( $week_notes ) ) {
            foreach ( $week_notes as $note ) {
                $html .= '<div class="gsh-tp-note">' . esc_html( $note ) . '</div>';
            }
        }
        $html .= '</td>';

        $html .= '</tr>';

        // Nächste Woche
        $current_date->modify( '+7 days' );
    }

    $html .= '</tbody></table>';
    return $html;
}

/**
 * Findet alle Events für ein bestimmtes Datum.
 * Berücksichtigt mehrtägige Events.
 */
function gsh_tp_get_events_for_date( $events, $date ) {
    $result = array();
    foreach ( $events as $ev ) {
        // Ganztägige Events: DTEND ist exklusiv (der Tag danach)
        $end_compare = $ev['end'];
        if ( $ev['allday'] && $ev['end'] > $ev['start'] ) {
            $end_dt = new DateTime( $ev['end'] );
            $end_dt->modify( '-1 day' );
            $end_compare = $end_dt->format( 'Y-m-d' );
        }

        if ( $date >= $ev['start'] && $date <= $end_compare ) {
            $result[] = $ev;
        }
    }
    return $result;
}

// ============================================================
// 6. CSS-STYLES (INLINE)
// ============================================================

function gsh_tp_render_styles() {
    return '<style>
/* ── GSH Terminplan Dashboard ── */
.gsh-tp-dashboard{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;max-width:1200px;margin:0 auto;color:#1a1a2e}
.gsh-tp-header{display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:3px solid #1a5276}
.gsh-tp-title{font-size:1.6rem;font-weight:700;color:#1a5276;margin:0}
.gsh-tp-updated{font-size:.8rem;color:#7f8c8d}

/* Tabs */
.gsh-tp-tabs{display:flex;gap:4px;margin-bottom:1rem}
.gsh-tp-tab{padding:.5rem 1.2rem;border:2px solid #d4e6f1;border-bottom:none;border-radius:6px 6px 0 0;background:#f7fbff;color:#1a5276;font-weight:600;font-size:.9rem;cursor:pointer;transition:all .2s}
.gsh-tp-tab:hover{background:#d4e6f1}
.gsh-tp-tab-active{background:#1a5276;color:#fff;border-color:#1a5276}

/* Filter */
.gsh-tp-filters{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:1rem}
.gsh-tp-filter{padding:6px 12px;border:1px solid #ddd;border-radius:20px;background:#fff;font-size:.8rem;cursor:pointer;transition:all .2s;opacity:1}
.gsh-tp-filter-active{border-color:#1a5276;box-shadow:0 0 0 1px #1a5276}
.gsh-tp-filter-inactive{opacity:.4;border-color:#eee}
.gsh-tp-filter-icon{font-size:.75rem}

/* Tabelle */
.gsh-tp-table{width:100%;border-collapse:collapse;font-size:.82rem;table-layout:fixed}
.gsh-tp-table thead th{background:#1a5276;color:#fff;padding:8px 6px;text-align:center;font-weight:600;font-size:.8rem;border:1px solid #15456a}
.gsh-tp-table tbody td{border:1px solid #e0e0e0;padding:4px 5px;vertical-align:top;min-height:60px}
.gsh-tp-col-sw{width:48px;text-align:center;background:#f0f4f8;font-size:.85rem}
.gsh-tp-col-notes{width:140px;background:#fefdf5}
.gsh-tp-date-label{display:block;font-size:.7rem;color:#95a5a6;margin-bottom:2px}
.gsh-tp-notes-cell{font-size:.75rem;color:#666}

/* Events */
.gsh-tp-event{padding:2px 5px;margin:1px 0;border-radius:3px;font-size:.75rem;line-height:1.3;cursor:default;border-left:3px solid transparent}
.gsh-tp-cat-konferenz{background:#d6eaf8;border-left-color:#2874a6;color:#1a5276}
.gsh-tp-cat-pruefung{background:#fadbd8;border-left-color:#c0392b;color:#922b21}
.gsh-tp-cat-projekt{background:#d5f5e3;border-left-color:#27ae60;color:#1e8449}
.gsh-tp-cat-frei{background:#eaecee;border-left-color:#95a5a6;color:#616a6b}
.gsh-tp-cat-eltern{background:#fdebd0;border-left-color:#e67e22;color:#b9770e}
.gsh-tp-cat-frist{background:#fcf3cf;border-left-color:#f1c40f;color:#7d6608}
.gsh-tp-cat-standard{background:#f2f3f4;border-left-color:#566573;color:#2c3e50}

/* Heute */
.gsh-tp-today{background:#eaf2f8;outline:2px solid #2874a6;outline-offset:-1px;position:relative}

/* Ferientag */
.gsh-tp-holiday{background:#f4f4f4}

/* Hinweise */
.gsh-tp-note{padding:2px 4px;margin:2px 0;background:#fef9e7;border-radius:2px;font-style:italic}

/* Footer */
.gsh-tp-footer{display:flex;justify-content:space-between;align-items:center;margin-top:1rem;padding-top:.75rem;border-top:1px solid #e0e0e0;flex-wrap:wrap;gap:.5rem}
.gsh-tp-footer-left{display:flex;gap:8px;flex-wrap:wrap}
.gsh-tp-print-btn{padding:8px 16px;background:#1a5276;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.85rem;transition:background .2s}
.gsh-tp-print-btn:hover{background:#154360}
.gsh-tp-print-btn-all{background:#566573}
.gsh-tp-print-btn-all:hover{background:#2c3e50}
.gsh-tp-source{font-size:.75rem;color:#aaa}

/* Druckkopfzeile (nur im Druck sichtbar) */
.gsh-tp-print-header{display:none}

/* Fehlermeldung */
.gsh-tp-error{padding:1.5rem;background:#fadbd8;border:1px solid #e74c3c;border-radius:8px;color:#922b21;text-align:center;font-size:.95rem}

/* Responsive */
@media(max-width:768px){
  .gsh-tp-table{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  .gsh-tp-tabs{overflow-x:auto;flex-wrap:nowrap}
  .gsh-tp-tab{white-space:nowrap;flex-shrink:0}
  .gsh-tp-header{flex-direction:column;gap:.5rem}
}

/* ── DRUCKANSICHT ── */
@media print{
  /* Alles außer Dashboard ausblenden */
  body>*:not(.gsh-tp-print-wrapper):not(#gsh-tp-dashboard){display:none!important}
  body .gsh-tp-dashboard{max-width:100%!important;margin:0!important;padding:0!important}

  /* Navigation/UI verstecken */
  .gsh-tp-tabs,.gsh-tp-filters,.gsh-tp-footer,
  .gsh-tp-print-btn,.gsh-tp-header .gsh-tp-updated,
  #wpadminbar,header,footer,nav,.site-header,.site-footer{display:none!important}

  /* Druckkopfzeile einblenden */
  .gsh-tp-print-header{display:block!important;text-align:center;margin-bottom:12pt;padding-bottom:6pt;border-bottom:2pt solid #1a5276}
  .gsh-tp-print-header h2{font-size:16pt;color:#1a5276;margin:0 0 2pt 0}
  .gsh-tp-print-header p{font-size:8pt;color:#666;margin:0}

  /* Sichtbare Quartale */
  .gsh-tp-quartal.gsh-tp-print-visible{display:block!important;page-break-after:always}
  .gsh-tp-quartal:not(.gsh-tp-print-visible){display:none!important}
  .gsh-tp-quartal:last-child{page-break-after:avoid}

  /* Tabelle optimieren */
  .gsh-tp-table{font-size:7.5pt!important;border-collapse:collapse}
  .gsh-tp-table thead th{background:#1a5276!important;color:#fff!important;padding:4pt 3pt;font-size:7pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .gsh-tp-table tbody td{padding:2pt 3pt;border:0.5pt solid #ccc}
  .gsh-tp-col-sw{width:30pt;background:#f0f4f8!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .gsh-tp-col-notes{width:90pt}
  .gsh-tp-date-label{font-size:6pt}

  /* Events im Druck */
  .gsh-tp-event{font-size:6.5pt!important;padding:1pt 3pt;margin:0.5pt 0;border-left-width:2pt;
    -webkit-print-color-adjust:exact;print-color-adjust:exact}
  .gsh-tp-cat-konferenz{background:#d6eaf8!important;border-left-color:#2874a6!important;color:#1a5276!important}
  .gsh-tp-cat-pruefung{background:#fadbd8!important;border-left-color:#c0392b!important;color:#922b21!important}
  .gsh-tp-cat-projekt{background:#d5f5e3!important;border-left-color:#27ae60!important;color:#1e8449!important}
  .gsh-tp-cat-frei{background:#eaecee!important;border-left-color:#95a5a6!important;color:#616a6b!important}
  .gsh-tp-cat-eltern{background:#fdebd0!important;border-left-color:#e67e22!important;color:#b9770e!important}
  .gsh-tp-cat-frist{background:#fcf3cf!important;border-left-color:#f1c40f!important;color:#7d6608!important}
  .gsh-tp-cat-standard{background:#f2f3f4!important;border-left-color:#566573!important;color:#2c3e50!important}

  /* Heute im Druck */
  .gsh-tp-today{outline:1.5pt solid #000!important;background:#eaf2f8!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .gsh-tp-holiday{background:#f0f0f0!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}

  /* Hinweise */
  .gsh-tp-note{font-size:6pt!important;background:#fef9e7!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .gsh-tp-notes-cell{font-size:6pt!important}

  /* Legende im Druck */
  .gsh-tp-print-legende{display:flex!important;gap:12pt;flex-wrap:wrap;margin-top:8pt;padding-top:6pt;border-top:0.5pt solid #ccc;font-size:6.5pt}
  .gsh-tp-print-legende span{display:inline-flex;align-items:center;gap:3pt}
  .gsh-tp-print-legende .gsh-tp-legende-dot{width:8pt;height:8pt;border-radius:1pt;-webkit-print-color-adjust:exact;print-color-adjust:exact}

  /* Seitenlayout */
  @page{size:A4 landscape;margin:10mm 12mm 10mm 12mm}
}
</style>';
}

// ============================================================
// 7. JAVASCRIPT (INLINE)
// ============================================================

function gsh_tp_render_scripts() {
    return '<script>
/* Quartals-Tab wechseln */
function gshTpSwitchTab(q){
  document.querySelectorAll(".gsh-tp-quartal").forEach(function(el){el.style.display="none"});
  document.querySelectorAll(".gsh-tp-tab").forEach(function(el){
    el.classList.remove("gsh-tp-tab-active");
    el.setAttribute("aria-selected","false");
  });
  var panel=document.getElementById("gsh-tp-q"+q);
  if(panel)panel.style.display="block";
  var tab=document.querySelector(".gsh-tp-tab[data-quartal=\""+q+"\"]");
  if(tab){tab.classList.add("gsh-tp-tab-active");tab.setAttribute("aria-selected","true");}
}

/* Kategorie-Filter toggle */
function gshTpToggleFilter(btn,cat){
  btn.classList.toggle("gsh-tp-filter-active");
  btn.classList.toggle("gsh-tp-filter-inactive");
  var isActive=btn.classList.contains("gsh-tp-filter-active");
  document.querySelectorAll(".gsh-tp-event[data-category=\""+cat+"\"]").forEach(function(ev){
    ev.style.display=isActive?"":"none";
  });
}

/**
 * Druckt nur das aktuell sichtbare Quartal.
 * - Markiert das aktive Quartal mit CSS-Klasse fuer print
 * - Oeffnet den Browser-Druckdialog
 * - Raumt nach dem Druck auf
 */
function gshTpPrintQuartal(){
  var panels=document.querySelectorAll(".gsh-tp-quartal");
  panels.forEach(function(p){p.classList.remove("gsh-tp-print-visible");});
  /* Finde das aktuell sichtbare Quartal */
  var active=document.querySelector(".gsh-tp-quartal[style*=\"display: block\"], .gsh-tp-quartal[style*=\"display:block\"]");
  if(!active){
    /* Fallback: erstes Quartal */
    active=panels[0];
  }
  if(active){
    active.classList.add("gsh-tp-print-visible");
  }
  window.print();
  /* Nach Druck aufräumen */
  setTimeout(function(){
    panels.forEach(function(p){p.classList.remove("gsh-tp-print-visible");});
  },500);
}

/**
 * Druckt alle Quartale (jedes auf einer eigenen Seite).
 */
function gshTpPrintAll(){
  var panels=document.querySelectorAll(".gsh-tp-quartal");
  panels.forEach(function(p){p.classList.add("gsh-tp-print-visible");});
  window.print();
  setTimeout(function(){
    panels.forEach(function(p){p.classList.remove("gsh-tp-print-visible");});
  },500);
}
</script>';
}

// ============================================================
// 8. DEAKTIVIERUNG: AUFRÄUMEN
// ============================================================

register_deactivation_hook( __FILE__, 'gsh_tp_deactivate' );
function gsh_tp_deactivate() {
    delete_transient( GSH_TP_CACHE_KEY );
}

// Optionale vollständige Deinstallation (nur bei Plugin-Löschung)
register_uninstall_hook( __FILE__, 'gsh_tp_uninstall' );
function gsh_tp_uninstall() {
    delete_option( 'gsh_tp_ical_url' );
    delete_option( 'gsh_tp_cache_duration' );
    delete_option( 'gsh_tp_schuljahr_start' );
    delete_option( 'gsh_tp_quartal_grenzen' );
    delete_option( GSH_TP_BACKUP_KEY );
    delete_transient( GSH_TP_CACHE_KEY );
}
