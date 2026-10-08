<?php
/**
 * Plugin Name: Cuba Investment Network Core
 * Plugin URI: https://cubainvestmentnetwork.com/
 * Description: Robust backend foundation for Cuba Investment Network: Investor & Business Owner accounts, opportunity listings, direct connections, private messaging, notifications, free launch entitlements, and secure REST APIs.
 * Version: 1.0.0
 * Author: Cuba Investment Network Development Team
 * Author URI: https://cubainvestmentnetwork.com/
 * Text Domain: cuba-investment-core
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package CubaInvestment\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Constants
define( 'CIN_VERSION', '1.0.0' );
define( 'CIN_PLUGIN_FILE', __FILE__ );
define( 'CIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader
require_once CIN_PLUGIN_DIR . 'includes/Autoloader.php';
\CubaInvestment\Core\Autoloader::register( CIN_PLUGIN_DIR . 'includes' );

// Global Template Helpers
require_once CIN_PLUGIN_DIR . 'includes/helpers.php';

// Activation & Deactivation Hooks
register_activation_hook( __FILE__, [ \CubaInvestment\Core\Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \CubaInvestment\Core\Plugin::class, 'deactivate' ] );

// Bootstrap Plugin
add_action( 'plugins_loaded', function() {
    \CubaInvestment\Core\Plugin::instance();
} );
