<?php
/**
 * Template: Create / Edit Investment Opportunity (Multi-Step Form)
 * Route: /business-owner/opportunities/create/
 *
 * Implements:
 * - 6-Step Multi-Step Submission Form
 * - Draft Creation, Resuming & Autosave
 * - Document Upload to Protected Storage & Secure Stream Download
 * - Live Review Summary & Authorized Declaration
 * - Strict Validation for Submission (Transition to Under Review)
 * - Read-Only Preview Mode for Submitted/Published Listings
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Security\NonceManager;
use CubaInvestment\Core\Security\Sanitizer;
use CubaInvestment\Core\Services\OpportunityService;
use CubaInvestment\Core\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();

// Check if editing an existing opportunity
$opp_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$opp    = null;
$is_new = true;

if ( $opp_id > 0 ) {
    $opp = OpportunityService::get_opportunity( $opp_id );
    if ( ! $opp ) {
        wp_safe_redirect( home_url( '/business-owner/opportunities/' ) );
        exit;
    }

    // Role & Ownership security check
    if ( (int) $opp['author_id'] !== (int) $user->ID && ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized: You do not have permission to view or edit this opportunity.', 'cuba-investment-core' ), 403 );
    }

    $is_new = false;
}

// Fetch Business Profile for prefilling if new
$business_profile = ProfileService::get_business_profile( $user->ID );

// Determine if editable
$is_read_only = false;
if ( ! $is_new ) {
    // Only draft or revision_requested can be edited
    $current_status = $opp['status'];
    if ( 'draft' !== $current_status && 'revision_requested' !== $current_status && ! current_user_can( 'manage_options' ) ) {
        $is_read_only = true;
    }
}

// Flash messages
$flash_error   = FormHandler::get_profile_flash_error( $user->ID );
$flash_success = FormHandler::get_profile_flash_success( $user->ID );

if ( empty( $flash_success ) && isset( $_GET['saved'] ) ) {
    $flash_success = __( 'Draft opportunity saved successfully.', 'cuba-investment-core' );
}

// Initial active step (from query string or default 1)
$current_step = isset( $_GET['step'] ) ? max( 1, min( 6, absint( $_GET['step'] ) ) ) : 1;

// Currencies, Sectors, Provinces, Legal Structures
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

$all_provinces = Sanitizer::get_provinces();
$all_legal_structures = Sanitizer::get_legal_structures();

$all_currencies = [
    'USD' => __( 'USD — US Dollar ($)', 'cuba-investment-core' ),
    'EUR' => __( 'EUR — Euro (€)', 'cuba-investment-core' ),
    'CUP' => __( 'CUP — Cuban Peso (₱)', 'cuba-investment-core' ),
    'CAD' => __( 'CAD — Canadian Dollar (C$)', 'cuba-investment-core' ),
    'GBP' => __( 'GBP — British Pound (£)', 'cuba-investment-core' ),
    'MLC' => __( 'MLC — Moneda Libremente Convertible', 'cuba-investment-core' ),
];

$all_stages = [
    'idea'        => __( 'Idea / Conceptual Stage', 'cuba-investment-core' ),
    'pre_revenue' => __( 'Pre-Revenue (Product/Service Development)', 'cuba-investment-core' ),
    'early_stage' => __( 'Early Stage / Seed (Initial Sales & Traction)', 'cuba-investment-core' ),
    'growth'      => __( 'Growth / Scaling (Expanding Operations)', 'cuba-investment-core' ),
    'established' => __( 'Established / Consistently Profitable', 'cuba-investment-core' ),
];

$partnership_options = [
    'Capital Investment'     => __( 'Capital Investment', 'cuba-investment-core' ),
    'Strategic Partnership'  => __( 'Strategic Partnership', 'cuba-investment-core' ),
    'Industry Expertise'     => __( 'Industry Expertise', 'cuba-investment-core' ),
    'Business Development'   => __( 'Business Development Support', 'cuba-investment-core' ),
    'Operational Support'    => __( 'Operational & Supply Chain Support', 'cuba-investment-core' ),
    'Technology Transfer'    => __( 'Technology Transfer / Licensing', 'cuba-investment-core' ),
    'Other'                  => __( 'Other Strategic Collaboration', 'cuba-investment-core' ),
];

// Helper to get form value with fallback to Business Profile for Step 1
$val = function( $key, $fallback = '' ) use ( $opp, $is_new, $business_profile ) {
    if ( $opp && isset( $opp[ $key ] ) && '' !== $opp[ $key ] && null !== $opp[ $key ] ) {
        return $opp[ $key ];
    }
    if ( $is_new && isset( $business_profile[ $key ] ) && '' !== $business_profile[ $key ] ) {
        return $business_profile[ $key ];
    }
    return $fallback;
};

$page_title = $is_new 
    ? __( 'Create Investment Opportunity', 'cuba-investment-core' ) 
    : ( $is_read_only 
        ? sprintf( __( 'Viewing Opportunity: %s', 'cuba-investment-core' ), esc_html( $opp['title'] ) )
        : sprintf( __( 'Edit Draft: %s', 'cuba-investment-core' ), esc_html( $opp['title'] ) ) 
    );

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-6">

        <!-- Flash Notices -->
        <?php if ( ! empty( $flash_success ) ) : ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start gap-3 shadow-xs animate-fade-in" role="alert">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium"><?php echo esc_html( $flash_success ); ?></div>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $flash_error ) ) : ?>
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3 shadow-xs animate-fade-in" role="alert">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium"><?php echo esc_html( $flash_error ); ?></div>
            </div>
        <?php endif; ?>

        <!-- Read-Only Notice -->
        <?php if ( $is_read_only ) : ?>
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-900 text-sm flex items-start gap-3 shadow-xs">
                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1">
                    <p class="font-bold"><?php esc_html_e( 'Opportunity Under Review or Published', 'cuba-investment-core' ); ?></p>
                    <p class="text-xs text-blue-700 mt-0.5">
                        <?php esc_html_e( 'This opportunity is currently in review or published. To preserve compliance and data integrity, listings cannot be edited directly while under administrative evaluation. You can review the complete listing details and download attached documents below.', 'cuba-investment-core' ); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Top Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to My Opportunities', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php if ( $is_new ) : ?>
                        <?php esc_html_e( 'Create Investment Opportunity', 'cuba-investment-core' ); ?>
                    <?php else : ?>
                        <?php echo esc_html( $opp['title'] ); ?>
                    <?php endif; ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Present your business, capital requirements, and strategic partnership goals to accredited investors.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <!-- Header Status & Draft Badge -->
            <div class="flex items-center gap-3">
                <?php if ( ! $is_new ) : ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border <?php echo esc_attr( $opp['badge_class'] ); ?>">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <?php echo esc_html( $opp['status_label'] ); ?>
                    </span>
                <?php endif; ?>

                <div id="cin-autosave-status" class="text-xs text-slate-400 font-medium flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                    <span id="cin-autosave-text"><?php echo $is_new ? esc_html__( 'New Listing', 'cuba-investment-core' ) : esc_html__( 'Draft Saved', 'cuba-investment-core' ); ?></span>
                </div>
            </div>
        </div>

        <!-- MULTI-STEP PROGRESS BAR -->
        <nav aria-label="<?php esc_attr_e( 'Submission Steps', 'cuba-investment-core' ); ?>" class="bg-white rounded-3xl border border-slate-200/80 p-3 sm:p-4 shadow-xs">
            <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                <?php
                $steps = [
                    1 => [ 'title' => __( '1. Overview', 'cuba-investment-core' ), 'desc' => __( 'Business Profile', 'cuba-investment-core' ) ],
                    2 => [ 'title' => __( '2. Capital', 'cuba-investment-core' ),  'desc' => __( 'Use of Funds', 'cuba-investment-core' ) ],
                    3 => [ 'title' => __( '3. Operations', 'cuba-investment-core' ),'desc' => __( 'Milestones', 'cuba-investment-core' ) ],
                    4 => [ 'title' => __( '4. Ownership', 'cuba-investment-core' ), 'desc' => __( 'Key Risks', 'cuba-investment-core' ) ],
                    5 => [ 'title' => __( '5. Partnership', 'cuba-investment-core' ),'desc' => __( 'Preferences', 'cuba-investment-core' ) ],
                    6 => [ 'title' => __( '6. Review', 'cuba-investment-core' ),    'desc' => __( 'Docs & Submit', 'cuba-investment-core' ) ],
                ];
                foreach ( $steps as $idx => $step_info ) :
                    $is_active = ( $current_step === $idx );
                ?>
                    <button type="button" 
                            data-step-target="<?php echo esc_attr( $idx ); ?>"
                            class="cin-step-tab flex flex-col items-center sm:items-start p-2.5 rounded-2xl text-left transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'; ?>">
                        <span class="text-xs font-bold leading-tight"><?php echo esc_html( $step_info['title'] ); ?></span>
                        <span class="hidden sm:inline text-[11px] opacity-80 font-medium truncate"><?php echo esc_html( $step_info['desc'] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </nav>

        <!-- MAIN FORM WRAPPER -->
        <form id="cin-opportunity-form" method="POST" action="<?php echo esc_url( home_url( '/business-owner/opportunities/create/' ) ); ?>" class="space-y-6">
            <?php NonceManager::field( 'cin_save_opportunity_draft' ); ?>
            <input type="hidden" name="cin_action" id="cin_form_action" value="cin_save_opportunity_draft" />
            <input type="hidden" name="opportunity_id" id="cin_opportunity_id" value="<?php echo esc_attr( $opp_id ); ?>" />
            <input type="hidden" name="current_step" id="cin_current_step" value="<?php echo esc_attr( $current_step ); ?>" />

            <!-- ========================================== -->
            <!-- STEP 01: BUSINESS OVERVIEW                -->
            <!-- ========================================== -->
            <section id="cin-step-1" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6 <?php echo 1 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 01 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Business Overview', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Introduce your business and explain the opportunity you want to present to investors.', 'cuba-investment-core' ); ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- 1. Opportunity Title -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_title" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Opportunity Title', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="cin_title" name="title" required
                               value="<?php echo esc_attr( $val( 'title' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., Expansion of Sustainable Agro-Industrial Processing Facility in Matanzas', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                        <p class="text-xs text-slate-400"><?php esc_html_e( 'A clear, descriptive title summarizing the investment initiative (minimum 5 characters).', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- 2. Business Name -->
                    <div class="space-y-1.5">
                        <label for="cin_company_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Name', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="cin_company_name" name="company_name" required
                               value="<?php echo esc_attr( $val( 'company_name' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'Registered or trading company name', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 3. Sector / Industry -->
                    <div class="space-y-1.5">
                        <label for="cin_sector" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Sector / Industry', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <select id="cin_sector" name="sector" required
                                <?php echo $is_read_only ? 'disabled' : ''; ?>
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white">
                            <option value=""><?php esc_html_e( 'Select industry sector...', 'cuba-investment-core' ); ?></option>
                            <?php 
                            $sel_sector = $val( 'sector_slug', $val( 'industry_slug' ) );
                            foreach ( $all_sectors as $s_key => $s_label ) : ?>
                                <option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $sel_sector, $s_key ); ?>>
                                    <?php echo esc_html( $s_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 4. Country -->
                    <div class="space-y-1.5">
                        <label for="cin_country" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Country', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="cin_country" name="country" required
                               value="<?php echo esc_attr( $val( 'country', 'Cuba' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 5. City / Province -->
                    <div class="space-y-1.5">
                        <label for="cin_city" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'City / Province / Region', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <select id="cin_city" name="city" required
                                <?php echo $is_read_only ? 'disabled' : ''; ?>
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white">
                            <option value=""><?php esc_html_e( 'Select province...', 'cuba-investment-core' ); ?></option>
                            <?php 
                            $sel_prov = $val( 'city', $val( 'location' ) );
                            foreach ( $all_provinces as $p_key => $p_name ) : ?>
                                <option value="<?php echo esc_attr( $p_name ); ?>" <?php selected( $sel_prov, $p_name ); ?>>
                                    <?php echo esc_html( $p_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 6. Business Description -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_description" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Description', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="cin_description" name="description" rows="5" required
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Provide a comprehensive overview of your business model, core value proposition, market positioning, and growth vision...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'description' ) ); ?></textarea>
                        <p class="text-xs text-slate-400"><?php esc_html_e( 'Minimum 20 characters. Clearly convey your venture to investors.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- 7. Products or Services -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_products_services" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Products or Services', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="cin_products_services" name="products_services" rows="3" required
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Detail your current product offerings, services rendered, pricing models, or technological assets...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'products_services' ) ); ?></textarea>
                    </div>

                    <!-- 8. Operating History -->
                    <div class="space-y-1.5">
                        <label for="cin_operating_history" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Operating History', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_operating_history" name="operating_history"
                               value="<?php echo esc_attr( $val( 'operating_history' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., 3 years operating in Havana, founded in 2021', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 9. Business Stage -->
                    <div class="space-y-1.5">
                        <label for="cin_business_stage" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Stage', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <select id="cin_business_stage" name="business_stage"
                                <?php echo $is_read_only ? 'disabled' : ''; ?>
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white">
                            <option value=""><?php esc_html_e( 'Select stage...', 'cuba-investment-core' ); ?></option>
                            <?php 
                            $sel_stage = $val( 'business_stage', $val( 'stage' ) );
                            foreach ( $all_stages as $st_key => $st_name ) : ?>
                                <option value="<?php echo esc_attr( $st_key ); ?>" <?php selected( $sel_stage, $st_key ); ?>>
                                    <?php echo esc_html( $st_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 10. Website -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_website" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Website', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="url" id="cin_website" name="website"
                               value="<?php echo esc_url( $val( 'website' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="https://example.com"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- STEP 02: CAPITAL REQUIREMENTS & FUNDS     -->
            <!-- ========================================== -->
            <section id="cin-step-2" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6 <?php echo 2 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 02 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Capital Requirements & Use of Funds', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Explain your funding goals and how capital will be strategically allocated.', 'cuba-investment-core' ); ?></p>
                </div>

                <!-- Platform Notice Banner -->
                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/70 text-xs text-amber-900 flex items-start gap-3">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <span class="font-bold"><?php esc_html_e( 'Regulatory Notice:', 'cuba-investment-core' ); ?></span>
                        <?php esc_html_e( 'Cuba Investment Network provides directory listings and introductions. The platform does not hold custody of funds, execute securities transactions, or promise investment returns.', 'cuba-investment-core' ); ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- 1. Capital Sought -->
                    <div class="space-y-1.5">
                        <label for="cin_capital_sought" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Capital Sought', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" id="cin_capital_sought" name="capital_sought" required
                                   value="<?php echo esc_attr( $val( 'capital_sought' ) ); ?>"
                                   <?php echo $is_read_only ? 'readonly' : ''; ?>
                                   placeholder="50000"
                                   class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                        </div>
                        <p class="text-xs text-slate-400"><?php esc_html_e( 'Numeric amount required for project execution.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- 2. Currency -->
                    <div class="space-y-1.5">
                        <label for="cin_currency" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Currency', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <select id="cin_currency" name="currency" required
                                <?php echo $is_read_only ? 'disabled' : ''; ?>
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white">
                            <?php 
                            $sel_curr = $val( 'currency', 'USD' );
                            foreach ( $all_currencies as $c_code => $c_name ) : ?>
                                <option value="<?php echo esc_attr( $c_code ); ?>" <?php selected( $sel_curr, $c_code ); ?>>
                                    <?php echo esc_html( $c_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400"><?php esc_html_e( 'Currency in which capital is sought.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- 3. Minimum Investment -->
                    <div class="space-y-1.5">
                        <label for="cin_minimum_investment" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Minimum Ticket / Investment Size', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="number" step="0.01" min="0" id="cin_minimum_investment" name="minimum_investment"
                               value="<?php echo esc_attr( $val( 'minimum_investment' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="10000"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 4. Funding Stage -->
                    <div class="space-y-1.5">
                        <label for="cin_funding_stage" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Funding Stage', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_funding_stage" name="funding_stage"
                               value="<?php echo esc_attr( $val( 'funding_stage' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., Seed, Expansion Round, Growth Capital', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 5. Use of Funds -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_use_of_funds" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Use of Funds', 'cuba-investment-core' ); ?> <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="cin_use_of_funds" name="use_of_funds" rows="4" required
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Detail how the capital will be allocated (e.g., 40% cold-storage equipment acquisition, 30% facility renovation, 20% working capital, 10% logistics fleet)...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'use_of_funds' ) ); ?></textarea>
                    </div>

                    <!-- 6. Expected Business Impact -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_expected_impact" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Expected Business Impact', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_expected_impact" name="expected_impact" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Explain how this capital will enhance operational capacity, expand market reach, create jobs, or improve efficiency...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'expected_impact' ) ); ?></textarea>
                    </div>

                    <!-- 7. Proposed Investment or Partnership Structure -->
                    <div class="space-y-1.5">
                        <label for="cin_partnership_structure" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Proposed Investment Structure', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_partnership_structure" name="partnership_structure"
                               value="<?php echo esc_attr( $val( 'partnership_structure' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., Equity Participation, Convertible Note, Revenue Sharing, Equipment Lease', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 8. Additional Capital Notes -->
                    <div class="space-y-1.5">
                        <label for="cin_capital_notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Additional Capital Notes', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_capital_notes" name="capital_notes"
                               value="<?php echo esc_attr( $val( 'capital_notes' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'Any specific timing or disbursement requirements', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- STEP 03: OPERATING PERFORMANCE & MILESTONES -->
            <!-- ========================================== -->
            <section id="cin-step-3" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6 <?php echo 3 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 03 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Operating Performance & Milestones', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Provide relevant information regarding operational traction, past achievements, and physical assets.', 'cuba-investment-core' ); ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- 1. Revenue Information -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_revenue_info" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Revenue Information & Financial Overview', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(If available / Not required to invent)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_revenue_info" name="revenue_info" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'State approximate historical revenue, monthly sales volume, profitability trends, or specify "Pre-revenue / Early-stage development"...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'revenue_info' ) ); ?></textarea>
                    </div>

                    <!-- 2. Major Business Milestones -->
                    <div class="space-y-1.5">
                        <label for="cin_milestones" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Major Business Milestones', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_milestones" name="milestones" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Key milestones achieved to date (e.g. pilot launch, client contracts, facilities completed)...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'milestones' ) ); ?></textarea>
                    </div>

                    <!-- 3. Existing Customers or Market -->
                    <div class="space-y-1.5">
                        <label for="cin_target_market" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Target Customers & Market Demand', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_target_market" name="target_market" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Customer demographics, repeat client base, domestic vs international demand, market share...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'target_market' ) ); ?></textarea>
                    </div>

                    <!-- 4. Key Contracts or Partnerships -->
                    <div class="space-y-1.5">
                        <label for="cin_key_partnerships" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Key Contracts & Supply Agreements', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_key_partnerships" name="key_partnerships" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Existing commercial contracts, supplier agreements, letters of intent, distribution networks...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'key_partnerships' ) ); ?></textarea>
                    </div>

                    <!-- 5. Business Assets -->
                    <div class="space-y-1.5">
                        <label for="cin_business_assets" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Business Assets & Infrastructure', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_business_assets" name="business_assets" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Owned or leased real estate, machinery, intellectual property, vehicles, production facilities...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'business_assets' ) ); ?></textarea>
                    </div>

                    <!-- 6. Licences or Permits -->
                    <div class="space-y-1.5">
                        <label for="cin_licences_permits" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Licences, Permits & Legal Authorizations', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_licences_permits" name="licences_permits"
                               value="<?php echo esc_attr( $val( 'licences_permits' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., MEP approved MIPYME permit, sanitary registration, import/export license', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 7. Current Projects -->
                    <div class="space-y-1.5">
                        <label for="cin_current_projects" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Current Projects & Immediate Initiatives', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_current_projects" name="current_projects"
                               value="<?php echo esc_attr( $val( 'current_projects' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., Facility expansion phase 1, solar rooftop installation', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- STEP 04: MANAGEMENT, OWNERSHIP & RISKS    -->
            <!-- ========================================== -->
            <section id="cin-step-4" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6 <?php echo 4 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 04 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Management, Ownership & Key Risks', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Demonstrate team capability, ownership governance, and transparent risk assessments.', 'cuba-investment-core' ); ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- 1. Ownership Structure -->
                    <div class="space-y-1.5">
                        <label for="cin_ownership_structure" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Ownership / Legal Structure', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <select id="cin_ownership_structure" name="ownership_structure"
                                <?php echo $is_read_only ? 'disabled' : ''; ?>
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-white">
                            <?php 
                            $sel_own = $val( 'ownership_structure_key', $val( 'ownership_structure' ) );
                            foreach ( $all_legal_structures as $l_key => $l_label ) : ?>
                                <option value="<?php echo esc_attr( $l_key ); ?>" <?php selected( $sel_own, $l_key ); ?>>
                                    <?php echo esc_html( $l_label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 2. Management Team Overview -->
                    <div class="space-y-1.5">
                        <label for="cin_management_overview" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Management Team Overview', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <input type="text" id="cin_management_overview" name="management_overview"
                               value="<?php echo esc_attr( $val( 'management_overview' ) ); ?>"
                               <?php echo $is_read_only ? 'readonly' : ''; ?>
                               placeholder="<?php esc_attr_e( 'e.g., 4 founding partners with combined 35 years in agritech and logistics', 'cuba-investment-core' ); ?>"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" />
                    </div>

                    <!-- 3. Key Team Members -->
                    <div class="md:col-span-2 space-y-1.5">
                        <label for="cin_key_team_members" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Key Team Members & Bios', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_key_team_members" name="key_team_members" rows="3"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'List key founders, executives, or technical leads with their backgrounds and roles...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'key_team_members' ) ); ?></textarea>
                    </div>

                    <!-- 4. Existing Liabilities -->
                    <div class="space-y-1.5">
                        <label for="cin_existing_liabilities" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Existing Liabilities / Outstanding Debt', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(If applicable)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_existing_liabilities" name="existing_liabilities" rows="2"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Detail any commercial bank loans, vendor debts, or note "No material debt"...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'existing_liabilities' ) ); ?></textarea>
                    </div>

                    <!-- 5. Operational Risks -->
                    <div class="space-y-1.5">
                        <label for="cin_operational_risks" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Operational Risks & Mitigations', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_operational_risks" name="operational_risks" rows="2"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Supply chain vulnerabilities, power stability, equipment parts availability...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'operational_risks' ) ); ?></textarea>
                    </div>

                    <!-- 6. Financial Risks -->
                    <div class="space-y-1.5">
                        <label for="cin_financial_risks" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Financial & Currency Risks', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_financial_risks" name="financial_risks" rows="2"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Exchange rate fluctuations, cash flow seasonality, raw material inflation...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'financial_risks' ) ); ?></textarea>
                    </div>

                    <!-- 7. Regulatory & Cross-Border Risks -->
                    <div class="space-y-1.5">
                        <label for="cin_regulatory_risks" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Regulatory & Cross-Border Factors', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <textarea id="cin_regulatory_risks" name="regulatory_risks" rows="2"
                                  <?php echo $is_read_only ? 'readonly' : ''; ?>
                                  placeholder="<?php esc_attr_e( 'Sector regulatory compliance, foreign exchange procedures, international trade channels...', 'cuba-investment-core' ); ?>"
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'regulatory_risks' ) ); ?></textarea>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- STEP 05: PARTNERSHIP REQUIREMENTS        -->
            <!-- ========================================== -->
            <section id="cin-step-5" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6 <?php echo 5 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 05 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Partnership Requirements', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Explain the type of strategic investor or industry partner your business is seeking.', 'cuba-investment-core' ); ?></p>
                </div>

                <div class="space-y-6">
                    <!-- 1. Partnership Types Checkboxes -->
                    <div class="space-y-3">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            <?php esc_html_e( 'Type of Partnership Sought', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Select all that apply)', 'cuba-investment-core' ); ?></span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <?php 
                            $sel_types = (array) $val( 'partnership_types', [] );
                            foreach ( $partnership_options as $opt_val => $opt_label ) :
                                $checked = in_array( $opt_val, $sel_types, true );
                            ?>
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200/80 hover:border-primary/40 bg-slate-50/50 hover:bg-white transition-all cursor-pointer">
                                    <input type="checkbox" name="partnership_types[]" value="<?php echo esc_attr( $opt_val ); ?>"
                                           <?php checked( $checked ); ?>
                                           <?php echo $is_read_only ? 'disabled' : ''; ?>
                                           class="mt-0.5 w-4 h-4 rounded text-primary focus:ring-primary/20 border-slate-300" />
                                    <span class="text-xs font-semibold text-slate-800 leading-snug"><?php echo esc_html( $opt_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- 2. Strategic Expertise Required -->
                        <div class="space-y-1.5">
                            <label for="cin_strategic_expertise" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                <?php esc_html_e( 'Strategic Expertise Desired', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                            </label>
                            <textarea id="cin_strategic_expertise" name="strategic_expertise" rows="3"
                                      <?php echo $is_read_only ? 'readonly' : ''; ?>
                                      placeholder="<?php esc_attr_e( 'e.g., Export market connections, European food safety certification expertise, renewable energy engineering...', 'cuba-investment-core' ); ?>"
                                      class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'strategic_expertise' ) ); ?></textarea>
                        </div>

                        <!-- 3. Business Development Support Needed -->
                        <div class="space-y-1.5">
                            <label for="cin_bizdev_support" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                <?php esc_html_e( 'Business Development Support Needed', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                            </label>
                            <textarea id="cin_bizdev_support" name="bizdev_support" rows="3"
                                      <?php echo $is_read_only ? 'readonly' : ''; ?>
                                      placeholder="<?php esc_attr_e( 'e.g., Introduction to international equipment vendors, supply chain contracts, brand licensing...', 'cuba-investment-core' ); ?>"
                                      class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'bizdev_support' ) ); ?></textarea>
                        </div>

                        <!-- 4. Preferred Partner Characteristics -->
                        <div class="md:col-span-2 space-y-1.5">
                            <label for="cin_partner_characteristics" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                <?php esc_html_e( 'Preferred Partner Characteristics', 'cuba-investment-core' ); ?> <span class="text-slate-400 font-normal"><?php esc_html_e( '(Optional)', 'cuba-investment-core' ); ?></span>
                            </label>
                            <textarea id="cin_partner_characteristics" name="partner_characteristics" rows="3"
                                      <?php echo $is_read_only ? 'readonly' : ''; ?>
                                      placeholder="<?php esc_attr_e( 'Describe your ideal partner profile (e.g., patient long-term investment horizon, values sustainable development, collaborative board participation)...', 'cuba-investment-core' ); ?>"
                                      class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"><?php echo esc_textarea( $val( 'partner_characteristics' ) ); ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- STEP 06: SUPPORTING DOCUMENTS & REVIEW    -->
            <!-- ========================================== -->
            <section id="cin-step-6" class="cin-form-step bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-8 <?php echo 6 === $current_step ? '' : 'hidden'; ?>">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary"><?php esc_html_e( 'Step 06 of 06', 'cuba-investment-core' ); ?></span>
                    <h2 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900 mt-1"><?php esc_html_e( 'Supporting Documents & Final Review', 'cuba-investment-core' ); ?></h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5"><?php esc_html_e( 'Upload supporting business documents, inspect the complete opportunity summary, and submit for review.', 'cuba-investment-core' ); ?></p>
                </div>

                <!-- 1. DOCUMENT UPLOAD SECTION -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider"><?php esc_html_e( 'Supporting Documents', 'cuba-investment-core' ); ?></h3>
                            <p class="text-xs text-slate-500"><?php esc_html_e( 'Pitch deck, business plan, financial statements, or commercial licences (PDF, DOCX, XLSX, PNG, JPG — Max 10MB).', 'cuba-investment-core' ); ?></p>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <?php esc_html_e( 'Protected Storage', 'cuba-investment-core' ); ?>
                        </span>
                    </div>

                    <!-- Uploaded Documents List -->
                    <div id="cin-documents-container" class="space-y-2">
                        <?php
                        $docs = (array) $val( 'documents', [] );
                        if ( empty( $docs ) ) :
                        ?>
                            <div id="cin-docs-empty" class="p-6 rounded-2xl border-2 border-dashed border-slate-200 text-center">
                                <p class="text-xs text-slate-400"><?php esc_html_e( 'No documents uploaded yet. You can attach business summaries, projections, or legal permits.', 'cuba-investment-core' ); ?></p>
                            </div>
                        <?php else : ?>
                            <?php foreach ( $docs as $d ) : ?>
                                <div class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 bg-slate-50/60 hover:bg-white transition-all text-xs" data-doc-id="<?php echo esc_attr( $d['id'] ); ?>">
                                    <div class="flex items-center gap-3 truncate">
                                        <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold uppercase shrink-0">
                                            <?php echo esc_html( strtoupper( $d['ext'] ?? 'DOC' ) ); ?>
                                        </div>
                                        <div class="truncate">
                                            <p class="font-bold text-slate-800 truncate"><?php echo esc_html( $d['name'] ); ?></p>
                                            <p class="text-[11px] text-slate-400">
                                                <?php echo esc_html( size_format( $d['size'] ?? 0 ) ); ?> &bull; <?php echo esc_html( $d['uploaded_at'] ?? '' ); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <!-- Secure Download -->
                                        <a href="<?php echo esc_url( add_query_arg( [ 'cin_action' => 'download_doc', 'opp_id' => $opp_id, 'doc_id' => $d['id'] ], home_url() ) ); ?>"
                                           class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition-colors">
                                            <?php esc_html_e( 'Download', 'cuba-investment-core' ); ?>
                                        </a>

                                        <?php if ( ! $is_read_only ) : ?>
                                            <!-- Remove Document button -->
                                            <button type="button" 
                                                    data-delete-doc-id="<?php echo esc_attr( $d['id'] ); ?>"
                                                    class="cin-btn-delete-doc p-1.5 rounded-xl text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors"
                                                    title="<?php esc_attr_e( 'Delete Document', 'cuba-investment-core' ); ?>">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Upload Input (If editable) -->
                    <?php if ( ! $is_read_only ) : ?>
                        <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/40 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="w-full sm:w-auto">
                                <label for="cin_document_input" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:border-primary/50 shadow-xs transition-all">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span id="cin-upload-label"><?php esc_html_e( 'Choose Supporting Document...', 'cuba-investment-core' ); ?></span>
                                </label>
                                <input type="file" id="cin_document_input" class="hidden" accept=".pdf,.docx,.xlsx,.png,.jpg,.jpeg" />
                            </div>

                            <button type="button" id="cin-btn-upload-file" disabled
                                    class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-primary text-white text-xs font-bold shadow-xs hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                <?php esc_html_e( 'Upload File', 'cuba-investment-core' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. LIVE REVIEW SUMMARY ACCORDION -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider"><?php esc_html_e( 'Listing Summary Inspection', 'cuba-investment-core' ); ?></h3>
                        <span class="text-xs text-slate-400"><?php esc_html_e( 'Click any section to edit', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Summary 1: Overview -->
                        <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/30 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm uppercase tracking-wider"><?php esc_html_e( '1. Business Overview', 'cuba-investment-core' ); ?></span>
                                <button type="button" data-jump-step="1" class="text-primary font-bold hover:underline"><?php esc_html_e( 'Edit', 'cuba-investment-core' ); ?></button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600">
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Title:', 'cuba-investment-core' ); ?></span> <span id="rev-title" class="font-medium"><?php echo esc_html( $val( 'title', '—' ) ); ?></span></div>
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Business:', 'cuba-investment-core' ); ?></span> <span id="rev-company" class="font-medium"><?php echo esc_html( $val( 'company_name', '—' ) ); ?></span></div>
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Sector:', 'cuba-investment-core' ); ?></span> <span id="rev-sector" class="font-medium"><?php echo esc_html( $val( 'sector_name', '—' ) ); ?></span></div>
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Location:', 'cuba-investment-core' ); ?></span> <span id="rev-location" class="font-medium"><?php echo esc_html( $val( 'city', '—' ) ); ?>, Cuba</span></div>
                            </div>
                        </div>

                        <!-- Summary 2: Capital -->
                        <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/30 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm uppercase tracking-wider"><?php esc_html_e( '2. Capital Requirements', 'cuba-investment-core' ); ?></span>
                                <button type="button" data-jump-step="2" class="text-primary font-bold hover:underline"><?php esc_html_e( 'Edit', 'cuba-investment-core' ); ?></button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600">
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Capital Sought:', 'cuba-investment-core' ); ?></span> <span id="rev-capital" class="font-bold text-primary"><?php echo esc_html( $val( 'currency', 'USD' ) ); ?> <?php echo esc_html( number_format( (float) $val( 'capital_sought', 0 ) ) ); ?></span></div>
                                <div><span class="font-semibold text-slate-700"><?php esc_html_e( 'Minimum Ticket:', 'cuba-investment-core' ); ?></span> <span id="rev-min-inv"><?php echo esc_html( $val( 'minimum_investment' ) ? number_format( (float) $val( 'minimum_investment' ) ) : 'Flexible' ); ?></span></div>
                            </div>
                        </div>

                        <!-- Summary 3: Partnership -->
                        <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/30 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm uppercase tracking-wider"><?php esc_html_e( '3. Partnership Details', 'cuba-investment-core' ); ?></span>
                                <button type="button" data-jump-step="5" class="text-primary font-bold hover:underline"><?php esc_html_e( 'Edit', 'cuba-investment-core' ); ?></button>
                            </div>
                            <div class="text-slate-600">
                                <span class="font-semibold text-slate-700"><?php esc_html_e( 'Structure:', 'cuba-investment-core' ); ?></span> 
                                <span id="rev-structure"><?php echo esc_html( $val( 'partnership_structure', 'Direct negotiation with business owner' ) ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. LEGAL DECLARATION & TERMS -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <label class="flex items-start gap-3 p-4 rounded-2xl border-2 border-emerald-100 bg-emerald-50/40 cursor-pointer">
                        <input type="checkbox" id="cin_declaration_confirmed" name="declaration_confirmed" value="1"
                               <?php checked( (bool) $val( 'declaration_confirmed' ) ); ?>
                               <?php echo $is_read_only ? 'disabled' : ''; ?>
                               class="mt-0.5 w-4 h-4 rounded text-primary focus:ring-primary/20 border-slate-300" />
                        <div class="space-y-1 text-xs">
                            <span class="font-bold text-slate-900 leading-snug block">
                                <?php esc_html_e( 'I confirm that I am authorized to submit this business information and that the information provided is accurate to the best of my knowledge.', 'cuba-investment-core' ); ?>
                            </span>
                            <span class="text-slate-500 block leading-relaxed">
                                <?php esc_html_e( 'By submitting, you acknowledge that Cuba Investment Network is an introduction and directory platform. No investment advice is rendered, and all due diligence remains the responsibility of negotiating parties.', 'cuba-investment-core' ); ?>
                            </span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- FORM CONTROLS FOOTER                      -->
            <!-- ========================================== -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Previous Button -->
                <div>
                    <button type="button" id="cin-btn-prev" class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                        &larr; <?php esc_html_e( 'Previous Step', 'cuba-investment-core' ); ?>
                    </button>
                </div>

                <!-- Actions: Save Draft / Next / Submit -->
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <?php if ( ! $is_read_only ) : ?>
                        <!-- Save Draft Button -->
                        <button type="button" id="cin-btn-save-draft" class="px-5 py-2.5 rounded-2xl border border-primary/30 text-xs font-bold text-primary hover:bg-primary/5 transition-all">
                            <?php esc_html_e( 'Save Draft', 'cuba-investment-core' ); ?>
                        </button>

                        <!-- Next Step Button (Visible steps 1-5) -->
                        <button type="button" id="cin-btn-next" class="px-6 py-2.5 rounded-2xl bg-primary text-white text-xs font-bold shadow-xs hover:bg-accent transition-all">
                            <?php esc_html_e( 'Continue', 'cuba-investment-core' ); ?> &rarr;
                        </button>

                        <!-- Final Submit Button (Visible step 6 only) -->
                        <button type="button" id="cin-btn-submit-review" class="hidden px-6 py-2.5 rounded-2xl bg-emerald-600 text-white text-xs font-bold shadow-xs hover:bg-emerald-700 transition-all">
                            <?php esc_html_e( 'Submit for Review', 'cuba-investment-core' ); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="px-6 py-2.5 rounded-2xl bg-primary text-white text-xs font-bold shadow-xs hover:bg-accent transition-all">
                            <?php esc_html_e( 'Back to Opportunities', 'cuba-investment-core' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- DELETE DRAFT MODAL (If existing draft) -->
        <?php if ( ! $is_new && ! $is_read_only ) : ?>
            <div class="pt-2 text-right">
                <button type="button" id="cin-btn-open-delete" class="text-xs font-bold text-rose-500 hover:text-rose-700 transition-colors">
                    <?php esc_html_e( 'Remove this draft permanently', 'cuba-investment-core' ); ?>
                </button>
            </div>

            <div id="cin-delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4 animate-scale-up">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900"><?php esc_html_e( 'Delete Draft Opportunity?', 'cuba-investment-core' ); ?></h3>
                        <p class="text-xs text-slate-500 mt-1"><?php esc_html_e( 'This action cannot be undone. All incomplete information and uploaded documents for this draft will be removed.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <form method="POST" action="<?php echo esc_url( home_url( '/business-owner/opportunities/' ) ); ?>" class="flex items-center justify-end gap-2 pt-2">
                        <?php NonceManager::field( 'cin_delete_opportunity_draft' ); ?>
                        <input type="hidden" name="cin_action" value="cin_delete_opportunity_draft" />
                        <input type="hidden" name="opportunity_id" value="<?php echo esc_attr( $opp_id ); ?>" />
                        
                        <button type="button" id="cin-btn-cancel-delete" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                            <?php esc_html_e( 'Cancel', 'cuba-investment-core' ); ?>
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white hover:bg-rose-700 transition-colors">
                            <?php esc_html_e( 'Yes, Delete Draft', 'cuba-investment-core' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
</div>

<!-- JAVASCRIPT LOGIC FOR MULTI-STEP NAVIGATION, AUTOSAVE & FILE UPLOADS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = parseInt(document.getElementById('cin-current_step')?.value || '1', 10);
    const maxSteps = 6;
    const isReadOnly = <?php echo $is_read_only ? 'true' : 'false'; ?>;
    const form = document.getElementById('cin-opportunity-form');
    const prevBtn = document.getElementById('cin-btn-prev');
    const nextBtn = document.getElementById('cin-btn-next');
    const submitBtn = document.getElementById('cin-btn-submit-review');
    const saveDraftBtn = document.getElementById('cin-btn-save-draft');
    const statusText = document.getElementById('cin-autosave-text');
    const oppIdInput = document.getElementById('cin_opportunity_id');
    const restNonce = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';
    const restDraftUrl = '<?php echo esc_js( rest_url( 'cuba-investment/v1/opportunities/draft' ) ); ?>';
    const restSubmitUrl = '<?php echo esc_js( rest_url( 'cuba-investment/v1/opportunities/' ) ); ?>';
    const restDocsUrl = '<?php echo esc_js( rest_url( 'cuba-investment/v1/opportunities/' ) ); ?>';

    // Step switching function
    function showStep(step) {
        step = Math.max(1, Math.min(maxSteps, step));
        currentStep = step;
        document.getElementById('cin_current_step').value = step;

        // Toggle sections
        for (let i = 1; i <= maxSteps; i++) {
            const sec = document.getElementById('cin-step-' + i);
            if (sec) {
                if (i === step) {
                    sec.classList.remove('hidden');
                } else {
                    sec.classList.add('hidden');
                }
            }
        }

        // Toggle tab active styles
        document.querySelectorAll('.cin-step-tab').forEach(tab => {
            const target = parseInt(tab.getAttribute('data-step-target'), 10);
            if (target === step) {
                tab.className = 'cin-step-tab flex flex-col items-center sm:items-start p-2.5 rounded-2xl text-left transition-all bg-primary text-white shadow-xs';
            } else {
                tab.className = 'cin-step-tab flex flex-col items-center sm:items-start p-2.5 rounded-2xl text-left transition-all text-slate-600 hover:bg-slate-50';
            }
        });

        // Prev / Next button state
        if (prevBtn) {
            prevBtn.disabled = (step === 1);
        }
        if (nextBtn) {
            if (step === maxSteps) {
                nextBtn.classList.add('hidden');
                if (submitBtn && !isReadOnly) submitBtn.classList.remove('hidden');
            } else {
                nextBtn.classList.remove('hidden');
                if (submitBtn) submitBtn.classList.add('hidden');
            }
        }

        // Update live review summary values if on step 6
        if (step === 6) {
            updateReviewSummary();
        }

        // Scroll to form top smoothly
        const mainContent = document.getElementById('dashboard-main-content');
        if (mainContent) {
            mainContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Tab buttons click
    document.querySelectorAll('.cin-step-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const target = parseInt(this.getAttribute('data-step-target'), 10);
            showStep(target);
        });
    });

    // Jump buttons from Step 6 review summary
    document.querySelectorAll('[data-jump-step]').forEach(btn => {
        btn.addEventListener('click', function() {
            const target = parseInt(this.getAttribute('data-jump-step'), 10);
            showStep(target);
        });
    });

    // Next / Prev actions
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            showStep(currentStep - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            // Light client-side validation per step
            if (validateStep(currentStep)) {
                showStep(currentStep + 1);
                // Trigger background autosave
                triggerAutosave();
            }
        });
    }

    // Step validation helper
    function validateStep(step) {
        if (isReadOnly) return true;
        let valid = true;

        if (step === 1) {
            const title = document.getElementById('cin_title');
            const comp = document.getElementById('cin_company_name');
            const sect = document.getElementById('cin_sector');
            const desc = document.getElementById('cin_description');

            if (!title.value.trim() || title.value.trim().length < 5) {
                alert('<?php echo esc_js( __( 'Please provide an opportunity title (at least 5 characters).', 'cuba-investment-core' ) ); ?>');
                title.focus();
                return false;
            }
            if (!comp.value.trim()) {
                alert('<?php echo esc_js( __( 'Please provide the business name.', 'cuba-investment-core' ) ); ?>');
                comp.focus();
                return false;
            }
            if (!sect.value.trim()) {
                alert('<?php echo esc_js( __( 'Please select a business sector / industry.', 'cuba-investment-core' ) ); ?>');
                sect.focus();
                return false;
            }
            if (!desc.value.trim() || desc.value.trim().length < 20) {
                alert('<?php echo esc_js( __( 'Please provide a business description (at least 20 characters).', 'cuba-investment-core' ) ); ?>');
                desc.focus();
                return false;
            }
        } else if (step === 2) {
            const cap = document.getElementById('cin_capital_sought');
            if (cap && (parseFloat(cap.value) <= 0 || isNaN(parseFloat(cap.value)))) {
                // If user is just moving forward, allow it as draft, but warn if negative
                if (parseFloat(cap.value) < 0) {
                    alert('<?php echo esc_js( __( 'Capital sought cannot be negative.', 'cuba-investment-core' ) ); ?>');
                    cap.focus();
                    return false;
                }
            }
        }

        return valid;
    }

    // Live review summary updater
    function updateReviewSummary() {
        const titleEl = document.getElementById('cin_title');
        const compEl = document.getElementById('cin_company_name');
        const sectEl = document.getElementById('cin_sector');
        const cityEl = document.getElementById('cin_city');
        const capEl = document.getElementById('cin_capital_sought');
        const currEl = document.getElementById('cin_currency');

        if (document.getElementById('rev-title') && titleEl) document.getElementById('rev-title').textContent = titleEl.value || '—';
        if (document.getElementById('rev-company') && compEl) document.getElementById('rev-company').textContent = compEl.value || '—';
        if (document.getElementById('rev-sector') && sectEl) {
            const sText = sectEl.options[sectEl.selectedIndex]?.text;
            document.getElementById('rev-sector').textContent = sText || '—';
        }
        if (document.getElementById('rev-location') && cityEl) {
            const cText = cityEl.options[cityEl.selectedIndex]?.text;
            document.getElementById('rev-location').textContent = (cText || 'Cuba') + ', Cuba';
        }
        if (document.getElementById('rev-capital') && capEl && currEl) {
            const curr = currEl.value || 'USD';
            const val = parseFloat(capEl.value) || 0;
            document.getElementById('rev-capital').textContent = curr + ' ' + val.toLocaleString();
        }
    }

    // Debounced Autosave via REST
    let autosaveTimer = null;
    function triggerAutosave() {
        if (isReadOnly) return;
        if (statusText) statusText.textContent = '<?php echo esc_js( __( 'Autosaving...', 'cuba-investment-core' ) ); ?>';

        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key.endsWith('[]')) {
                const cleanKey = key.slice(0, -2);
                if (!data[cleanKey]) data[cleanKey] = [];
                data[cleanKey].push(value);
            } else {
                data[key] = value;
            }
        });

        fetch(restDraftUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': restNonce
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.opportunity_id) {
                if (oppIdInput && (!oppIdInput.value || oppIdInput.value === '0')) {
                    oppIdInput.value = res.opportunity_id;
                    // Update URL without reloading
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('id', res.opportunity_id);
                    window.history.replaceState({}, '', newUrl.toString());
                }
                if (statusText) statusText.textContent = '<?php echo esc_js( __( 'Draft Saved', 'cuba-investment-core' ) ); ?>';
            } else {
                if (statusText) statusText.textContent = '<?php echo esc_js( __( 'Save draft manually', 'cuba-investment-core' ) ); ?>';
            }
        })
        .catch(() => {
            if (statusText) statusText.textContent = '<?php echo esc_js( __( 'Offline / Unsaved', 'cuba-investment-core' ) ); ?>';
        });
    }

    // Autosave on field changes with 3.5s debounce
    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', function() {
            if (statusText) statusText.textContent = '<?php echo esc_js( __( 'Unsaved changes', 'cuba-investment-core' ) ); ?>';
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(triggerAutosave, 3500);
        });
    });

    // Save Draft Button Click (Form submit or REST save)
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function() {
            saveDraftBtn.disabled = true;
            saveDraftBtn.textContent = '<?php echo esc_js( __( 'Saving...', 'cuba-investment-core' ) ); ?>';
            document.getElementById('cin_form_action').value = 'cin_save_opportunity_draft';
            form.submit();
        });
    }

    // Submit for Review Button Click (Step 6)
    if (submitBtn) {
        submitBtn.addEventListener('click', function() {
            // Check declaration checkbox
            const decl = document.getElementById('cin_declaration_confirmed');
            if (decl && !decl.checked) {
                alert('<?php echo esc_js( __( 'Please confirm the authorized submission declaration before submitting for review.', 'cuba-investment-core' ) ); ?>');
                decl.focus();
                return;
            }

            // Verify all required fields from earlier steps
            const title = document.getElementById('cin_title')?.value.trim();
            const comp = document.getElementById('cin_company_name')?.value.trim();
            const sect = document.getElementById('cin_sector')?.value.trim();
            const city = document.getElementById('cin_city')?.value.trim();
            const desc = document.getElementById('cin_description')?.value.trim();
            const prods = document.getElementById('cin_products_services')?.value.trim();
            const cap = parseFloat(document.getElementById('cin_capital_sought')?.value || '0');
            const useFund = document.getElementById('cin_use_of_funds')?.value.trim();

            if (!title || title.length < 5) {
                alert('<?php echo esc_js( __( 'Please provide an Opportunity Title with at least 5 characters.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (!comp) {
                alert('<?php echo esc_js( __( 'Please provide your Business Name.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (!sect) {
                alert('<?php echo esc_js( __( 'Please select your Business Sector / Industry.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (!city) {
                alert('<?php echo esc_js( __( 'Please select your City / Province.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (!desc || desc.length < 20) {
                alert('<?php echo esc_js( __( 'Please provide a Business Description of at least 20 characters.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (!prods) {
                alert('<?php echo esc_js( __( 'Please describe your Products or Services.', 'cuba-investment-core' ) ); ?>');
                showStep(1);
                return;
            }
            if (isNaN(cap) || cap <= 0) {
                alert('<?php echo esc_js( __( 'Please specify a valid Capital Sought amount greater than 0.', 'cuba-investment-core' ) ); ?>');
                showStep(2);
                return;
            }
            if (!useFund) {
                alert('<?php echo esc_js( __( 'Please detail the Use of Funds.', 'cuba-investment-core' ) ); ?>');
                showStep(2);
                return;
            }

            if (!confirm('<?php echo esc_js( __( 'Are you sure you want to submit this opportunity for administrative review? Once submitted, it will be placed in review mode.', 'cuba-investment-core' ) ); ?>')) {
                return;
            }

            // Set action and submit form
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php echo esc_js( __( 'Submitting...', 'cuba-investment-core' ) ); ?>';
            
            // If opportunity is not saved yet, save first via sync form POST
            document.getElementById('cin_form_action').value = 'cin_submit_opportunity';
            form.submit();
        });
    }

    // Document Upload Handling
    const fileInput = document.getElementById('cin_document_input');
    const uploadBtn = document.getElementById('cin-btn-upload-file');
    const uploadLabel = document.getElementById('cin-upload-label');

    if (fileInput && uploadBtn) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                uploadLabel.textContent = this.files[0].name;
                uploadBtn.disabled = false;
            } else {
                uploadLabel.textContent = '<?php echo esc_js( __( 'Choose Supporting Document...', 'cuba-investment-core' ) ); ?>';
                uploadBtn.disabled = true;
            }
        });

        uploadBtn.addEventListener('click', function() {
            if (!fileInput.files || fileInput.files.length === 0) return;

            const oppId = oppIdInput.value;
            if (!oppId || oppId === '0') {
                alert('<?php echo esc_js( __( 'Please save your draft first before attaching documents.', 'cuba-investment-core' ) ); ?>');
                return;
            }

            const formData = new FormData();
            formData.append('document', fileInput.files[0]);

            uploadBtn.disabled = true;
            uploadBtn.textContent = '<?php echo esc_js( __( 'Uploading...', 'cuba-investment-core' ) ); ?>';

            fetch(restDocsUrl + oppId + '/documents', {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': restNonce
                },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                uploadBtn.textContent = '<?php echo esc_js( __( 'Upload File', 'cuba-investment-core' ) ); ?>';
                if (res.success) {
                    alert('<?php echo esc_js( __( 'Document uploaded securely.', 'cuba-investment-core' ) ); ?>');
                    window.location.reload();
                } else {
                    alert(res.message || '<?php echo esc_js( __( 'Upload failed. Please ensure file is valid and under 10MB.', 'cuba-investment-core' ) ); ?>');
                    uploadBtn.disabled = false;
                }
            })
            .catch(() => {
                alert('<?php echo esc_js( __( 'Upload request error.', 'cuba-investment-core' ) ); ?>');
                uploadBtn.disabled = false;
                uploadBtn.textContent = '<?php echo esc_js( __( 'Upload File', 'cuba-investment-core' ) ); ?>';
            });
        });
    }

    // Document Delete Buttons
    document.querySelectorAll('.cin-btn-delete-doc').forEach(btn => {
        btn.addEventListener('click', function() {
            const docId = this.getAttribute('data-delete-doc-id');
            const oppId = oppIdInput.value;
            if (!docId || !oppId) return;

            if (!confirm('<?php echo esc_js( __( 'Are you sure you want to remove this document?', 'cuba-investment-core' ) ); ?>')) {
                return;
            }

            fetch(restDocsUrl + oppId + '/documents/' + docId, {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': restNonce
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const row = document.querySelector(`[data-doc-id="${docId}"]`);
                    if (row) row.remove();
                } else {
                    alert(res.message || '<?php echo esc_js( __( 'Failed to remove document.', 'cuba-investment-core' ) ); ?>');
                }
            })
            .catch(() => {
                alert('<?php echo esc_js( __( 'Failed to remove document.', 'cuba-investment-core' ) ); ?>');
            });
        });
    });

    // Delete Draft Modal
    const openDeleteBtn = document.getElementById('cin-btn-open-delete');
    const deleteModal = document.getElementById('cin-delete-modal');
    const cancelDeleteBtn = document.getElementById('cin-btn-cancel-delete');

    if (openDeleteBtn && deleteModal) {
        openDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('hidden');
        });
    }
    if (cancelDeleteBtn && deleteModal) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.add('hidden');
        });
    }

    // Initialize current step
    showStep(currentStep);
});
</script>
