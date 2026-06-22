<?php
/**
 * Partial: tabella shortcode copiabili.
 *
 * @package CasaVacanzaPrenotazioni
 * @var array $shortcodes Elenco da CVP\Help::get_shortcodes().
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $shortcodes ) ) {
	$shortcodes = \CVP\Help::get_shortcodes();
}
?>
<div class="cvp-shortcodes-reference">
	<h2><?php esc_html_e( 'Shortcode disponibili', 'casa-vacanza-prenotazioni' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Copia e incolla negli editor di pagine, articoli o widget HTML.', 'casa-vacanza-prenotazioni' ); ?></p>

	<table class="widefat striped cvp-shortcodes-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Shortcode', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'A cosa serve', 'casa-vacanza-prenotazioni' ); ?></th>
				<th><?php esc_html_e( 'Esempio', 'casa-vacanza-prenotazioni' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $shortcodes as $shortcode ) : ?>
				<tr>
					<td>
						<code class="cvp-shortcode-tag"><?php echo esc_html( $shortcode['code'] ); ?></code>
						<button type="button" class="button button-small cvp-copy-shortcode" data-copy="<?php echo esc_attr( $shortcode['example'] ); ?>">
							<?php esc_html_e( 'Copia', 'casa-vacanza-prenotazioni' ); ?>
						</button>
					</td>
					<td>
						<strong><?php echo esc_html( $shortcode['title'] ); ?></strong><br>
						<?php echo esc_html( $shortcode['description'] ); ?>
						<?php if ( ! empty( $shortcode['attributes'] ) ) : ?>
							<ul class="cvp-shortcode-attrs">
								<?php foreach ( $shortcode['attributes'] as $attr => $desc ) : ?>
									<li><code><?php echo esc_html( $attr ); ?></code> — <?php echo esc_html( $desc ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</td>
					<td><code><?php echo esc_html( $shortcode['example'] ); ?></code></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
