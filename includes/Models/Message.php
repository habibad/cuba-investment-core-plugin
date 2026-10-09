<?php
/**
 * Cuba Investment Core - Message and Conversation Model
 *
 * @package CubaInvestment\Core
 */

namespace CubaInvestment\Core\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Message implements \ArrayAccess {

    public $id;
    public $conversation_id;
    public $sender_user_id;
    public $recipient_user_id;
    public $message_body;
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
            'id'                => (int) $this->id,
            'conversation_id'   => (int) $this->conversation_id,
            'sender_user_id'    => (int) $this->sender_user_id,
            'recipient_user_id' => (int) $this->recipient_user_id,
            'message_body'      => esc_html( $this->message_body ),
            'is_read'           => (bool) $this->is_read,
            'read_at'           => $this->read_at,
            'created_at'        => $this->created_at,
        ];
    }

    #[\ReturnTypeWillChange]
    public function offsetExists( $offset ) {
        return property_exists( $this, $offset );
    }

    #[\ReturnTypeWillChange]
    public function offsetGet( $offset ) {
        return property_exists( $this, $offset ) ? $this->$offset : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet( $offset, $value ) {
        if ( property_exists( $this, $offset ) ) {
            $this->$offset = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset( $offset ) {
        if ( property_exists( $this, $offset ) ) {
            $this->$offset = null;
        }
    }
}
