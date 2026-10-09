<?php
/**
 * Template: Account Settings & Security
 * Route: /account/
 *
 * Implements Section A (Account Information), Section B (Personal Settings),
 * Section C (Email Address & Verification), Section D (Password Change),
 * and Free Membership status using the unified dashboard layout.
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
$is_verified       = (bool) get_user_meta( $user->ID, '_cin_email_verified', true );
$membership_tier   = get_user_meta( $user->ID, '_cin_membership_tier', true ) ?: Constants::TIER_LAUNCH;
$membership_status = get_user_meta( $user->ID, '_cin_membership_status', true ) ?: 'active';

$notify_inquiries  = get_user_meta( $user->ID, '_cin_notify_inquiries', true );
if ( '' === $notify_inquiries ) {
    $notify_inquiries = 1;
}
$notify_news       = get_user_meta( $user->ID, '_cin_notify_announcements', true );
if ( '' === $notify_news ) {
    $notify_news = 1;
}
$pref_language     = get_user_meta( $user->ID, '_cin_preferred_language', true ) ?: 'en';

$terms_accepted_at = get_user_meta( $user->ID, '_cin_terms_accepted_at', true );
$terms_version     = get_user_meta( $user->ID, '_cin_terms_version', true ) ?: '1.0';

$error_msg   = FormHandler::get_profile_flash_error( $user->ID );
$success_msg = FormHandler::get_profile_flash_success( $user->ID );
if ( empty( $success_msg ) && isset( $_GET['pw_updated'] ) ) {
    $success_msg = __( 'Your password has been changed successfully.', 'cuba-investment-core' );
}
if ( empty( $success_msg ) && isset( $_GET['personal_updated'] ) ) {
    $success_msg = __( 'Personal settings updated successfully.', 'cuba-investment-core' );
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
                    <?php esc_html_e( 'Account Settings & Operations', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your personal settings, password security, and active membership tier.', 'cuba-investment-core' ); ?>
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

        <!-- SECTION A: Account Information -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-heading font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span><?php esc_html_e( 'Section A — Account Information', 'cuba-investment-core' ); ?></span>
                <span class="text-xs font-normal text-slate-500">
                    <?php esc_html_e( 'Member Since: ', 'cuba-investment-core' ); ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?>
                </span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Full Name -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Full Name', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-slate-900 mt-1 block">
                        <?php echo esc_html( trim( $first_name . ' ' . $last_name ) ?: $user->display_name ); ?>
                    </span>
                </div>

                <!-- Email Address -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Email Address', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-slate-900 mt-1 block truncate"><?php echo esc_html( $user->user_email ); ?></span>
                </div>

                <!-- Account Type / Role -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Account Type', 'cuba-investment-core' ); ?></span>
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

                <!-- Membership Type -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Membership Type', 'cuba-investment-core' ); ?></span>
                    <span class="text-sm font-bold text-slate-900 mt-1 block">
                        <?php esc_html_e( 'Launch Early Access (Free)', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <!-- Account Status -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Account Status', 'cuba-investment-core' ); ?></span>
                    <?php if ( 'active' === $account_status ) : ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 mt-1">
                            ● <?php esc_html_e( 'Active', 'cuba-investment-core' ); ?>
                        </span>
                    <?php elseif ( 'suspended' === $account_status ) : ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-50 text-red-700 mt-1">
                            ● <?php esc_html_e( 'Suspended', 'cuba-investment-core' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 mt-1">
                            ● <?php echo esc_html( ucfirst( str_replace( '_', ' ', $account_status ) ) ); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Email Verification Status -->
                <div>
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Email Verification', 'cuba-investment-core' ); ?></span>
                    <?php if ( $is_verified ) : ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 mt-1">
                            ✓ <?php esc_html_e( 'Verified', 'cuba-investment-core' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-50 text-amber-700 mt-1">
                            ● <?php esc_html_e( 'Pending Verification', 'cuba-investment-core' ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end">
                <a href="<?php echo esc_url( $is_investor ? home_url( '/investor/profile/' ) : home_url( '/business-owner/profile/' ) ); ?>" class="btn btn-outline btn-sm font-semibold text-primary border-primary hover:bg-primary hover:text-white">
                    <?php esc_html_e( 'Edit Full Profile Details &rarr;', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

        <!-- SECTION B: Personal Settings -->
        <div id="personal-settings" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            <div class="pb-3 border-b border-slate-100">
                <h2 class="text-base font-heading font-bold text-slate-900">
                    <?php esc_html_e( 'Section B — Personal Settings', 'cuba-investment-core' ); ?>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    <?php esc_html_e( 'Update your personal name and notification preferences across the Cuba Investment Network.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <form method="post" action="" class="space-y-5 max-w-xl">
                <input type="hidden" name="cin_action" value="cin_update_personal_settings">
                <?php wp_nonce_field( 'cin_update_personal_settings', '_cin_nonce' ); ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'First Name', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <div>
                        <label for="last_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Last Name', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>
                </div>

                <div>
                    <label for="preferred_language" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Preferred Communication Language', 'cuba-investment-core' ); ?>
                    </label>
                    <select id="preferred_language" name="preferred_language" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors bg-white">
                        <option value="en" <?php selected( $pref_language, 'en' ); ?>><?php esc_html_e( 'English (Default)', 'cuba-investment-core' ); ?></option>
                        <option value="es" <?php selected( $pref_language, 'es' ); ?>><?php esc_html_e( 'Español (Spanish)', 'cuba-investment-core' ); ?></option>
                    </select>
                </div>

                <div class="pt-2 border-t border-slate-100 space-y-3">
                    <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        <?php esc_html_e( 'Communication & Notification Preferences', 'cuba-investment-core' ); ?>
                    </span>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="notify_inquiries" value="1" <?php checked( (bool) $notify_inquiries ); ?> class="mt-0.5 rounded text-primary focus:ring-primary">
                        <span class="text-xs text-slate-600">
                            <strong><?php esc_html_e( 'Direct Inquiries & Match Alerts', 'cuba-investment-core' ); ?></strong><br>
                            <span class="text-slate-400"><?php esc_html_e( 'Receive email notifications when an investor or entrepreneur responds to an introduction.', 'cuba-investment-core' ); ?></span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="notify_announcements" value="1" <?php checked( (bool) $notify_news ); ?> class="mt-0.5 rounded text-primary focus:ring-primary">
                        <span class="text-xs text-slate-600">
                            <strong><?php esc_html_e( 'Platform Updates & Cuba Market Insights', 'cuba-investment-core' ); ?></strong><br>
                            <span class="text-slate-400"><?php esc_html_e( 'Receive platform feature announcements and curated ecosystem briefings.', 'cuba-investment-core' ); ?></span>
                        </span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary btn-md px-6 py-2.5 font-bold shadow-xs hover:shadow transition-all cursor-pointer">
                        <?php esc_html_e( 'Save Personal Settings', 'cuba-investment-core' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- SECTION C: Email Address & Verification -->
        <div id="email-address" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
            <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-heading font-bold text-slate-900">
                        <?php esc_html_e( 'Section C — Primary Email Address', 'cuba-investment-core' ); ?>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <?php esc_html_e( 'Your registered portal email address and verification credential.', 'cuba-investment-core' ); ?>
                    </p>
                </div>

                <div>
                    <?php if ( $is_verified ) : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ✓ <?php esc_html_e( 'Verified Email', 'cuba-investment-core' ); ?>
                        </span>
                    <?php else : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            ● <?php esc_html_e( 'Pending Verification', 'cuba-investment-core' ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="max-w-xl space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Current Email Address', 'cuba-investment-core' ); ?>
                    </label>
                    <div class="relative">
                        <input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly class="w-full pl-4 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-700 font-medium cursor-not-allowed">
                        <span class="absolute inset-y-0 right-3 flex items-center text-slate-400" title="<?php esc_attr_e( 'Protected read-only field', 'cuba-investment-core' ); ?>">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                    </div>
                </div>

                <?php if ( ! $is_verified ) : ?>
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-xs text-amber-800">
                        <span><?php esc_html_e( 'Your email is not verified yet. Please check your inbox or request a new verification email.', 'cuba-investment-core' ); ?></span>
                        <form method="post" action="">
                            <input type="hidden" name="cin_action" value="cin_resend_verification">
                            <input type="hidden" name="email" value="<?php echo esc_attr( $user->user_email ); ?>">
                            <?php wp_nonce_field( 'cin_resend_verification', '_cin_nonce' ); ?>
                            <button type="submit" class="font-bold underline text-amber-900 hover:text-amber-950 cursor-pointer">
                                <?php esc_html_e( 'Resend Verification', 'cuba-investment-core' ); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-xs text-slate-500 leading-relaxed flex items-start gap-3">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong class="text-slate-700"><?php esc_html_e( 'Why is email modification locked?', 'cuba-investment-core' ); ?></strong><br>
                        <?php esc_html_e( 'To prevent account takeover, protect verified transaction logs, and maintain marketplace integrity, primary email addresses cannot be altered directly via metadata editing. If you require an email update, please submit a verified support inquiry.', 'cuba-investment-core' ); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION D: Password & Security -->
        <div id="security" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
            <div class="pb-3 border-b border-slate-100">
                <h2 class="text-base font-heading font-bold text-slate-900">
                    <?php esc_html_e( 'Section D — Password & Security', 'cuba-investment-core' ); ?>
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

        <!-- Free Membership & Access Tier -->
        <div class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-heading font-bold text-slate-900 mb-4 pb-3 border-b border-slate-100">
                <?php esc_html_e( 'Membership & Access Tier', 'cuba-investment-core' ); ?>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Membership Type', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-primary mt-1 block"><?php esc_html_e( 'Launch Early Access', 'cuba-investment-core' ); ?></span>
                    <span class="text-xs text-emerald-600 font-semibold block mt-0.5"><?php esc_html_e( '100% Free Platform Access', 'cuba-investment-core' ); ?></span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Billing Amount', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-slate-900 mt-1 block">$0.00 / month</span>
                    <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'No payment required during launch', 'cuba-investment-core' ); ?></span>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block"><?php esc_html_e( 'Membership Status', 'cuba-investment-core' ); ?></span>
                    <span class="text-base font-bold text-emerald-700 mt-1 block">✓ <?php echo esc_html( ucfirst( $membership_status ) ); ?></span>
                    <span class="text-xs text-slate-500 block mt-0.5"><?php esc_html_e( 'Recurring Billing: Disabled', 'cuba-investment-core' ); ?></span>
                </div>
            </div>
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
