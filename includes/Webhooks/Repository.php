<?php
namespace Appsero\Helper\Webhooks;

class Repository {

    const OPTION_KEY = 'appsero_webhooks';

    /**
     * Get all webhooks
     *
     * @return array
     */
    public function get_all() {
        return get_option( self::OPTION_KEY, [] );
    }

    /**
     * Get active webhooks for a specific event
     *
     * @param string $event
     * @return array
     */
    public function get_active_for_event( $event ) {
        $webhooks = $this->get_all();

        return array_filter( $webhooks, function ( $webhook ) use ( $event ) {
            return ! empty( $webhook['status'] )
                && is_array( $webhook['events'] )
                && in_array( $event, $webhook['events'], true );
        } );
    }

    /**
     * Create a new webhook
     *
     * @param array $data
     * @return string The new webhook ID
     */
    public function create( $data ) {
        $webhooks = $this->get_all();

        $id = uniqid( 'wh_' );

        $webhooks[ $id ] = [
            'id'         => $id,
            'name'       => sanitize_text_field( $data['name'] ),
            'url'        => esc_url_raw( $data['url'] ),
            'secret'     => sanitize_text_field( $data['secret'] ),
            'events'     => array_map( 'sanitize_text_field', $data['events'] ),
            'status'     => 1,
            'created_at' => current_time( 'mysql' ),
        ];

        update_option( self::OPTION_KEY, $webhooks, false );

        return $id;
    }

    /**
     * Delete a webhook by ID
     *
     * @param string $id
     * @return bool
     */
    public function delete( $id ) {
        $webhooks = $this->get_all();

        if ( ! isset( $webhooks[ $id ] ) ) {
            return false;
        }

        unset( $webhooks[ $id ] );

        update_option( self::OPTION_KEY, $webhooks, false );

        return true;
    }
}
