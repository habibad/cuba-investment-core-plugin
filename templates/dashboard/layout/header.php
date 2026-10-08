<?php
/**
 * Dashboard Layout - Standalone Header
 *
 * @package CubaInvestment\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();

// Unconditionally suppress WordPress default admin bar and margin bump on dashboard pages
show_admin_bar( false );
add_filter( 'show_admin_bar', '__return_false', 999 );
remove_action( 'wp_head', '_admin_bar_bump_cb' );
remove_action( 'wp_head', 'wp_admin_bar_header' );
remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
add_filter( 'body_class', function( $classes ) {
    return array_diff( $classes, [ 'admin-bar' ] );
}, 999 );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="h-full bg-slate-50">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <style>
        /* Force remove WordPress default Admin Bar & margin offset */
        #wpadminbar {
            display: none !important;
        }
        html, html.h-full, body, body.admin-bar {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        /* Standalone dashboard core styling enhancements */
        :root {
            --cin-primary: #0A2540;
            --cin-accent: #00875A;
            --cin-accent-hover: #006644;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0F172A;
            background-color: #F8FAFC;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0A2540;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .dashboard-collapsed .sidebar-desktop {
            width: 5rem;
        }
        .dashboard-collapsed .sidebar-text,
        .dashboard-collapsed .sidebar-badge {
            display: none !important;
        }
        .dashboard-collapsed .main-content-area {
            padding-left: 5rem !important;
        }
        .dashboard-collapsed .sidebar-nav-item {
            justify-content: center;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
    </style>
</head>
<body <?php body_class( 'h-full bg-slate-50/75 text-slate-800 font-sans antialiased flex flex-col selection:bg-accent selection:text-white' ); ?>>
<?php wp_body_open(); ?>

<!-- Accessibility Skip Link -->
<a href="#dashboard-main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none">
    <?php esc_html_e( 'Skip to dashboard content', 'cuba-investment-core' ); ?>
</a>

<div id="dashboard-app" class="min-h-screen flex flex-col bg-slate-50/75">
