<?php
/**
 * Widget Elementor: Card Appartamento.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use CVP\Post_Types;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Apartment_Card_Widget extends Widget_Base {

	public function get_name() {
		return 'cvp_apartment_card';
	}

	public function get_title() {
		return __( 'Card Appartamento', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'casa-vacanza' );
	}

	protected function register_controls() {
		$apartments = get_posts(
			array(
				'post_type'      => Post_Types::APPARTAMENTO,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$options = array( '0' => __( '— Appartamento corrente —', 'casa-vacanza-prenotazioni' ) );
		foreach ( $apartments as $apt ) {
			$options[ $apt->ID ] = $apt->post_title;
		}

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
				'options' => $options,
				'default' => '0',
			)
		);

		$this->add_control(
			'show_booking',
			array(
				'label'        => __( 'Mostra prenotazione', 'casa-vacanza-prenotazioni' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Sì', 'casa-vacanza-prenotazioni' ),
				'label_off'    => __( 'No', 'casa-vacanza-prenotazioni' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo Shortcodes::apartment_card(
			array(
				'id'           => $settings['apartment_id'],
				'show_booking' => $settings['show_booking'] ? 'yes' : 'no',
			)
		);
	}
}
