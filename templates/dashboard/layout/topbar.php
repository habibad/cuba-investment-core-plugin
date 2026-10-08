<?php
/**
 * Dashboard Layout - Topbar Component
 *
 * Implements compact top navigation bar with sidebar toggles, breadcrumb hierarchy,
 * launch membership status pill, contextual action CTA, and accessible user account dropdown.
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

$initials = strtoupper( substr( $first_name ?: $user->user_login, 0, 1 ) . substr( $last_name ?: '', 0, 1 ) );
if ( empty( $initials ) ) {
    $initials = 'CIN';
}

$profile_url = $is_investor ? home_url( '/investor/profile/' ) : home_url( '/business-owner/profile/' );
$dashboard_url = $is_investor ? home_url( '/investor/dashboard/' ) : home_url( '/business-owner/dashboard/' );
$role_label  = $is_investor ? __( 'Verified Investor', 'cuba-investment-core' ) : ( $is_business ? __( 'Verified Business Owner', 'cuba-investment-core' ) : __( 'Administrator', 'cuba-investment-core' ) );
?>

<header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200/80 h-20 transition-all duration-300">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">

        <!-- Left: Toggle & Breadcrumbs -->
        <div class="flex items-center gap-3 sm:gap-4">
            <!-- Mobile Menu Toggle Button -->
            <button type="button" id="mobile-drawer-open" class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer" aria-label="<?php esc_attr_e( 'Open navigation menu', 'cuba-investment-core' ); ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Desktop Collapse Toggle Button -->
            <button type="button" id="sidebar-collapse-toggle" class="hidden lg:flex p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary cursor-pointer transition-colors" title="<?php esc_attr_e( 'Toggle sidebar', 'cuba-investment-core' ); ?>" aria-label="<?php esc_attr_e( 'Toggle sidebar collapse', 'cuba-investment-core' ); ?>">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16" />
                </svg>
            </button>

            <!-- Breadcrumbs / Page Context -->
            <div class="flex items-center gap-2 text-xs sm:text-sm">
                <a href="<?php echo esc_url( $dashboard_url ); ?>" class="font-semibold text-slate-500 hover:text-primary transition-colors">
                    <?php esc_html_e( 'Portal', 'cuba-investment-core' ); ?>
                </a>
                <span class="text-slate-300">/</span>
                <span class="font-bold text-slate-900 truncate max-w-[160px] sm:max-w-none">
                    <?php echo esc_html( $page_title ?? __( 'Dashboard', 'cuba-investment-core' ) ); ?>
                </span>
            </div>
        </div>

        <!-- Right: Status Pill, Contextual CTA, User Dropdown -->
        <div class="flex items-center gap-2 sm:gap-4">

            <!-- Launch Membership Status Pill -->
            <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span><?php esc_html_e( 'Launch Access · $0 / Free', 'cuba-investment-core' ); ?></span>
            </div>

            <!-- Contextual CTA if specified -->
            <?php if ( ! empty( $topbar_cta ) && is_array( $topbar_cta ) ) : ?>
                <a href="<?php echo esc_url( $topbar_cta['url'] ); ?>" class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-primary hover:bg-primary-light text-white shadow-xs transition-colors">
                    <?php if ( ! empty( $topbar_cta['icon'] ) ) : ?>
                        <?php echo $topbar_cta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <span><?php echo esc_html( $topbar_cta['label'] ); ?></span>
                </a>
            <?php endif; ?>

            <!-- User Account Dropdown -->
            <div class="relative" id="user-account-dropdown">
                <button type="button" id="user-dropdown-button" aria-expanded="false" aria-haspopup="true" class="flex items-center gap-2.5 p-1 rounded-full hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all cursor-pointer">
                    <?php if ( ! empty( $avatar_url ) ) : ?>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $full_name ); ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-xs">
                    <?php else : ?>
                        <div class="w-9 h-9 rounded-full bg-primary-100 text-primary font-bold text-xs flex items-center justify-center border border-primary-200 shadow-xs">
                            <?php echo esc_html( $initials ); ?>
                        </div>
                    <?php endif; ?>
                    <span class="hidden md:block text-xs font-bold text-slate-800 text-left pr-1 leading-tight max-w-[120px] truncate">
                        <?php echo esc_html( $first_name ); ?>
                    </span>
                    <svg class="hidden md:block w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div id="user-dropdown-menu" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50 transform origin-top-right transition-all">
                    <!-- Dropdown Header -->
                    <div class="px-4 py-3 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-900 truncate"><?php echo esc_html( $full_name ); ?></p>
                        <p class="text-[11px] text-slate-500 truncate mt-0.5"><?php echo esc_html( $user->user_email ); ?></p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 mt-2">
                            ● <?php echo esc_html( $role_label ); ?>
                        </span>
                    </div>

                    <!-- Dropdown Links -->
                    <div class="py-1">
                        <a href="<?php echo esc_url( $profile_url ); ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:text-primary hover:bg-slate-50 transition-colors">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span><?php esc_html_e( 'View Profile', 'cuba-investment-core' ); ?></span>
                        </a>

                        <?php if ( $is_business ) : ?>
                            <a href="<?php echo esc_url( home_url( '/business-owner/business-profile/' ) ); ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:text-primary hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span><?php esc_html_e( 'Business Profile', 'cuba-investment-core' ); ?></span>
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:text-primary hover:bg-slate-50 transition-colors">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span><?php esc_html_e( 'Account Settings', 'cuba-investment-core' ); ?></span>
                        </a>

                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-slate-700 hover:text-primary hover:bg-slate-50 transition-colors">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span><?php esc_html_e( 'Public Website', 'cuba-investment-core' ); ?></span>
                        </a>
                    </div>

                    <!-- Divider & Logout -->
                    <div class="border-t border-slate-100 pt-1">
                        <a href="<?php echo esc_url( home_url( '/logout/' ) ); ?>" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span><?php esc_html_e( 'Log Out', 'cuba-investment-core' ); ?></span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</header>
