<?php
/**
 * Calcolo prezzi unificato.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Pricing {

	/**
	 * Verifica che l'appartamento sia pubblicato e del tipo corretto.
	 *
	 * @param int $apartment_id ID appartamento.
	 * @return bool
	 */
	public static function is_valid_apartment( $apartment_id ) {
		$apartment_id = absint( $apartment_id );
		if ( ! $apartment_id ) {
			return false;
		}

		$post = get_post( $apartment_id );
		return $post
			&& Post_Types::APPARTAMENTO === $post->post_type
			&& 'publish' === $post->post_status;
	}

	/**
	 * Calcola il dettaglio prezzo per un soggiorno.
	 *
	 * @param int    $apartment_id ID appartamento.
	 * @param string $check_in     Check-in (Y-m-d).
	 * @param string $check_out    Check-out (Y-m-d).
	 * @return array{nights:int,price_night:float,cleaning_fee:float,subtotal:float,total:float}
	 */
	public static function calculate( $apartment_id, $check_in, $check_out ) {
		$nights      = Availability::count_nights( $check_in, $check_out );
		$price_night = (float) get_post_meta( $apartment_id, Apartment_Meta::PRICE, true );
		$cleaning    = (float) get_post_meta( $apartment_id, Apartment_Meta::CLEANING_FEE, true );
		$subtotal    = $nights * $price_night;
		$total       = $subtotal + $cleaning;

		return array(
			'nights'       => $nights,
			'price_night'  => $price_night,
			'cleaning_fee' => $cleaning,
			'subtotal'     => $subtotal,
			'total'        => $total,
		);
	}
}
