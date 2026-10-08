<?php
/**
 * Template: Investor Registration Form
 * Route: /register/investor/
 *
 * @package CubaInvestment\Core
 */

use CubaInvestment\Core\Auth\FormHandler;
use CubaInvestment\Core\Security\NonceManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$is_success  = isset( $_GET['status'] ) && 'registered' === $_GET['status'];
$flash_error = FormHandler::get_flash_error();
$registered_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
?>

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[80vh] flex items-center">
    <div class="container mx-auto px-4 max-w-xl">

        <?php if ( $is_success ) : ?>
            <!-- Success Confirmation Screen -->
            <div class="card p-8 sm:p-10 bg-white border border-emerald-200 rounded-2xl shadow-sm text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                <span class="badge badge-accent mb-3"><?php esc_html_e( 'Account Created', 'cuba-investment-core' ); ?></span>
                <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary mb-3">
                    <?php esc_html_e( 'Check Your Email', 'cuba-investment-core' ); ?>
                </h1>

                <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6">
                    <?php if ( $registered_email ) : ?>
                        <?php printf( esc_html__( 'We have sent a secure verification link to %s. Please click the link in your email to activate your account.', 'cuba-investment-core' ), '<strong>' . esc_html( $registered_email ) . '</strong>' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'We have sent a secure verification link to your email address. Please click the link in your email to activate your account.', 'cuba-investment-core' ); ?>
                    <?php endif; ?>
                </p>

                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl text-xs text-slate-500 text-left space-y-2 mb-8">
                    <p class="font-semibold text-slate-700"><?php esc_html_e( 'Did not receive the email?', 'cuba-investment-core' ); ?></p>
                    <p><?php esc_html_e( '• Check your spam or promotions folder.', 'cuba-investment-core' ); ?></p>
                    <p><?php esc_html_e( '• Verification links expire in 24 hours.', 'cuba-investment-core' ); ?></p>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary w-full sm:w-auto font-bold px-6">
                        <?php esc_html_e( 'Return to Log In', 'cuba-investment-core' ); ?>
                    </a>
                    <a href="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>" class="btn btn-secondary w-full sm:w-auto font-semibold px-6">
                        <?php esc_html_e( 'Resend Verification Email', 'cuba-investment-core' ); ?>
                    </a>
                </div>
            </div>

        <?php else : ?>
            <!-- Registration Form Card -->
            <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm">
                <!-- Header -->
                <div class="mb-8">
                    <a href="<?php echo esc_url( home_url( '/join-network/' ) ); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-primary transition-colors mb-4">
                        &larr; <?php esc_html_e( 'Back to registration options', 'cuba-investment-core' ); ?>
                    </a>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="badge badge-primary text-[11px]"><?php esc_html_e( 'Investor Account', 'cuba-investment-core' ); ?></span>
                        <span class="text-xs text-slate-400">&bull;</span>
                        <span class="text-xs font-semibold text-accent"><?php esc_html_e( 'Free Launch Membership', 'cuba-investment-core' ); ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-primary tracking-tight">
                        <?php esc_html_e( 'Join as an Investor', 'cuba-investment-core' ); ?>
                    </h1>
                    <p class="text-slate-600 text-xs sm:text-sm mt-1.5 leading-relaxed">
                        <?php esc_html_e( 'Create an investor account to explore Cuba-focused business opportunities and connect with Business Owners.', 'cuba-investment-core' ); ?>
                    </p>
                </div>

                <!-- Flash Error Announcement -->
                <?php if ( ! empty( $flash_error ) ) : ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-xs sm:text-sm text-red-800 flex items-start gap-3" role="alert" aria-live="assertive">
                        <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                        <div class="leading-relaxed"><?php echo esc_html( $flash_error ); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="<?php echo esc_url( home_url( '/register/investor/' ) ); ?>" class="space-y-4" novalidate>
                    <?php NonceManager::field( 'cin_register_investor', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_register_investor" />

                    <!-- Honeypot Bot Trap -->
                    <div style="display:none !important;" aria-hidden="true">
                        <label for="website_hp">Leave this field blank</label>
                        <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off" />
                    </div>

                    <!-- First and Last Name Grid -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                <?php esc_html_e( 'First Name *', 'cuba-investment-core' ); ?>
                            </label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                required 
                                autocomplete="given-name" 
                                placeholder="Carlos" 
                                class="form-input"
                            />
                        </div>
                        <div>
                            <label for="last_name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                <?php esc_html_e( 'Last Name *', 'cuba-investment-core' ); ?>
                            </label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                required 
                                autocomplete="family-name" 
                                placeholder="Mendez" 
                                class="form-input"
                            />
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <?php esc_html_e( 'Email Address *', 'cuba-investment-core' ); ?>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            autocomplete="email" 
                            placeholder="carlos.mendez@example.com" 
                            class="form-input"
                        />
                        <p class="text-[11px] text-slate-400 mt-1"><?php esc_html_e( 'We will send a verification link to this email address.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- Password and Confirm Password Grid -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                <?php esc_html_e( 'Password *', 'cuba-investment-core' ); ?>
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
                        <div>
                            <label for="password_confirm" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                <?php esc_html_e( 'Confirm Password *', 'cuba-investment-core' ); ?>
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
                                    class="form-input pr-10"
                                />
                                <button 
                                    type="button" 
                                    onclick="togglePasswordVisibility('password_confirm', this)" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                                    aria-label="<?php esc_attr_e( 'Toggle confirm password visibility', 'cuba-investment-core' ); ?>"
                                >
                                    <span class="text-xs">👁</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-400"><?php esc_html_e( 'Password must be at least 8 characters and include letters and numbers.', 'cuba-investment-core' ); ?></p>

                    <!-- Country of Residence -->
                    <div>
                        <label for="country" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <?php esc_html_e( 'Country of Residence *', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="country" name="country" required class="form-input">
                            <option value=""><?php esc_html_e( 'Select your country...', 'cuba-investment-core' ); ?></option>
                            <option value="Spain">Spain</option>
                            <option value="Canada">Canada</option>
                            <option value="Mexico">Mexico</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="Italy">Italy</option>
                            <option value="Panama">Panama</option>
                            <option value="Dominican Republic">Dominican Republic</option>
                            <option value="Cuba">Cuba</option>
                            <option value="United States">United States</option>
                            <option value="Other">Other Country</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1"><?php esc_html_e( 'Required for jurisdictional compliance and cross-border guidance.', 'cuba-investment-core' ); ?></p>
                    </div>

                    <!-- Terms of Service Agreement -->
                    <div class="pt-2 border-t border-slate-100 space-y-3">
                        <div class="flex items-start gap-2.5">
                            <input 
                                type="checkbox" 
                                id="terms_agree" 
                                name="terms_agree" 
                                value="1" 
                                required 
                                class="mt-1 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer"
                            />
                            <label for="terms_agree" class="text-xs text-slate-600 leading-relaxed cursor-pointer">
                                <?php printf(
                                    esc_html__( 'I agree to the %s of Cuba Investment Network. *', 'cuba-investment-core' ),
                                    '<a href="' . esc_url( home_url( '/terms-of-service/' ) ) . '" target="_blank" class="text-primary font-semibold hover:underline">' . esc_html__( 'Terms of Service', 'cuba-investment-core' ) . '</a>'
                                ); ?>
                            </label>
                        </div>

                        <!-- Privacy Policy Acknowledgement -->
                        <div class="flex items-start gap-2.5">
                            <input 
                                type="checkbox" 
                                id="privacy_agree" 
                                name="privacy_agree" 
                                value="1" 
                                required 
                                class="mt-1 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer"
                            />
                            <label for="privacy_agree" class="text-xs text-slate-600 leading-relaxed cursor-pointer">
                                <?php printf(
                                    esc_html__( 'I have read and acknowledge the %s and %s. *', 'cuba-investment-core' ),
                                    '<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '" target="_blank" class="text-primary font-semibold hover:underline">' . esc_html__( 'Privacy Policy', 'cuba-investment-core' ) . '</a>',
                                    '<a href="' . esc_url( home_url( '/risk-disclosure/' ) ) . '" target="_blank" class="text-primary font-semibold hover:underline">' . esc_html__( 'General Risk Disclosure', 'cuba-investment-core' ) . '</a>'
                                ); ?>
                            </label>
                        </div>

                        <!-- Optional Marketing Communication Opt-In -->
                        <div class="flex items-start gap-2.5">
                            <input 
                                type="checkbox" 
                                id="marketing_opt_in" 
                                name="marketing_opt_in" 
                                value="1" 
                                class="mt-1 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer"
                            />
                            <label for="marketing_opt_in" class="text-xs text-slate-500 leading-relaxed cursor-pointer">
                                <?php esc_html_e( 'Send me periodic research digests on Cuba’s private sector and market insights (optional).', 'cuba-investment-core' ); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all mt-4 cursor-pointer"
                    >
                        <?php esc_html_e( 'Create Free Investor Account', 'cuba-investment-core' ); ?>
                    </button>

                    <!-- Links -->
                    <div class="text-center text-xs text-slate-500 pt-3">
                        <?php esc_html_e( 'Already registered?', 'cuba-investment-core' ); ?>
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline ml-1">
                            <?php esc_html_e( 'Sign In', 'cuba-investment-core' ); ?> &rarr;
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

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
