<?php
/**
 * Template: Active Connections (Investor & Business Owner Portals)
 * Routes: /investor/connections/ and /business-owner/connections/
 *
 * Displays direct mutual connections established between investors and business owners
 * following accepted introductions, with direct messaging links and compliance notices.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Services\ConnectionService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Access Control
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/login/?redirect_to=' . urlencode( $_SERVER['REQUEST_URI'] ?? '' ) ) );
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_investor = in_array( Constants::ROLE_INVESTOR, $roles, true );
$is_business = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );
$is_admin    = in_array( 'administrator', $roles, true );

$current_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
if ( false !== strpos( $current_path, 'investor' ) ) {
    $portal_role = 'investor';
    $back_url    = home_url( '/investor/dashboard/' );
    $messages_base_url = home_url( '/investor/messages/' );
} else {
    $portal_role = 'business';
    $back_url    = home_url( '/business-owner/dashboard/' );
    $messages_base_url = home_url( '/business-owner/messages/' );
}

$connections = ConnectionService::get_connections_for_user( $user->ID );
$total_conn  = count( $connections );

$page_title = __( 'Active Connections', 'cuba-investment-core' );
$topbar_cta = [
    'label' => ( 'investor' === $portal_role ) ? __( 'Explore Deals', 'cuba-investment-core' ) : __( 'My Opportunities', 'cuba-investment-core' ),
    'url'   => ( 'investor' === $portal_role ) ? home_url( '/invest/' ) : home_url( '/business-owner/opportunities/' ),
    'icon'  => '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>',
];

require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/header.php';
require_once CIN_PLUGIN_DIR . 'templates/dashboard/layout/sidebar.php';
?>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content-area lg:pl-64 xl:pl-72 flex flex-col flex-1 min-h-screen transition-all duration-300">
    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/topbar.php'; ?>

    <main id="dashboard-main-content" class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <a href="<?php echo esc_url( $back_url ); ?>" class="inline-flex items-center text-xs font-bold text-primary hover:text-accent mb-2 transition-colors">
                    &larr; <?php esc_html_e( 'Back to Overview', 'cuba-investment-core' ); ?>
                </a>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-slate-900 tracking-tight">
                    <?php esc_html_e( 'Active Connections', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    <?php esc_html_e( 'Approved introductions between investors and Cuban business founders.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?php echo esc_url( $messages_base_url ); ?>" class="btn btn-primary btn-sm font-bold shadow-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span><?php esc_html_e( 'View All Messages', 'cuba-investment-core' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Compliance & Regulatory Notice -->
        <div class="p-4 sm:p-5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-3.5 shadow-2xs">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="space-y-1">
                <span class="font-bold block"><?php esc_html_e( 'Networking & Due Diligence Standard', 'cuba-investment-core' ); ?></span>
                <p class="text-amber-800 leading-relaxed font-normal">
                    <?php esc_html_e( 'Connection acceptance reflects a mutual agreement to communicate regarding a specific opportunity. A connection does not constitute investment approval, regulatory clearance, business verification, or a funding commitment. Both parties must conduct independent due diligence.', 'cuba-investment-core' ); ?>
                </p>
            </div>
        </div>

        <!-- Stats Bar -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                        <?php echo esc_html( $total_conn ); ?> <?php esc_html_e( 'Active Connections', 'cuba-investment-core' ); ?>
                    </h3>
                    <p class="text-xs text-slate-500">
                        <?php esc_html_e( 'Direct contacts with mutual messaging access.', 'cuba-investment-core' ); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ( empty( $connections ) ) : ?>
            <!-- Empty State -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-8 sm:p-14 text-center shadow-xs">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-400 mx-auto flex items-center justify-center mb-4 border border-slate-100">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-heading font-bold text-slate-900 mb-2">
                    <?php esc_html_e( 'No Active Connections Yet', 'cuba-investment-core' ); ?>
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed">
                    <?php if ( 'investor' === $portal_role ) : ?>
                        <?php esc_html_e( 'Submit an introduction inquiry on any published opportunity. When the business owner accepts your request, your direct connection will be activated here.', 'cuba-investment-core' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'When you accept an introduction request from an interested investor, your direct connection will be activated here for secure messaging.', 'cuba-investment-core' ); ?>
                    <?php endif; ?>
                </p>
                <a 
                    href="<?php echo esc_url( ( 'investor' === $portal_role ) ? home_url( '/invest/' ) : home_url( '/business-owner/enquiries/' ) ); ?>" 
                    class="btn btn-primary btn-md font-bold shadow-xs inline-flex items-center gap-2"
                >
                    <span><?php echo ( 'investor' === $portal_role ) ? esc_html__( 'Explore Opportunities', 'cuba-investment-core' ) : esc_html__( 'View Investor Enquiries', 'cuba-investment-core' ); ?></span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        <?php else : ?>
            <!-- Connections Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ( $connections as $c ) : ?>
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group">
                        
                        <!-- Partner Overview -->
                        <div class="p-5 sm:p-6 space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <?php if ( ! empty( $c['partner_avatar'] ) ) : ?>
                                        <img src="<?php echo esc_url( $c['partner_avatar'] ); ?>" alt="<?php echo esc_attr( $c['partner_name'] ); ?>" class="w-12 h-12 rounded-full object-cover border border-slate-200">
                                    <?php else : ?>
                                        <div class="w-12 h-12 rounded-full bg-primary/10 text-primary font-black flex items-center justify-center text-sm">
                                            <?php echo esc_html( strtoupper( substr( $c['partner_name'], 0, 2 ) ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-base font-heading font-bold text-slate-900 group-hover:text-primary transition-colors">
                                            <?php echo esc_html( $c['partner_name'] ); ?>
                                        </h3>
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 mt-0.5">
                                            <?php echo esc_html( $c['partner_role'] ); ?>
                                        </span>
                                    </div>
                                </div>

                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                                    <?php esc_html_e( 'Active', 'cuba-investment-core' ); ?>
                                </span>
                            </div>

                            <!-- Details -->
                            <div class="space-y-2 pt-2 border-t border-slate-100 text-xs">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-slate-400 font-medium"><?php esc_html_e( 'Organization / Type:', 'cuba-investment-core' ); ?></span>
                                    <span class="font-bold text-slate-800 truncate max-w-[150px]"><?php echo esc_html( $c['partner_business'] ); ?></span>
                                </div>

                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-slate-400 font-medium"><?php esc_html_e( 'Location:', 'cuba-investment-core' ); ?></span>
                                    <span class="font-semibold text-slate-700"><?php echo esc_html( $c['partner_location'] ); ?></span>
                                </div>

                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="text-slate-400 font-medium"><?php esc_html_e( 'Connected Since:', 'cuba-investment-core' ); ?></span>
                                    <span class="font-semibold text-slate-700"><?php echo esc_html( $c['connected_date'] ); ?></span>
                                </div>
                            </div>

                            <!-- Related Opportunity Card -->
                            <?php if ( ! empty( $c['opportunity_title'] ) ) : ?>
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">
                                        <?php esc_html_e( 'Related Listing', 'cuba-investment-core' ); ?>
                                    </span>
                                    <a href="<?php echo esc_url( $c['opportunity_url'] ); ?>" class="font-bold text-primary hover:underline line-clamp-1">
                                        <?php echo esc_html( $c['opportunity_title'] ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Actions -->
                        <div class="px-5 py-4 sm:px-6 bg-slate-50/60 border-t border-slate-100 flex items-center justify-end gap-2.5">
                            <a 
                                href="<?php echo esc_url( $messages_base_url . ( ! empty( $c['conversation_id'] ) ? '?convo=' . $c['conversation_id'] : '' ) ); ?>" 
                                class="btn btn-primary btn-sm font-bold shadow-xs inline-flex items-center gap-1.5 w-full justify-center"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span><?php esc_html_e( 'Open Direct Messages', 'cuba-investment-core' ); ?></span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>

    <?php require CIN_PLUGIN_DIR . 'templates/dashboard/layout/footer.php'; ?>
</div>
