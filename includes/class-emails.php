<?php
/**
 * Invio email automatiche.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Emails {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		// Nessun filtro globale su wp_mail: il mittente è impostato solo nelle email del plugin.
	}

	/**
	 * Dati prenotazione per template email.
	 *
	 * @param int $booking_id ID prenotazione.
	 * @return array
	 */
	public static function get_booking_data( $booking_id ) {
		$apartment_id = (int) get_post_meta( $booking_id, '_cvp_apartment_id', true );

		return array(
			'booking_id'      => $booking_id,
			'customer_name'   => get_post_meta( $booking_id, '_cvp_customer_name', true ),
			'customer_email'  => get_post_meta( $booking_id, '_cvp_customer_email', true ),
			'customer_phone'  => get_post_meta( $booking_id, '_cvp_customer_phone', true ),
			'check_in'        => get_post_meta( $booking_id, '_cvp_check_in', true ),
			'check_out'       => get_post_meta( $booking_id, '_cvp_check_out', true ),
			'check_in_fmt'    => Post_Types::format_date( get_post_meta( $booking_id, '_cvp_check_in', true ) ),
			'check_out_fmt'   => Post_Types::format_date( get_post_meta( $booking_id, '_cvp_check_out', true ) ),
			'guests'          => get_post_meta( $booking_id, '_cvp_guests', true ),
			'total_price'     => get_post_meta( $booking_id, '_cvp_total_price', true ),
			'total_price_fmt' => Settings::format_price( get_post_meta( $booking_id, '_cvp_total_price', true ) ),
			'apartment'       => get_the_title( $apartment_id ),
			'status_note'     => get_post_meta( $booking_id, '_cvp_status_note', true ),
		);
	}

	/**
	 * Sostituisce placeholder nel testo.
	 *
	 * @param string $template Template.
	 * @param array  $data     Dati.
	 * @return string
	 */
	public static function replace_placeholders( $template, $data ) {
		$replacements = array(
			'{nome}'          => isset( $data['customer_name'] ) ? $data['customer_name'] : '',
			'{email}'         => isset( $data['customer_email'] ) ? $data['customer_email'] : '',
			'{appartamento}'  => isset( $data['apartment'] ) ? $data['apartment'] : '',
			'{check_in}'      => isset( $data['check_in_fmt'] ) ? $data['check_in_fmt'] : '',
			'{check_out}'     => isset( $data['check_out_fmt'] ) ? $data['check_out_fmt'] : '',
			'{ospiti}'        => isset( $data['guests'] ) ? $data['guests'] : '',
			'{totale}'        => isset( $data['total_price_fmt'] ) ? $data['total_price_fmt'] : '',
			'{motivazione}'   => isset( $data['status_note'] ) ? $data['status_note'] : '',
			'{sito}'          => get_bloginfo( 'name' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Invia email.
	 *
	 * @param string $to      Destinatario.
	 * @param string $subject Oggetto.
	 * @param string $body    Corpo.
	 * @return bool
	 */
	public static function send( $to, $subject, $body ) {
		$settings = Settings::get();
		$headers  = array( 'Content-Type: text/plain; charset=UTF-8' );

		if ( ! empty( $settings['from_email'] ) && is_email( $settings['from_email'] ) ) {
			$from_name = ! empty( $settings['from_name'] ) ? $settings['from_name'] : get_bloginfo( 'name' );
			$headers[] = sprintf( 'From: %s <%s>', $from_name, $settings['from_email'] );
		}

		return wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Email cliente: richiesta ricevuta.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function send_customer_request_received( $booking_id ) {
		$settings = Settings::get();
		$data     = self::get_booking_data( $booking_id );

		$subject = self::replace_placeholders(
			isset( $settings['email_customer_request_subject'] ) ? $settings['email_customer_request_subject'] : __( 'Richiesta prenotazione ricevuta', 'casa-vacanza-prenotazioni' ),
			$data
		);

		$body = self::replace_placeholders(
			isset( $settings['email_customer_request_body'] ) ? $settings['email_customer_request_body'] : self::default_customer_request_body(),
			$data
		);

		self::send( $data['customer_email'], $subject, $body );
	}

	/**
	 * Email operatore: nuova richiesta.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function send_operator_new_request( $booking_id ) {
		$settings = Settings::get();
		$data     = self::get_booking_data( $booking_id );
		$to       = isset( $settings['operator_email'] ) ? $settings['operator_email'] : get_option( 'admin_email' );

		$subject = self::replace_placeholders(
			isset( $settings['email_operator_new_subject'] ) ? $settings['email_operator_new_subject'] : __( 'Nuova richiesta di prenotazione', 'casa-vacanza-prenotazioni' ),
			$data
		);

		$body = self::replace_placeholders(
			isset( $settings['email_operator_new_body'] ) ? $settings['email_operator_new_body'] : self::default_operator_new_body(),
			$data
		);

		self::send( $to, $subject, $body );
	}

	/**
	 * Email cliente: prenotazione confermata.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function send_customer_confirmed( $booking_id ) {
		$settings = Settings::get();
		$data     = self::get_booking_data( $booking_id );

		$subject = self::replace_placeholders(
			isset( $settings['email_customer_confirmed_subject'] ) ? $settings['email_customer_confirmed_subject'] : __( 'Prenotazione confermata', 'casa-vacanza-prenotazioni' ),
			$data
		);

		$body = self::replace_placeholders(
			isset( $settings['email_customer_confirmed_body'] ) ? $settings['email_customer_confirmed_body'] : self::default_customer_confirmed_body(),
			$data
		);

		self::send( $data['customer_email'], $subject, $body );
	}

	/**
	 * Email cliente: prenotazione rifiutata.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function send_customer_rejected( $booking_id ) {
		$settings = Settings::get();
		$data     = self::get_booking_data( $booking_id );

		$subject = self::replace_placeholders(
			isset( $settings['email_customer_rejected_subject'] ) ? $settings['email_customer_rejected_subject'] : __( 'Prenotazione non disponibile', 'casa-vacanza-prenotazioni' ),
			$data
		);

		$body = self::replace_placeholders(
			isset( $settings['email_customer_rejected_body'] ) ? $settings['email_customer_rejected_body'] : self::default_customer_rejected_body(),
			$data
		);

		self::send( $data['customer_email'], $subject, $body );
	}

	/**
	 * Email cliente: prenotazione annullata.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	public static function send_customer_cancelled( $booking_id ) {
		$settings = Settings::get();
		$data     = self::get_booking_data( $booking_id );

		$subject = self::replace_placeholders(
			isset( $settings['email_customer_cancelled_subject'] ) ? $settings['email_customer_cancelled_subject'] : __( 'Prenotazione annullata', 'casa-vacanza-prenotazioni' ),
			$data
		);

		$body = self::replace_placeholders(
			isset( $settings['email_customer_cancelled_body'] ) ? $settings['email_customer_cancelled_body'] : self::default_customer_cancelled_body(),
			$data
		);

		self::send( $data['customer_email'], $subject, $body );
	}

	/**
	 * Template default richiesta ricevuta.
	 *
	 * @return string
	 */
	public static function default_customer_request_body() {
		return __( "Gentile {nome},\n\nabbiamo ricevuto la tua richiesta di prenotazione per {appartamento} dal {check_in} al {check_out} per {ospiti} ospiti.\n\nTi contatteremo a breve con la conferma.\n\nCordiali saluti,\n{sito}", 'casa-vacanza-prenotazioni' );
	}

	/**
	 * Template default nuova richiesta operatore.
	 *
	 * @return string
	 */
	public static function default_operator_new_body() {
		return __( "Nuova richiesta di prenotazione:\n\nCliente: {nome} ({email})\nAppartamento: {appartamento}\nDate: {check_in} – {check_out}\nOspiti: {ospiti}\nTotale stimato: {totale}", 'casa-vacanza-prenotazioni' );
	}

	/**
	 * Template default confermata.
	 *
	 * @return string
	 */
	public static function default_customer_confirmed_body() {
		return __( "Gentile {nome},\n\nla tua prenotazione per {appartamento} dal {check_in} al {check_out} è stata confermata.\n\nTotale: {totale}\n\nA presto!\n{sito}", 'casa-vacanza-prenotazioni' );
	}

	/**
	 * Template default rifiutata.
	 *
	 * @return string
	 */
	public static function default_customer_rejected_body() {
		return __( "Gentile {nome},\n\npurtroppo non possiamo confermare la tua prenotazione per {appartamento} nelle date richieste.\n\nMotivazione: {motivazione}\n\nCordiali saluti,\n{sito}", 'casa-vacanza-prenotazioni' );
	}

	/**
	 * Template default annullata.
	 *
	 * @return string
	 */
	public static function default_customer_cancelled_body() {
		return __( "Gentile {nome},\n\nla tua prenotazione per {appartamento} dal {check_in} al {check_out} è stata annullata.\n\nMotivazione: {motivazione}\n\nCordiali saluti,\n{sito}", 'casa-vacanza-prenotazioni' );
	}
}
