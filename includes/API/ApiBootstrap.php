<?php
/**
 * Cuba Investment Core - REST API Bootstrap
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\API;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\API\Controllers\AuthController;
use CubaInvestment\Core\API\Controllers\ProfileController;
use CubaInvestment\Core\API\Controllers\OpportunityController;
use CubaInvestment\Core\API\Controllers\InquiryController;
use CubaInvestment\Core\API\Controllers\ConnectionController;
use CubaInvestment\Core\API\Controllers\MessagingController;
use CubaInvestment\Core\API\Controllers\NotificationController;
use CubaInvestment\Core\API\Controllers\EntitlementController;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ApiBootstrap {

    /**
     * Register all REST API routes
     */
    public static function register_routes() {
        // Health check endpoint
        register_rest_route( Constants::API_NAMESPACE, '/health', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'health_check' ],
            'permission_callback' => '__return_true',
        ] );

        // Register Sub-Controllers
        AuthController::register_routes();
        ProfileController::register_routes();
        OpportunityController::register_routes();
        InquiryController::register_routes();
        ConnectionController::register_routes();
        MessagingController::register_routes();
        NotificationController::register_routes();
        EntitlementController::register_routes();
    }

    /**
     * System health and status endpoint
     */
    public static function health_check() {
        return rest_ensure_response( [
            'status'     => 'healthy',
            'plugin'     => 'cuba-investment-core',
            'version'    => Constants::VERSION,
            'db_version' => Constants::DB_VERSION,
            'timestamp'  => current_time( 'mysql' ),
        ] );
    }
}
