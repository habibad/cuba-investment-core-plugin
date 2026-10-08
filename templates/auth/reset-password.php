<?php
/**
 * Template: Reset Password Form
 * Route: /reset-password/
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

$flash_error = FormHandler::get_flash_error();
$key         = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$login       = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';

$key_valid = false;
$user_obj  = null;

if ( ! empty( $key ) && ! empty( $login ) ) {
    $user_check = check_password_reset_key( $key, $login );
    if ( ! is_wp_error( $user_check ) && $user_check instanceof \WP_User ) {
        $key_valid = true;
        $user_obj  = $user_check;
    }
}
?>

<div class="site-auth-page min-h-[85vh] py-10 sm:py-16 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm">
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden z-10 transition-all duration-300">

        <!-- Modal Header with Brand Logo & Portal Badge -->
        <div class="px-6 pt-6 pb-4 border-b border-slate-100 bg-white">
            <div class="flex items-center justify-between gap-3">
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
        </div>

        <div class="p-6 space-y-4">

            <div class="text-center mb-2">
                <h1 class="text-lg font-heading font-extrabold text-primary">
                    <?php esc_html_e( 'Choose New Password', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    <?php esc_html_e( 'Enter and confirm your new secure password below.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <!-- Error Notification -->
            <?php if ( ! empty( $flash_error ) ) : ?>
                <div class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs flex items-start gap-2.5" role="alert" aria-live="assertive">
                    <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                    <div class="leading-relaxed"><?php echo esc_html( $flash_error ); ?></div>
                </div>
            <?php endif; ?>

            <?php if ( ! $key_valid ) : ?>
                <!-- Invalid or Expired Key State -->
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs sm:text-sm text-amber-900 leading-relaxed mb-4" role="alert">
                    <span class="font-bold block mb-1 text-amber-800">⚠️ <?php esc_html_e( 'Invalid or Expired Link', 'cuba-investment-core' ); ?></span>
                    <?php esc_html_e( 'This password reset link is invalid or has expired. For security, password reset links can only be used once.', 'cuba-investment-core' ); ?>
                </div>

                <div class="space-y-2 pt-1">
                    <a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md block text-center">
                        <?php esc_html_e( 'Request a New Reset Link', 'cuba-investment-core' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-secondary w-full btn-lg font-semibold block text-center">
                        <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                    </a>
                </div>

            <?php else : ?>
                <!-- Valid Key Form -->
                <form method="POST" action="<?php echo esc_url( home_url( '/reset-password/' ) ); ?>" class="space-y-4" novalidate>
                    <?php NonceManager::field( 'cin_reset_password', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_reset_password" />
                    <input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>" />
                    <input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>" />

                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-700 mb-1">
                            <?php esc_html_e( 'New Password (min. 8 chars) *', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                minlength="8" 
                                autocomplete="new-password" 
                                placeholder="••••••••" 
                                class="form-input text-xs sm:text-sm pr-9"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer" 
                                onclick="const input = document.getElementById('password'); input.type = input.type === 'password' ? 'text' : 'password';"
                                aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-xs font-medium text-slate-700 mb-1">
                            <?php esc_html_e( 'Confirm New Password *', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password_confirm" 
                                name="password_confirm" 
                                required 
                                minlength="8" 
                                autocomplete="new-password" 
                                placeholder="••••••••" 
                                class="form-input text-xs sm:text-sm pr-9"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer" 
                                onclick="const input = document.getElementById('password_confirm'); input.type = input.type === 'password' ? 'text' : 'password';"
                                aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg font-bold shadow-md cursor-pointer flex items-center justify-center gap-2 mt-4"
                    >
                        <span><?php esc_html_e( 'Set New Password &amp; Log In', 'cuba-investment-core' ); ?></span>
                    </button>
                </form>
            <?php endif; ?>

        </div>

    </div>
</div>

<?php
get_footer();
