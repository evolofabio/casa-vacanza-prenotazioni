<?php
/**
 * Base widget Elementor Casa Vacanza.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Garantisce caricamento asset frontend su tutti i viewport (anche mobile/cache Elementor).
 */
abstract class Cvp_Widget_Base extends Widget_Base {

	/**
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'cvp-public' );
	}

	/**
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'cvp-public' );
	}
}
