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
	const BEDS         = '_cvp_beds';
	const LOCATION     = '_cvp_location';
	const MIN_NIGHTS   = '_cvp_min_nights';
	const CLEANING_FEE = '_cvp_cleaning_fee';
	const SERVICES     = '_cvp_services';
	const GALLERY      = '_cvp_gallery';
	const LINKED_PAGE  = '_cvp_linked_page_id';
	const AVAILABLE_FROM = '_cvp_available_from';
	const AVAILABLE_TO   = '_cvp_available_to';
	const MANUAL_BLOCKS  = '_cvp_manual_blocks';

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ), 15 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_to_linked_page' ) );
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
			self::BEDS,
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

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::LINKED_PAGE,
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
			self::AVAILABLE_FROM,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::AVAILABLE_TO,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
			)
		);

		register_post_meta(
			Post_Types::APPARTAMENTO,
			self::MANUAL_BLOCKS,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
				'sanitize_callback' => array( __CLASS__, 'sanitize_manual_blocks' ),
			)
		);
	}

	/**
	 * Sanitizza data Y-m-d.
	 *
	 * @param mixed $value Valore.
	 * @return string
	 */
	public static function sanitize_date( $value ) {
		$value = sanitize_text_field( (string) $value );
		if ( ! $value ) {
			return '';
		}

		$time = strtotime( $value );
		return $time ? gmdate( 'Y-m-d', $time ) : '';
	}

	/**
	 * Sanitizza blocchi manuali calendario.
	 *
	 * @param mixed $value Valore grezzo.
	 * @return array
	 */
	public static function sanitize_manual_blocks( $value ) {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			$value   = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$blocks = array();
		foreach ( $value as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$check_in  = self::sanitize_date( isset( $block['check_in'] ) ? $block['check_in'] : '' );
			$check_out = self::sanitize_date( isset( $block['check_out'] ) ? $block['check_out'] : '' );

			if ( ! $check_in || ! $check_out || $check_out <= $check_in ) {
				continue;
			}

			$blocks[] = array(
				'check_in'  => $check_in,
				'check_out' => $check_out,
				'note'      => isset( $block['note'] ) ? sanitize_text_field( $block['note'] ) : '',
			);
		}

		return $blocks;
	}

	/**
	 * Salva blocchi manuali.
	 *
	 * @param int   $post_id ID appartamento.
	 * @param mixed $value   Blocchi grezzi.
	 */
	public static function save_manual_blocks( $post_id, $value ) {
		update_post_meta( $post_id, self::MANUAL_BLOCKS, self::sanitize_manual_blocks( $value ) );
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
			'beds'         => (int) get_post_meta( $post_id, self::BEDS, true ),
			'location'     => (string) get_post_meta( $post_id, self::LOCATION, true ),
			'min_nights'   => (int) get_post_meta( $post_id, self::MIN_NIGHTS, true ),
			'cleaning_fee' => $cleaning_fee,
			'cleaning_fmt' => $cleaning_fee ? Settings::format_price( $cleaning_fee ) : '',
			'services'     => $services,
			'gallery'      => array_map( 'absint', $gallery ),
			'linked_page'  => (int) get_post_meta( $post_id, self::LINKED_PAGE, true ),
			'available_from' => (string) get_post_meta( $post_id, self::AVAILABLE_FROM, true ),
			'available_to'   => (string) get_post_meta( $post_id, self::AVAILABLE_TO, true ),
			'manual_blocks'  => Availability::get_manual_blocks( $post_id ),
		);
	}

	/**
	 * Risolve l'ID appartamento dal contesto corrente o da un ID esplicito.
	 *
	 * @param int $explicit_id ID esplicito (shortcode/widget).
	 * @return int
	 */
	public static function resolve_apartment_id( $explicit_id = 0 ) {
		$explicit_id = absint( $explicit_id );
		if ( $explicit_id ) {
			return $explicit_id;
		}

		global $post;
		if ( ! $post ) {
			return 0;
		}

		if ( Post_Types::APPARTAMENTO === $post->post_type ) {
			return (int) $post->ID;
		}

		return self::get_apartment_id_by_page( $post->ID );
	}

	/**
	 * Trova l'appartamento collegato a una pagina.
	 *
	 * @param int $page_id ID pagina.
	 * @return int
	 */
	public static function get_apartment_id_by_page( $page_id ) {
		$page_id = absint( $page_id );
		if ( ! $page_id ) {
			return 0;
		}

		$apartments = get_posts(
			array(
				'post_type'              => Post_Types::APPARTAMENTO,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::LINKED_PAGE,
						'value' => $page_id,
					),
				),
			)
		);

		return ! empty( $apartments ) ? (int) $apartments[0] : 0;
	}

	/**
	 * Mappa pagine già collegate ad appartamenti.
	 *
	 * @return array<int, int> page_id => apartment_id
	 */
	public static function get_linked_pages_map() {
		$apartments = get_posts(
			array(
				'post_type'      => Post_Types::APPARTAMENTO,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$map = array();
		foreach ( $apartments as $apartment_id ) {
			$page_id = (int) get_post_meta( $apartment_id, self::LINKED_PAGE, true );
			if ( $page_id ) {
				$map[ $page_id ] = (int) $apartment_id;
			}
		}

		return $map;
	}

	/**
	 * Pagine disponibili per il collegamento.
	 *
	 * @param int $current_apartment_id Appartamento in modifica (0 in creazione).
	 * @return array<\WP_Post>
	 */
	public static function get_linkable_pages( $current_apartment_id = 0 ) {
		$linked_map = self::get_linked_pages_map();
		$pages      = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);

		$available = array();
		foreach ( $pages as $page ) {
			if ( ! isset( $linked_map[ $page->ID ] ) || (int) $linked_map[ $page->ID ] === (int) $current_apartment_id ) {
				$available[] = $page;
			}
		}

		return $available;
	}

	/**
	 * Pagine non ancora collegate a nessun appartamento.
	 *
	 * @return array<\WP_Post>
	 */
	public static function get_unlinked_pages() {
		$linked_map = self::get_linked_pages_map();
		$pages      = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);

		$unlinked = array();
		foreach ( $pages as $page ) {
			if ( ! isset( $linked_map[ $page->ID ] ) ) {
				$unlinked[] = $page;
			}
		}

		return $unlinked;
	}

	/**
	 * URL pubblico dell'appartamento (pagina collegata o permalink CPT).
	 *
	 * @param int $apartment_id ID appartamento.
	 * @return string
	 */
	public static function get_public_permalink( $apartment_id ) {
		$page_id = (int) get_post_meta( $apartment_id, self::LINKED_PAGE, true );
		if ( $page_id ) {
			$url = get_permalink( $page_id );
			if ( $url ) {
				return $url;
			}
		}

		return get_permalink( $apartment_id ) ?: '';
	}

	/**
	 * Collega una pagina a un appartamento (unicità garantita).
	 *
	 * @param int $apartment_id ID appartamento.
	 * @param int $page_id        ID pagina (0 per rimuovere).
	 * @return true|\WP_Error
	 */
	public static function assign_linked_page( $apartment_id, $page_id ) {
		$apartment_id = absint( $apartment_id );
		$page_id      = absint( $page_id );

		if ( ! $apartment_id || Post_Types::APPARTAMENTO !== get_post_type( $apartment_id ) ) {
			return new \WP_Error( 'invalid_apartment', __( 'Appartamento non valido.', 'casa-vacanza-prenotazioni' ) );
		}

		if ( ! $page_id ) {
			delete_post_meta( $apartment_id, self::LINKED_PAGE );
			return true;
		}

		$page = get_post( $page_id );
		if ( ! $page || 'page' !== $page->post_type ) {
			return new \WP_Error( 'invalid_page', __( 'Pagina non valida.', 'casa-vacanza-prenotazioni' ) );
		}

		$existing = self::get_apartment_id_by_page( $page_id );
		if ( $existing && $existing !== $apartment_id ) {
			return new \WP_Error(
				'page_in_use',
				sprintf(
					/* translators: %s: apartment title */
					__( 'Questa pagina è già collegata all\'appartamento "%s".', 'casa-vacanza-prenotazioni' ),
					get_the_title( $existing )
				)
			);
		}

		update_post_meta( $apartment_id, self::LINKED_PAGE, $page_id );
		return true;
	}

	/**
	 * Crea un appartamento da una pagina WordPress esistente.
	 *
	 * @param int $page_id ID pagina.
	 * @return int|\WP_Error ID appartamento creato.
	 */
	public static function create_from_page( $page_id ) {
		$page_id = absint( $page_id );
		$page    = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type ) {
			return new \WP_Error( 'invalid_page', __( 'Pagina non valida.', 'casa-vacanza-prenotazioni' ) );
		}

		$existing = self::get_apartment_id_by_page( $page_id );
		if ( $existing ) {
			return new \WP_Error(
				'already_linked',
				sprintf(
					/* translators: %s: apartment title */
					__( 'La pagina è già collegata all\'appartamento "%s".', 'casa-vacanza-prenotazioni' ),
					get_the_title( $existing )
				)
			);
		}

		$apartment_id = wp_insert_post(
			array(
				'post_type'    => Post_Types::APPARTAMENTO,
				'post_title'   => $page->post_title,
				'post_content' => $page->post_content,
				'post_excerpt' => $page->post_excerpt,
				'post_status'  => 'publish' === $page->post_status ? 'publish' : 'draft',
			),
			true
		);

		if ( is_wp_error( $apartment_id ) ) {
			return $apartment_id;
		}

		$thumb_id = get_post_thumbnail_id( $page_id );
		if ( $thumb_id ) {
			set_post_thumbnail( $apartment_id, $thumb_id );
		}

		update_post_meta( $apartment_id, self::LINKED_PAGE, $page_id );
		update_post_meta( $apartment_id, self::MAX_GUESTS, 2 );

		return $apartment_id;
	}

	/**
	 * Reindirizza il singolo CPT alla pagina collegata.
	 */
	public static function maybe_redirect_to_linked_page() {
		if ( ! is_singular( Post_Types::APPARTAMENTO ) ) {
			return;
		}

		$page_id = (int) get_post_meta( get_queried_object_id(), self::LINKED_PAGE, true );
		if ( ! $page_id ) {
			return;
		}

		$url = get_permalink( $page_id );
		if ( $url && get_queried_object_id() !== $page_id ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
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
			'cvp_beds'         => isset( $_POST['cvp_beds'] ) ? wp_unslash( $_POST['cvp_beds'] ) : null,
			'cvp_location'     => isset( $_POST['cvp_location'] ) ? wp_unslash( $_POST['cvp_location'] ) : null,
			'cvp_min_nights'   => isset( $_POST['cvp_min_nights'] ) ? wp_unslash( $_POST['cvp_min_nights'] ) : null,
			'cvp_cleaning_fee' => isset( $_POST['cvp_cleaning_fee'] ) ? wp_unslash( $_POST['cvp_cleaning_fee'] ) : null,
			'cvp_available_from' => isset( $_POST['cvp_available_from'] ) ? wp_unslash( $_POST['cvp_available_from'] ) : null,
			'cvp_available_to'   => isset( $_POST['cvp_available_to'] ) ? wp_unslash( $_POST['cvp_available_to'] ) : null,
			'cvp_services'     => isset( $_POST['cvp_services'] ) ? wp_unslash( $_POST['cvp_services'] ) : null,
			'cvp_gallery'      => isset( $_POST['cvp_gallery'] ) ? wp_unslash( $_POST['cvp_gallery'] ) : null,
			'cvp_linked_page_id' => isset( $_POST['cvp_linked_page_id'] ) ? wp_unslash( $_POST['cvp_linked_page_id'] ) : null,
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
			'cvp_beds'         => self::BEDS,
			'cvp_location'     => self::LOCATION,
			'cvp_min_nights'   => self::MIN_NIGHTS,
			'cvp_cleaning_fee' => self::CLEANING_FEE,
			'cvp_available_from' => self::AVAILABLE_FROM,
			'cvp_available_to'   => self::AVAILABLE_TO,
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
			} elseif ( in_array( $meta_key, array( self::AVAILABLE_FROM, self::AVAILABLE_TO, self::LOCATION ), true ) ) {
				$value = self::LOCATION === $meta_key ? sanitize_text_field( $value ) : self::sanitize_date( $value );
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

		if ( array_key_exists( 'cvp_linked_page_id', $settings ) && null !== $settings['cvp_linked_page_id'] ) {
			$result = self::assign_linked_page( $post_id, absint( $settings['cvp_linked_page_id'] ) );
			if ( is_wp_error( $result ) && is_admin() ) {
				set_transient( 'cvp_link_page_error_' . get_current_user_id(), $result->get_error_message(), 45 );
			}
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
