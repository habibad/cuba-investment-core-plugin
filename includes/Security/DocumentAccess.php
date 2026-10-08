<?php
/**
 * Cuba Investment Core - Restricted Document Access Design
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Security;

use CubaInvestment\Core\Auth\Permissions;
use CubaInvestment\Core\Common\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DocumentAccess {

    /**
     * Protected folder relative to uploads
     */
    const FOLDER_NAME = 'cin-protected';

    /**
     * Token expiration in seconds (default 2 hours)
     */
    const TOKEN_LIFETIME = 7200;

    /**
     * Get protected documents directory path
     *
     * @return string
     */
    public static function get_protected_dir() {
        $upload_dir = wp_upload_dir();
        $dir = trailingslashit( $upload_dir['basedir'] ) . self::FOLDER_NAME;

        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
            self::protect_directory( $dir );
        }

        return $dir;
    }

    /**
     * Protect directory with .htaccess and blank index.php
     *
     * @param string $dir
     */
    protected static function protect_directory( $dir ) {
        $htaccess_file = trailingslashit( $dir ) . '.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "# Cuba Investment Network - Restricted Pitch Documents\n";
            $htaccess_content .= "Require all denied\n";
            $htaccess_content .= "<Files ~ \"^.*\">\n  Deny from all\n</Files>\n";
            @file_put_contents( $htaccess_file, $htaccess_content ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }

        $index_file = trailingslashit( $dir ) . 'index.php';
        if ( ! file_exists( $index_file ) ) {
            @file_put_contents( $index_file, "<?php\n// Silence is golden.\nexit;\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
    }

    /**
     * Generate secure signed download token
     *
     * @param int $user_id
     * @param int $attachment_id
     * @param int $opportunity_id
     * @return string
     */
    public static function generate_token( $user_id, $attachment_id, $opportunity_id ) {
        $expires = time() + self::TOKEN_LIFETIME;
        $data    = "{$user_id}:{$attachment_id}:{$opportunity_id}:{$expires}";
        $sig     = hash_hmac( 'sha256', $data, wp_salt( 'auth' ) );

        return base64_encode( "{$data}:{$sig}" );
    }

    /**
     * Verify signed download token
     *
     * @param string $token
     * @return array|false Parsed array [user_id, attachment_id, opportunity_id] or false on failure
     */
    public static function verify_token( $token ) {
        $decoded = base64_decode( $token, true );
        if ( ! $decoded ) {
            return false;
        }

        $parts = explode( ':', $decoded );
        if ( count( $parts ) !== 5 ) {
            return false;
        }

        list( $user_id, $attachment_id, $opportunity_id, $expires, $sig ) = $parts;

        if ( time() > (int) $expires ) {
            Logger::warning( 'Expired document download token used', [
                'user_id' => $user_id,
                'attachment_id' => $attachment_id,
            ] );
            return false;
        }

        $expected_data = "{$user_id}:{$attachment_id}:{$opportunity_id}:{$expires}";
        $expected_sig  = hash_hmac( 'sha256', $expected_data, wp_salt( 'auth' ) );

        if ( ! hash_equals( $expected_sig, $sig ) ) {
            Logger::warning( 'Invalid document download signature', [
                'user_id' => $user_id,
                'attachment_id' => $attachment_id,
            ] );
            return false;
        }

        return [
            'user_id'        => (int) $user_id,
            'attachment_id'  => (int) $attachment_id,
            'opportunity_id' => (int) $opportunity_id,
        ];
    }

    /**
     * Check if user is authorized to download this opportunity document
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool
     */
    public static function user_can_download( $user_id, $opportunity_id ) {
        if ( ! $user_id || ! $opportunity_id ) {
            return false;
        }

        if ( Permissions::is_admin_or_reviewer( $user_id ) ) {
            return true;
        }

        $post = get_post( $opportunity_id );
        if ( ! $post ) {
            return false;
        }

        // Owner can always access own documents
        if ( (int) $post->post_author === (int) $user_id ) {
            return true;
        }

        // Connected investors can access documents if connection is active
        if ( Permissions::is_investor( $user_id ) && Permissions::are_connected( $user_id, (int) $post->post_author ) ) {
            return true;
        }

        return false;
    }
}
