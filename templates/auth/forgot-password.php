<?php
/**
 * Template: Forgot Password Request Form
 * Route: /forgot-password/
 *
 * Styled to match the client-approved Opportunity Portal Modal design.
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
                    <?php esc_html_e( 'Reset Your Password', 'cuba-investment-core' ); ?>
                </h1>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    <?php esc_html_e( 'Enter your registered email address and we will dispatch password recovery instructions.', 'cuba-investment-core' ); ?>
                </p>
            </div>

            <?php if ( $submitted ) : ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-xs sm:text-sm leading-relaxed mb-4 text-center space-y-3" role="status">
                    <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-lg font-bold">✓</div>
                    <div class="font-bold text-emerald-950"><?php esc_html_e( 'Recovery Link Dispatched', 'cuba-investment-core' ); ?></div>
                    <p class="text-xs text-emerald-800">
                        <?php esc_html_e( 'If an account exists for this email address, password recovery instructions have been sent. Please check your inbox and spam folder.', 'cuba-investment-core' ); ?>
                    </p>
                </div>

                <div class="text-center pt-2">
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md">
                        <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                    </a>
                </div>

            <?php else : ?>

                <form method="POST" action="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="space-y-4">
                    <?php NonceManager::field( 'cin_forgot_password', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_forgot_password" />

                    <div>
                        <label for="forgot-email" class="block text-xs font-medium text-slate-700 mb-1">
                            <?php esc_html_e( 'Registered Email *', 'cuba-investment-core' ); ?>
                        </label>
                        <input 
                            type="email" 
                            id="forgot-email" 
                            name="email" 
                            required 
                            autocomplete="email" 
                            placeholder="your.name@example.com" 
                            class="form-input text-xs sm:text-sm"
                        />
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg font-bold shadow-md cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span><?php esc_html_e( 'Send Recovery Link', 'cuba-investment-core' ); ?></span>
                    </button>

                    <div class="text-center pt-1">
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-xs font-bold text-slate-600 hover:text-primary">
                            &larr; <?php esc_html_e( 'Back to Log In', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </form>

            <?php endif; ?>

        </div>

    </div>
</div>

<?php
get_footer();
