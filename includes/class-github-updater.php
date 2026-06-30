<?php
/**
 * Aggiornamenti plugin da GitHub Releases.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class GitHub_Updater {

	const GITHUB_OWNER   = 'evolofabio';
	const GITHUB_REPO    = 'casa-vacanza-prenotazioni';
	const PLUGIN_MAIN    = 'casa-vacanza-prenotazioni.php';
	const STANDARD_FOLDER = 'casa-vacanza-prenotazioni';
	const CACHE_KEY      = 'cvp_github_release';
	const CACHE_TTL      = 43200;

	public static function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_pre_install', array( __CLASS__, 'block_git_install_upgrade' ), 10, 2 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'fix_source_selection' ), 10, 4 );
		add_filter( 'upgrader_post_install', array( __CLASS__, 'verify_install' ), 10, 3 );
		add_filter( 'upgrader_package_options', array( __CLASS__, 'package_options' ) );
		add_filter( 'http_request_args', array( __CLASS__, 'github_download_args' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'handle_force_check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'git_update_admin_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'duplicate_install_notice' ) );
	}

	public static function get_plugin_slug() {
		return CVP_PLUGIN_BASENAME;
	}

	public static function get_plugin_folder() {
		return dirname( self::get_plugin_slug() );
	}

	public static function get_repo_url() {
		return 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO;
	}

	/**
	 * Percorso canonico del file principale del plugin.
	 *
	 * @return string
	 */
	public static function get_canonical_plugin_slug() {
		return self::STANDARD_FOLDER . '/' . self::PLUGIN_MAIN;
	}

	/**
	 * Verifica se il basename plugin appartiene a questo plugin.
	 *
	 * @param string $plugin_basename Basename plugin.
	 * @return bool
	 */
	public static function is_our_plugin_basename( $plugin_basename ) {
		return is_string( $plugin_basename ) && false !== strpos( $plugin_basename, self::PLUGIN_MAIN );
	}

	/**
	 * Timeout e header per download zip da GitHub.
	 *
	 * @param array  $args Argomenti richiesta HTTP.
	 * @param string $url  URL richiesto.
	 * @return array
	 */
	public static function github_download_args( $args, $url ) {
		if ( ! is_string( $url ) ) {
			return $args;
		}

		if (
			false === stripos( $url, 'github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO )
			&& false === stripos( $url, 'githubusercontent.com' )
		) {
			return $args;
		}

		$args['timeout']     = max( 60, (int) ( $args['timeout'] ?? 5 ) );
		$args['redirection'] = max( 5, (int) ( $args['redirection'] ?? 5 ) );

		if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$args['headers']['Accept']     = 'application/octet-stream';
		$args['headers']['User-Agent'] = 'Casa-Vacanza-Prenotazioni-WordPress/' . CVP_VERSION;

		return $args;
	}

	/**
	 * Opzioni installazione: evita abort se la cartella di destinazione non è vuota.
	 *
	 * @param array $options Opzioni upgrader.
	 * @return array
	 */
	public static function package_options( $options ) {
		if ( empty( $options['hook_extra']['plugin'] ) || ! self::is_our_plugin_basename( $options['hook_extra']['plugin'] ) ) {
			return $options;
		}

		$options['clear_destination']           = true;
		$options['abort_if_destination_exists'] = false;
		$options['clear_working']               = true;

		return $options;
	}

	/**
	 * Elenco installazioni plugin trovate in wp-content/plugins.
	 *
	 * @return array<string>
	 */
	public static function find_plugin_copies() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$copies = array();
		foreach ( get_plugins() as $plugin_file => $data ) {
			if ( false !== strpos( $plugin_file, self::PLUGIN_MAIN ) ) {
				$copies[] = $plugin_file;
			}
		}

		return $copies;
	}

	/**
	 * Avvisa se sono presenti più copie del plugin.
	 */
	public static function duplicate_install_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$copies = self::find_plugin_copies();
		if ( count( $copies ) < 2 ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>';
		esc_html_e( 'Casa Vacanza Prenotazioni: rilevate più copie del plugin.', 'casa-vacanza-prenotazioni' );
		echo '</strong> ';
		esc_html_e( 'Questo può mandare il sito in errore. Elimina le cartelle duplicate in wp-content/plugins e tieni solo casa-vacanza-prenotazioni attiva.', 'casa-vacanza-prenotazioni' );
		echo '</p><ul style="list-style:disc;padding-left:20px;">';
		foreach ( $copies as $plugin_file ) {
			echo '<li><code>' . esc_html( $plugin_file ) . '</code></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Installazione Git o symlink: l'updater WordPress non può sovrascrivere .git.
	 *
	 * @return bool
	 */
	public static function is_git_install() {
		$plugin_path = WP_PLUGIN_DIR . '/' . self::get_plugin_folder();

		if ( is_link( $plugin_path ) ) {
			return true;
		}

		if ( is_dir( CVP_PLUGIN_DIR . '.git' ) ) {
			return true;
		}

		$real = realpath( CVP_PLUGIN_DIR );
		if ( $real && is_dir( $real . '/.git' ) ) {
			return true;
		}

		return false;
	}

	public static function handle_force_check() {
		if ( ! isset( $_GET['cvp_check_updates'], $_GET['_wpnonce'] ) || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'cvp_check_updates' ) ) {
			return;
		}

		self::clear_cache();
		wp_safe_redirect( add_query_arg( array( 'page' => 'cvp-settings', 'cvp_update_check' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
	}

	public static function get_check_updates_url() {
		return wp_nonce_url( add_query_arg( 'cvp_check_updates', '1', admin_url( 'admin.php?page=cvp-settings' ) ), 'cvp_check_updates' );
	}

	public static function get_remote_release( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$release = self::fetch_latest_release() ?: self::fetch_latest_tag();
		if ( $release ) {
			set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );
		}

		return $release;
	}

	private static function fetch_latest_release() {
		$data = self::api_get( '/releases/latest' );
		if ( ! $data || empty( $data['tag_name'] ) ) {
			return null;
		}

		$package = self::pick_release_zip( $data['assets'] ?? array() );

		if ( ! $package ) {
			return null;
		}

		return array(
			'version'      => self::normalize_version( $data['tag_name'] ),
			'package'      => $package,
			'url'          => $data['html_url'] ?? self::get_repo_url(),
			'notes'        => $data['body'] ?? '',
			'release_date' => $data['published_at'] ?? '',
		);
	}

	/**
	 * Preferisce lo zip pulito allegato alla release (senza .git).
	 *
	 * @param array $assets Asset release GitHub.
	 * @return string
	 */
	private static function pick_release_zip( $assets ) {
		if ( empty( $assets ) || ! is_array( $assets ) ) {
			return '';
		}

		$preferred = '';
		$fallback  = '';

		foreach ( $assets as $asset ) {
			$name = $asset['name'] ?? '';
			$url  = $asset['browser_download_url'] ?? '';

			if ( ! $url || ! preg_match( '/\.zip$/i', $name ) ) {
				continue;
			}

			if ( false !== stripos( $name, 'casa-vacanza-prenotazioni' ) ) {
				$preferred = $url;
				break;
			}

			if ( ! $fallback ) {
				$fallback = $url;
			}
		}

		return $preferred ?: $fallback;
	}

	private static function fetch_latest_tag() {
		$data = self::api_get( '/tags?per_page=1' );
		if ( ! is_array( $data ) || empty( $data[0]['name'] ) ) {
			return null;
		}

		$tag = $data[0]['name'];
		return array(
			'version'      => self::normalize_version( $tag ),
			'package'      => self::get_repo_url() . '/archive/refs/tags/' . rawurlencode( $tag ) . '.zip',
			'url'          => self::get_repo_url() . '/releases/tag/' . rawurlencode( $tag ),
			'notes'        => '',
			'release_date' => '',
		);
	}

	private static function api_get( $endpoint ) {
		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO . $endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Casa-Vacanza-Prenotazioni-WordPress/' . CVP_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $body ) ? $body : null;
	}

	private static function normalize_version( $tag ) {
		return ltrim( trim( $tag ), 'vV' );
	}

	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$remote = self::get_remote_release();
		if ( ! $remote || empty( $remote['version'] ) || empty( $remote['package'] ) ) {
			return $transient;
		}

		if ( version_compare( CVP_VERSION, $remote['version'], '>=' ) ) {
			return $transient;
		}

		$slug = self::get_plugin_slug();
		$transient->response[ $slug ] = (object) array(
			'slug'        => self::STANDARD_FOLDER,
			'plugin'      => $slug,
			'new_version' => $remote['version'],
			'url'         => $remote['url'],
			'package'     => $remote['package'],
			'tested'      => get_bloginfo( 'version' ),
		);

		return $transient;
	}

	/**
	 * Blocca aggiornamento WordPress su installazioni Git.
	 *
	 * @param bool|WP_Error $response Risposta corrente.
	 * @param array         $hook_extra Dati hook.
	 * @return bool|WP_Error
	 */
	public static function block_git_install_upgrade( $response, $hook_extra ) {
		if ( empty( $hook_extra['plugin'] ) || ! self::is_our_plugin_basename( $hook_extra['plugin'] ) ) {
			return $response;
		}

		return $response;
	}

	public static function git_update_admin_notice() {
		if ( ! current_user_can( 'update_plugins' ) || ! self::is_git_install() ) {
			return;
		}

		$remote = self::get_remote_release();
		if ( ! $remote || ! version_compare( CVP_VERSION, $remote['version'], '<' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'toplevel_page_cvp-dashboard', 'casa-vacanza_page_cvp-settings' ), true ) ) {
			return;
		}

		echo '<div class="notice notice-info"><p>';
		printf(
			/* translators: 1: current version, 2: latest version */
			esc_html__( 'Casa Vacanza Prenotazioni: disponibile la versione %2$s (installata %1$s). Puoi aggiornare da Plugin → Aggiorna oppure con git pull nella cartella del plugin.', 'casa-vacanza-prenotazioni' ),
			esc_html( CVP_VERSION ),
			esc_html( $remote['version'] )
		);
		echo '</p></div>';
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}

		if ( self::STANDARD_FOLDER !== $args->slug && self::get_plugin_folder() !== $args->slug ) {
			return $result;
		}

		$remote = self::get_remote_release();
		if ( ! $remote ) {
			return $result;
		}

		$info                = new \stdClass();
		$info->name          = 'Casa Vacanza Prenotazioni';
		$info->slug          = self::get_plugin_folder();
		$info->version       = $remote['version'];
		$info->author        = '<a href="https://github.com/evolofabio">Evolo Digital Studio</a>';
		$info->homepage      = self::get_repo_url();
		$info->requires      = '6.0';
		$info->requires_php  = '7.4';
		$info->download_link = $remote['package'];
		$info->sections      = array(
			'description' => __( 'Sistema completo di prenotazioni per case vacanza.', 'casa-vacanza-prenotazioni' ),
			'changelog'   => $remote['notes'] ? wp_kses_post( $remote['notes'] ) : __( 'Vedi le note su GitHub.', 'casa-vacanza-prenotazioni' ),
		);

		return $info;
	}

	private static function find_plugin_root_native( $source ) {
		$source = trailingslashit( $source );

		if ( file_exists( $source . self::PLUGIN_MAIN ) ) {
			return untrailingslashit( $source );
		}

		$candidates = array(
			self::STANDARD_FOLDER,
			self::GITHUB_REPO,
			self::GITHUB_REPO . '-main',
			self::GITHUB_REPO . '-master',
		);

		foreach ( $candidates as $name ) {
			$path = $source . $name;
			if ( file_exists( trailingslashit( $path ) . self::PLUGIN_MAIN ) ) {
				return $path;
			}
		}

		if ( ! is_dir( untrailingslashit( $source ) ) ) {
			return '';
		}

		$items = scandir( untrailingslashit( $source ) );
		if ( ! is_array( $items ) ) {
			return '';
		}

		foreach ( $items as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}

			$path = $source . $name;
			if ( is_dir( $path ) && file_exists( trailingslashit( $path ) . self::PLUGIN_MAIN ) ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * Individua la cartella radice del plugin nello zip estratto.
	 *
	 * @param string              $source     Percorso estratto.
	 * @param \WP_Filesystem_Base $filesystem Filesystem WP.
	 * @return string
	 */
	private static function find_plugin_root( $source, $filesystem ) {
		$source = trailingslashit( $source );

		if ( $filesystem && $filesystem->exists( $source . self::PLUGIN_MAIN ) ) {
			return untrailingslashit( $source );
		}

		$candidates = array(
			self::STANDARD_FOLDER,
			self::GITHUB_REPO,
			self::GITHUB_REPO . '-main',
			self::GITHUB_REPO . '-master',
		);

		foreach ( $candidates as $name ) {
			$path = $source . $name;
			if ( $filesystem && $filesystem->exists( trailingslashit( $path ) . self::PLUGIN_MAIN ) ) {
				return $path;
			}
		}

		if ( $filesystem ) {
			$list = $filesystem->dirlist( untrailingslashit( $source ), false, false );
			if ( is_array( $list ) ) {
				foreach ( $list as $name => $item ) {
					if ( 'd' !== ( $item['type'] ?? '' ) ) {
						continue;
					}

					$path = $source . $name;
					if ( $filesystem->exists( trailingslashit( $path ) . self::PLUGIN_MAIN ) ) {
						return $path;
					}
				}
			}
		}

		return self::find_plugin_root_native( $source );
	}

	/**
	 * Rinomina la cartella estratta per allinearla alla destinazione WordPress.
	 *
	 * @param string                    $plugin_root  Percorso radice plugin nello zip.
	 * @param string                    $dest_folder  Nome cartella destinazione.
	 * @param \WP_Filesystem_Base|null  $filesystem   Filesystem WP.
	 * @return string
	 */
	private static function align_plugin_folder_name( $plugin_root, $dest_folder, $filesystem ) {
		$dest_folder = sanitize_file_name( $dest_folder );
		if ( ! $dest_folder || basename( $plugin_root ) === $dest_folder ) {
			return $plugin_root;
		}

		$dest_path = trailingslashit( dirname( $plugin_root ) ) . $dest_folder;

		if ( $filesystem && $filesystem->move( $plugin_root, $dest_path ) ) {
			return $dest_path;
		}

		if ( @rename( $plugin_root, $dest_path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return $dest_path;
		}

		return $plugin_root;
	}

	/**
	 * Verifica se lo zip estratto contiene questo plugin.
	 *
	 * @param string $source Percorso estratto.
	 * @return bool
	 */
	private static function archive_contains_plugin( $source ) {
		return (bool) self::find_plugin_root_native( $source );
	}

	/**
	 * Allinea active_plugins al percorso canonico se i file sono nella cartella standard.
	 */
	private static function maybe_normalize_active_plugin_path() {
		$canonical = self::get_canonical_plugin_slug();
		$current   = self::get_plugin_slug();

		if ( $current === $canonical ) {
			return;
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $canonical ) ) {
			return;
		}

		$active = (array) get_option( 'active_plugins', array() );
		$changed = false;

		foreach ( $active as $index => $plugin ) {
			if ( self::is_our_plugin_basename( $plugin ) && $plugin !== $canonical ) {
				$active[ $index ] = $canonical;
				$changed          = true;
			}
		}

		if ( $changed ) {
			update_option( 'active_plugins', array_values( array_unique( $active ) ) );
		}
	}

	/**
	 * Cerca il file principale del plugin in wp-content/plugins.
	 *
	 * @return string Percorso assoluto o stringa vuota.
	 */
	private static function find_any_installed_main_file() {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return '';
		}

		$matches = glob( WP_PLUGIN_DIR . '/*/' . self::PLUGIN_MAIN );
		if ( empty( $matches ) || ! is_array( $matches ) ) {
			return '';
		}

		foreach ( $matches as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return '';
	}

	/**
	 * Sposta l'installazione nella cartella canonica casa-vacanza-prenotazioni.
	 *
	 * @param array $result Risultato installazione upgrader.
	 */
	private static function migrate_to_canonical_folder( $result ) {
		if ( empty( $result['destination'] ) || ! defined( 'WP_PLUGIN_DIR' ) ) {
			return;
		}

		$destination   = wp_normalize_path( $result['destination'] );
		$canonical_dir = wp_normalize_path( WP_PLUGIN_DIR . '/' . self::STANDARD_FOLDER );

		if ( $destination === $canonical_dir ) {
			return;
		}

		if ( ! file_exists( trailingslashit( $destination ) . self::PLUGIN_MAIN ) ) {
			return;
		}

		if ( is_dir( $canonical_dir ) ) {
			if ( is_dir( $canonical_dir . '/.git' ) ) {
				return;
			}
			self::delete_plugin_folder( $canonical_dir );
		}

		if ( @rename( $destination, $canonical_dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			self::maybe_normalize_active_plugin_path();
		}
	}

	/**
	 * Punta WordPress alla sottocartella corretta dello zip prima dell'installazione.
	 *
	 * @param string                           $source         Sorgente proposta.
	 * @param string                           $remote_source  Sorgente remota.
	 * @param \Plugin_Upgrader|\WP_Upgrader    $upgrader       Upgrader.
	 * @param array                            $hook_extra     Dati hook.
	 * @return string|\WP_Error
	 */
	public static function fix_source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
		$is_our_update = ! empty( $hook_extra['plugin'] ) && self::is_our_plugin_basename( $hook_extra['plugin'] );
		$is_our_upload = empty( $hook_extra['plugin'] ) && self::archive_contains_plugin( $source );

		if ( ! $is_our_update && ! $is_our_upload ) {
			return $source;
		}

		global $wp_filesystem;

		$plugin_root = self::find_plugin_root( $source, $wp_filesystem );
		if ( ! $plugin_root ) {
			return new \WP_Error(
				'cvp_bad_zip_structure',
				__( 'Zip GitHub non valido: usa il file casa-vacanza-prenotazioni.zip allegato alla release (non il codice sorgente).', 'casa-vacanza-prenotazioni' )
			);
		}

		$dest_folder = self::STANDARD_FOLDER;

		return self::align_plugin_folder_name( $plugin_root, $dest_folder, $wp_filesystem );
	}

	/**
	 * Verifica che l'aggiornamento abbia lasciato il plugin nella cartella attesa.
	 *
	 * @param bool|\WP_Error $response   Risposta corrente.
	 * @param array          $hook_extra Dati hook.
	 * @param array          $result     Risultato installazione.
	 * @return bool|\WP_Error
	 */
	public static function verify_install( $response, $hook_extra, $result ) {
		$is_our_update = ! empty( $hook_extra['plugin'] ) && self::is_our_plugin_basename( $hook_extra['plugin'] );
		$is_our_upload = empty( $hook_extra['plugin'] ) && ! empty( $result['destination'] ) && file_exists( trailingslashit( $result['destination'] ) . self::PLUGIN_MAIN );

		if ( ! $is_our_update && ! $is_our_upload ) {
			return $response;
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		self::migrate_to_canonical_folder( $result );

		$paths = array(
			WP_PLUGIN_DIR . '/' . self::get_canonical_plugin_slug(),
			WP_PLUGIN_DIR . '/' . self::get_plugin_slug(),
			self::find_any_installed_main_file(),
		);

		if ( ! empty( $result['destination'] ) ) {
			$paths[] = trailingslashit( $result['destination'] ) . self::PLUGIN_MAIN;
		}

		foreach ( array_filter( array_unique( $paths ) ) as $plugin_file ) {
			if ( file_exists( $plugin_file ) ) {
				self::maybe_normalize_active_plugin_path();
				self::cleanup_stale_install_folders();
				return $response;
			}
		}

		return $response;
	}

	/**
	 * Rimuove cartelle obsolete create da zip GitHub (es. *-main) dopo aggiornamento riuscito.
	 */
	private static function cleanup_stale_install_folders() {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return;
		}

		$active_folder = self::get_plugin_folder();
		$patterns      = array(
			self::GITHUB_REPO . '-main',
			self::GITHUB_REPO . '-master',
			self::GITHUB_REPO . '-old',
			self::GITHUB_REPO . '-OFF',
		);

		foreach ( $patterns as $folder ) {
			if ( $folder === $active_folder ) {
				continue;
			}

			$path = WP_PLUGIN_DIR . '/' . $folder;
			if ( ! is_dir( $path ) ) {
				continue;
			}

			$main_file = $path . '/' . self::PLUGIN_MAIN;
			if ( ! file_exists( $main_file ) ) {
				continue;
			}

			// Non cancellare installazioni Git attive.
			if ( is_dir( $path . '/.git' ) ) {
				continue;
			}

			self::delete_plugin_folder( $path );
		}
	}

	/**
	 * Elimina ricorsivamente una cartella plugin (solo file, senza toccare altre directory).
	 *
	 * @param string $path Percorso assoluto.
	 */
	private static function delete_plugin_folder( $path ) {
		if ( ! is_dir( $path ) ) {
			return;
		}

		$items = scandir( $path );
		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$full = $path . '/' . $item;
			if ( is_dir( $full ) ) {
				self::delete_plugin_folder( $full );
				continue;
			}

			if ( function_exists( 'wp_delete_file' ) ) {
				wp_delete_file( $full );
			} elseif ( is_writable( $full ) ) {
				unlink( $full );
			}
		}

		@rmdir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Migra cartelle con nome errato (es. *-main) alla cartella canonica.
	 */
	public static function maybe_migrate_install_directory() {
		if ( ! defined( 'WP_PLUGIN_DIR' ) || ! defined( 'CVP_PLUGIN_DIR' ) ) {
			return;
		}

		if ( self::get_plugin_folder() === self::STANDARD_FOLDER ) {
			return;
		}

		$canonical_dir = WP_PLUGIN_DIR . '/' . self::STANDARD_FOLDER;
		$current_dir   = wp_normalize_path( CVP_PLUGIN_DIR );

		if ( ! file_exists( trailingslashit( $current_dir ) . self::PLUGIN_MAIN ) ) {
			return;
		}

		if ( is_dir( $canonical_dir ) ) {
			return;
		}

		if ( @rename( untrailingslashit( $current_dir ), $canonical_dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			self::maybe_normalize_active_plugin_path();
		}
	}

	public static function get_update_status( $force_refresh = false ) {
		$remote = self::get_remote_release( $force_refresh );

		return array(
			'current'    => CVP_VERSION,
			'latest'     => $remote ? $remote['version'] : CVP_VERSION,
			'has_update' => $remote && version_compare( CVP_VERSION, $remote['version'], '<' ),
			'remote'     => $remote,
			'is_git'     => self::is_git_install(),
		);
	}
}
