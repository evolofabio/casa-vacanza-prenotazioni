<?php
/**
 * Vista collegamento pagine esistenti come appartamenti.
 *
 * @package CasaVacanzaPrenotazioni
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cvp-admin-wrap">
	<h1><?php esc_html_e( 'Collega pagine come appartamenti', 'casa-vacanza-prenotazioni' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Seleziona le pagine WordPress già create (anche con Elementor) per convertirle in appartamenti prenotabili. Verrà creato un record appartamento collegato a ciascuna pagina.', 'casa-vacanza-prenotazioni' ); ?>
	</p>

	<?php if ( $created ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %d: number of apartments created */
					esc_html( _n( '%d appartamento creato.', '%d appartamenti creati.', $created, 'casa-vacanza-prenotazioni' ) ),
					$created
				);
				?>
				<?php if ( $skipped ) : ?>
					<?php
					printf(
						/* translators: %d: number of skipped pages */
						esc_html( _n( ' %d pagina saltata (già collegata).', ' %d pagine saltate (già collegate).', $skipped, 'casa-vacanza-prenotazioni' ) ),
						$skipped
					);
					?>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="cvp-admin-panel">
		<p>
			<strong><?php esc_html_e( 'Appartamenti con pagina collegata:', 'casa-vacanza-prenotazioni' ); ?></strong>
			<?php echo esc_html( (string) $linked_count ); ?>
			&nbsp;|&nbsp;
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . \CVP\Post_Types::APPARTAMENTO ) ); ?>">
				<?php esc_html_e( 'Vai all’elenco appartamenti', 'casa-vacanza-prenotazioni' ); ?>
			</a>
		</p>

		<?php if ( empty( $unlinked_pages ) ) : ?>
			<p><em><?php esc_html_e( 'Tutte le pagine sono già collegate a un appartamento.', 'casa-vacanza-prenotazioni' ); ?></em></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="cvp_link_pages" />
				<?php wp_nonce_field( 'cvp_link_pages' ); ?>

				<table class="widefat striped cvp-link-pages-table">
					<thead>
						<tr>
							<td class="check-column">
								<input type="checkbox" id="cvp-select-all-pages" />
							</td>
							<th><?php esc_html_e( 'Pagina', 'casa-vacanza-prenotazioni' ); ?></th>
							<th><?php esc_html_e( 'Stato', 'casa-vacanza-prenotazioni' ); ?></th>
							<th><?php esc_html_e( 'Anteprima', 'casa-vacanza-prenotazioni' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $unlinked_pages as $page ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="page_ids[]" value="<?php echo esc_attr( $page->ID ); ?>" class="cvp-page-checkbox" />
								</th>
								<td>
									<strong><?php echo esc_html( $page->post_title ); ?></strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Modifica', 'casa-vacanza-prenotazioni' ); ?></a>
										</span>
									</div>
								</td>
								<td><?php echo esc_html( ucfirst( $page->post_status ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_permalink( $page->ID ) ); ?>" target="_blank" rel="noopener">
										<?php esc_html_e( 'Apri', 'casa-vacanza-prenotazioni' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Imposta come appartamenti', 'casa-vacanza-prenotazioni' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>
	</div>
</div>

<script>
(function () {
	var selectAll = document.getElementById('cvp-select-all-pages');
	if (!selectAll) return;
	selectAll.addEventListener('change', function () {
		document.querySelectorAll('.cvp-page-checkbox').forEach(function (cb) {
			cb.checked = selectAll.checked;
		});
	});
})();
</script>
