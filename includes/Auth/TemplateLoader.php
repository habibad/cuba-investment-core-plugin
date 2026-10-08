<?php
/**
 * Cuba Investment Core - Authentication & Dashboard Template Loader
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TemplateLoader {

    public static function init() {
        add_filter( 'query_vars', [ __CLASS__, 'register_query_vars' ] );
        add_action( 'init', [ __CLASS__, 'add_rewrite_rules' ] );
        add_filter( 'template_include', [ __CLASS__, 'route_templates' ], 90 );
        add_filter( 'pre_get_document_title', [ __CLASS__, 'filter_document_title' ], 15 );

        // Suppress default WordPress Admin Bar on portal/dashboard routes & for non-admins
        add_filter( 'show_admin_bar', [ __CLASS__, 'filter_show_admin_bar' ], 999 );
        add_action( 'template_redirect', [ __CLASS__, 'disable_admin_bar_on_portal' ], 1 );
    }

    public static function register_query_vars( $vars ) {
        $vars[] = 'cin_auth_page';
        return $vars;
    }

    public static function add_rewrite_rules() {
        // Authentication routes
        add_rewrite_rule( '^join-network/?$', 'index.php?cin_auth_page=join-network', 'top' );
        add_rewrite_rule( '^register/investor/?$', 'index.php?cin_auth_page=register-investor', 'top' );
        add_rewrite_rule( '^register/business-owner/?$', 'index.php?cin_auth_page=register-business-owner', 'top' );
        add_rewrite_rule( '^login/?$', 'index.php?cin_auth_page=login', 'top' );
        add_rewrite_rule( '^logout/?$', 'index.php?cin_auth_page=logout', 'top' );
        add_rewrite_rule( '^verify-email/?$', 'index.php?cin_auth_page=verify-email', 'top' );
        add_rewrite_rule( '^forgot-password/?$', 'index.php?cin_auth_page=forgot-password', 'top' );
        add_rewrite_rule( '^reset-password/?$', 'index.php?cin_auth_page=reset-password', 'top' );

        // Investor Dashboard & Profile routes
        add_rewrite_rule( '^investor/dashboard/?$', 'index.php?cin_auth_page=dashboard-investor', 'top' );
        add_rewrite_rule( '^investor/profile/?$', 'index.php?cin_auth_page=investor-profile', 'top' );
        add_rewrite_rule( '^dashboard/investor/profile/?$', 'index.php?cin_auth_page=investor-profile', 'top' );
        add_rewrite_rule( '^dashboard/investor/?$', 'index.php?cin_auth_page=dashboard-investor', 'top' );

        // Business Owner Dashboard & Profile routes
        add_rewrite_rule( '^business-owner/dashboard/?$', 'index.php?cin_auth_page=dashboard-business', 'top' );
        add_rewrite_rule( '^business-owner/profile/?$', 'index.php?cin_auth_page=business-owner-profile', 'top' );
        add_rewrite_rule( '^business-owner/business-profile/?$', 'index.php?cin_auth_page=business-profile', 'top' );
        add_rewrite_rule( '^dashboard/business/profile/?$', 'index.php?cin_auth_page=business-owner-profile', 'top' );
        add_rewrite_rule( '^dashboard/business/business-profile/?$', 'index.php?cin_auth_page=business-profile', 'top' );
        add_rewrite_rule( '^dashboard/business/?$', 'index.php?cin_auth_page=dashboard-business', 'top' );

        // Generic Dashboard & Account routes
        add_rewrite_rule( '^dashboard/?$', 'index.php?cin_auth_page=dashboard', 'top' );
        add_rewrite_rule( '^account/?$', 'index.php?cin_auth_page=account', 'top' );
    }

    /**
     * Intercept and load custom templates
     *
     * @param string $template
     * @return string
     */
    public static function route_templates( $template ) {
        global $wp_query;

        $route = get_query_var( 'cin_auth_page' );

        // Fallback detection from request URI if rewrites not flushed yet
        if ( empty( $route ) ) {
            $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
            $site_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
            if ( ! empty( $site_path ) && 0 === strpos( $path, $site_path ) ) {
                $path = trim( substr( $path, strlen( $site_path ) ), '/' );
            }

            $valid_routes = [
                'join-network',
                'register/investor'                   => 'register-investor',
                'register/business-owner'            => 'register-business-owner',
                'login',
                'verify-email',
                'forgot-password',
                'reset-password',
                'investor/dashboard'                 => 'dashboard-investor',
                'investor/profile'                   => 'investor-profile',
                'dashboard/investor'                 => 'dashboard-investor',
                'dashboard/investor/profile'         => 'investor-profile',
                'business-owner/dashboard'            => 'dashboard-business',
                'business-owner/profile'             => 'business-owner-profile',
                'business-owner/business-profile'    => 'business-profile',
                'dashboard/business'                 => 'dashboard-business',
                'dashboard/business/profile'         => 'business-owner-profile',
                'dashboard/business/business-profile'=> 'business-profile',
                'dashboard',
                'account',
            ];

            if ( isset( $valid_routes[ $path ] ) ) {
                $route = $valid_routes[ $path ];
            } elseif ( in_array( $path, $valid_routes, true ) ) {
                $route = $path;
            }
        }

        if ( empty( $route ) ) {
            return $template;
        }

        // Fix WordPress query state (ensure 200 OK, not 404)
        if ( is_404() ) {
            $wp_query->is_404  = false;
            $wp_query->is_page = true;
            status_header( 200 );
        }

        // Map route to template file
        $template_map = [
            'join-network'            => 'auth/join-network.php',
            'register-investor'       => 'auth/register-investor.php',
            'register-business-owner' => 'auth/register-business-owner.php',
            'login'                   => 'auth/login.php',
            'verify-email'            => 'auth/verify-email.php',
            'forgot-password'         => 'auth/forgot-password.php',
            'reset-password'          => 'auth/reset-password.php',
            'dashboard-investor'      => 'dashboard/dashboard-investor.php',
            'investor-profile'        => 'dashboard/investor-profile.php',
            'dashboard-business'      => 'dashboard/dashboard-business.php',
            'business-owner-profile'  => 'dashboard/business-owner-profile.php',
            'business-profile'        => 'dashboard/business-profile.php',
            'account'                 => 'dashboard/account.php',
        ];

        if ( ! isset( $template_map[ $route ] ) ) {
            return $template;
        }

        $rel_path = $template_map[ $route ];

        // 1. Check for theme override in child/parent theme
        $theme_file = locate_template( [
            "cuba-investment/{$rel_path}",
            $rel_path,
        ] );

        if ( ! empty( $theme_file ) ) {
            return $theme_file;
        }

        // 2. Load plugin bundled template
        $plugin_file = CIN_PLUGIN_DIR . 'templates/' . $rel_path;
        if ( file_exists( $plugin_file ) ) {
            return $plugin_file;
        }

        return $template;
    }

    /**
     * Filter document title for custom auth & dashboard pages
     *
     * @param string $title
     * @return string
     */
    public static function filter_document_title( $title ) {
        $route = get_query_var( 'cin_auth_page' );
        if ( empty( $route ) ) {
            $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
            $site_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
            if ( ! empty( $site_path ) && 0 === strpos( $path, $site_path ) ) {
                $path = trim( substr( $path, strlen( $site_path ) ), '/' );
            }
            $route = str_replace( '/', '-', $path );
        }

        $titles = [
            'join-network'            => __( 'Join Cuba Investment Network', 'cuba-investment-core' ),
            'register-investor'       => __( 'Investor Registration — Cuba Investment Network', 'cuba-investment-core' ),
            'register-business-owner' => __( 'Business Owner Registration — Cuba Investment Network', 'cuba-investment-core' ),
            'login'                   => __( 'Sign In to Portal — Cuba Investment Network', 'cuba-investment-core' ),
            'verify-email'            => __( 'Email Verification — Cuba Investment Network', 'cuba-investment-core' ),
            'forgot-password'         => __( 'Forgot Password — Cuba Investment Network', 'cuba-investment-core' ),
            'reset-password'          => __( 'Reset Password — Cuba Investment Network', 'cuba-investment-core' ),
            'dashboard-investor'      => __( 'Investor Portal — Cuba Investment Network', 'cuba-investment-core' ),
            'investor-profile'        => __( 'Investor Profile — Cuba Investment Network', 'cuba-investment-core' ),
            'dashboard-business'      => __( 'Business Owner Portal — Cuba Investment Network', 'cuba-investment-core' ),
            'business-owner-profile'  => __( 'Personal Profile — Cuba Investment Network', 'cuba-investment-core' ),
            'business-profile'        => __( 'Business Profile — Cuba Investment Network', 'cuba-investment-core' ),
            'account'                 => __( 'Account Settings — Cuba Investment Network', 'cuba-investment-core' ),
        ];

        if ( isset( $titles[ $route ] ) ) {
            return $titles[ $route ] . ' | ' . get_bloginfo( 'name' );
        }

        return $title;
    }

    /**
     * Check if current request matches any portal, dashboard, or auth route
     *
     * @return bool
     */
    public static function is_portal_or_auth_route() {
        $route = get_query_var( 'cin_auth_page' );
        if ( ! empty( $route ) ) {
            return true;
        }

        $path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
        $site_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( ! empty( $site_path ) && 0 === strpos( $path, $site_path ) ) {
            $path = trim( substr( $path, strlen( $site_path ) ), '/' );
        }

        $portal_prefixes = [
            'investor',
            'business-owner',
            'dashboard',
            'account',
            'join-network',
            'register',
            'login',
            'logout',
            'verify-email',
            'forgot-password',
            'reset-password',
        ];

        foreach ( $portal_prefixes as $prefix ) {
            if ( $path === $prefix || 0 === strpos( $path, $prefix . '/' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter show_admin_bar to hide on portal/dashboard routes and for non-admins
     *
     * @param bool $show
     * @return bool
     */
    public static function filter_show_admin_bar( $show ) {
        if ( is_admin() ) {
            return $show;
        }

        // Never show admin bar to non-administrators anywhere on the site
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        // Never show admin bar on dashboard or portal pages for anyone (even Admin)
        if ( self::is_portal_or_auth_route() ) {
            return false;
        }

        return $show;
    }

    /**
     * Disarm admin bar rendering and margin bump on portal routes
     */
    public static function disable_admin_bar_on_portal() {
        if ( is_admin() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) || self::is_portal_or_auth_route() ) {
            show_admin_bar( false );
            add_filter( 'show_admin_bar', '__return_false', 999 );
            remove_action( 'wp_head', '_admin_bar_bump_cb' );
            remove_action( 'wp_head', 'wp_admin_bar_header' );
            remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
            remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
            add_filter( 'body_class', function( $classes ) {
                return array_diff( $classes, [ 'admin-bar' ] );
            }, 999 );
        }
    }
}

