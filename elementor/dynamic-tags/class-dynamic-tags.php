<?php
/**
 * Registrazione dynamic tag Elementor per appartamenti.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP\Elementor\DynamicTags;

use CVP\Apartment_Meta;
use CVP\Post_Types;
use CVP\Settings;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

defined( 'ABSPATH' ) || exit;

class Dynamic_Tags {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_tags' ) );
		add_action( 'elementor/dynamic_tags/register', array( __CLASS__, 'register_group' ) );
	}

	/**
	 * Registra gruppo tag.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Manager tag.
	 */
	public static function register_group( $manager ) {
		$manager->register_group(
			'casa-vacanza',
			array(
				'title' => __( 'Casa Vacanza', 'casa-vacanza-prenotazioni' ),
			)
		);
	}

	/**
	 * Registra tutti i tag.
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $manager Manager tag.
	 */
	public static function register_tags( $manager ) {
		$manager->register( new Price_Tag() );
		$manager->register( new Guests_Tag() );
		$manager->register( new Bedrooms_Tag() );
		$manager->register( new Bathrooms_Tag() );
		$manager->register( new Location_Tag() );
		$manager->register( new Cleaning_Fee_Tag() );
		$manager->register( new Services_Tag() );
	}
}

/**
 * Tag base per meta appartamento.
 */
abstract class Base_Apartment_Tag extends Tag {

	/**
	 * ID appartamento dal contesto.
	 *
	 * @return int
	 */
	protected function get_apartment_id() {
		$post_id = (int) get_the_ID();
		if ( $post_id && Post_Types::APPARTAMENTO === get_post_type( $post_id ) ) {
			return $post_id;
		}

		return 0;
	}

	public function get_group() {
		return 'casa-vacanza';
	}
}

class Price_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-price';
	}

	public function get_title() {
		return __( 'Prezzo per notte', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		echo esc_html( Settings::format_price( get_post_meta( $post_id, Apartment_Meta::PRICE, true ) ) );
	}
}

class Guests_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-guests';
	}

	public function get_title() {
		return __( 'Capienza massima', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		echo esc_html( (string) get_post_meta( $post_id, Apartment_Meta::MAX_GUESTS, true ) );
	}
}

class Bedrooms_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-bedrooms';
	}

	public function get_title() {
		return __( 'Camere da letto', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		echo esc_html( (string) get_post_meta( $post_id, Apartment_Meta::BEDROOMS, true ) );
	}
}

class Bathrooms_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-bathrooms';
	}

	public function get_title() {
		return __( 'Bagni', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		echo esc_html( (string) get_post_meta( $post_id, Apartment_Meta::BATHROOMS, true ) );
	}
}

class Location_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-location';
	}

	public function get_title() {
		return __( 'Ubicazione', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		echo esc_html( (string) get_post_meta( $post_id, Apartment_Meta::LOCATION, true ) );
	}
}

class Cleaning_Fee_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-cleaning-fee';
	}

	public function get_title() {
		return __( 'Spese pulizia', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		$fee = get_post_meta( $post_id, Apartment_Meta::CLEANING_FEE, true );
		echo esc_html( $fee ? Settings::format_price( $fee ) : '' );
	}
}

class Services_Tag extends Base_Apartment_Tag {

	public function get_name() {
		return 'cvp-services';
	}

	public function get_title() {
		return __( 'Servizi (elenco)', 'casa-vacanza-prenotazioni' );
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}

	public function render() {
		$post_id = $this->get_apartment_id();
		if ( ! $post_id ) {
			return;
		}

		$services = get_post_meta( $post_id, Apartment_Meta::SERVICES, true );
		if ( ! is_array( $services ) ) {
			return;
		}

		echo esc_html( implode( ', ', $services ) );
	}
}
