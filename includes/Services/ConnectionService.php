<?php
/**
 * Cuba Investment Core - Direct Connection Service
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Services;

use CubaInvestment\Core\Common\Constants;
use CubaInvestment\Core\Common\Logger;
use CubaInvestment\Core\Models\Connection;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConnectionService {

    /**
     * Establish or reactivate a direct connection between an investor and business owner
     *
     * @param int $investor_id
     * @param int $business_id
     * @param int $origin_inquiry_id
     * @param int $opportunity_id
     * @return int|\WP_Error Connection ID or WP_Error
     */
    public static function establish_connection( $investor_id, $business_id, $origin_inquiry_id = 0, $opportunity_id = 0 ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_CONNECTIONS );

        // Check if connection already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status FROM {$table} WHERE investor_user_id = %d AND business_user_id = %d",
                $investor_id,
                $business_id
            )
        );

        $now = current_time( 'mysql' );

        if ( $existing ) {
            if ( $existing->status !== Constants::CONNECTION_ACTIVE ) {
                $wpdb->update(
                    $table,
                    [
                        'status'            => Constants::CONNECTION_ACTIVE,
                        'origin_inquiry_id' => $origin_inquiry_id ?: (int) $existing->id,
                        'opportunity_id'    => $opportunity_id ?: 0,
                        'updated_at'        => $now,
                    ],
                    [ 'id' => (int) $existing->id ],
                    [ '%s', '%d', '%d', '%s' ],
                    [ '%d' ]
                );
            }
            return (int) $existing->id;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'investor_user_id'  => (int) $investor_id,
                'business_user_id'  => (int) $business_id,
                'origin_inquiry_id' => (int) $origin_inquiry_id,
                'opportunity_id'    => (int) $opportunity_id,
                'status'            => Constants::CONNECTION_ACTIVE,
                'connected_at'      => $now,
                'updated_at'        => $now,
            ],
            [ '%d', '%d', '%d', '%d', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            Logger::error( 'Failed to establish connection', [ 'investor_id' => $investor_id, 'business_id' => $business_id ] );
            return new \WP_Error( 'db_error', __( 'Could not establish connection.', 'cuba-investment-core' ) );
        }

        $connection_id = $wpdb->insert_id;

        Logger::audit( 'connection_established', 'Direct connection created between investor and business owner', [
            'connection_id' => $connection_id,
            'investor_id'   => $investor_id,
            'business_id'   => $business_id,
        ] );

        return $connection_id;
    }

    /**
     * Get active connections for a user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_connections_for_user( $user_id ) {
        global $wpdb;

        $table = Constants::get_table_name( Constants::TABLE_CONNECTIONS );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE (investor_user_id = %d OR business_user_id = %d)
                   AND status = %s
                 ORDER BY updated_at DESC",
                $user_id,
                $user_id,
                Constants::CONNECTION_ACTIVE
            )
        );

        $results = [];
        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $conn = new Connection( $row );
                $arr = $conn->to_array();

                // Attach partner user info
                $is_inv     = ( (int) $conn->investor_user_id === (int) $user_id );
                $partner_id = $is_inv ? (int) $conn->business_user_id : (int) $conn->investor_user_id;
                $partner    = get_userdata( $partner_id );
                $part_meta  = get_user_meta( $partner_id );

                $first_n = $part_meta['first_name'][0] ?? ( $partner ? $partner->first_name : '' );
                $last_n  = $part_meta['last_name'][0] ?? ( $partner ? $partner->last_name : '' );
                $full_n  = trim( "{$first_n} {$last_n}" ) ?: ( $partner ? $partner->display_name : __( 'Network Member', 'cuba-investment-core' ) );

                $arr['partner_id']       = $partner_id;
                $arr['partner_name']     = $full_n;
                $arr['partner_role']     = $is_inv ? __( 'Business Owner', 'cuba-investment-core' ) : __( 'Investor', 'cuba-investment-core' );
                $arr['partner_avatar']   = $part_meta['_cin_avatar_url'][0] ?? '';
                $arr['connected_date']   = date_i18n( 'F j, Y', strtotime( $conn->connected_at ) );

                if ( $is_inv ) {
                    // Current user is investor, partner is business owner
                    $biz_name = get_post_meta( $conn->opportunity_id, '_cin_company_name', true );
                    if ( empty( $biz_name ) ) {
                        $biz_name = $part_meta['_cin_company_legal_name'][0] ?? ( $part_meta['_cin_company_name'][0] ?? '' );
                    }
                    $arr['partner_business'] = $biz_name ?: __( 'Cuban Enterprise', 'cuba-investment-core' );
                    $arr['partner_location'] = $part_meta['_cin_company_city'][0] ?? 'Cuba';
                } else {
                    // Current user is business owner, partner is investor
                    $arr['partner_business'] = $part_meta['_cin_investor_type'][0] ?? __( 'Private Investor', 'cuba-investment-core' );
                    $arr['partner_location'] = $part_meta['_cin_country_of_residence'][0] ?? 'International';
                }

                // Related Opportunity
                $opp = get_post( $conn->opportunity_id );
                $arr['opportunity_title'] = $opp ? get_the_title( $opp ) : __( 'General Introduction', 'cuba-investment-core' );
                $arr['opportunity_url']   = $opp ? home_url( '/opportunity/' . $opp->post_name . '/' ) : '#';

                // Conversation link
                $t_conv = Constants::get_table_name( Constants::TABLE_CONVERSATIONS );
                $convo_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$t_conv} WHERE connection_id = %d",
                    $conn->id
                ) );

                if ( ! $convo_id ) {
                    // Lazy initialize conversation if not already created
                    $convo_id = MessagingService::get_or_create_conversation( $conn->id, (int) $conn->investor_user_id, (int) $conn->business_user_id );
                }

                $arr['conversation_id'] = (int) $convo_id;

                $results[] = $arr;
            }
        }

        return $results;
    }

    /**
     * Alias for investor connections
     *
     * @param int $user_id
     * @return array
     */
    public static function get_investor_connections( $user_id ) {
        return self::get_connections_for_user( $user_id );
    }

    /**
     * Alias for business connections
     *
     * @param int $user_id
     * @return array
     */
    public static function get_business_connections( $user_id ) {
        return self::get_connections_for_user( $user_id );
    }

    /**
     * Get connection by origin inquiry ID
     *
     * @param int $inquiry_id
     * @return Connection|null
     */
    public static function get_connection_by_inquiry( $inquiry_id ) {
        global $wpdb;

        $inquiry_id = absint( $inquiry_id );
        if ( ! $inquiry_id ) {
            return null;
        }

        $table = Constants::get_table_name( Constants::TABLE_CONNECTIONS );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE origin_inquiry_id = %d", $inquiry_id ) );

        return $row ? new Connection( $row ) : null;
    }

    /**
     * Get active connections count for a user
     *
     * @param int $user_id
     * @return int
     */
    public static function get_connections_count( $user_id ) {
        global $wpdb;

        $user_id = absint( $user_id );
        if ( ! $user_id ) {
            return 0;
        }

        $table = Constants::get_table_name( Constants::TABLE_CONNECTIONS );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE (investor_user_id = %d OR business_user_id = %d)
                   AND status = %s",
                $user_id,
                $user_id,
                Constants::CONNECTION_ACTIVE
            )
        );
    }
}

