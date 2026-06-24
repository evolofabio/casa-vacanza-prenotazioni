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
		add_action( 'admin_init', array( __CLASS__, 'ensure_cpt_support' ) );

		require_once CVP_PLUGIN_DIR . 'elementor/class-elementor-apartment-document.php';
		require_once CVP_PLUGIN_DIR . 'elementor/class-cvp-widget-base.php';
		require_once CVP_PLUGIN_DIR . 'elementor/dynamic-tags/class-dynamic-tags.php';

		Elementor\Apartment_Document::init();
		Elementor\DynamicTags\Dynamic_Tags::init();

		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'register_frontend_assets' ) );
	}

	/**
	 * Registra CSS/JS plugin per Elementor frontend.
	 */
	public static function register_frontend_assets() {
		Assets::register_frontend();
	}

	/**
	 * Abilita il CPT appartamenti in Elementor.
	 */
	public static function ensure_cpt_support() {
		$cpt_support = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );
		if ( ! is_array( $cpt_support ) ) {
			$cpt_support = array( 'page', 'post' );
		}

		if ( in_array( Post_Types::APPARTAMENTO, $cpt_support, true ) ) {
			return;
		}

		$cpt_support[] = Post_Types::APPARTAMENTO;
		update_option( 'elementor_cpt_support', $cpt_support );
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
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-search-results-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-apartment-card-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-apartment-gallery-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-apartment-details-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-apartment-services-widget.php';
		require_once CVP_PLUGIN_DIR . 'elementor/widgets/class-booking-form-widget.php';

		$widgets_manager->register( new \CVP\Elementor\Widgets\Search_Bar_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Search_Results_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Apartment_Card_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Apartment_Gallery_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Apartment_Details_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Apartment_Services_Widget() );
		$widgets_manager->register( new \CVP\Elementor\Widgets\Booking_Form_Widget() );
	}
}
