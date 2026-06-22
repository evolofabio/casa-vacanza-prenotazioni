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
		add_filter( 'upgrader_post_install', array( __CLASS__, 'fix_install_directory' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'handle_force_check' ) );
	}

	public static function get_plugin_slug() {
		return CVP_PLUGIN_BASENAME;
	}

	public static function get_repo_url() {
		return 'https://github.com/' . self::GITHUB_OWNER . '/' . self::GITHUB_REPO;
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

		$package = '';
		if ( ! empty( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $asset['name'] ?? '' ) ) {
					$package = $asset['browser_download_url'];
					break;
				}
			}
		}

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
			'slug'        => dirname( $slug ),
			'plugin'      => $slug,
			'new_version' => $remote['version'],
			'url'         => $remote['url'],
			'package'     => $remote['package'],
			'tested'      => get_bloginfo( 'version' ),
		);

		return $transient;
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( self::get_plugin_slug() ) !== $args->slug ) {
			return $result;
		}

		$remote = self::get_remote_release();
		if ( ! $remote ) {
			return $result;
		}

		$info                = new \stdClass();
		$info->name          = 'Casa Vacanza Prenotazioni';
		$info->slug          = dirname( self::get_plugin_slug() );
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

		global $wp_filesystem;
		if ( empty( $result['destination'] ) || ! $wp_filesystem ) {
			return $response;
		}

		$destination = WP_PLUGIN_DIR . '/' . dirname( self::get_plugin_slug() );
		if ( $result['destination'] !== $destination ) {
			if ( $wp_filesystem->exists( $destination ) ) {
				$wp_filesystem->delete( $destination, true );
			}
			$wp_filesystem->move( $result['destination'], $destination );
		}

		if ( is_plugin_active( self::get_plugin_slug() ) ) {
			activate_plugin( self::get_plugin_slug() );
		}

		return $response;
	}

	public static function get_update_status( $force_refresh = false ) {
		$remote = self::get_remote_release( $force_refresh );
		$status = array(
			'current'    => CVP_VERSION,
			'latest'     => $remote ? $remote['version'] : CVP_VERSION,
			'has_update' => $remote && version_compare( CVP_VERSION, $remote['version'], '<' ),
			'remote'     => $remote,
			'is_git'     => is_dir( CVP_PLUGIN_DIR . '.git' ),
		);
		return $status;
	}
}
