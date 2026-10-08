<?php
/**
 * Template: Account Settings & Security
 * Route: /account/
 *
 * Implements password management, membership tier overview,
 * and legal terms acceptance log using the unified dashboard layout.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\AuthManager;
use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_investor       = in_array( Constants::ROLE_INVESTOR, $roles, true );
$is_business_owner = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );

$first_name        = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$last_name         = get_user_meta( $user->ID, 'last_name', true ) ?: $user->last_name;
$account_status    = get_user_meta( $user->ID, '_cin_account_status', true ) ?: 'active';
$membership_tier   = get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $user->ID, '_cin_membership_status', true ) ?: 'active';
$terms_accepted_at = get_user_meta( $user->ID, '_cin_terms_accepted_at', true );
$terms_version     = get_user_meta( $user->ID, '_cin_terms_version', true ) ?: '1.0';

$error_msg   = FormHandler::get_profile_flash_error( $user->ID );
$success_msg = FormHandler::get_profile_flash_success( $user->ID );
if ( empty( $success_msg ) && isset( $_GET['pw_updated'] ) ) {
    $success_msg = __( 'Your password has been changed successfully.', 'cuba-investment-core' );
}

$dashboard_url = AuthManager::get_user_dashboard_url( $user );
$page_title    = __( 'Account Settings', 'cuba-investment-core' );

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto space-y-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( $dashboard_url ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Portal Dashboard', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Account Settings & Security', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your login credentials, password security, and active membership tier.', 'cuba-investment-core' ); ?>
                </p>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if ( ! empty( $error_msg ) ) : ?>
            <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm font-medium flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1"><?php echo esc_html( $error_msg ); ?></div>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $success_msg ) ) : ?>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1"><?php echo esc_html( $success_msg ); ?></div>
            </div>
        <?php endif; ?>

        <!-- Account Profile Summary Card -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-heading font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span><?php esc_html_e( 'Profile Summary', 'cuba-investment-core' ); ?></span>
                <span class="text-xs font-normal text-slate-500">
                    <?php esc_html_e( 'Registered: ', 'cuba-investment-core' ); ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?>
                </span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Full Name', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-slate-900 mt-1 block">
                        <?php echo esc_html( trim( $first_name . ' ' . $last_name ) ?: $user->display_name ); ?>
                    </span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Email Address', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-slate-900 mt-1 block truncate"><?php echo esc_html( $user->user_email ); ?></span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Account Role', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-primary mt-1 block">
                        <?php 
                        if ( $is_investor ) {
                            esc_html_e( 'Investor', 'cuba-investment-core' );
                        } elseif ( $is_business_owner ) {
                            esc_html_e( 'Business Owner', 'cuba-investment-core' );
                        } else {
                            echo esc_html( ucfirst( $roles[0] ?? 'User' ) );
                        }
                        ?>
                    </span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Account Status', 'cuba-investment-core' ); ?></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 mt-1">
                        ● <?php echo esc_html( ucfirst( $account_status ) ); ?>
                    </span>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end">
                <a href="<?php echo esc_url( $is_investor ? home_url( '/investor/profile/' ) : home_url( '/business-owner/profile/' ) ); ?>" class="btn btn-outline btn-sm font-semibold text-primary border-primary hover:bg-primary hover:text-white">
                    <?php esc_html_e( 'Edit Full Profile &rarr;', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

        <!-- Membership & Access Tier -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-heading font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100">
                <?php esc_html_e( 'Membership & Access Tier', 'cuba-investment-core' ); ?>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Current Tier', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-primary mt-1 block"><?php esc_html_e( 'Launch Early Access', 'cuba-investment-core' ); ?></span>
                    <span class="text-xs text-emerald-600 font-semibold block mt-0.5"><?php esc_html_e( '100% Free Platform Access', 'cuba-investment-core' ); ?></span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Billing Amount', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-slate-900 mt-1 block">$0.00 / month</span>
                    <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'No credit card required', 'cuba-investment-core' ); ?></span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Membership Status', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-emerald-700 mt-1 block">✓ <?php echo esc_html( ucfirst( $membership_status ) ); ?></span>
                    <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'Recurring Billing: Disabled', 'cuba-investment-core' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Security: Change Password Form -->
        <div id="security" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            <div class="pb-3 border-b border-slate-100">
                <h2 class="text-base font-heading font-bold text-slate-900">
                    <?php esc_html_e( 'Password & Security', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?php esc_html_e( 'Update your password directly below. Choose a strong password with at least 8 characters.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <form method="post" action="" class="space-y-4 max-w-lg">
                <input type="hidden" name="cin_action" value="cin_update_password">
                <?php wp_nonce_field( 'cin_update_password', '_cin_nonce' ); ?>

                <div>
                    <label for="current_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Current Password', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="current_password" name="current_password" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'New Password', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    <span class="text-[11px] text-slate-400 mt-1 block"><?php esc_html_e( 'Minimum 8 characters.', 'cuba-investment-core' ); ?></span>
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Confirm New Password', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="8" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary btn-md px-6 py-2.5 font-bold shadow-xs hover:shadow transition-all cursor-pointer">
                        <?php esc_html_e( 'Update Password', 'cuba-investment-core' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Legal & Compliance Log -->
        <div class="card bg-slate-50/70 p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-heading font-bold text-slate-900 mb-3">
                <?php esc_html_e( 'Legal & Policy Acknowledgements', 'cuba-investment-core' ); ?>
            </h2>
            <div class="text-xs text-slate-600 space-y-2">
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
                <p class="flex items-center gap-2 text-slate-500">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span><?php esc_html_e( 'Neutral informational marketplace consent acknowledged.', 'cuba-investment-core' ); ?></span>
                </p>
            </div>
        </div>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
