<?php
/**
 * Aggiornamenti plugin da GitHub Releases.
 *
 * @package CasaVacanzaPrenotazioni
 */

namespace CVP;

defined( 'ABSPATH' ) || exit;

class GitHub_Updater {

	const GITHUB_OWNER = 'evolofabio';
	const GITHUB_REPO  = 'casa-vacanza-prenotazioni';
	const CACHE_KEY    = 'cvp_github_release';
	const CACHE_TTL    = 43200;

	public static function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_pre_install', array( __CLASS__, 'block_git_install_upgrade' ), 10, 2 );
		add_filter( 'upgrader_post_install', array( __CLASS__, 'fix_install_directory' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'handle_force_check' ) );
		add_action( 'admin_notices', array( __CLASS__, 'git_update_admin_notice' ) );
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

		if ( ! $package && ! empty( $data['zipball_url'] ) ) {
			$package = $data['zipball_url'];
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
		if ( ! is_object( $transient ) || self::is_git_install() ) {
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
			'slug'        => self::get_plugin_folder(),
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
		if ( empty( $hook_extra['plugin'] ) || self::get_plugin_slug() !== $hook_extra['plugin'] ) {
			return $response;
		}

		if ( ! self::is_git_install() ) {
			return $response;
		}

		return new \WP_Error(
			'cvp_git_install',
			__( 'Questo plugin è installato via Git. Non usare "Aggiorna" da WordPress: esegui git pull nella cartella del plugin.', 'casa-vacanza-prenotazioni' )
		);
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

		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: 1: current version, 2: latest version */
			esc_html__( 'Casa Vacanza Prenotazioni: disponibile la versione %2$s (installata %1$s). Installazione Git rilevata: aggiorna con git pull, non da Plugin → Aggiorna.', 'casa-vacanza-prenotazioni' ),
			esc_html( CVP_VERSION ),
			esc_html( $remote['version'] )
		);
		echo '</p></div>';
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::get_plugin_folder() !== $args->slug ) {
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

	public static function fix_install_directory( $response, $hook_extra, $result ) {
		if ( empty( $hook_extra['plugin'] ) || self::get_plugin_slug() !== $hook_extra['plugin'] ) {
			return $response;
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		global $wp_filesystem;
		if ( empty( $result['destination'] ) || ! $wp_filesystem ) {
			return $response;
		}

		$destination = WP_PLUGIN_DIR . '/' . self::get_plugin_folder();
		$source      = $result['destination'];

		if ( $source !== $destination ) {
			// Non tentare di cancellare .git: rimuovi solo i file del plugin.
			if ( $wp_filesystem->exists( $destination ) && ! self::is_git_install() ) {
				$wp_filesystem->delete( $destination, true );
			}

			if ( $wp_filesystem->exists( $destination ) ) {
				self::merge_directory( $wp_filesystem, $source, $destination );
				$wp_filesystem->delete( $source, true );
			} else {
				$wp_filesystem->move( $source, $destination );
			}
		}

		if ( is_plugin_active( self::get_plugin_slug() ) ) {
			activate_plugin( self::get_plugin_slug() );
		}

		return $response;
	}

	/**
	 * Copia ricorsiva senza toccare .git.
	 *
	 * @param \WP_Filesystem_Base $filesystem Filesystem WP.
	 * @param string              $source     Sorgente.
	 * @param string              $dest       Destinazione.
	 */
	private static function merge_directory( $filesystem, $source, $dest ) {
		$items = $filesystem->dirlist( $source, true, false );
		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $name => $item ) {
			if ( '.git' === $name ) {
				continue;
			}

			$from = trailingslashit( $source ) . $name;
			$to   = trailingslashit( $dest ) . $name;

			if ( 'd' === ( $item['type'] ?? '' ) ) {
				if ( ! $filesystem->exists( $to ) ) {
					$filesystem->mkdir( $to );
				}
				self::merge_directory( $filesystem, $from, $to );
				continue;
			}

			if ( $filesystem->exists( $to ) ) {
				$filesystem->delete( $to );
			}
			$filesystem->copy( $from, $to );
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
