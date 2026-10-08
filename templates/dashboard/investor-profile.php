<?php
/**
 * Template: Investor Profile Management
 * Route: /investor/profile/ (and /dashboard/investor/profile/)
 *
 * Implements Section A (Personal Info), Section B (Investment Preferences),
 * Section C (Profile Visibility/Privacy), and Section D (Account Information).
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
$profile = ProfileService::get_investor_profile( $user->ID );

// Flash notices
$error_msg   = FormHandler::get_profile_flash_error( $user->ID );
$success_msg = FormHandler::get_profile_flash_success( $user->ID );
if ( empty( $success_msg ) && isset( $_GET['updated'] ) ) {
    $success_msg = __( 'Investor profile saved successfully.', 'cuba-investment-core' );
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

// Available Capital Ranges
$all_ranges = [
    'under_25k' => __( 'Under $25,000 USD', 'cuba-investment-core' ),
    '25k_50k'   => __( '$25,000 – $50,000 USD', 'cuba-investment-core' ),
    '50k_100k'  => __( '$50,000 – $100,000 USD', 'cuba-investment-core' ),
    '100k_250k' => __( '$100,000 – $250,000 USD', 'cuba-investment-core' ),
    '250k_500k' => __( '$250,000 – $500,000 USD', 'cuba-investment-core' ),
    '500k_plus' => __( '$500,000+ USD', 'cuba-investment-core' ),
];

// Available Cuban Provinces
$all_provinces = [
    'All Cuba'           => __( 'All Cuba (Nationwide)', 'cuba-investment-core' ),
    'La Habana'          => 'La Habana',
    'Matanzas'           => 'Matanzas / Varadero',
    'Villa Clara'        => 'Villa Clara',
    'Cienfuegos'         => 'Cienfuegos',
    'Sancti Spíritus'    => 'Sancti Spíritus / Trinidad',
    'Camagüey'           => 'Camagüey',
    'Holguín'            => 'Holguín',
    'Santiago de Cuba'   => 'Santiago de Cuba',
    'Pinar del Río'      => 'Pinar del Río',
    'Artemisa'           => 'Artemisa',
    'Mayabeque'          => 'Mayabeque',
    'Ciego de Ávila'     => 'Ciego de Ávila',
    'Las Tunas'          => 'Las Tunas',
    'Granma'             => 'Granma',
    'Guantánamo'         => 'Guantánamo',
    'Isla de la Juventud'=> 'Isla de la Juventud',
];

// Available Partnership Interests
$all_interests = [
    'equity'          => __( 'Equity Investment (Direct Stake)', 'cuba-investment-core' ),
    'joint_venture'   => __( 'Joint Venture (Empresa Mixta / Strategic)', 'cuba-investment-core' ),
    'debt_financing'  => __( 'Debt Financing / Commercial Loans', 'cuba-investment-core' ),
    'advisory'        => __( 'Advisory & Board Participation', 'cuba-investment-core' ),
    'commercial'      => __( 'Commercial Partnership & Distribution', 'cuba-investment-core' ),
];

// Areas of Expertise
$all_expertise = [
    'Finance & Banking'          => __( 'Finance, Capital & Banking', 'cuba-investment-core' ),
    'Technology & IT'            => __( 'Technology, Software & Digital Transformation', 'cuba-investment-core' ),
    'Operations & Supply Chain'  => __( 'Operations, Logistics & Supply Chain', 'cuba-investment-core' ),
    'Marketing & Global Sales'   => __( 'Marketing, Brand & Global Sales', 'cuba-investment-core' ),
    'International Trade & Law'  => __( 'International Trade, Customs & Legal Compliance', 'cuba-investment-core' ),
    'Hospitality & Tourism'      => __( 'Hospitality, Travel & Guest Experience', 'cuba-investment-core' ),
    'Agribusiness & Farming'     => __( 'Agribusiness, Crop Science & Food Processing', 'cuba-investment-core' ),
];

// Collaboration Types
$all_collab_types = [
    'strategic_partner' => __( 'Strategic Partner (Hands-on Growth & Network)', 'cuba-investment-core' ),
    'active_advisory'   => __( 'Active Advisor (Regular Guidance & Mentorship)', 'cuba-investment-core' ),
    'silent_passive'    => __( 'Passive / Silent Investor (Capital Only)', 'cuba-investment-core' ),
    'board_member'      => __( 'Board Member / Oversight', 'cuba-investment-core' ),
];

$page_title = __( 'My Investor Profile', 'cuba-investment-core' );

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-5xl w-full mx-auto space-y-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( home_url( '/investor/dashboard/' ) ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Investor Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Investor Profile Management', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Manage your personal details, capital allocation preferences, focus sectors, and privacy preferences.', 'cuba-investment-core' ); ?>
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
            'profile_url' => '#section-personal',
            'title'       => __( 'Investor Profile Progress', 'cuba-investment-core' ),
            'description' => __( 'A complete profile provides Cuba-focused founders with confidence in your experience and preferred collaboration terms.', 'cuba-investment-core' ),
        ];
        include CIN_PLUGIN_DIR . 'templates/dashboard/components/completion-card.php';
        ?>

        <!-- PROFILE EDIT FORM -->
        <form method="post" action="" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="cin_action" value="cin_update_investor_profile">
            <?php wp_nonce_field( 'cin_update_investor_profile', '_cin_nonce' ); ?>

            <!-- SECTION A: Personal Information -->
            <div id="section-personal" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section A — Personal Information', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Basic personal and contact details for your verified investor profile.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">
                        <?php esc_html_e( 'Required', 'cuba-investment-core' ); ?>
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
                        <p class="text-[11px] text-slate-400 mt-1">
                            <?php esc_html_e( 'Email address changes require identity re-verification. Contact platform support to request an email update.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>

                    <!-- Country of Residence -->
                    <div>
                        <label for="country_of_residence" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Country of Residence', 'cuba-investment-core' ); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="country_of_residence" name="country_of_residence" value="<?php echo esc_attr( $profile['country_of_residence'] ); ?>" required placeholder="e.g. Spain, United States, Canada, Mexico" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- City / Region -->
                    <div class="sm:col-span-2">
                        <label for="city_region" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'City / Region', 'cuba-investment-core' ); ?>
                        </label>
                        <input type="text" id="city_region" name="city_region" value="<?php echo esc_attr( $profile['city_region'] ); ?>" placeholder="e.g. Madrid, Miami, Toronto, Mexico City" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                    </div>

                    <!-- Profile Photo Upload -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Profile Photo (Optional)', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="shrink-0">
                                <?php if ( ! empty( $profile['avatar_url'] ) ) : ?>
                                    <img id="avatar-preview-img" src="<?php echo esc_url( $profile['avatar_url'] ); ?>" alt="Avatar" class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shadow-xs">
                                <?php else : ?>
                                    <div id="avatar-preview-fallback" class="w-16 h-16 rounded-2xl bg-primary-100 text-primary font-bold text-lg flex items-center justify-center border border-primary-200 shadow-xs">
                                        <?php echo esc_html( strtoupper( substr( $profile['first_name'] ?: 'U', 0, 1 ) ) ); ?>
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
                        <label for="bio" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Short Professional Bio', 'cuba-investment-core' ); ?>
                        </label>
                        <textarea id="bio" name="bio" rows="3" placeholder="<?php esc_attr_e( 'Briefly summarize your background, investment thesis, executive experience, or commercial interest in Cuba...', 'cuba-investment-core' ); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors"><?php echo esc_textarea( $profile['bio'] ); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION B: Investment Preferences -->
            <div id="section-preferences" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section B — Investment Preferences', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Specify the types of ventures and commercial terms you are seeking.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700">
                        <?php esc_html_e( 'Matching Engine', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <!-- Preferred Sectors -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Preferred Business Sectors (Select all that apply)', 'cuba-investment-core' ); ?>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <?php foreach ( $all_sectors as $key => $label ) : 
                            $checked = in_array( $key, (array) $profile['preferred_sectors'], true );
                        ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border <?php echo $checked ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors">
                                <input type="checkbox" name="preferred_sectors[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                <span class="text-xs sm:text-sm font-semibold text-slate-800"><?php echo esc_html( $label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Capital Range -->
                <div class="pt-2">
                    <label for="capital_range" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Investment Interest / Capital Range', 'cuba-investment-core' ); ?>
                    </label>
                    <select id="capital_range" name="capital_range" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                        <option value=""><?php esc_html_e( '— Select Capital Range —', 'cuba-investment-core' ); ?></option>
                        <?php foreach ( $all_ranges as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['capital_range'], $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Preferred Opportunity Locations -->
                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Preferred Opportunity Locations in Cuba', 'cuba-investment-core' ); ?>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                        <?php foreach ( $all_provinces as $key => $label ) : 
                            $checked = in_array( $key, (array) $profile['preferred_locations'], true );
                        ?>
                            <label class="flex items-center gap-2.5 p-2 rounded-lg border <?php echo $checked ? 'border-primary bg-primary-50/30' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors text-xs font-medium text-slate-800">
                                <input type="checkbox" name="preferred_locations[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> class="w-3.5 h-3.5 text-primary rounded border-slate-300 focus:ring-primary">
                                <span class="truncate"><?php echo esc_html( $label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Investment or Partnership Interests -->
                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Investment or Partnership Interests', 'cuba-investment-core' ); ?>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <?php foreach ( $all_interests as $key => $label ) : 
                            $checked = in_array( $key, (array) $profile['investment_interests'], true );
                        ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border <?php echo $checked ? 'border-primary bg-primary-50/40' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors">
                                <input type="checkbox" name="investment_interests[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary">
                                <span class="text-xs sm:text-sm font-semibold text-slate-800"><?php echo esc_html( $label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Areas of Expertise -->
                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Areas of Professional Expertise', 'cuba-investment-core' ); ?>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                        <?php foreach ( $all_expertise as $key => $label ) : 
                            $checked = in_array( $key, (array) $profile['areas_of_expertise'], true );
                        ?>
                            <label class="flex items-center gap-2.5 p-2.5 rounded-lg border <?php echo $checked ? 'border-primary bg-primary-50/30' : 'border-slate-200 hover:bg-slate-50'; ?> cursor-pointer transition-colors text-xs font-medium text-slate-800">
                                <input type="checkbox" name="areas_of_expertise[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); ?> class="w-3.5 h-3.5 text-primary rounded border-slate-300 focus:ring-primary">
                                <span><?php echo esc_html( $label ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Preferred Collaboration Type -->
                <div class="pt-2">
                    <label for="collaboration_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        <?php esc_html_e( 'Preferred Collaboration Role', 'cuba-investment-core' ); ?>
                    </label>
                    <select id="collaboration_type" name="collaboration_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                        <?php foreach ( $all_collab_types as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $profile['collaboration_type'], $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Compliance Notice -->
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 leading-relaxed flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        <?php esc_html_e( 'Cuba Investment Network provides informational matching only. Self-declared investor criteria does not constitute accredited investor verification or financial compliance clearance.', 'cuba-investment-core' ); ?>
                    </span>
                </div>
            </div>

            <!-- SECTION C: Profile Visibility & Privacy -->
            <div id="section-visibility" class="card bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-heading font-bold text-slate-900">
                            <?php esc_html_e( 'Section C — Profile Visibility & Privacy', 'cuba-investment-core' ); ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?php esc_html_e( 'Control how your profile appears to other members on the platform.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">
                        <?php esc_html_e( 'Privacy Defaults: Strict', 'cuba-investment-core' ); ?>
                    </span>
                </div>

                <div class="space-y-4">
                    <!-- Visibility Option -->
                    <div>
                        <label for="profile_visibility" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Profile Discovery Setting', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="profile_visibility" name="profile_visibility" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm text-slate-900 transition-colors">
                            <option value="registered_only" <?php selected( $profile['profile_visibility'], 'registered_only' ); ?>>
                                <?php esc_html_e( 'Registered Members Only (Verified founders can discover your preferences)', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="connections_only" <?php selected( $profile['profile_visibility'], 'connections_only' ); ?>>
                                <?php esc_html_e( 'Connections Only (Only founders with accepted inquiries see details)', 'cuba-investment-core' ); ?>
                            </option>
                            <option value="anonymous" <?php selected( $profile['profile_visibility'], 'anonymous' ); ?>>
                                <?php esc_html_e( 'Anonymous Dealflow Mode (Browse deals privately, reveal name only on inquiry)', 'cuba-investment-core' ); ?>
                            </option>
                        </select>
                    </div>

                    <!-- Direct Contact Sharing Toggles -->
                    <div class="pt-2 space-y-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="show_email_to_connections" value="1" <?php checked( $profile['show_email_to_connections'] ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary mt-0.5">
                            <div>
                                <span class="text-xs sm:text-sm font-semibold text-slate-800 block">
                                    <?php esc_html_e( 'Share verified email with connected business owners', 'cuba-investment-core' ); ?>
                                </span>
                                <span class="text-xs text-slate-500">
                                    <?php esc_html_e( 'Allow founders to see your email only after an inquiry has been mutually accepted.', 'cuba-investment-core' ); ?>
                                </span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="show_phone_to_connections" value="1" <?php checked( $profile['show_phone_to_connections'] ); ?> class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary mt-0.5">
                            <div>
                                <span class="text-xs sm:text-sm font-semibold text-slate-800 block">
                                    <?php esc_html_e( 'Share phone number with mutually connected founders', 'cuba-investment-core' ); ?>
                                </span>
                                <span class="text-xs text-slate-500">
                                    <?php esc_html_e( 'Direct telephone/WhatsApp contact remains hidden by default until approved.', 'cuba-investment-core' ); ?>
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SECTION D: Account Information Summary -->
            <div id="section-account" class="card bg-slate-50/70 p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                <h2 class="text-base font-heading font-bold text-slate-900 pb-3 border-b border-slate-200 flex items-center justify-between">
                    <span><?php esc_html_e( 'Section D — Account Information', 'cuba-investment-core' ); ?></span>
                    <span class="text-xs font-normal text-slate-500">
                        <?php esc_html_e( 'Registered: ', 'cuba-investment-core' ); ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $profile['registered_at'] ) ) ); ?>
                    </span>
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 uppercase tracking-wider block font-semibold"><?php esc_html_e( 'Account Type', 'cuba-investment-core' ); ?></span>
                        <span class="text-sm font-bold text-primary mt-1 block"><?php esc_html_e( 'Investor', 'cuba-investment-core' ); ?></span>
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
                            ● <?php echo esc_html( ucfirst( $profile['account_status'] ) ); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="flex items-center justify-end gap-4 pt-2">
                <a href="<?php echo esc_url( home_url( '/investor/dashboard/' ) ); ?>" class="btn btn-secondary btn-md font-semibold text-slate-600 hover:text-slate-900">
                    <?php esc_html_e( 'Cancel', 'cuba-investment-core' ); ?>
                </a>
                <button type="submit" class="btn btn-primary btn-md px-8 py-3 font-bold shadow-md hover:shadow-lg transition-all cursor-pointer">
                    <?php esc_html_e( 'Save Profile Changes', 'cuba-investment-core' ); ?>
                </button>
            </div>
        </form>

    </main>
</div>

<?php
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php';
