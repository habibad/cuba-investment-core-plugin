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
use CubaInvestment\Core\Auth\Permissions;

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

        if ( ! Permissions::is_account_active( $investor_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended. Submitting enquiries is blocked.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! Permissions::is_investor( $investor_id ) && ! Permissions::is_admin_or_reviewer( $investor_id ) ) {
            return new \WP_Error( 'forbidden_role', __( 'Only registered investors can submit introduction enquiries.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $post = get_post( $opportunity_id );
        if ( ! $post || $post->post_type !== Constants::POST_TYPE_OPPORTUNITY ) {
            return new \WP_Error( 'invalid_opportunity', __( 'The specified business opportunity does not exist.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( 'publish' !== $post->post_status ) {
            return new \WP_Error( 'opportunity_not_published', __( 'You cannot submit an enquiry on an unpublished opportunity.', 'cuba-investment-core' ), [ 'status' => 400 ] );
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

        if ( ! Permissions::is_account_active( $business_user_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended. Responding to enquiries is blocked.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( ! $inquiry ) {
            return new \WP_Error( 'not_found', __( 'Inquiry not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $inquiry->business_user_id !== (int) $business_user_id && ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'unauthorized', __( 'You are not authorized to respond to this inquiry.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( Constants::INQUIRY_PENDING !== $inquiry->status ) {
            return new \WP_Error( 'invalid_status', __( 'This inquiry is not pending response or has already been responded to.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $is_accepted = in_array( strtolower( (string) $action ), [ 'accept', 'accepted' ], true );
        $new_status  = $is_accepted ? Constants::INQUIRY_ACCEPTED : Constants::INQUIRY_DECLINED;

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

        if ( $is_accepted ) {
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
     * Withdraw an inquiry (Investor action)
     *
     * @param int $inquiry_id
     * @param int $investor_user_id
     * @return bool|\WP_Error
     */
    public static function withdraw( $inquiry_id, $investor_user_id ) {
        global $wpdb;

        if ( ! Permissions::is_account_active( $investor_user_id ) ) {
            return new \WP_Error( 'account_inactive', __( 'Your account is suspended.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );

        $inquiry = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $inquiry_id )
        );

        if ( ! $inquiry ) {
            return new \WP_Error( 'not_found', __( 'Inquiry not found.', 'cuba-investment-core' ), [ 'status' => 404 ] );
        }

        if ( (int) $inquiry->investor_user_id !== (int) $investor_user_id && ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'unauthorized', __( 'You are not authorized to withdraw this inquiry.', 'cuba-investment-core' ), [ 'status' => 403 ] );
        }

        if ( Constants::INQUIRY_PENDING !== $inquiry->status ) {
            return new \WP_Error( 'invalid_status', __( 'Only pending inquiries can be withdrawn.', 'cuba-investment-core' ), [ 'status' => 400 ] );
        }

        $wpdb->update(
            $table,
            [
                'status'     => Constants::INQUIRY_WITHDRAWN,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => (int) $inquiry_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        Logger::audit( 'inquiry_withdrawn', 'Investor withdrew introduction inquiry', [
            'inquiry_id' => $inquiry_id,
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
    public static function get_for_user( $user_id, $role = 'investor', $limit = 50 ) {
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
                $arr  = $item->to_array();

                // Attach opportunity info
                $opp = get_post( $item->opportunity_id );
                $arr['opportunity_title'] = $opp ? get_the_title( $opp ) : __( 'Investment Opportunity', 'cuba-investment-core' );
                $arr['opportunity_slug']  = $opp ? $opp->post_name : '';
                $arr['opportunity_url']   = $opp ? home_url( '/opportunity/' . $opp->post_name . '/' ) : '#';

                // Attach partner details
                if ( 'business_owner' === $role ) {
                    $inv_user = get_userdata( $item->investor_user_id );
                    $inv_meta = get_user_meta( $item->investor_user_id );
                    $first_n  = $inv_meta['first_name'][0] ?? ( $inv_user ? $inv_user->first_name : '' );
                    $last_n   = $inv_meta['last_name'][0] ?? ( $inv_user ? $inv_user->last_name : '' );
                    $inv_name = trim( "{$first_n} {$last_n}" ) ?: ( $inv_user ? $inv_user->display_name : __( 'Verified Investor', 'cuba-investment-core' ) );

                    $arr['partner_name']     = $inv_name;
                    $arr['partner_type']     = $inv_meta['_cin_investor_type'][0] ?? 'Angel Investor';
                    $arr['partner_country']  = $inv_meta['_cin_country_of_residence'][0] ?? 'International';
                    $arr['partner_avatar']   = $inv_meta['_cin_avatar_url'][0] ?? '';
                } else {
                    $biz_user = get_userdata( $item->business_user_id );
                    $biz_name = get_post_meta( $item->opportunity_id, '_cin_company_name', true );
                    if ( empty( $biz_name ) && $biz_user ) {
                        $biz_name = $biz_user->display_name;
                    }
                    $arr['partner_name'] = $biz_name ?: __( 'Business Founder', 'cuba-investment-core' );
                }

                // If accepted, check for connection and conversation
                if ( Constants::INQUIRY_ACCEPTED === $item->status ) {
                    $t_conn = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
                    $conn_id = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$t_conn} WHERE origin_inquiry_id = %d OR (investor_user_id = %d AND business_user_id = %d)",
                        $item->id,
                        $item->investor_user_id,
                        $item->business_user_id
                    ) );

                    if ( $conn_id ) {
                        $t_conv = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );
                        $convo_id = $wpdb->get_var( $wpdb->prepare(
                            "SELECT id FROM {$t_conv} WHERE connection_id = %d",
                            $conn_id
                        ) );
                        $arr['connection_id']   = (int) $conn_id;
                        $arr['conversation_id'] = $convo_id ? (int) $convo_id : 0;
                    }
                }

                $results[] = $arr;
            }
        }

        return $results;
    }

    /**
     * Alias for investor inquiries
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_investor_inquiries( $user_id, $limit = 50 ) {
        return self::get_for_user( $user_id, 'investor', $limit );
    }

    /**
     * Alias for business owner inquiries
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_business_inquiries( $user_id, $limit = 50 ) {
        return self::get_for_user( $user_id, 'business_owner', $limit );
    }

    /**
     * Get a single inquiry by ID
     *
     * @param int $inquiry_id
     * @return Inquiry|null
     */
    public static function get_inquiry( $inquiry_id ) {
        global $wpdb;

        $inquiry_id = absint( $inquiry_id );
        if ( ! $inquiry_id ) {
            return null;
        }

        $table = Constants::get_table_name( Constants::TABLE_INQUIRIES );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $inquiry_id ) );

        return $row ? new Inquiry( $row ) : null;
    }
}

