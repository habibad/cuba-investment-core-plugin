<?php
/**
 * Cuba Investment Core - Main Plugin Container
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Common\ThemeBridge;
use CubaInvestment\Core\Database\Migrations;
use CubaInvestment\Core\Auth\Roles;
use CubaInvestment\Core\PostTypes\OpportunityPostType;
use CubaInvestment\Core\PostTypes\OpportunityMetaBoxes;
use CubaInvestment\Core\API\ApiBootstrap;
use CubaInvestment\Core\Admin\AdminManager;
use CubaInvestment\Core\Auth\TemplateLoader;
use CubaInvestment\Core\Auth\RouteProtection;
use CubaInvestment\Core\Auth\FormHandler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    /**
     * Singleton instance
     *
     * @var Plugin|null
     */
    protected static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    protected function __construct() {
        $this->init_hooks();
    }

    /**
     * Register core WordPress hooks
     */
    protected function init_hooks() {
        // Core initialization
        add_action( 'init', [ $this, 'on_init' ] );

        // Auth Routing & Protection
        TemplateLoader::init();
        RouteProtection::init();
        FormHandler::init();

        // REST API
        add_action( 'rest_api_init', [ ApiBootstrap::class, 'register_routes' ] );

        // Admin & Meta Boxes
        if ( is_admin() ) {
            OpportunityMetaBoxes::register();
            AdminManager::init();
        }

        // Theme Bridge
        ThemeBridge::init();
    }

    /**
     * Runs on 'init'
     */
    public function on_init() {
        // Register Post Types & Taxonomies
        OpportunityPostType::register();
    }

    /**
     * Plugin Activation Routine
     */
    public static function activate() {
        // 1. Run Database Migrations
        Migrations::run();

        // 2. Register Roles & Capabilities
        Roles::register();

        // 3. Register CPT & Seed Taxonomies
        OpportunityPostType::register();
        OpportunityPostType::seed_default_terms();

        // 4. Flush Rewrite Rules
        flush_rewrite_rules();

        Logger::audit( 'plugin_activated', 'Cuba Investment Core plugin activated' );
    }

    /**
     * Plugin Deactivation Routine
     */
    public static function deactivate() {
        // Do NOT drop database tables or delete user data on simple deactivation!
        // Clean up rewrite rules
        flush_rewrite_rules();

        Logger::audit( 'plugin_deactivated', 'Cuba Investment Core plugin deactivated' );
    }
}
