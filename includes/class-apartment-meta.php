<?php
/**
 * Meta campi appartamento: registrazione, lettura e salvataggio.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Apartment_Meta {

	const PRICE        = '_cvp_price';
	const MAX_GUESTS   = '_cvp_max_guests';
	const BEDROOMS     = '_cvp_bedrooms';
	const BATHROOMS    = '_cvp_bathrooms';
	const LOCATION     = '_cvp_location';
	const MIN_NIGHTS   = '_cvp_min_nights';
	const CLEANING_FEE = '_cvp_cleaning_fee';
	const SERVICES     = '_cvp_services';
	const GALLERY      = '_cvp_gallery';

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 15 );
	}

	/**
	 * Registra meta per REST API e block editor.
	 */
	public static function register_meta() {
		$auth = static function () {
			return current_user_can( 'edit_posts' );
		};

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::PRICE,
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => static function ( $value ) {
					return max( 0, floatval( $value ) );
				},
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::MAX_GUESTS,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => static function ( $value ) {
					return max( 1, absint( $value ) );
				},
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::BEDROOMS,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'absint',
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::BATHROOMS,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'absint',
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::LOCATION,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::MIN_NIGHTS,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => static function ( $value ) {
					return max( 0, absint( $value ) );
				},
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::CLEANING_FEE,
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => static function ( $value ) {
					return max( 0, floatval( $value ) );
				},
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::SERVICES,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_services' ),
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::GALLERY,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'integer',
						),
					),
				),
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_gallery' ),
			)
		);
	}

	/**
	 * Sanitizza elenco servizi.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return array
	 */
	public static function sanitize_services( $value ) {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( "\n", $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $value )
			)
		);
	}

	/**
	 * Sanitizza ID galleria.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return array
	 */
	public static function sanitize_gallery( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				$ids[] = absint( $item['id'] );
			} else {
				$ids[] = absint( $item );
			}
		}

		return array_values( array_filter( $ids ) );
	}

	/**
	 * Dati meta per un appartamento.
	 *
	 * @param int $post_id ID post.
	 * @return array
	 */
	public static function get_all( $post_id ) {
		$services = get_post_meta( $post_id, self::SERVICES, true );
		if ( ! is_array( $services ) ) {
			$services = array();
		}

		$gallery = get_post_meta( $post_id, self::GALLERY, true );
		if ( ! is_array( $gallery ) ) {
			$gallery = array();
		}

		$price        = get_post_meta( $post_id, self::PRICE, true );
		$cleaning_fee = get_post_meta( $post_id, self::CLEANING_FEE, true );

		return array(
			'price'        => $price,
			'price_fmt'    => Settings::format_price( $price ),
			'max_guests'   => (int) get_post_meta( $post_id, self::MAX_GUESTS, true ),
			'bedrooms'     => (int) get_post_meta( $post_id, self::BEDROOMS, true ),
			'bathrooms'    => (int) get_post_meta( $post_id, self::BATHROOMS, true ),
			'location'     => (string) get_post_meta( $post_id, self::LOCATION, true ),
			'min_nights'   => (int) get_post_meta( $post_id, self::MIN_NIGHTS, true ),
			'cleaning_fee' => $cleaning_fee,
			'cleaning_fmt' => $cleaning_fee ? Settings::format_price( $cleaning_fee ) : '',
			'services'     => $services,
			'gallery'      => array_map( 'absint', $gallery ),
		);
	}

	/**
	 * Salva meta da richiesta admin classica.
	 *
	 * @param int $post_id ID post.
	 */
	public static function save_from_request( $post_id ) {
		$data = array(
			'cvp_price'        => isset( $_POST['cvp_price'] ) ? wp_unslash( $_POST['cvp_price'] ) : null,
			'cvp_max_guests'   => isset( $_POST['cvp_max_guests'] ) ? wp_unslash( $_POST['cvp_max_guests'] ) : null,
			'cvp_bedrooms'     => isset( $_POST['cvp_bedrooms'] ) ? wp_unslash( $_POST['cvp_bedrooms'] ) : null,
			'cvp_bathrooms'    => isset( $_POST['cvp_bathrooms'] ) ? wp_unslash( $_POST['cvp_bathrooms'] ) : null,
			'cvp_location'     => isset( $_POST['cvp_location'] ) ? wp_unslash( $_POST['cvp_location'] ) : null,
			'cvp_min_nights'   => isset( $_POST['cvp_min_nights'] ) ? wp_unslash( $_POST['cvp_min_nights'] ) : null,
			'cvp_cleaning_fee' => isset( $_POST['cvp_cleaning_fee'] ) ? wp_unslash( $_POST['cvp_cleaning_fee'] ) : null,
			'cvp_services'     => isset( $_POST['cvp_services'] ) ? wp_unslash( $_POST['cvp_services'] ) : null,
			'cvp_gallery'      => isset( $_POST['cvp_gallery'] ) ? wp_unslash( $_POST['cvp_gallery'] ) : null,
		);

		self::save_from_array( $post_id, $data );
	}

	/**
	 * Salva meta da array (Elementor o admin).
	 *
	 * @param int   $post_id  ID post.
	 * @param array $settings Impostazioni con chiavi cvp_*.
	 */
	public static function save_from_array( $post_id, $settings ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$map = array(
			'cvp_price'        => self::PRICE,
			'cvp_max_guests'   => self::MAX_GUESTS,
			'cvp_bedrooms'     => self::BEDROOMS,
			'cvp_bathrooms'    => self::BATHROOMS,
			'cvp_location'     => self::LOCATION,
			'cvp_min_nights'   => self::MIN_NIGHTS,
			'cvp_cleaning_fee' => self::CLEANING_FEE,
		);

		foreach ( $map as $key => $meta_key ) {
			if ( ! array_key_exists( $key, $settings ) || null === $settings[ $key ] ) {
				continue;
			}

			$value = $settings[ $key ];
			if ( in_array( $meta_key, array( self::PRICE, self::CLEANING_FEE ), true ) ) {
				$value = max( 0, floatval( $value ) );
			} elseif ( self::MAX_GUESTS === $meta_key ) {
				$value = max( 1, absint( $value ) );
			} elseif ( self::MIN_NIGHTS === $meta_key ) {
				$value = max( 0, absint( $value ) );
			} elseif ( self::LOCATION === $meta_key ) {
				$value = sanitize_text_field( $value );
			} else {
				$value = absint( $value );
			}

			update_post_meta( $post_id, $meta_key, $value );
		}

		if ( array_key_exists( 'cvp_services', $settings ) && null !== $settings['cvp_services'] ) {
			update_post_meta( $post_id, self::SERVICES, self::sanitize_services( $settings['cvp_services'] ) );
		}

		if ( array_key_exists( 'cvp_gallery', $settings ) && null !== $settings['cvp_gallery'] ) {
			update_post_meta( $post_id, self::GALLERY, self::sanitize_gallery( $settings['cvp_gallery'] ) );
		}
	}

	/**
	 * Galleria in formato controllo Elementor.
	 *
	 * @param int $post_id ID post.
	 * @return array
	 */
	public static function get_gallery_for_elementor( $post_id ) {
		$gallery = get_post_meta( $post_id, self::GALLERY, true );
		if ( ! is_array( $gallery ) ) {
			$gallery = array();
		}

		$items = array();
		foreach ( $gallery as $attachment_id ) {
			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				$items[] = array(
					'id'  => (int) $attachment_id,
					'url' => $url,
				);
			}
		}

		return $items;
	}

	/**
	 * Servizi come testo multilinea.
	 *
	 * @param int $post_id ID post.
	 * @return string
	 */
	public static function get_services_text( $post_id ) {
		$services = get_post_meta( $post_id, self::SERVICES, true );
		if ( ! is_array( $services ) ) {
			return '';
		}

		return implode( "\n", $services );
	}
}
