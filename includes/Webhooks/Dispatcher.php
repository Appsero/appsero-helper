<?php
namespace Appsero\Helper\Webhooks;

class Dispatcher {

    /**
     * @var Repository
     */
    private $repository;

    public function __construct( Repository $repository = null ) {
        $this->repository = $repository ?: new Repository();
    }

    /**
     * Dispatch an event to all active webhooks listening for it
     *
     * @param string $event
     * @param array  $data
     */
    public function dispatch( $event, $data ) {
        $webhooks = $this->repository->get_active_for_event( $event );

        foreach ( $webhooks as $webhook ) {
            $this->deliver( $webhook, $event, $data );
        }
    }

    /**
     * Deliver a payload to a single webhook URL
     *
     * @param array  $webhook
     * @param string $event
     * @param array  $data
     */
    private function deliver( $webhook, $event, $data ) {
        $payload = wp_json_encode( [
            'event'     => $event,
            'timestamp' => time(),
            'data'      => $data,
        ] );

        $headers = [
            'Content-Type' => 'application/json',
            'X-Api-Key'    => appsero_helper_get_api_key(),
        ];

        if ( ! empty( $webhook['secret'] ) ) {
            $signature = hash_hmac( 'sha256', $payload, $webhook['secret'] );
            $headers['X-Appsero-Signature'] = 'sha256=' . $signature;
        }

        wp_remote_post( $webhook['url'], [
            'body'     => $payload,
            'headers'  => $headers,
            'timeout'  => 5,
            'blocking' => false,
        ] );
    }
}
