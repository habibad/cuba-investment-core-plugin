<?php
/**
 * Template: Business Owner Dashboard (Phase 02 Minimal Protected Landing Placeholder)
 * Route: /dashboard/business/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$current_user     = wp_get_current_user();
$first_name       = get_user_meta( $current_user->ID, 'first_name', true ) ?: $current_user->display_name;
$business_name    = get_user_meta( $current_user->ID, '_cin_business_name', true ) ?: __( 'My Business', 'cuba-investment-core' );
$location         = get_user_meta( $current_user->ID, '_cin_business_location', true ) ?: 'Cuba';
$account_status   = get_user_meta( $current_user->ID, '_cin_account_status', true ) ?: 'active';
$membership_tier  = get_user_meta( $current_user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $current_user->ID, '_cin_membership_status', true ) ?: 'active';
?>

<main id="primary" class="site-main py-12 sm:py-16 bg-slate-50 min-h-[85vh]">
    <div class="container mx-auto px-4 max-w-5xl">

        <!-- Welcome Banner -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-accent-50 text-accent uppercase tracking-wider">
                            <?php esc_html_e( 'Business Owner Portal', 'cuba-investment-core' ); ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            ● <?php echo esc_html( ucfirst( $account_status ) ); ?>
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary">
                        <?php echo esc_html( sprintf( __( 'Welcome, %s', 'cuba-investment-core' ), $first_name ) ); ?>
                    </h1>
                    <p class="text-slate-600 text-sm mt-1">
                        <strong class="text-slate-800"><?php echo esc_html( $business_name ); ?></strong> · <?php echo esc_html( $location ); ?> · <?php echo esc_html( $current_user->user_email ); ?>
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
                        <?php esc_html_e( 'Opportunity Submission', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-base font-bold text-slate-700 mt-1 block">
                        <?php esc_html_e( 'Phase 05 Module', 'cuba-investment-core' ); ?>
                    </span>
                    <span class="text-xs text-accent font-semibold mt-0.5 block">
                        <?php esc_html_e( 'Multi-step Submission Wizard', 'cuba-investment-core' ); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Next Steps & Modules Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-accent-50 text-accent flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h2 class="text-lg font-heading font-bold text-primary mb-2">
                    <?php esc_html_e( 'Business Profile & Verification', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-6">
                    <?php esc_html_e( 'Your detailed enterprise documentation, sector categorization, and team information will be managed in Phase 03.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="btn btn-outline btn-md font-semibold">
                    <?php esc_html_e( 'Review Account Information', 'cuba-investment-core' ); ?>
                </a>
            </div>

            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <h2 class="text-lg font-heading font-bold text-primary mb-2">
                    <?php esc_html_e( 'Capital Opportunities', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-slate-600 text-xs sm:text-sm leading-relaxed mb-6">
                    <?php esc_html_e( 'Listing submission and deal management tools will become active in Phase 05 following profile verification.', 'cuba-investment-core' ); ?>
                </p>
                <a href="<?php echo esc_url( home_url( '/for-business-owners/' ) ); ?>" class="btn btn-primary btn-md font-bold">
                    <?php esc_html_e( 'How It Works for Businesses &rarr;', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

    </div>
</main>

<?php
get_footer();
