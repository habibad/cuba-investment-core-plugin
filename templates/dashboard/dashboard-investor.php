<?php
/**
 * Template: Investor Dashboard (Phase 02 Minimal Protected Landing Placeholder)
 * Route: /dashboard/investor/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$current_user = wp_get_current_user();
$first_name   = get_user_meta( $current_user->ID, 'first_name', true ) ?: $current_user->display_name;
$account_status = get_user_meta( $current_user->ID, '_cin_account_status', true ) ?: 'active';
$membership_tier = get_user_meta( $current_user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $current_user->ID, '_cin_membership_status', true ) ?: 'active';
$country = get_user_meta( $current_user->ID, '_cin_country', true ) ?: 'International';
?>

<main id="primary" class="site-main py-12 sm:py-16 bg-slate-50 min-h-[85vh]">
    <div class="container mx-auto px-4 max-w-5xl">

        <!-- Welcome Banner -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary-50 text-primary uppercase tracking-wider">
                            <?php esc_html_e( 'Investor Portal', 'cuba-investment-core' ); ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            ● <?php echo esc_html( ucfirst( $account_status ) ); ?>
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary">
                        <?php echo esc_html( sprintf( __( 'Welcome back, %s', 'cuba-investment-core' ), $first_name ) ); ?>
                    </h1>
                    <p class="text-slate-600 text-sm mt-1">
                        <?php echo esc_html( $current_user->user_email ); ?> · <?php echo esc_html( $country ); ?>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="btn btn-outline btn-sm font-semibold">
                        <?php esc_html_e( 'Account Settings', 'cuba-investment-core' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/logout/' ) ); ?>" class="btn btn-secondary btn-sm text-slate-600 hover:text-red-600 font-semibold">
                        <?php esc_html_e( 'Log Out', 'cuba-investment-core' ); ?>
                    </a>
                </div>
            </div>

            <!-- Membership Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">
                        <?php esc_html_e( 'Membership Tier', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-base font-bold text-primary mt-1 block">
                        <?php esc_html_e( 'Early Access (Launch Period)', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-xs text-emerald-600 font-semibold mt-0.5 block">
                        <?php esc_html_e( '100% Free · $0 / Billed', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">
                        <?php esc_html_e( 'Membership Status', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-base font-bold text-emerald-700 mt-1 block">
                        ✓ <?php echo esc_html( ucfirst( $membership_status ) ); ?>
                    </span>
                    <span class="text-xs text-slate-500 mt-0.5 block">
                        <?php esc_html_e( 'Recurring Billing: Disabled', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-500 font-medium uppercase tracking-wider block">
                        <?php esc_html_e( 'Investor Profile', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-base font-bold text-slate-700 mt-1 block">
                        <?php esc_html_e( 'Phase 03 In Progress', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-xs text-primary font-semibold mt-0.5 block">
                        <?php esc_html_e( 'Accreditation / Preferences', 'cuba-investment-core' ); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Next Steps & Modules Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-accent-50 text-accent flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h2 class="text-lg font-heading font-bold text-primary mb-2">
                    <?php esc_html_e( 'Explore Opportunities', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-6">
                    <?php esc_html_e( 'Browse verified Cuba-focused business listings, emerging private enterprises, and cross-border commercial opportunities.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>" class="btn btn-primary btn-md font-bold">
                    <?php esc_html_e( 'Browse Opportunities &rarr;', 'cuba-investment-core' ); ?>
                </a>
            </div>

            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h2 class="text-lg font-heading font-bold text-primary mb-2">
                    <?php esc_html_e( 'Investor Profile & Preferences', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-6">
                    <?php esc_html_e( 'Detailed investment criteria, capital allocation preferences, and private dealflow matching will be configured in Phase 03.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="btn btn-outline btn-md font-semibold">
                    <?php esc_html_e( 'View Account Details', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

    </div>
</main>

<?php
get_footer();
