<?php
/**
 * Scadenza automatica richieste in attesa.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Booking_Expiry {

	const CRON_HOOK = 'cvp_expire_pending_bookings';

	/**
	 * Inizializza hook e cron.
	 */
	public static function init() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'expire_pending' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ) );
	}

	/**
	 * Pianifica il cron se non già attivo.
	 */
	public static function maybe_schedule_cron() {
		if ( self::get_hold_hours() <= 0 ) {
			self::clear_cron();
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Rimuove il cron pianificato.
	 */
	public static function clear_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Ore di blocco per richieste in attesa (0 = disabilitato).
	 *
	 * @return int
	 */
	public static function get_hold_hours() {
		$settings = Settings::get();
		return max( 0, absint( $settings['pending_hold_hours'] ) );
	}

	/**
	 * Salva scadenza su nuova prenotazione.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function set_expiry( $booking_id ) {
		$hours = self::get_hold_hours();
		if ( $hours <= 0 ) {
			delete_post_meta( $booking_id, '_cvp_expires_at' );
			return;
		}

		$expires = gmdate( 'Y-m-d H:i:s', time() + ( $hours * HOUR_IN_SECONDS ) );
		update_post_meta( $booking_id, '_cvp_expires_at', $expires );
	}

	/**
	 * Scade le richieste in attesa oltre il termine.
	 */
	public static function expire_pending() {
		$hours = self::get_hold_hours();
		if ( $hours <= 0 ) {
			return;
		}

		$now = current_time( 'mysql', true );

		$query = new \WP_Query(
			array(
				'post_type'      => Post_Types::PRENOTAZIONE,
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_cvp_status',
						'value' => Post_Types::STATUS_IN_ATTESA,
					),
					array(
						'key'     => '_cvp_expires_at',
						'value'   => $now,
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			)
		);

		$note = __( 'Scaduta automaticamente per mancata conferma entro il termine previsto.', 'casa-vacanza-prenotazioni' );

		foreach ( $query->posts as $booking_id ) {
			Booking::update_status( (int) $booking_id, Post_Types::STATUS_RIFIUTATA, $note );
		}
	}
}
