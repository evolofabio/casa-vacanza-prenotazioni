<?php
/**
 * GDPR: consenso, export e cancellazione dati personali.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Privacy {

	/**
	 * Inizializza hook privacy WordPress.
	 */
	public static function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
	}

	/**
	 * URL informativa privacy per il form.
	 *
	 * @return string
	 */
	public static function get_policy_url() {
		$url = get_privacy_policy_url();
		if ( $url ) {
			return $url;
		}

		$settings = Settings::get();
		$page_id  = isset( $settings['privacy_policy_page'] ) ? (int) $settings['privacy_policy_page'] : 0;

		if ( $page_id ) {
			$permalink = get_permalink( $page_id );
			if ( $permalink ) {
				return $permalink;
			}
		}

		return '';
	}

	/**
	 * Testo etichetta consenso per il form.
	 *
	 * @return string
	 */
	public static function get_consent_label() {
		$policy_url = self::get_policy_url();

		if ( $policy_url ) {
			return sprintf(
				/* translators: %s: privacy policy URL */
				__( 'Ho letto e accetto l\'<a href="%s" target="_blank" rel="noopener noreferrer">informativa sulla privacy</a> *', 'casa-vacanza-prenotazioni' ),
				esc_url( $policy_url )
			);
		}

		return __( 'Acconsento al trattamento dei miei dati personali per gestire la richiesta di prenotazione *', 'casa-vacanza-prenotazioni' );
	}

	/**
	 * Registra exporter dati personali.
	 *
	 * @param array $exporters Exporters registrati.
	 * @return array
	 */
	public static function register_exporter( $exporters ) {
		$exporters['casa-vacanza-prenotazioni'] = array(
			'exporter_friendly_name' => __( 'Casa Vacanza Prenotazioni', 'casa-vacanza-prenotazioni' ),
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Registra eraser dati personali.
	 *
	 * @param array $erasers Erasers registrati.
	 * @return array
	 */
	public static function register_eraser( $erasers ) {
		$erasers['casa-vacanza-prenotazioni'] = array(
			'eraser_friendly_name' => __( 'Casa Vacanza Prenotazioni', 'casa-vacanza-prenotazioni' ),
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Trova prenotazioni per email cliente.
	 *
	 * @param string $email Email.
	 * @param int    $page  Pagina (1-based).
	 * @return array{ids: int[], done: bool}
	 */
	private static function find_bookings_by_email( $email, $page = 1 ) {
		$email = sanitize_email( $email );
		$limit = 20;
		$page  = max( 1, absint( $page ) );

		$query = new \WP_Query(
			array(
				'post_type'      => Post_Types::PRENOTAZIONE,
				'posts_per_page' => $limit,
				'paged'          => $page,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_cvp_customer_email',
						'value' => $email,
					),
				),
			)
		);

		return array(
			'ids'  => array_map( 'intval', $query->posts ),
			'done' => ( $page >= (int) $query->max_num_pages ),
		);
	}

	/**
	 * Export dati personali per email.
	 *
	 * @param string $email_address Email.
	 * @param int    $page          Pagina.
	 * @return array
	 */
	public static function export_personal_data( $email_address, $page = 1 ) {
		$result = self::find_bookings_by_email( $email_address, $page );
		$data   = array();

		foreach ( $result['ids'] as $booking_id ) {
			$group = array(
				'group_id'          => 'cvp-booking-' . $booking_id,
				'group_label'       => __( 'Prenotazione Casa Vacanza', 'casa-vacanza-prenotazioni' ),
				'group_description' => sprintf(
					/* translators: %d: booking ID */
					__( 'Dati prenotazione #%d', 'casa-vacanza-prenotazioni' ),
					$booking_id
				),
				'items'             => self::get_booking_export_items( $booking_id ),
			);

			$data[] = $group;
		}

		return array(
			'data' => $data,
			'done' => $result['done'],
		);
	}

	/**
	 * Campi export per singola prenotazione.
	 *
	 * @param int $booking_id ID prenotazione.
	 * @return array
	 */
	private static function get_booking_export_items( $booking_id ) {
		$apartment_id = (int) get_post_meta( $booking_id, '_cvp_apartment_id', true );
		$status       = get_post_meta( $booking_id, '_cvp_status', true );
		$labels       = Post_Types::get_status_labels();

		$fields = array(
			array(
				'name'  => __( 'Nome', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_customer_name', true ),
			),
			array(
				'name'  => __( 'Email', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_customer_email', true ),
			),
			array(
				'name'  => __( 'Telefono', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_customer_phone', true ),
			),
			array(
				'name'  => __( 'Appartamento', 'casa-vacanza-prenotazioni' ),
				'value' => $apartment_id ? get_the_title( $apartment_id ) : '',
			),
			array(
				'name'  => __( 'Check-in', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_check_in', true ),
			),
			array(
				'name'  => __( 'Check-out', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_check_out', true ),
			),
			array(
				'name'  => __( 'Ospiti', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_guests', true ),
			),
			array(
				'name'  => __( 'Stato', 'casa-vacanza-prenotazioni' ),
				'value' => isset( $labels[ $status ] ) ? $labels[ $status ] : $status,
			),
			array(
				'name'  => __( 'Note', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_customer_note', true ),
			),
			array(
				'name'  => __( 'Consenso privacy', 'casa-vacanza-prenotazioni' ),
				'value' => get_post_meta( $booking_id, '_cvp_privacy_consent_at', true ),
			),
		);

		$items = array();
		foreach ( $fields as $field ) {
			if ( '' === (string) $field['value'] ) {
				continue;
			}
			$items[] = array(
				'name'  => $field['name'],
				'value' => $field['value'],
			);
		}

		return $items;
	}

	/**
	 * Cancella o anonimizza dati personali per email.
	 *
	 * @param string $email_address Email.
	 * @param int    $page          Pagina.
	 * @return array
	 */
	public static function erase_personal_data( $email_address, $page = 1 ) {
		$result  = self::find_bookings_by_email( $email_address, $page );
		$removed = array();
		$retained = array();
		$messages = array();

		foreach ( $result['ids'] as $booking_id ) {
			$status = get_post_meta( $booking_id, '_cvp_status', true );

			if ( Post_Types::STATUS_CONFERMATA === $status ) {
				self::anonymize_booking( $booking_id );
				$removed[] = (string) $booking_id;
				continue;
			}

			if ( wp_delete_post( $booking_id, true ) ) {
				$removed[] = (string) $booking_id;
			} else {
				$retained[] = (string) $booking_id;
			}
		}

		if ( ! empty( $removed ) ) {
			$messages[] = sprintf(
				/* translators: %s: booking IDs */
				__( 'Rimosse o anonimizzate prenotazioni: %s', 'casa-vacanza-prenotazioni' ),
				implode( ', ', $removed )
			);
		}

		return array(
			'items_removed'  => ! empty( $removed ),
			'items_retained' => ! empty( $retained ),
			'messages'       => $messages,
			'done'           => $result['done'],
		);
	}

	/**
	 * Anonimizza dati personali mantenendo la prenotazione confermata.
	 *
	 * @param int $booking_id ID prenotazione.
	 */
	private static function anonymize_booking( $booking_id ) {
		update_post_meta( $booking_id, '_cvp_customer_name', __( 'Utente anonimizzato', 'casa-vacanza-prenotazioni' ) );
		update_post_meta( $booking_id, '_cvp_customer_email', 'deleted-' . $booking_id . '@anonymous.local' );
		delete_post_meta( $booking_id, '_cvp_customer_phone' );
		delete_post_meta( $booking_id, '_cvp_customer_note' );
		delete_post_meta( $booking_id, '_cvp_privacy_consent_at' );
	}
}
