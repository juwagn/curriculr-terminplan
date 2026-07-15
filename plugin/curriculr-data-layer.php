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
    // Defensiv: Altdaten in der DB oder kaputte Events dürfen den Feed nie
    // mit einer uncaught DateTime-Exception zum 500er machen (Spec SEC-MED-002).
    // Fehlende Keys fallen auf '' zurück; ein Event ohne valides start/end wird
    // komplett übersprungen statt eine Exception zu werfen.
    if ( ! is_array( $e ) ) {
        return array();
    }
    $id    = $e['id'] ?? '';
    $title = $e['title'] ?? '';
    $start = $e['start'] ?? '';
    $end   = $e['end'] ?? '';
    if ( ! gsh_tp_curriculr_is_iso_date( $start ) || ! gsh_tp_curriculr_is_iso_date( $end ) ) {
        return array();
    }

    $lines   = array( 'BEGIN:VEVENT' );
    $lines[] = 'UID:' . gsh_tp_curriculr_ics_escape( $id ) . '@curriculr-planner';
    $lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
    $lines[] = 'SUMMARY:' . gsh_tp_curriculr_ics_escape( $title );

    if ( ! empty( $e['allDay'] ) ) {
        $end_exclusive = ( new DateTime( $end ) )->modify( '+1 day' )->format( 'Ymd' );
        $lines[]       = 'DTSTART;VALUE=DATE:' . gsh_tp_curriculr_ics_fmt_date( $start );
        $lines[]       = 'DTEND;VALUE=DATE:' . $end_exclusive;
    } else {
        $st      = ( isset( $e['startTime'] ) && $e['startTime'] !== null ) ? $e['startTime'] : '00:00';
        $et      = ( isset( $e['endTime'] ) && $e['endTime'] !== null ) ? $e['endTime'] : '23:59';
        $lines[] = 'DTSTART:' . gsh_tp_curriculr_ics_fmt_datetime( $start, $st );
        $lines[] = 'DTEND:' . gsh_tp_curriculr_ics_fmt_datetime( $end, $et );
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

    if ( ! empty( $e['groups'] ) && is_array( $e['groups'] ) ) {
        $groups = array();
        foreach ( $e['groups'] as $g ) {
            $g = trim( (string) $g );
            if ( '' !== $g ) {
                $groups[] = gsh_tp_curriculr_ics_escape( $g );
            }
        }
        if ( $groups ) {
            // Multi-Value wie CATEGORIES: Werte einzeln escaped, Separator-Komma roh.
            $lines[] = 'X-GSH-GROUPS:' . implode( ',', $groups );
        }
    }

    $lines[] = 'END:VEVENT';
    return $lines;
}

function gsh_tp_curriculr_build_ics( $doc, $target_group = null ) {
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
            if ( null !== $target_group ) {
                // Missing or non-array groups key → treat as no restriction (same as groups: []).
                $groups = ( isset( $e['groups'] ) && is_array( $e['groups'] ) ) ? $e['groups'] : array();
                if ( ! empty( $groups ) && ! in_array( $target_group, $groups, true ) ) {
                    continue;
                }
            }
            $lines = array_merge( $lines, gsh_tp_curriculr_build_event( $e, $cats_by_id ) );
        }
    }

    // Schulferien und gesetzliche Feiertage aus dem Schuljahr als VEVENT-Einträge.
    // Ohne diese Einträge fehlen Ferienzeiträume im ICS-Feed und werden in der
    // WP-Anzeige nicht als graue Ferien-Zeilen dargestellt.
    // Holidays have no groups — always included regardless of target_group.
    if ( ! empty( $doc['schoolyear']['holidays'] ) && is_array( $doc['schoolyear']['holidays'] ) ) {
        foreach ( $doc['schoolyear']['holidays'] as $h ) {
            if ( empty( $h['start'] ) || empty( $h['end'] ) || empty( $h['label'] ) ) {
                continue;
            }
            if ( ! gsh_tp_curriculr_is_iso_date( $h['start'] ) || ! gsh_tp_curriculr_is_iso_date( $h['end'] ) ) {
                continue;
            }
            $uid   = 'holiday-' . ( isset( $h['id'] ) ? $h['id'] : md5( $h['start'] . $h['label'] ) ) . '@curriculr-planner';
            // Holiday.end ist inklusiv (wie PlanEvent.end) → DTEND exklusiv = +1 Tag
            $dtend = ( new DateTime( $h['end'] ) )->modify( '+1 day' )->format( 'Ymd' );
            $lines = array_merge( $lines, array(
                'BEGIN:VEVENT',
                'UID:' . gsh_tp_curriculr_ics_escape( $uid ),
                'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
                'SUMMARY:' . gsh_tp_curriculr_ics_escape( $h['label'] ),
                'DTSTART;VALUE=DATE:' . gsh_tp_curriculr_ics_fmt_date( $h['start'] ),
                'DTEND;VALUE=DATE:' . $dtend,
                'CATEGORIES:feiertage',
                'END:VEVENT',
            ) );
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
    } else {
        foreach ( $body['doc']['events'] as $i => $e ) {
            if ( ! gsh_tp_curriculr_validate_event( $e ) ) {
                $errors[] = 'invalid_event_' . $i;
            }
        }
    }
    if ( ! array_key_exists( 'baseVersion', $body ) || ! is_int( $body['baseVersion'] ) ) {
        $errors[] = 'baseVersion_missing';
    }
    return array( 'valid' => empty( $errors ), 'errors' => $errors );
}

/* ---------- Pure: Event-Tiefenvalidierung (Spec SEC-MED-002) ---------- */
// Minimalprüfung genau der Felder, die in build_event()/ICS-Ausgabe fließen.
// Kein volles Schema — nur die Typen/Formate, deren Fehlen build_event() zum
// Absturz bringen (DateTime-Exception) oder eine Property-Injection erlauben
// würde (CRLF in start/end via ics_fmt_date/ics_fmt_datetime, unescaped).

function gsh_tp_curriculr_validate_event( $e ) {
    if ( ! is_array( $e ) ) {
        return false;
    }
    if ( ! isset( $e['id'] ) || ! is_string( $e['id'] ) ) {
        return false;
    }
    if ( ! isset( $e['title'] ) || ! is_string( $e['title'] ) ) {
        return false;
    }
    if ( ! isset( $e['start'] ) || ! gsh_tp_curriculr_is_iso_date( $e['start'] ) ) {
        return false;
    }
    if ( ! isset( $e['end'] ) || ! gsh_tp_curriculr_is_iso_date( $e['end'] ) ) {
        return false;
    }
    if ( array_key_exists( 'startTime', $e ) && $e['startTime'] !== null ) {
        if ( ! is_string( $e['startTime'] ) || preg_match( '/^\d{2}:\d{2}$/', $e['startTime'] ) !== 1 ) {
            return false;
        }
    }
    if ( array_key_exists( 'endTime', $e ) && $e['endTime'] !== null ) {
        if ( ! is_string( $e['endTime'] ) || preg_match( '/^\d{2}:\d{2}$/', $e['endTime'] ) !== 1 ) {
            return false;
        }
    }
    if ( array_key_exists( 'allDay', $e ) && ! is_bool( $e['allDay'] ) ) {
        return false;
    }
    return true;
}

/* ---------- Pure: Datei-Upload-Dekodierung (manueller Import, Spec 2026-07-04) ---------- */

function gsh_tp_curriculr_decode_doc_upload( $raw ) {
    $decoded = json_decode( (string) $raw, true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
        return array( 'valid' => false, 'errors' => array( 'invalid_json' ) );
    }
    $envelope = gsh_tp_curriculr_validate_envelope( array( 'doc' => $decoded, 'baseVersion' => 0 ) );
    if ( ! $envelope['valid'] ) {
        return array( 'valid' => false, 'errors' => $envelope['errors'] );
    }
    return array( 'valid' => true, 'doc' => $decoded );
}

/* ---------- Pure: Stufen-Normalisierung (entwurf|genehmigt|oeffentlich) ---------- */

function gsh_tp_curriculr_normalize_stage( $stage ) {
    $s     = strtolower( (string) $stage );
    $valid = array( 'entwurf', 'genehmigt', 'oeffentlich' );
    return in_array( $s, $valid, true ) ? $s : 'entwurf';
}

/* ---------- Pure: Quartalsgrenzen aus dem Planner-Doc ableiten ---------- */
// Die Anzeige (gsh_tp_table) rendert nur Wochen innerhalb der Profil-Quartals-
// fenster. Ohne Sync zeigen veraltete Grenzen (z. B. 2025/26-Defaults) einen
// leeren Plan, obwohl der ICS-Cache korrekt ist. Semantik spiegelt
// schoolweeks.ts getQuarterRange: Grenzen auf Freitag der ISO-Woche snappen.

function gsh_tp_curriculr_is_iso_date( $d ) {
    return is_string( $d ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) === 1;
}

function gsh_tp_curriculr_snap_friday( $iso ) {
    $d   = new DateTime( $iso );
    $dow = (int) $d->format( 'N' );
    if ( 5 !== $dow ) {
        $d->modify( sprintf( '%+d days', 5 - $dow ) );
    }
    return $d->format( 'Y-m-d' );
}

function gsh_tp_curriculr_monday_of_week( $iso ) {
    $d   = new DateTime( $iso );
    $dow = (int) $d->format( 'N' );
    if ( $dow > 1 ) {
        $d->modify( '-' . ( $dow - 1 ) . ' days' );
    }
    return $d->format( 'Y-m-d' );
}

function gsh_tp_curriculr_quartal_grenzen_from_doc( $doc ) {
    if ( ! is_array( $doc ) || ! isset( $doc['schoolyear'] ) || ! is_array( $doc['schoolyear'] ) ) {
        return '';
    }
    $sy    = $doc['schoolyear'];
    $first = isset( $sy['firstSchoolDay'] ) ? $sy['firstSchoolDay'] : '';
    $last  = isset( $sy['lastSchoolDay'] ) ? $sy['lastSchoolDay'] : '';
    $qb    = isset( $sy['quarterBoundaries'] ) ? $sy['quarterBoundaries'] : null;
    if ( ! gsh_tp_curriculr_is_iso_date( $first ) || ! gsh_tp_curriculr_is_iso_date( $last ) ) {
        return '';
    }
    if ( ! is_array( $qb ) || count( $qb ) !== 3 ) {
        return '';
    }
    $ends = array();
    foreach ( array_values( $qb ) as $b ) {
        if ( ! gsh_tp_curriculr_is_iso_date( $b ) ) {
            return '';
        }
        $ends[] = gsh_tp_curriculr_snap_friday( $b );
    }
    // Folgequartal beginnt am Montag NACH dem Grenz-Freitag: gsh_tp_table()
    // richtet den Quartalsstart auf Montag aus — ein Samstag-Start würde die
    // Grenzwoche in zwei Quartalen doppelt rendern.
    $q2 = ( new DateTime( $ends[0] ) )->modify( '+3 days' )->format( 'Y-m-d' );
    $q3 = ( new DateTime( $ends[1] ) )->modify( '+3 days' )->format( 'Y-m-d' );
    $q4 = ( new DateTime( $ends[2] ) )->modify( '+3 days' )->format( 'Y-m-d' );
    return $first . '|' . $ends[0] . "\n"
         . $q2 . '|' . $ends[1] . "\n"
         . $q3 . '|' . $ends[2] . "\n"
         . $q4 . '|' . $last;
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

function gsh_tp_curriculr_repo_put_conflict( $sj, $current, $existing ) {
    global $wpdb;
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

function gsh_tp_curriculr_repo_put( $sj, $doc, $base_version, $stage = 'entwurf', $author_override = null ) {
    global $wpdb;
    $table    = gsh_tp_curriculr_table();
    $sj       = sanitize_key( $sj );
    $existing = gsh_tp_curriculr_repo_get( $sj );
    $current  = $existing ? (int) $existing['version'] : 0;

    // Schnellpfad: offensichtlicher Konflikt ohne DB-Write (Tests nutzen ihn direkt).
    if ( gsh_tp_curriculr_version_decision( $current, $base_version ) === 'conflict' ) {
        return gsh_tp_curriculr_repo_put_conflict( $sj, $current, $existing );
    }

    $new_version = $current + 1;
    $token       = ( $existing && ! empty( $existing['feed_token'] ) )
        ? $existing['feed_token']
        : wp_generate_password( 32, false, false );

    $json_str = wp_json_encode( $doc );
    $data = array(
        'schoolyear' => $sj,
        'json'       => $json_str,
        'version'    => $new_version,
        'stage'      => gsh_tp_curriculr_normalize_stage( $stage ),
        'updated_at' => current_time( 'mysql' ),
        'updated_by' => get_current_user_id(),
        'feed_token' => $token,
    );

    if ( $existing ) {
        // Atomarer Update: version-Bedingung im WHERE schließt die Lost-Update-Race
        // zwischen dem SELECT oben und diesem UPDATE (CODE-MED-002). Betrifft der
        // Write 0 Zeilen, hat ein paralleler Request denselben baseVersion bereits
        // committet -> frisches repo_get, Conflict wie im Schnellpfad.
        $affected = $wpdb->update( $table, $data, array( 'schoolyear' => $sj, 'version' => $current ) );
        if ( ! $affected ) {
            $fresh = gsh_tp_curriculr_repo_get( $sj );
            return gsh_tp_curriculr_repo_put_conflict( $sj, $fresh ? (int) $fresh['version'] : $current, $fresh );
        }
    } else {
        // Insert-Kollision (Duplicate-PK auf schoolyear durch Race) -> false statt
        // PHP-Warning; wpdb::insert() gibt bei Fehler false zurück.
        $ok = $wpdb->insert( $table, $data );
        if ( ! $ok ) {
            $fresh = gsh_tp_curriculr_repo_get( $sj );
            return gsh_tp_curriculr_repo_put_conflict( $sj, $fresh ? (int) $fresh['version'] : $current, $fresh );
        }
    }

    // Revision-Snapshot + Retention-Prune.
    $guard       = null !== $author_override
        ? $author_override
        : ( function_exists( 'gsh_tp_curriculr_guard_current_claims' ) ? gsh_tp_curriculr_guard_current_claims() : null );
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

function gsh_tp_curriculr_feed_url_group( $sj, $token, $group ) {
    return rest_url( 'curriculr/v1/feed/' . rawurlencode( $sj ) . '/' . $token . '/' . str_replace( '.', '%2E', rawurlencode( $group ) ) . '.ics' );
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
        '/docs',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_doc_list',
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
        '/feed/(?P<sj>[a-z0-9_\-]+)/(?P<token>[A-Za-z0-9]+)/(?P<group>[^/]+)\.ics',
        array(
            'methods'             => 'GET',
            'callback'            => 'gsh_tp_curriculr_rest_feed_group',
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
    register_rest_route(
        'curriculr/v1',
        '/profile-map',
        array(
            'methods'             => 'POST',
            'callback'            => 'gsh_tp_curriculr_rest_profile_map_put',
            'permission_callback' => 'gsh_tp_curriculr_perm',
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

    // Feed-Reuse-Refresh für alle Stages — auch Entwurf-Vorschau (token-geschützt) soll aktuelle Daten zeigen.
    gsh_tp_curriculr_after_put( $req['sj'], $res['feed_token'] );

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

function gsh_tp_curriculr_rest_feed_group( $req ) {
    $row = gsh_tp_curriculr_repo_get( $req['sj'] );
    if ( ! $row || ! hash_equals( (string) $row['feed_token'], (string) $req['token'] ) ) {
        return new WP_REST_Response( array( 'error' => 'not_found' ), 404 );
    }
    $group = sanitize_text_field( urldecode( $req['group'] ) );
    $doc   = json_decode( $row['json'], true );
    $ics   = gsh_tp_curriculr_build_ics( $doc, $group );

    if ( ! headers_sent() ) {
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $req['sj'] ) . '-' . sanitize_file_name( $group ) . '.ics"' );
        header( 'Cache-Control: max-age=300' );
    }
    // phpcs:ignore -- raw ICS output, bewusst kein wp_die.
    echo $ics;
    exit;
}

/**
 * POST /curriculr/v1/profile-map
 *
 * New form (4.24.0): { sj: string, label: string, groups: string[] }
 * Old form (kompat):  { sj: string, mappings: [{profileId: string, group: string|null}] }
 *
 * @since 4.22.0 (rewritten 4.24.0)
 */
function gsh_tp_curriculr_rest_profile_map_put( $req ) {
    $body = $req->get_json_params();
    $sj   = isset( $body['sj'] ) ? sanitize_key( $body['sj'] ) : '';

    if ( '' === $sj ) {
        return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'sj required' ), 400 );
    }

    // Detect form: new = has 'groups' key (even if empty array)
    if ( array_key_exists( 'groups', $body ) ) {
        $label  = isset( $body['label'] ) ? sanitize_text_field( $body['label'] ) : $sj;
        $groups = is_array( $body['groups'] ) ? $body['groups'] : array();
        return gsh_tp_curriculr_provision_schoolyear( $sj, $label, $groups );
    }

    // Old form: { sj, mappings:[{profileId, group}] } — Kompat-Pfad
    $mappings = isset( $body['mappings'] ) ? $body['mappings'] : null;
    if ( ! is_array( $mappings ) || empty( $mappings ) ) {
        return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'mappings required (old form) or groups required (new form)' ), 400 );
    }

    $normalised = array();
    foreach ( $mappings as $m ) {
        if ( ! is_array( $m ) ) {
            return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'each mapping must be an object' ), 400 );
        }
        $pid = sanitize_key( $m['profileId'] ?? '' );
        if ( '' === $pid ) {
            return new WP_REST_Response( array( 'code' => 'invalid_input', 'message' => 'profileId required' ), 400 );
        }
        $group        = ( isset( $m['group'] ) && is_string( $m['group'] ) && '' !== $m['group'] )
            ? sanitize_text_field( $m['group'] ) : null;
        $normalised[] = array( 'profileId' => $pid, 'group' => $group );
    }

    $map        = get_option( 'gsh_tp_curriculr_profile_map', array() );
    $map        = is_array( $map ) ? $map : array();
    $map[ $sj ] = $normalised;
    update_option( 'gsh_tp_curriculr_profile_map', $map, false );

    return new WP_REST_Response( array( 'updated' => true ), 200 );
}

/**
 * Provisioniert ein Schuljahr mit Haupt-Kalender + optionalen Gruppen-Kalendern.
 *
 * Legt das Schuljahr (falls fehlt) und alle angeforderten Kalender an.
 * Entfernte verwaltete Gruppen-Kalender werden als orphaned markiert (nicht gelöscht).
 * Vorhandene verwaiste Kalender werden reaktiviert wenn ihre Gruppe wieder genannt wird.
 *
 * @since 4.24.0
 * @param  string $sj     Schuljahr-Schlüssel (z.B. 'sj_2026_27').
 * @param  string $label  Schuljahr-Label (z.B. '2026/27').
 * @param  array  $groups Gruppenname-Liste (Strings).
 * @return WP_REST_Response
 */
function gsh_tp_curriculr_provision_schoolyear( $sj, $label, $groups ) {
    // Deduplizieren und sanitieren der Gruppen
    $requested = array();
    foreach ( (array) $groups as $g ) {
        $g = sanitize_text_field( $g );
        if ( '' !== $g && ! in_array( $g, $requested, true ) ) {
            $requested[] = $g;
        }
    }

    // Limit: max 7 Gruppen (= 8 Kalender inkl. Haupt)
    if ( count( $requested ) > 7 ) {
        return new WP_REST_Response(
            array( 'code' => 'limit_exceeded', 'message' => 'Max 7 group calendars per schoolyear (8 total)' ),
            400
        );
    }

    $schoolyears = gsh_tp_get_schoolyears();

    // Schuljahr finden oder anlegen
    $sy_idx = null;
    foreach ( $schoolyears as $i => $sy ) {
        if ( $sy['key'] === $sj ) {
            $sy_idx = $i;
            break;
        }
    }
    if ( null === $sy_idx ) {
        $schoolyears[] = array(
            'key'       => sanitize_key( $sj ),
            'label'     => sanitize_text_field( $label ),
            'is_active' => false,
            'created'   => current_time( 'Y-m-d' ),
            'shared'    => array( 'quartal_grenzen' => '', 'schuljahr_start' => '', 'cache_duration' => 3600 ),
            'calendars' => array(),
        );
        $sy_idx = count( $schoolyears ) - 1;
    }

    $sy = &$schoolyears[ $sy_idx ];

    // Haupt-Kalender sicherstellen
    $has_main = false;
    foreach ( $sy['calendars'] as $cal ) {
        if ( null === $cal['group'] ) {
            $has_main = true;
            break;
        }
    }
    if ( ! $has_main ) {
        array_unshift( $sy['calendars'], array(
            'group'    => null,
            'label'    => sanitize_text_field( $label ) . ' · Alle Termine',
            'ical_url' => '',
            'is_draft' => false,
            'managed'  => true,
            'orphaned' => false,
        ) );
    }

    // Gruppen-Kalender sicherstellen / un-orphanen
    foreach ( $requested as $group ) {
        $found = false;
        foreach ( $sy['calendars'] as &$cal ) {
            if ( $cal['group'] === $group ) {
                $cal['orphaned'] = false; // un-orphan on re-add
                $found = true;
                break;
            }
        }
        unset( $cal );
        if ( ! $found ) {
            $sy['calendars'][] = array(
                'group'    => $group,
                'label'    => $group,
                'ical_url' => '',
                'is_draft' => false,
                'managed'  => true,
                'orphaned' => false,
            );
        }
    }

    // Verwaltete Gruppen-Kalender die nicht (mehr) angefordert sind → orphaned
    foreach ( $sy['calendars'] as &$cal ) {
        if ( null === $cal['group'] ) {
            continue; // Haupt niemals orphanen
        }
        if ( ! empty( $cal['managed'] ) && ! in_array( $cal['group'], $requested, true ) ) {
            $cal['orphaned'] = true;
        }
    }
    unset( $cal );

    gsh_tp_save_schoolyears( $schoolyears );

    // Response: alle nicht-orphaned Kalender mit Feed-URL (leer wenn noch kein Token)
    $result = array();
    foreach ( $sy['calendars'] as $cal ) {
        if ( ! empty( $cal['orphaned'] ) ) {
            continue;
        }
        $result[] = array(
            'group'   => $cal['group'],
            'label'   => $cal['label'],
            'feedUrl' => ( '' !== $cal['ical_url'] ) ? $cal['ical_url'] : null,
        );
    }

    return new WP_REST_Response( array( 'updated' => true, 'calendars' => $result ), 200 );
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

function gsh_tp_curriculr_rest_doc_list( $req = null ) {
    global $wpdb;
    $docs_table = gsh_tp_curriculr_table();
    $rev_table  = gsh_tp_curriculr_revisions_table();

    $rows = $wpdb->get_results(
        "SELECT schoolyear, json, version, stage, updated_at FROM $docs_table ORDER BY updated_at DESC",
        ARRAY_A
    );
    if ( $rows === null ) {
        return new WP_REST_Response( array( 'error' => 'db_error' ), 500 );
    }

    // Fetch all revisions in one query: map schoolyear→author_name
    $revisions = $wpdb->get_results(
        "SELECT r.schoolyear, r.author_name
         FROM {$rev_table} r
         INNER JOIN {$docs_table} d ON d.schoolyear = r.schoolyear AND d.version = r.version",
        ARRAY_A
    );
    $author_map = array();
    if ( $revisions ) {
        foreach ( $revisions as $rev ) {
            $author_map[ (string) $rev['schoolyear'] ] = (string) $rev['author_name'];
        }
    }

    $out = array();
    foreach ( (array) $rows as $row ) {
        $doc  = json_decode( $row['json'], true );
        $name = ( is_array( $doc ) && isset( $doc['meta']['name'] ) && '' !== $doc['meta']['name'] )
            ? (string) $doc['meta']['name']
            : (string) $row['schoolyear'];

        $out[] = array(
            'sj'         => (string) $row['schoolyear'],
            'name'       => $name,
            'stage'      => isset( $row['stage'] ) ? (string) $row['stage'] : 'entwurf',
            'version'    => (int) $row['version'],
            'updatedAt'  => (string) $row['updated_at'],
            'authorName' => isset( $author_map[ (string) $row['schoolyear'] ] ) ? $author_map[ (string) $row['schoolyear'] ] : '',
        );
    }

    return new WP_REST_Response( $out, 200 );
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
    if ( ! is_array( $map ) || ! isset( $map[ $sj ] ) ) {
        return array();
    }
    $val = $map[ $sj ];
    // Lazy migration: old format stored a plain profile-ID string.
    if ( is_string( $val ) && '' !== $val ) {
        $normalised = array( array( 'profileId' => $val, 'group' => null ) );
        $map[ $sj ] = $normalised;
        update_option( 'gsh_tp_curriculr_profile_map', $map, false );
        return $normalised;
    }
    return is_array( $val ) ? $val : array();
}

/* ---------- Kategorien-Sync: Planner ist Quelle für Label + Farbe (Spec 2026-07-15) ---------- */
// Merge, nie löschen: Match per id (Fallback slug) -> Label/Farbe überschreiben,
// Unbekannte anlegen (keywords leer), WP-Stichwörter und WP-eigene Kategorien
// bleiben. Fail-silent: Rückgabewert wird vom PUT-Pfad ignoriert.

function gsh_tp_curriculr_sync_categories( $doc ) {
    if ( ! is_array( $doc ) || empty( $doc['categories'] ) || ! is_array( $doc['categories'] ) ) {
        return false;
    }
    if ( ! function_exists( 'gsh_tp_get_categories' ) || ! function_exists( 'gsh_tp_save_categories' ) ) {
        return false;
    }

    $existing = gsh_tp_get_categories();
    $by_id    = array();
    $by_slug  = array();
    foreach ( $existing as $i => $cat ) {
        $by_id[ (string) ( $cat['id'] ?? '' ) ] = $i;
        if ( ! empty( $cat['slug'] ) ) {
            $by_slug[ (string) $cat['slug'] ] = $i;
        }
    }

    $hex     = '/^#[0-9a-fA-F]{6}$/';
    $changed = false;

    foreach ( $doc['categories'] as $pc ) {
        if ( ! is_array( $pc ) ) {
            continue;
        }
        $id    = sanitize_key( $pc['id'] ?? '' );
        $label = sanitize_text_field( (string) ( $pc['label'] ?? '' ) );
        $color = (string) ( $pc['color'] ?? '' );
        $slug  = sanitize_key( $pc['slug'] ?? '' );
        if ( '' === $id || '' === $label || ! preg_match( $hex, $color ) ) {
            continue;
        }
        $idx = $by_id[ $id ] ?? ( '' !== $slug && isset( $by_slug[ $slug ] ) ? $by_slug[ $slug ] : null );
        if ( null !== $idx ) {
            if ( $existing[ $idx ]['label'] !== $label || $existing[ $idx ]['color'] !== $color ) {
                $existing[ $idx ]['label'] = $label;
                $existing[ $idx ]['color'] = $color;
                $changed                   = true;
            }
        } else {
            $existing[] = array(
                'id'       => $id,
                'label'    => $label,
                'color'    => $color,
                'slug'     => ( '' !== $slug ? $slug : $id ),
                'keywords' => array(),
            );
            $by_id[ $id ] = count( $existing ) - 1;
            $changed      = true;
        }
    }

    if ( ! $changed ) {
        return true;
    }
    return false !== gsh_tp_save_categories( $existing );
}

/**
 * Nach erfolgreichem PUT: ICS-Cache + Feed-URL für alle Kalender dieses Schuljahres aktualisieren.
 *
 * Dual-path: schoolyears-nativ wenn das Schuljahr in gsh_tp_schoolyears liegt,
 * sonst legacy-Pfad via gsh_tp_curriculr_profile_map (Kompat für alte Installs).
 *
 * @since 4.6.0 (dual-path seit 4.24.0)
 * @param  string $sj    Schuljahr-Schlüssel.
 * @param  string $token Feed-Token.
 */
function gsh_tp_curriculr_after_put( $sj, $token ) {
    // Kategorien-Sync: Planner-Farben/-Labels gewinnen bei jedem Push.
    $row0 = gsh_tp_curriculr_repo_get( $sj );
    if ( $row0 ) {
        $doc0 = json_decode( $row0['json'], true );
        if ( is_array( $doc0 ) ) {
            gsh_tp_curriculr_sync_categories( $doc0 );
        }
    }

    // Check schoolyears first (new nested model)
    $schoolyears = gsh_tp_get_schoolyears();
    $sy_idx      = null;
    foreach ( $schoolyears as $i => $sy ) {
        if ( $sy['key'] === $sj ) {
            $sy_idx = $i;
            break;
        }
    }

    if ( null === $sy_idx ) {
        // Legacy install with explicit profile_map mapping → old flat path.
        if ( ! empty( gsh_tp_curriculr_profile_for( $sj ) ) ) {
            gsh_tp_curriculr_after_put_legacy( $sj, $token );
            return;
        }

        // Weder Schuljahr noch Legacy-Mapping: bisher stilles No-op — das Doc
        // lag in wp_curriculr_docs, aber Anzeige/Admin kannten es nicht (kein
        // Kalender, keine Zuordnung). Auto-Provision legt das Schuljahr
        // INAKTIV an (is_active=false, Live-Anzeige unberührt), damit es im
        // Admin sichtbar/zuordenbar ist und der ICS-Cache gefüllt wird.
        $row   = gsh_tp_curriculr_repo_get( $sj );
        $doc   = $row ? json_decode( $row['json'], true ) : null;
        $label = '';
        if ( is_array( $doc ) ) {
            if ( isset( $doc['schoolyear']['label'] ) && is_string( $doc['schoolyear']['label'] ) && '' !== $doc['schoolyear']['label'] ) {
                $label = $doc['schoolyear']['label'];
            } elseif ( isset( $doc['meta']['name'] ) && is_string( $doc['meta']['name'] ) ) {
                $label = $doc['meta']['name'];
            }
        }
        if ( '' === $label ) {
            $label = $sj;
        }
        gsh_tp_curriculr_provision_schoolyear( $sj, $label, array() );

        $schoolyears = gsh_tp_get_schoolyears();
        $sy_idx      = null;
        foreach ( $schoolyears as $i => $sy ) {
            if ( $sy['key'] === $sj ) {
                $sy_idx = $i;
                break;
            }
        }
        if ( null === $sy_idx ) {
            return; // Provisionierung nicht möglich (z. B. Schoolyears-Store fehlt).
        }
    }

    gsh_tp_curriculr_after_put_nested( $schoolyears, $sy_idx, $sj, $token );
}

/**
 * after_put for nested schoolyears model.
 *
 * @since 4.24.0
 */
function gsh_tp_curriculr_after_put_nested( &$schoolyears, $sy_idx, $sj, $token ) {
    $sy  = &$schoolyears[ $sy_idx ];
    $row = gsh_tp_curriculr_repo_get( $sj );
    $doc = $row ? json_decode( $row['json'], true ) : null;

    $grenzen  = is_array( $doc ) ? gsh_tp_curriculr_quartal_grenzen_from_doc( $doc ) : '';
    $sj_start = '';
    if ( '' !== $grenzen && isset( $doc['schoolyear']['firstSchoolDay'] ) && gsh_tp_curriculr_is_iso_date( $doc['schoolyear']['firstSchoolDay'] ) ) {
        $sj_start = gsh_tp_curriculr_monday_of_week( $doc['schoolyear']['firstSchoolDay'] );
    }

    if ( '' !== $grenzen )  { $sy['shared']['quartal_grenzen'] = $grenzen; }
    if ( '' !== $sj_start ) { $sy['shared']['schuljahr_start'] = $sj_start; }

    foreach ( $sy['calendars'] as &$cal ) {
        if ( ! empty( $cal['orphaned'] ) ) {
            continue;
        }
        $group    = $cal['group'];
        $cal_id   = gsh_tp_calendar_id( $sj, $group );
        $feed_url = ( null === $group )
            ? gsh_tp_curriculr_feed_url( $sj, $token )
            : gsh_tp_curriculr_feed_url_group( $sj, $token, $group );

        $cal['ical_url'] = $feed_url;

        if ( $row && function_exists( 'gsh_tp_ck' ) && is_array( $doc ) ) {
            $pid_key = sanitize_key( $cal_id );
            update_option( gsh_tp_ck( 'gsh_tp_ical_', $pid_key ), gsh_tp_curriculr_build_ics( $doc, $group ), false );
            delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid_key ) );
        }
    }
    unset( $cal );

    gsh_tp_save_schoolyears( $schoolyears );
}

/**
 * after_put legacy path: reads gsh_tp_curriculr_profile_map → gsh_tp_profiles (flat).
 * Runs only when schoolyears model doesn't have this sj yet.
 *
 * @since 4.24.0 (extracted from original after_put)
 */
function gsh_tp_curriculr_after_put_legacy( $sj, $token ) {
    $mappings = gsh_tp_curriculr_profile_for( $sj );
    if ( empty( $mappings ) ) {
        return;
    }

    $row = gsh_tp_curriculr_repo_get( $sj );
    $doc = $row ? json_decode( $row['json'], true ) : null;

    $grenzen  = is_array( $doc ) ? gsh_tp_curriculr_quartal_grenzen_from_doc( $doc ) : '';
    $sj_start = '';
    if ( '' !== $grenzen && isset( $doc['schoolyear']['firstSchoolDay'] ) && gsh_tp_curriculr_is_iso_date( $doc['schoolyear']['firstSchoolDay'] ) ) {
        $sj_start = gsh_tp_curriculr_monday_of_week( $doc['schoolyear']['firstSchoolDay'] );
    }

    $profiles = get_option( 'gsh_tp_profiles', array() );
    $profiles = is_array( $profiles ) ? $profiles : array();
    $changed  = false;

    foreach ( $mappings as $mapping ) {
        if ( empty( $mapping['profileId'] ) ) { continue; }
        $pid      = $mapping['profileId'];
        $group    = isset( $mapping['group'] ) && is_string( $mapping['group'] ) ? $mapping['group'] : null;
        $feed_url = ( null === $group )
            ? gsh_tp_curriculr_feed_url( $sj, $token )
            : gsh_tp_curriculr_feed_url_group( $sj, $token, $group );

        foreach ( $profiles as &$p ) {
            if ( ! isset( $p['id'] ) || $p['id'] !== $pid ) { continue; }
            if ( ( $p['ical_url'] ?? '' ) !== $feed_url ) { $p['ical_url'] = $feed_url; $changed = true; }
            if ( '' !== $grenzen && ( $p['quartal_grenzen'] ?? '' ) !== $grenzen ) { $p['quartal_grenzen'] = $grenzen; $changed = true; }
            if ( '' !== $sj_start && ( $p['schuljahr_start'] ?? '' ) !== $sj_start ) { $p['schuljahr_start'] = $sj_start; $changed = true; }
        }
        unset( $p );

        if ( $row && function_exists( 'gsh_tp_ck' ) && is_array( $doc ) ) {
            $pid_key = sanitize_key( $pid );
            update_option( gsh_tp_ck( 'gsh_tp_ical_', $pid_key ), gsh_tp_curriculr_build_ics( $doc, $group ), false );
            delete_transient( gsh_tp_ck( 'gsh_tp_fresh_', $pid_key ) );
        }
    }

    if ( $changed ) {
        update_option( 'gsh_tp_profiles', $profiles, true );
    }
}

/* ---------- Pure: Backup-Retention (PRIV-MED-001, Art. 5 Abs. 1 lit. e DSGVO) ---------- */
// Dateien ohne Datumsmuster (z. B. .htaccess, index.html) werden nie als
// abgelaufen gemeldet — reines String-/Zeit-Parsing, ohne WordPress lauffähig.

function gsh_tp_curriculr_backup_is_expired( $filename, $now ) {
    if ( ! preg_match( '/-(\d{4}-\d{2}-\d{2})\.(?:json|ics)$/', (string) basename( $filename ), $m ) ) {
        return false;
    }
    $file_time = strtotime( $m[1] . ' 00:00:00 UTC' );
    if ( false === $file_time ) {
        return false;
    }
    $age_days = ( (int) $now - $file_time ) / 86400;
    return $age_days > 30;
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

    // Öffentlichen HTTP-Zugriff auf Backup-Dateien sperren (predictable URLs).
    $htaccess = $backup_dir . '/.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        $wp_filesystem->put_contents(
            $htaccess,
            "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order Allow,Deny\n  Deny from all\n</IfModule>\n",
            FS_CHMOD_FILE
        );
    }
    // Fallback für Server, die .htaccess nicht auswerten (z. B. Nginx) — leeres
    // index.html verhindert Directory-Listing als zweite Verteidigungslinie.
    $index_html = $backup_dir . '/index.html';
    if ( ! file_exists( $index_html ) ) {
        $wp_filesystem->put_contents( $index_html, '', FS_CHMOD_FILE );
    }

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

    // Speicherbegrenzung (Art. 5 Abs. 1 lit. e DSGVO): Backups älter als 30 Tage
    // löschen. Hartes Zeitfenster, kein Setting (Spec PRIV-MED-001).
    $now        = time();
    $candidates = array_merge(
        glob( "$backup_dir/*.json" ) ?: array(),
        glob( "$backup_dir/*.ics" )  ?: array()
    );
    foreach ( $candidates as $path ) {
        if ( gsh_tp_curriculr_backup_is_expired( $path, $now ) ) {
            $wp_filesystem->delete( $path );
        }
    }
}

/* ---------- Settings Backup: Export / Import ---------- */

function gsh_tp_curriculr_gather_settings() {
    $profiles = get_option( 'gsh_tp_profiles', array() );
    $data     = array(
        'gsh_tp_profiles'              => $profiles,
        'gsh_tp_ical_url'              => get_option( 'gsh_tp_ical_url', '' ),
        'gsh_tp_cache_duration'        => get_option( 'gsh_tp_cache_duration', 3600 ),
        'gsh_tp_schuljahr_start'       => get_option( 'gsh_tp_schuljahr_start', '' ),
        'gsh_tp_quartal_grenzen'       => get_option( 'gsh_tp_quartal_grenzen', '' ),
        'gsh_tp_kategorie_mapping'     => get_option( 'gsh_tp_kategorie_mapping', '' ),
        'gsh_tp_categories'            => get_option( 'gsh_tp_categories', array() ),
        'gsh_tp_kiosk_token'           => get_option( 'gsh_tp_kiosk_token', '' ),
        'gsh_tp_draft_kiosk_token'     => get_option( 'gsh_tp_draft_kiosk_token', '' ),
        'gsh_tp_iserv_domain'          => get_option( 'gsh_tp_iserv_domain', '' ),
        'gsh_tp_curriculr_origin'      => get_option( 'gsh_tp_curriculr_origin', '' ),
        'gsh_tp_curriculr_profile_map' => get_option( 'gsh_tp_curriculr_profile_map', array() ),
    );
    if ( is_array( $profiles ) ) {
        foreach ( $profiles as $p ) {
            $pid = sanitize_key( $p['id'] ?? '' );
            if ( $pid ) {
                $ck          = gsh_tp_ck( 'gsh_tp_ical_', $pid );
                $data[ $ck ] = get_option( $ck, '' );
            }
        }
    }
    return $data;
}

function gsh_tp_curriculr_apply_settings( $settings ) {
    if ( ! is_array( $settings ) ) {
        return;
    }
    $allowlist = array(
        'gsh_tp_profiles',
        'gsh_tp_ical_url',
        'gsh_tp_cache_duration',
        'gsh_tp_schuljahr_start',
        'gsh_tp_quartal_grenzen',
        'gsh_tp_kategorie_mapping',
        'gsh_tp_categories',
        'gsh_tp_kiosk_token',
        'gsh_tp_draft_kiosk_token',
        'gsh_tp_iserv_domain',
        'gsh_tp_curriculr_origin',
        'gsh_tp_curriculr_profile_map',
    );
    foreach ( $allowlist as $key ) {
        if ( array_key_exists( $key, $settings ) ) {
            update_option( $key, $settings[ $key ] );
        }
    }
    $profiles = $settings['gsh_tp_profiles'] ?? array();
    if ( is_array( $profiles ) ) {
        foreach ( $profiles as $p ) {
            $pid = sanitize_key( $p['id'] ?? '' );
            if ( ! $pid ) {
                continue;
            }
            $ck = gsh_tp_ck( 'gsh_tp_ical_', $pid );
            if ( array_key_exists( $ck, $settings ) ) {
                update_option( $ck, $settings[ $ck ] );
            }
        }
    }
}

function gsh_tp_curriculr_handle_export() {
    check_admin_referer( 'gsh_tp_curriculr_export_nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        status_header( 403 );
        exit;
    }
    $payload = wp_json_encode(
        array(
            'version'     => GSH_TP_VERSION,
            'exported_at' => gmdate( 'c' ),
            'settings'    => gsh_tp_curriculr_gather_settings(),
        ),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    if ( false === $payload ) {
        status_header( 500 );
        exit;
    }
    $date = gmdate( 'Y-m-d' );
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="curriculr-settings-' . $date . '.json"' );
    header( 'Content-Length: ' . strlen( $payload ) );
    header( 'Cache-Control: no-cache, no-store' );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $payload;
    exit;
}

function gsh_tp_curriculr_handle_import() {
    check_admin_referer( 'gsh_tp_import_settings' );
    if ( ! current_user_can( 'manage_options' ) ) {
        status_header( 403 );
        exit;
    }
    $page_url = admin_url( 'options-general.php?page=gsh-terminplan-backup' );

    if ( empty( $_FILES['settings_file']['tmp_name'] ) ) {
        wp_safe_redirect( add_query_arg( 'import_error', '1', $page_url ) );
        exit;
    }
    if ( (int) ( $_FILES['settings_file']['size'] ?? 0 ) > 512 * 1024 ) {
        wp_safe_redirect( add_query_arg( 'import_error', '2', $page_url ) );
        exit;
    }
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw  = file_get_contents( $_FILES['settings_file']['tmp_name'] );
    $data = json_decode( $raw, true );
    if ( JSON_ERROR_NONE !== json_last_error()
        || ! is_array( $data )
        || ! isset( $data['settings'] )
        || ! is_array( $data['settings'] )
        || ! isset( $data['version'] )
    ) {
        wp_safe_redirect( add_query_arg( 'import_error', '3', $page_url ) );
        exit;
    }
    gsh_tp_curriculr_apply_settings( $data['settings'] );
    wp_safe_redirect( add_query_arg( 'imported', '1', $page_url ) );
    exit;
}

/**
 * POST-Handler: Planungsdokument (JSON) manuell hochladen — SSO-Alternative.
 *
 * Inline-Handler (kein admin-post.php/exit) — läuft mitten in gsh_tp_settings_page()
 * und gibt ein <div class="notice"> zurück, analog zu gsh_tp_handle_new_schoolyear().
 * Erzwingt Überschreiben (baseVersion = aktuelle Version), da der Admin bereits über
 * die Bestätigungs-Checkbox zugestimmt hat — keine 409-Konflikt-UI im manuellen Pfad.
 *
 * @since 4.28.0
 * @return void
 */
function gsh_tp_curriculr_handle_doc_import() {
    $sy_key = sanitize_key( wp_unslash( $_POST['gsh_tp_di_sy'] ?? '' ) );
    $pid    = sanitize_key( $sy_key );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'gsh_tp_di_n_' . $pid ] ?? '' ) ), 'gsh_tp_doc_import_' . $pid ) ) {
        echo '<div class="notice notice-error"><p>Sicherheitsprüfung fehlgeschlagen.</p></div>';
        return;
    }

    $known = false;
    foreach ( gsh_tp_get_schoolyears() as $sy ) {
        if ( $sy['key'] === $sy_key ) { $known = true; break; }
    }
    if ( ! $known ) {
        echo '<div class="notice notice-error"><p>Unbekanntes Schuljahr.</p></div>';
        return;
    }

    $current_row = gsh_tp_curriculr_repo_get( $sy_key );
    if ( $current_row && empty( $_POST['gsh_tp_di_confirm'] ) ) {
        echo '<div class="notice notice-error"><p>Bitte bestätige, dass der aktuelle Stand überschrieben werden soll.</p></div>';
        return;
    }

    $upload_error = $_FILES['gsh_tp_di_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ( empty( $_FILES['gsh_tp_di_file']['tmp_name'] ) || UPLOAD_ERR_OK !== $upload_error ) {
        echo '<div class="notice notice-error"><p>Keine Datei ausgewählt.</p></div>';
        return;
    }
    if ( (int) ( $_FILES['gsh_tp_di_file']['size'] ?? 0 ) > 2 * 1024 * 1024 ) {
        echo '<div class="notice notice-error"><p>Datei zu groß (max. 2 MB).</p></div>';
        return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw    = file_get_contents( $_FILES['gsh_tp_di_file']['tmp_name'] );
    $parsed = gsh_tp_curriculr_decode_doc_upload( $raw );
    if ( ! $parsed['valid'] ) {
        echo '<div class="notice notice-error"><p>Ungültiges Dokumentformat. Bitte eine Curriculr-JSON-Backup-Datei wählen.</p></div>';
        return;
    }

    $stage        = gsh_tp_curriculr_normalize_stage( wp_unslash( $_POST['gsh_tp_di_stage'] ?? 'entwurf' ) );
    $base_version = $current_row ? (int) $current_row['version'] : 0;
    $user         = wp_get_current_user();
    $author       = array( 'sub' => 'manual:' . get_current_user_id(), 'name' => (string) $user->display_name );

    $res = gsh_tp_curriculr_repo_put( $sy_key, $parsed['doc'], $base_version, $stage, $author );
    if ( $res['status'] !== 'ok' ) {
        echo '<div class="notice notice-error"><p>Hochladen fehlgeschlagen — der Stand hat sich zwischenzeitlich geändert. Bitte erneut versuchen.</p></div>';
        return;
    }
    gsh_tp_curriculr_after_put( $sy_key, $res['feed_token'] );

    echo '<div class="notice notice-success"><p>Planungsdokument für <strong>' . esc_html( $sy_key ) . '</strong> hochgeladen (Version ' . (int) $res['version'] . ').</p></div>';
}

/**
 * admin-post.php-Handler: Aktuellen Planungsdokument-Stand als JSON herunterladen.
 *
 * Braucht header()+exit für den Datei-Download — läuft deshalb über die separate
 * admin-post.php-Request statt inline in gsh_tp_settings_page() (dort ist zum
 * Zeitpunkt des Seiten-Callbacks bereits HTML gesendet, header() würde fehlschlagen).
 *
 * @since 4.28.0
 * @return void
 */
function gsh_tp_curriculr_handle_doc_export() {
    $sj = sanitize_key( wp_unslash( $_GET['sj'] ?? '' ) );
    check_admin_referer( 'gsh_tp_curriculr_doc_export_' . $sj );
    if ( ! current_user_can( 'manage_options' ) ) {
        status_header( 403 );
        exit;
    }
    $row = gsh_tp_curriculr_repo_get( $sj );
    if ( ! $row ) {
        status_header( 404 );
        exit;
    }
    $payload = $row['json'];
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $sj ) . '-' . gmdate( 'Y-m-d' ) . '.json"' );
    header( 'Content-Length: ' . strlen( $payload ) );
    header( 'Cache-Control: no-cache, no-store' );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rohe JSON-Ausgabe, bewusst kein wp_die.
    echo $payload;
    exit;
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
    add_action( 'admin_post_gsh_tp_curriculr_export', 'gsh_tp_curriculr_handle_export' );
    add_action( 'admin_post_gsh_tp_import_settings',  'gsh_tp_curriculr_handle_import' );
    add_action( 'admin_post_gsh_tp_curriculr_doc_export', 'gsh_tp_curriculr_handle_doc_export' );
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
