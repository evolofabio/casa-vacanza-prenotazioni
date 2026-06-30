<?php
/**
 * Template form prenotazione.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

\CVP\Assets::enqueue_if_needed();

$apt_data     = \CVP\Shortcodes::get_apartment_data( $apartment_id );
$max_guests   = (int) $apt_data['max_guests'];
$availability = \CVP\Availability::get_frontend_availability( $apartment_id );
?>
<div class="cvp-booking-form-wrapper">
	<h3 class="cvp-booking-form__title">
		<?php
		printf(
			/* translators: %s: apartment title */
			esc_html__( 'Richiedi prenotazione: %s', 'casa-vacanza-prenotazioni' ),
			esc_html( $apt_data['title'] )
		);
		?>
	</h3>

	<form class="cvp-booking-form" data-apartment-id="<?php echo esc_attr( $apartment_id ); ?>" data-availability="<?php echo esc_attr( wp_json_encode( $availability ) ); ?>">
		<input type="hidden" name="apartment_id" value="<?php echo esc_attr( $apartment_id ); ?>" />
		<div class="cvp-honeypot" aria-hidden="true" hidden>
			<label for="cvp_website_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Lascia vuoto', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="text" id="cvp_website_<?php echo esc_attr( $apartment_id ); ?>" name="cvp_website" value="" tabindex="-1" autocomplete="off" />
		</div>

		<div class="cvp-form-row cvp-form-row--2">
			<div class="cvp-form-field">
				<label for="cvp_bf_check_in_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></label>
				<input type="date" id="cvp_bf_check_in_<?php echo esc_attr( $apartment_id ); ?>" name="check_in" value="<?php echo esc_attr( $check_in ); ?>" required min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" class="cvp-date-input" />
			</div>
			<div class="cvp-form-field">
				<label for="cvp_bf_check_out_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></label>
				<input type="date" id="cvp_bf_check_out_<?php echo esc_attr( $apartment_id ); ?>" name="check_out" value="<?php echo esc_attr( $check_out ); ?>" required class="cvp-date-input" />
			</div>
		</div>

		<div class="cvp-form-field">
			<label for="cvp_bf_guests_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Numero ospiti', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="number" id="cvp_bf_guests_<?php echo esc_attr( $apartment_id ); ?>" name="guests" value="<?php echo esc_attr( $guests ); ?>" min="1" <?php echo $max_guests ? 'max="' . esc_attr( $max_guests ) . '"' : ''; ?> required />
			<?php if ( ! empty( $apt_data['beds'] ) ) : ?>
				<p class="description"><?php echo esc_html( sprintf( __( 'Posti letto: %d', 'casa-vacanza-prenotazioni' ), $apt_data['beds'] ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="cvp-date-feedback" role="status" hidden></div>

		<div class="cvp-price-summary" hidden>
			<span class="cvp-price-summary__label"><?php esc_html_e( 'Totale stimato:', 'casa-vacanza-prenotazioni' ); ?></span>
			<span class="cvp-price-summary__value"></span>
		</div>

		<hr />

		<div class="cvp-form-field">
			<label for="cvp_bf_name_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Nome e cognome', 'casa-vacanza-prenotazioni' ); ?> *</label>
			<input type="text" id="cvp_bf_name_<?php echo esc_attr( $apartment_id ); ?>" name="customer_name" required />
		</div>

		<div class="cvp-form-row cvp-form-row--2">
			<div class="cvp-form-field">
				<label for="cvp_bf_email_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Email', 'casa-vacanza-prenotazioni' ); ?> *</label>
				<input type="email" id="cvp_bf_email_<?php echo esc_attr( $apartment_id ); ?>" name="customer_email" required />
			</div>
			<div class="cvp-form-field">
				<label for="cvp_bf_phone_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Telefono', 'casa-vacanza-prenotazioni' ); ?></label>
				<input type="tel" id="cvp_bf_phone_<?php echo esc_attr( $apartment_id ); ?>" name="customer_phone" />
			</div>
		</div>

		<div class="cvp-form-field">
			<label for="cvp_bf_note_<?php echo esc_attr( $apartment_id ); ?>"><?php esc_html_e( 'Note (opzionale)', 'casa-vacanza-prenotazioni' ); ?></label>
			<textarea id="cvp_bf_note_<?php echo esc_attr( $apartment_id ); ?>" name="customer_note" rows="3"></textarea>
		</div>

		<div class="cvp-form-field cvp-form-field--checkbox">
			<label class="cvp-checkbox-label" for="cvp_bf_privacy_<?php echo esc_attr( $apartment_id ); ?>">
				<input type="checkbox" id="cvp_bf_privacy_<?php echo esc_attr( $apartment_id ); ?>" name="privacy_consent" value="1" required />
				<span><?php echo wp_kses_post( \CVP\Privacy::get_consent_label() ); ?></span>
			</label>
		</div>

		<div class="cvp-form-message" role="alert" hidden></div>

		<button type="submit" class="cvp-btn cvp-btn--primary cvp-btn--block">
			<?php esc_html_e( 'Invia richiesta', 'casa-vacanza-prenotazioni' ); ?>
		</button>
	</form>
</div>
