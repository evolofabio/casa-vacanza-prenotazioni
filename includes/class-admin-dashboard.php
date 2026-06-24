<?php
/**
 * Dashboard operatore in wp-admin.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Admin_Dashboard {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 9 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_post_cvp_link_pages', array( __CLASS__, 'handle_link_pages' ) );
	}

	/**
	 * Registra menu admin.
	 */
	public static function register_menu() {
		if ( ! Roles::user_can_view_dashboard() ) {
			return;
		}

		add_menu_page(
			__( 'Casa Vacanza', 'casa-vacanza-prenotazioni' ),
			__( 'Casa Vacanza', 'casa-vacanza-prenotazioni' ),
			'cvp_view_dashboard',
			'cvp-dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-building',
			26
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Dashboard', 'casa-vacanza-prenotazioni' ),
			__( 'Dashboard', 'casa-vacanza-prenotazioni' ),
			'cvp_view_dashboard',
			'cvp-dashboard',
			array( __CLASS__, 'render_dashboard' )
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Guida e Shortcode', 'casa-vacanza-prenotazioni' ),
			__( 'Guida e Shortcode', 'casa-vacanza-prenotazioni' ),
			'cvp_view_dashboard',
			'cvp-help',
			array( __CLASS__, 'render_help' )
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Prenotazioni', 'casa-vacanza-prenotazioni' ),
			__( 'Prenotazioni', 'casa-vacanza-prenotazioni' ),
			'cvp_manage_bookings',
			'cvp-bookings',
			array( __CLASS__, 'render_bookings' )
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Appartamenti', 'casa-vacanza-prenotazioni' ),
			__( 'Appartamenti', 'casa-vacanza-prenotazioni' ),
			Roles::get_apartments_menu_cap(),
			'edit.php?post_type=' . Post_Types::APPARTAMENTO
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Collega pagine', 'casa-vacanza-prenotazioni' ),
			__( 'Collega pagine', 'casa-vacanza-prenotazioni' ),
			Roles::get_apartments_menu_cap(),
			'cvp-link-pages',
			array( __CLASS__, 'render_link_pages' )
		);

		add_submenu_page(
			'cvp-dashboard',
			__( 'Impostazioni', 'casa-vacanza-prenotazioni' ),
			__( 'Impostazioni', 'casa-vacanza-prenotazioni' ),
			'manage_options',
			'cvp-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Script admin dashboard.
	 *
	 * @param string $hook Hook corrente.
	 */
	public static function enqueue_scripts( $hook ) {
		$cvp_pages = array(
			'toplevel_page_cvp-dashboard',
			'casa-vacanza_page_cvp-bookings',
			'casa-vacanza_page_cvp-help',
			'casa-vacanza_page_cvp-settings',
			'casa-vacanza_page_cvp-link-pages',
		);

		if ( ! in_array( $hook, $cvp_pages, true ) && strpos( $hook, 'cvp-' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'cvp-admin',
			CVP_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			CVP_VERSION
		);

		wp_enqueue_script(
			'cvp-admin',
			CVP_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			CVP_VERSION,
			true
		);

		wp_localize_script(
			'cvp-admin',
			'cvpAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cvp_admin' ),
				'i18n'    => array(
					'confirmReject'  => __( 'Motivazione del rifiuto (opzionale):', 'casa-vacanza-prenotazioni' ),
					'confirmCancel'  => __( 'Motivazione dell\'annullamento (opzionale):', 'casa-vacanza-prenotazioni' ),
					'error'          => __( 'Si è verificato un errore.', 'casa-vacanza-prenotazioni' ),
					'copied'         => __( 'Copiato!', 'casa-vacanza-prenotazioni' ),
				),
			)
		);
	}

	/**
	 * Render dashboard principale.
	 */
	public static function render_dashboard() {
		if ( ! Roles::user_can_view_dashboard() ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		$pending   = Booking::count_by_status( Post_Types::STATUS_IN_ATTESA );
		$confirmed = Booking::count_by_status( Post_Types::STATUS_CONFERMATA );
		$upcoming  = Booking::get_upcoming_confirmed( 5 );

		include CVP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render gestione prenotazioni.
	 */
	public static function render_bookings() {
		if ( ! Roles::user_can_manage_bookings() ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

		$args = array(
			'post_type'      => Post_Types::PRENOTAZIONE,
			'posts_per_page' => 50,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $status_filter ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_cvp_status',
					'value' => $status_filter,
				),
			);
		}

		$bookings = get_posts( $args );
		$labels   = Post_Types::get_status_labels();

		include CVP_PLUGIN_DIR . 'admin/views/bookings.php';
	}

	/**
	 * Render guida e shortcode.
	 */
	public static function render_help() {
		if ( ! Roles::user_can_view_dashboard() ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		include CVP_PLUGIN_DIR . 'admin/views/help.php';
	}

	/**
	 * Render impostazioni.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		$settings = Settings::get();
		$pages    = get_pages();
		$update   = GitHub_Updater::get_update_status( isset( $_GET['cvp_update_check'] ) );

		include CVP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render collegamento pagine esistenti.
	 */
	public static function render_link_pages() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		$unlinked_pages = Apartment_Meta::get_unlinked_pages();
		$linked_count   = count( Apartment_Meta::get_linked_pages_map() );
		$created        = isset( $_GET['cvp_created'] ) ? absint( $_GET['cvp_created'] ) : 0;
		$skipped        = isset( $_GET['cvp_skipped'] ) ? absint( $_GET['cvp_skipped'] ) : 0;

		include CVP_PLUGIN_DIR . 'admin/views/link-pages.php';
	}

	/**
	 * Crea appartamenti dalle pagine selezionate.
	 */
	public static function handle_link_pages() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Accesso negato.', 'casa-vacanza-prenotazioni' ) );
		}

		check_admin_referer( 'cvp_link_pages' );

		$page_ids = isset( $_POST['page_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['page_ids'] ) ) : array();
		$created  = 0;
		$skipped  = 0;

		foreach ( $page_ids as $page_id ) {
			if ( ! $page_id ) {
				continue;
			}

			$result = Apartment_Meta::create_from_page( $page_id );
			if ( is_wp_error( $result ) ) {
				++$skipped;
				continue;
			}

			++$created;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'cvp-link-pages',
					'cvp_created' => $created,
					'cvp_skipped' => $skipped,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
