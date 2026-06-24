<?php
/**
 * Handler AJAX aggiuntivi.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'wp_ajax_cvp_calculate_price', array( __CLASS__, 'calculate_price' ) );
		add_action( 'wp_ajax_nopriv_cvp_calculate_price', array( __CLASS__, 'calculate_price' ) );
		add_action( 'wp_ajax_cvp_check_availability', array( __CLASS__, 'check_availability' ) );
		add_action( 'wp_ajax_nopriv_cvp_check_availability', array( __CLASS__, 'check_availability' ) );
	}

	/**
	 * Calcola prezzo totale per date.
	 */
	public static function calculate_price() {
		check_ajax_referer( 'cvp_frontend', 'nonce' );

		$apartment_id = isset( $_POST['apartment_id'] ) ? absint( $_POST['apartment_id'] ) : 0;
		$check_in     = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out    = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';

		$validation = Availability::validate_dates( $check_in, $check_out, $apartment_id );
		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
		}

		$nights      = Availability::count_nights( $check_in, $check_out );
		$price_night = (float) get_post_meta( $apartment_id, Apartment_Meta::PRICE, true );
		$cleaning    = (float) get_post_meta( $apartment_id, Apartment_Meta::CLEANING_FEE, true );
		$total       = ( $nights * $price_night ) + $cleaning;

		wp_send_json_success(
			array(
				'nights'      => $nights,
				'price_night' => Settings::format_price( $price_night ),
				'total'       => Settings::format_price( $total ),
				'total_raw'   => $total,
			)
		);
	}

	/**
	 * Verifica disponibilità date per appartamento.
	 */
	public static function check_availability() {
		check_ajax_referer( 'cvp_frontend', 'nonce' );

		$apartment_id = isset( $_POST['apartment_id'] ) ? absint( $_POST['apartment_id'] ) : 0;
		$check_in     = isset( $_POST['check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) ) : '';
		$check_out    = isset( $_POST['check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) ) : '';

		$validation = Availability::validate_dates( $check_in, $check_out, $apartment_id );
		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Date disponibili.', 'casa-vacanza-prenotazioni' ) ) );
	}
}
