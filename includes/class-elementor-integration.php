<?php
/**
 * Integrazione Elementor.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Elementor_Integration {

	/**
	 * Inizializza integrazione Elementor.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );
	}

	/**
	 * Registra categoria widget.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Manager Elementor.
	 */
	public static function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'casa-vacanza',
			array(
				'title' => __( 'Casa Vacanza', 'casa-vacanza-prenotazioni' ),
				'icon'  => 'fa fa-home',
			)
		);
	}

	/**
	 * Registra widget Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager widget.
	 */
	public static function register_widgets( $widgets_manager ) {
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-search-bar-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-apartment-card-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-booking-form-widget.php';

		$widgets_manager->register( new \CVP\Elementor\Widgets\Search_Bar_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Apartment_Card_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Booking_Form_Widget() );
	}
}
