<?php
/**
 * Cuba Investment Core - Inquiry Model
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Models;

use CubaInvestment\Core\Common\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Inquiry {

    public $id;
    public $opportunity_id;
    public $investor_user_id;
    public $business_user_id;
    public $subject;
    public $message;
    public $capital_range;
    public $status;
    public $admin_notes;
    public $created_at;
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

    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array() {
        return [
            'id'               => (int) $this->id,
            'opportunity_id'   => (int) $this->opportunity_id,
            'investor_user_id' => (int) $this->investor_user_id,
            'business_user_id' => (int) $this->business_user_id,
            'subject'          => $this->subject,
            'message'          => $this->message,
            'capital_range'    => $this->capital_range,
            'status'           => $this->status,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
