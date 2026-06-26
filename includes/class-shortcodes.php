<?php
/**
 * Shortcodes frontend.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_shortcode( 'cvp_search_bar', array( __CLASS__, 'search_bar' ) );
		add_shortcode( 'cvp_apartment_card', array( __CLASS__, 'apartment_card' ) );
		add_shortcode( 'cvp_booking_form', array( __CLASS__, 'booking_form' ) );
		add_shortcode( 'cvp_search_results', array( __CLASS__, 'search_results' ) );
	}

	/**
	 * Shortcode barra ricerca.
	 *
	 * @param array $atts Attributi.
	 * @return string
	 */
	public static function search_bar( $atts ) {
		$atts = shortcode_atts(
			array(
				'results_page' => '',
			),
			$atts,
			'cvp_search_bar'
		);

		$check_in  = isset( $_GET['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_in'] ) ) : '';
		$check_out = isset( $_GET['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_out'] ) ) : '';
		$guests    = isset( $_GET['cvp_guests'] ) ? absint( $_GET['cvp_guests'] ) : 2;

		$action          = $atts['results_page'] ? get_permalink( absint( $atts['results_page'] ) ) : Settings::get_results_page_url();
		$form_id         = wp_unique_id( 'cvp_sb_' );
		$today           = current_time( 'Y-m-d' );
		$max_guests_limit = self::get_search_max_guests();

		ob_start();
		include CVP_PLUGIN_DIR . 'templates/search-bar.php';
		return ob_get_clean();
	}

	/**
	 * Shortcode card appartamento.
	 *
	 * @param array $atts Attributi.
	 * @return string
	 */
	public static function apartment_card( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'               => 0,
				'show_booking'     => 'yes',
				'check_in'         => '',
				'check_out'        => '',
				'guests'           => '',
			),
			$atts,
			'cvp_apartment_card'
		);

		$apartment_id = absint( $atts['id'] );
		if ( ! $apartment_id ) {
			$apartment_id = Apartment_Meta::resolve_apartment_id( 0 );
		}

		if ( ! $apartment_id ) {
			return '';
		}

		$apartment = get_post( $apartment_id );
		if ( ! $apartment || Post_Types::APPARTAMENTO !== $apartment->post_type ) {
			return '';
		}

		$check_in  = $atts['check_in'] ?: ( isset( $_GET['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_in'] ) ) : '' );
		$check_out = $atts['check_out'] ?: ( isset( $_GET['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_out'] ) ) : '' );
		$guests    = $atts['guests'] ? absint( $atts['guests'] ) : ( isset( $_GET['cvp_guests'] ) ? absint( $_GET['cvp_guests'] ) : 2 );
		$show_booking = 'yes' === $atts['show_booking'];

		ob_start();
		include CVP_PLUGIN_DIR . 'templates/apartment-card.php';
		return ob_get_clean();
	}

	/**
	 * Shortcode form prenotazione.
	 *
	 * @param array $atts Attributi.
	 * @return string
	 */
	public static function booking_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'apartment_id' => 0,
			),
			$atts,
			'cvp_booking_form'
		);

		$apartment_id = absint( $atts['apartment_id'] );
		if ( ! $apartment_id ) {
			$apartment_id = isset( $_GET['cvp_apartment'] ) ? absint( $_GET['cvp_apartment'] ) : 0;
		}
		if ( ! $apartment_id ) {
			$apartment_id = Apartment_Meta::resolve_apartment_id( 0 );
		}

		if ( ! $apartment_id || ! get_post( $apartment_id ) ) {
			return '<p class="cvp-notice">' . esc_html__( 'Seleziona un appartamento per prenotare.', 'casa-vacanza-prenotazioni' ) . '</p>';
		}

		$check_in  = isset( $_GET['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_in'] ) ) : '';
		$check_out = isset( $_GET['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_out'] ) ) : '';
		$guests    = isset( $_GET['cvp_guests'] ) ? absint( $_GET['cvp_guests'] ) : 2;

		ob_start();
		include CVP_PLUGIN_DIR . 'templates/booking-form.php';
		return ob_get_clean();
	}

	/**
	 * Shortcode risultati ricerca.
	 *
	 * @return string
	 */
	public static function search_results() {
		$check_in  = isset( $_GET['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_in'] ) ) : '';
		$check_out = isset( $_GET['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['cvp_check_out'] ) ) : '';
		$guests    = isset( $_GET['cvp_guests'] ) ? absint( $_GET['cvp_guests'] ) : 2;

		$apartments = array();
		$error      = '';

		if ( $check_in && $check_out ) {
			$validation = Availability::validate_dates( $check_in, $check_out );
			if ( is_wp_error( $validation ) ) {
				$error = $validation->get_error_message();
			} else {
				$apartments = Availability::search_available( $check_in, $check_out, $guests );
			}
		}

		ob_start();
		include CVP_PLUGIN_DIR . 'templates/search-results.php';
		return ob_get_clean();
	}

	/**
	 * Capienza massima per la barra ricerca (max tra tutti gli appartamenti pubblicati).
	 *
	 * @return int
	 */
	public static function get_search_max_guests() {
		$apartments = get_posts(
			array(
				'post_type'              => Post_Types::APPARTAMENTO,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
			)
		);

		$max = 2;
		foreach ( $apartments as $apartment_id ) {
			$guests = (int) get_post_meta( $apartment_id, Apartment_Meta::MAX_GUESTS, true );
			if ( $guests > $max ) {
				$max = $guests;
			}
		}

		return $max;
	}

	/**
	 * Dati appartamento per template.
	 *
	 * @param int $apartment_id ID appartamento.
	 * @return array
	 */
	public static function get_apartment_data( $apartment_id ) {
		$meta = Apartment_Meta::get_all( $apartment_id );

		$images = array();
		foreach ( $meta['gallery'] as $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'large' );
			if ( $url ) {
				$images[] = array(
					'id'    => $attachment_id,
					'url'   => $url,
					'thumb' => wp_get_attachment_image_url( $attachment_id, 'medium' ),
				);
			}
		}

		if ( empty( $images ) && has_post_thumbnail( $apartment_id ) ) {
			$images[] = array(
				'id'    => get_post_thumbnail_id( $apartment_id ),
				'url'   => get_the_post_thumbnail_url( $apartment_id, 'large' ),
				'thumb' => get_the_post_thumbnail_url( $apartment_id, 'medium' ),
			);
		}

		$excerpt = get_post_field( 'post_excerpt', $apartment_id );
		if ( ! $excerpt ) {
			$excerpt = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $apartment_id ) ), 30 );
		}

		return array_merge(
			$meta,
			array(
				'id'          => $apartment_id,
				'title'       => get_the_title( $apartment_id ),
				'description' => apply_filters( 'the_content', get_post_field( 'post_content', $apartment_id ) ),
				'excerpt'     => $excerpt,
				'images'      => $images,
				'permalink'   => Apartment_Meta::get_public_permalink( $apartment_id ),
			)
		);
	}
}
