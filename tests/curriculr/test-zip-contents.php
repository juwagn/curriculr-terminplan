<?php
/**
 * Jede per require_once geladene Plugin-Datei muss existieren, damit der ZIP-Bau
 * (zip *.php) nie eine Datei vergisst und das Plugin beim Laden nicht abstürzt.
 */
require __DIR__ . '/assert.php';

$main = (string) file_get_contents( __DIR__ . '/../../plugin/gsh-terminplan.php' );
preg_match_all( "/require_once plugin_dir_path\\( __FILE__ \\) \\. '([^']+)'/", $main, $m );
gsh_assert_true( count( $m[1] ) >= 4, 'Hauptdatei lädt mindestens vier Teil-Dateien' );
foreach ( $m[1] as $file ) {
    gsh_assert_true( is_file( __DIR__ . '/../../plugin/' . $file ), 'geladene Datei existiert: ' . $file );
}
gsh_assert_true( in_array( 'curriculr-year-calendar.php', $m[1], true ), 'Jahreskalender-Datei wird geladen' );

gsh_test_done();
