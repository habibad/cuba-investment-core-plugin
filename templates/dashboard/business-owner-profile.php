<?php
/**
 * Template: Business Owner Personal Profile
 * Route: /business-owner/profile/ (and /dashboard/business/profile/)
 *
 * Implements Section A (Personal Information) for Business Owner.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();
$profile = ProfileService::get_business_owner_profile( $user->ID );

// Flash notices
$error_msg   = FormHandler::get_profile_flash_error( $user->ID );
$success_msg = FormHandler::get_profile_flash_success( $user->ID );
if ( empty( $success_msg ) && isset( $_GET['updated'] ) ) {
    $success_msg = __( 'Personal profile saved successfully.', 'cuba-investment-core' );
}

$page_title = __( 'Personal Profile', 'cuba-investment-core' );

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-4xl w-full mx-auto space-y-8">

        <!-- Navigation Tabs: Personal vs Business -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <a href="<?php echo esc_url( home_url( '/business-owner/profile/' ) ); ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-primary text-white shadow-xs">
                <?php esc_html_e( '1. Personal Profile', 'cuba-investment-core' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-primary hover:bg-slate-100 transition-colors">
                <?php esc_html_e( '2. Business Profile & Enterprise', 'cuba-investment-core' ); ?> &rarr;
            </a>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Business Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Business Owner Personal Profile', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your founder credentials, personal biography, and verified direct contact details.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-primary-50 text-primary border border-primary-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <?php echo esc_html( sprintf( __( 'Profile %d%% Complete', 'cuba-investment-core' ), $profile['completion']['percentage'] ) ); ?>
                </span>
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

        <!-- Dynamic Profile Completion Header Banner -->
        <?php
        $args = [
            'completion'  => $profile['completion'],
            'profile_url' => home_url( '/business-owner/business-profile/' ),
            'title'       => __( 'Overall Profile Completion', 'cuba-investment-core' ),
            'description' => __( 'A complete personal profile and business profile gives your enterprise maximum visibility.', 'cuba-investment-core' ),
        ];
        include CIN_PLUGIN_DIR . 'templates/dashboard/components/completion-card.php';
        ?>

        <!-- PROFILE EDIT FORM -->
        <form method="post" action="" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="cin_action" value="cin_update_business_owner_profile">
            <?php wp_nonce_field( 'cin_update_business_owner_profile', '_cin_nonce' ); ?>

            <!-- SECTION A: Personal Information -->
            <div id="section-personal" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section A — Personal Information', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Founder contact information and verified credentials.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">
                        <?php esc_html_e( 'Personal Info', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'First Name', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( $profile['first_name'] ); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Last Name', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( $profile['last_name'] ); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Email Address (Display Only / Verified) -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <?php esc_html_e( 'Verified Email Address', 'cuba-investment-core' ); ?>
                            </label>
                            <span class="text-[11px] font-bold text-emerald-700 inline-flex items-center gap-1">
                                ✓ <?php esc_html_e( 'Verified', 'cuba-investment-core' ); ?>
                            </span>
                        </div>
                        <input type="email" value="<?php echo esc_attr( $profile['email'] ); ?>" disabled class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-500 cursor-not-allowed">
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Phone / WhatsApp Number (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo esc_attr( $profile['phone_number'] ); ?>" placeholder="+53 5 123 4567" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Country of Residence -->
                    <div class="sm:col-span-2">
                        <label for="country_of_residence" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Country of Residence', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="country_of_residence" name="country_of_residence" value="<?php echo esc_attr( $profile['country_of_residence'] ); ?>" required placeholder="e.g. Cuba, Spain, Mexico, United States" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Profile Photo Upload -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Founder Photo (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0">
                                <?php if ( ! empty( $profile['avatar_url'] ) ) : ?>
                                    <img id="avatar-preview-img" src="<?php echo esc_url( $profile['avatar_url'] ); ?>" alt="Avatar" class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shadow-xs">
                                <?php else : ?>
                                    <div id="avatar-preview-fallback" class="w-16 h-16 rounded-2xl bg-primary-100 text-primary font-bold text-lg flex items-center justify-center border border-primary-200 shadow-xs">
                                        <?php echo esc_html( strtoupper( substr( $profile['first_name'] ?: 'B', 0, 1 ) ) ); ?>
                                    </div>
                                    <img id="avatar-preview-img" src="" alt="Avatar" class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shadow-xs hidden">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="avatar" id="avatar_upload" accept="image/jpeg,image/png,image/webp" data-preview-target="avatar-preview-img" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary hover:file:bg-primary-100 cursor-pointer">
                                <p class="text-[11px] text-slate-400 mt-1">
                                    <?php esc_html_e( 'Accepted formats: JPG, PNG, WebP. Maximum size: 2MB.', 'cuba-investment-core' ); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Short Professional Bio -->
                    <div class="sm:col-span-2">
                        <label for="founder_bio" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Founder Bio / Background (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <textarea id="founder_bio" name="founder_bio" rows="3" placeholder="<?php esc_attr_e( 'Briefly summarize your industry background, entrepreneurial experience in Cuba, or leadership role in the enterprise...', 'cuba-investment-core' ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $profile['founder_bio'] ); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex items-center justify-between pt-2">
                <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="text-xs font-bold text-primary hover:underline">
                    <?php esc_html_e( 'Proceed to Business Profile &rarr;', 'cuba-investment-core' ); ?>
                </a>
                <button type="submit" class="btn btn-primary btn-md px-8 py-3 font-bold shadow-md hover:shadow-lg transition-all cursor-pointer">
                    <?php esc_html_e( 'Save Personal Details', 'cuba-investment-core' ); ?>
                </button>
            </div>
        </form>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
