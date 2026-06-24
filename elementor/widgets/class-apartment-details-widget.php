<?php
/**
 * Widget Elementor: Dettagli Appartamento.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use CVP\Apartment_Meta;
use CVP\Post_Types;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Apartment_Details_Widget extends Widget_Base {

	public function get_name() {
		return 'cvp_apartment_details';
	}

	public function get_title() {
		return __( 'Dettagli Appartamento', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-info-circle-o';
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
			'show_price',
			array(
				'label'        => __( 'Mostra prezzo', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_guests',
			array(
				'label'        => __( 'Mostra capienza', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_bedrooms',
			array(
				'label'        => __( 'Mostra camere', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_beds',
			array(
				'label'        => __( 'Mostra posti letto', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_bathrooms',
			array(
				'label'        => __( 'Mostra bagni', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_location',
			array(
				'label'        => __( 'Mostra ubicazione', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_cleaning_fee',
			array(
				'label'        => __( 'Mostra spese pulizia', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private static function get_apartment_options() {
		$options = array( '0' => __( '— Appartamento corrente —', 'casa-vacanza-prenotazioni' ) );
		foreach ( get_posts( array( 'post_type' => Post_Types::APPARTAMENTO, 'posts_per_page' => -1, 'post_status' => array( 'publish', 'draft' ) ) ) as $apt ) {
			$options[ $apt->ID ] = $apt->post_title;
		}
		return $options;
	}

	protected function render() {
		$settings     = $this->get_settings_for_display();
		$apartment_id = Apartment_Meta::resolve_apartment_id( absint( $settings['apartment_id'] ) );
		if ( ! $apartment_id ) {
			return;
		}

		$data = Shortcodes::get_apartment_data( $apartment_id );
		\CVP\Assets::enqueue_if_needed();
		?>
		<ul class="cvp-apartment-details">
			<?php if ( 'yes' === $settings['show_price'] && $data['price_fmt'] ) : ?>
				<li class="cvp-apartment-details__price">
					<strong><?php esc_html_e( 'Prezzo', 'casa-vacanza-prenotazioni' ); ?>:</strong>
					<?php echo esc_html( $data['price_fmt'] ); ?> / <?php esc_html_e( 'notte', 'casa-vacanza-prenotazioni' ); ?>
				</li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_guests'] && $data['max_guests'] ) : ?>
				<li><strong><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( (string) $data['max_guests'] ); ?></li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_bedrooms'] && $data['bedrooms'] ) : ?>
				<li><strong><?php esc_html_e( 'Camere', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( (string) $data['bedrooms'] ); ?></li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_beds'] && $data['beds'] ) : ?>
				<li><strong><?php esc_html_e( 'Posti letto', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( (string) $data['beds'] ); ?></li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_bathrooms'] && $data['bathrooms'] ) : ?>
				<li><strong><?php esc_html_e( 'Bagni', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( (string) $data['bathrooms'] ); ?></li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_location'] && $data['location'] ) : ?>
				<li><strong><?php esc_html_e( 'Ubicazione', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( $data['location'] ); ?></li>
			<?php endif; ?>
			<?php if ( 'yes' === $settings['show_cleaning_fee'] && $data['cleaning_fmt'] ) : ?>
				<li><strong><?php esc_html_e( 'Pulizia', 'casa-vacanza-prenotazioni' ); ?>:</strong> <?php echo esc_html( $data['cleaning_fmt'] ); ?></li>
			<?php endif; ?>
		</ul>
		<?php
	}
}
