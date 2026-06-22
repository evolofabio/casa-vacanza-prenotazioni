<?php
/**
 * Registrazione blocchi Gutenberg.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Blocks {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Registra blocchi solo lato server (evita conflitti editor).
	 */
	public static function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$blocks = array(
			'search-bar'     => array(
				'callback'   => array( __CLASS__, 'render_search_bar_block' ),
				'attributes' => array(
					'resultsPage' => array( 'type' => 'number', 'default' => 0 ),
				),
			),
			'apartment-card' => array(
				'callback'   => array( __CLASS__, 'render_apartment_card_block' ),
				'attributes' => array(
					'apartmentId' => array( 'type' => 'number', 'default' => 0 ),
					'showBooking' => array( 'type' => 'boolean', 'default' => true ),
				),
			),
			'booking-form'   => array(
				'callback'   => array( __CLASS__, 'render_booking_form_block' ),
				'attributes' => array(
					'apartmentId' => array( 'type' => 'number', 'default' => 0 ),
				),
			),
			'search-results' => array(
				'callback'   => array( Shortcodes::class, 'search_results' ),
				'attributes' => array(),
			),
		);

		foreach ( $blocks as $slug => $config ) {
			$metadata = self::get_block_metadata( $slug );
			if ( empty( $metadata ) ) {
				continue;
			}

			register_block_type(
				'cvp/' . $slug,
				array_merge(
					$metadata,
					array(
						'attributes'      => $config['attributes'],
						'render_callback' => $config['callback'],
					)
				)
			);
		}
	}

	/**
	 * Metadati blocchi da block.json (senza editorScript).
	 *
	 * @param string $slug Slug blocco.
	 * @return array
	 */
	private static function get_block_metadata( $slug ) {
		$file = CVP_PLUGIN_DIR . 'blocks/' . $slug . '/block.json';
		if ( ! file_exists( $file ) ) {
			return array();
		}

		$metadata = json_decode( file_get_contents( $file ), true );
		if ( ! is_array( $metadata ) ) {
			return array();
		}

		unset( $metadata['editorScript'], $metadata['$schema'] );

		return $metadata;
	}

	/**
	 * Render blocco search bar.
	 *
	 * @param array $attributes Attributi blocco.
	 * @return string
	 */
	public static function render_search_bar_block( $attributes ) {
		$atts = array();

		if ( ! empty( $attributes['resultsPage'] ) ) {
			$atts['results_page'] = $attributes['resultsPage'];
		}

		return Shortcodes::search_bar( $atts );
	}

	/**
	 * Render blocco apartment card.
	 *
	 * @param array $attributes Attributi blocco.
	 * @return string
	 */
	public static function render_apartment_card_block( $attributes ) {
		$atts = array(
			'id'           => isset( $attributes['apartmentId'] ) ? $attributes['apartmentId'] : 0,
			'show_booking' => isset( $attributes['showBooking'] ) && false === $attributes['showBooking'] ? 'no' : 'yes',
		);

		return Shortcodes::apartment_card( $atts );
	}

	/**
	 * Render blocco booking form.
	 *
	 * @param array $attributes Attributi blocco.
	 * @return string
	 */
	public static function render_booking_form_block( $attributes ) {
		$atts = array(
			'apartment_id' => isset( $attributes['apartmentId'] ) ? $attributes['apartmentId'] : 0,
		);

		return Shortcodes::booking_form( $atts );
	}
}
