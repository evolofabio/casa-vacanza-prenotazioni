<?php
/**
 * Plugin Name:       Casa Vacanza Prenotazioni
 * Plugin URI:        https://github.com/evolofabio/casa-vacanza-prenotazioni
 * Description:       Sistema completo di prenotazioni per case vacanza con appartamenti, calendario disponibilità, widget e area operatore.
 * Version:           1.1.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Evolo Digital Studio
 * Text Domain:       casa-vacanza-prenotazioni
 * Domain Path:       /languages
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

// Evita fatal error se esistono più cartelle del plugin (es. dopo aggiornamento fallito).
if ( defined( 'CVP_LOADED' ) ) {
	return;
}
define( 'CVP_LOADED', true );

if ( ! defined( 'CVP_VERSION' ) ) {
	define( 'CVP_VERSION', '1.1.2' );
	define( 'CVP_PLUGIN_FILE', __FILE__ );
	define( 'CVP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'CVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	define( 'CVP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
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
	CVP\Roles::create_role();
	CVP\Post_Types::register();
	CVP\Roles::maybe_add_caps_to_admin();
	flush_rewrite_rules();
	CVP\Activator::create_pages();
}
register_activation_hook( __FILE__, 'cvp_activate' );

/**
 * Disattivazione.
 */
function cvp_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cvp_deactivate' );
