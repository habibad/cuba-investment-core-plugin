<?php
/**
 * Cuba Investment Core - Uninstall Procedure
 *
 * Runs when the plugin is deleted from the WordPress admin plugins screen.
 * Safely preserves data by default unless CIN_PURGE_DATA is explicitly defined.
 *
 * @package CubaInvestment\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Autoloader
require_once plugin_dir_path( __FILE__ ) . 'includes/Autoloader.php';
\CubaInvestment\Core\Autoloader::register( plugin_dir_path( __FILE__ ) . 'includes' );

// Check if data purge is explicitly desired
if ( defined( 'CIN_PURGE_DATA' ) && CIN_PURGE_DATA ) {
    \CubaInvestment\Core\Database\Migrations::drop_tables();
    \CubaInvestment\Core\Auth\Roles::unregister();
}
