<?php
/**
 * Bootstrap sicuro e utilità installazione.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Install {

	const CANONICAL_SLUG = 'casa-vacanza-prenotazioni/casa-vacanza-prenotazioni.php';

	/**
	 * File minimi richiesti per avviare il plugin.
	 *
	 * @return array
	 */
	public static function get_required_files() {
		return array(
			'includes/class-autoloader.php',
			'includes/class-plugin.php',
			'includes/class-post-types.php',
			'includes/class-roles.php',
			'includes/class-settings.php',
		);
	}

	/**
	 * Verifica che l'installazione sul disco sia completa.
	 *
	 * @return bool
	 */
	public static function is_complete() {
		if ( ! defined( 'CVP_PLUGIN_DIR' ) ) {
			return false;
		}

		foreach ( self::get_required_files() as $file ) {
			if ( ! is_readable( CVP_PLUGIN_DIR . $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Registra autoloader per attivazione/ripristino.
	 */
	public static function register_autoloader() {
		$file = CVP_PLUGIN_DIR . 'includes/class-autoloader.php';
		if ( is_readable( $file ) ) {
			require_once $file;
			Autoloader::register();
		}
	}

	/**
	 * Avviso installazione incompleta (evita white screen).
	 */
	public static function register_incomplete_notice() {
		if ( ! is_admin() ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				echo '<div class="notice notice-error"><p><strong>';
				esc_html_e( 'Casa Vacanza Prenotazioni: installazione incompleta o corrotta.', 'casa-vacanza-prenotazioni' );
				echo '</strong> ';
				esc_html_e( 'Elimina la cartella del plugin e reinstalla lo zip dalla release GitHub senza estrarlo manualmente.', 'casa-vacanza-prenotazioni' );
				echo ' <a href="https://github.com/evolofabio/casa-vacanza-prenotazioni/releases/latest" target="_blank" rel="noopener">';
				esc_html_e( 'Scarica release', 'casa-vacanza-prenotazioni' );
				echo '</a></p></div>';
			}
		);

		add_action(
			'admin_init',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) || ! defined( 'CVP_PLUGIN_BASENAME' ) ) {
					return;
				}

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				deactivate_plugins( CVP_PLUGIN_BASENAME, true );
			},
			1
		);
	}

	/**
	 * Pulisce copie duplicate nel database dopo attivazione.
	 */
	public static function cleanup_duplicate_entries() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$current = defined( 'CVP_PLUGIN_BASENAME' ) ? CVP_PLUGIN_BASENAME : self::CANONICAL_SLUG;
		$active  = (array) get_option( 'active_plugins', array() );
		$clean   = array();

		foreach ( $active as $plugin ) {
			if ( false !== strpos( $plugin, 'casa-vacanza-prenotazioni.php' ) ) {
				if ( $plugin === $current ) {
					$clean[] = $plugin;
				}
				continue;
			}

			$clean[] = $plugin;
		}

		if ( $clean !== $active ) {
			update_option( 'active_plugins', array_values( array_unique( $clean ) ) );
		}
	}

	/**
	 * Attivazione sicura.
	 */
	public static function activate() {
		if ( ! self::is_complete() ) {
			wp_die(
				esc_html__( 'Installazione incompleta: reinstalla lo zip casa-vacanza-prenotazioni dalla release GitHub.', 'casa-vacanza-prenotazioni' ),
				esc_html__( 'Casa Vacanza Prenotazioni', 'casa-vacanza-prenotazioni' ),
				array( 'back_link' => true )
			);
		}

		self::register_autoloader();
		self::cleanup_duplicate_entries();
		GitHub_Updater::maybe_migrate_install_directory();

		Roles::create_role();
		Post_Types::register();
		Roles::maybe_add_caps_to_admin();
		Booking_Expiry::maybe_schedule_cron();
		flush_rewrite_rules();
		Activator::create_pages();
	}
}
