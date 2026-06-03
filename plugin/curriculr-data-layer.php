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
