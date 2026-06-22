<?php
/**
 * Vista guida completa plugin.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

$sections   = \CVP\Help::get_sections();
$shortcodes = \CVP\Help::get_shortcodes();
$results_url = \CVP\Settings::get_results_page_url();
?>
<div class="wrap cvp-admin-wrap">
	<h1><?php esc_html_e( 'Guida Casa Vacanza Prenotazioni', 'casa-vacanza-prenotazioni' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Sviluppato da Evolo Digital Studio', 'casa-vacanza-prenotazioni' ); ?></p>

	<?php foreach ( $sections as $section ) : ?>
		<div class="cvp-admin-panel cvp-help-section">
			<h2><?php echo esc_html( $section['title'] ); ?></h2>
			<p><?php echo esc_html( $section['content'] ); ?></p>
		</div>
	<?php endforeach; ?>

	<div class="cvp-admin-panel">
		<h2><?php esc_html_e( 'Collegamenti rapidi', 'casa-vacanza-prenotazioni' ); ?></h2>
		<ul class="cvp-quick-links">
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-dashboard' ) ); ?>"><?php esc_html_e( 'Dashboard', 'casa-vacanza-prenotazioni' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-bookings' ) ); ?>"><?php esc_html_e( 'Prenotazioni', 'casa-vacanza-prenotazioni' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cv_appartamento' ) ); ?>"><?php esc_html_e( 'Appartamenti', 'casa-vacanza-prenotazioni' ); ?></a></li>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-settings' ) ); ?>"><?php esc_html_e( 'Impostazioni', 'casa-vacanza-prenotazioni' ); ?></a></li>
			<?php endif; ?>
			<?php if ( $results_url ) : ?>
				<li><a href="<?php echo esc_url( $results_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Pagina risultati (anteprima)', 'casa-vacanza-prenotazioni' ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>

	<div class="cvp-admin-panel">
		<?php include CVP_PLUGIN_DIR . 'admin/views/partials/shortcodes-reference.php'; ?>
	</div>

	<div class="cvp-admin-panel">
		<h2><?php esc_html_e( 'Parametri URL ricerca', 'casa-vacanza-prenotazioni' ); ?></h2>
		<p><?php esc_html_e( 'La barra ricerca passa questi parametri alla pagina risultati:', 'casa-vacanza-prenotazioni' ); ?></p>
		<ul>
			<li><code>cvp_check_in</code> — <?php esc_html_e( 'data check-in (YYYY-MM-DD)', 'casa-vacanza-prenotazioni' ); ?></li>
			<li><code>cvp_check_out</code> — <?php esc_html_e( 'data check-out (YYYY-MM-DD)', 'casa-vacanza-prenotazioni' ); ?></li>
			<li><code>cvp_guests</code> — <?php esc_html_e( 'numero ospiti', 'casa-vacanza-prenotazioni' ); ?></li>
		</ul>
	</div>
</div>
