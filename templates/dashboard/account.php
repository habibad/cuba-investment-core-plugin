<?php
/**
 * Template: Account Settings (Phase 02 Minimal Protected Landing Placeholder)
 * Route: /account/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\AuthManager;
use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$current_user     = wp_get_current_user();
$first_name       = get_user_meta( $current_user->ID, 'first_name', true ) ?: $current_user->first_name;
$last_name        = get_user_meta( $current_user->ID, 'last_name', true ) ?: $current_user->last_name;
$account_status   = get_user_meta( $current_user->ID, '_cin_account_status', true ) ?: 'active';
$membership_tier  = get_user_meta( $current_user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $current_user->ID, '_cin_membership_status', true ) ?: 'active';
$terms_accepted_at = get_user_meta( $current_user->ID, '_cin_terms_accepted_at', true );
$terms_version    = get_user_meta( $current_user->ID, '_cin_terms_version', true ) ?: '1.0';

$is_investor       = in_array( Constants::ROLE_INVESTOR, (array) $current_user->roles, true );
$is_business_owner = in_array( Constants::ROLE_BUSINESS_OWNER, (array) $current_user->roles, true );

$country           = get_user_meta( $current_user->ID, '_cin_country', true );
$business_name     = get_user_meta( $current_user->ID, '_cin_business_name', true );
$business_location = get_user_meta( $current_user->ID, '_cin_business_location', true );

$dashboard_url = AuthManager::get_user_dashboard_url( $current_user );
?>

<main id="primary" class="site-main py-12 sm:py-16 bg-slate-50 min-h-[85vh]">
    <div class="container mx-auto px-4 max-w-4xl">

        <!-- Header Navigation Bar -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="<?php echo esc_url( $dashboard_url ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:underline mb-2">
                    &larr; <?php esc_html_e( 'Back to Portal Dashboard', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary">
                    <?php esc_html_e( 'Account Settings &amp; Security', 'cuba-investment-core' ); ?>
                </h1>
            </div>
            <div>
                <a href="<?php echo esc_url( home_url( '/logout/' ) ); ?>" class="btn btn-secondary btn-sm text-slate-600 hover:text-red-600 font-semibold">
                    <?php esc_html_e( 'Log Out', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

        <div class="space-y-6">

            <!-- Personal Profile Card -->
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h2 class="text-lg font-heading font-bold text-primary mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span><?php esc_html_e( 'Profile Summary', 'cuba-investment-core' ); ?></span>
                    <span class="text-xs font-normal text-slate-500">
                        <?php esc_html_e( 'Registered: ', 'cuba-investment-core' ); ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $current_user->user_registered ) ) ); ?>
                    </span>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Full Name', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block">
                            <?php echo esc_html( trim( $first_name . ' ' . $last_name ) ?: $current_user->display_name ); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Email Address', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block"><?php echo esc_html( $current_user->user_email ); ?></span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Account Role', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-primary mt-1 block">
                            <?php 
                            if ( $is_investor ) {
                                esc_html_e( 'Investor', 'cuba-investment-core' );
                            } elseif ( $is_business_owner ) {
                                esc_html_e( 'Business Owner', 'cuba-investment-core' );
                            } else {
                                echo esc_html( ucfirst( $current_user->roles[0] ?? 'User' ) );
                            }
                            ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Account Status', 'cuba-investment-core' ); ?></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 mt-1">
                            ● <?php echo esc_html( ucfirst( $account_status ) ); ?>
                        </span>
                    </div>

                    <?php if ( $is_investor && ! empty( $country ) ) : ?>
                        <div>
                            <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Country of Residence', 'cuba-investment-core' ); ?></span>
                            <span class="text-sm font-bold text-slate-800 mt-1 block"><?php echo esc_html( $country ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $is_business_owner ) : ?>
                        <?php if ( ! empty( $business_name ) ) : ?>
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Registered Business', 'cuba-investment-core' ); ?></span>
                                <span class="text-sm font-bold text-slate-800 mt-1 block"><?php echo esc_html( $business_name ); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $business_location ) ) : ?>
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Business Location', 'cuba-investment-core' ); ?></span>
                                <span class="text-sm font-bold text-slate-800 mt-1 block"><?php echo esc_html( $business_location ); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Free Membership Status Card -->
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h2 class="text-lg font-heading font-bold text-primary mb-4 pb-3 border-b border-slate-100">
                    <?php esc_html_e( 'Membership &amp; Access Tier', 'cuba-investment-core' ); ?>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Current Plan', 'cuba-investment-core' ); ?></span>
                        <span class="text-base font-bold text-primary mt-1 block"><?php esc_html_e( 'Launch Early Access', 'cuba-investment-core' ); ?></span>
                        <span class="text-xs text-emerald-600 font-semibold block mt-0.5"><?php esc_html_e( 'Free Membership', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl">
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Billing Amount', 'cuba-investment-core' ); ?></span>
                        <span class="text-base font-bold text-slate-900 mt-1 block">$0.00</span>
                        <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'Payment Method Not Required', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl">
                        <span class="text-xs text-slate-500 uppercase tracking-wider block"><?php esc_html_e( 'Subscription Renewal', 'cuba-investment-core' ); ?></span>
                        <span class="text-base font-bold text-emerald-700 mt-1 block"><?php esc_html_e( 'Complimentary', 'cuba-investment-core' ); ?></span>
                        <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'Recurring Billing: Disabled', 'cuba-investment-core' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Security & Password -->
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h2 class="text-lg font-heading font-bold text-primary mb-2">
                    <?php esc_html_e( 'Security &amp; Password', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-slate-600 text-xs sm:text-sm mb-4 leading-relaxed">
                    <?php esc_html_e( 'Need to update your password? You can dispatch a secure reset instructions link to your registered email address.', 'cuba-investment-core' ); ?>
                </p>

                <form method="POST" action="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="inline-block">
                    <?php \CubaInvestment\Core\Security\NonceManager::field( 'cin_forgot_password', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_forgot_password" />
                    <input type="hidden" name="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />
                    <button type="submit" class="btn btn-outline btn-md font-semibold cursor-pointer">
                        <?php esc_html_e( 'Send Password Reset Email', 'cuba-investment-core' ); ?>
                    </button>
                </form>
            </div>

            <!-- Legal & Compliance Log -->
            <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                <h2 class="text-lg font-heading font-bold text-primary mb-3">
                    <?php esc_html_e( 'Legal &amp; Policy Acknowledgements', 'cuba-investment-core' ); ?>
                </h2>
                <div class="text-xs sm:text-sm text-slate-600 space-y-2">
                    <p class="flex items-center gap-2">
                        <span class="text-emerald-600 font-bold">✓</span>
                        <span>
                            <?php 
                            if ( $terms_accepted_at ) {
                                echo esc_html( sprintf( __( 'Accepted Terms of Service (v%s) & Privacy Policy on %s', 'cuba-investment-core' ), $terms_version, date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $terms_accepted_at ) ) ) );
                            } else {
                                esc_html_e( 'Terms of Service & Privacy Policy agreed during registration.', 'cuba-investment-core' );
                            }
                            ?>
                        </span>
                    </p>
                </div>
            </div>

        </div>

    </div>
</main>

<?php
get_footer();
