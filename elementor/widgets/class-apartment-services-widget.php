<?php
/**
 * Widget Elementor: Servizi Appartamento.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Controls_Manager;
use CVP\Apartment_Meta;
use CVP\Post_Types;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Apartment_Services_Widget extends Cvp_Widget_Base {

	public function get_name() {
		return 'cvp_apartment_services';
	}

	public function get_title() {
		return __( 'Servizi Appartamento', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
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
			'title',
			array(
				'label'   => __( 'Titolo sezione', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Servizi inclusi', 'casa-vacanza-prenotazioni' ),
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
		if ( empty( $data['services'] ) ) {
			return;
		}

		\CVP\Assets::enqueue_if_needed();
		?>
		<div class="cvp-apartment-services">
			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<h3 class="cvp-apartment-services__title"><?php echo esc_html( $settings['title'] ); ?></h3>
			<?php endif; ?>
			<ul class="cvp-services-list">
				<?php foreach ( $data['services'] as $service ) : ?>
					<li><?php echo esc_html( $service ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
