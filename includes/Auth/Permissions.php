<?php
/**
 * Cuba Investment Core - Granular Permissions and Access Control
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Auth;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Permissions {

    /**
     * Check if user is an Investor
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_investor( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $user = get_userdata( $user_id );
        return $user && in_array( Constants::ROLE_INVESTOR, (array) $user->roles, true );
    }

    /**
     * Check if user is a Business Owner
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_business_owner( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $user = get_userdata( $user_id );
        return $user && in_array( Constants::ROLE_BUSINESS_OWNER, (array) $user->roles, true );
    }

    /**
     * Check if user is platform administrator or reviewer
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_admin_or_reviewer( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        return user_can( $user_id, 'manage_options' ) || user_can( $user_id, Constants::CAP_REVIEW_OPPORTUNITIES );
    }

    /**
     * Check if actor can edit target user profile
     *
     * @param int $actor_id
     * @param int $target_user_id
     * @return bool
     */
    public static function can_edit_profile( $actor_id, $target_user_id ) {
        if ( ! $actor_id || ! $target_user_id ) {
            return false;
        }
        if ( (int) $actor_id === (int) $target_user_id ) {
            return self::is_account_active( $actor_id );
        }
        return self::is_admin_or_reviewer( $actor_id );
    }

    /**
     * Check if user can edit a specific opportunity
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool
     */
    public static function can_edit_opportunity( $user_id, $opportunity_id ) {
        if ( self::is_admin_or_reviewer( $user_id ) ) {
            return true;
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return false;
        }

        // Must be the author and have capability
        return ( (int) $post->post_author === (int) $user_id ) && user_can( $user_id, Constants::CAP_EDIT_OWN_OPPORTUNITY );
    }

    /**
     * Check if user can send an inquiry for an opportunity
     *
     * @param int $user_id
     * @param int $opportunity_id
     * @return bool
     */
    public static function can_send_inquiry( $user_id, $opportunity_id ) {
        if ( ! $user_id ) {
            return false;
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY || $post->post_status !== 'publish' ) {
            return false;
        }

        // Cannot inquire on own opportunity
        if ( (int) $post->post_author === (int) $user_id ) {
            return false;
        }

        return user_can( $user_id, Constants::CAP_SEND_INQUIRIES );
    }

    /**
     * Check if two users have an active direct connection
     *
     * @param int $user_a
     * @param int $user_b
     * @return bool
     */
    public static function are_connected( $user_a, $user_b ) {
        global $wpdb;

        if ( ! $user_a || ! $user_b || $user_a === $user_b ) {
            return false;
        }

        $table = Constants::get_table_name( Constants::TABLE_CONNECTIONS );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE ((investor_user_id = %d AND business_user_id = %d)
                    OR (investor_user_id = %d AND business_user_id = %d))
                   AND status = %s",
                $user_a, $user_b,
                $user_b, $user_a,
                Constants::CONNECTION_ACTIVE
            )
        );

        return (int) $count > 0;
    }

    /**
     * Check if user can access a specific conversation
     *
     * @param int $user_id
     * @param int $conversation_id
     * @return bool
     */
    public static function can_access_conversation( $user_id, $conversation_id ) {
        global $wpdb;

        if ( ! $user_id || ! $conversation_id ) {
            return false;
        }

        if ( ! self::is_account_active( $user_id ) ) {
            return false;
        }

        if ( self::is_admin_or_reviewer( $user_id ) ) {
            return true;
        }

        $table = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );

        $convo = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT participant_one_id, participant_two_id FROM {$table} WHERE id = %d",
                $conversation_id
            )
        );

        if ( ! $convo ) {
            return false;
        }

        return ( (int) $convo->participant_one_id === (int) $user_id ) || ( (int) $convo->participant_two_id === (int) $user_id );
    }

    /**
     * Check if user account is active
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_account_active( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $status = get_user_meta( $user_id, '_cin_account_status', true );
        return ( 'active' === $status || empty( $status ) );
    }

    /**
     * Check if user account is suspended
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function is_account_suspended( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $status = get_user_meta( $user_id, '_cin_account_status', true );
        return ( 'suspended' === $status || 'disabled' === $status );
    }

    /**
     * Check if user can manage saved opportunities (bookmark deals)
     *
     * @param int|null $user_id
     * @return bool
     */
    public static function can_manage_saved_opportunities( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        return self::is_investor( $user_id ) || self::is_admin_or_reviewer( $user_id );
    }

    /**
     * Get primary platform role for user
     *
     * @param int|null $user_id
     * @return string
     */
    public static function get_user_role( $user_id = null ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! $user_id ) {
            return '';
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return '';
        }

        if ( in_array( Constants::ROLE_INVESTOR, (array) $user->roles, true ) ) {
            return Constants::ROLE_INVESTOR;
        }

        if ( in_array( Constants::ROLE_BUSINESS_OWNER, (array) $user->roles, true ) ) {
            return Constants::ROLE_BUSINESS_OWNER;
        }

        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return 'administrator';
        }

        return ! empty( $user->roles[0] ) ? $user->roles[0] : '';
    }
}

