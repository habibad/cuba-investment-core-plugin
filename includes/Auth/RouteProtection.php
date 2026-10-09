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
        add_filter( 'wp_authenticate_user', [ __CLASS__, 'filter_authenticate_suspended_user' ], 20, 2 );
        add_filter( 'rest_authentication_errors', [ __CLASS__, 'filter_rest_suspended_user' ], 20 );
        add_action( 'init', [ __CLASS__, 'enforce_ajax_suspension' ], 5 );
    }

    /**
     * Prevent suspended accounts from authenticating via WordPress standard login
     */
    public static function filter_authenticate_suspended_user( $user, $password ) {
        if ( is_wp_error( $user ) ) {
            return $user;
        }

        $status = get_user_meta( $user->ID, '_cin_account_status', true );
        if ( 'suspended' === $status || 'disabled' === $status ) {
            return new \WP_Error(
                'account_suspended',
                __( 'Your account access has been suspended. Please contact platform support for assistance.', 'cuba-investment-core' )
            );
        }

        return $user;
    }

    /**
     * Block authenticated REST API calls from suspended users
     */
    public static function filter_rest_suspended_user( $result ) {
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $status  = get_user_meta( $user_id, '_cin_account_status', true );
            if ( 'suspended' === $status || 'disabled' === $status ) {
                return new \WP_Error(
                    'rest_account_suspended',
                    __( 'Your account has been suspended. Protected API access is forbidden.', 'cuba-investment-core' ),
                    [ 'status' => 403 ]
                );
            }
        }

        return $result;
    }

    /**
     * Block AJAX calls from suspended accounts
     */
    public static function enforce_ajax_suspension() {
        if ( wp_doing_ajax() && is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $status  = get_user_meta( $user_id, '_cin_account_status', true );
            if ( 'suspended' === $status || 'disabled' === $status ) {
                wp_send_json_error( [
                    'message' => __( 'Your account has been suspended.', 'cuba-investment-core' ),
                ], 403 );
            }
        }
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
            'dashboard-investor'      => 'investor/dashboard',
            'investor-profile'        => 'investor/profile',
            'saved-opportunities'     => 'investor/saved-opportunities',
            'enquiries-investor'      => 'investor/enquiries',
            'connections-investor'    => 'investor/connections',
            'messages-investor'       => 'investor/messages',
            'dashboard-business'      => 'business-owner/dashboard',
            'business-owner-profile'  => 'business-owner/profile',
            'business-profile'        => 'business-owner/business-profile',
            'opportunities'           => 'business-owner/opportunities',
            'opportunity-create'      => 'business-owner/opportunities/create',
            'enquiries-business'      => 'business-owner/enquiries',
            'connections-business'    => 'business-owner/connections',
            'messages-business'       => 'business-owner/messages',
            'dashboard-messages'      => 'dashboard/messages',
            'dashboard-connections'   => 'dashboard/connections',
        ];
        if ( isset( $route_alias_map[ $route ] ) ) {
            $route = $route_alias_map[ $route ];
        }

        $is_logged_in = is_user_logged_in();
        $user_id      = get_current_user_id();

        // 0. Handle Email Verification Callback Route
        if ( 'verify-email' === $route ) {
            $token = '';
            if ( ! empty( $_GET['token'] ) ) {
                $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
            } elseif ( ! empty( $_GET['amp;token'] ) ) {
                $token = sanitize_text_field( wp_unslash( $_GET['amp;token'] ) );
            } elseif ( ! empty( $_GET['#038;token'] ) ) {
                $token = sanitize_text_field( wp_unslash( $_GET['#038;token'] ) );
            }

            $uid = 0;
            if ( ! empty( $_GET['uid'] ) ) {
                $uid = absint( $_GET['uid'] );
            } elseif ( ! empty( $_GET['amp;uid'] ) ) {
                $uid = absint( $_GET['amp;uid'] );
            } elseif ( ! empty( $_GET['#038;uid'] ) ) {
                $uid = absint( $_GET['#038;uid'] );
            }

            if ( ! empty( $token ) ) {
                $result = EmailVerification::verify( $uid, $token );
                EmailVerification::$last_result = $result;

                if ( true === $result ) {
                    wp_safe_redirect( add_query_arg( [ 'verified' => 'success' ], home_url( '/login/' ) ) );
                    exit;
                }

                if ( 'already_verified' === $result ) {
                    wp_safe_redirect( add_query_arg( [ 'verified' => 'already' ], home_url( '/login/' ) ) );
                    exit;
                }
            }
        }

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
            'investor/dashboard',
            'investor/profile',
            'dashboard/investor/profile',
            'investor/saved-opportunities',
            'dashboard/investor/saved-opportunities',
            'investor/enquiries',
            'dashboard/investor/enquiries',
            'investor/connections',
            'dashboard/investor/connections',
            'investor/messages',
            'dashboard/investor/messages',
            'dashboard/business',
            'business-owner/dashboard',
            'business-owner/profile',
            'dashboard/business/profile',
            'business-owner/business-profile',
            'dashboard/business/business-profile',
            'business-owner/opportunities',
            'business-owner/opportunities/create',
            'dashboard/business/opportunities',
            'dashboard/business/opportunities/create',
            'business-owner/enquiries',
            'dashboard/business/enquiries',
            'business-owner/connections',
            'dashboard/business/connections',
            'business-owner/messages',
            'dashboard/business/messages',
            'dashboard/messages',
            'dashboard/connections',
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

            // Role-based authorization for dashboards & profiles
            $user     = get_userdata( $user_id );
            $roles    = (array) $user->roles;
            $is_admin = in_array( 'administrator', $roles, true );
            $is_inv   = in_array( Constants::ROLE_INVESTOR, $roles, true );
            $is_biz   = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );

            // Route: Generic /dashboard/ -> dispatch to proper role dashboard
            if ( 'dashboard' === $route ) {
                wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                exit;
            }

            if ( 'dashboard/messages' === $route ) {
                $dest = $is_inv ? home_url( '/investor/messages/' ) : home_url( '/business-owner/messages/' );
                wp_safe_redirect( $dest );
                exit;
            }

            if ( 'dashboard/connections' === $route ) {
                $dest = $is_inv ? home_url( '/investor/connections/' ) : home_url( '/business-owner/connections/' );
                wp_safe_redirect( $dest );
                exit;
            }

            // Investor Routes
            $investor_routes = [
                'dashboard/investor',
                'investor/dashboard',
                'investor/profile',
                'dashboard/investor/profile',
                'investor/saved-opportunities',
                'dashboard/investor/saved-opportunities',
                'investor/enquiries',
                'dashboard/investor/enquiries',
                'investor/connections',
                'dashboard/investor/connections',
                'investor/messages',
                'dashboard/investor/messages',
            ];
            if ( in_array( $route, $investor_routes, true ) ) {
                if ( ! $is_inv && ! $is_admin ) {
                    // Unauthorized role! Redirect to their own dashboard
                    wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                    exit;
                }
            }

            // Business Owner Routes
            $business_routes = [
                'dashboard/business',
                'business-owner/dashboard',
                'business-owner/profile',
                'dashboard/business/profile',
                'business-owner/business-profile',
                'dashboard/business/business-profile',
                'business-owner/opportunities',
                'business-owner/opportunities/create',
                'dashboard/business/opportunities',
                'dashboard/business/opportunities/create',
                'business-owner/enquiries',
                'dashboard/business/enquiries',
                'business-owner/connections',
                'dashboard/business/connections',
                'business-owner/messages',
                'dashboard/business/messages',
            ];
            if ( in_array( $route, $business_routes, true ) ) {
                if ( ! $is_biz && ! $is_admin ) {
                    // Unauthorized role! Redirect to their own dashboard
                    wp_safe_redirect( AuthManager::get_user_dashboard_url( $user ) );
                    exit;
                }
            }
        }
    }
}
