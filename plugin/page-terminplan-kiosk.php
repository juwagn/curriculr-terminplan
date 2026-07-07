<?php
/**
 * Template Name: Terminplan Kiosk
 *
 * Token-gesicherter Kiosk-Zugang für IServ-Einbettung (iframe-Kachel).
 * Zeigt den beschlossenen Terminplan ohne WP-Theme-Chrome.
 * Token wird via gsh_tp_check_kiosk_access() validiert (timing-sicher,
 * mit IP-basiertem Rate-Limiting).
 *
 * Einrichtung:
 *   1. Im WordPress-Backend eine neue Seite anlegen.
 *   2. Als Seitenvorlage „Terminplan Kiosk" wählen.
 *   3. Im Plugin-Admin (Kiosk-Tab) Kiosk-Token generieren + IServ-Domain eintragen.
 *   4. Die angezeigte Kiosk-URL in IServ als iframe-Kachel hinterlegen.
 *
 * @since 4.20.0
 */

// Zugriff ohne WordPress verweigern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Token aus URL prüfen — vor jedem weiteren Output
$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
if ( ! function_exists( 'gsh_tp_check_kiosk_access' ) || ! gsh_tp_check_kiosk_access( $token ) ) {
	status_header( 403 );
	nocache_headers();
	exit;
}

nocache_headers();

// CSP frame-ancestors: erlaubt IServ-Domain + 'self' als Einbetter.
// X-Frame-Options SAMEORIGIN als Legacy-Fallback für Browser ohne CSP-Support.
// Kein ALLOW-FROM — deprecated, kein Browser-Support mehr.
$iserv_domain = get_option( 'gsh_tp_iserv_domain', '' );
if ( $iserv_domain ) {
	header( "Content-Security-Policy: frame-ancestors 'self' " . esc_url_raw( $iserv_domain ) );
}
header( 'X-Frame-Options: SAMEORIGIN' );
// Token steht in der URL — Referrer darf nie an Dritt-Ressourcen leaken.
header( 'Referrer-Policy: no-referrer' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> &ndash; Terminplan</title>
<?php wp_head(); ?>
</head>
<body class="gtp-kiosk <?php echo is_admin_bar_showing() ? 'admin-bar' : ''; ?>">

<main class="gtp-kiosk-main">
	<?php echo do_shortcode( '[gsh_terminplan]' ); ?>
</main>

<?php wp_footer(); ?>
</body>
</html>
