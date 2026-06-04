<?php
/**
 * Curriculr Data Layer
 *
 * Speichert das Planner-Dokument (REST), liefert einen Token-geschützten
 * ICS-Feed und verdrahtet ihn per Feed-Reuse mit dem bestehenden Renderer.
 * Prozedural, keine Klassen — passend zur Plugin-Konvention (AGENTS.md).
 *
 * Pure Funktionen (build_ics, version_decision, validate_envelope) sind ohne
 * WordPress lauffähig und werden mit tests/curriculr/*.php geprüft.
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'GSH_TP_CURRICULR_TEST' ) ) {
    // Direktaufruf im Browser verhindern, Tests (CLI) aber zulassen.
    if ( PHP_SAPI !== 'cli' ) {
        exit;
    }
}

/* ---------- Pure: ICS-Erzeugung (spiegelt ics-export.ts buildIcs) ---------- */

function gsh_tp_curriculr_ics_escape( $s ) {
    $s = str_replace( '\\', '\\\\', (string) $s );
    $s = str_replace( ',', '\\,', $s );
    $s = str_replace( ';', '\\;', $s );
    $s = str_replace( "\n", '\\n', $s );
    return $s;
}

function gsh_tp_curriculr_ics_fmt_date( $iso ) {
    // 'YYYY-MM-DD' -> 'YYYYMMDD'
    return str_replace( '-', '', (string) $iso );
}

function gsh_tp_curriculr_ics_fmt_datetime( $iso, $time ) {
    return gsh_tp_curriculr_ics_fmt_date( $iso ) . 'T' . str_replace( ':', '', (string) $time ) . '00';
}

function gsh_tp_curriculr_ics_fold( $line ) {
    // Zeilenfaltung nach RFC 5545: max 75 Oktette/Zeile, Folgezeilen mit Space-Präfix.
    // UTF-8-Mehrbyte-Zeichen werden nie zerschnitten.
    if ( strlen( $line ) <= 75 ) {
        return $line;
    }
    $out   = array();
    $len   = strlen( $line );
    $i     = 0;
    $first = true;
    while ( $i < $len ) {
        $max  = $first ? 75 : 74; // Folgezeilen tragen 1 Space-Präfix.
        $take = min( $max, $len - $i );
        // Nicht mitten in einer UTF-8-Sequenz schneiden: zurück, solange das
        // nächste Byte ein Continuation-Byte (10xxxxxx) ist.
        while ( $take > 0 && isset( $line[ $i + $take ] ) && ( ord( $line[ $i + $take ] ) & 0xC0 ) === 0x80 ) {
            $take--;
        }
        if ( $take <= 0 ) {
            $take = $max; // Sicherheitsnetz (sollte nie eintreten).
        }
        $chunk = substr( $line, $i, $take );
        $out[] = $first ? $chunk : ' ' . $chunk;
        $i    += $take;
        $first = false;
    }
    return implode( "\r\n", $out );
}

function gsh_tp_curriculr_build_event( $e, $cats_by_id ) {
    $lines   = array( 'BEGIN:VEVENT' );
    $lines[] = 'UID:' . $e['id'] . '@curriculr-planner';
    $lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
    $lines[] = 'SUMMARY:' . gsh_tp_curriculr_ics_escape( $e['title'] );

    if ( ! empty( $e['allDay'] ) ) {
        $end_exclusive = ( new DateTime( $e['end'] ) )->modify( '+1 day' )->format( 'Ymd' );
        $lines[]       = 'DTSTART;VALUE=DATE:' . gsh_tp_curriculr_ics_fmt_date( $e['start'] );
        $lines[]       = 'DTEND;VALUE=DATE:' . $end_exclusive;
    } else {
        $st      = ( isset( $e['startTime'] ) && $e['startTime'] !== null ) ? $e['startTime'] : '00:00';
        $et      = ( isset( $e['endTime'] ) && $e['endTime'] !== null ) ? $e['endTime'] : '23:59';
        $lines[] = 'DTSTART:' . gsh_tp_curriculr_ics_fmt_datetime( $e['start'], $st );
        $lines[] = 'DTEND:' . gsh_tp_curriculr_ics_fmt_datetime( $e['end'], $et );
    }

    if ( ! empty( $e['location'] ) ) {
        $lines[] = 'LOCATION:' . gsh_tp_curriculr_ics_escape( $e['location'] );
    }

    $desc_parts = array();
    if ( ! empty( $e['notes'] ) ) {
        $desc_parts[] = $e['notes'];
    }
    if ( ! empty( $e['groups'] ) ) {
        $desc_parts[] = 'Gruppen: ' . implode( ', ', $e['groups'] );
    }
    if ( $desc_parts ) {
        $lines[] = 'DESCRIPTION:' . gsh_tp_curriculr_ics_escape( implode( "\n", $desc_parts ) );
    }

    if ( isset( $e['categoryId'] ) && isset( $cats_by_id[ $e['categoryId'] ] ) ) {
        $lines[] = 'CATEGORIES:' . gsh_tp_curriculr_ics_escape( $cats_by_id[ $e['categoryId'] ] );
    }

    $lines[] = 'END:VEVENT';
    return $lines;
}

function gsh_tp_curriculr_build_ics( $doc ) {
    $name        = isset( $doc['meta']['name'] ) ? $doc['meta']['name'] : 'Schulterminplan';
    $cats_by_id  = array();
    if ( ! empty( $doc['categories'] ) ) {
        foreach ( $doc['categories'] as $c ) {
            $cats_by_id[ $c['id'] ] = $c['label'];
        }
    }
    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Curriculr Planner//DE',
        'CALSCALE:GREGORIAN',
        'X-WR-CALNAME:' . gsh_tp_curriculr_ics_escape( $name ),
        'X-WR-TIMEZONE:Europe/Berlin',
    );
    if ( ! empty( $doc['events'] ) ) {
        foreach ( $doc['events'] as $e ) {
            $lines = array_merge( $lines, gsh_tp_curriculr_build_event( $e, $cats_by_id ) );
        }
    }
    $lines[]  = 'END:VCALENDAR';
    $folded   = array_map( 'gsh_tp_curriculr_ics_fold', $lines );
    return implode( "\r\n", $folded ) . "\r\n";
}

/* ---------- Pure: Versions-Entscheidung (Konflikt-Schutz, Spec §5) ---------- */

function gsh_tp_curriculr_version_decision( $current, $base ) {
    // $current = gespeicherte Server-Version (0 = noch keine Zeile).
    // $base    = baseVersion des Clients. Nur exakte Übereinstimmung darf schreiben.
    return ( (int) $base === (int) $current ) ? 'ok' : 'conflict';
}

/* ---------- Pure: Envelope-Validierung des PUT-Bodys (Spec §6) ---------- */

function gsh_tp_curriculr_validate_envelope( $body ) {
    if ( ! is_array( $body ) ) {
        return array( 'valid' => false, 'errors' => array( 'body_not_object' ) );
    }
    $errors = array();
    if ( ! isset( $body['doc'] ) || ! is_array( $body['doc'] ) ) {
        $errors[] = 'doc_missing';
    } elseif ( ! isset( $body['doc']['events'] ) || ! is_array( $body['doc']['events'] ) ) {
        $errors[] = 'doc_events_missing';
    }
    if ( ! array_key_exists( 'baseVersion', $body ) || ! is_int( $body['baseVersion'] ) ) {
        $errors[] = 'baseVersion_missing';
    }
    return array( 'valid' => empty( $errors ), 'errors' => $errors );
}

/* ---------- Pure: Stufen-Normalisierung (entwurf|genehmigt|oeffentlich) ---------- */

function gsh_tp_curriculr_normalize_stage( $stage ) {
    $s     = strtolower( (string) $stage );
    $valid = array( 'entwurf', 'genehmigt', 'oeffentlich' );
    return in_array( $s, $valid, true ) ? $s : 'entwurf';
}

/* ---------- WP: Tabelle + Repository ---------- */

function gsh_tp_curriculr_table() {
    global $wpdb;
    return $wpdb->prefix . 'curriculr_docs';
}

function gsh_tp_curriculr_install() {
    global $wpdb;
    $table   = gsh_tp_curriculr_table();
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE $table (
        schoolyear varchar(64) NOT NULL,
        json longtext NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        updated_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        updated_by bigint unsigned NOT NULL DEFAULT 0,
        feed_token varchar(64) NOT NULL DEFAULT '',
        PRIMARY KEY  (schoolyear)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'gsh_tp_curriculr_db_version', 1, false );
}

function gsh_tp_curriculr_repo_get( $sj ) {
    global $wpdb;
    $table = gsh_tp_curriculr_table();
    $sj    = sanitize_key( $sj );
    $row   = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM $table WHERE schoolyear = %s", $sj ),
        ARRAY_A
    );
    return $row ? $row : null;
}

function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version ) {
    global $wpdb;
    $table    = gsh_tp_curriculr_table();
    $sj       = sanitize_key( $sj );
    $existing = gsh_tp_curriculr_repo_get( $sj );
    $current  = $existing ? (int) $existing['version'] : 0;

    if ( gsh_tp_curriculr_version_decision( $current, $base_version ) === 'conflict' ) {
        return array( 'status' => 'conflict', 'current' => $existing );
    }

    $new_version = $current + 1;
    $token       = ( $existing && ! empty( $existing['feed_token'] ) )
        ? $existing['feed_token']
        : wp_generate_password( 32, false, false );

    $data = array(
        'schoolyear' => $sj,
        'json'       => wp_json_encode( $doc ),
        'version'    => $new_version,
        'updated_at' => current_time( 'mysql' ),
        'updated_by' => get_current_user_id(),
        'feed_token' => $token,
    );

    if ( $existing ) {
        $wpdb->update( $table, $data, array( 'schoolyear' => $sj ) );
    } else {
        $wpdb->insert( $table, $data );
    }

    return array(
        'status'     => 'ok',
        'version'    => $new_version,
        'feed_token' => $token,
        'updated_at' => $data['updated_at'],
    );
}

/* ---------- WP: REST-Routen, Auth, CORS ---------- */

function gsh_tp_curriculr_feed_url( $sj, $token ) {
    return rest_url( 'curriculr/v1/feed/' . rawurlencode( $sj ) . '/' . $token . '.ics' );
}

function gsh_tp_curriculr_perm() {
    // Application Passwords authentifizieren den Request als WP-User;
    // danach greift current_user_can wie bei einer normalen Session.
    return current_user_can( 'manage_options' );
}

function gsh_tp_curriculr_allowed_origin() {
    return get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' );
}

function gsh_tp_curriculr_send_cors() {
    header( 'Access-Control-Allow-Origin: ' . gsh_tp_curriculr_allowed_origin() );
    header( 'Access-Control-Allow-Methods: GET, PUT, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
    header( 'Vary: Origin' );
}

function gsh_tp_curriculr_cors_filter( $served, $result, $request, $server ) {
    if ( strpos( $request->get_route(), '/curriculr/v1' ) === 0 ) {
        gsh_tp_curriculr_send_cors();
        if ( $request->get_method() === 'OPTIONS' ) {
            status_header( 200 );
            return true; // Preflight kurzschließen.
        }
    }
    return $served;
}

function gsh_tp_curriculr_register_rest() {
    register_rest_route(
        'curriculr/v1',
        '/doc/(?P<sj>[a-z0-9_\-]+)',
        array(
            array(
                'methods'             => 'GET',
                'callback'            => 'gsh_tp_curriculr_rest_get',
                'permission_callback' => 'gsh_tp_curriculr_perm',
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => 'gsh_tp_curriculr_rest_put',
                'permission_callback' => 'gsh_tp_curriculr_perm',
            ),
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/health',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_health',
            'permission_callback' => 'gsh_tp_curriculr_perm',
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/feed/(?P<sj>[a-z0-9_\-]+)/(?P<token>[A-Za-z0-9]+)\.ics',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_feed',
            'permission_callback' => '__return_true',
        )
    );
}

function gsh_tp_curriculr_rest_health() {
    return new WP_REST_Response( array( 'ok' => true, 'plugin' => GSH_TP_VERSION ), 200 );
}

function gsh_tp_curriculr_rest_get( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row ) {
        return new WP_REST_Response( array( 'exists' => false ), 404 );
    }
    return new WP_REST_Response(
        array(
            'exists'    => true,
            'doc'       => json_decode( $row['json'], true ),
            'version'   => (int) $row['version'],
            'updatedAt' => $row['updated_at'],
            'feedUrl'   => gsh_tp_curriculr_feed_url( $req['sj'], $row['feed_token'] ),
        ),
        200
    );
}

function gsh_tp_curriculr_rest_put( $req ) {
    $body = $req->get_json_params();
    $v    = gsh_tp_curriculr_validate_envelope( $body );
    if ( ! $v['valid'] ) {
        return new WP_REST_Response( array( 'error' => 'invalid', 'details' => $v['errors'] ), 400 );
    }

    $res = gsh_tp_curriculr_repo_put( $req['sj'], $body['doc'], (int) $body['baseVersion'] );

    if ( $res['status'] === 'conflict' ) {
        return new WP_REST_Response(
            array(
                'error'         => 'conflict',
                'serverVersion' => (int) $res['current']['version'],
                'doc'           => json_decode( $res['current']['json'], true ),
            ),
            409
        );
    }

    gsh_tp_curriculr_after_put( $req['sj'], $res['feed_token'] );

    return new WP_REST_Response(
        array(
            'status'    => 'ok',
            'version'   => $res['version'],
            'updatedAt' => $res['updated_at'],
            'feedUrl'   => gsh_tp_curriculr_feed_url( $req['sj'], $res['feed_token'] ),
        ),
        200
    );
}

/* ---------- WP: Öffentlicher Token-Feed (IServ + WP-Anzeige) ---------- */

function gsh_tp_curriculr_rest_feed( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row || ! hash_equals( (string) $row['feed_token'], (string) $req['token'] ) ) {
        return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
    }
    $doc = json_decode( $row['json'], true );
    $ics = gsh_tp_curriculr_build_ics( $doc );

    if ( ! headers_sent() ) {
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename="' . sanitize_key( $req['sj'] ) . '.ics"' );
        header( 'Cache-Control: max-age=300' );
    }
    echo $ics; // phpcs:ignore -- rohe ICS-Ausgabe, bewusst kein wp_die.
    exit;
}

/* ---------- WP: Feed-Reuse-Verdrahtung (Spec §3/§7) ---------- */

function gsh_tp_curriculr_profile_for( $sj ) {
    // Schuljahr -> Profil-ID. Map-Option erlaubt explizite Zuordnung;
    // Fallback = aktives Profil.
    $map = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $sj  = sanitize_key( $sj );
    if ( is_array( $map ) && isset( $map[ $sj ] ) ) {
        return $map[ $sj ];
    }
    return function_exists( 'gsh_tp_active_profile_id' ) ? gsh_tp_active_profile_id() : '';
}

function gsh_tp_curriculr_after_put( $sj, $token ) {
    $pid = gsh_tp_curriculr_profile_for( $sj );
    if ( ! $pid ) {
        return;
    }
    $feed_url = gsh_tp_curriculr_feed_url( $sj, $token );
    $profiles = gsh_tp_get_profiles();
    $changed  = false;
    foreach ( $profiles as &$p ) {
        if ( isset( $p['id'] ) && $p['id'] === $pid && ( ! isset( $p['ical_url'] ) || $p['ical_url'] !== $feed_url ) ) {
            $p['ical_url'] = $feed_url;
            $changed       = true;
        }
    }
    unset( $p );
    if ( $changed ) {
        update_option( 'gsh_tp_profiles', $profiles, true );
    }
    // Bestehenden Refresh anstoßen → Anzeige-Cache sofort aktuell.
    if ( function_exists( 'gsh_tp_do_refresh' ) ) {
        gsh_tp_do_refresh( $pid );
    }
}

/* ---------- WP: Hooks (nur unter WordPress aktiv) ---------- */

if ( function_exists( 'add_action' ) ) {
    add_action( 'rest_api_init', 'gsh_tp_curriculr_register_rest' );
    add_filter( 'rest_pre_serve_request', 'gsh_tp_curriculr_cors_filter', 10, 4 );

    // Tabelle bei Aktivierung anlegen ...
    register_activation_hook( dirname( __FILE__ ) . '/gsh-terminplan.php', 'gsh_tp_curriculr_install' );

    // ... und defensiv, falls das Plugin per Update statt Reaktivierung kam.
    add_action(
        'admin_init',
        function () {
            if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 1 ) {
                gsh_tp_curriculr_install();
            }
        }
    );
}
