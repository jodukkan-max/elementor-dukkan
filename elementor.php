<?php
/**
 * Plugin Name: Elementor Dukkan
 * Description: Drag and drop page builder, Atomic Editor, pixel perfect design, global and reusable style systems, and mobile responsive editing. A privately maintained build of Elementor with the upsell, telemetry and remote-API surface removed.
 * Plugin URI: https://github.com/jodukkan-max/elementor-dukkan
 * Update URI: https://github.com/jodukkan-max/elementor-dukkan
 * Version: 1.0.1
 * Author: jodukkan-max
 * Author URI: https://github.com/jodukkan-max
 * Requires PHP: 7.4
 * Requires at least: 6.6
 * Text Domain: elementor
 *
 * @package Elementor
 * @category Core
 *
 * This is a modified version of Elementor 4.1.1, originally by Elementor.com.
 * It is not affiliated with, endorsed by, or supported by Elementor Ltd.
 * See README.md for the full list of modifications.
 *
 * Elementor is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Elementor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ELEMENTOR_VERSION', '4.1.1' );

/**
 * Dukkan fork release number, deliberately separate from ELEMENTOR_VERSION.
 *
 * This is the value WordPress compares against GitHub release tags. ELEMENTOR_VERSION
 * stays frozen at the upstream base so third-party `version_compare( ELEMENTOR_VERSION, ... )`
 * checks keep their current answers and the upgrade manager never fires a DB migration.
 * Keep this in sync with the `Version:` header above.
 */
define( 'ELEMENTOR_DUKKAN_VERSION', '1.0.1' );

define( 'ELEMENTOR__FILE__', __FILE__ );
define( 'ELEMENTOR_PLUGIN_BASE', plugin_basename( ELEMENTOR__FILE__ ) );
define( 'ELEMENTOR_PATH', plugin_dir_path( ELEMENTOR__FILE__ ) );

if ( defined( 'ELEMENTOR_TESTS' ) && ELEMENTOR_TESTS ) {
	define( 'ELEMENTOR_URL', 'file://' . ELEMENTOR_PATH );
} else {
	define( 'ELEMENTOR_URL', plugins_url( '/', ELEMENTOR__FILE__ ) );
}

define( 'ELEMENTOR_MODULES_PATH', plugin_dir_path( ELEMENTOR__FILE__ ) . '/modules' );
define( 'ELEMENTOR_ASSETS_PATH', ELEMENTOR_PATH . 'assets/' );
define( 'ELEMENTOR_ASSETS_URL', ELEMENTOR_URL . 'assets/' );

if ( ! defined( 'ELEMENTOR_EDITOR_EVENTS_MIXPANEL_TOKEN' ) ) {
	define( 'ELEMENTOR_EDITOR_EVENTS_MIXPANEL_TOKEN', '150605b3b9f979922f2ac5a52e2dcfe9' );
}

// Loaded outside the module system and before the PHP/WP version gate below, so updates
// keep working even if the plugin itself declines to boot.
require_once ELEMENTOR_PATH . 'includes/dukkan-updater.php';
\Elementor\Dukkan_Updater::init();

if ( file_exists( ELEMENTOR_PATH . 'vendor/autoload.php' ) ) {
	require_once ELEMENTOR_PATH . 'vendor/autoload.php';
	// We need this file because of the DI\create function that we are using.
	// Autoload classmap doesn't include this file.
}

$deprecation_func_file = ELEMENTOR_PATH . 'vendor_prefixed/twig/symfony/deprecation-contracts/function.php';
if ( file_exists( $deprecation_func_file ) ) {
	require_once $deprecation_func_file;
	if ( ! function_exists( 'trigger_deprecation' ) ) {
		function trigger_deprecation( string $package, string $version, string $message, ...$args ): void {
			\ElementorDeps\trigger_deprecation( $package, $version, $message, ...$args );
		}
	}
}

if ( ! version_compare( PHP_VERSION, '7.4', '>=' ) ) {
	add_action( 'admin_notices', 'elementor_fail_php_version' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
	add_action( 'admin_notices', 'elementor_fail_wp_version' );
} else {
	require ELEMENTOR_PATH . 'includes/plugin.php';
}

/**
 * Elementor admin notice for minimum PHP version.
 *
 * Warning when the site doesn't have the minimum required PHP version.
 *
 * @since 1.0.0
 *
 * @return void
 */
function elementor_fail_php_version() {
	$html_message = sprintf(
		'<div class="error"><h3>%1$s</h3><p>%2$s <a href="https://go.elementor.com/wp-dash-update-php/" target="_blank">%3$s</a></p></div>',
		esc_html__( 'Elementor isn’t running because PHP is outdated.', 'elementor' ),
		sprintf(
			/* translators: %s: PHP version. */
			esc_html__( 'Update to version %s and get back to creating!', 'elementor' ),
			'7.4'
		),
		esc_html__( 'Show me how', 'elementor' )
	);

	echo wp_kses_post( $html_message );
}

/**
 * Elementor admin notice for minimum WordPress version.
 *
 * Warning when the site doesn't have the minimum required WordPress version.
 *
 * @since 1.5.0
 *
 * @return void
 */
function elementor_fail_wp_version() {
	$html_message = sprintf(
		'<div class="error"><h3>%1$s</h3><p>%2$s <a href="https://go.elementor.com/wp-dash-update-wordpress/" target="_blank">%3$s</a></p></div>',
		esc_html__( 'Elementor isn’t running because WordPress is outdated.', 'elementor' ),
		sprintf(
			/* translators: %s: WordPress version. */
			esc_html__( 'Update to version %s and get back to creating!', 'elementor' ),
			'6.5'
		),
		esc_html__( 'Show me how', 'elementor' )
	);

	echo wp_kses_post( $html_message );
}
