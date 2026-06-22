<?php
/**
 * Meta box per appartamenti e prenotazioni.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class Meta_Boxes {

	/**
	 * Inizializza hook.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Post_Types::APPARTAMENTO, array( __CLASS__, 'save_apartment' ), 10, 2 );
		add_action( 'save_post_' . Post_Types::PRENOTAZIONE, array( __CLASS__, 'save_booking' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Registra meta box.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'cvp_apartment_details',
			__( 'Dettagli Appartamento', 'casa-vacanza-prenotazioni' ),
			array( __CLASS__, 'render_apartment_meta_box' ),
			Post_Types::APPARTAMENTO,
			'normal',
			'high'
		);

		add_meta_box(
			'cvp_apartment_gallery',
			__( 'Galleria Immagini', 'casa-vacanza-prenotazioni' ),
			array( __CLASS__, 'render_gallery_meta_box' ),
			Post_Types::APPARTAMENTO,
			'normal',
			'default'
		);

		add_meta_box(
			'cvp_apartment_availability',
			__( 'Calendario Disponibilità', 'casa-vacanza-prenotazioni' ),
			array( __CLASS__, 'render_availability_meta_box' ),
			Post_Types::APPARTAMENTO,
			'side',
			'default'
		);

		add_meta_box(
			'cvp_booking_details',
			__( 'Dettagli Prenotazione', 'casa-vacanza-prenotazioni' ),
			array( __CLASS__, 'render_booking_meta_box' ),
			Post_Types::PRENOTAZIONE,
			'normal',
			'high'
		);
	}

	/**
	 * Script admin per galleria.
	 *
	 * @param string $hook Hook corrente.
	 */
	public static function enqueue_admin_scripts( $hook ) {
		global $post_type;

		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( Post_Types::APPARTAMENTO === $post_type ) {
			wp_enqueue_media();
			wp_enqueue_script(
				'cvp-admin-gallery',
				CVP_PLUGIN_URL . 'admin/js/gallery.js',
				array( 'jquery' ),
				CVP_VERSION,
				true
			);
			wp_enqueue_style(
				'cvp-admin',
				CVP_PLUGIN_URL . 'admin/css/admin.css',
				array(),
				CVP_VERSION
			);
		}
	}

	/**
	 * Render dettagli appartamento.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_apartment_meta_box( $post ) {
		wp_nonce_field( 'cvp_save_apartment', 'cvp_apartment_nonce' );

		$price      = get_post_meta( $post->ID, '_cvp_price', true );
		$max_guests = get_post_meta( $post->ID, '_cvp_max_guests', true );
		$services   = get_post_meta( $post->ID, '_cvp_services', true );
		if ( ! is_array( $services ) ) {
			$services = array();
		}
		?>
		<table class="form-table cvp-meta-table">
			<tr>
				<th><label for="cvp_price"><?php esc_html_e( 'Prezzo per notte', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" step="0.01" min="0" id="cvp_price" name="cvp_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_max_guests"><?php esc_html_e( 'Capienza massima', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="1" id="cvp_max_guests" name="cvp_max_guests" value="<?php echo esc_attr( $max_guests ); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_services"><?php esc_html_e( 'Servizi (uno per riga)', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<textarea id="cvp_services" name="cvp_services" rows="6" class="large-text"><?php echo esc_textarea( implode( "\n", $services ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Es: Wi-Fi, Parcheggio, Aria condizionata', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render galleria immagini.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_gallery_meta_box( $post ) {
		$gallery = get_post_meta( $post->ID, '_cvp_gallery', true );
		if ( ! is_array( $gallery ) ) {
			$gallery = array();
		}
		?>
		<div class="cvp-gallery-admin" data-gallery='<?php echo esc_attr( wp_json_encode( array_map( 'intval', $gallery ) ) ); ?>'>
			<ul class="cvp-gallery-list">
				<?php foreach ( $gallery as $attachment_id ) : ?>
					<?php $url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' ); ?>
					<?php if ( $url ) : ?>
						<li data-id="<?php echo esc_attr( $attachment_id ); ?>">
							<img src="<?php echo esc_url( $url ); ?>" alt="" />
							<button type="button" class="cvp-remove-image">&times;</button>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="cvp_gallery" id="cvp_gallery" value="<?php echo esc_attr( implode( ',', array_map( 'intval', $gallery ) ) ); ?>" />
			<button type="button" class="button" id="cvp-add-gallery-images"><?php esc_html_e( 'Aggiungi immagini', 'casa-vacanza-prenotazioni' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render calendario disponibilità admin.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_availability_meta_box( $post ) {
		$bookings = Availability::get_blocked_dates_for_apartment( $post->ID );
		?>
		<div class="cvp-availability-sidebar">
			<p><?php esc_html_e( 'Date bloccate da prenotazioni in attesa o confermate:', 'casa-vacanza-prenotazioni' ); ?></p>
			<?php if ( empty( $bookings ) ) : ?>
				<p><em><?php esc_html_e( 'Nessun blocco attivo.', 'casa-vacanza-prenotazioni' ); ?></em></p>
			<?php else : ?>
				<ul class="cvp-blocked-dates">
					<?php foreach ( $bookings as $booking ) : ?>
						<li>
							<strong><?php echo esc_html( Post_Types::format_date( $booking['check_in'] ) . ' – ' . Post_Types::format_date( $booking['check_out'] ) ); ?></strong>
							<br>
							<small><?php echo esc_html( $booking['status_label'] ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render dettagli prenotazione.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_booking_meta_box( $post ) {
		wp_nonce_field( 'cvp_save_booking', 'cvp_booking_nonce' );

		$fields = array(
			'status'         => get_post_meta( $post->ID, '_cvp_status', true ),
			'apartment_id'   => get_post_meta( $post->ID, '_cvp_apartment_id', true ),
			'check_in'       => get_post_meta( $post->ID, '_cvp_check_in', true ),
			'check_out'      => get_post_meta( $post->ID, '_cvp_check_out', true ),
			'guests'         => get_post_meta( $post->ID, '_cvp_guests', true ),
			'customer_name'  => get_post_meta( $post->ID, '_cvp_customer_name', true ),
			'customer_email' => get_post_meta( $post->ID, '_cvp_customer_email', true ),
			'customer_phone' => get_post_meta( $post->ID, '_cvp_customer_phone', true ),
			'customer_note'  => get_post_meta( $post->ID, '_cvp_customer_note', true ),
			'status_note'    => get_post_meta( $post->ID, '_cvp_status_note', true ),
			'total_price'    => get_post_meta( $post->ID, '_cvp_total_price', true ),
		);

		$apartments = get_posts(
			array(
				'post_type'      => Post_Types::APPARTAMENTO,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<table class="form-table cvp-meta-table">
			<tr>
				<th><label for="cvp_status"><?php esc_html_e( 'Stato', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_status" name="cvp_status">
						<?php foreach ( Post_Types::get_status_labels() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $fields['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_apartment_id"><?php esc_html_e( 'Appartamento', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_apartment_id" name="cvp_apartment_id">
						<option value=""><?php esc_html_e( '— Seleziona —', 'casa-vacanza-prenotazioni' ); ?></option>
						<?php foreach ( $apartments as $apt ) : ?>
							<option value="<?php echo esc_attr( $apt->ID ); ?>" <?php selected( $fields['apartment_id'], $apt->ID ); ?>><?php echo esc_html( $apt->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_check_in"><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="date" id="cvp_check_in" name="cvp_check_in" value="<?php echo esc_attr( $fields['check_in'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="cvp_check_out"><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="date" id="cvp_check_out" name="cvp_check_out" value="<?php echo esc_attr( $fields['check_out'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="cvp_guests"><?php esc_html_e( 'Ospiti', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="number" min="1" id="cvp_guests" name="cvp_guests" value="<?php echo esc_attr( $fields['guests'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_customer_name"><?php esc_html_e( 'Nome cliente', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="text" id="cvp_customer_name" name="cvp_customer_name" value="<?php echo esc_attr( $fields['customer_name'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_customer_email"><?php esc_html_e( 'Email cliente', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="email" id="cvp_customer_email" name="cvp_customer_email" value="<?php echo esc_attr( $fields['customer_email'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_customer_phone"><?php esc_html_e( 'Telefono', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="text" id="cvp_customer_phone" name="cvp_customer_phone" value="<?php echo esc_attr( $fields['customer_phone'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_customer_note"><?php esc_html_e( 'Note cliente', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><textarea id="cvp_customer_note" name="cvp_customer_note" rows="3" class="large-text"><?php echo esc_textarea( $fields['customer_note'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="cvp_status_note"><?php esc_html_e( 'Motivazione (rifiuto/annullamento)', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><textarea id="cvp_status_note" name="cvp_status_note" rows="3" class="large-text"><?php echo esc_textarea( $fields['status_note'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="cvp_total_price"><?php esc_html_e( 'Totale', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="number" step="0.01" id="cvp_total_price" name="cvp_total_price" value="<?php echo esc_attr( $fields['total_price'] ); ?>" class="regular-text" readonly /></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Salva meta appartamento.
	 *
	 * @param int      $post_id ID post.
	 * @param \WP_Post $post    Post.
	 */
	public static function save_apartment( $post_id, $post ) {
		if ( ! isset( $_POST['cvp_apartment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cvp_apartment_nonce'] ) ), 'cvp_save_apartment' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_cvp_price', isset( $_POST['cvp_price'] ) ? floatval( $_POST['cvp_price'] ) : 0 );
		update_post_meta( $post_id, '_cvp_max_guests', isset( $_POST['cvp_max_guests'] ) ? absint( $_POST['cvp_max_guests'] ) : 1 );

		$services_raw = isset( $_POST['cvp_services'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cvp_services'] ) ) : '';
		$services     = array_filter( array_map( 'trim', explode( "\n", $services_raw ) ) );
		update_post_meta( $post_id, '_cvp_services', $services );

		$gallery_raw = isset( $_POST['cvp_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_gallery'] ) ) : '';
		$gallery     = array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) );
		update_post_meta( $post_id, '_cvp_gallery', $gallery );
	}

	/**
	 * Salva meta prenotazione.
	 *
	 * @param int      $post_id ID post.
	 * @param \WP_Post $post    Post.
	 */
	public static function save_booking( $post_id, $post ) {
		if ( ! isset( $_POST['cvp_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cvp_booking_nonce'] ) ), 'cvp_save_booking' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$old_status = get_post_meta( $post_id, '_cvp_status', true );
		$new_status = isset( $_POST['cvp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_status'] ) ) : Post_Types::STATUS_IN_ATTESA;

		$fields = array(
			'_cvp_status'         => $new_status,
			'_cvp_apartment_id'   => isset( $_POST['cvp_apartment_id'] ) ? absint( $_POST['cvp_apartment_id'] ) : 0,
			'_cvp_check_in'       => isset( $_POST['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_check_in'] ) ) : '',
			'_cvp_check_out'      => isset( $_POST['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_check_out'] ) ) : '',
			'_cvp_guests'         => isset( $_POST['cvp_guests'] ) ? absint( $_POST['cvp_guests'] ) : 1,
			'_cvp_customer_name'  => isset( $_POST['cvp_customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_customer_name'] ) ) : '',
			'_cvp_customer_email' => isset( $_POST['cvp_customer_email'] ) ? sanitize_email( wp_unslash( $_POST['cvp_customer_email'] ) ) : '',
			'_cvp_customer_phone' => isset( $_POST['cvp_customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_customer_phone'] ) ) : '',
			'_cvp_customer_note'  => isset( $_POST['cvp_customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cvp_customer_note'] ) ) : '',
			'_cvp_status_note'    => isset( $_POST['cvp_status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cvp_status_note'] ) ) : '',
		);

		foreach ( $fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		if ( $old_status !== $new_status ) {
			Booking::handle_status_change( $post_id, $old_status, $new_status );
		}
	}
}
