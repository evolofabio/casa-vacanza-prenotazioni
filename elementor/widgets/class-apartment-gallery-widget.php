<?php
/**
 * Widget Elementor: Galleria Appartamento.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Controls_Manager;
use CVP\Apartment_Meta;
use CVP\Post_Types;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Apartment_Gallery_Widget extends Cvp_Widget_Base {

	public function get_name() {
		return 'cvp_apartment_gallery';
	}

	public function get_title() {
		return __( 'Galleria Appartamento', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-gallery-masonry';
	}

	public function get_categories() {
		return array( 'casa-vacanza' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Impostazioni', 'casa-vacanza-prenotazioni' ),
			)
		);

		$this->add_control(
			'apartment_id',
			array(
				'label'   => __( 'Appartamento', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::SELECT,
				'options' => self::get_apartment_options(),
				'default' => '0',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'carousel' => __( 'Carousel con miniature', 'casa-vacanza-prenotazioni' ),
					'grid'     => __( 'Griglia', 'casa-vacanza-prenotazioni' ),
				),
				'default' => 'carousel',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Opzioni select appartamenti.
	 *
	 * @return array
	 */
	private static function get_apartment_options() {
		$options = array( '0' => __( '— Appartamento corrente —', 'casa-vacanza-prenotazioni' ) );
		$apartments = get_posts(
			array(
				'post_type'      => Post_Types::APPARTAMENTO,
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $apartments as $apt ) {
			$options[ $apt->ID ] = $apt->post_title;
		}

		return $options;
	}

	/**
	 * Risolve ID appartamento.
	 *
	 * @param array $settings Impostazioni widget.
	 * @return int
	 */
	private function resolve_apartment_id( $settings ) {
		$apartment_id = absint( $settings['apartment_id'] );
		return Apartment_Meta::resolve_apartment_id( $apartment_id );
	}

	protected function render() {
		$settings     = $this->get_settings_for_display();
		$apartment_id = $this->resolve_apartment_id( $settings );
		if ( ! $apartment_id ) {
			return;
		}

		$data = Shortcodes::get_apartment_data( $apartment_id );
		if ( empty( $data['images'] ) ) {
			return;
		}

		\CVP\Assets::enqueue_if_needed();
		$layout = 'grid' === $settings['layout'] ? 'grid' : 'carousel';
		?>
		<div class="cvp-apartment-gallery cvp-apartment-gallery--<?php echo esc_attr( $layout ); ?>" data-apartment-id="<?php echo esc_attr( $apartment_id ); ?>">
			<?php if ( 'carousel' === $layout ) : ?>
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
			<?php else : ?>
				<div class="cvp-gallery-grid">
					<?php foreach ( $data['images'] as $image ) : ?>
						<figure class="cvp-gallery-grid__item">
							<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $data['title'] ); ?>" />
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
