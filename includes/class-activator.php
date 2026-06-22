<?php
/**
 * Attivazione plugin.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Activator {

	/**
	 * Crea pagine predefinite se non esistono.
	 */
	public static function create_pages() {
		$pages = array(
			'cvp_risultati_page' => array(
				'title'   => __( 'Risultati Ricerca Appartamenti', 'casa-vacanza-prenotazioni' ),
				'content' => '[cvp_search_results]',
				'slug'    => 'risultati-appartamenti',
			),
		);

		$options = get_option( 'cvp_settings', array() );

		foreach ( $pages as $option_key => $page_data ) {
			if ( ! empty( $options[ $option_key ] ) && get_post( $options[ $option_key ] ) ) {
				continue;
			}

			$existing = get_page_by_path( $page_data['slug'] );
			if ( $existing ) {
				$options[ $option_key ] = $existing->ID;
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_title'   => $page_data['title'],
					'post_content' => $page_data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_name'    => $page_data['slug'],
				)
			);

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$options[ $option_key ] = $page_id;
			}
		}

		update_option( 'cvp_settings', $options );
	}
}
