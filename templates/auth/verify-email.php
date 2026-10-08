<?php
/**
 * Template: Email Verification Workflow & Resend Center
 * Route: /verify-email/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\EmailVerification;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$uid   = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

$verification_attempted = ( $uid > 0 && ! empty( $token ) );
$verification_result    = null;

if ( $verification_attempted ) {
    $verification_result = EmailVerification::verify( $uid, $token );
}

$notice_pending = isset( $_GET['notice'] ) && 'pending' === $_GET['notice'];
$notice_resent  = isset( $_GET['notice'] ) && 'resent' === $_GET['notice'];

get_header();
?>

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[80vh] flex items-center">
    <div class="container mx-auto px-4 max-w-md">

        <!-- Verification Result: Success -->
        <?php if ( $verification_attempted && true === $verification_result ) : ?>
            <div class="card p-8 sm:p-10 bg-white border border-emerald-200 rounded-2xl shadow-sm text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <span class="badge badge-accent mb-3"><?php esc_html_e( 'Verification Complete', 'cuba-investment-core' ); ?></span>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary mb-3">
                    <?php esc_html_e( 'Email Verified Successfully', 'cuba-investment-core' ); ?>
                </h1>

                <p class="text-slate-600 text-sm leading-relaxed mb-6">
                    <?php esc_html_e( 'Your email address has been verified and your account is now active. You have full access during our launch period.', 'cuba-investment-core' ); ?>
                </p>

                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all text-center justify-center">
                    <?php esc_html_e( 'Proceed to Log In →', 'cuba-investment-core' ); ?>
                </a>
            </div>

        <!-- Verification Result: Expired or Invalid Token -->
        <?php elseif ( $verification_attempted && is_wp_error( $verification_result ) ) : ?>
            <div class="card p-8 sm:p-10 bg-white border border-red-200 rounded-2xl shadow-sm text-center">
                <div class="w-16 h-16 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <span class="badge badge-slate mb-3"><?php esc_html_e( 'Verification Issue', 'cuba-investment-core' ); ?></span>
                <h1 class="text-2xl font-heading font-extrabold text-primary mb-3">
                    <?php esc_html_e( 'Verification Link Expired', 'cuba-investment-core' ); ?>
                </h1>

                <p class="text-slate-600 text-sm leading-relaxed mb-6">
                    <?php echo esc_html( $verification_result->get_error_message() ); ?>
                </p>

                <div class="p-6 bg-slate-50 border border-slate-200 rounded-xl text-left">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        <?php esc_html_e( 'Request a New Verification Link', 'cuba-investment-core' ); ?>
                    </h3>
                    <form method="POST" action="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>" class="space-y-3">
                        <?php NonceManager::field( 'cin_resend_verification', '_cin_nonce' ); ?>
                        <input type="hidden" name="cin_action" value="cin_resend_verification" />
                        <div>
                            <input 
                                type="email" 
                                name="email" 
                                required 
                                placeholder="Enter your registered email address" 
                                class="form-input text-xs sm:text-sm"
                            />
                        </div>
                        <button type="submit" class="btn btn-primary w-full btn-sm font-bold">
                            <?php esc_html_e( 'Send Fresh Link', 'cuba-investment-core' ); ?>
                        </button>
                    </form>
                </div>
            </div>

        <!-- Standalone Resend & Pending Center -->
        <?php else : ?>
            <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm">
                <div class="text-center mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-heading font-extrabold text-primary tracking-tight">
                        <?php esc_html_e( 'Email Verification', 'cuba-investment-core' ); ?>
                    </h1>
                    <p class="text-slate-600 text-xs sm:text-sm mt-1.5 leading-relaxed">
                        <?php esc_html_e( 'All accounts must be verified before accessing the network.', 'cuba-investment-core' ); ?>
                    </p>
                </div>

                <?php if ( $notice_pending ) : ?>
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs sm:text-sm flex items-start gap-3">
                        <span class="font-bold text-amber-700 mt-0.5">ℹ</span>
                        <div class="leading-relaxed">
                            <?php esc_html_e( 'Your account email has not been verified yet. Please check your inbox for the link we sent or enter your email below to request a new one.', 'cuba-investment-core' ); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $notice_resent ) : ?>
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-xs sm:text-sm flex items-center gap-2.5">
                        <span class="font-bold text-accent">✓</span>
                        <span><?php esc_html_e( 'If an unverified account matches that email, a fresh verification link has been dispatched.', 'cuba-investment-core' ); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>" class="space-y-4">
                    <?php NonceManager::field( 'cin_resend_verification', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_resend_verification" />

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

                    <button type="submit" class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all">
                        <?php esc_html_e( 'Resend Verification Email', 'cuba-investment-core' ); ?>
                    </button>

                    <div class="text-center text-xs text-slate-500 pt-3">
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline">
                            &larr; <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
get_footer();
