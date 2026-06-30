<?php
/**
 * Bootstrap principale del plugin.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Plugin {

	/**
	 * Istanza singleton.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Ottiene l'istanza.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Costruttore privato.
	 */
	private function __construct() {
		$this->load_textdomain();
		$this->init_components();
	}

	/**
	 * Carica traduzioni.
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'casa-vacanza-prenotazioni',
			false,
			dirname( CVP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Inizializza tutti i componenti.
	 */
	private function init_components() {
		Post_Types::init();
		Apartment_Meta::init();
		Meta_Boxes::init();
		Roles::init();
		Settings::init();
		Availability::init();
		Booking::init();
		Booking_Expiry::init();
		Privacy::init();
		Emails::init();
		Admin_Dashboard::init();
		Shortcodes::init();
		Ajax::init();
		Assets::init();
		Blocks::init();
		GitHub_Updater::init();

		if ( did_action( 'elementor/loaded' ) ) {
			Elementor_Integration::init();
		} else {
			add_action( 'elementor/loaded', array( Elementor_Integration::class, 'init' ) );
		}
	}
}
