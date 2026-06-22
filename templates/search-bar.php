<?php
/**
 * Template barra ricerca.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

\CVP\Assets::enqueue_if_needed();
?>
<form class="cvp-search-bar" method="get" action="<?php echo esc_url( $action ); ?>">
	<div class="cvp-search-bar__inner">
		<div class="cvp-search-bar__field">
			<label for="cvp_check_in"><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="date" id="cvp_check_in" name="cvp_check_in" value="<?php echo esc_attr( $check_in ); ?>" required min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
		</div>
		<div class="cvp-search-bar__field">
			<label for="cvp_check_out"><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="date" id="cvp_check_out" name="cvp_check_out" value="<?php echo esc_attr( $check_out ); ?>" required />
		</div>
		<div class="cvp-search-bar__field">
			<label for="cvp_guests"><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="number" id="cvp_guests" name="cvp_guests" value="<?php echo esc_attr( $guests ); ?>" min="1" max="20" required />
		</div>
		<div class="cvp-search-bar__submit">
			<button type="submit" class="cvp-btn cvp-btn--primary"><?php esc_html_e( 'Cerca', 'casa-vacanza-prenotazioni' ); ?></button>
		</div>
	</div>
</form>
