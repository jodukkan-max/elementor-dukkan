<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Serves plugin updates from GitHub releases instead of wordpress.org.
 *
 * The plugin folder is `elementor`, which collides with the wordpress.org slug of the
 * same name. The `Update URI` header in elementor.php is what stops the wordpress.org
 * API from answering for this plugin at all; this class supplies the replacement via
 * the `update_plugins_{hostname}` filter that the same header enables.
 */
class Dukkan_Updater {

	const OWNER = 'jodukkan-max';

	const REPO = 'elementor-dukkan';

	/**
	 * The release asset to install.
	 *
	 * Must not be GitHub's auto-generated source zip: that one unpacks to a
	 * `elementor-dukkan-1.0.0/` directory, which would move the plugin out of
	 * `elementor/` and deactivate it mid-update.
	 */
	const ASSET_NAME = 'elementor.zip';

	/**
	 * Deliberately not `elementor`. This slug is what the "View details" link queries,
	 * and `elementor` would make WordPress fetch the real plugin's page from wordpress.org.
	 */
	const SLUG = 'elementor-dukkan';

	const CACHE_KEY = 'elementor_dukkan_release';

	public static function init() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function __construct() {
		add_filter( 'update_plugins_github.com', [ $this, 'check_for_update' ], 10, 3 );
		add_filter( 'plugins_api', [ $this, 'plugin_details' ], 20, 3 );
		add_filter( 'site_transient_update_plugins', [ $this, 'block_dotorg_package' ] );
		add_action( 'upgrader_process_complete', [ $this, 'flush_cache' ], 10, 0 );
	}

	/**
	 * Answers the update check for this plugin.
	 *
	 * Returns the release data whether or not it is newer; core compares it against the
	 * installed header version and files it under `response` or `no_update` itself.
	 *
	 * @param array|false $update      Update data from a previous filter callback.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 *
	 * @return array|false
	 */
	public function check_for_update( $update, $plugin_data, $plugin_file ) {
		if ( ELEMENTOR_PLUGIN_BASE !== $plugin_file ) {
			return $update;
		}

		if ( ! empty( $update ) ) {
			return $update;
		}

		$release = $this->get_release();

		// Without a package there is nothing installable, so stay silent rather than
		// showing an update row that cannot complete.
		if ( empty( $release['version'] ) || empty( $release['package'] ) ) {
			return $update;
		}

		return [
			'slug' => self::SLUG,
			'version' => $release['version'],
			'url' => $release['url'],
			'package' => $release['package'],
			'requires_php' => '7.4',
		];
	}

	/**
	 * Populates the "View details" modal, which would otherwise be empty or, worse,
	 * fall through to wordpress.org.
	 *
	 * @param false|object|array $result
	 * @param string             $action
	 * @param object             $args
	 *
	 * @return false|object|array
	 */
	public function plugin_details( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();

		if ( empty( $release['version'] ) ) {
			return $result;
		}

		return (object) [
			'name' => 'Elementor Dukkan',
			'slug' => self::SLUG,
			'version' => $release['version'],
			'author' => '<a href="https://github.com/' . self::OWNER . '">' . self::OWNER . '</a>',
			'homepage' => $this->get_repo_url(),
			'download_link' => $release['package'],
			'trunk' => $release['package'],
			'requires' => '6.6',
			'requires_php' => '7.4',
			'last_updated' => $release['published'],
			'sections' => [
				'description' => 'A privately maintained build of Elementor with the upsell, telemetry and remote-API surface removed. Not affiliated with Elementor Ltd.',
				'changelog' => $this->format_changelog( $release ),
			],
		];
	}

	/**
	 * Last line of defence against a wordpress.org package being installed over this fork.
	 *
	 * The `Update URI` header should already prevent wordpress.org from answering, and the
	 * two in-plugin injectors (Beta_Testers and Canary_Deployment) are disabled. This drops
	 * anything that still slips through.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function block_dotorg_package( $transient ) {
		if ( empty( $transient ) || ! is_object( $transient ) || empty( $transient->response[ ELEMENTOR_PLUGIN_BASE ] ) ) {
			return $transient;
		}

		$item = $transient->response[ ELEMENTOR_PLUGIN_BASE ];
		$package = is_object( $item ) ? ( isset( $item->package ) ? $item->package : '' ) : ( isset( $item['package'] ) ? $item['package'] : '' );

		if ( is_string( $package ) && false !== strpos( $package, 'downloads.wordpress.org' ) ) {
			unset( $transient->response[ ELEMENTOR_PLUGIN_BASE ] );
		}

		return $transient;
	}

	public function flush_cache() {
		delete_site_transient( self::CACHE_KEY );
	}

	/**
	 * Fetches the latest release, cached for 6 hours.
	 *
	 * Unauthenticated GitHub API calls are limited to 60 per hour per IP, so this is
	 * cached rather than hit on every update check. Failures are cached briefly too, so
	 * an unreachable API does not add a network round trip to every admin page load.
	 *
	 * @return array
	 */
	private function get_release() {
		if ( ! $this->is_forced_check() ) {
			$cached = get_site_transient( self::CACHE_KEY );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
			[
				'timeout' => 10,
				'headers' => [
					'Accept' => 'application/vnd.github+json',
					// GitHub rejects API requests that do not identify themselves.
					'User-Agent' => 'ElementorDukkan/' . ELEMENTOR_DUKKAN_VERSION . ' (' . home_url( '/' ) . ')',
				],
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_site_transient( self::CACHE_KEY, [], 15 * MINUTE_IN_SECONDS );

			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['tag_name'] ) || ! is_array( $body ) ) {
			set_site_transient( self::CACHE_KEY, [], 15 * MINUTE_IN_SECONDS );

			return [];
		}

		$release = [
			'version' => ltrim( $body['tag_name'], 'vV' ),
			'url' => isset( $body['html_url'] ) ? $body['html_url'] : $this->get_repo_url(),
			'package' => $this->find_asset( $body ),
			'notes' => isset( $body['body'] ) ? $body['body'] : '',
			'published' => isset( $body['published_at'] ) ? $body['published_at'] : '',
		];

		set_site_transient( self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * @param array $body Decoded release payload.
	 *
	 * @return string Download URL of the release asset, or an empty string.
	 */
	private function find_asset( $body ) {
		if ( empty( $body['assets'] ) || ! is_array( $body['assets'] ) ) {
			return '';
		}

		foreach ( $body['assets'] as $asset ) {
			if ( isset( $asset['name'], $asset['browser_download_url'] ) && self::ASSET_NAME === $asset['name'] ) {
				return $asset['browser_download_url'];
			}
		}

		return '';
	}

	/**
	 * Whether the user pressed "Check again" on the updates screen, in which case the
	 * 6 hour cache should be bypassed.
	 *
	 * @return bool
	 */
	private function is_forced_check() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only, only bypasses a cache.
		return ! empty( $_GET['force-check'] );
	}

	private function get_repo_url() {
		return 'https://github.com/' . self::OWNER . '/' . self::REPO;
	}

	private function format_changelog( $release ) {
		if ( empty( $release['notes'] ) ) {
			return '<p>See the <a href="' . esc_url( $this->get_repo_url() ) . '/releases">releases page</a>.</p>';
		}

		return wpautop( wp_kses_post( $release['notes'] ) );
	}
}
