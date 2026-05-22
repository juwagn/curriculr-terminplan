<?php
/**
 * Template Name: Terminplan Entwurf-Vorschau
 *
 * Passwortloser Entwurf-Vorschau-Zugang für das Schulleitungsteam per Token-URL.
 * Token wird via gsh_tp_check_draft_kiosk_access() validiert (timing-sicher,
 * mit IP-basiertem Rate-Limiting).
 *
 * Einrichtung:
 *   1. Diese Datei in den aktiven Theme-Ordner kopieren.
 *   2. Im WordPress-Backend eine neue Seite anlegen.
 *   3. Als Seitenvorlage „Terminplan Entwurf-Vorschau" wählen.
 *   4. Im Plugin-Admin (Kiosk & System) einen Entwurf-Token generieren.
 *   5. Die angezeigte URL an das Schulleitungsteam weitergeben.
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
get_header();
?>

<main style="max-width:1200px;margin:0 auto;padding:1rem">
	<div class="gtp-draft-banner" style="margin-bottom:1.5rem;border-radius:6px;padding:0.75rem 1.25rem;display:flex;align-items:center;gap:0.5rem">
		<?php echo gsh_tp_icon( 'alert-triangle' ); ?>
		<span><strong>Entwurf</strong> &ndash; dieser Terminplan ist noch nicht beschlossen.
		Bitte &Auml;nderungsw&uuml;nsche direkt an die Schulleitung melden.</span>
	</div>

	<?php echo do_shortcode( '[gsh_terminplan schuljahr="entwurf"]' ); ?>
</main>

<?php
get_footer();
