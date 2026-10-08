<?php
/**
 * Template: Reset Password Form
 * Route: /reset-password/
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

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[80vh] flex items-center">
    <div class="container mx-auto px-4 max-w-md">

        <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm">
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-heading font-extrabold text-primary tracking-tight">
                    <?php esc_html_e( 'Choose New Password', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-slate-600 text-xs sm:text-sm mt-1.5 leading-relaxed">
                    <?php esc_html_e( 'Enter and confirm your new secure password below.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <!-- Error Notification -->
            <?php if ( ! empty( $flash_error ) ) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-800 flex items-start gap-3" role="alert" aria-live="assertive">
                    <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                    <div class="leading-relaxed">
                        <?php echo esc_html( $flash_error ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! $key_valid ) : ?>
                <!-- Invalid or Expired Key State -->
                <div class="p-5 bg-amber-50 border border-amber-200 rounded-xl text-xs sm:text-sm text-amber-900 leading-relaxed mb-6" role="alert">
                    <span class="font-bold block mb-1 text-amber-800">⚠️ <?php esc_html_e( 'Invalid or Expired Link', 'cuba-investment-core' ); ?></span>
                    <?php esc_html_e( 'This password reset link is invalid, incomplete, or has expired. For security, password reset links can only be used once.', 'cuba-investment-core' ); ?>
                </div>

                <div class="space-y-3">
                    <a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md block text-center">
                        <?php esc_html_e( 'Request a New Reset Link', 'cuba-investment-core' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-outline w-full btn-lg font-semibold block text-center">
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
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <?php esc_html_e( 'New Password *', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                minlength="8" 
                                autocomplete="new-password" 
                                placeholder="<?php esc_attr_e( 'At least 8 characters with letters and numbers', 'cuba-investment-core' ); ?>" 
                                class="form-input pr-10"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" 
                                onclick="const input = document.getElementById('password'); input.type = input.type === 'password' ? 'text' : 'password';"
                                aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            <?php esc_html_e( 'Must be at least 8 characters and include both letters and numbers.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-xs font-semibold text-slate-700 mb-1.5">
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
                                placeholder="<?php esc_attr_e( 'Re-type your new password', 'cuba-investment-core' ); ?>" 
                                class="form-input pr-10"
                            />
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" 
                                onclick="const input = document.getElementById('password_confirm'); input.type = input.type === 'password' ? 'text' : 'password';"
                                aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all mt-6 cursor-pointer"
                    >
                        <?php esc_html_e( 'Set New Password &amp; Log In', 'cuba-investment-core' ); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php
get_footer();
