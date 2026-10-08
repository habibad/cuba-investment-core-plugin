<?php
/**
 * Cuba Investment Core - Branded Transactional Email Engine
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Common;

use CubaInvestment\Core\Auth\EmailVerification;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mailer {

    /**
     * Get official support contact link or email
     * Never invents email address; uses configured theme mod or site contact URL
     *
     * @return array [ 'label' => string, 'url' => string ]
     */
    public static function get_support_contact() {
        $email = get_theme_mod( 'angel_support_email', '' );
        if ( ! empty( $email ) && is_email( $email ) ) {
            return [
                'label' => $email,
                'url'   => 'mailto:' . esc_attr( $email ),
            ];
        }

        return [
            'label' => __( 'Contact Support Portal', 'cuba-investment-core' ),
            'url'   => home_url( '/contact/' ),
        ];
    }

    /**
     * Send email verification link
     *
     * @param int    $user_id
     * @param string $raw_token
     * @param string $role 'investor' or 'business_owner'
     * @return bool
     */
    public static function send_verification_email( $user_id, $raw_token, $role = 'investor' ) {
        $user = get_userdata( $user_id );
        if ( ! $user || ! is_email( $user->user_email ) ) {
            return false;
        }

        $verify_url = EmailVerification::get_verification_url( $user_id, $raw_token );
        $role_label = ( 'business_owner' === $role ) ? __( 'Business Owner', 'cuba-investment-core' ) : __( 'Investor', 'cuba-investment-core' );

        $subject = __( 'Verify Your Email Address — Cuba Investment Network', 'cuba-investment-core' );

        $headline = sprintf( __( 'Welcome to Cuba Investment Network, %s', 'cuba-investment-core' ), esc_html( $user->first_name ?: $user->display_name ) );
        $body = sprintf(
            __( 'Thank you for registering your %s account. To activate your account and complete your registration, please verify your email address by clicking the button below.', 'cuba-investment-core' ),
            $role_label
        );

        $html = self::render_template( [
            'headline'     => $headline,
            'body'         => $body,
            'cta_text'     => __( 'Verify Email Address', 'cuba-investment-core' ),
            'cta_url'      => $verify_url,
            'note'         => __( 'This verification link is single-use and will expire in 24 hours. If you did not create an account on Cuba Investment Network, please disregard this message.', 'cuba-investment-core' ),
            'raw_fallback' => $verify_url,
        ] );

        return self::send( $user->user_email, $subject, $html );
    }

    /**
     * Send account verification success email
     *
     * @param int $user_id
     * @return bool
     */
    public static function send_welcome_verified_email( $user_id ) {
        $user = get_userdata( $user_id );
        if ( ! $user || ! is_email( $user->user_email ) ) {
            return false;
        }

        $subject = __( 'Account Verified — Welcome to Cuba Investment Network', 'cuba-investment-core' );

        $headline = sprintf( __( 'Your Account is Active, %s!', 'cuba-investment-core' ), esc_html( $user->first_name ?: $user->display_name ) );
        $body     = __( 'Your email has been successfully verified. You now have access to your account and the Cuba Investment Network platform during our launch period.', 'cuba-investment-core' );

        $login_url = home_url( '/login/' );

        $html = self::render_template( [
            'headline'     => $headline,
            'body'         => $body,
            'cta_text'     => __( 'Log In to Portal', 'cuba-investment-core' ),
            'cta_url'      => $login_url,
            'note'         => __( 'All accounts enjoy full access during the platform launch period. No billing or payment information is required.', 'cuba-investment-core' ),
            'raw_fallback' => $login_url,
        ] );

        return self::send( $user->user_email, $subject, $html );
    }

    /**
     * Send branded password reset email
     *
     * @param int    $user_id
     * @param string $reset_key
     * @return bool
     */
    public static function send_password_reset_email( $user_id, $reset_key ) {
        $user = get_userdata( $user_id );
        if ( ! $user || ! is_email( $user->user_email ) ) {
            return false;
        }

        $reset_url = add_query_arg(
            [
                'key'   => $reset_key,
                'login' => rawurlencode( $user->user_login ),
            ],
            home_url( '/reset-password/' )
        );

        $subject = __( 'Password Reset Request — Cuba Investment Network', 'cuba-investment-core' );

        $headline = __( 'Reset Your Password', 'cuba-investment-core' );
        $body = sprintf(
            __( 'A password reset request was received for your account (%s). Click the button below to choose a new password.', 'cuba-investment-core' ),
            esc_html( $user->user_email )
        );

        $html = self::render_template( [
            'headline'     => $headline,
            'body'         => $body,
            'cta_text'     => __( 'Reset Password', 'cuba-investment-core' ),
            'cta_url'      => $reset_url,
            'note'         => __( 'This password reset link is time-sensitive. If you did not initiate this request, you can safely ignore this email; your existing password will remain unchanged.', 'cuba-investment-core' ),
            'raw_fallback' => $reset_url,
        ] );

        return self::send( $user->user_email, $subject, $html );
    }

    /**
     * Dispatch email with HTML headers
     *
     * @param string $to
     * @param string $subject
     * @param string $html_content
     * @return bool
     */
    protected static function send( $to, $subject, $html_content ) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
        ];

        return wp_mail( $to, $subject, $html_content, $headers );
    }

    /**
     * Render responsive branded HTML email layout
     *
     * @param array $params
     * @return string
     */
    protected static function render_template( array $params ) {
        $site_name   = get_bloginfo( 'name' );
        $home_url    = home_url( '/' );
        $logo_url    = get_template_directory_uri() . '/assets/images/logo.png';
        $support     = self::get_support_contact();
        $headline    = isset( $params['headline'] ) ? $params['headline'] : '';
        $body        = isset( $params['body'] ) ? $params['body'] : '';
        $cta_text    = isset( $params['cta_text'] ) ? $params['cta_text'] : '';
        $cta_url     = isset( $params['cta_url'] ) ? $params['cta_url'] : '';
        $note        = isset( $params['note'] ) ? $params['note'] : '';
        $raw_url     = isset( $params['raw_fallback'] ) ? $params['raw_fallback'] : '';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $headline ); ?></title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #1E293B; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #F8FAFC; padding: 40px 16px;">
        <tr>
            <td align="center">
                <!-- Main Container Card -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 580px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    
                    <!-- Header with Brand Logo -->
                    <tr>
                        <td align="center" style="background-color: #0A2540; padding: 32px 24px; border-bottom: 3px solid #00875A;">
                            <a href="<?php echo esc_url( $home_url ); ?>" target="_blank" style="text-decoration: none; display: inline-block;">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" height="38" style="height: 38px; width: auto; display: block; border: 0;" />
                            </a>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 40px 32px 32px 32px;">
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 700; line-height: 1.3; color: #0A2540;">
                                <?php echo esc_html( $headline ); ?>
                            </h1>

                            <p style="margin: 0 0 28px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                <?php echo esc_html( $body ); ?>
                            </p>

                            <?php if ( ! empty( $cta_url ) && ! empty( $cta_text ) ) : ?>
                                <!-- CTA Button -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 0 32px 0;">
                                    <tr>
                                        <td align="center" style="border-radius: 8px; background-color: #00875A;">
                                            <a href="<?php echo esc_url( $cta_url ); ?>" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">
                                                <?php echo esc_html( $cta_text ); ?> &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            <?php endif; ?>

                            <?php if ( ! empty( $raw_url ) ) : ?>
                                <p style="margin: 0 0 20px 0; font-size: 12px; line-height: 1.5; color: #94A3B8; word-break: break-all;">
                                    <?php esc_html_e( 'If the button above does not work, copy and paste this link into your browser:', 'cuba-investment-core' ); ?><br />
                                    <a href="<?php echo esc_url( $raw_url ); ?>" target="_blank" style="color: #00875A; text-decoration: underline;"><?php echo esc_url( $raw_url ); ?></a>
                                </p>
                            <?php endif; ?>

                            <?php if ( ! empty( $note ) ) : ?>
                                <div style="background-color: #F8FAFC; border-left: 3px solid #CBD5E1; padding: 12px 16px; margin: 24px 0 0 0; border-radius: 0 6px 6px 0;">
                                    <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748B;">
                                        <?php echo esc_html( $note ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F8FAFC; padding: 24px 32px; border-top: 1px solid #E2E8F0; text-align: center;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #94A3B8;">
                                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>. <?php esc_html_e( 'All rights reserved.', 'cuba-investment-core' ); ?>
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94A3B8;">
                                <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" style="color: #64748B; text-decoration: underline;"><?php esc_html_e( 'Privacy Policy', 'cuba-investment-core' ); ?></a> &bull; 
                                <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>" style="color: #64748B; text-decoration: underline;"><?php esc_html_e( 'Terms of Service', 'cuba-investment-core' ); ?></a> &bull; 
                                <a href="<?php echo esc_url( $support['url'] ); ?>" style="color: #64748B; text-decoration: underline;"><?php echo esc_html( $support['label'] ); ?></a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
