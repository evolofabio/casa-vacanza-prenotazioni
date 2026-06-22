<?php
/**
 * Template risultati ricerca.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

\CVP\Assets::enqueue_if_needed();
?>
<div class="cvp-search-results">
	<?php echo do_shortcode( '[cvp_search_bar]' ); ?>

	<?php if ( $error ) : ?>
		<div class="cvp-notice cvp-notice--error"><?php echo esc_html( $error ); ?></div>
	<?php elseif ( $check_in && $check_out ) : ?>
		<div class="cvp-search-results__header">
			<h2>
				<?php
				printf(
					/* translators: 1: check-in, 2: check-out, 3: guests */
					esc_html__( 'Risultati per %1$s – %2$s (%3$d ospiti)', 'casa-vacanza-prenotazioni' ),
					esc_html( \CVP\Post_Types::format_date( $check_in ) ),
					esc_html( \CVP\Post_Types::format_date( $check_out ) ),
					$guests
				);
				?>
			</h2>
			<p><?php echo esc_html( sprintf( _n( '%d appartamento disponibile', '%d appartamenti disponibili', count( $apartments ), 'casa-vacanza-prenotazioni' ), count( $apartments ) ) ); ?></p>
		</div>

		<?php if ( empty( $apartments ) ) : ?>
			<div class="cvp-notice"><?php esc_html_e( 'Nessun appartamento disponibile per le date selezionate. Prova con date diverse.', 'casa-vacanza-prenotazioni' ); ?></div>
		<?php else : ?>
			<div class="cvp-search-results__grid">
				<?php foreach ( $apartments as $apartment ) : ?>
					<?php
					$apartment_id = $apartment->ID;
					$show_booking = true;
					include CVP_PLUGIN_DIR . 'templates/apartment-card.php';
					?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<div class="cvp-notice"><?php esc_html_e( 'Inserisci le date per vedere gli appartamenti disponibili.', 'casa-vacanza-prenotazioni' ); ?></div>
	<?php endif; ?>
</div>
