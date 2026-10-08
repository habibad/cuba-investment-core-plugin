<?php
/**
 * Template: Forgot Password Request Form
 * Route: /forgot-password/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$submitted = isset( $_GET['submitted'] ) && 'true' === $_GET['submitted'];
?>

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[80vh] flex items-center">
    <div class="container mx-auto px-4 max-w-md">

        <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm">
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-heading font-extrabold text-primary tracking-tight">
                    <?php esc_html_e( 'Forgot Password?', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-slate-600 text-xs sm:text-sm mt-1.5 leading-relaxed">
                    <?php esc_html_e( 'Enter your registered email address and we will send you secure password reset instructions.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <?php if ( $submitted ) : ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-xs sm:text-sm leading-relaxed mb-6" role="status">
                    <span class="font-bold text-accent block mb-1">✓ <?php esc_html_e( 'Request Received', 'cuba-investment-core' ); ?></span>
                    <?php esc_html_e( 'If an account exists for this email address, password reset instructions have been dispatched. Please check your inbox and spam folder.', 'cuba-investment-core' ); ?>
                </div>

                <div class="text-center">
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md">
                        <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                    </a>
                </div>

            <?php else : ?>
                <form method="POST" action="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="space-y-4">
                    <?php NonceManager::field( 'cin_forgot_password', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_forgot_password" />

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <?php esc_html_e( 'Registered Email Address *', 'cuba-investment-core' ); ?>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            autocomplete="email" 
                            placeholder="your.email@example.com" 
                            class="form-input"
                        />
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all mt-4 cursor-pointer"
                    >
                        <?php esc_html_e( 'Send Reset Instructions', 'cuba-investment-core' ); ?>
                    </button>

                    <div class="text-center text-xs text-slate-500 pt-3">
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline">
                            &larr; <?php esc_html_e( 'Back to Log In', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php
get_footer();
