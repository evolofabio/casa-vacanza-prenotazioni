<?php
/**
 * Template barra ricerca.
 *
 * @package CasaVacanzaPrenotazioni
 *
 * @var string $action
 * @var string $form_id
 * @var string $today
 * @var int    $max_guests_limit
 * @var string $check_in
 * @var string $check_out
 * @var int    $guests
 */

defined( 'ABSPATH' ) || exit;

\CVP\Assets::enqueue_if_needed();

$check_in_id  = $form_id . '_check_in';
$check_out_id = $form_id . '_check_out';
$guests_id    = $form_id . '_guests';
?>
<form class="cvp-search-bar" method="get" action="<?php echo esc_url( $action ); ?>">
	<div class="cvp-search-bar__inner">
		<div class="cvp-search-bar__field">
			<label for="<?php echo esc_attr( $check_in_id ); ?>"><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $check_in_id ); ?>" name="cvp_check_in" value="<?php echo esc_attr( $check_in ); ?>" required min="<?php echo esc_attr( $today ); ?>" />
		</div>
		<div class="cvp-search-bar__field">
			<label for="<?php echo esc_attr( $check_out_id ); ?>"><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $check_out_id ); ?>" name="cvp_check_out" value="<?php echo esc_attr( $check_out ); ?>" required />
		</div>
		<div class="cvp-search-bar__field">
			<label for="<?php echo esc_attr( $guests_id ); ?>"><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?></label>
			<input type="number" id="<?php echo esc_attr( $guests_id ); ?>" name="cvp_guests" value="<?php echo esc_attr( $guests ); ?>" min="1" max="<?php echo esc_attr( $max_guests_limit ); ?>" required />
		</div>
		<div class="cvp-search-bar__submit">
			<button type="submit" class="cvp-btn cvp-btn--primary"><?php esc_html_e( 'Cerca', 'casa-vacanza-prenotazioni' ); ?></button>
		</div>
	</div>
</form>
