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
    $s = str_replace( ',',  '\\,',  $s );
    $s = str_replace( ';',  '\\;',  $s );
    $s = str_replace( "\r", '',     $s ); // CR entfernen — verhindert CRLF-Injection in ICS-Properties.
    $s = str_replace( "\n", '\\n',  $s );
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
    $lines[] = 'UID:' . gsh_tp_curriculr_ics_escape( $e['id'] ) . '@curriculr-planner';
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

function gsh_tp_curriculr_revisions_table() {
    global $wpdb;
    return $wpdb->prefix . 'curriculr_doc_revisions';
}

function gsh_tp_curriculr_install() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $docs_table = gsh_tp_curriculr_table();
    $docs_sql   = "CREATE TABLE $docs_table (
        schoolyear varchar(64) NOT NULL,
        json longtext NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        stage varchar(16) NOT NULL DEFAULT 'entwurf',
        updated_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        updated_by bigint unsigned NOT NULL DEFAULT 0,
        feed_token varchar(64) NOT NULL DEFAULT '',
        PRIMARY KEY  (schoolyear)
    ) $charset;";

    $rev_table = gsh_tp_curriculr_revisions_table();
    $rev_sql   = "CREATE TABLE $rev_table (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        schoolyear varchar(64) NOT NULL,
        version int unsigned NOT NULL DEFAULT 0,
        json longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
        author_sub varchar(255) NOT NULL DEFAULT '',
        author_name varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY sj_version (schoolyear, version)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $docs_sql );
    dbDelta( $rev_sql );
    update_option( 'gsh_tp_curriculr_db_version', 4, false );
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

function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version, $stage = 'entwurf' ) {
    global $wpdb;
    $table    = gsh_tp_curriculr_table();
    $sj       = sanitize_key( $sj );
    $existing = gsh_tp_curriculr_repo_get( $sj );
    $current  = $existing ? (int) $existing['version'] : 0;

    if ( gsh_tp_curriculr_version_decision( $current, $base_version ) === 'conflict' ) {
        $rev_table = gsh_tp_curriculr_revisions_table();
        $rev       = $wpdb->get_row( $wpdb->prepare(
            "SELECT author_name, created_at FROM {$rev_table} WHERE schoolyear = %s AND version = %d LIMIT 1",
            $sj, $current
        ) );
        return array(
            'status'     => 'conflict',
            'current'    => $existing,
            'authorName' => $rev ? (string) $rev->author_name : '',
            'savedAt'    => $rev ? (string) $rev->created_at  : '',
        );
    }

    $new_version = $current + 1;
    $token       = ( $existing && ! empty( $existing['feed_token'] ) )
        ? $existing['feed_token']
        : wp_generate_password( 32, false, false );

    $data = array(
        'schoolyear' => $sj,
        'json'       => wp_json_encode( $doc ),
        'version'    => $new_version,
        'stage'      => gsh_tp_curriculr_normalize_stage( $stage ),
        'updated_at' => current_time( 'mysql' ),
        'updated_by' => get_current_user_id(),
        'feed_token' => $token,
    );

    if ( $existing ) {
        $wpdb->update( $table, $data, array( 'schoolyear' => $sj ) );
    } else {
        $wpdb->insert( $table, $data );
    }

    // Revision-Snapshot + Retention-Prune.
    $json_str    = wp_json_encode( $doc );
    $guard       = function_exists( 'gsh_tp_curriculr_guard_current_claims' )
        ? gsh_tp_curriculr_guard_current_claims()
        : null;
    $author_sub  = $guard ? (string) ( $guard['sub'] ?? '' ) : '';
    $author_name = $guard ? (string) ( $guard['name'] ?? '' ) : '';
    gsh_tp_curriculr_repo_save_revision( $sj, $new_version, $json_str, $author_sub, $author_name );
    gsh_tp_curriculr_prune_revisions( $sj );

    return array(
        'status'     => 'ok',
        'version'    => $new_version,
        'stage'      => $data['stage'],
        'feed_token' => $token,
        'updated_at' => $data['updated_at'],
    );
}

function gsh_tp_curriculr_repo_save_revision( $sj, $version, $json_str, $author_sub = '', $author_name = '' ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $wpdb->insert(
        $table,
        array(
            'schoolyear'  => sanitize_key( $sj ),
            'version'     => (int) $version,
            'json'        => $json_str,
            'created_at'  => current_time( 'mysql' ),
            'author_sub'  => (string) $author_sub,
            'author_name' => (string) $author_name,
        )
    );
    return (int) $wpdb->insert_id;
}

function gsh_tp_curriculr_prune_revisions( $sj ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $sj    = sanitize_key( $sj );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table
             WHERE schoolyear = %s
               AND id NOT IN (
                 SELECT id FROM (
                   SELECT id FROM $table
                   WHERE schoolyear = %s
                   ORDER BY id DESC
                   LIMIT 50
                 ) AS keep
               )",
            $sj,
            $sj
        )
    );
}

/* ---------- WP: REST-Routen, Auth, CORS ---------- */

function gsh_tp_curriculr_feed_url( $sj, $token ) {
    return rest_url( 'curriculr/v1/feed/' . rawurlencode( $sj ) . '/' . $token . '.ics' );
}

function gsh_tp_curriculr_perm( $req ) {
    return gsh_tp_curriculr_guard_perm( $req );
}

function gsh_tp_curriculr_allowed_origin() {
    return get_option( 'gsh_tp_curriculr_origin', 'https://juwagn.github.io' );
}

function gsh_tp_curriculr_send_cors() {
    header( 'Access-Control-Allow-Origin: ' . esc_url_raw( gsh_tp_curriculr_allowed_origin() ) );
    header( 'Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS' );
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
    register_rest_route(
        'curriculr/v1',
        '/doc/(?P<sj>[a-z0-9_\-]+)/revisions',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_revisions_list',
            'permission_callback' => 'gsh_tp_curriculr_perm',
            'args'                => array( 'sj' => array( 'required' => true ) ),
        )
    );
    register_rest_route(
        'curriculr/v1',
        '/doc/(?P<sj>[a-z0-9_\-]+)/revisions/(?P<id>\d+)',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_revisions_get',
            'permission_callback' => 'gsh_tp_curriculr_perm',
            'args'                => array(
                'sj' => array( 'required' => true ),
                'id' => array( 'required' => true ),
            ),
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
            'stage'     => isset( $row['stage'] ) ? $row['stage'] : 'entwurf',
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

    $stage = gsh_tp_curriculr_normalize_stage( isset( $body['stage'] ) ? $body['stage'] : 'entwurf' );
    $res   = gsh_tp_curriculr_repo_put( $req['sj'], $body['doc'], (int) $body['baseVersion'], $stage );

    if ( $res['status'] === 'conflict' ) {
        return new WP_REST_Response(
            array(
                'error'         => 'conflict',
                'serverVersion' => (int) $res['current']['version'],
                'doc'           => json_decode( $res['current']['json'], true ),
                'authorName'    => (string) ( $res['authorName'] ?? '' ),
                'savedAt'       => (string) ( $res['savedAt']    ?? '' ),
            ),
            409
        );
    }

    // Sicherheit: Feed-Reuse-Refresh nur, wenn der Plan öffentlich ist.
    if ( $res['stage'] === 'oeffentlich' ) {
        gsh_tp_curriculr_after_put( $req['sj'], $res['feed_token'] );
    }

    return new WP_REST_Response(
        array(
            'status'    => 'ok',
            'version'   => $res['version'],
            'stage'     => $res['stage'],
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

function gsh_tp_curriculr_rest_revisions_list( $req ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $sj    = sanitize_key( $req['sj'] );
    $rows  = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, schoolyear, version, created_at, author_sub, author_name FROM $table WHERE schoolyear = %s ORDER BY id DESC LIMIT 100",
            $sj
        ),
        ARRAY_A
    );
    if ( $rows === null ) {
        return new WP_REST_Response( array( 'error' => 'db_error' ), 500 );
    }
    return new WP_REST_Response( $rows ? array_values( $rows ) : array(), 200 );
}

function gsh_tp_curriculr_rest_revisions_get( $req ) {
    global $wpdb;
    $table = gsh_tp_curriculr_revisions_table();
    $sj    = sanitize_key( $req['sj'] );
    $id    = (int) $req['id'];
    $row   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, schoolyear, version, json, created_at FROM $table WHERE id = %d AND schoolyear = %s",
            $id,
            $sj
        ),
        ARRAY_A
    );
    if ( ! $row ) {
        return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
    }
    return new WP_REST_Response(
        array(
            'id'         => (int) $row['id'],
            'schoolyear' => $row['schoolyear'],
            'version'    => (int) $row['version'],
            'json'       => json_decode( $row['json'], true ),
            'created_at' => $row['created_at'],
        ),
        200
    );
}

/* ---------- WP: Feed-Reuse-Verdrahtung (Spec §3/§7) ---------- */

function gsh_tp_curriculr_profile_for( $sj ) {
    // Nur EXPLIZITE Zuordnung. Kein Rückfall auf das aktive Profil
    // (Nicht-Disruption: das Live-Profil darf nie versehentlich umgebogen werden).
    $map = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $sj  = sanitize_key( $sj );
    return ( is_array( $map ) && isset( $map[ $sj ] ) ) ? $map[ $sj ] : '';
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

function gsh_tp_curriculr_backup_cron() {
    global $wpdb;
    $table = gsh_tp_curriculr_table();
    $rows  = $wpdb->get_results( "SELECT schoolyear, json, feed_token FROM $table", ARRAY_A );
    if ( ! $rows ) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $backup_dir = $upload_dir['basedir'] . '/curriculr-backups';
    wp_mkdir_p( $backup_dir );

    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();
    global $wp_filesystem;

    $stamp = gmdate( 'Y-m-d' );
    foreach ( $rows as $row ) {
        $sj  = sanitize_key( $row['schoolyear'] );
        $doc = json_decode( $row['json'], true );
        $wp_filesystem->put_contents(
            "$backup_dir/{$sj}-{$stamp}.json",
            $row['json'],
            FS_CHMOD_FILE
        );
        if ( is_array( $doc ) ) {
            $wp_filesystem->put_contents(
                "$backup_dir/{$sj}-{$stamp}.ics",
                gsh_tp_curriculr_build_ics( $doc ),
                FS_CHMOD_FILE
            );
        }
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
            if ( (int) get_option( 'gsh_tp_curriculr_db_version', 0 ) < 4 ) {
                gsh_tp_curriculr_install();
            }
        }
    );

    add_action( 'gsh_tp_curriculr_daily_backup', 'gsh_tp_curriculr_backup_cron' );
    add_action(
        'wp_loaded',
        function () {
            if ( ! wp_next_scheduled( 'gsh_tp_curriculr_daily_backup' ) ) {
                wp_schedule_event( strtotime( 'tomorrow 02:00:00' ), 'daily', 'gsh_tp_curriculr_daily_backup' );
            }
        }
    );

    register_deactivation_hook(
        dirname( __FILE__ ) . '/gsh-terminplan.php',
        function () {
            $timestamp = wp_next_scheduled( 'gsh_tp_curriculr_daily_backup' );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'gsh_tp_curriculr_daily_backup' );
            }
        }
    );
}
