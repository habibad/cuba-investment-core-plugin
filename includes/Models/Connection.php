<?php
/**
 * Cuba Investment Core - Connection Model
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Connection {

    public $id;
    public $investor_user_id;
    public $business_user_id;
    public $origin_inquiry_id;
    public $opportunity_id;
    public $status;
    public $connected_at;
    public $updated_at;

    public function __construct( $data = [] ) {
        if ( is_object( $data ) ) {
            $data = (array) $data;
        }

        if ( ! empty( $data ) && is_array( $data ) ) {
            foreach ( $data as $key => $val ) {
                if ( property_exists( $this, $key ) ) {
                    $this->$key = $val;
                }
            }
        }
    }

    public function to_array() {
        return [
            'id'                => (int) $this->id,
            'investor_user_id'  => (int) $this->investor_user_id,
            'business_user_id'  => (int) $this->business_user_id,
            'origin_inquiry_id' => (int) $this->origin_inquiry_id,
            'opportunity_id'    => (int) $this->opportunity_id,
            'status'            => $this->status,
            'connected_at'      => $this->connected_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
