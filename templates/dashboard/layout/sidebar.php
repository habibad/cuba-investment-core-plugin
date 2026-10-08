<?php
/**
 * Dashboard Layout - Sidebar Navigation Component
 *
 * Implements fixed desktop sidebar with collapse toggle, role-specific navigation,
 * upcoming phase indicator badges, mobile off-canvas drawer, and bottom profile widget.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_investor = in_array( Constants::ROLE_INVESTOR, $roles, true );
$is_business = in_array( Constants::ROLE_BUSINESS_OWNER, $roles, true );

$first_name = get_user_meta( $user->ID, 'first_name', true ) ?: $user->first_name;
$last_name  = get_user_meta( $user->ID, 'last_name', true ) ?: $user->last_name;
$full_name  = trim( "{$first_name} {$last_name}" ) ?: $user->display_name;
$avatar_url = get_user_meta( $user->ID, '_cin_avatar_url', true );
if ( empty( $avatar_url ) && $is_business ) {
    $avatar_url = get_user_meta( $user->ID, '_cin_company_logo_url', true );
}

// Compute initials for fallback
$initials = strtoupper( substr( $first_name ?: $user->user_login, 0, 1 ) . substr( $last_name ?: '', 0, 1 ) );
if ( empty( $initials ) ) {
    $initials = 'CIN';
}

// Determine active route
$current_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
$site_path    = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
if ( ! empty( $site_path ) && 0 === strpos( $current_path, $site_path ) ) {
    $current_path = trim( substr( $current_path, strlen( $site_path ) ), '/' );
}

$role_badge = $is_investor ? __( 'Investor Portal', 'cuba-investment-core' ) : ( $is_business ? __( 'Business Portal', 'cuba-investment-core' ) : __( 'Admin Portal', 'cuba-investment-core' ) );

if ( ! function_exists( 'cin_dashboard_render_nav_items' ) ) {
    function cin_dashboard_render_nav_items( $is_investor, $is_business, $current_path ) {
        // Normalization
        $current_path = trim( $current_path, '/' );

        if ( $is_investor ) : ?>
            <!-- GROUP: Main Navigation -->
            <div>
                <p class="sidebar-text px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    <?php esc_html_e( 'Investor Portal', 'cuba-investment-core' ); ?>
                </p>
                <div class="space-y-1">
                    <?php
                    $is_active = in_array( $current_path, [ 'investor/dashboard', 'dashboard/investor', 'dashboard' ], true );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/investor/dashboard/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Overview', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>

                    <?php
                    $is_active = in_array( $current_path, [ 'investor/profile', 'dashboard/investor/profile' ], true );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/investor/profile/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'My Profile', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>

                    <a href="<?php echo esc_url( home_url( '/invest/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Explore Opportunities', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- GROUP: Dealflow & Networking -->
            <div>
                <p class="sidebar-text px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    <?php esc_html_e( 'Network & Dealflow', 'cuba-investment-core' ); ?>
                </p>
                <div class="space-y-1">
                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Saved Opportunities', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'My Enquiries', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Connections', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Messages', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>
                </div>
            </div>

        <?php elseif ( $is_business ) : ?>
            <!-- GROUP: Business Management -->
            <div>
                <p class="sidebar-text px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    <?php esc_html_e( 'Business Portal', 'cuba-investment-core' ); ?>
                </p>
                <div class="space-y-1">
                    <?php
                    $is_active = in_array( $current_path, [ 'business-owner/dashboard', 'dashboard/business', 'dashboard' ], true );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/business-owner/dashboard/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Overview', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>

                    <?php
                    $is_active = in_array( $current_path, [ 'business-owner/profile', 'dashboard/business/profile' ], true );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/business-owner/profile/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Personal Profile', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>

                    <?php
                    $is_active = in_array( $current_path, [ 'business-owner/business-profile', 'dashboard/business/business-profile' ], true );
                    ?>
                    <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Business Profile', 'cuba-investment-core' ); ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- GROUP: Capital & Listings -->
            <div>
                <p class="sidebar-text px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                    <?php esc_html_e( 'Opportunities & Network', 'cuba-investment-core' ); ?>
                </p>
                <div class="space-y-1">
                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'My Opportunities', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 05', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Create Opportunity', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 05', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Investor Enquiries', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Connections', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>

                    <div class="sidebar-nav-item flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-slate-400 cursor-not-allowed">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span class="sidebar-text"><?php esc_html_e( 'Messages', 'cuba-investment-core' ); ?></span>
                        </div>
                        <span class="sidebar-badge text-[9px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-slate-100 text-slate-400"><?php esc_html_e( 'Phase 04', 'cuba-investment-core' ); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- GROUP: Account & Preferences -->
        <div>
            <p class="sidebar-text px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                <?php esc_html_e( 'Preferences', 'cuba-investment-core' ); ?>
            </p>
            <div class="space-y-1">
                <?php
                $is_active = ( 'account' === $current_path );
                ?>
                <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all <?php echo $is_active ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="sidebar-text"><?php esc_html_e( 'Account Settings', 'cuba-investment-core' ); ?></span>
                    </div>
                </a>

                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sidebar-nav-item flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span class="sidebar-text"><?php esc_html_e( 'Public Website', 'cuba-investment-core' ); ?></span>
                    </div>
                </a>
            </div>
        </div>
        <?php
    }
}
?>

<!-- ========================================== -->
<!-- 1. MOBILE OFF-CANVAS DRAWER BACKDROP & MENU -->
<!-- ========================================== -->
<div id="mobile-sidebar-drawer" class="fixed inset-0 z-50 lg:hidden hidden" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Mobile Navigation Drawer', 'cuba-investment-core' ); ?>">
    <!-- Backdrop overlay -->
    <div id="mobile-drawer-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-300"></div>

    <!-- Sliding Sidebar Container -->
    <div class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-white shadow-2xl flex flex-col justify-between z-10 transform transition-transform duration-300">
        <!-- Drawer Header -->
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5">
                <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="Cuba Investment Network" class="h-8 w-auto object-contain">
            </a>
            <button type="button" id="mobile-drawer-close" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary" aria-label="<?php esc_attr_e( 'Close navigation', 'cuba-investment-core' ); ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6">
            <?php cin_dashboard_render_nav_items( $is_investor, $is_business, $current_path ); ?>
        </div>

        <!-- Drawer Bottom User Strip -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <?php if ( ! empty( $avatar_url ) ) : ?>
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $full_name ); ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 shrink-0">
                <?php else : ?>
                    <div class="w-9 h-9 rounded-full bg-primary-100 text-primary font-bold text-xs flex items-center justify-center shrink-0 border border-primary-200">
                        <?php echo esc_html( $initials ); ?>
                    </div>
                <?php endif; ?>
                <div class="truncate">
                    <p class="text-xs font-bold text-slate-900 truncate leading-snug"><?php echo esc_html( $full_name ); ?></p>
                    <p class="text-[11px] text-slate-500 truncate leading-snug"><?php echo esc_html( $user->user_email ); ?></p>
                </div>
            </div>
            <a href="<?php echo esc_url( home_url( '/logout/' ) ); ?>" class="p-2 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors" title="<?php esc_attr_e( 'Log Out', 'cuba-investment-core' ); ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. DESKTOP FIXED SIDEBAR                   -->
<!-- ========================================== -->
<aside class="sidebar-desktop hidden lg:flex flex-col justify-between fixed top-0 left-0 bottom-0 w-64 xl:w-72 bg-white border-r border-slate-200/80 z-30 transition-all duration-300">
    <!-- Brand / Logo Header -->
    <div class="h-20 px-5 flex items-center justify-between border-b border-slate-100">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5 group">
            <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="Cuba Investment Network" class="h-9 w-auto object-contain transition-transform group-hover:scale-[1.02]">
        </a>
        <span class="sidebar-badge inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase bg-emerald-50 text-emerald-700 border border-emerald-100">
            <?php echo esc_html( $role_badge ); ?>
        </span>
    </div>

    <!-- Navigation Menu (Scrollable) -->
    <div class="flex-1 overflow-y-auto px-3.5 py-5 space-y-6">
        <?php cin_dashboard_render_nav_items( $is_investor, $is_business, $current_path ); ?>
    </div>

    <!-- Bottom User Strip -->
    <div class="p-4 border-t border-slate-100 bg-slate-50/50">
        <div class="flex items-center justify-between gap-3">
            <a href="<?php echo esc_url( $is_investor ? home_url( '/investor/profile/' ) : home_url( '/business-owner/profile/' ) ); ?>" class="flex items-center gap-3 min-w-0 group flex-1">
                <div class="relative shrink-0">
                    <?php if ( ! empty( $avatar_url ) ) : ?>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $full_name ); ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200 group-hover:border-accent transition-colors">
                    <?php else : ?>
                        <div class="w-10 h-10 rounded-full bg-primary-100 text-primary font-bold text-xs flex items-center justify-center border border-primary-200 group-hover:border-accent transition-colors">
                            <?php echo esc_html( $initials ); ?>
                        </div>
                    <?php endif; ?>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full" title="<?php esc_attr_e( 'Account Active', 'cuba-investment-core' ); ?>"></span>
                </div>
                <div class="sidebar-text truncate">
                    <p class="text-xs font-bold text-slate-900 group-hover:text-primary transition-colors truncate leading-tight"><?php echo esc_html( $full_name ); ?></p>
                    <p class="text-[11px] text-slate-500 truncate leading-tight mt-0.5"><?php echo esc_html( $user->user_email ); ?></p>
                </div>
            </a>
            <a href="<?php echo esc_url( home_url( '/logout/' ) ); ?>" class="p-2 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors shrink-0" title="<?php esc_attr_e( 'Log Out', 'cuba-investment-core' ); ?>">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>
    </div>
</aside>
