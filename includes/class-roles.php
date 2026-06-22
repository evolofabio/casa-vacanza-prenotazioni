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
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_add_caps_to_admin' ) );
		add_filter( 'user_has_cap', array( __CLASS__, 'grant_caps_to_administrators' ), 10, 4 );
	}

	/**
	 * Capability WordPress generate per il CPT Appartamenti.
	 *
	 * @return array
	 */
	public static function get_apartment_capabilities() {
		return array(
			'edit_cv_appartamento',
			'read_cv_appartamento',
			'delete_cv_appartamento',
			'edit_cv_appartamentos',
			'edit_others_cv_appartamentos',
			'publish_cv_appartamentos',
			'read_private_cv_appartamentos',
			'delete_cv_appartamentos',
			'delete_private_cv_appartamentos',
			'delete_published_cv_appartamentos',
			'delete_others_cv_appartamentos',
			'edit_private_cv_appartamentos',
			'edit_published_cv_appartamentos',
		);
	}

	/**
	 * Capability plugin interne.
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
	 * Assegna tutte le capability necessarie a un ruolo.
	 *
	 * @param \WP_Role $role Ruolo WordPress.
	 */
	public static function assign_all_caps( $role ) {
		if ( ! $role ) {
			return;
		}

		foreach ( array_merge( self::get_plugin_capabilities(), self::get_apartment_capabilities() ) as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Garantisce le capability plugin agli amministratori (anche senza riattivazione).
	 *
	 * @param array    $allcaps Tutte le capability utente.
	 * @param array    $caps    Capability richieste.
	 * @param array    $args    Argomenti extra.
	 * @param \WP_User $user    Utente.
	 * @return array
	 */
	public static function grant_caps_to_administrators( $allcaps, $caps, $args, $user ) {
		if ( empty( $allcaps['manage_options'] ) ) {
			return $allcaps;
		}

		foreach ( array_merge( self::get_plugin_capabilities(), self::get_apartment_capabilities() ) as $cap ) {
			$allcaps[ $cap ] = true;
		}

		return $allcaps;
	}

	/**
	 * Crea ruolo alla attivazione.
	 */
	public static function create_role() {
		remove_role( self::ROLE );

		$gestore = add_role(
			self::ROLE,
			__( 'Gestore Prenotazioni', 'casa-vacanza-prenotazioni' ),
			self::get_role_capabilities()
		);

		self::assign_all_caps( $gestore );
		self::maybe_add_caps_to_admin();
	}

	/**
	 * Capability base del ruolo gestore.
	 *
	 * @return array
	 */
	public static function get_role_capabilities() {
		return array(
			'read'         => true,
			'upload_files' => true,
		);
	}

	/**
	 * Aggiunge capability agli admin.
	 */
	public static function maybe_add_caps_to_admin() {
		self::assign_all_caps( get_role( 'administrator' ) );
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
		return current_user_can( 'edit_cv_appartamentos' ) || current_user_can( 'cvp_manage_apartments' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Verifica accesso dashboard.
	 *
	 * @return bool
	 */
	public static function user_can_view_dashboard() {
		return current_user_can( 'cvp_view_dashboard' ) || current_user_can( 'manage_options' );
	}
}
