<?php
/**
 * Custom Post Types: Appartamenti e Prenotazioni.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Post_Types {

	const APPARTAMENTO = 'cv_appartamento';
	const PRENOTAZIONE = 'cv_prenotazione';

	/**
	 * Stati prenotazione.
	 */
	const STATUS_IN_ATTESA  = 'in_attesa';
	const STATUS_CONFERMATA = 'confermata';
	const STATUS_RIFIUTATA  = 'rifiutata';
	const STATUS_ANNULLATA  = 'annullata';

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'manage_' . self::PRENOTAZIONE . '_posts_columns', array( __CLASS__, 'booking_columns' ) );
		add_action( 'manage_' . self::PRENOTAZIONE . '_posts_custom_column', array( __CLASS__, 'booking_column_content' ), 10, 2 );
		add_filter( 'manage_' . self::APPARTAMENTO . '_posts_columns', array( __CLASS__, 'apartment_columns' ) );
		add_action( 'manage_' . self::APPARTAMENTO . '_posts_custom_column', array( __CLASS__, 'apartment_column_content' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'apartment_quick_edit' ), 10, 2 );
		add_action( 'save_post_' . self::APPARTAMENTO, array( __CLASS__, 'save_apartment_quick_edit' ), 5, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_list_scripts' ) );
	}

	/**
	 * Registra i CPT.
	 */
	public static function register() {
		register_post_type(
			self::APPARTAMENTO,
			array(
				'labels'              => array(
					'name'               => __( 'Appartamenti', 'casa-vacanza-prenotazioni' ),
					'singular_name'      => __( 'Appartamento', 'casa-vacanza-prenotazioni' ),
					'add_new'            => __( 'Aggiungi Appartamento', 'casa-vacanza-prenotazioni' ),
					'add_new_item'       => __( 'Aggiungi Nuovo Appartamento', 'casa-vacanza-prenotazioni' ),
					'edit_item'          => __( 'Modifica Appartamento', 'casa-vacanza-prenotazioni' ),
					'new_item'           => __( 'Nuovo Appartamento', 'casa-vacanza-prenotazioni' ),
					'view_item'          => __( 'Visualizza Appartamento', 'casa-vacanza-prenotazioni' ),
					'search_items'       => __( 'Cerca Appartamenti', 'casa-vacanza-prenotazioni' ),
					'not_found'          => __( 'Nessun appartamento trovato', 'casa-vacanza-prenotazioni' ),
					'not_found_in_trash' => __( 'Nessun appartamento nel cestino', 'casa-vacanza-prenotazioni' ),
					'menu_name'          => __( 'Appartamenti', 'casa-vacanza-prenotazioni' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'appartamenti' ),
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'menu_icon'           => 'dashicons-building',
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);

		add_post_type_support( self::APPARTAMENTO, 'elementor' );

		register_post_type(
			self::PRENOTAZIONE,
			array(
				'labels'              => array(
					'name'               => __( 'Prenotazioni', 'casa-vacanza-prenotazioni' ),
					'singular_name'      => __( 'Prenotazione', 'casa-vacanza-prenotazioni' ),
					'add_new'            => __( 'Aggiungi Prenotazione', 'casa-vacanza-prenotazioni' ),
					'add_new_item'       => __( 'Aggiungi Nuova Prenotazione', 'casa-vacanza-prenotazioni' ),
					'edit_item'          => __( 'Modifica Prenotazione', 'casa-vacanza-prenotazioni' ),
					'new_item'           => __( 'Nuova Prenotazione', 'casa-vacanza-prenotazioni' ),
					'view_item'          => __( 'Visualizza Prenotazione', 'casa-vacanza-prenotazioni' ),
					'search_items'       => __( 'Cerca Prenotazioni', 'casa-vacanza-prenotazioni' ),
					'not_found'          => __( 'Nessuna prenotazione trovata', 'casa-vacanza-prenotazioni' ),
					'not_found_in_trash' => __( 'Nessuna prenotazione nel cestino', 'casa-vacanza-prenotazioni' ),
					'menu_name'          => __( 'Prenotazioni', 'casa-vacanza-prenotazioni' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Etichette stati prenotazione.
	 *
	 * @return array
	 */
	public static function get_status_labels() {
		return array(
			self::STATUS_IN_ATTESA  => __( 'In attesa', 'casa-vacanza-prenotazioni' ),
			self::STATUS_CONFERMATA => __( 'Confermata', 'casa-vacanza-prenotazioni' ),
			self::STATUS_RIFIUTATA  => __( 'Rifiutata', 'casa-vacanza-prenotazioni' ),
			self::STATUS_ANNULLATA  => __( 'Annullata', 'casa-vacanza-prenotazioni' ),
		);
	}

	/**
	 * Colonne lista prenotazioni.
	 *
	 * @param array $columns Colonne esistenti.
	 * @return array
	 */
	public static function booking_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['cvp_status']     = __( 'Stato', 'casa-vacanza-prenotazioni' );
				$new['cvp_apartment']  = __( 'Appartamento', 'casa-vacanza-prenotazioni' );
				$new['cvp_dates']      = __( 'Date', 'casa-vacanza-prenotazioni' );
				$new['cvp_guests']     = __( 'Ospiti', 'casa-vacanza-prenotazioni' );
				$new['cvp_customer'] = __( 'Cliente', 'casa-vacanza-prenotazioni' );
			}
		}
		return $new;
	}

	/**
	 * Contenuto colonne prenotazioni.
	 *
	 * @param string $column  Nome colonna.
	 * @param int    $post_id ID post.
	 */
	public static function booking_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'cvp_status':
				$status = get_post_meta( $post_id, '_cvp_status', true );
				$labels = self::get_status_labels();
				echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status );
				break;
			case 'cvp_apartment':
				$apt_id = (int) get_post_meta( $post_id, '_cvp_apartment_id', true );
				echo $apt_id ? esc_html( get_the_title( $apt_id ) ) : '—';
				break;
			case 'cvp_dates':
				$in  = get_post_meta( $post_id, '_cvp_check_in', true );
				$out = get_post_meta( $post_id, '_cvp_check_out', true );
				if ( $in && $out ) {
					echo esc_html( self::format_date( $in ) . ' → ' . self::format_date( $out ) );
				}
				break;
			case 'cvp_guests':
				echo esc_html( get_post_meta( $post_id, '_cvp_guests', true ) );
				break;
			case 'cvp_customer':
				$name  = get_post_meta( $post_id, '_cvp_customer_name', true );
				$email = get_post_meta( $post_id, '_cvp_customer_email', true );
				echo esc_html( $name );
				if ( $email ) {
					echo '<br><small>' . esc_html( $email ) . '</small>';
				}
				break;
		}
	}

	/**
	 * Colonne lista appartamenti.
	 *
	 * @param array $columns Colonne esistenti.
	 * @return array
	 */
	public static function apartment_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['cvp_price']  = __( 'Prezzo/notte', 'casa-vacanza-prenotazioni' );
				$new['cvp_guests'] = __( 'Ospiti', 'casa-vacanza-prenotazioni' );
				$new['cvp_beds']   = __( 'Posti letto', 'casa-vacanza-prenotazioni' );
				$new['cvp_page']   = __( 'Pagina', 'casa-vacanza-prenotazioni' );
			}
		}
		return $new;
	}

	/**
	 * Contenuto colonne appartamenti.
	 *
	 * @param string $column  Nome colonna.
	 * @param int    $post_id ID post.
	 */
	public static function apartment_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'cvp_price':
				$price = get_post_meta( $post_id, Apartment_Meta::PRICE, true );
				echo esc_html( Settings::format_price( $price ) );
				echo '<div class="hidden" id="cvp_inline_' . esc_attr( (string) $post_id ) . '">';
				echo '<span class="cvp-qe-price">' . esc_html( (string) $price ) . '</span>';
				echo '<span class="cvp-qe-guests">' . esc_html( (string) get_post_meta( $post_id, Apartment_Meta::MAX_GUESTS, true ) ) . '</span>';
				echo '<span class="cvp-qe-beds">' . esc_html( (string) get_post_meta( $post_id, Apartment_Meta::BEDS, true ) ) . '</span>';
				echo '</div>';
				break;
			case 'cvp_guests':
				echo esc_html( get_post_meta( $post_id, Apartment_Meta::MAX_GUESTS, true ) );
				break;
			case 'cvp_beds':
				echo esc_html( get_post_meta( $post_id, Apartment_Meta::BEDS, true ) );
				break;
			case 'cvp_page':
				$page_id = (int) get_post_meta( $post_id, Apartment_Meta::LINKED_PAGE, true );
				if ( $page_id ) {
					echo '<a href="' . esc_url( get_edit_post_link( $page_id ) ) . '">' . esc_html( get_the_title( $page_id ) ) . '</a>';
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Formatta data per visualizzazione.
	 *
	 * @param string $date Data Y-m-d.
	 * @return string
	 */
	public static function format_date( $date ) {
		$timestamp = strtotime( $date );
		return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $date;
	}

	/**
	 * Campi modifica rapida appartamenti.
	 *
	 * @param string $column_name Nome colonna.
	 * @param string $post_type   Tipo post.
	 */
	public static function apartment_quick_edit( $column_name, $post_type ) {
		if ( self::APPARTAMENTO !== $post_type || 'title' !== $column_name ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Prezzo/notte', 'casa-vacanza-prenotazioni' ); ?></span>
					<span class="input-text-wrap"><input type="number" step="0.01" min="0" name="cvp_price" class="cvp-qe-price" value="" /></span>
				</label>
				<label>
					<span class="title"><?php esc_html_e( 'Ospiti max', 'casa-vacanza-prenotazioni' ); ?></span>
					<span class="input-text-wrap"><input type="number" min="1" name="cvp_max_guests" class="cvp-qe-guests" value="" /></span>
				</label>
				<label>
					<span class="title"><?php esc_html_e( 'Posti letto', 'casa-vacanza-prenotazioni' ); ?></span>
					<span class="input-text-wrap"><input type="number" min="0" name="cvp_beds" class="cvp-qe-beds" value="" /></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Salva modifica rapida appartamenti.
	 *
	 * @param int      $post_id ID post.
	 * @param \WP_Post $post    Post.
	 */
	public static function save_apartment_quick_edit( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['cvp_price'] ) ) {
			update_post_meta( $post_id, Apartment_Meta::PRICE, max( 0, floatval( wp_unslash( $_POST['cvp_price'] ) ) ) );
		}
		if ( isset( $_POST['cvp_max_guests'] ) ) {
			update_post_meta( $post_id, Apartment_Meta::MAX_GUESTS, max( 1, absint( wp_unslash( $_POST['cvp_max_guests'] ) ) ) );
		}
		if ( isset( $_POST['cvp_beds'] ) ) {
			update_post_meta( $post_id, Apartment_Meta::BEDS, absint( wp_unslash( $_POST['cvp_beds'] ) ) );
		}
	}

	/**
	 * Script lista appartamenti (modifica rapida).
	 *
	 * @param string $hook Hook corrente.
	 */
	public static function enqueue_list_scripts( $hook ) {
		global $post_type;

		if ( 'edit.php' !== $hook || self::APPARTAMENTO !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'cvp-admin-quick-edit',
			CVP_PLUGIN_URL . 'admin/js/quick-edit.js',
			array( 'jquery', 'inline-edit-post' ),
			CVP_VERSION,
			true
		);
	}
}
