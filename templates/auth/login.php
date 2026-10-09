<?php
/**
 * Template: Login System
 * Route: /login/
 *
 * Styled to match the client-approved Opportunity Portal Modal design.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$flash_error     = FormHandler::get_flash_error();
$is_logged_out   = isset( $_GET['loggedout'] );
$is_reset_ok     = isset( $_GET['reset'] ) && 'success' === $_GET['reset'];
$is_verified_ok  = isset( $_GET['verified'] ) && 'success' === $_GET['verified'];
$is_already_ver  = isset( $_GET['verified'] ) && 'already' === $_GET['verified'];
$is_suspended    = isset( $_GET['error'] ) && 'suspended' === $_GET['error'];
$resend_email    = isset( $_GET['resend_email'] ) ? sanitize_email( wp_unslash( $_GET['resend_email'] ) ) : '';
$redirect_to     = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
?>

<div class="site-auth-page min-h-[85vh] py-10 sm:py-16 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-10 transition-all duration-300">

        <!-- Modal Header with Tab Switcher -->
        <div class="px-6 pt-6 pb-2 border-b border-slate-100 bg-white">
            <div class="flex items-center justify-between gap-3 mb-4">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block">
                    <img 
                        src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" 
                        alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" 
                        class="h-9 w-auto object-contain"
                    />
                </a>
                <span class="text-[10px] uppercase font-bold tracking-wider text-primary bg-primary-50 px-2.5 py-1 rounded-md">
                    <?php esc_html_e( 'Opportunity Portal', 'cuba-investment-core' ); ?>
                </span>
            </div>

            <!-- Tab Buttons -->
            <div id="modal-tab-nav" class="flex border-b border-slate-200">
                <a 
                    href="<?php echo esc_url( home_url( '/join-network/' ) ); ?>" 
                    class="flex-1 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors text-center"
                >
                    <?php esc_html_e( 'Create Account', 'cuba-investment-core' ); ?>
                </a>
                <a 
                    href="<?php echo esc_url( home_url( '/login/' ) ); ?>" 
                    class="flex-1 py-3 text-sm font-semibold border-b-2 border-primary text-primary transition-colors text-center"
                >
                    <?php esc_html_e( 'Log In', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

        <div class="p-6">

            <!-- Email Verified Success Notice -->
            <?php if ( $is_verified_ok ) : ?>
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs sm:text-sm text-emerald-900 flex items-start gap-3" role="status">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">
                        ✓
                    </div>
                    <div>
                        <div class="font-bold text-emerald-950 mb-0.5"><?php esc_html_e( 'Email Verified Successfully', 'cuba-investment-core' ); ?></div>
                        <div class="text-emerald-800 text-xs sm:text-sm leading-relaxed"><?php esc_html_e( 'Your account is now active. Please sign in below to access your dashboard.', 'cuba-investment-core' ); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Already Verified Notice -->
            <?php if ( $is_already_ver ) : ?>
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs sm:text-sm text-blue-900 flex items-start gap-3" role="status">
                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">
                        ℹ
                    </div>
                    <div>
                        <div class="font-bold text-blue-950 mb-0.5"><?php esc_html_e( 'Account Already Verified', 'cuba-investment-core' ); ?></div>
                        <div class="text-blue-800 text-xs sm:text-sm leading-relaxed"><?php esc_html_e( 'Your email address is already verified. Please sign in below.', 'cuba-investment-core' ); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Suspended Account Notice -->
            <?php if ( $is_suspended ) : ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-900 flex items-start gap-3" role="alert">
                    <div class="w-6 h-6 rounded-full bg-red-100 text-red-700 flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">
                        ⚠️
                    </div>
                    <div>
                        <div class="font-bold text-red-950 mb-0.5"><?php esc_html_e( 'Account Suspended', 'cuba-investment-core' ); ?></div>
                        <div class="text-red-800 text-xs sm:text-sm leading-relaxed"><?php esc_html_e( 'Your account access has been suspended. Please contact platform support for assistance.', 'cuba-investment-core' ); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Logged out notice -->
            <?php if ( $is_logged_out ) : ?>
                <div class="mb-4 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-700 flex items-center gap-2.5" role="status">
                    <span class="text-accent font-bold">✓</span>
                    <span><?php esc_html_e( 'You have been successfully logged out.', 'cuba-investment-core' ); ?></span>
                </div>
            <?php endif; ?>

            <!-- Password Reset Success Notice -->
            <?php if ( $is_reset_ok ) : ?>
                <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs sm:text-sm text-emerald-800 flex items-center gap-2.5" role="status">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span><?php esc_html_e( 'Your password has been reset. Please sign in with your new credentials.', 'cuba-investment-core' ); ?></span>
                </div>
            <?php endif; ?>

            <!-- Error Notification -->
            <?php if ( ! empty( $flash_error ) ) : ?>
                <div class="mb-4 p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 flex flex-col gap-2" role="alert" aria-live="assertive">
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                        <div class="leading-relaxed"><?php echo esc_html( $flash_error ); ?></div>
                    </div>
                    <?php if ( $resend_email ) : ?>
                        <div class="pl-6 pt-1 border-t border-red-200/60">
                            <form method="POST" action="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>">
                                <?php NonceManager::field( 'cin_resend_verification', '_cin_nonce' ); ?>
                                <input type="hidden" name="cin_action" value="cin_resend_verification" />
                                <input type="hidden" name="email" value="<?php echo esc_attr( $resend_email ); ?>" />
                                <button type="submit" class="text-xs font-bold text-red-700 hover:underline cursor-pointer">
                                    <?php esc_html_e( 'Resend verification link to my email &rarr;', 'cuba-investment-core' ); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form (Exact classes matching modal-auth.php) -->
            <form method="POST" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="space-y-4" novalidate>
                <?php NonceManager::field( 'cin_login', '_cin_nonce' ); ?>
                <input type="hidden" name="cin_action" value="cin_login" />
                <?php if ( ! empty( $redirect_to ) ) : ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
                <?php endif; ?>

                <div>
                    <label for="login-username" class="block text-xs font-medium text-slate-700 mb-1">
                        <?php esc_html_e( 'Email Address or Username *', 'cuba-investment-core' ); ?>
                    </label>
                    <input 
                        type="text" 
                        id="login-username" 
                        name="username" 
                        required 
                        autocomplete="username" 
                        placeholder="your.name@example.com" 
                        class="form-input text-xs sm:text-sm"
                    />
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="login-password" class="text-xs font-medium text-slate-700">
                            <?php esc_html_e( 'Password *', 'cuba-investment-core' ); ?>
                        </label>
                        <a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="text-xs text-primary font-semibold hover:underline cursor-pointer">
                            <?php esc_html_e( 'Forgot password?', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="login-password" 
                            name="password" 
                            required 
                            autocomplete="current-password" 
                            placeholder="••••••••" 
                            class="form-input text-xs sm:text-sm pr-9"
                        />
                        <button 
                            type="button" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer" 
                            onclick="const p=document.getElementById('login-password'); p.type = p.type==='password'?'text':'password';"
                            aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-600">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="login-remember" name="remember" value="1" class="rounded text-primary focus:ring-primary">
                        <span><?php esc_html_e( 'Keep me signed in', 'cuba-investment-core' ); ?></span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="btn btn-primary w-full btn-lg mt-2 font-bold shadow-md cursor-pointer flex items-center justify-center gap-2"
                >
                    <span><?php esc_html_e( 'Sign In to Portal', 'cuba-investment-core' ); ?></span>
                </button>

                <!-- Switch to Register -->
                <p class="text-xs text-center text-slate-500 pt-1">
                    <?php esc_html_e( 'New to the network?', 'cuba-investment-core' ); ?> 
                    <a href="<?php echo esc_url( home_url( '/join-network/' ) ); ?>" class="text-accent font-bold hover:underline cursor-pointer ml-1">
                        <?php esc_html_e( 'Join Network', 'cuba-investment-core' ); ?> &rarr;
                    </a>
                </p>
            </form>
        </div>

    </div>
</div>

<?php
get_footer();
