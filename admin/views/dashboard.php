<?php
/**
 * Vista dashboard operatore.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cvp-admin-wrap">
	<h1><?php esc_html_e( 'Dashboard Casa Vacanza', 'casa-vacanza-prenotazioni' ); ?></h1>

	<div class="notice notice-info inline cvp-dashboard-notice">
		<p>
			<?php esc_html_e( 'Guida, shortcode e istruzioni:', 'casa-vacanza-prenotazioni' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-help' ) ); ?>"><strong><?php esc_html_e( 'Guida e Shortcode', 'casa-vacanza-prenotazioni' ); ?></strong></a>
		</p>
	</div>

	<div class="cvp-stats-grid">
		<div class="cvp-stat-card cvp-stat-card--warning">
			<span class="cvp-stat-card__number"><?php echo esc_html( $pending ); ?></span>
			<span class="cvp-stat-card__label"><?php esc_html_e( 'Richieste in attesa', 'casa-vacanza-prenotazioni' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-bookings&status=in_attesa' ) ); ?>" class="cvp-stat-card__link"><?php esc_html_e( 'Gestisci →', 'casa-vacanza-prenotazioni' ); ?></a>
		</div>
		<div class="cvp-stat-card cvp-stat-card--success">
			<span class="cvp-stat-card__number"><?php echo esc_html( $confirmed ); ?></span>
			<span class="cvp-stat-card__label"><?php esc_html_e( 'Prenotazioni confermate', 'casa-vacanza-prenotazioni' ); ?></span>
		</div>
		<div class="cvp-stat-card">
			<span class="cvp-stat-card__number"><?php echo esc_html( wp_count_posts( \CVP\Post_Types::APPARTAMENTO )->publish ); ?></span>
			<span class="cvp-stat-card__label"><?php esc_html_e( 'Appartamenti attivi', 'casa-vacanza-prenotazioni' ); ?></span>
		</div>
	</div>

	<div class="cvp-admin-panel">
		<h2><?php esc_html_e( 'Prossime prenotazioni confermate', 'casa-vacanza-prenotazioni' ); ?></h2>
		<?php if ( empty( $upcoming ) ) : ?>
			<p><?php esc_html_e( 'Nessuna prenotazione in programma.', 'casa-vacanza-prenotazioni' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Cliente', 'casa-vacanza-prenotazioni' ); ?></th>
						<th><?php esc_html_e( 'Appartamento', 'casa-vacanza-prenotazioni' ); ?></th>
						<th><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></th>
						<th><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></th>
						<th><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $upcoming as $booking ) : ?>
						<tr>
							<td><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_customer_name', true ) ); ?></td>
							<td><?php echo esc_html( get_the_title( get_post_meta( $booking->ID, '_cvp_apartment_id', true ) ) ); ?></td>
							<td><?php echo esc_html( \CVP\Post_Types::format_date( get_post_meta( $booking->ID, '_cvp_check_in', true ) ) ); ?></td>
							<td><?php echo esc_html( \CVP\Post_Types::format_date( get_post_meta( $booking->ID, '_cvp_check_out', true ) ) ); ?></td>
							<td><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_guests', true ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
