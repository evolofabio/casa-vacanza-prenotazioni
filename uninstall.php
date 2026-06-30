<?php
/**
 * Pulizia alla disinstallazione del plugin.
 *
 * Non elimina appartamenti, prenotazioni o meta: i dati restano nel database.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'cvp_settings' );
delete_option( 'cvp_caps_version' );

wp_clear_scheduled_hook( 'cvp_expire_pending_bookings' );

$gestore = get_role( 'cvp_gestore' );
if ( $gestore ) {
	remove_role( 'cvp_gestore' );
}

$admin = get_role( 'administrator' );
if ( $admin ) {
	foreach ( array( 'cvp_view_dashboard', 'cvp_manage_bookings', 'cvp_manage_apartments' ) as $cap ) {
		$admin->remove_cap( $cap );
	}
}
