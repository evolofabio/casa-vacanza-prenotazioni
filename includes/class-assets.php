<?php
/**
 * Caricamento asset frontend e admin.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Assets {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_frontend' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ) );
	}

	/**
	 * Registra handle CSS/JS (idempotente).
	 */
	public static function register_frontend() {
		if ( ! wp_style_is( 'cvp-public', 'registered' ) ) {
			wp_register_style(
				'cvp-public',
				CVP_PLUGIN_URL . 'public/css/public.css',
				array(),
				CVP_VERSION
			);
		}

		if ( ! wp_script_is( 'cvp-public', 'registered' ) ) {
			wp_register_script(
				'cvp-public',
				CVP_PLUGIN_URL . 'public/js/public.js',
				array( 'jquery' ),
				CVP_VERSION,
				true
			);

			wp_localize_script(
				'cvp-public',
				'cvpPublic',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'cvp_frontend' ),
					'i18n'    => array(
						'sending' => __( 'Invio in corso...', 'casa-vacanza-prenotazioni' ),
						'submit'  => __( 'Invia richiesta', 'casa-vacanza-prenotazioni' ),
						'error'   => __( 'Si è verificato un errore. Riprova.', 'casa-vacanza-prenotazioni' ),
						'book'    => __( 'Richiedi prenotazione', 'casa-vacanza-prenotazioni' ),
					),
				)
			);
		}
	}

	/**
	 * Asset frontend.
	 */
	public static function enqueue_frontend() {
		self::register_frontend();
	}

	/**
	 * Enqueue asset se non già caricati.
	 */
	public static function enqueue_if_needed() {
		self::register_frontend();

		if ( ! wp_style_is( 'cvp-public', 'enqueued' ) ) {
			wp_enqueue_style( 'cvp-public' );
		}
		if ( ! wp_script_is( 'cvp-public', 'enqueued' ) ) {
			wp_enqueue_script( 'cvp-public' );
		}
	}
}
