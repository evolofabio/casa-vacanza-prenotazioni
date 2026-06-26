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
		Emails::init();
		Admin_Dashboard::init();
		Shortcodes::init();
		Ajax::init();
		Assets::init();
		Blocks::init();
		GitHub_Updater::init();

		if ( is_admin() ) {
			add_action( 'admin_notices', array( __CLASS__, 'maybe_health_notice' ) );
		}

		if ( did_action( 'elementor/loaded' ) ) {
			Elementor_Integration::init();
		} else {
			add_action( 'elementor/loaded', array( Elementor_Integration::class, 'init' ) );
		}
	}

	/**
	 * Avviso se sono presenti copie duplicate del plugin.
	 */
	public static function maybe_health_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! class_exists( GitHub_Updater::class ) || ! method_exists( GitHub_Updater::class, 'find_plugin_copies' ) ) {
			return;
		}

		$copies = GitHub_Updater::find_plugin_copies();
		if ( count( $copies ) <= 1 ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>';
		esc_html_e( 'Casa Vacanza Prenotazioni: rilevate più copie del plugin.', 'casa-vacanza-prenotazioni' );
		echo '</strong> ';
		echo esc_html( implode( ', ', $copies ) );
		echo ' — ';
		esc_html_e( 'Disattiva il plugin, elimina tutte le cartelle casa-vacanza-prenotazioni* e reinstalla lo zip dalla release GitHub.', 'casa-vacanza-prenotazioni' );
		echo '</p></div>';
	}
}
