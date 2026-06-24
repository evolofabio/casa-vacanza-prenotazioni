<?php
/**
 * Pannello dati appartamento nell'editor Elementor.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor;

use CVP\Apartment_Meta;
use CVP\Post_Types;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

class Apartment_Document {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'elementor/documents/register_controls', array( __CLASS__, 'register_controls' ) );
		add_action( 'elementor/document/after_save', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Registra controlli nel pannello Impostazioni pagina.
	 *
	 * @param \Elementor\Core\Base\Document $document Documento Elementor.
	 */
	public static function register_controls( $document ) {
		if ( ! method_exists( $document, 'get_main_post' ) ) {
			return;
		}

		$post = $document->get_main_post();
		if ( ! $post || Post_Types::APPARTAMENTO !== $post->post_type ) {
			return;
		}

		$meta           = Apartment_Meta::get_all( $post->ID );
		$linkable_pages = Apartment_Meta::get_linkable_pages( $post->ID );
		$page_options   = array( '0' => __( '— Nessuna —', 'casa-vacanza-prenotazioni' ) );
		foreach ( $linkable_pages as $page ) {
			$page_options[ (string) $page->ID ] = $page->post_title;
		}

		$document->start_controls_section(
			'cvp_apartment_data',
			array(
				'label' => __( 'Dati Appartamento', 'casa-vacanza-prenotazioni' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			)
		);

		$document->add_control(
			'cvp_linked_page_id',
			array(
				'label'   => __( 'Pagina collegata', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $page_options,
				'default' => (string) $meta['linked_page'],
			)
		);

		$document->add_control(
			'cvp_beds',
			array(
				'label'   => __( 'Posti letto', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'default' => $meta['beds'],
			)
		);

		$document->add_control(
			'cvp_available_from',
			array(
				'label'   => __( 'Disponibile dal', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::DATE_TIME,
				'default' => $meta['available_from'] ? $meta['available_from'] . ' 00:00' : '',
			)
		);

		$document->add_control(
			'cvp_available_to',
			array(
				'label'   => __( 'Disponibile fino al (check-out)', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::DATE_TIME,
				'default' => $meta['available_to'] ? $meta['available_to'] . ' 00:00' : '',
			)
		);

		$document->add_control(
			'cvp_price',
			array(
				'label'   => __( 'Prezzo per notte', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'step'    => 0.01,
				'default' => $meta['price'],
			)
		);

		$document->add_control(
			'cvp_max_guests',
			array(
				'label'   => __( 'Capienza massima', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'default' => $meta['max_guests'] ?: 2,
			)
		);

		$document->add_control(
			'cvp_bedrooms',
			array(
				'label'   => __( 'Camere da letto', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'default' => $meta['bedrooms'],
			)
		);

		$document->add_control(
			'cvp_bathrooms',
			array(
				'label'   => __( 'Bagni', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'default' => $meta['bathrooms'],
			)
		);

		$document->add_control(
			'cvp_location',
			array(
				'label'       => __( 'Ubicazione', 'casa-vacanza-prenotazioni' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $meta['location'],
				'placeholder' => __( 'Es: Lago di Garda, Desenzano', 'casa-vacanza-prenotazioni' ),
			)
		);

		$document->add_control(
			'cvp_min_nights',
			array(
				'label'       => __( 'Notti minime', 'casa-vacanza-prenotazioni' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'default'     => $meta['min_nights'],
				'description' => __( '0 = usa il valore globale nelle Impostazioni plugin.', 'casa-vacanza-prenotazioni' ),
			)
		);

		$document->add_control(
			'cvp_cleaning_fee',
			array(
				'label'   => __( 'Spese pulizia', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'step'    => 0.01,
				'default' => $meta['cleaning_fee'],
			)
		);

		$document->add_control(
			'cvp_services',
			array(
				'label'       => __( 'Servizi (uno per riga)', 'casa-vacanza-prenotazioni' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => Apartment_Meta::get_services_text( $post->ID ),
				'rows'        => 6,
				'description' => __( 'Es: Wi-Fi, Parcheggio, Aria condizionata', 'casa-vacanza-prenotazioni' ),
			)
		);

		$document->add_control(
			'cvp_gallery',
			array(
				'label'   => __( 'Galleria immagini', 'casa-vacanza-prenotazioni' ),
				'type'    => Controls_Manager::GALLERY,
				'default' => Apartment_Meta::get_gallery_for_elementor( $post->ID ),
			)
		);

		$document->end_controls_section();
	}

	/**
	 * Salva meta quando si salva il documento Elementor.
	 *
	 * @param \Elementor\Core\Base\Document $document Documento.
	 * @param array                         $data     Dati salvati.
	 */
	public static function save( $document, $data ) {
		if ( ! method_exists( $document, 'get_main_post' ) ) {
			return;
		}

		$post = $document->get_main_post();
		if ( ! $post || Post_Types::APPARTAMENTO !== $post->post_type ) {
			return;
		}

		$settings = isset( $data['settings'] ) ? $data['settings'] : $document->get_settings();
		if ( ! is_array( $settings ) ) {
			return;
		}

		Apartment_Meta::save_from_array( $post->ID, $settings );
	}
}
