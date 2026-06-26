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
	 * Slug blocchi registrati.
	 *
	 * @var string[]
	 */
	private static $block_slugs = array(
		'search-bar',
		'apartment-card',
		'booking-form',
		'search-results',
	);

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
	}

	/**
	 * Registra blocchi server-side con editor script da block.json.
	 */
	public static function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$callbacks = array(
			'search-bar'     => array( __CLASS__, 'render_search_bar_block' ),
			'apartment-card' => array( __CLASS__, 'render_apartment_card_block' ),
			'booking-form'   => array( __CLASS__, 'render_booking_form_block' ),
			'search-results' => array( Shortcodes::class, 'search_results' ),
		);

		foreach ( self::$block_slugs as $slug ) {
			$dir = CVP_PLUGIN_DIR . 'blocks/' . $slug;
			if ( ! file_exists( $dir . '/block.json' ) ) {
				continue;
			}

			register_block_type(
				$dir,
				array(
					'render_callback' => isset( $callbacks[ $slug ] ) ? $callbacks[ $slug ] : null,
				)
			);
		}
	}

	/**
	 * Dati condivisi per gli script editor dei blocchi.
	 */
	public static function enqueue_editor_assets() {
		$pages = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);

		$apartments = get_posts(
			array(
				'post_type'              => Post_Types::APPARTAMENTO,
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);

		$page_items = array();
		foreach ( $pages as $page ) {
			$page_items[] = array(
				'id'    => $page->ID,
				'title' => $page->post_title,
			);
		}

		$apartment_items = array();
		foreach ( $apartments as $apartment ) {
			$apartment_items[] = array(
				'id'    => $apartment->ID,
				'title' => $apartment->post_title,
			);
		}

		wp_register_script( 'cvp-blocks-data', false, array(), CVP_VERSION, true );
		wp_enqueue_script( 'cvp-blocks-data' );
		wp_add_inline_script(
			'cvp-blocks-data',
			'window.cvpBlockData = ' . wp_json_encode(
				array(
					'pages'      => $page_items,
					'apartments' => $apartment_items,
				)
			) . ';',
			'before'
		);
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
