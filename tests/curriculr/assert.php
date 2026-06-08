<?php
// Dependency-free Test-Helfer (kein Composer/PHPUnit nötig).
$GLOBALS['gsh_test_fail'] = 0;

function gsh_assert_eq( $actual, $expected, $msg ) {
    if ( $actual !== $expected ) {
        $GLOBALS['gsh_test_fail']++;
        fwrite( STDERR, "FAIL: $msg\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
    } else {
        echo "PASS: $msg\n";
    }
}

function gsh_assert_true( $cond, $msg ) {
    gsh_assert_eq( $cond === true, true, $msg );
}

function gsh_assert_contains( $haystack, $needle, $msg ) {
    gsh_assert_eq( strpos( $haystack, $needle ) !== false, true, $msg . " (needle: $needle)" );
}

function gsh_test_done() {
    if ( $GLOBALS['gsh_test_fail'] > 0 ) {
        fwrite( STDERR, $GLOBALS['gsh_test_fail'] . " failure(s)\n" );
        exit( 1 );
    }
    echo "ALL PASS\n";
}
