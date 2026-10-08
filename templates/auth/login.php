<?php
/**
 * Template: Login System
 * Route: /login/
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
$resend_email    = isset( $_GET['resend_email'] ) ? sanitize_email( wp_unslash( $_GET['resend_email'] ) ) : '';
$redirect_to     = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
?>

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[80vh] flex items-center">
    <div class="container mx-auto px-4 max-w-md">

        <!-- Login Card -->
        <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm">
            <!-- Header -->
            <div class="text-center mb-8">
                <img 
                    src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" 
                    alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" 
                    class="h-10 w-auto object-contain mx-auto mb-4"
                />
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary tracking-tight">
                    <?php esc_html_e( 'Sign In to Portal', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-slate-600 text-xs sm:text-sm mt-1.5 leading-relaxed">
                    <?php esc_html_e( 'Access your dashboard, manage your account, and connect with platform opportunities.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <!-- Logged out notice -->
            <?php if ( $is_logged_out ) : ?>
                <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-700 flex items-center gap-2.5" role="status">
                    <span class="text-accent font-bold">✓</span>
                    <span><?php esc_html_e( 'You have been successfully logged out.', 'cuba-investment-core' ); ?></span>
                </div>
            <?php endif; ?>

            <!-- Password Reset Success Notice -->
            <?php if ( $is_reset_ok ) : ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs sm:text-sm text-emerald-800 flex items-center gap-2.5" role="status">
                    <span class="text-emerald-600 font-bold">✓</span>
                    <span><?php esc_html_e( 'Your password has been reset. Please sign in with your new credentials.', 'cuba-investment-core' ); ?></span>
                </div>
            <?php endif; ?>

            <!-- Error Notification -->
            <?php if ( ! empty( $flash_error ) ) : ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-800 flex items-start gap-3" role="alert" aria-live="assertive">
                    <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                    <div class="leading-relaxed">
                        <?php echo esc_html( $flash_error ); ?>
                        <?php if ( $resend_email ) : ?>
                            <div class="mt-2 pt-2 border-t border-red-200/80">
                                <form method="POST" action="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>">
                                    <?php NonceManager::field( 'cin_resend_verification', '_cin_nonce' ); ?>
                                    <input type="hidden" name="cin_action" value="cin_resend_verification" />
                                    <input type="hidden" name="email" value="<?php echo esc_attr( $resend_email ); ?>" />
                                    <button type="submit" class="text-xs font-bold text-red-700 hover:underline cursor-pointer">
                                        <?php esc_html_e( 'Click here to resend verification link &rarr;', 'cuba-investment-core' ); ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="space-y-4" novalidate>
                <?php NonceManager::field( 'cin_login', '_cin_nonce' ); ?>
                <input type="hidden" name="cin_action" value="cin_login" />
                <?php if ( ! empty( $redirect_to ) ) : ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
                <?php endif; ?>

                <!-- Email or Username -->
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        <?php esc_html_e( 'Email Address or Username *', 'cuba-investment-core' ); ?>
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required 
                        autocomplete="username" 
                        placeholder="your.email@example.com" 
                        class="form-input"
                    />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-xs font-semibold text-slate-700">
                            <?php esc_html_e( 'Password *', 'cuba-investment-core' ); ?>
                        </label>
                        <a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="text-xs text-primary font-semibold hover:underline">
                            <?php esc_html_e( 'Forgot password?', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="current-password" 
                            placeholder="••••••••" 
                            class="form-input pr-10"
                        />
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('password', this)" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                            aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                        >
                            <span class="text-xs">👁</span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs text-slate-600 pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="rounded text-primary focus:ring-primary border-slate-300">
                        <span><?php esc_html_e( 'Keep me signed in', 'cuba-investment-core' ); ?></span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all mt-4 cursor-pointer"
                >
                    <?php esc_html_e( 'Log In', 'cuba-investment-core' ); ?>
                </button>

                <!-- Join Network link -->
                <div class="text-center text-xs text-slate-500 pt-4 border-t border-slate-100 mt-6">
                    <?php esc_html_e( 'New to Cuba Investment Network?', 'cuba-investment-core' ); ?>
                    <a href="<?php echo esc_url( home_url( '/join-network/' ) ); ?>" class="text-accent font-bold hover:underline ml-1">
                        <?php esc_html_e( 'Join Network', 'cuba-investment-core' ); ?> &rarr;
                    </a>
                </div>
            </form>
        </div>

    </div>
</main>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.querySelector('span').innerText = '🙈';
    } else {
        input.type = 'password';
        btn.querySelector('span').innerText = '👁';
    }
}
</script>

<?php
get_footer();
