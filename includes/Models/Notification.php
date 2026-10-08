<?php
/**
 * Cuba Investment Core - Notification Model
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Notification {

    public $id;
    public $user_id;
    public $type;
    public $title;
    public $content;
    public $action_url;
    public $is_read;
    public $read_at;
    public $created_at;

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
            'id'         => (int) $this->id,
            'user_id'    => (int) $this->user_id,
            'type'       => $this->type,
            'title'      => esc_html( $this->title ),
            'content'    => esc_html( $this->content ),
            'action_url' => esc_url( $this->action_url ),
            'is_read'    => (bool) $this->is_read,
            'read_at'    => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
