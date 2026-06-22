<?php
/**
 * Autoloader PSR-4 semplificato.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Autoloader {

	/**
	 * Registra l'autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Carica classi nel namespace CVP.
	 *
	 * @param string $class Nome classe completo.
	 */
	public static function autoload( $class ) {
		if ( strpos( $class, 'CVP\\' ) !== 0 ) {
			return;
		}

		$relative = str_replace( 'CVP\\', '', $class );
		$relative = str_replace( '\\', '/', $relative );
		$file     = CVP_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
