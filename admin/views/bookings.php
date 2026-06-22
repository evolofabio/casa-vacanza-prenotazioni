<?php
/**
 * Vista gestione prenotazioni.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cvp-admin-wrap">
	<h1><?php esc_html_e( 'Gestione Prenotazioni', 'casa-vacanza-prenotazioni' ); ?></h1>

	<ul class="subsubsub">
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-bookings' ) ); ?>" <?php echo empty( $status_filter ) ? 'class="current"' : ''; ?>><?php esc_html_e( 'Tutte', 'casa-vacanza-prenotazioni' ); ?></a> |</li>
		<?php foreach ( $labels as $value => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-bookings&status=' . $value ) ); ?>" <?php echo $status_filter === $value ? 'class="current"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
				<?php echo $value !== \CVP\Post_Types::STATUS_ANNULLATA ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<table class="wp-list-table widefat fixed striped cvp-bookings-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Cliente', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Appartamento', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Date', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Totale', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Stato', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Azioni', 'casa-vacanza-prenotazioni' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $bookings ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'Nessuna prenotazione trovata.', 'casa-vacanza-prenotazioni' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $bookings as $booking ) : ?>
					<?php
					$status     = get_post_meta( $booking->ID, '_cvp_status', true );
					$apt_id     = get_post_meta( $booking->ID, '_cvp_apartment_id', true );
					$check_in   = get_post_meta( $booking->ID, '_cvp_check_in', true );
					$check_out  = get_post_meta( $booking->ID, '_cvp_check_out', true );
					?>
					<tr data-booking-id="<?php echo esc_attr( $booking->ID ); ?>">
						<td>#<?php echo esc_html( $booking->ID ); ?></td>
						<td>
							<strong><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_customer_name', true ) ); ?></strong><br>
							<small><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_customer_email', true ) ); ?></small>
							<?php if ( get_post_meta( $booking->ID, '_cvp_customer_phone', true ) ) : ?>
								<br><small><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_customer_phone', true ) ); ?></small>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( get_the_title( $apt_id ) ); ?></td>
						<td><?php echo esc_html( \CVP\Post_Types::format_date( $check_in ) . ' → ' . \CVP\Post_Types::format_date( $check_out ) ); ?></td>
						<td><?php echo esc_html( get_post_meta( $booking->ID, '_cvp_guests', true ) ); ?></td>
						<td><?php echo esc_html( \CVP\Settings::format_price( get_post_meta( $booking->ID, '_cvp_total_price', true ) ) ); ?></td>
						<td><span class="cvp-status cvp-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></span></td>
						<td class="cvp-actions">
							<?php if ( \CVP\Post_Types::STATUS_IN_ATTESA === $status ) : ?>
								<button type="button" class="button button-primary cvp-action" data-action="confermata" data-id="<?php echo esc_attr( $booking->ID ); ?>"><?php esc_html_e( 'Accetta', 'casa-vacanza-prenotazioni' ); ?></button>
								<button type="button" class="button cvp-action" data-action="rifiutata" data-id="<?php echo esc_attr( $booking->ID ); ?>" data-ask-note="1"><?php esc_html_e( 'Rifiuta', 'casa-vacanza-prenotazioni' ); ?></button>
							<?php endif; ?>
							<?php if ( in_array( $status, array( \CVP\Post_Types::STATUS_IN_ATTESA, \CVP\Post_Types::STATUS_CONFERMATA ), true ) ) : ?>
								<button type="button" class="button cvp-action" data-action="annullata" data-id="<?php echo esc_attr( $booking->ID ); ?>" data-ask-note="1"><?php esc_html_e( 'Annulla', 'casa-vacanza-prenotazioni' ); ?></button>
							<?php endif; ?>
							<a href="<?php echo esc_url( get_edit_post_link( $booking->ID ) ); ?>" class="button"><?php esc_html_e( 'Dettagli', 'casa-vacanza-prenotazioni' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
