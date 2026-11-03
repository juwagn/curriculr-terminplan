<?php
/**
 * Curricu:lr Terminplan – Jahreskalender (Desktop und Druck).
 *
 * Wandplaner: zwölf Monate als Spalten, die Tage 1–31 als Zeilen. Jede Zelle zeigt
 * Wochentag, Datum und alle Termine des Tages; die Schulwoche steht am Montag.
 * Reine Darstellungsfunktion: Die Daten kommen fertig aus gsh_tp_yearview()
 * (Termine je Tag, Ferien, Kategorien), hier wird nichts gelesen oder gespeichert.
 *
 * @since 4.45.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Baut den Jahreskalender als HTML.
 *
 * @param array<int, array{0:int,1:int}>                 $months Liste von [Jahr, Monat].
 * @param array<string, array{hol:bool, slugs:string[]}> $info   Tag → Ferien-Kennzeichen und Kategorie-Slugs.
 * @param array<string, array<int, array>>               $days   Tag → Termine; je Termin [Titel, Zeit, Slug].
 * @param array<string, string>                          $col    Kategorie-Slug → Farbe.
 * @param string                                         $td     Heutiges Datum (Y-m-d).
 * @param string                                         $sjs    Erster Montag des Schuljahres (Y-m-d) für die Schulwochen.
 * @return string HTML.
 */
function gsh_tp_year_calendar_html( array $months, array $info, array $days, array $col, string $td, string $sjs ): string {
	$month_names = array( 1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' );
	$weekdays    = array( 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So' );

	$o = '<div class="gtp-cal" role="group" aria-label="Jahreskalender">';
	foreach ( $months as $ym ) {
		list( $y, $m ) = $ym;
		$dim           = (int) gmdate( 't', gmmktime( 0, 0, 0, $m, 1, $y ) );

		$o .= '<div class="gtp-cal-m" data-m="' . esc_attr( sprintf( '%04d-%02d', $y, $m ) ) . '">';
		$o .= '<div class="gtp-cal-t" title="' . esc_attr( $month_names[ $m ] . ' ' . $y ) . '">' . esc_html( $month_names[ $m ] ) . '</div>';

		for ( $d = 1; $d <= 31; $d++ ) {
			if ( $d > $dim ) {
				$o .= '<span class="gtp-cal-x"></span>';
				continue;
			}
			$ts  = gmmktime( 0, 0, 0, $m, $d, $y );
			$i   = (int) gmdate( 'N', $ts ) - 1; // 0 = Montag.
			$ds  = sprintf( '%04d-%02d-%02d', $y, $m, $d );
			$inf = $info[ $ds ] ?? null;
			$cls = 'gtp-cal-d' . ( $i >= 5 ? ' gtp-cal-we' : '' ) . ( $ds === $td ? ' gtp-cal-td' : '' )
				. ( $inf && $inf['hol'] ? ' gtp-cal-f' : '' ) . ( $inf && ! $inf['hol'] ? ' gtp-cal-ev' : '' );

			$sw = '';
			if ( 0 === $i && '' !== $sjs ) {
				$n = gsh_tp_schulwoche( $ds, $sjs );
				if ( $n >= 0 ) {
					$sw = '<span class="gtp-cal-sw" title="Schulwoche ' . $n . '">' . sprintf( '%02d', $n ) . '</span>';
				}
			}
			$head = '<span class="gtp-cal-wd">' . $weekdays[ $i ] . '</span><span class="gtp-cal-n">' . $d . '</span>';

			if ( ! $inf ) {
				$o .= '<span class="' . esc_attr( $cls ) . '" data-d="' . esc_attr( $ds ) . '">' . $head . $sw . '</span>';
				continue;
			}

			$list = $days[ $ds ] ?? array();
			$cnt  = count( $list );
			$evs  = '';
			foreach ( $list as $e ) {
				$slug = (string) ( $e[2] ?? '' );
				$evs .= '<span class="gtp-cal-e" data-c="' . esc_attr( $slug ) . '"'
					. ( ! empty( $col[ $slug ] ) ? ' style="--cat:' . esc_attr( $col[ $slug ] ) . '"' : '' ) . '>'
					. esc_html( (string) $e[0] ) . '</span>';
			}
			$o .= '<button type="button" class="' . esc_attr( $cls ) . '" data-d="' . esc_attr( $ds ) . '"'
				. ' aria-label="' . esc_attr( $weekdays[ $i ] . ', ' . $d . '. ' . $month_names[ $m ] . ' ' . $y . ', ' . $cnt . ( 1 === $cnt ? ' Termin' : ' Termine' ) ) . '"'
				. ' onclick="gtpYearSelect(this)">' . $head . '<span class="gtp-cal-es">' . $evs . '</span>' . $sw . '</button>';
		}
		$o .= '</div>';
	}
	$o .= '</div>';
	return $o;
}
