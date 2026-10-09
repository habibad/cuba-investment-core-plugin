<?php
/**
 * Template: Investor Dashboard Overview
 * Route: /investor/dashboard/ (and /dashboard/investor/)
 *
 * Implements the 4-column summary metric cards with real database counts,
 * dynamic profile completion tracking, quick action cards, and real activity feed.
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
$profile    = ProfileService::get_investor_profile( $user->ID );
$metrics    = ProfileService::get_investor_metrics( $user->ID );
$activities = ProfileService::get_recent_activity( $user->ID, 5 );
$is_email_verified = (bool) get_user_meta( $user->ID, '_cin_email_verified', true );
$account_status    = get_user_meta( $user->ID, '_cin_account_status', true ) ?: 'active';
$membership_tier   = get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $user->ID, '_cin_membership_status', true ) ?: 'active';

// Set Topbar Parameters
$page_title = __( 'Investor Overview', 'cuba-investment-core' );
$topbar_cta = [
    'label' => __( 'Edit Profile', 'cuba-investment-core' ),
    'url'   => home_url( '/investor/profile/' ),
    'icon'  => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>',
];

// Include Standalone Header
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
                    <?php esc_html_e( 'Manage your investor profile and explore opportunities connected to Cuba.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="btn btn-outline btn-sm font-semibold border-slate-300 text-slate-700 hover:border-primary hover:text-primary transition-colors">
                    <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span><?php esc_html_e( 'Explore Deals', 'cuba-investment-core' ); ?></span>
                </a>
                <a href="<?php echo esc_url( home_url( '/investor/profile/' ) ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs">
                    <span><?php esc_html_e( 'Manage Profile', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- 4-Column Summary Cards (Real DB Metrics Only) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- 1. Available Opportunities -->
            <?php
            $icon_opps = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>';
            $args = [
                'title'       => __( 'Available Opportunities', 'cuba-investment-core' ),
                'value'       => $metrics['available_opportunities'],
                'note'        => __( 'Published business opportunities', 'cuba-investment-core' ),
                'icon_svg'    => $icon_opps,
                'color_class' => 'emerald',
                'link'        => home_url( '/invest/' ),
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 2. Saved Opportunities -->
            <?php
            $icon_saved = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>';
            $args = [
                'title'       => __( 'Saved Opportunities', 'cuba-investment-core' ),
                'value'       => $metrics['saved_opportunities'],
                'note'        => __( 'Bookmarked deal flow', 'cuba-investment-core' ),
                'icon_svg'    => $icon_saved,
                'color_class' => 'blue',
                'link'        => home_url( '/investor/saved-opportunities/' ),
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 3. Enquiries Sent -->
            <?php
            $icon_inq = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>';
            $args = [
                'title'       => __( 'Enquiries Sent', 'cuba-investment-core' ),
                'value'       => $metrics['enquiries_sent'],
                'note'        => __( 'Direct founder enquiries', 'cuba-investment-core' ),
                'icon_svg'    => $icon_inq,
                'color_class' => 'amber',
                'link'        => home_url( '/investor/enquiries/' ),
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>

            <!-- 4. Active Connections -->
            <?php
            $icon_conn = '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
            $args = [
                'title'       => __( 'Active Connections', 'cuba-investment-core' ),
                'value'       => $metrics['active_connections'],
                'note'        => __( 'Approved introductions', 'cuba-investment-core' ),
                'icon_svg'    => $icon_conn,
                'color_class' => 'purple',
                'link'        => home_url( '/investor/connections/' ),
            ];
            include CIN_PLUGIN_DIR . 'templates/dashboard/components/stats-card.php';
            ?>
        </div>

        <!-- Profile Completion Widget (Dynamic %) -->
        <?php
        $args = [
            'completion'  => $profile['completion'],
            'profile_url' => home_url( '/investor/profile/' ),
            'title'       => __( 'Investor Profile Completion', 'cuba-investment-core' ),
            'description' => __( 'Complete your investment preferences, capital parameters, and focus sectors to receive curated matches.', 'cuba-investment-core' ),
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

                <!-- Grid of Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Action 1: Edit & Complete Profile -->
                    <div class="card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary flex items-center justify-center shrink-0 border border-primary-100 mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Investor Profile', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                <?php esc_html_e( 'Manage investment preferences, preferred sectors, and capital parameters.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <a href="<?php echo esc_url( home_url( '/investor/profile/' ) ); ?>" class="text-xs font-bold text-primary hover:text-accent">
                                <?php esc_html_e( 'Edit / Complete Profile &rarr;', 'cuba-investment-core' ); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Action 2: Account Settings -->
                    <div class="card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-700 flex items-center justify-center shrink-0 border border-slate-200 mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Account Settings', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                <?php esc_html_e( 'Configure personal settings, display language, and email notifications.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="text-xs font-bold text-primary hover:text-accent">
                                <?php esc_html_e( 'Manage Account &rarr;', 'cuba-investment-core' ); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Action 3: Change Password -->
                    <div class="card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200 mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Security & Password', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                <?php esc_html_e( 'Update your account login password and credential security settings.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <a href="<?php echo esc_url( home_url( '/account/#security' ) ); ?>" class="text-xs font-bold text-primary hover:text-accent">
                                <?php esc_html_e( 'Change Password &rarr;', 'cuba-investment-core' ); ?>
                            </a>
                        </div>
                    </div>

                    <!-- Action 4: Explore Opportunities -->
                    <div class="card bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-accent-50 text-accent flex items-center justify-center shrink-0 border border-accent-100 mb-3">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-heading font-bold text-slate-900 mb-1">
                                <?php esc_html_e( 'Explore Deals', 'cuba-investment-core' ); ?>
                            </h4>
                            <p class="text-xs text-slate-500 leading-relaxed">
                                <?php esc_html_e( 'Browse verified Cuban business listings and investment opportunities.', 'cuba-investment-core' ); ?>
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                            <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="text-xs font-bold text-accent hover:text-primary">
                                <?php esc_html_e( 'Browse Listings &rarr;', 'cuba-investment-core' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Panel -->
            <div>
                <?php
                $args = [
                    'activities' => $activities,
                    'title'      => __( 'Recent Platform Activity', 'cuba-investment-core' ),
                ];
                include CIN_PLUGIN_DIR . 'templates/dashboard/components/activity-panel.php';
                ?>
            </div>

        </div>

        <!-- Membership & Verification Status Card (Real Data) -->
        <div class="card bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold text-slate-900">
                                <?php esc_html_e( 'Membership: Launch Early Access Tier', 'cuba-investment-core' ); ?>
                            </h4>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700">
                                ● <?php echo esc_html( ucfirst( $account_status ) ); ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( '100% Free · No payment required during launch · Full platform access', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <?php if ( $is_email_verified ) : ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ✓ <?php esc_html_e( 'Email Verified', 'cuba-investment-core' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            ● <?php esc_html_e( 'Pending Verification', 'cuba-investment-core' ); ?>
                        </span>
                        <form method="post" action="" class="inline">
                            <input type="hidden" name="cin_action" value="cin_resend_verification">
                            <input type="hidden" name="email" value="<?php echo esc_attr( $user->user_email ); ?>">
                            <?php wp_nonce_field( 'cin_resend_verification', '_cin_nonce' ); ?>
                            <button type="submit" class="text-xs font-bold text-amber-700 hover:text-amber-900 underline cursor-pointer">
                                <?php esc_html_e( 'Resend Verification', 'cuba-investment-core' ); ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="text-xs font-bold text-primary hover:underline ml-1">
                        <?php esc_html_e( 'Account Details &rarr;', 'cuba-investment-core' ); ?>
                    </a>
                </div>
            </div>
        </div>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
