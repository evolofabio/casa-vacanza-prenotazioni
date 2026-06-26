<?php
/**
 * Base widget Elementor Casa Vacanza.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Garantisce caricamento asset frontend su tutti i viewport (anche mobile/cache Elementor).
 */
abstract class Cvp_Widget_Base extends Widget_Base {

	/**
	 * @return array
	 */
	public function get_style_depends() {
		\CVP\Assets::register_frontend();

		return array( 'cvp-public' );
	}

	/**
	 * @return array
	 */
	public function get_script_depends() {
		\CVP\Assets::register_frontend();

		return array( 'cvp-public' );
	}
}
