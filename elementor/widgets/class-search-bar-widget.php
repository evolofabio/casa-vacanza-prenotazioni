<?php
/**
 * Widget Elementor: Barra Ricerca.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use CVP\Shortcodes;
use CVP\Settings;

defined( 'ABSPATH' ) || exit;

class Search_Bar_Widget extends Widget_Base {

	public function get_name() {
		return 'cvp_search_bar';
	}

	public function get_title() {
		return __( 'Barra Ricerca Casa Vacanza', 'casa-vacanza-prenotazioni' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return array( 'casa-vacanza' );
	}

	protected function register_controls() {
		$pages = get_pages();
		$options = array( '0' => __( '— Default —', 'casa-vacanza-prenotazioni' ) );

		foreach ( $pages as $page ) {
			$options[ $page->ID ] = $page->post_title;
		}

		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Impostazioni', 'casa-vacanza-prenotazioni' ),
			)
		);

		$this->add_control(
			'results_page',
			array(
				'label'   => __( 'Pagina risultati', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $options,
				'default' => (string) Settings::get()['cvp_risultati_page'],
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo Shortcodes::search_bar(
			array(
				'results_page' => $settings['results_page'],
			)
		);
	}
}
