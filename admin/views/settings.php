<?php
/**
 * Vista impostazioni plugin.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;

$placeholders = '{nome}, {email}, {appartamento}, {check_in}, {check_out}, {ospiti}, {totale}, {motivazione}, {sito}';
?>
<div class="wrap cvp-admin-wrap">
	<h1><?php esc_html_e( 'Impostazioni Casa Vacanza', 'casa-vacanza-prenotazioni' ); ?></h1>

	<div class="cvp-admin-panel cvp-admin-panel--shortcodes">
		<?php
		$shortcodes = \CVP\Help::get_shortcodes();
		include CVP_PLUGIN_DIR . 'admin/views/partials/shortcodes-reference.php';
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cvp-help' ) ); ?>" class="button">
				<?php esc_html_e( 'Apri guida completa', 'casa-vacanza-prenotazioni' ); ?>
			</a>
		</p>
	</div>

	<div class="cvp-admin-panel cvp-update-panel">
		<h2><?php esc_html_e( 'Aggiornamenti da GitHub', 'casa-vacanza-prenotazioni' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Versione installata', 'casa-vacanza-prenotazioni' ); ?></th>
				<td><code><?php echo esc_html( $update['current'] ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Ultima versione GitHub', 'casa-vacanza-prenotazioni' ); ?></th>
				<td><code><?php echo esc_html( $update['latest'] ); ?></code></td>
			</tr>
		</table>
		<?php if ( ! empty( $update['is_git'] ) ) : ?>
			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Installazione Git rilevata', 'casa-vacanza-prenotazioni' ); ?></strong><br>
					<?php esc_html_e( 'Non usare Plugin → Aggiorna: WordPress non può sovrascrivere la cartella .git.', 'casa-vacanza-prenotazioni' ); ?>
				</p>
				<p><code>cd wp-content/plugins/casa-vacanza-prenotazioni && git pull origin main</code></p>
			</div>
		<?php elseif ( ! empty( $update['has_update'] ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Aggiornamento disponibile.', 'casa-vacanza-prenotazioni' ); ?> <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Plugin → Aggiorna', 'casa-vacanza-prenotazioni' ); ?></a></p></div>
		<?php else : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Plugin aggiornato.', 'casa-vacanza-prenotazioni' ); ?></p></div>
		<?php endif; ?>
		<p>
			<a href="<?php echo esc_url( \CVP\GitHub_Updater::get_check_updates_url() ); ?>" class="button"><?php esc_html_e( 'Controlla aggiornamenti', 'casa-vacanza-prenotazioni' ); ?></a>
			<a href="<?php echo esc_url( \CVP\GitHub_Updater::get_repo_url() ); ?>" class="button" target="_blank" rel="noopener">GitHub</a>
		</p>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'cvp_settings_group' ); ?>

		<h2 class="title"><?php esc_html_e( 'Generale', 'casa-vacanza-prenotazioni' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="cvp_currency_symbol"><?php esc_html_e( 'Simbolo valuta', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="text" id="cvp_currency_symbol" name="cvp_settings[currency_symbol]" value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_currency_position"><?php esc_html_e( 'Posizione simbolo', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_currency_position" name="cvp_settings[currency_position]">
						<option value="after" <?php selected( $settings['currency_position'], 'after' ); ?>><?php esc_html_e( 'Dopo (100 €)', 'casa-vacanza-prenotazioni' ); ?></option>
						<option value="before" <?php selected( $settings['currency_position'], 'before' ); ?>><?php esc_html_e( 'Prima (€ 100)', 'casa-vacanza-prenotazioni' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_min_nights"><?php esc_html_e( 'Notti minime', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="number" min="1" id="cvp_min_nights" name="cvp_settings[min_nights]" value="<?php echo esc_attr( $settings['min_nights'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_risultati_page"><?php esc_html_e( 'Pagina risultati ricerca', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_risultati_page" name="cvp_settings[cvp_risultati_page]">
						<option value="0"><?php esc_html_e( '— Seleziona —', 'casa-vacanza-prenotazioni' ); ?></option>
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['cvp_risultati_page'], $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_pending_hold_hours"><?php esc_html_e( 'Scadenza richieste in attesa', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<input type="number" min="0" step="1" id="cvp_pending_hold_hours" name="cvp_settings[pending_hold_hours]" value="<?php echo esc_attr( $settings['pending_hold_hours'] ); ?>" class="small-text" />
					<p class="description"><?php esc_html_e( 'Ore dopo le quali una richiesta non confermata viene rifiutata automaticamente e le date tornano libere. 0 = disabilitato.', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="cvp_privacy_policy_page"><?php esc_html_e( 'Pagina privacy (fallback)', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td>
					<select id="cvp_privacy_policy_page" name="cvp_settings[privacy_policy_page]">
						<option value="0"><?php esc_html_e( '— Usa impostazione WordPress —', 'casa-vacanza-prenotazioni' ); ?></option>
						<?php foreach ( $pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $settings['privacy_policy_page'], $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Usata nel form se non è impostata una pagina privacy in Impostazioni → Privacy.', 'casa-vacanza-prenotazioni' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Email', 'casa-vacanza-prenotazioni' ); ?></h2>
		<p class="description"><?php echo esc_html( sprintf( __( 'Placeholder disponibili: %s', 'casa-vacanza-prenotazioni' ), $placeholders ) ); ?></p>
		<table class="form-table">
			<tr>
				<th><label for="cvp_from_email"><?php esc_html_e( 'Email mittente', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="email" id="cvp_from_email" name="cvp_settings[from_email]" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_from_name"><?php esc_html_e( 'Nome mittente', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="text" id="cvp_from_name" name="cvp_settings[from_name]" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="cvp_operator_email"><?php esc_html_e( 'Email operatore', 'casa-vacanza-prenotazioni' ); ?></label></th>
				<td><input type="email" id="cvp_operator_email" name="cvp_settings[operator_email]" value="<?php echo esc_attr( $settings['operator_email'] ); ?>" class="regular-text" /></td>
			</tr>
		</table>

		<?php
		$email_sections = array(
			'email_customer_request'  => __( 'Cliente – Richiesta ricevuta', 'casa-vacanza-prenotazioni' ),
			'email_operator_new'      => __( 'Operatore – Nuova richiesta', 'casa-vacanza-prenotazioni' ),
			'email_customer_confirmed'=> __( 'Cliente – Prenotazione confermata', 'casa-vacanza-prenotazioni' ),
			'email_customer_rejected' => __( 'Cliente – Prenotazione rifiutata', 'casa-vacanza-prenotazioni' ),
			'email_customer_cancelled'=> __( 'Cliente – Prenotazione annullata', 'casa-vacanza-prenotazioni' ),
		);

		foreach ( $email_sections as $key => $title ) :
			?>
			<h3><?php echo esc_html( $title ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>_subject"><?php esc_html_e( 'Oggetto', 'casa-vacanza-prenotazioni' ); ?></label></th>
					<td><input type="text" id="<?php echo esc_attr( $key ); ?>_subject" name="cvp_settings[<?php echo esc_attr( $key ); ?>_subject]" value="<?php echo esc_attr( $settings[ $key . '_subject' ] ); ?>" class="large-text" /></td>
				</tr>
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>_body"><?php esc_html_e( 'Corpo', 'casa-vacanza-prenotazioni' ); ?></label></th>
					<td><textarea id="<?php echo esc_attr( $key ); ?>_body" name="cvp_settings[<?php echo esc_attr( $key ); ?>_body]" rows="5" class="large-text"><?php echo esc_textarea( $settings[ $key . '_body' ] ); ?></textarea></td>
				</tr>
			</table>
		<?php endforeach; ?>

		<?php submit_button(); ?>
	</form>
</div>
