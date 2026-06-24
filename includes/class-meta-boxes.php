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
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Avvisi admin per errori di collegamento pagina.
	 */
	public static function admin_notices() {
		$message = get_transient( 'cvp_link_page_error_' . get_current_user_id() );
		if ( $message ) {
			delete_transient( 'cvp_link_page_error_' . get_current_user_id() );
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( $message )
			);
		}

		$notice = get_transient( 'cvp_admin_notice_' . get_current_user_id() );
		if ( ! $notice || ! is_array( $notice ) ) {
			return;
		}

		delete_transient( 'cvp_admin_notice_' . get_current_user_id() );
		$type = 'error' === ( $notice['type'] ?? '' ) ? 'error' : 'success';
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $notice['message'] ?? '' )
		);
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
			__( 'Disponibilità e calendario', 'casa-vacanza-prenotazioni' ),
			array( __CLASS__, 'render_availability_meta_box' ),
			Post_Types::APPARTAMENTO,
			'normal',
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
			if ( 'edit.php' !== $hook || Post_Types::APPARTAMENTO !== $post_type ) {
				return;
			}
		}

		if ( Post_Types::APPARTAMENTO === $post_type ) {
			wp_enqueue_style(
				'cvp-admin',
				CVP_PLUGIN_URL . 'admin/css/admin.css',
				array(),
				CVP_VERSION
			);

			if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_media();
				wp_enqueue_script(
					'cvp-admin-gallery',
					CVP_PLUGIN_URL . 'admin/js/gallery.js',
					array( 'jquery' ),
					CVP_VERSION,
					true
				);
				wp_enqueue_script(
					'cvp-admin-availability',
					CVP_PLUGIN_URL . 'admin/js/availability.js',
					array( 'jquery' ),
					CVP_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Render dettagli appartamento.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_apartment_meta_box( $post ) {
		wp_nonce_field( 'cvp_save_apartment', 'cvp_apartment_nonce' );

		$meta = Apartment_Meta::get_all( $post->ID );
		$linkable_pages = Apartment_Meta::get_linkable_pages( $post->ID );
		?>
		<p class="description" style="margin-bottom:1em;">
			<?php esc_html_e( 'Compila tutti i campi qui sotto. Puoi collegare una pagina WordPress già creata (es. con Elementor) come scheda pubblica dell\'appartamento.', 'casa-vacanza-prenotazioni' ); ?>
		</p>
		<table class="form-table cvp-meta-table">
			<tr>
				<th><label for="cvp_linked_page_id"><?php esc_html_e( 'Pagina collegata', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_linked_page_id" name="cvp_linked_page_id" class="regular-text">
						<option value="0"><?php esc_html_e( '— Nessuna (usa URL appartamento) —', 'casa-vacanza-prenotazioni' ); ?></option>
						<?php foreach ( $linkable_pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $meta['linked_page'], $page->ID ); ?>>
								<?php echo esc_html( $page->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'I widget e il form prenotazione sulla pagina selezionata useranno automaticamente i dati di questo appartamento.', 'casa-vacanza-prenotazioni' ); ?>
						<?php if ( $meta['linked_page'] ) : ?>
							<a href="<?php echo esc_url( get_permalink( $meta['linked_page'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visualizza pagina', 'casa-vacanza-prenotazioni' ); ?></a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_price"><?php esc_html_e( 'Prezzo per notte', 'casa-vacanza-prenotazioni' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="number" step="0.01" min="0" id="cvp_price" name="cvp_price" value="<?php echo esc_attr( $meta['price'] ); ?>" class="regular-text" required />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_max_guests"><?php esc_html_e( 'Capienza massima (ospiti)', 'casa-vacanza-prenotazioni' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="number" min="1" id="cvp_max_guests" name="cvp_max_guests" value="<?php echo esc_attr( $meta['max_guests'] ); ?>" class="small-text" required />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_beds"><?php esc_html_e( 'Posti letto', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="0" id="cvp_beds" name="cvp_beds" value="<?php echo esc_attr( $meta['beds'] ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Numero totale di posti letto disponibili.', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_bedrooms"><?php esc_html_e( 'Camere da letto', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="0" id="cvp_bedrooms" name="cvp_bedrooms" value="<?php echo esc_attr( $meta['bedrooms'] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_bathrooms"><?php esc_html_e( 'Bagni', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="0" id="cvp_bathrooms" name="cvp_bathrooms" value="<?php echo esc_attr( $meta['bathrooms'] ); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_location"><?php esc_html_e( 'Ubicazione', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="text" id="cvp_location" name="cvp_location" value="<?php echo esc_attr( $meta['location'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Es: Lago di Garda, Desenzano del Garda', 'casa-vacanza-prenotazioni' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_min_nights"><?php esc_html_e( 'Notti minime', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="0" id="cvp_min_nights" name="cvp_min_nights" value="<?php echo esc_attr( $meta['min_nights'] ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Lascia 0 per usare il valore globale nelle Impostazioni.', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_cleaning_fee"><?php esc_html_e( 'Spese pulizia', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" step="0.01" min="0" id="cvp_cleaning_fee" name="cvp_cleaning_fee" value="<?php echo esc_attr( $meta['cleaning_fee'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="cvp_services"><?php esc_html_e( 'Servizi (uno per riga)', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<textarea id="cvp_services" name="cvp_services" rows="6" class="large-text"><?php echo esc_textarea( implode( "\n", $meta['services'] ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Es: Wi-Fi, Parcheggio, Aria condizionata', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="description">
			<?php esc_html_e( 'Usa il riassunto (excerpt) per una breve descrizione nelle card di ricerca. La descrizione completa va nell’editor principale.', 'casa-vacanza-prenotazioni' ); ?>
		</p>
		<?php
	}

	/**
	 * Render galleria immagini.
	 *
	 * @param \WP_Post $post Post corrente.
	 */
	public static function render_gallery_meta_box( $post ) {
		$gallery = get_post_meta( $post->ID, Apartment_Meta::GALLERY, true );
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
		$meta           = Apartment_Meta::get_all( $post->ID );
		$bookings       = Availability::get_blocked_dates_for_apartment( $post->ID );
		$manual_blocks  = $meta['manual_blocks'];
		$all_blocked    = Availability::get_all_blocked_ranges( $post->ID );
		?>
		<div class="cvp-availability-admin">
			<h4><?php esc_html_e( 'Periodo di apertura', 'casa-vacanza-prenotazioni' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Lascia vuoto per rendere l\'appartamento prenotabile tutto l\'anno (salvo blocchi e prenotazioni).', 'casa-vacanza-prenotazioni' ); ?></p>
			<table class="form-table cvp-meta-table">
				<tr>
					<th><label for="cvp_available_from"><?php esc_html_e( 'Disponibile dal (check-in)', 'casa-vacanza-prenotazioni' ); ?></label></th>
					<td><input type="date" id="cvp_available_from" name="cvp_available_from" value="<?php echo esc_attr( $meta['available_from'] ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="cvp_available_to"><?php esc_html_e( 'Disponibile fino al (check-out)', 'casa-vacanza-prenotazioni' ); ?></label></th>
					<td><input type="date" id="cvp_available_to" name="cvp_available_to" value="<?php echo esc_attr( $meta['available_to'] ); ?>" /></td>
				</tr>
			</table>

			<h4><?php esc_html_e( 'Blocchi manuali', 'casa-vacanza-prenotazioni' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Aggiungi periodi non prenotabili (manutenzione, uso personale, ecc.).', 'casa-vacanza-prenotazioni' ); ?></p>
			<div class="cvp-manual-blocks" data-blocks='<?php echo esc_attr( wp_json_encode( $manual_blocks ) ); ?>'>
				<table class="widefat striped cvp-manual-blocks-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Check-in', 'casa-vacanza-prenotazioni' ); ?></th>
							<th><?php esc_html_e( 'Check-out', 'casa-vacanza-prenotazioni' ); ?></th>
							<th><?php esc_html_e( 'Nota', 'casa-vacanza-prenotazioni' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="cvp-manual-blocks-body">
						<?php foreach ( $manual_blocks as $index => $block ) : ?>
							<tr class="cvp-manual-block-row">
								<td><input type="date" name="cvp_manual_blocks[<?php echo esc_attr( $index ); ?>][check_in]" value="<?php echo esc_attr( $block['check_in'] ); ?>" /></td>
								<td><input type="date" name="cvp_manual_blocks[<?php echo esc_attr( $index ); ?>][check_out]" value="<?php echo esc_attr( $block['check_out'] ); ?>" /></td>
								<td><input type="text" class="regular-text" name="cvp_manual_blocks[<?php echo esc_attr( $index ); ?>][note]" value="<?php echo esc_attr( $block['note'] ); ?>" /></td>
								<td><button type="button" class="button cvp-remove-manual-block">&times;</button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p><button type="button" class="button" id="cvp-add-manual-block"><?php esc_html_e( 'Aggiungi blocco', 'casa-vacanza-prenotazioni' ); ?></button></p>
			</div>

			<h4><?php esc_html_e( 'Calendario aggiornato (prenotazioni + blocchi)', 'casa-vacanza-prenotazioni' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Le prenotazioni in attesa o confermate bloccano automaticamente le date.', 'casa-vacanza-prenotazioni' ); ?></p>
			<?php if ( empty( $all_blocked ) ) : ?>
				<p><em><?php esc_html_e( 'Nessun blocco attivo.', 'casa-vacanza-prenotazioni' ); ?></em></p>
			<?php else : ?>
				<ul class="cvp-blocked-dates">
					<?php foreach ( $all_blocked as $block ) : ?>
						<li class="cvp-blocked-dates__item cvp-blocked-dates__item--<?php echo esc_attr( $block['type'] ); ?>">
							<strong><?php echo esc_html( Post_Types::format_date( $block['check_in'] ) . ' – ' . Post_Types::format_date( $block['check_out'] ) ); ?></strong>
							<br>
							<small>
								<?php echo esc_html( $block['label'] ); ?>
								<?php if ( 'booking' === $block['type'] ) : ?>
									(<?php esc_html_e( 'automatico', 'casa-vacanza-prenotazioni' ); ?>)
								<?php endif; ?>
							</small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $bookings ) ) : ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-bookings' ) ); ?>"><?php esc_html_e( 'Gestisci prenotazioni', 'casa-vacanza-prenotazioni' ); ?></a>
				</p>
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

		Apartment_Meta::save_from_request( $post_id );

		if ( isset( $_POST['cvp_manual_blocks'] ) && is_array( $_POST['cvp_manual_blocks'] ) ) {
			Apartment_Meta::save_manual_blocks( $post_id, wp_unslash( $_POST['cvp_manual_blocks'] ) );
		}
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

		$old_status   = get_post_meta( $post_id, '_cvp_status', true );
		$new_status   = isset( $_POST['cvp_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_status'] ) ) : Post_Types::STATUS_IN_ATTESA;
		$apartment_id = isset( $_POST['cvp_apartment_id'] ) ? absint( $_POST['cvp_apartment_id'] ) : 0;
		$check_in     = isset( $_POST['cvp_check_in'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_check_in'] ) ) : '';
		$check_out    = isset( $_POST['cvp_check_out'] ) ? sanitize_text_field( wp_unslash( $_POST['cvp_check_out'] ) ) : '';

		if ( $apartment_id && in_array( $new_status, Availability::blocking_statuses(), true ) ) {
			$validation = Availability::validate_dates( $check_in, $check_out, $apartment_id, $post_id );
			if ( is_wp_error( $validation ) ) {
				set_transient(
					'cvp_admin_notice_' . get_current_user_id(),
					array(
						'type'    => 'error',
						'message' => $validation->get_error_message(),
					),
					30
				);
				return;
			}
		}

		$fields = array(
			'_cvp_status'         => $new_status,
			'_cvp_apartment_id'   => $apartment_id,
			'_cvp_check_in'       => $check_in,
			'_cvp_check_out'      => $check_out,
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
