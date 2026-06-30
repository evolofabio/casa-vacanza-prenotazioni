<?php
/**
 * Plugin Name:       Casa Vacanza Prenotazioni
 * Plugin URI:        https://github.com/evolofabio/casa-vacanza-prenotazioni
 * Description:       Sistema completo di prenotazioni per case vacanza con appartamenti, calendario disponibilità, widget e area operatore.
 * Version:           1.3.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Evolo Digital Studio
 * Text Domain:       casa-vacanza-prenotazioni
 * Domain Path:       /languages
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

// Evita fatal error se WordPress carica più copie del plugin.
if ( defined( 'CVP_LOADED' ) ) {
	return;
}
define( 'CVP_LOADED', true );

if ( ! defined( 'CVP_PLUGIN_FILE' ) ) {
	define( 'CVP_PLUGIN_FILE', __FILE__ );
	define( 'CVP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'CVP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'CVP_VERSION' ) ) {
	define( 'CVP_VERSION', '1.3.2' );
}

/**
 * Verifica file core prima del bootstrap (evita white screen).
 *
 * @return bool
 */
function cvp_is_install_complete() {
	$required = array(
		'includes/class-autoloader.php',
		'includes/class-plugin.php',
		'includes/class-post-types.php',
		'includes/class-roles.php',
		'includes/class-settings.php',
	);

	foreach ( $required as $file ) {
		if ( ! is_readable( CVP_PLUGIN_DIR . $file ) ) {
			return false;
		}
	}

	return true;
}

if ( ! cvp_is_install_complete() ) {
	if ( is_admin() ) {
		add_action(
			'admin_notices',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>';
				esc_html_e( 'Casa Vacanza Prenotazioni: installazione incompleta.', 'casa-vacanza-prenotazioni' );
				echo '</strong> ';
				esc_html_e( 'Elimina wp-content/plugins/casa-vacanza-prenotazioni* e reinstalla lo zip dalla release GitHub.', 'casa-vacanza-prenotazioni' );
				echo '</p></div>';
			}
		);
		add_action(
			'admin_init',
			static function () {
				if ( ! current_user_can( 'activate_plugins' ) ) {
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
	return;
}

require_once CVP_PLUGIN_DIR . 'includes/class-autoloader.php';
CVP\Autoloader::register();

/**
 * Avvia il plugin.
 */
function cvp_init() {
	CVP\Plugin::instance();
}
add_action( 'plugins_loaded', 'cvp_init' );

/**
 * Attivazione: ruoli, pagine, rewrite rules.
 */
function cvp_activate() {
	require_once CVP_PLUGIN_DIR . 'includes/class-install.php';
	CVP\Install::activate();
}
register_activation_hook( __FILE__, 'cvp_activate' );

/**
 * Disattivazione.
 */
function cvp_deactivate() {
	if ( class_exists( 'CVP\\Booking_Expiry' ) ) {
		CVP\Booking_Expiry::clear_cron();
	}
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cvp_deactivate' );
