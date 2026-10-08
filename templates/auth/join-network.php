<?php
/**
 * Template: Registration Entry / Objective Selection
 * Route: /join-network/
 *
 * @package CubaInvestment\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main py-16 sm:py-20 bg-slate-50 min-h-[75vh] flex items-center">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Header -->
        <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
            <span class="badge badge-accent mb-3"><?php esc_html_e( 'Platform Registration', 'cuba-investment-core' ); ?></span>
            <h1 class="text-3xl sm:text-4xl font-heading font-extrabold text-primary mb-4 tracking-tight">
                <?php esc_html_e( 'Join Cuba Investment Network', 'cuba-investment-core' ); ?>
            </h1>
            <p class="text-slate-600 text-base sm:text-lg leading-relaxed">
                <?php esc_html_e( 'Connect with Cuba-focused businesses, investors, and strategic partners through our network.', 'cuba-investment-core' ); ?>
            </p>
            <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 bg-accent/10 text-accent rounded-full text-xs font-semibold">
                <span>✦</span> <?php esc_html_e( 'Early Access — Free During Our Launch Period', 'cuba-investment-core' ); ?>
            </div>
        </div>

        <!-- Two Selection Cards -->
        <div class="grid md:grid-cols-2 gap-8 items-stretch">
            
            <!-- Option A: Join as an Investor -->
            <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm hover:shadow-md hover:border-primary/40 transition-all duration-200 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-primary-50 text-primary flex items-center justify-center mb-6 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <span class="text-xs font-bold uppercase tracking-wider text-primary block mb-2">
                        <?php esc_html_e( 'For Capital & Strategic Partners', 'cuba-investment-core' ); ?>
                    </span>

                    <h2 class="text-2xl font-heading font-extrabold text-primary mb-3">
                        <?php esc_html_e( 'Option A — Join as an Investor', 'cuba-investment-core' ); ?>
                    </h2>

                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6">
                        <?php esc_html_e( 'Create an investor account to explore Cuba-focused business opportunities and connect with Business Owners.', 'cuba-investment-core' ); ?>
                    </p>

                    <!-- Highlights List -->
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Explore reviewed Cuban business opportunities', 'cuba-investment-core' ); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Request direct introductions to founders', 'cuba-investment-core' ); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Zero membership or platform connection fees', 'cuba-investment-core' ); ?></span>
                        </li>
                    </ul>
                </div>

                <a 
                    href="<?php echo esc_url( home_url( '/register/investor/' ) ); ?>" 
                    class="btn btn-primary w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all text-center justify-center"
                >
                    <?php esc_html_e( 'Join as an Investor →', 'cuba-investment-core' ); ?>
                </a>
            </div>

            <!-- Option B: Join as a Business Owner -->
            <div class="card p-8 sm:p-10 bg-white border border-slate-200/90 rounded-2xl shadow-sm hover:shadow-md hover:border-accent/40 transition-all duration-200 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-accent-50 text-accent flex items-center justify-center mb-6 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-7 h-7 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>

                    <span class="text-xs font-bold uppercase tracking-wider text-accent block mb-2">
                        <?php esc_html_e( 'For Cuban Enterprises & MIPYMEs', 'cuba-investment-core' ); ?>
                    </span>

                    <h2 class="text-2xl font-heading font-extrabold text-primary mb-3">
                        <?php esc_html_e( 'Option B — Join as a Business Owner', 'cuba-investment-core' ); ?>
                    </h2>

                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6">
                        <?php esc_html_e( 'Create a Business Owner account to present your business, describe your capital requirements, and connect with potential investors.', 'cuba-investment-core' ); ?>
                    </p>

                    <!-- Highlights List -->
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 mb-8">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Present your business profile and capital needs', 'cuba-investment-core' ); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Direct communication with qualified investors', 'cuba-investment-core' ); ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <span><?php esc_html_e( 'Free listing during the platform launch period', 'cuba-investment-core' ); ?></span>
                        </li>
                    </ul>
                </div>

                <a 
                    href="<?php echo esc_url( home_url( '/register/business-owner/' ) ); ?>" 
                    class="btn btn-accent w-full btn-lg font-bold shadow-md hover:shadow-lg transition-all text-center justify-center"
                >
                    <?php esc_html_e( 'Join as a Business Owner →', 'cuba-investment-core' ); ?>
                </a>
            </div>

        </div>

        <!-- Already registered footer -->
        <div class="mt-12 text-center text-sm text-slate-500">
            <?php esc_html_e( 'Already registered with Cuba Investment Network?', 'cuba-investment-core' ); ?>
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="text-primary font-bold hover:underline ml-1">
                <?php esc_html_e( 'Log In to Portal &rarr;', 'cuba-investment-core' ); ?>
            </a>
        </div>
    </div>
</main>

<?php
get_footer();
