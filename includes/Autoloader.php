<?php
/**
 * Cuba Investment Core - PSR-4 Autoloader
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autoloader {

    /**
     * Namespace prefix for the plugin
     *
     * @var string
     */
    protected static $prefix = 'CubaInvestment\\Core\\';

    /**
     * Base directory path for classes
     *
     * @var string
     */
    protected static $base_dir = '';

    /**
     * Register autoloader
     *
     * @param string $base_dir Absolute base directory for classes (typically includes/)
     */
    public static function register( $base_dir ) {
        self::$base_dir = trailingslashit( $base_dir );

        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    /**
     * Autoload callback
     *
     * @param string $class Fully qualified class name
     */
    public static function autoload( $class ) {
        $len = strlen( self::$prefix );

        // Ensure class uses this plugin's namespace prefix
        if ( strncmp( self::$prefix, $class, $len ) !== 0 ) {
            return;
        }

        // Relative class name
        $relative_class = substr( $class, $len );

        // Replace namespace separators with directory separators and append .php
        $file = self::$base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
