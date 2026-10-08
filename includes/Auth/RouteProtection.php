<?php
/**
 * Cuba Investment Core - Route Protection & Authorization Middleware
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RouteProtection {

    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'handle_route_protection' ], 5 );
    }

    /**
     * Intercept request and enforce authentication / role-based authorization
     */
    public static function handle_route_protection() {
        // Determine current route from query var or URL path
        $route = get_query_var( 'cin_auth_page' );
        if ( empty( $route ) ) {
            $path  = trim( parse_url( add_query_arg( [] ), PHP_URL_PATH ), '/' );
            $site_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
            if ( ! empty( $site_path ) && 0 === strpos( $path, $site_path ) ) {
                $path = trim( substr( $path, strlen( $site_path ) ), '/' );
            }
            $route = $path;
        }

        // Normalize hyphenated query var routes to slashed format
        $route_alias_map = [
            'register-investor'       => 'register/investor',
            'register-business-owner' => 'register/business-owner',
            'dashboard-investor'      => 'dashboard/investor',
            'dashboard-business'      => 'dashboard/business',
        ];
        if ( isset( $route_alias_map[ $route ] ) ) {
            $route = $route_alias_map[ $route ];
        }

        $is_logged_in = is_user_logged_in();
        $user_id      = get_current_user_id();

        // 1. Handle Logout Route
        if ( 'logout' === $route ) {
            AuthManager::logout();
            exit;
        }

        // 2. Logged-in user visiting public guest auth routes: redirect to their dashboard
        $guest_only_routes = [
            'login',
            'join-network',
            'register/investor',
            'register/business-owner',
            'forgot-password',
            'reset-password',
        ];

        if ( $is_logged_in && in_array( $route, $guest_only_routes, true ) ) {
            $dest = AuthManager::get_user_dashboard_url( $user_id );
            wp_safe_redirect( $dest );
            exit;
        }

        // 3. Protected Dashboard & Account Routes
        $protected_routes = [
            'dashboard',
            'dashboard/investor',
            'dashboard/business',
            'account',
        ];

        if ( in_array( $route, $protected_routes, true ) ) {
            // Must be logged in
            if ( ! $is_logged_in ) {
                $redirect_to = home_url( '/' . $route . '/' );
                wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), home_url( '/login/' ) ) );
                exit;
            }

            // Check account status
            $status = get_user_meta( $user_id, '_cin_account_status', true );
            if ( 'pending_verification' === $status ) {
                wp_safe_redirect( add_query_arg( 'notice', 'pending', home_url( '/verify-email/' ) ) );
                exit;
            }

            if ( 'suspended' === $status || 'disabled' === $status ) {
                wp_logout();
                wp_safe_redirect( add_query_arg( 'error', 'suspended', home_url( '/login/' ) ) );
                exit;
            }

            // Role-based authorization for dashboards
            $user  = get_userdata( $user_id );
            $roles = (array) $user->roles;
            $is_admin = in_array( 'administrator', $roles, true );

            // Route: Generic /dashboard/ -> dispatch to proper role dashboard
            if ( 'dashboard' === $route ) {
                wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                exit;
            }

            // Route: Investor Dashboard
            if ( 'dashboard/investor' === $route ) {
                if ( ! in_array( Constants::ROLE_INVESTOR, $roles, true ) && ! $is_admin ) {
                    // Wrong role! Redirect to their own dashboard
                    wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                    exit;
                }
            }

            // Route: Business Owner Dashboard
            if ( 'dashboard/business' === $route ) {
                if ( ! in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true ) && ! $is_admin ) {
                    // Wrong role! Redirect to their own dashboard
                    wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                    exit;
                }
            }
        }
    }
}
