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
    }

    public static function register_query_vars( $vars ) {
        $vars[] = 'cin_auth_page';
        return $vars;
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule( '^join-network/?$', 'index.php?cin_auth_page=join-network', 'top' );
        add_rewrite_rule( '^register/investor/?$', 'index.php?cin_auth_page=register-investor', 'top' );
        add_rewrite_rule( '^register/business-owner/?$', 'index.php?cin_auth_page=register-business-owner', 'top' );
        add_rewrite_rule( '^login/?$', 'index.php?cin_auth_page=login', 'top' );
        add_rewrite_rule( '^logout/?$', 'index.php?cin_auth_page=logout', 'top' );
        add_rewrite_rule( '^verify-email/?$', 'index.php?cin_auth_page=verify-email', 'top' );
        add_rewrite_rule( '^forgot-password/?$', 'index.php?cin_auth_page=forgot-password', 'top' );
        add_rewrite_rule( '^reset-password/?$', 'index.php?cin_auth_page=reset-password', 'top' );
        add_rewrite_rule( '^dashboard/investor/?$', 'index.php?cin_auth_page=dashboard-investor', 'top' );
        add_rewrite_rule( '^dashboard/business/?$', 'index.php?cin_auth_page=dashboard-business', 'top' );
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
                'register/investor'       => 'register-investor',
                'register/business-owner' => 'register-business-owner',
                'login',
                'verify-email',
                'forgot-password',
                'reset-password',
                'dashboard/investor'      => 'dashboard-investor',
                'dashboard/business'      => 'dashboard-business',
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
            'dashboard-business'      => 'dashboard/dashboard-business.php',
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
            'dashboard-business'      => __( 'Business Owner Portal — Cuba Investment Network', 'cuba-investment-core' ),
            'account'                 => __( 'Account Settings — Cuba Investment Network', 'cuba-investment-core' ),
        ];

        if ( isset( $titles[ $route ] ) ) {
            return $titles[ $route ] . ' | ' . get_bloginfo( 'name' );
        }

        return $title;
    }
}
