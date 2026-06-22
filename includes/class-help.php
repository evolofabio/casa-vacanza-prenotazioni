<?php
/**
 * Documentazione plugin e riferimento shortcode.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Help {

	/**
	 * Elenco shortcode con descrizione.
	 *
	 * @return array
	 */
	public static function get_shortcodes() {
		return array(
			array(
				'code'        => '[cvp_search_bar]',
				'title'       => __( 'Barra ricerca', 'casa-vacanza-prenotazioni' ),
				'description' => __( 'Mostra la barra con check-in, check-out, numero ospiti e bottone Cerca. Reindirizza alla pagina risultati.', 'casa-vacanza-prenotazioni' ),
				'example'     => '[cvp_search_bar]',
				'attributes'  => array(
					'results_page' => __( 'ID pagina risultati (opzionale, altrimenti usa impostazioni)', 'casa-vacanza-prenotazioni' ),
				),
			),
			array(
				'code'        => '[cvp_search_results]',
				'title'       => __( 'Risultati ricerca', 'casa-vacanza-prenotazioni' ),
				'description' => __( 'Pagina completa: barra ricerca + griglia appartamenti disponibili in base ai parametri URL (cvp_check_in, cvp_check_out, cvp_guests).', 'casa-vacanza-prenotazioni' ),
				'example'     => '[cvp_search_results]',
				'attributes'  => array(),
			),
			array(
				'code'        => '[cvp_apartment_card]',
				'title'       => __( 'Card appartamento', 'casa-vacanza-prenotazioni' ),
				'description' => __( 'Card con galleria, prezzo, descrizione, servizi e bottone per richiedere prenotazione.', 'casa-vacanza-prenotazioni' ),
				'example'     => '[cvp_apartment_card id="123"]',
				'attributes'  => array(
					'id'           => __( 'ID appartamento (se omesso usa il post corrente)', 'casa-vacanza-prenotazioni' ),
					'show_booking' => __( 'yes/no — mostra bottone prenotazione (default: yes)', 'casa-vacanza-prenotazioni' ),
					'check_in'     => __( 'Data check-in precompilata (opzionale)', 'casa-vacanza-prenotazioni' ),
					'check_out'    => __( 'Data check-out precompilata (opzionale)', 'casa-vacanza-prenotazioni' ),
					'guests'       => __( 'Numero ospiti precompilato (opzionale)', 'casa-vacanza-prenotazioni' ),
				),
			),
			array(
				'code'        => '[cvp_booking_form]',
				'title'       => __( 'Form prenotazione', 'casa-vacanza-prenotazioni' ),
				'description' => __( 'Form per inviare una richiesta di prenotazione. Crea una prenotazione in stato "In attesa" e invia le email automatiche.', 'casa-vacanza-prenotazioni' ),
				'example'     => '[cvp_booking_form apartment_id="123"]',
				'attributes'  => array(
					'apartment_id' => __( 'ID appartamento (se omesso legge ?cvp_apartment= dall\'URL)', 'casa-vacanza-prenotazioni' ),
				),
			),
		);
	}

	/**
	 * Sezioni guida plugin.
	 *
	 * @return array
	 */
	public static function get_sections() {
		return array(
			array(
				'title'   => __( 'Cos\'è questo plugin', 'casa-vacanza-prenotazioni' ),
				'content' => __( 'Casa Vacanza Prenotazioni gestisce appartamenti, disponibilità e richieste di prenotazione. Gli ospiti cercano date disponibili, inviano una richiesta dal sito e ricevono email di conferma. Tu gestisci le prenotazioni dalla dashboard: accetti, rifiuti o annulli con motivazione.', 'casa-vacanza-prenotazioni' ),
			),
			array(
				'title'   => __( 'Flusso operativo', 'casa-vacanza-prenotazioni' ),
				'content' => __( '1) Crea gli appartamenti con prezzo, capienza e galleria. 2) Inserisci la barra ricerca in homepage. 3) Crea una pagina risultati con [cvp_search_results]. 4) L\'ospite invia la richiesta → prenotazione "In attesa". 5) Tu confermi o rifiuti da Casa Vacanza → Prenotazioni.', 'casa-vacanza-prenotazioni' ),
			),
			array(
				'title'   => __( 'Gutenberg ed Elementor', 'casa-vacanza-prenotazioni' ),
				'content' => __( 'Gli appartamenti sono compatibili con Elementor: modifica con Elementor e inserisci i dati da Impostazioni pagina → Dati Appartamento (prezzo, galleria, servizi, ecc.). Widget disponibili nella categoria "Casa Vacanza": Barra Ricerca, Risultati Ricerca, Galleria, Dettagli, Servizi, Card Appartamento e Form Prenotazione. Dynamic tag per prezzo, ospiti, camere, ubicazione e altro.', 'casa-vacanza-prenotazioni' ),
			),
			array(
				'title'   => __( 'Ruolo Gestore Prenotazioni', 'casa-vacanza-prenotazioni' ),
				'content' => __( 'Assegna il ruolo "Gestore Prenotazioni" a chi deve gestire appartamenti e prenotazioni senza accesso completo da amministratore.', 'casa-vacanza-prenotazioni' ),
			),
		);
	}
}
