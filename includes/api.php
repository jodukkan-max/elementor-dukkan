<?php
namespace Elementor;

use Elementor\Core\Common\Modules\Connect\Apps\Library;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor API.
 *
 * Elementor API handler class is responsible for communicating with Elementor
 * remote servers retrieving templates data and to send uninstall feedback.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * Elementor library option key.
	 */
	const LIBRARY_OPTION_KEY = 'elementor_remote_info_library';

	/**
	 * Elementor feed option key.
	 */
	const FEED_OPTION_KEY = 'elementor_remote_info_feed_data';

	const TRANSIENT_KEY_PREFIX = 'elementor_remote_info_api_data_';

	/**
	 * API info URL.
	 *
	 * Holds the URL of the info API.
	 *
	 * @access public
	 * @static
	 *
	 * @var string API info URL. (v2 excludes the Library info)
	 */
	public static $api_info_url = 'https://my.elementor.com/api/v2/info/';

	/**
	 * API feedback URL.
	 *
	 * Holds the URL of the feedback API.
	 *
	 * @access private
	 * @static
	 *
	 * @var string API feedback URL.
	 */
	private static $api_feedback_url = 'https://my.elementor.com/api/v1/feedback/';

	private static $api_library_info_url = 'https://my.elementor.com/api/v1/templates/info/';

	private static function get_info_data( $force_update = false, $additional_status = false ) {
		return [];
	}

	public static function get_site_key() {
		if ( null === Plugin::$instance->common ) {
			return get_option( Library::OPTION_CONNECT_SITE_KEY );
		}

		/** @var Library $library */
		$library = Plugin::$instance->common->get_component( 'connect' )->get_app( 'library' );

		if ( ! $library || ! method_exists( $library, 'get_site_key' ) ) {
			return false;
		}

		return $library->get_site_key();
	}

	/**
	 * Get upgrade notice.
	 *
	 * Retrieve the upgrade notice if one exists, or false otherwise.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return array|false Upgrade notice, or false none exist.
	 */
	public static function get_upgrade_notice() {
		$data = self::get_info_data();

		if ( empty( $data['upgrade_notice'] ) ) {
			return false;
		}

		return $data['upgrade_notice'];
	}

	public static function get_admin_notice() {
		$data = self::get_info_data();
		if ( empty( $data['admin_notice'] ) ) {
			return false;
		}
		return $data['admin_notice'];
	}

	public static function get_canary_deployment_info( $force = false ) {
		$data = self::get_info_data( $force );

		if ( empty( $data['canary_deployment'] ) ) {
			return false;
		}

		return $data['canary_deployment'];
	}

	public static function get_promotion_widgets() {
		return [];
	}

	/**
	 * Get templates data.
	 *
	 * Retrieve the templates data from a remote server.
	 *
	 * @since 2.0.0
	 * @access public
	 * @static
	 *
	 * @param bool $force_update Optional. Whether to force the data update or
	 *                                     not. Default is false.
	 *
	 * @return array The templates' data.
	 */
	public static function get_library_data( bool $force_update = false ): array {
		$library_data = get_option( self::LIBRARY_OPTION_KEY );

		if ( ! empty( $library_data ) && is_array( $library_data ) && isset( $library_data['types_data'] ) ) {
			return $library_data;
		}

		// Must stay non-empty: Manager::get_library_data() bails before reading local templates on an empty array.
		return [ 'types_data' => [] ];
	}

	/**
	 * Get feed data.
	 *
	 * Retrieve the feed info data from remote elementor server.
	 *
	 * @since 1.9.0
	 * @access public
	 * @static
	 *
	 * @param bool $force_update Optional. Whether to force the data update or
	 *                                     not. Default is false.
	 *
	 * @return array Feed data.
	 */
	public static function get_feed_data( $force_update = false ) {
		self::get_info_data( $force_update );

		$feed = get_option( self::FEED_OPTION_KEY );

		if ( empty( $feed ) ) {
			return [];
		}

		return $feed;
	}

	public static function get_deactivation_data() {
		$data = self::get_info_data( true, 'deactivated' );

		if ( empty( $data['deactivate_data'] ) ) {
			return false;
		}

		return $data['deactivate_data'];
	}

	public static function get_uninstalled_data() {
		$data = self::get_info_data( true, 'uninstalled' );

		if ( empty( $data['uninstall_data'] ) ) {
			return false;
		}

		return $data['uninstall_data'];
	}

	/**
	 * Get template content.
	 *
	 * Retrieve the templates content received from a remote server.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param int $template_id The template ID.
	 *
	 * @return object|\WP_Error The template content.
	 */
	public static function get_template_content( $template_id ) {
		/** @var Library $library */
		$library = Plugin::$instance->common->get_component( 'connect' )->get_app( 'library' );

		return $library->get_template_content( $template_id );
	}

	/**
	 * Send Feedback.
	 *
	 * Fires a request to Elementor server with the feedback data.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $feedback_key  Feedback key.
	 * @param string $feedback_text Feedback text.
	 *
	 * @return array The response of the request.
	 */
	public static function send_feedback( $feedback_key, $feedback_text ) {
		return wp_remote_post( self::$api_feedback_url, [
			'timeout' => 30,
			'body' => [
				'api_version' => ELEMENTOR_VERSION,
				'site_lang' => get_bloginfo( 'language' ),
				'feedback_key' => $feedback_key,
				'feedback' => $feedback_text,
			],
		] );
	}

	/**
	 * Ajax reset API data.
	 *
	 * Reset Elementor library API data using an ajax call.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_reset_api_data() {
		check_ajax_referer( 'elementor_reset_library', '_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		self::get_info_data( true );

		wp_send_json_success();
	}

	/**
	 * Init.
	 *
	 * Initialize Elementor API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		add_action( 'wp_ajax_elementor_reset_library', [ __CLASS__, 'ajax_reset_api_data' ] );
	}
}
