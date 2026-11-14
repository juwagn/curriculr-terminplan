<?php
// Exercise the real uninstall and settings functions with isolated storage and files.
require __DIR__ . '/assert.php';
define( 'GSH_TP_CACHE_KEY', 'gsh_tp_ical_data' );
define( 'GSH_TP_BACKUP_KEY', 'gsh_tp_ical_backup' );
define( 'GSH_TP_FRESH_KEY', 'gsh_tp_ical_freshness' );
$source = file_get_contents( __DIR__ . '/../../plugin/gsh-terminplan.php' );
$tokens = token_get_all( $source );
$wanted = array( 'gsh_tp_uninstall', 'curriculr_tp_remove_backup_directory', 'curriculr_tp_delete_uninstall_data', 'curriculr_tp_handle_save_uninstall', 'curriculr_tp_render_uninstall_settings' );
for ( $i = 0; $i < count( $tokens ); $i++ ) {
    if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
    $j = $i + 1;
    while ( is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { $j++; }
    if ( ! is_array( $tokens[$j] ) || ! in_array( $tokens[$j][1], $wanted, true ) ) { continue; }
    $code = ''; $depth = 0; $started = false;
    for ( ; $i < count( $tokens ); $i++ ) {
        $t = $tokens[$i]; $code .= is_array( $t ) ? $t[1] : $t;
        if ( '{' === $t || ( is_array( $t ) && in_array( $t[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) { $depth++; $started = true; }
        if ( '}' === $t && --$depth === 0 && $started ) { break; }
    }
    eval( $code );
}
function get_option( $k, $d = false ) { return $GLOBALS['options'][$k] ?? $d; }
function update_option( $k, $v, $autoload = null ) { if ( ! empty( $GLOBALS['fail_update'] ) ) { return false; } $GLOBALS['options'][$k] = $v; return true; }
function delete_option( $k ) { if ( ( $GLOBALS['fail_option'] ?? '' ) === $k ) { return false; } unset( $GLOBALS['options'][$k] ); return true; }
function delete_transient( $k ) { unset( $GLOBALS['transients'][$k], $GLOBALS['options']['_transient_' . $k], $GLOBALS['options']['_transient_timeout_' . $k] ); return true; }
function wp_clear_scheduled_hook( $k ) { $GLOBALS['cleared'][] = $k; }
function wp_upload_dir() { return array( 'basedir' => $GLOBALS['upload'], 'error' => false ); }
function sanitize_key( $s ) { return $s; }
function gsh_tp_get_profiles() { return array(); }
function gsh_tp_ck( $prefix, $id ) { return $prefix . $id . '_v3'; }
function current_user_can( $cap ) { return $GLOBALS['allowed']; }
function wp_verify_nonce( $nonce, $action ) { return 'good' === $nonce; }
function wp_unslash( $s ) { return $s; }
function sanitize_text_field( $s ) { return (string) $s; }
function wp_die( $message ) { throw new RuntimeException( $message ); }
function wp_cache_delete( $key, $group ) { $GLOBALS['cache_deleted'][] = array( $key, $group ); }
$GLOBALS['wpdb'] = new class {
    public $prefix = 'wp_'; public $options = 'wp_options'; public $tables = array( 'wp_curriculr_docs', 'wp_curriculr_doc_revisions', 'wp_other' ); public $last_sql = ''; public $last_error = '';
    function esc_like( $s ) { return addcslashes( $s, '_%\\' ); }
    function prepare( $sql, ...$values ) { foreach ( $values as $v ) { $sql = preg_replace( '/%s/', "'" . $v . "'", $sql, 1 ); } return $sql; }
    function get_col( $sql ) {
        $this->last_sql = $sql;
        return array_values( array_filter( array_keys( $GLOBALS['options'] ), function( $k ) {
            return 0 === strpos( $k, 'gsh_tp_' ) || 0 === strpos( $k, '_transient_gsh_tp_' ) || 0 === strpos( $k, '_transient_timeout_gsh_tp_' );
        } ) );
    }
    function query( $sql ) {
        if ( ! empty( $GLOBALS['fail_table'] ) && false !== strpos( $sql, $GLOBALS['fail_table'] ) ) { return false; }
        foreach ( array( 'wp_curriculr_docs', 'wp_curriculr_doc_revisions' ) as $table ) {
            if ( false !== strpos( $sql, $table ) ) { $this->tables = array_values( array_diff( $this->tables, array( $table ) ) ); }
        }
        return 1;
    }
};
$root = sys_get_temp_dir() . '/curriculr-uninstall-' . uniqid();
$GLOBALS['upload'] = $root . '/uploads';
$backup = $GLOBALS['upload'] . '/curriculr-backups';
mkdir( $backup, 0700, true );
file_put_contents( $backup . '/plan.json', '{"school":"Muster-Gesamtschule"}' );
file_put_contents( $backup . '/.htaccess', 'Require all denied' );
file_put_contents( $root . '/outside.txt', 'keep' );
symlink( $root . '/outside.txt', $backup . '/link.txt' );
$fixture = array(
    'gsh_tp_categories' => array( 'demo' ), 'gsh_tp_schoolyears' => array( 'demo' ),
    'gsh_tp_profiles' => array(), 'gsh_tp_feedback_log' => array( 'demo' ),
    'gsh_tp_feedback_email' => 'demo@example.test', 'gsh_tp_school_name' => 'Muster-Gesamtschule',
    'gsh_tp_mail_fail_count' => 1, 'gsh_tp_noticed_version' => 'old', 'gsh_tp_source_migrated' => 1,
    'gsh_tp_ical_orphan_v1' => 'old cache', '_transient_gsh_tp_cur_oauth_demo' => 'demo',
    '_transient_timeout_gsh_tp_cur_oauth_demo' => 123, '_transient_gsh_tp_snap_old' => array( 'demo' ),
    'other_plugin' => 'keep', 'gshXtp_unrelated' => 'keep', '_transient_other_plugin' => 'keep',
);
$GLOBALS['options'] = $fixture;
$GLOBALS['cleared'] = array();
gsh_tp_uninstall();
gsh_assert_eq( $GLOBALS['options'], $fixture, 'default-off uninstall preserves every option and transient' );
gsh_assert_eq( $GLOBALS['cleared'], array( 'gsh_tp_cron_refresh', 'gsh_tp_curriculr_daily_backup' ), 'default-off uninstall clears scheduled hooks' );
gsh_assert_true( file_exists( $backup . '/plan.json' ), 'default-off keeps backups' );
gsh_assert_eq( count( $GLOBALS['wpdb']->tables ), 3, 'default-off keeps tables' );
$GLOBALS['options'] = $fixture + array( 'gsh_tp_delete_on_uninstall' => '1' );
$GLOBALS['fail_table'] = 'wp_curriculr_doc_revisions';
try { gsh_tp_uninstall(); gsh_assert_true( false, 'table failure stops uninstall' ); } catch ( RuntimeException $e ) { gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall' ), '1', 'table failure keeps opt-in for retry' ); }
$GLOBALS['fail_table'] = '';
$GLOBALS['fail_option'] = 'gsh_tp_schoolyears';
try { gsh_tp_uninstall(); gsh_assert_true( false, 'option failure stops uninstall' ); } catch ( RuntimeException $e ) { gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall' ), '1', 'option failure keeps opt-in for retry' ); }
$GLOBALS['fail_option'] = '';
$GLOBALS['options'] = $fixture + array( 'gsh_tp_delete_on_uninstall' => '1' );
$GLOBALS['transients'] = array( 'gsh_tp_events_snapshot' => 'sensitive', 'gsh_tp_changes' => 'sensitive', 'gsh_tp_ical_data' => 'sensitive', 'other_plugin' => 'keep' );
gsh_tp_uninstall();
gsh_assert_eq( $GLOBALS['options'], array( 'other_plugin' => 'keep', 'gshXtp_unrelated' => 'keep', '_transient_other_plugin' => 'keep' ), 'opt-in removes plugin data including orphaned keys, preserving other plugins' );
gsh_assert_eq( $GLOBALS['wpdb']->tables, array( 'wp_other' ), 'opt-in drops only the two plugin tables' );
gsh_assert_true( ! file_exists( $backup ), 'opt-in removes backup directory and hidden files' );
gsh_assert_eq( file_get_contents( $root . '/outside.txt' ), 'keep', 'backup symlink never deletes its target' );
gsh_assert_eq( $GLOBALS['transients'], array( 'other_plugin' => 'keep' ), 'known global cache-only transients are removed without flushing unrelated cache' );
gsh_assert_true( false !== strpos( $GLOBALS['wpdb']->last_sql, 'gsh\\_tp\\_' ), 'option prefix escapes SQL LIKE wildcards' );
mkdir( $root . '/outside-dir' );
file_put_contents( $root . '/outside-dir/keep.txt', 'keep' );
symlink( $root . '/outside-dir', $backup );
gsh_assert_true( curriculr_tp_remove_backup_directory(), 'symlinked backup directory is removed safely' );
gsh_assert_eq( file_get_contents( $root . '/outside-dir/keep.txt' ), 'keep', 'symlinked backup directory target is untouched' );
unlink( $root . '/outside-dir/keep.txt' ); rmdir( $root . '/outside-dir' );
if ( function_exists( 'curriculr_tp_handle_save_uninstall' ) ) {
    $GLOBALS['allowed'] = false; $_POST = array( '_wpnonce' => 'good', 'gsh_tp_delete_on_uninstall' => '1' ); $profiles = array();
    ob_start(); curriculr_tp_handle_save_uninstall( $profiles ); ob_end_clean();
    gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall', false ), false, 'non-admin cannot enable destructive setting' );
    $GLOBALS['allowed'] = true; $_POST['_wpnonce'] = 'bad';
    ob_start(); curriculr_tp_handle_save_uninstall( $profiles ); ob_end_clean();
    gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall', false ), false, 'bad nonce cannot enable destructive setting' );
    $_POST['_wpnonce'] = 'good';
    ob_start(); curriculr_tp_handle_save_uninstall( $profiles ); ob_end_clean();
    gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall' ), '1', 'admin with nonce can opt in' );
    unset( $_POST['gsh_tp_delete_on_uninstall'] );
    $GLOBALS['fail_update'] = true;
    ob_start(); curriculr_tp_handle_save_uninstall( $profiles ); $message = ob_get_clean();
    gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall' ), '1', 'failed save leaves existing opt-in unchanged' );
    gsh_assert_true( false !== strpos( $message, 'notice-error' ) && false === strpos( $message, 'notice-success' ), 'failed save warns instead of reporting success' );
    $GLOBALS['fail_update'] = false;
    ob_start(); curriculr_tp_handle_save_uninstall( $profiles ); ob_end_clean();
    gsh_assert_eq( get_option( 'gsh_tp_delete_on_uninstall' ), '0', 'unchecked form disables deletion again' );
} else { gsh_assert_true( false, 'protected opt-in handler exists' ); }
// Clean only this test's own temporary directory, even before the fix exists.
if ( is_dir( $backup ) ) { foreach ( scandir( $backup ) as $name ) { if ( '.' !== $name && '..' !== $name ) { unlink( $backup . '/' . $name ); } } rmdir( $backup ); }
unlink( $root . '/outside.txt' ); rmdir( $GLOBALS['upload'] ); rmdir( $root );
gsh_test_done();
