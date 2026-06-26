<?php
/**
 * Gestione prenotazioni.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Booking {

	/**
	 * Limite richieste AJAX per IP in 15 minuti.
	 */
	const RATE_LIMIT_MAX = 10;

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'wp_ajax_cvp_submit_booking', array( __CLASS__, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_nopriv_cvp_submit_booking', array( __CLASS__, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_cvp_update_booking_status', array( __CLASS__, 'ajax_update_status' ) );
	}

	/**
	 * Verifica che il post sia una prenotazione del plugin.
	 *
	 * @param int $post_id ID post.
	 * @return bool
	 */
	public static function is_booking( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		return $post && Post_Types::PRENOTAZIONE === $post->post_type;
	}

	/**
	 * Controlla rate limiting per IP.
	 *
	 * @return true|\WP_Error
	 */
	private static function check_rate_limit() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( ! $ip ) {
			return true;
		}

		$key   = 'cvp_rl_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT_MAX ) {
			return new \WP_Error(
				'rate_limit',
				__( 'Troppe richieste. Riprova tra qualche minuto.', 'casa-vacanza-prenotazioni' )
			);
		}

		set_transient( $key, $count + 1, 15 * MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Crea prenotazione da frontend.
	 *
	 * @param array $data Dati prenotazione.
	 * @return int|\WP_Error ID prenotazione.
	 */
	public static function create( $data ) {
		$apartment_id = isset( $data['apartment_id'] ) ? absint( $data['apartment_id'] ) : 0;
		$check_in     = isset( $data['check_in'] ) ? sanitize_text_field( $data['check_in'] ) : '';
		$check_out    = isset( $data['check_out'] ) ? sanitize_text_field( $data['check_out'] ) : '';
		$guests       = isset( $data['guests'] ) ? absint( $data['guests'] ) : 1;
		$name         = isset( $data['customer_name'] ) ? sanitize_text_field( $data['customer_name'] ) : '';
		$email        = isset( $data['customer_email'] ) ? sanitize_email( $data['customer_email'] ) : '';
		$phone        = isset( $data['customer_phone'] ) ? sanitize_text_field( $data['customer_phone'] ) : '';
		$note         = isset( $data['customer_note'] ) ? sanitize_textarea_field( $data['customer_note'] ) : '';

		if ( ! Pricing::is_valid_apartment( $apartment_id ) ) {
			return new \WP_Error( 'invalid_apartment', __( 'Appartamento non valido.', 'casa-vacanza-prenotazioni' ) );
		}

		$date_validation = Availability::validate_dates( $check_in, $check_out, $apartment_id );
		if ( is_wp_error( $date_validation ) ) {
			return $date_validation;
		}

		$max_guests = (int) get_post_meta( $apartment_id, Apartment_Meta::MAX_GUESTS, true );
		if ( $max_guests > 0 && $guests > $max_guests ) {
			return new \WP_Error(
				'too_many_guests',
				sprintf(
					/* translators: %d: max guests */
					__( 'Capienza massima: %d ospiti.', 'casa-vacanza-prenotazioni' ),
					$max_guests
				)
			);
		}

		if ( empty( $name ) || empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_customer', __( 'Inserisci nome e email validi.', 'casa-vacanza-prenotazioni' ) );
		}

		if ( ! Availability::is_available( $apartment_id, $check_in, $check_out ) ) {
			return new \WP_Error( 'not_available', __( 'Le date selezionate non sono più disponibili.', 'casa-vacanza-prenotazioni' ) );
		}

		$pricing = Pricing::calculate( $apartment_id, $check_in, $check_out );

		$title = sprintf(
			/* translators: 1: customer name, 2: apartment title */
			__( 'Prenotazione %1$s – %2$s', 'casa-vacanza-prenotazioni' ),
			$name,
			get_the_title( $apartment_id )
		);

		$booking_id = wp_insert_post(
			array(
				'post_type'   => Post_Types::PRENOTAZIONE,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $booking_id ) ) {
			return $booking_id;
		}

		// Ricontrollo dopo insert per evitare race condition su prenotazioni concorrenti.
		if ( ! Availability::is_available( $apartment_id, $check_in, $check_out, $booking_id ) ) {
			wp_delete_post( $booking_id, true );
			return new \WP_Error( 'not_available', __( 'Le date selezionate non sono più disponibili.', 'casa-vacanza-prenotazioni' ) );
		}

		update_post_meta( $booking_id, '_cvp_status', Post_Types::STATUS_IN_ATTESA );
		update_post_meta( $booking_id, '_cvp_apartment_id', $apartment_id );
		update_post_meta( $booking_id, '_cvp_check_in', $check_in );
		update_post_meta( $booking_id, '_cvp_check_out', $check_out );
		update_post_meta( $booking_id, '_cvp_guests', $guests );
		update_post_meta( $booking_id, '_cvp_customer_name', $name );
		update_post_meta( $booking_id, '_cvp_customer_email', $email );
		update_post_meta( $booking_id, '_cvp_customer_phone', $phone );
		update_post_meta( $booking_id, '_cvp_customer_note', $note );
		update_post_meta( $booking_id, '_cvp_total_price', $pricing['total'] );

		Emails::send_customer_request_received( $booking_id );
		Emails::send_operator_new_request( $booking_id );

		return $booking_id;
	}

	/**
	 * Aggiorna stato prenotazione.
	 *
	 * @param int    $booking_id ID prenotazione.
	 * @param string $new_status Nuovo stato.
	 * @param string $note       Motivazione opzionale.
	 * @return true|\WP_Error
	 */
	public static function update_status( $booking_id, $new_status, $note = '' ) {
		if ( ! self::is_booking( $booking_id ) ) {
			return new \WP_Error( 'invalid_booking', __( 'Prenotazione non valida.', 'casa-vacanza-prenotazioni' ) );
		}

		$labels = Post_Types::get_status_labels();

		if ( ! isset( $labels[ $new_status ] ) ) {
			return new \WP_Error( 'invalid_status', __( 'Stato non valido.', 'casa-vacanza-prenotazioni' ) );
		}

		$old_status = get_post_meta( $booking_id, '_cvp_status', true );

		if ( Post_Types::STATUS_CONFERMATA === $new_status ) {
			$apartment_id = (int) get_post_meta( $booking_id, '_cvp_apartment_id', true );
			$check_in     = get_post_meta( $booking_id, '_cvp_check_in', true );
			$check_out    = get_post_meta( $booking_id, '_cvp_check_out', true );

			if ( ! Availability::is_available( $apartment_id, $check_in, $check_out, $booking_id ) ) {
				return new \WP_Error( 'conflict', __( 'Conflitto con un\'altra prenotazione attiva.', 'casa-vacanza-prenotazioni' ) );
			}
		}

		update_post_meta( $booking_id, '_cvp_status', $new_status );

		if ( $note ) {
			update_post_meta( $booking_id, '_cvp_status_note', sanitize_textarea_field( $note ) );
		}

		self::handle_status_change( $booking_id, $old_status, $new_status );

		return true;
	}

	/**
	 * Gestisce cambio stato e invio email.
	 *
	 * @param int    $booking_id ID prenotazione.
	 * @param string $old_status Stato precedente.
	 * @param string $new_status Nuovo stato.
	 */
	public static function handle_status_change( $booking_id, $old_status, $new_status ) {
		if ( $old_status === $new_status ) {
			return;
		}

		switch ( $new_status ) {
			case Post_Types::STATUS_CONFERMATA:
				Emails::send_customer_confirmed( $booking_id );
				break;
			case Post_Types::STATUS_RIFIUTATA:
				Emails::send_customer_rejected( $booking_id );
				break;
			case Post_Types::STATUS_ANNULLATA:
				Emails::send_customer_cancelled( $booking_id );
				break;
		}
	}

	/**
	 * AJAX submit prenotazione frontend.
	 */
	public static function ajax_submit_booking() {
		check_ajax_referer( 'cvp_frontend', 'nonce' );

		// Honeypot anti-spam: campo nascosto che i bot compilano.
		if ( ! empty( $_POST['cvp_website'] ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'Richiesta inviata con successo! Riceverai una email di conferma.', 'casa-vacanza-prenotazioni' ),
				)
			);
		}

		$rate_check = self::check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			wp_send_json_error( array( 'message' => $rate_check->get_error_message() ) );
		}

		$result = self::create( $_POST );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Richiesta inviata con successo! Riceverai una email di conferma.', 'casa-vacanza-prenotazioni' ),
				'booking_id' => $result,
			)
		);
	}

	/**
	 * AJAX aggiornamento stato da dashboard.
	 */
	public static function ajax_update_status() {
		check_ajax_referer( 'cvp_admin', 'nonce' );

		if ( ! Roles::user_can_manage_bookings() ) {
			wp_send_json_error( array( 'message' => __( 'Permessi insufficienti.', 'casa-vacanza-prenotazioni' ) ) );
		}

		$booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$note       = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! self::is_booking( $booking_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Prenotazione non valida.', 'casa-vacanza-prenotazioni' ) ) );
		}

		$result = self::update_status( $booking_id, $status, $note );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Stato aggiornato.', 'casa-vacanza-prenotazioni' ) ) );
	}

	/**
	 * Conta prenotazioni per stato.
	 *
	 * @param string $status Stato.
	 * @return int
	 */
	public static function count_by_status( $status ) {
		$query = new \WP_Query(
			array(
				'post_type'      => Post_Types::PRENOTAZIONE,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_cvp_status',
						'value' => $status,
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * Prossime prenotazioni confermate.
	 *
	 * @param int $limit Limite risultati.
	 * @return array
	 */
	public static function get_upcoming_confirmed( $limit = 5 ) {
		$today = current_time( 'Y-m-d' );

		return get_posts(
			array(
				'post_type'      => Post_Types::PRENOTAZIONE,
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_cvp_status',
						'value' => Post_Types::STATUS_CONFERMATA,
					),
					array(
						'key'     => '_cvp_check_in',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
				'meta_key'       => '_cvp_check_in',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			)
		);
	}
}
