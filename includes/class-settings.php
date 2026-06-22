<?php
/**
 * Impostazioni plugin.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Settings {

	const OPTION_KEY = 'cvp_settings';

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Ottiene impostazioni con default.
	 *
	 * @return array
	 */
	public static function get() {
		$defaults = array(
			'currency'                        => 'EUR',
			'currency_symbol'                 => '€',
			'currency_position'               => 'after',
			'min_nights'                      => 2,
			'from_email'                      => get_option( 'admin_email' ),
			'from_name'                       => get_bloginfo( 'name' ),
			'operator_email'                  => get_option( 'admin_email' ),
			'cvp_risultati_page'              => 0,
			'email_customer_request_subject'  => __( 'Richiesta prenotazione ricevuta', 'casa-vacanza-prenotazioni' ),
			'email_customer_request_body'     => "Gentile {nome},\n\nabbiamo ricevuto la tua richiesta di prenotazione per {appartamento} dal {check_in} al {check_out} per {ospiti} ospiti.\n\nTi contatteremo a breve con la conferma.\n\nCordiali saluti,\n{sito}",
			'email_operator_new_subject'      => __( 'Nuova richiesta di prenotazione', 'casa-vacanza-prenotazioni' ),
			'email_operator_new_body'         => "Nuova richiesta di prenotazione:\n\nCliente: {nome} ({email})\nAppartamento: {appartamento}\nDate: {check_in} – {check_out}\nOspiti: {ospiti}\nTotale stimato: {totale}",
			'email_customer_confirmed_subject'=> __( 'Prenotazione confermata', 'casa-vacanza-prenotazioni' ),
			'email_customer_confirmed_body'   => "Gentile {nome},\n\nla tua prenotazione per {appartamento} dal {check_in} al {check_out} è stata confermata.\n\nTotale: {totale}\n\nA presto!\n{sito}",
			'email_customer_rejected_subject' => __( 'Prenotazione non disponibile', 'casa-vacanza-prenotazioni' ),
			'email_customer_rejected_body'    => "Gentile {nome},\n\npurtroppo non possiamo confermare la tua prenotazione per {appartamento} nelle date richieste.\n\nMotivazione: {motivazione}\n\nCordiali saluti,\n{sito}",
			'email_customer_cancelled_subject'=> __( 'Prenotazione annullata', 'casa-vacanza-prenotazioni' ),
			'email_customer_cancelled_body'   => "Gentile {nome},\n\nla tua prenotazione per {appartamento} dal {check_in} al {check_out} è stata annullata.\n\nMotivazione: {motivazione}\n\nCordiali saluti,\n{sito}",
		);

		$settings = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Registra impostazioni.
	 */
	public static function register_settings() {
		register_setting( 'cvp_settings_group', self::OPTION_KEY, array( __CLASS__, 'sanitize' ) );
	}

	/**
	 * Sanitizza impostazioni.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$output = self::get();

		$text_fields = array(
			'currency',
			'currency_symbol',
			'currency_position',
			'from_name',
			'email_customer_request_subject',
			'email_operator_new_subject',
			'email_customer_confirmed_subject',
			'email_customer_rejected_subject',
			'email_customer_cancelled_subject',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$output[ $field ] = sanitize_text_field( $input[ $field ] );
			}
		}

		$email_fields = array( 'from_email', 'operator_email' );
		foreach ( $email_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$output[ $field ] = sanitize_email( $input[ $field ] );
			}
		}

		$textarea_fields = array(
			'email_customer_request_body',
			'email_operator_new_body',
			'email_customer_confirmed_body',
			'email_customer_rejected_body',
			'email_customer_cancelled_body',
		);

		foreach ( $textarea_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$output[ $field ] = sanitize_textarea_field( $input[ $field ] );
			}
		}

		if ( isset( $input['min_nights'] ) ) {
			$output['min_nights'] = max( 1, absint( $input['min_nights'] ) );
		}

		if ( isset( $input['cvp_risultati_page'] ) ) {
			$output['cvp_risultati_page'] = absint( $input['cvp_risultati_page'] );
		}

		return $output;
	}

	/**
	 * Formatta prezzo.
	 *
	 * @param float $price Prezzo.
	 * @return string
	 */
	public static function format_price( $price ) {
		$settings = self::get();
		$symbol   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '€';
		$formatted = number_format_i18n( (float) $price, 2 );

		if ( 'before' === $settings['currency_position'] ) {
			return $symbol . ' ' . $formatted;
		}

		return $formatted . ' ' . $symbol;
	}

	/**
	 * URL pagina risultati ricerca.
	 *
	 * @return string
	 */
	public static function get_results_page_url() {
		$settings = self::get();
		$page_id  = isset( $settings['cvp_risultati_page'] ) ? (int) $settings['cvp_risultati_page'] : 0;

		if ( $page_id ) {
			return get_permalink( $page_id );
		}

		return home_url( '/risultati-appartamenti/' );
	}
}
