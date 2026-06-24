<?php
/**
 * Widget Elementor: Form Prenotazione.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Controls_Manager;
use CVP\Post_Types;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Booking_Form_Widget extends Cvp_Widget_Base {

	public function get_name() {
		return 'cvp_booking_form';
	}

	public function get_title() {
		return __( 'Form Prenotazione', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
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

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo Shortcodes::booking_form(
			array(
				'apartment_id' => $settings['apartment_id'],
			)
		);
	}
}
