<?php
/**
 * Template Name: Terminplan Entwurf-Vorschau
 *
 * Passwortloser Entwurf-Vorschau-Zugang für das Schulleitungsteam per Token-URL.
 * Token wird via gsh_tp_check_draft_kiosk_access() validiert (timing-sicher,
 * mit IP-basiertem Rate-Limiting).
 *
 * Einrichtung:
 *   1. Im WordPress-Backend eine neue Seite anlegen.
 *   2. Als Seitenvorlage „Terminplan Entwurf-Vorschau" wählen.
 *   3. Im Plugin-Admin (System-Tab) einen Entwurf-Token generieren.
 *   4. Die angezeigte URL an das Schulleitungsteam weitergeben.
 *
 * @since 4.1.0
 */

// Zugriff ohne WordPress verweigern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Token aus URL prüfen — vor jedem weiteren Output
$token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
if ( ! function_exists( 'gsh_tp_check_draft_kiosk_access' ) || ! gsh_tp_check_draft_kiosk_access( $token ) ) {
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

// Kiosk-Kontext setzen BEVOR do_shortcode läuft, damit der Shortcode keinen
// zweiten Entwurfs-Banner rendert (gsh_tp_draft_kiosk_context() prüft dieses Flag).
if ( function_exists( 'gsh_tp_draft_kiosk_context' ) ) {
	gsh_tp_draft_kiosk_context( true );
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> &ndash; Terminplan Entwurf</title>
<?php wp_head(); ?>
</head>
<body class="gtp-entwurf-kiosk <?php echo is_admin_bar_showing() ? 'admin-bar' : ''; ?>">

<main style="max-width:1200px;margin:0 auto;padding:1rem">
	<div class="gtp-draft-banner" style="margin-bottom:1.5rem;border-radius:6px;padding:0.75rem 1.25rem;display:flex;align-items:center;gap:0.5rem">
		<?php echo gsh_tp_icon( 'alert-triangle' ); ?>
		<span><strong>Entwurf</strong> &ndash; dieser Terminplan ist noch nicht beschlossen.
		Bitte &Auml;nderungsw&uuml;nsche direkt an die Schulleitung melden.</span>
	</div>

	<?php echo do_shortcode( '[gsh_terminplan schuljahr="entwurf"]' ); ?>
</main>

<?php wp_footer(); ?>
</body>
</html>
