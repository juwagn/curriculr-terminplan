<?php
/**
 * Tests for backup retention (PRIV-MED-001): pure date-expiry helper +
 * the backup cron's prune/index.html behavior with a stubbed filesystem.
 * Dependency-free, runs with plain `php`.
 */
define( 'GSH_TP_CURRICULR_TEST', true );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'FS_CHMOD_FILE', 0644 );

require __DIR__ . '/assert.php';
require __DIR__ . '/../../plugin/curriculr-data-layer.php';

/* ---------- Pure Helfer: gsh_tp_curriculr_backup_is_expired ---------- */
$now = strtotime( '2026-07-07 12:00:00 UTC' );

gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'sj_2026_27-2026-01-01.json', $now ), true, 'Datei aelter als 30 Tage -> expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'sj_2026_27-2026-07-07.json', $now ), false, 'heutige Datei -> nicht expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'sj_2026_27-2026-06-20.ics', $now ), false, '17 Tage alt -> nicht expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'sj_2026_27-2026-05-01.ics', $now ), true, 'ueber 30 Tage alt (.ics) -> expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( '.htaccess', $now ), false, '.htaccess ohne Datumsmuster -> nie expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'index.html', $now ), false, 'index.html ohne Datumsmuster -> nie expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( 'garbage-file.json', $now ), false, 'Datei ohne Datumsmuster -> nie expired' );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( '/var/www/uploads/curriculr-backups/sj-2026-01-01.json', $now ), true, 'voller Pfad wird per basename() geparst' );

// Grenzfall: exakt 30 Tage alt ist NICHT expired (nur > 30, spec sagt "aelter als 30 Tage").
// Referenzpunkt bewusst Mitternacht UTC, damit die Tagesdifferenz eindeutig ist.
$now_midnight = strtotime( '2026-07-07 00:00:00 UTC' );
$exactly_30   = gmdate( 'Y-m-d', $now_midnight - 30 * 86400 );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( "sj-{$exactly_30}.json", $now_midnight ), false, 'genau 30 Tage alt -> noch nicht expired' );
$just_over_30 = gmdate( 'Y-m-d', $now_midnight - 31 * 86400 );
gsh_assert_eq( gsh_tp_curriculr_backup_is_expired( "sj-{$just_over_30}.json", $now_midnight ), true, '31 Tage alt -> expired' );

/* ---------- backup_cron: index.html + Retention-Prune (gestubbtes Filesystem,
   das echt auf ein Temp-Verzeichnis schreibt — glob() liest das reale FS und
   laesst sich sonst nicht sinnvoll gegen den Cron testen) ---------- */
class Gsh_Fake_Backup_Filesystem {
    public function put_contents( $path, $contents, $mode = null ) {
        return file_put_contents( $path, $contents ) !== false;
    }
    public function delete( $path ) {
        return @unlink( $path );
    }
}

$GLOBALS['wpdb'] = new class {
    public $prefix = 'wp_';
    public function get_results( $sql, $out = null ) {
        return array(
            array( 'schoolyear' => 'sj_2026_27', 'json' => json_encode( array( 'events' => array() ) ), 'feed_token' => 'tok' ),
        );
    }
};

$upload_basedir = sys_get_temp_dir() . '/gsh_tp_curriculr_backup_test_' . uniqid();
$backup_root    = $upload_basedir . '/curriculr-backups';
mkdir( $backup_root, 0777, true );

// Alte Backup-Dateien simulieren (aelter als 30 Tage) direkt im Dateisystem,
// damit glob() sie findet (glob() laesst sich nicht sinnvoll stubben).
$old_stamp = gmdate( 'Y-m-d', time() - 45 * 86400 );
file_put_contents( "$backup_root/sj_2026_27-{$old_stamp}.json", '{}' );
file_put_contents( "$backup_root/sj_2026_27-{$old_stamp}.ics", 'BEGIN:VCALENDAR' );
$fresh_stamp = gmdate( 'Y-m-d' );
file_put_contents( "$backup_root/sj_old_year-{$old_stamp}.json", '{}' );

function wp_upload_dir() {
    global $upload_basedir;
    return array( 'basedir' => $upload_basedir );
}
function wp_mkdir_p( $dir ) { if ( ! is_dir( $dir ) ) { mkdir( $dir, 0777, true ); } }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $k ) ); }
function WP_Filesystem() { return true; }
$GLOBALS['wp_filesystem'] = new Gsh_Fake_Backup_Filesystem();

gsh_tp_curriculr_backup_cron();

// scandir() statt glob(): glob("*") ignoriert Punktdateien wie .htaccess.
$names = array_diff( scandir( $backup_root ), array( '.', '..' ) );

gsh_assert_true( in_array( 'index.html', $names, true ), 'backup_cron creates index.html fallback' );
gsh_assert_true( in_array( '.htaccess', $names, true ), 'backup_cron creates .htaccess' );
gsh_assert_true( ! in_array( "sj_2026_27-{$old_stamp}.json", $names, true ), 'expired json backup pruned' );
gsh_assert_true( ! in_array( "sj_2026_27-{$old_stamp}.ics", $names, true ), 'expired ics backup pruned' );
gsh_assert_true( ! in_array( "sj_old_year-{$old_stamp}.json", $names, true ), 'expired backup of a since-removed schoolyear is also pruned' );
gsh_assert_true( in_array( "sj_2026_27-{$fresh_stamp}.json", $names, true ), 'freshly written backup for current run is kept' );

// Aufraeumen.
foreach ( array_diff( scandir( $backup_root ), array( '.', '..' ) ) as $f ) {
    @unlink( "$backup_root/$f" );
}
@rmdir( $backup_root );
@rmdir( $upload_basedir );

gsh_test_done();
