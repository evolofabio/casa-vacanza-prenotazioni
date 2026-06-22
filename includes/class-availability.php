<?php
/**
 * Logica disponibilità e calendario.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Availability {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		// Nessun hook frontend necessario qui.
	}

	/**
	 * Stati che bloccano il calendario.
	 *
	 * @return array
	 */
	public static function blocking_statuses() {
		return array(
			Post_Types::STATUS_IN_ATTESA,
			Post_Types::STATUS_CONFERMATA,
		);
	}

	/**
	 * Verifica se due intervalli di date si sovrappongono.
	 *
	 * @param string $in1  Check-in 1.
	 * @param string $out1 Check-out 1.
	 * @param string $in2  Check-in 2.
	 * @param string $out2 Check-out 2.
	 * @return bool
	 */
	public static function dates_overlap( $in1, $out1, $in2, $out2 ) {
		$start1 = strtotime( $in1 );
		$end1   = strtotime( $out1 );
		$start2 = strtotime( $in2 );
		$end2   = strtotime( $out2 );

		if ( ! $start1 || ! $end1 || ! $start2 || ! $end2 ) {
			return false;
		}

		return $start1 < $end2 && $start2 < $end1;
	}

	/**
	 * Verifica disponibilità appartamento per date.
	 *
	 * @param int    $apartment_id ID appartamento.
	 * @param string $check_in     Check-in Y-m-d.
	 * @param string $check_out    Check-out Y-m-d.
	 * @param int    $exclude_id   ID prenotazione da escludere.
	 * @return bool
	 */
	public static function is_available( $apartment_id, $check_in, $check_out, $exclude_id = 0 ) {
		$bookings = self::get_bookings_for_apartment( $apartment_id, self::blocking_statuses() );

		foreach ( $bookings as $booking ) {
			if ( $exclude_id && (int) $booking['id'] === (int) $exclude_id ) {
				continue;
			}

			if ( self::dates_overlap( $check_in, $check_out, $booking['check_in'], $booking['check_out'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Ottiene prenotazioni per appartamento.
	 *
	 * @param int   $apartment_id ID appartamento.
	 * @param array $statuses     Stati da includere.
	 * @return array
	 */
	public static function get_bookings_for_apartment( $apartment_id, $statuses = array() ) {
		$args = array(
			'post_type'      => Post_Types::PRENOTAZIONE,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_cvp_apartment_id',
					'value' => $apartment_id,
				),
			),
		);

		if ( ! empty( $statuses ) ) {
			$args['meta_query'][] = array(
				'key'     => '_cvp_status',
				'value'   => $statuses,
				'compare' => 'IN',
			);
		}

		$posts    = get_posts( $args );
		$bookings = array();
		$labels   = Post_Types::get_status_labels();

		foreach ( $posts as $post ) {
			$status = get_post_meta( $post->ID, '_cvp_status', true );
			$bookings[] = array(
				'id'           => $post->ID,
				'check_in'     => get_post_meta( $post->ID, '_cvp_check_in', true ),
				'check_out'    => get_post_meta( $post->ID, '_cvp_check_out', true ),
				'status'       => $status,
				'status_label' => isset( $labels[ $status ] ) ? $labels[ $status ] : $status,
				'guests'       => get_post_meta( $post->ID, '_cvp_guests', true ),
			);
		}

		return $bookings;
	}

	/**
	 * Date bloccate per sidebar admin.
	 *
	 * @param int $apartment_id ID appartamento.
	 * @return array
	 */
	public static function get_blocked_dates_for_apartment( $apartment_id ) {
		return self::get_bookings_for_apartment( $apartment_id, self::blocking_statuses() );
	}

	/**
	 * Cerca appartamenti disponibili.
	 *
	 * @param string $check_in  Check-in.
	 * @param string $check_out Check-out.
	 * @param int    $guests    Numero ospiti.
	 * @return array Array di WP_Post.
	 */
	public static function search_available( $check_in, $check_out, $guests = 1 ) {
		$apartments = get_posts(
			array(
				'post_type'      => Post_Types::APPARTAMENTO,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$available = array();

		foreach ( $apartments as $apartment ) {
			$max_guests = (int) get_post_meta( $apartment->ID, '_cvp_max_guests', true );

			if ( $max_guests > 0 && $guests > $max_guests ) {
				continue;
			}

			if ( self::is_available( $apartment->ID, $check_in, $check_out ) ) {
				$available[] = $apartment;
			}
		}

		return $available;
	}

	/**
	 * Calcola numero notti.
	 *
	 * @param string $check_in  Check-in.
	 * @param string $check_out Check-out.
	 * @return int
	 */
	public static function count_nights( $check_in, $check_out ) {
		$start = strtotime( $check_in );
		$end   = strtotime( $check_out );

		if ( ! $start || ! $end || $end <= $start ) {
			return 0;
		}

		return (int) round( ( $end - $start ) / DAY_IN_SECONDS );
	}

	/**
	 * Valida date ricerca/prenotazione.
	 *
	 * @param string $check_in  Check-in.
	 * @param string $check_out Check-out.
	 * @return true|\WP_Error
	 */
	public static function validate_dates( $check_in, $check_out ) {
		$settings = Settings::get();

		if ( empty( $check_in ) || empty( $check_out ) ) {
			return new \WP_Error( 'missing_dates', __( 'Inserisci le date di check-in e check-out.', 'casa-vacanza-prenotazioni' ) );
		}

		$start = strtotime( $check_in );
		$end   = strtotime( $check_out );
		$today = strtotime( 'today' );

		if ( ! $start || ! $end ) {
			return new \WP_Error( 'invalid_dates', __( 'Date non valide.', 'casa-vacanza-prenotazioni' ) );
		}

		if ( $start < $today ) {
			return new \WP_Error( 'past_date', __( 'Il check-in non può essere nel passato.', 'casa-vacanza-prenotazioni' ) );
		}

		if ( $end <= $start ) {
			return new \WP_Error( 'invalid_range', __( 'Il check-out deve essere successivo al check-in.', 'casa-vacanza-prenotazioni' ) );
		}

		$nights = self::count_nights( $check_in, $check_out );
		$min    = isset( $settings['min_nights'] ) ? (int) $settings['min_nights'] : 1;

		if ( $nights < $min ) {
			return new \WP_Error(
				'min_nights',
				sprintf(
					/* translators: %d: minimum nights */
					__( 'Soggiorno minimo di %d notti.', 'casa-vacanza-prenotazioni' ),
					$min
				)
			);
		}

		return true;
	}
}
