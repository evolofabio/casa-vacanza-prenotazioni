<?php
/**
 * Widget Elementor: Risultati Ricerca.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;
use CVP\Shortcodes;

defined( 'ABSPATH' ) || exit;

class Search_Results_Widget extends Widget_Base {

	public function get_name() {
		return 'cvp_search_results';
	}

	public function get_title() {
		return __( 'Risultati Ricerca', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'casa-vacanza' );
	}

	protected function register_controls() {}

	protected function render() {
		echo Shortcodes::search_results();
	}
}
