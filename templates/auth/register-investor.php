<?php
/**
 * Template: Investor Registration Form
 * Route: /register/investor/
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

$is_success       = isset( $_GET['status'] ) && 'registered' === $_GET['status'];
$flash_error      = FormHandler::get_flash_error();
$registered_email = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';

$countries = [
    'United States'  => 'United States',
    'Spain'          => 'Spain',
    'Canada'         => 'Canada',
    'United Kingdom' => 'United Kingdom',
    'Mexico'         => 'Mexico',
    'Panama'         => 'Panama',
    'Italy'          => 'Italy',
    'France'         => 'France',
    'Germany'        => 'Germany',
    'Switzerland'    => 'Switzerland',
    'Other'          => 'Other Country',
];
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
                    class="flex-1 py-3 text-sm font-semibold border-b-2 border-primary text-primary transition-colors text-center"
                >
                    <?php esc_html_e( 'Create Account', 'cuba-investment-core' ); ?>
                </a>
                <a 
                    href="<?php echo esc_url( home_url( '/login/' ) ); ?>" 
                    class="flex-1 py-3 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-800 transition-colors text-center"
                >
                    <?php esc_html_e( 'Log In', 'cuba-investment-core' ); ?>
                </a>
            </div>
        </div>

        <div class="p-6">

            <?php if ( $is_success ) : ?>
                <!-- Success Confirmation State (Matching Modal) -->
                <div class="p-5 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl text-center space-y-3">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-xl font-bold">✓</div>
                    <h3 class="text-base font-bold text-emerald-900"><?php esc_html_e( 'Registration Received!', 'cuba-investment-core' ); ?></h3>
                    <p class="text-xs text-emerald-800 leading-relaxed">
                        <?php if ( $registered_email ) : ?>
                            <?php printf( esc_html__( 'A verification email has been dispatched to %s. Please check your inbox and verify your account.', 'cuba-investment-core' ), '<strong>' . esc_html( $registered_email ) . '</strong>' ); ?>
                        <?php else : ?>
                            <?php esc_html_e( 'A verification email has been dispatched to your email address. Please check your inbox and verify your account.', 'cuba-investment-core' ); ?>
                        <?php endif; ?>
                    </p>
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2">
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn btn-primary btn-sm px-6 font-bold shadow-sm w-full sm:w-auto">
                            <?php esc_html_e( 'Proceed to Log In', 'cuba-investment-core' ); ?>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/verify-email/' ) ); ?>" class="btn btn-secondary btn-sm px-4 font-semibold w-full sm:w-auto">
                            <?php esc_html_e( 'Resend Link', 'cuba-investment-core' ); ?>
                        </a>
                    </div>
                </div>

            <?php else : ?>

                <!-- Error Alert Box -->
                <?php if ( ! empty( $flash_error ) ) : ?>
                    <div class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-800 rounded-xl text-xs flex items-start gap-2.5" role="alert" aria-live="assertive">
                        <span class="font-bold text-red-600 mt-0.5">⚠️</span>
                        <div class="leading-relaxed"><?php echo esc_html( $flash_error ); ?></div>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" action="<?php echo esc_url( home_url( '/register/investor/' ) ); ?>" class="space-y-4" novalidate>
                    <?php NonceManager::field( 'cin_register_investor', '_cin_nonce' ); ?>
                    <input type="hidden" name="cin_action" value="cin_register_investor" />

                    <!-- Honeypot Bot Trap -->
                    <div style="display:none !important;" aria-hidden="true">
                        <label for="website_hp">Leave this field blank</label>
                        <input type="text" id="website_hp" name="website_hp" tabindex="-1" autocomplete="off" />
                    </div>

                    <!-- Role Selector (Investor vs Business Owner) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                            <?php esc_html_e( 'Select Your Objective', 'cuba-investment-core' ); ?>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <a 
                                href="<?php echo esc_url( home_url( '/register/investor/' ) ); ?>" 
                                class="flex items-center justify-center gap-2 p-3 border-2 border-primary bg-primary-50/50 rounded-xl text-primary font-semibold text-xs sm:text-sm transition-all shadow-xs text-center"
                            >
                                <span><?php esc_html_e( 'Join as an Investor', 'cuba-investment-core' ); ?></span>
                            </a>
                            <a 
                                href="<?php echo esc_url( home_url( '/register/business-owner/' ) ); ?>" 
                                class="flex items-center justify-center gap-2 p-3 border-2 border-slate-200 rounded-xl text-slate-600 font-semibold text-xs sm:text-sm transition-all hover:border-slate-300 text-center"
                            >
                                <span><?php esc_html_e( 'Join as Business Owner', 'cuba-investment-core' ); ?></span>
                            </a>
                        </div>
                    </div>

                    <!-- Name Inputs -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="reg-first-name" class="block text-xs font-medium text-slate-700 mb-1">
                                <?php esc_html_e( 'First Name *', 'cuba-investment-core' ); ?>
                            </label>
                            <input 
                                type="text" 
                                id="reg-first-name" 
                                name="first_name" 
                                required 
                                autocomplete="given-name" 
                                placeholder="Carlos" 
                                class="form-input text-xs sm:text-sm"
                            />
                        </div>
                        <div>
                            <label for="reg-last-name" class="block text-xs font-medium text-slate-700 mb-1">
                                <?php esc_html_e( 'Last Name *', 'cuba-investment-core' ); ?>
                            </label>
                            <input 
                                type="text" 
                                id="reg-last-name" 
                                name="last_name" 
                                required 
                                autocomplete="family-name" 
                                placeholder="Valdes" 
                                class="form-input text-xs sm:text-sm"
                            />
                        </div>
                    </div>

                    <!-- Email Input -->
                    <div>
                        <label for="reg-email" class="block text-xs font-medium text-slate-700 mb-1">
                            <?php esc_html_e( 'Email Address *', 'cuba-investment-core' ); ?>
                        </label>
                        <input 
                            type="email" 
                            id="reg-email" 
                            name="email" 
                            required 
                            autocomplete="email" 
                            placeholder="carlos.valdes@example.com" 
                            class="form-input text-xs sm:text-sm"
                        />
                    </div>

                    <!-- Password Inputs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="reg-password" class="block text-xs font-medium text-slate-700 mb-1">
                                <?php esc_html_e( 'Password (min. 8 chars) *', 'cuba-investment-core' ); ?>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="reg-password" 
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
                                    onclick="const p=document.getElementById('reg-password'); p.type = p.type==='password'?'text':'password';"
                                    aria-label="<?php esc_attr_e( 'Toggle password visibility', 'cuba-investment-core' ); ?>"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="reg-password-confirm" class="block text-xs font-medium text-slate-700 mb-1">
                                <?php esc_html_e( 'Confirm Password *', 'cuba-investment-core' ); ?>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="reg-password-confirm" 
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
                                    onclick="const p=document.getElementById('reg-password-confirm'); p.type = p.type==='password'?'text':'password';"
                                    aria-label="<?php esc_attr_e( 'Toggle confirm password visibility', 'cuba-investment-core' ); ?>"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Country of Residence -->
                    <div>
                        <label for="reg-country" class="block text-xs font-medium text-slate-700 mb-1">
                            <?php esc_html_e( 'Country of Residence *', 'cuba-investment-core' ); ?>
                        </label>
                        <select id="reg-country" name="country" required class="form-input text-xs sm:text-sm">
                            <option value=""><?php esc_html_e( '— Select Your Country —', 'cuba-investment-core' ); ?></option>
                            <?php foreach ( $countries as $c_val => $c_label ) : ?>
                                <option value="<?php echo esc_attr( $c_val ); ?>"><?php echo esc_html( $c_label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Investor Accreditation Clause (Matching Modal) -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600">
                        <div class="flex items-start gap-2">
                            <input type="checkbox" id="investor-cert" name="accreditation_clause" checked class="mt-0.5 rounded text-accent focus:ring-accent">
                            <label for="investor-cert" class="cursor-pointer text-[11px] leading-relaxed text-slate-600">
                                <?php esc_html_e( 'I confirm that I am exploring private business opportunities in accordance with applicable laws in my jurisdiction.', 'cuba-investment-core' ); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Legal Consent (Matching Modal) -->
                    <div class="space-y-2 pt-1 text-xs text-slate-600">
                        <div class="flex items-start gap-2">
                            <input type="checkbox" id="terms-agree" name="terms_agree" value="1" required class="mt-0.5 rounded text-primary focus:ring-primary">
                            <label for="terms-agree" class="cursor-pointer text-[11px] leading-relaxed text-slate-600">
                                <?php printf( 
                                    esc_html__( 'I agree to the %sTerms of Service%s and %sPrivacy Policy%s.', 'cuba-investment-core' ),
                                    '<a href="' . esc_url( home_url( '/terms-of-service/' ) ) . '" target="_blank" class="text-primary font-bold hover:underline">', '</a>',
                                    '<a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '" target="_blank" class="text-primary font-bold hover:underline">', '</a>'
                                ); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn btn-primary w-full btn-lg mt-2 font-bold shadow-md cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span><?php esc_html_e( 'Create Free Account', 'cuba-investment-core' ); ?></span>
                    </button>

                    <!-- Switch to Login -->
                    <p class="text-xs text-center text-slate-500 pt-1">
                        <?php esc_html_e( 'Already registered?', 'cuba-investment-core' ); ?> 
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline cursor-pointer ml-1">
                            <?php esc_html_e( 'Log In', 'cuba-investment-core' ); ?> &rarr;
                        </a>
                    </p>
                </form>

            <?php endif; ?>

        </div>

    </div>
</div>

<?php
get_footer();
