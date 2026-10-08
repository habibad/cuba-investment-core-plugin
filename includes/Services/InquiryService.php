<?php
/**
 * Cuba Investment Core - Inquiry Service
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Models\Inquiry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class InquiryService {

    /**
     * Submit an investor inquiry on a published opportunity
     *
     * @param int $investor_id
     * @param int $opportunity_id
     * @param array $data
     * @return int|\WP_Error Inquiry ID or WP_Error
     */
    public static function create_inquiry( $investor_id, $opportunity_id, array $data ) {
        global $wpdb;

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'invalid_opportunity', __( 'The specified business opportunity does not exist.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        $business_user_id = (int) $post->post_author;
        if ( $business_user_id === (int) $investor_id ) {
            return new \WP_Error( 'self_inquiry', __( 'You cannot submit an inquiry on your own listing.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : '';
        $message = isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '';
        $capital = isset( $data['capital_range'] ) ? sanitize_text_field( $data['capital_range'] ) : '';

        if ( empty( $subject ) ) {
            $subject = sprintf( __( 'Introduction Request: %s', 'cuba-investment-core' ), get_the_title( $post ) );
        }

        if ( empty( $message ) || strlen( $message ) < 10 ) {
            return new \WP_Error( 'empty_message', __( 'Please provide introductory context in your inquiry message.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );

        // Prevent duplicate spam submissions while prior inquiry is pending
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE opportunity_id = %d AND investor_user_id = %d AND status = %s",
                $opportunity_id,
                $investor_id,
                Constants::INQUIRY_PENDING
            )
        );

        if ( $existing ) {
            return new \WP_Error( 'duplicate_pending', __( 'You already have a pending introduction inquiry for this opportunity.', 'cuba-investment-core' ), [ 'status' => 409 ] );
        }

        $now = current_time( 'mysql' );

        $inserted = $wpdb->insert(
            $table,
            [
                'opportunity_id'   => $opportunity_id,
                'investor_user_id' => $investor_id,
                'business_user_id' => $business_user_id,
                'subject'          => $subject,
                'message'          => $message,
                'capital_range'    => $capital,
                'status'           => Constants::INQUIRY_PENDING,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            Logger::error( 'Failed to insert inquiry', [ 'investor_id' => $investor_id, 'opp_id' => $opportunity_id ] );
            return new \WP_Error( 'db_error', __( 'Could not save inquiry.', 'cuba-investment-core' ) );
        }

        $inquiry_id = $wpdb->insert_id;

        // Notify Business Owner
        $investor = get_userdata( $investor_id );
        NotificationService::send(
            $business_user_id,
            'inquiry_received',
            __( 'New Investor Introduction Request', 'cuba-investment-core' ),
            sprintf( __( '%s has requested an introduction regarding "%s".', 'cuba-investment-core' ), $investor ? $investor->display_name : 'An investor', get_the_title( $post ) ),
            home_url( '/dashboard/business' ),
            true
        );

        Logger::audit( 'inquiry_created', 'Investor submitted introduction request', [
            'inquiry_id'     => $inquiry_id,
            'opportunity_id' => $opportunity_id,
            'investor_id'    => $investor_id,
            'business_id'    => $business_user_id,
        ] );

        return $inquiry_id;
    }

    /**
     * Respond to an inquiry (Business Owner action)
     *
     * @param int $inquiry_id
     * @param int $business_user_id
     * @param string $action 'accept' or 'decline'
     * @param string $note Optional note
     * @return bool|\WP_Error
     */
    public static function respond( $inquiry_id, $business_user_id, $action, $note = '' ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );

        $inquiry = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $inquiry_id )
        );

        if ( ! $inquiry ) {
            return new \WP_Error( 'not_found', __( 'Inquiry not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $inquiry->business_user_id !== (int) $business_user_id && ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'unauthorized', __( 'You are not authorized to respond to this inquiry.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $new_status = ( 'accept' === $action ) ? Constants::INQUIRY_ACCEPTED : Constants::INQUIRY_DECLINED;

        $wpdb->update(
            $table,
            [
                'status'      => $new_status,
                'admin_notes' => sanitize_textarea_field( $note ),
                'updated_at'  => current_time( 'mysql' ),
            ],
            [ 'id' => (int) $inquiry_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        $opp = get_post( $inquiry->opportunity_id );
        $opp_title = $opp ? get_the_title( $opp ) : 'Opportunity';

        if ( 'accept' === $action ) {
            // 1. Establish direct connection
            $connection_id = ConnectionService::establish_connection(
                (int) $inquiry->investor_user_id,
                (int) $inquiry->business_user_id,
                (int) $inquiry->id,
                (int) $inquiry->opportunity_id
            );

            // 2. Initialize conversation
            if ( ! is_wp_error( $connection_id ) ) {
                $conversation_id = MessagingService::get_or_create_conversation( $connection_id, (int) $inquiry->business_user_id, (int) $inquiry->investor_user_id );

                // Post first introductory message
                MessagingService::send_message(
                    $conversation_id,
                    (int) $inquiry->investor_user_id,
                    (int) $inquiry->business_user_id,
                    $inquiry->message
                );
            }

            // 3. Notify Investor
            NotificationService::send(
                (int) $inquiry->investor_user_id,
                'inquiry_accepted',
                __( 'Connection Accepted!', 'cuba-investment-core' ),
                sprintf( __( 'The business owner of "%s" accepted your introduction request. Direct messaging is now open.', 'cuba-investment-core' ), $opp_title ),
                home_url( '/dashboard/investor' ),
                true
            );
        } else {
            // Notify Investor of polite declination
            NotificationService::send(
                (int) $inquiry->investor_user_id,
                'inquiry_declined',
                __( 'Update on your introduction inquiry', 'cuba-investment-core' ),
                sprintf( __( 'The business owner of "%s" is currently unable to proceed with your inquiry.', 'cuba-investment-core' ), $opp_title ),
                home_url( '/dashboard/investor' )
            );
        }

        Logger::audit( 'inquiry_responded', "Inquiry {$action}ed", [
            'inquiry_id' => $inquiry_id,
            'action'     => $action,
        ] );

        return true;
    }

    /**
     * Get inquiries for a user
     *
     * @param int $user_id
     * @param string $role 'investor' or 'business_owner'
     * @param int $limit
     * @return array
     */
    public static function get_for_user( $user_id, $role = 'investor', $limit = 20 ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );
        $field = ( 'business_owner' === $role ) ? 'business_user_id' : 'investor_user_id';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$field} = %d ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $user_id,
                $limit
            )
        );

        $results = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $item = new Inquiry( $row );
                $arr = $item->to_array();

                // Attach opportunity title
                $opp = get_post( $item->opportunity_id );
                $arr['opportunity_title'] = $opp ? get_the_title( $opp ) : '';
                $results[] = $arr;
            }
        }

        return $results;
    }
}
