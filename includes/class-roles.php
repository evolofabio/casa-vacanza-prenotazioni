<?php
/**
 * Ruolo Gestore Prenotazioni e capability.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Roles {

	const ROLE = 'cvp_gestore';

	/**
	 * Evita ricorsione infinita in map_meta_cap.
	 *
	 * @var bool
	 */
	private static $mapping_caps = false;

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_add_caps_to_admin' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_repair_caps' ) );
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
		add_action( 'admin_menu', array( __CLASS__, 'restrict_gestore_menus' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'block_gestore_unauthorized_screens' ) );
	}

	/**
	 * Ripara capability dopo aggiornamenti plugin.
	 */
	public static function maybe_repair_caps() {
		$stored = get_option( 'cvp_caps_version', '' );
		if ( $stored === CVP_VERSION ) {
			return;
		}

		self::create_role();
		update_option( 'cvp_caps_version', CVP_VERSION );
	}

	/**
	 * Capability per il ruolo Gestore Prenotazioni.
	 *
	 * @return array
	 */
	public static function get_gestore_capabilities() {
		return array(
			'read'                   => true,
			'upload_files'           => true,
			'edit_posts'             => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'edit_published_posts'   => true,
			'delete_published_posts' => true,
			'cvp_view_dashboard'     => true,
			'cvp_manage_bookings'    => true,
			'cvp_manage_apartments'  => true,
		);
	}

	/**
	 * Capability plugin interne (admin).
	 *
	 * @return array
	 */
	public static function get_plugin_capabilities() {
		return array(
			'cvp_view_dashboard',
			'cvp_manage_bookings',
			'cvp_manage_apartments',
		);
	}

	/**
	 * Assegna capability plugin a un ruolo.
	 *
	 * @param \WP_Role $role Ruolo WordPress.
	 */
	public static function assign_plugin_caps( $role ) {
		if ( ! $role ) {
			return;
		}

		foreach ( self::get_plugin_capabilities() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Verifica capability senza richiamare user_can (evita loop in map_meta_cap).
	 *
	 * @param int    $user_id ID utente.
	 * @param string $cap     Capability.
	 * @return bool
	 */
	private static function user_has_cap_direct( $user_id, $cap ) {
		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->allcaps ) ) {
			return false;
		}

		return ! empty( $user->allcaps[ $cap ] );
	}

	/**
	 * Limita il gestore ai soli CPT del plugin.
	 *
	 * @param array  $caps    Capability richieste.
	 * @param string $cap     Capability.
	 * @param int    $user_id ID utente.
	 * @param array  $args    Argomenti.
	 * @return array
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( self::$mapping_caps ) {
			return $caps;
		}

		self::$mapping_caps = true;

		if ( self::user_has_cap_direct( $user_id, 'manage_options' ) ) {
			self::$mapping_caps = false;
			return $caps;
		}

		if ( ! self::user_has_cap_direct( $user_id, 'cvp_view_dashboard' ) ) {
			self::$mapping_caps = false;
			return $caps;
		}

		$plugin_types = array( Post_Types::APPARTAMENTO, Post_Types::PRENOTAZIONE );
		$post_id      = isset( $args[0] ) ? (int) $args[0] : 0;
		$post         = $post_id ? get_post( $post_id ) : null;

		if ( $post && ! in_array( $post->post_type, $plugin_types, true ) ) {
			self::$mapping_caps = false;
			return array( 'do_not_allow' );
		}

		if ( in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) && $post ) {
			if ( Post_Types::APPARTAMENTO === $post->post_type && self::user_has_cap_direct( $user_id, 'cvp_manage_apartments' ) ) {
				self::$mapping_caps = false;
				return array( 'edit_posts' );
			}
			if ( Post_Types::PRENOTAZIONE === $post->post_type && self::user_has_cap_direct( $user_id, 'cvp_manage_bookings' ) ) {
				self::$mapping_caps = false;
				return array( 'edit_posts' );
			}
		}

		self::$mapping_caps = false;
		return $caps;
	}

	/**
	 * Crea ruolo alla attivazione.
	 */
	public static function create_role() {
		$gestore = get_role( self::ROLE );

		if ( ! $gestore ) {
			$gestore = add_role(
				self::ROLE,
				__( 'Gestore Prenotazioni', 'casa-vacanza-prenotazioni' ),
				self::get_gestore_capabilities()
			);
		} else {
			foreach ( self::get_gestore_capabilities() as $cap => $grant ) {
				if ( $grant ) {
					$gestore->add_cap( $cap );
				}
			}
			$gestore->remove_cap( 'edit_others_posts' );
			$gestore->remove_cap( 'delete_others_posts' );
		}

		self::maybe_add_caps_to_admin();
	}

	/**
	 * Aggiunge capability plugin agli admin.
	 */
	public static function maybe_add_caps_to_admin() {
		self::assign_plugin_caps( get_role( 'administrator' ) );
	}

	/**
	 * Verifica se utente può gestire prenotazioni.
	 *
	 * @return bool
	 */
	public static function user_can_manage_bookings() {
		return current_user_can( 'cvp_manage_bookings' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Verifica se utente può gestire appartamenti.
	 *
	 * @return bool
	 */
	public static function user_can_manage_apartments() {
		return current_user_can( 'edit_posts' ) || current_user_can( 'cvp_manage_apartments' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Verifica accesso dashboard.
	 *
	 * @return bool
	 */
	public static function user_can_view_dashboard() {
		return current_user_can( 'cvp_view_dashboard' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Capability richiesta per il menu Appartamenti.
	 *
	 * @return string
	 */
	public static function get_apartments_menu_cap() {
		return 'edit_posts';
	}

	/**
	 * Verifica se l'utente corrente è un gestore (non admin).
	 *
	 * @return bool
	 */
	private static function is_gestore_only() {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}

		return current_user_can( 'cvp_view_dashboard' );
	}

	/**
	 * Nasconde menu non pertinenti al gestore prenotazioni.
	 */
	public static function restrict_gestore_menus() {
		if ( ! self::is_gestore_only() ) {
			return;
		}

		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'options-general.php' );
	}

	/**
	 * Impedisce al gestore di creare o modificare contenuti non del plugin.
	 */
	public static function block_gestore_unauthorized_screens() {
		if ( ! self::is_gestore_only() ) {
			return;
		}

		global $pagenow;

		$allowed_types = array( Post_Types::APPARTAMENTO, Post_Types::PRENOTAZIONE );

		if ( 'post-new.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
			if ( ! in_array( $post_type, $allowed_types, true ) ) {
				wp_die( esc_html__( 'Non hai i permessi per creare questo contenuto.', 'casa-vacanza-prenotazioni' ) );
			}
		}

		if ( 'edit.php' === $pagenow ) {
			$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
			if ( ! in_array( $post_type, $allowed_types, true ) ) {
				wp_die( esc_html__( 'Non hai i permessi per accedere a questa sezione.', 'casa-vacanza-prenotazioni' ) );
			}
		}

		if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
			$post = get_post( absint( $_GET['post'] ) );
			if ( $post && ! in_array( $post->post_type, $allowed_types, true ) ) {
				wp_die( esc_html__( 'Non hai i permessi per modificare questo contenuto.', 'casa-vacanza-prenotazioni' ) );
			}
		}
	}
}
