<?php
/**
 * Template: Business Owner Dashboard Overview
 * Route: /business-owner/dashboard/ (and /dashboard/business/)
 *
 * Implements 4-column summary metric cards with real database counts,
 * dynamic business profile completion tracking, quick actions, and recent activity.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();
$first_name = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$business   = ProfileService::get_business_profile( $user->ID );
$personal   = ProfileService::get_business_owner_profile( $user->ID );
$metrics    = ProfileService::get_business_metrics( $user->ID );
$activities = ProfileService::get_recent_activity( $user->ID, 5 );

$page_title = __( 'Business Overview', 'cuba-investment-core' );
$topbar_cta = [
    'label' => __( 'Manage Business', 'cuba-investment-core' ),
    'url'   => home_url( '/business-owner/business-profile/' ),
    'icon'  => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>',
];

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-8">

        <!-- Page Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php echo esc_html( sprintf( __( 'Welcome Back, %s', 'cuba-investment-core' ), $first_name ) ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your business profile and prepare your opportunities for potential investors.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/business-owner/profile/' ) ); ?>" class="btn btn-outline btn-sm font-semibold border-slate-300 text-slate-700 hover:border-primary hover:text-primary transition-colors">
                    <span><?php esc_html_e( 'Personal Profile', 'cuba-investment-core' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs">
                    <span><?php esc_html_e( 'Business Profile', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- 4-Column Summary Cards (Real DB Metrics Only) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- 1. My Opportunities -->
            <?php
            $icon_opps = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
            $args = [
                'title'       => __( 'My Opportunities', 'cuba-investment-core' ),
                'value'       => $metrics['my_opportunities'],
                'note'        => __( 'Draft & submitted deals', 'cuba-investment-core' ),
                'icon_svg'    => $icon_opps,
                'color_class' => 'emerald',
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 2. Published Listings -->
            <?php
            $icon_pub = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            $args = [
                'title'       => __( 'Published Listings', 'cuba-investment-core' ),
                'value'       => $metrics['published_listings'],
                'note'        => __( 'Active on portal marketplace', 'cuba-investment-core' ),
                'icon_svg'    => $icon_pub,
                'color_class' => 'blue',
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 3. Investor Enquiries -->
            <?php
            $icon_inq = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>';
            $args = [
                'title'       => __( 'Investor Enquiries', 'cuba-investment-core' ),
                'value'       => $metrics['investor_enquiries'],
                'note'        => __( 'Prospective investor enquiries', 'cuba-investment-core' ),
                'icon_svg'    => $icon_inq,
                'color_class' => 'amber',
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 4. Active Connections -->
            <?php
            $icon_conn = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
            $args = [
                'title'       => __( 'Active Connections', 'cuba-investment-core' ),
                'value'       => $metrics['active_connections'],
                'note'        => __( 'Direct investor connections', 'cuba-investment-core' ),
                'icon_svg'    => $icon_conn,
                'color_class' => 'purple',
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>
        </div>

        <!-- Profile Completion Widget (Dynamic %) -->
        <?php
        $args = [
            'completion'  => $business['completion'],
            'profile_url' => home_url( '/business-owner/business-profile/' ),
            'title'       => __( 'Business Profile Completion', 'cuba-investment-core' ),
            'description' => __( 'A complete profile with company overview, stage, and partnership interests builds trust with international investors.', 'cuba-investment-core' ),
        ];
        include CIN_PLUGIN_DIR . 'templates/dashboard/components/completion-card.php';
        ?>

        <!-- Two-Column Grid: Quick Actions & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">

            <!-- Quick Action Cards -->
            <div class="space-y-4">
                <h3 class="text-base sm:text-lg font-heading font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span><?php esc_html_e( 'Quick Actions', 'cuba-investment-core' ); ?></span>
                </h3>

                <!-- Action 1: Manage Business Profile -->
                <div class="card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary flex items-center justify-center shrink-0 border border-primary-100">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Manage Business Profile', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed mb-4">
                                <?php esc_html_e( 'Update your enterprise name, Cuban province, operating stage, legal structure, company logo, and partnership interests.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                        <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs">
                            <?php esc_html_e( 'Edit Business Profile &rarr;', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </div>

                <!-- Action 2: Personal Profile -->
                <div class="card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-accent-50 text-accent flex items-center justify-center shrink-0 border border-accent-100">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Personal Profile & Contact', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed mb-4">
                                <?php esc_html_e( 'Manage your founder credentials, personal biography, verified contact telephone, and individual photo.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                        <a href="<?php echo esc_url( home_url( '/business-owner/profile/' ) ); ?>" class="btn btn-outline btn-sm font-semibold text-primary border-primary hover:bg-primary hover:text-white">
                            <?php esc_html_e( 'Edit Personal Profile &rarr;', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Panel -->
            <div>
                <?php
                $args = [
                    'activities' => $activities,
                    'title'      => __( 'Recent Business Activity', 'cuba-investment-core' ),
                ];
                include CIN_PLUGIN_DIR . 'templates/dashboard/components/activity-panel.php';
                ?>
            </div>

        </div>

        <!-- Business Summary Card -->
        <?php if ( ! empty( $business['company_name'] ) ) : ?>
            <div class="card bg-white p-6 sm:p-7 rounded-2xl border border-slate-200/80 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <?php if ( ! empty( $business['company_logo_url'] ) ) : ?>
                            <img src="<?php echo esc_url( $business['company_logo_url'] ); ?>" alt="Logo" class="w-12 h-12 rounded-xl object-cover border border-slate-200">
                        <?php else : ?>
                            <div class="w-12 h-12 rounded-xl bg-slate-100 text-primary font-bold text-base flex items-center justify-center border border-slate-200">
                                <?php echo esc_html( strtoupper( substr( $business['company_name'], 0, 2 ) ) ); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="text-base font-heading font-bold text-slate-900"><?php echo esc_html( $business['company_name'] ); ?></h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <?php echo esc_html( ucfirst( str_replace( '_', ' ', $business['company_sector'] ) ) ); ?> · <?php echo esc_html( $business['company_location_province'] ); ?> · <?php echo esc_html( ucfirst( $business['company_stage'] ) ); ?>
                            </p>
                        </div>
                    </div>

                    <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="text-xs font-bold text-primary hover:underline">
                        <?php esc_html_e( 'Manage Enterprise Details &rarr;', 'cuba-investment-core' ); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Membership & Verification Status Card -->
        <div class="card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">
                            <?php esc_html_e( 'Membership: Early Access Launch Tier', 'cuba-investment-core' ); ?>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( '100% Free · Verified account status · Standard marketplace listing rights', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        ✓ <?php esc_html_e( 'Email Verified', 'cuba-investment-core' ); ?>
                    </span>
                    <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="text-xs font-bold text-primary hover:underline ml-2">
                        <?php esc_html_e( 'Account Details &rarr;', 'cuba-investment-core' ); ?>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
