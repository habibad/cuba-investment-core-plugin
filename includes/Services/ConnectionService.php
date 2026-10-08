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
                $partner_id = ( (int) $conn->investor_user_id === (int) $user_id ) ? (int) $conn->business_user_id : (int) $conn->investor_user_id;
                $partner = get_userdata( $partner_id );
                $arr['partner_id']   = $partner_id;
                $arr['partner_name'] = $partner ? $partner->display_name : __( 'Member', 'cuba-investment-core' );

                $results[] = $arr;
            }
        }

        return $results;
    }
}
