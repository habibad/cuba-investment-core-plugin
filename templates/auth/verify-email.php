<?php
/**
 * Template: Email Verification Workflow & Resend Center
 * Route: /verify-email/
 *
 * Styled to match the client-approved Opportunity Portal Modal design.
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\EmailVerification;
use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$token = '';
if ( ! empty( $_GET['token'] ) ) {
    $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
} elseif ( ! empty( $_GET['amp;token'] ) ) {
    $token = sanitize_text_field( wp_unslash( $_GET['amp;token'] ) );
} elseif ( ! empty( $_GET['#038;token'] ) ) {
    $token = sanitize_text_field( wp_unslash( $_GET['#038;token'] ) );
}

$uid = 0;
if ( ! empty( $_GET['uid'] ) ) {
    $uid = absint( $_GET['uid'] );
} elseif ( ! empty( $_GET['amp;uid'] ) ) {
    $uid = absint( $_GET['amp;uid'] );
} elseif ( ! empty( $_GET['#038;uid'] ) ) {
    $uid = absint( $_GET['#038;uid'] );
}

$verification_attempted = ! empty( $token );
$verification_result    = EmailVerification::$last_result;

if ( $verification_attempted && null === $verification_result ) {
    $verification_result            = EmailVerification::verify( $uid, $token );
    EmailVerification::$last_result = $verification_result;
}

// Redirect to login if verified and headers have not yet been flushed
if ( true === $verification_result ) {
    if ( ! headers_sent() ) {
        wp_safe_redirect( add_query_arg( [ 'verified' => 'success' ], home_url( '/login/' ) ) );
        exit;
    }
} elseif ( 'already_verified' === $verification_result ) {
    if ( ! headers_sent() ) {
        wp_safe_redirect( add_query_arg( [ 'verified' => 'already' ], home_url( '/login/' ) ) );
        exit;
    }
}

$flash_error    = FormHandler::get_flash_error();
$notice_pending = isset( $_GET['notice'] ) && 'pending' === $_GET['notice'];
$notice_resent  = isset( $_GET['notice'] ) && 'resent' === $_GET['notice'];

get_header();
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

        <div class="p-6">

            <!-- Verification Result: Success (Fallback if headers were already sent) -->
            <?php if ( $verification_attempted && true === $verification_result ) : ?>
                <div class="p-5 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-center space-y-3">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-xl font-bold">✓</div>
                    <span class="badge badge-accent mb-1"><?php esc_html_e( 'Verification Complete', 'cuba-investment-core' ); ?></span>
                    <h1 class="text-xl sm:text-2xl font-heading font-extrabold text-primary">
                        <?php esc_html_e( 'Email Verified Successfully', 'cuba-investment-core' ); ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
                        <?php esc_html_e( 'Your email address has been verified and your account is active. You have full access during our launch period.', 'cuba-investment-core' ); ?>
                    </p>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary w-full btn-lg font-bold shadow-md text-center justify-center">
                        <?php esc_html_e( 'Proceed to Log In →', 'cuba-investment-core' ); ?>
                    </a>
                </div>

            <!-- Verification Result: Expired or Invalid Token -->
            <?php elseif ( $verification_attempted && is_wp_error( $verification_result ) ) : ?>
                <div class="p-5 bg-red-50 border border-red-200 rounded-xl text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto text-xl font-bold">⚠️</div>
                    <span class="badge badge-slate mb-1"><?php esc_html_e( 'Verification Issue', 'cuba-investment-core' ); ?></span>
                    <h1 class="text-xl font-heading font-extrabold text-primary">
                        <?php esc_html_e( 'Verification Link Expired', 'cuba-investment-core' ); ?>
                    </h1>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        <?php echo esc_html( $verification_result->get_error_message() ); ?>
                    </p>

                    <div class="pt-3 border-t border-red-200/60 text-left">
                        <h3 class="text-xs font-bold text-slate-700 mb-2">
                            <?php esc_html_e( 'Request a Fresh Verification Link', 'cuba-investment-core' ); ?>
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
                <div class="space-y-4">
                    <div class="text-center mb-3">
                        <h1 class="text-xl font-heading font-extrabold text-primary tracking-tight">
                            <?php esc_html_e( 'Email Verification Center', 'cuba-investment-core' ); ?>
                        </h1>
                        <p class="text-slate-600 text-xs mt-1 leading-relaxed">
                            <?php esc_html_e( 'All accounts must be verified before accessing the network.', 'cuba-investment-core' ); ?>
                        </p>
                    </div>

                    <?php if ( ! empty( $flash_error ) ) : ?>
                        <div class="p-3.5 bg-red-50 border border-red-200 text-red-900 rounded-xl text-xs flex items-start gap-2.5" role="alert">
                            <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                            <div class="leading-relaxed"><?php echo esc_html( $flash_error ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $notice_pending ) : ?>
                        <div class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs flex items-start gap-2.5">
                            <span class="font-bold text-amber-700 mt-0.5">ℹ</span>
                            <div class="leading-relaxed">
                                <?php esc_html_e( 'Your account email has not been verified yet. Please check your inbox for the link we sent or enter your email below to request a new one.', 'cuba-investment-core' ); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $notice_resent ) : ?>
                        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-xs flex items-center gap-2">
                            <span class="font-bold text-emerald-600">✓</span>
                            <span><?php esc_html_e( 'If an unverified account matches that email, a fresh verification link has been dispatched.', 'cuba-investment-core' ); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>" class="space-y-4">
                        <?php NonceManager::field( 'cin_resend_verification', '_cin_nonce' ); ?>
                        <input type="hidden" name="cin_action" value="cin_resend_verification" />

                        <div>
                            <label for="email" class="block text-xs font-medium text-slate-700 mb-1">
                                <?php esc_html_e( 'Registered Email Address *', 'cuba-investment-core' ); ?>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                autocomplete="email" 
                                placeholder="your.email@example.com" 
                                class="form-input text-xs sm:text-sm"
                            />
                        </div>

                        <button type="submit" class="btn btn-primary w-full btn-lg font-bold shadow-md cursor-pointer flex items-center justify-center gap-2">
                            <span><?php esc_html_e( 'Resend Verification Email', 'cuba-investment-core' ); ?></span>
                        </button>

                        <div class="text-center text-xs text-slate-500 pt-2">
                            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline">
                                &larr; <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<?php
get_footer();
