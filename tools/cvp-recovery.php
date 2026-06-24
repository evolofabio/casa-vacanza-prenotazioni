<?php
/**
 * Ripristino installazione Casa Vacanza Prenotazioni
 *
 * ISTRUZIONI:
 * 1. Carica questo file nella root di WordPress (stessa cartella di wp-config.php)
 * 2. Apri nel browser: https://tuosito.it/cvp-recovery.php
 * 3. Segui i passaggi indicati
 * 4. ELIMINA questo file subito dopo l'uso
 *
 * @package CasaVacanzaPrenotazioni
 */

// Carica WordPress.
$wp_load = __DIR__ . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	exit( 'wp-load.php non trovato. Metti questo file nella root di WordPress.' );
}
require_once $wp_load;

if ( ! is_user_logged_in() || ! current_user_can( 'activate_plugins' ) ) {
	wp_die( 'Devi essere loggato come amministratore per usare questo strumento.' );
}

$plugin_main   = 'casa-vacanza-prenotazioni/casa-vacanza-prenotazioni.php';
$plugin_dir    = WP_PLUGIN_DIR . '/casa-vacanza-prenotazioni';
$plugin_file   = $plugin_dir . '/casa-vacanza-prenotazioni.php';
$file_exists   = file_exists( $plugin_file );
$active        = (array) get_option( 'active_plugins', array() );
$was_active    = in_array( $plugin_main, $active, true );
$cleaned       = false;

if ( isset( $_GET['clean'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'cvp_recovery_clean' ) ) {
	$active = array_values(
		array_filter(
			$active,
			static function ( $path ) {
				return false === strpos( $path, 'casa-vacanza-prenotazioni' );
			}
		)
	);
	update_option( 'active_plugins', $active );
	$cleaned    = true;
	$was_active = false;
}

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="it">
<head>
	<meta charset="utf-8">
	<title>Casa Vacanza — Ripristino plugin</title>
	<style>
		body { font-family: system-ui, sans-serif; max-width: 720px; margin: 40px auto; padding: 0 20px; line-height: 1.5; }
		.ok { background: #ecfdf5; border: 1px solid #6ee7b7; padding: 12px; border-radius: 8px; }
		.err { background: #fef2f2; border: 1px solid #fca5a5; padding: 12px; border-radius: 8px; }
		.warn { background: #fffbeb; border: 1px solid #fcd34d; padding: 12px; border-radius: 8px; }
		code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
		.btn { display: inline-block; margin-top: 12px; padding: 10px 16px; background: #0f766e; color: #fff; text-decoration: none; border-radius: 8px; }
	</style>
</head>
<body>
	<h1>Casa Vacanza Prenotazioni — Ripristino</h1>

	<?php if ( $cleaned ) : ?>
		<div class="ok"><strong>OK:</strong> Riferimenti obsoleti rimossi da <code>active_plugins</code>.</div>
	<?php endif; ?>

	<h2>Stato attuale</h2>
	<ul>
		<li>Cartella plugin: <code><?php echo esc_html( $plugin_dir ); ?></code></li>
		<li>File principale: <?php echo $file_exists ? '<strong style="color:green">PRESENTE</strong>' : '<strong style="color:red">MANCANTE</strong>'; ?></li>
		<li>In active_plugins: <?php echo $was_active ? 'sì (ma file assente = errore)' : 'no'; ?></li>
	</ul>

	<?php if ( ! $file_exists ) : ?>
		<div class="err">
			<strong>Il file del plugin non esiste sul server.</strong><br>
			WordPress mostra l'errore perché il database punta ancora a un percorso eliminato.
		</div>

		<h2>Cosa fare (in ordine)</h2>
		<ol>
			<li>In <code>wp-content/plugins/</code> elimina tutte le cartelle <code>casa-vacanza-prenotazioni*</code> (anche <code>-main</code>, <code>-old</code>, ecc.)</li>
			<?php if ( $was_active || $cleaned ) : ?>
				<li>✓ Riferimento database già pulito o usa il pulsante sotto</li>
			<?php else : ?>
				<li>Pulisci il riferimento nel database (pulsante sotto)</li>
			<?php endif; ?>
			<li>Vai in <strong>Plugin → Aggiungi nuovo → Carica plugin</strong></li>
			<li>Carica lo zip dalla <a href="https://github.com/evolofabio/casa-vacanza-prenotazioni/releases/latest" target="_blank" rel="noopener">release GitHub v1.1.3</a> (non estrarre manualmente)</li>
			<li>Attiva il plugin</li>
			<li><strong>Elimina questo file</strong> (<code>cvp-recovery.php</code>) dalla root del sito</li>
		</ol>

		<?php if ( $was_active ) : ?>
			<a class="btn" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'clean', '1' ), 'cvp_recovery_clean' ) ); ?>">Pulisci riferimento nel database</a>
		<?php endif; ?>

	<?php else : ?>
		<div class="ok">
			<strong>Il plugin è installato correttamente.</strong><br>
			Vai in <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">Plugin</a> e attivalo se non lo è già.
		</div>
		<div class="warn">Elimina <code>cvp-recovery.php</code> dalla root del sito per sicurezza.</div>
	<?php endif; ?>

	<h2>Struttura corretta</h2>
	<pre>wp-content/plugins/casa-vacanza-prenotazioni/casa-vacanza-prenotazioni.php</pre>
</body>
</html>
