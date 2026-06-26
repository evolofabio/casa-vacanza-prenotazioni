<?php
/**
 * Template card appartamento.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

\CVP\Assets::enqueue_if_needed();

$data = \CVP\Shortcodes::get_apartment_data( $apartment_id );
?>
<article class="cvp-apartment-card" data-apartment-id="<?php echo esc_attr( $data['id'] ); ?>">
	<?php if ( ! empty( $data['images'] ) ) : ?>
		<div class="cvp-apartment-card__gallery">
			<div class="cvp-gallery-main">
				<img src="<?php echo esc_url( $data['images'][0]['url'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" />
			</div>
			<?php if ( count( $data['images'] ) > 1 ) : ?>
				<div class="cvp-gallery-thumbs">
					<?php foreach ( $data['images'] as $index => $image ) : ?>
						<button type="button" class="cvp-gallery-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-url="<?php echo esc_url( $image['url'] ); ?>">
							<img src="<?php echo esc_url( $image['thumb'] ); ?>" alt="" />
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="cvp-apartment-card__body">
		<h3 class="cvp-apartment-card__title">
			<?php if ( ! empty( $data['permalink'] ) ) : ?>
				<a href="<?php echo esc_url( $data['permalink'] ); ?>"><?php echo esc_html( $data['title'] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $data['title'] ); ?>
			<?php endif; ?>
		</h3>

		<div class="cvp-apartment-card__meta">
			<span class="cvp-price"><?php echo esc_html( $data['price_fmt'] ); ?> <small>/ <?php esc_html_e( 'notte', 'casa-vacanza-prenotazioni' ); ?></small></span>
			<?php if ( $data['max_guests'] ) : ?>
				<span class="cvp-guests"><?php echo esc_html( sprintf( __( 'Max %d ospiti', 'casa-vacanza-prenotazioni' ), $data['max_guests'] ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $data['bedrooms'] ) ) : ?>
				<span class="cvp-bedrooms"><?php echo esc_html( sprintf( __( '%d camere', 'casa-vacanza-prenotazioni' ), $data['bedrooms'] ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $data['beds'] ) ) : ?>
				<span class="cvp-beds"><?php echo esc_html( sprintf( __( '%d posti letto', 'casa-vacanza-prenotazioni' ), $data['beds'] ) ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $data['location'] ) ) : ?>
				<span class="cvp-location"><?php echo esc_html( $data['location'] ); ?></span>
			<?php endif; ?>
		</div>

		<div class="cvp-apartment-card__description">
			<?php echo wp_kses_post( $data['excerpt'] ); ?>
		</div>

		<?php if ( ! empty( $data['services'] ) ) : ?>
			<ul class="cvp-services-list">
				<?php foreach ( $data['services'] as $service ) : ?>
					<li><?php echo esc_html( $service ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $show_booking ) : ?>
			<div class="cvp-apartment-card__actions">
				<?php if ( ! empty( $data['permalink'] ) ) : ?>
					<a href="<?php echo esc_url( $data['permalink'] ); ?>" class="cvp-btn cvp-btn--secondary">
						<?php esc_html_e( 'Vedi scheda', 'casa-vacanza-prenotazioni' ); ?>
					</a>
				<?php endif; ?>
				<button type="button"
					class="cvp-btn cvp-btn--primary cvp-open-booking"
					data-apartment-id="<?php echo esc_attr( $data['id'] ); ?>"
					data-check-in="<?php echo esc_attr( $check_in ); ?>"
					data-check-out="<?php echo esc_attr( $check_out ); ?>"
					data-guests="<?php echo esc_attr( $guests ); ?>">
					<?php esc_html_e( 'Richiedi prenotazione', 'casa-vacanza-prenotazioni' ); ?>
				</button>
			</div>

			<div class="cvp-booking-modal" id="cvp-booking-modal-<?php echo esc_attr( $data['id'] ); ?>" role="dialog" aria-modal="true" aria-labelledby="cvp-modal-title-<?php echo esc_attr( $data['id'] ); ?>" hidden>
				<div class="cvp-booking-modal__overlay" tabindex="-1"></div>
				<div class="cvp-booking-modal__content">
					<button type="button" class="cvp-booking-modal__close" aria-label="<?php esc_attr_e( 'Chiudi', 'casa-vacanza-prenotazioni' ); ?>">&times;</button>
					<h3 id="cvp-modal-title-<?php echo esc_attr( $data['id'] ); ?>" class="screen-reader-text"><?php esc_html_e( 'Richiedi prenotazione', 'casa-vacanza-prenotazioni' ); ?></h3>
					<?php
					$apartment_id = $data['id'];
					include CVP_PLUGIN_DIR . 'templates/booking-form.php';
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</article>
