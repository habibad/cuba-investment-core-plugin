<?php
/**
 * Template: Business Profile & Partnership Interests
 * Route: /business-owner/business-profile/ (and /dashboard/business/business-profile/)
 *
 * Implements Section B (Business Info), Section C (Partnership Interests),
 * Section D (Profile Visibility), and Section E (Account Information).
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;
use CubaInvestment\Core\Security\Sanitizer;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();
$business = ProfileService::get_business_profile( $user->ID );

// Flash notices
$error_msg   = FormHandler::get_profile_flash_error( $user->ID );
$success_msg = FormHandler::get_profile_flash_success( $user->ID );
if ( empty( $success_msg ) && isset( $_GET['updated'] ) ) {
    $success_msg = __( 'Business profile saved successfully.', 'cuba-investment-core' );
}

// Available Sectors
$all_sectors = [
    'hospitality'   => __( 'Tourism & Hospitality', 'cuba-investment-core' ),
    'agriculture'   => __( 'Agriculture & Food Production', 'cuba-investment-core' ),
    'cleantech'     => __( 'Renewable Energy & Cleantech', 'cuba-investment-core' ),
    'logistics'     => __( 'Supply Chain & Logistics', 'cuba-investment-core' ),
    'manufacturing' => __( 'Light Manufacturing & Industry', 'cuba-investment-core' ),
    'technology'    => __( 'Technology & Software Services', 'cuba-investment-core' ),
    'healthcare'    => __( 'Biotechnology & Healthcare', 'cuba-investment-core' ),
    'real_estate'   => __( 'Real Estate & Infrastructure', 'cuba-investment-core' ),
    'retail'        => __( 'Retail & Consumer Goods', 'cuba-investment-core' ),
    'services'      => __( 'Professional & Consulting Services', 'cuba-investment-core' ),
];

// Available Cuban Provinces
$all_provinces = Sanitizer::get_provinces();

// Available Legal Structures
$all_legal_structures = Sanitizer::get_legal_structures();

// Available Stages
$all_stages = [
    'idea'        => __( 'Idea / Conceptual Stage', 'cuba-investment-core' ),
    'pre_revenue' => __( 'Pre-Revenue (Product/Service Development)', 'cuba-investment-core' ),
    'early_stage' => __( 'Early Stage / Seed (Initial Sales & Traction)', 'cuba-investment-core' ),
    'growth'      => __( 'Growth / Scaling (Expanding Operations)', 'cuba-investment-core' ),
    'established' => __( 'Established / Consistently Profitable', 'cuba-investment-core' ),
];

$page_title = __( 'Business Profile', 'cuba-investment-core' );

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-8">

        <!-- Navigation Tabs: Personal vs Business -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
            <a href="<?php echo esc_url( home_url( '/business-owner/profile/' ) ); ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-primary hover:bg-slate-100 transition-colors">
                &larr; <?php esc_html_e( '1. Personal Profile', 'cuba-investment-core' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-primary text-white shadow-xs">
                <?php esc_html_e( '2. Business Profile & Enterprise', 'cuba-investment-core' ); ?>
            </a>
        </div>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Business Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Business Profile & Enterprise Details', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your company overview, legal structure, target market, and strategic collaboration interests.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-primary-50 text-primary border border-primary-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <?php echo esc_html( sprintf( __( 'Profile %d%% Complete', 'cuba-investment-core' ), $business['completion']['percentage'] ) ); ?>
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
            'completion'  => $business['completion'],
            'profile_url' => '#field-company-name',
            'title'       => __( 'Enterprise Profile Progress', 'cuba-investment-core' ),
            'description' => __( 'A complete business profile showcases your Cuban enterprise to accredited and verified investors.', 'cuba-investment-core' ),
        ];
        include CIN_PLUGIN_DIR . 'templates/dashboard/components/completion-card.php';
        ?>

        <!-- PROFILE EDIT FORM -->
        <form method="post" action="" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="cin_action" value="cin_update_business_profile">
            <?php wp_nonce_field( 'cin_update_business_profile', '_cin_nonce' ); ?>

            <!-- SECTION B: Business Information -->
            <div id="section-business-info" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section B — Business Information', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Core organizational and legal profile of your venture.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">
                        <?php esc_html_e( 'Enterprise Details', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Business Name -->
                    <div id="field-company-name" class="sm:col-span-2">
                        <label for="company_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Business / Enterprise Name', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr( $business['company_name'] ); ?>" required placeholder="e.g. Caribe Agroindustrial S.R.L." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Business Sector / Industry -->
                    <div id="field-sector">
                        <label for="company_sector" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Industry Sector', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="company_sector" name="company_sector" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <?php foreach ( $all_sectors as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $business['company_sector'], $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Location / Cuban Province -->
                    <div>
                        <label for="company_location_province" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Cuban Province / Territory', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="company_location_province" name="company_location_province" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <?php foreach ( $all_provinces as $prov ) : ?>
                                <option value="<?php echo esc_attr( $prov ); ?>" <?php selected( $business['company_location_province'], $prov ); ?>>
                                    <?php echo esc_html( $prov ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Business Country -->
                    <div>
                        <label for="company_country" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Country of Operation', 'cuba-investment-core' ); ?>
                        </label>
                        <input type="text" id="company_country" name="company_country" value="<?php echo esc_attr( $business['company_country'] ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Website URL -->
                    <div>
                        <label for="company_website" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Business Website (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <input type="url" id="company_website" name="company_website" value="<?php echo esc_attr( $business['company_website'] ); ?>" placeholder="https://example.cu" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Business Description -->
                    <div id="field-description" class="sm:col-span-2">
                        <label for="company_description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Business Description / Executive Overview', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <textarea id="company_description" name="company_description" rows="4" placeholder="<?php esc_attr_e( 'Describe your enterprise: what problem you solve, your competitive advantage in Cuba, and current operations...', 'cuba-investment-core' ); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $business['company_description'] ); ?></textarea>
                    </div>

                    <!-- Products or Services -->
                    <div id="field-products" class="sm:col-span-2">
                        <label for="company_products_services" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Products or Services Offered', 'cuba-investment-core' ); ?>
                        </label>
                        <textarea id="company_products_services" name="company_products_services" rows="3" placeholder="<?php esc_attr_e( 'List your primary commercial products, software platforms, manufactured goods, or customer services...', 'cuba-investment-core' ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $business['company_products_services'] ); ?></textarea>
                    </div>

                    <!-- Operating History / Year Established -->
                    <div>
                        <label for="company_year_established" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Year Established', 'cuba-investment-core' ); ?>
                        </label>
                        <input type="number" id="company_year_established" name="company_year_established" value="<?php echo esc_attr( $business['company_year_established'] ); ?>" min="1990" max="<?php echo esc_attr( date( 'Y' ) ); ?>" placeholder="e.g. 2021" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Business Stage -->
                    <div id="field-stage">
                        <label for="company_stage" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Business Development Stage', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="company_stage" name="company_stage" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <?php foreach ( $all_stages as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $business['company_stage'], $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Legal Structure / Ownership Overview -->
                    <div class="sm:col-span-2">
                        <label for="company_legal_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Ownership Overview / Legal Structure', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="company_legal_type" name="company_legal_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <?php foreach ( $all_legal_structures as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $business['company_legal_type'], $key ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Primary Customer / Market Description -->
                    <div class="sm:col-span-2">
                        <label for="company_market_description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Primary Customer / Target Market Description', 'cuba-investment-core' ); ?>
                        </label>
                        <textarea id="company_market_description" name="company_market_description" rows="2" placeholder="<?php esc_attr_e( 'e.g. Domestic consumers in Havana and Matanzas (B2C), supplying state tourism hotels (B2G), private Cuban restaurants (B2B), or overseas diaspora remittances...', 'cuba-investment-core' ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $business['company_market_description'] ); ?></textarea>
                    </div>

                    <!-- Company Logo Upload -->
                    <div id="field-logo" class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Company Logo / Enterprise Brand (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0">
                                <?php if ( ! empty( $business['company_logo_url'] ) ) : ?>
                                    <img id="logo-preview-img" src="<?php echo esc_url( $business['company_logo_url'] ); ?>" alt="Company Logo" class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shadow-xs">
                                <?php else : ?>
                                    <div id="logo-preview-fallback" class="w-16 h-16 rounded-2xl bg-primary-100 text-primary font-bold text-lg flex items-center justify-center border border-primary-200 shadow-xs">
                                        <?php echo esc_html( strtoupper( substr( $business['company_name'] ?: 'CO', 0, 2 ) ) ); ?>
                                    </div>
                                    <img id="logo-preview-img" src="" alt="Company Logo" class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shadow-xs hidden">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="company_logo" id="logo_upload" accept="image/jpeg,image/png,image/webp" data-preview-target="logo-preview-img" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary hover:file:bg-primary-100 cursor-pointer">
                                <p class="text-[11px] text-slate-400 mt-1">
                                    <?php esc_html_e( 'Accepted formats: JPG, PNG, WebP. Maximum size: 2MB.', 'cuba-investment-core' ); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION C: Partnership Interests -->
            <div id="section-partnership" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section C — Partnership Interests', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'What kind of support and collaboration is your enterprise seeking?', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700">
                        <?php esc_html_e( 'Strategic Focus', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        <?php esc_html_e( 'Collaboration Goals (Select all that apply)', 'cuba-investment-core' ); ?>
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border <?php echo $business['seeking_investment'] ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors">
                            <input type="checkbox" name="seeking_investment" value="1" <?php checked( $business['seeking_investment'] ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <div>
                                <span class="text-xs sm:text-sm font-bold text-slate-900 block"><?php esc_html_e( 'Seeking Investment', 'cuba-investment-core' ); ?></span>
                                <span class="text-[11px] text-slate-500"><?php esc_html_e( 'Growth or expansion capital', 'cuba-investment-core' ); ?></span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 rounded-xl border <?php echo $business['seeking_partners'] ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors">
                            <input type="checkbox" name="seeking_partners" value="1" <?php checked( $business['seeking_partners'] ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <div>
                                <span class="text-xs sm:text-sm font-bold text-slate-900 block"><?php esc_html_e( 'Seeking Partners', 'cuba-investment-core' ); ?></span>
                                <span class="text-[11px] text-slate-500"><?php esc_html_e( 'Joint venture / trade ties', 'cuba-investment-core' ); ?></span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 rounded-xl border <?php echo $business['seeking_expertise'] ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors">
                            <input type="checkbox" name="seeking_expertise" value="1" <?php checked( $business['seeking_expertise'] ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                            <div>
                                <span class="text-xs sm:text-sm font-bold text-slate-900 block"><?php esc_html_e( 'Seeking Expertise', 'cuba-investment-core' ); ?></span>
                                <span class="text-[11px] text-slate-500"><?php esc_html_e( 'Advisory, tech or management', 'cuba-investment-core' ); ?></span>
                            </div>
                        </label>
                    </div>

                    <div class="pt-2">
                        <label for="collaboration_interests" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Additional Collaboration Details (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <textarea id="collaboration_interests" name="collaboration_interests" rows="3" placeholder="<?php esc_attr_e( 'Describe your ideal partner profile, operational synergies, or international export goals...', 'cuba-investment-core' ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $business['collaboration_interests'] ); ?></textarea>
                    </div>

                    <!-- Scope note -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 leading-relaxed flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>
                            <?php esc_html_e( 'Note: Specific deal structures, capital targets, and detailed valuation decks will be submitted through the dedicated Opportunity Submission module in Phase 05.', 'cuba-investment-core' ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- SECTION D: Profile Visibility & Privacy -->
            <div id="section-visibility" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section D — Profile Visibility & Privacy', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Control what information verified investors can see prior to direct connection.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">
                        <?php esc_html_e( 'Confidentiality', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="business_visibility" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Business Profile Visibility', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="business_visibility" name="business_visibility" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <option value="verified_investors" <?php selected( $business['business_visibility'], 'verified_investors' ); ?>>
                                <?php esc_html_e( 'Verified Investors Only (Recommended)', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="members_only" <?php selected( $business['business_visibility'], 'members_only' ); ?>>
                                <?php esc_html_e( 'All Registered Network Members', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="public" <?php selected( $business['business_visibility'], 'public' ); ?>>
                                <?php esc_html_e( 'Public (Listed on open marketplace)', 'cuba-investment-core' ); ?>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="show_phone_privacy" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Direct Telephone Contact', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="show_phone_privacy" name="show_phone_privacy" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <option value="connections_only" <?php selected( $business['show_phone_privacy'], 'connections_only' ); ?>>
                                <?php esc_html_e( 'Connected Investors Only (After Mutual Inquiry)', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="verified_investors" <?php selected( $business['show_phone_privacy'], 'verified_investors' ); ?>>
                                <?php esc_html_e( 'All Verified Investors', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="hidden" <?php selected( $business['show_phone_privacy'], 'hidden' ); ?>>
                                <?php esc_html_e( 'Always Hidden (Platform Messaging Only)', 'cuba-investment-core' ); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION E: Account Information Summary -->
            <div id="section-account" class="card bg-slate-50/70 p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <h2 class="text-base font-heading font-bold text-slate-900 pb-3 border-b border-slate-200 flex items-center justify-between">
                    <span><?php esc_html_e( 'Section E — Account Information', 'cuba-investment-core' ); ?></span>
                    <span class="text-xs font-normal text-slate-500">
                        <?php esc_html_e( 'Role: Business Owner', 'cuba-investment-core' ); ?>
                    </span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 uppercase tracking-wider block font-semibold"><?php esc_html_e( 'Account Type', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-primary mt-1 block"><?php esc_html_e( 'Business Owner', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div>
                        <span class="text-slate-400 uppercase tracking-wider block font-semibold"><?php esc_html_e( 'Email Status', 'cuba-investment-core' ); ?></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 mt-1">
                            ✓ <?php esc_html_e( 'Verified', 'cuba-investment-core' ); ?>
                        </span>
                    </div>

                    <div>
                        <span class="text-slate-400 uppercase tracking-wider block font-semibold"><?php esc_html_e( 'Membership Tier', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-slate-800 mt-1 block"><?php esc_html_e( 'Early Access (Free)', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div>
                        <span class="text-slate-400 uppercase tracking-wider block font-semibold"><?php esc_html_e( 'Account Status', 'cuba-investment-core' ); ?></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 mt-1">
                            ● <?php echo esc_html( ucfirst( $business['account_status'] ) ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex items-center justify-end gap-4 pt-2">
                <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="btn btn-secondary btn-md font-semibold text-slate-600 hover:text-slate-900">
                    <?php esc_html_e( 'Cancel', 'cuba-investment-core' ); ?>
                </a>
                <button type="submit" class="btn btn-primary btn-md px-8 py-3 font-bold shadow-md hover:shadow-lg transition-all cursor-pointer">
                    <?php esc_html_e( 'Save Business Profile', 'cuba-investment-core' ); ?>
                </button>
            </div>
        </form>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
