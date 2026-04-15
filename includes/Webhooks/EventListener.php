<?php
namespace Appsero\Helper\Webhooks;

use Appsero\Helper\Traits\Hooker;

class EventListener {

    use Hooker;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct() {
        $this->dispatcher = new Dispatcher();

        $this->action( 'appsero_user_created', 'on_user_created', 10, 2 );
    }

    /**
     * Handle user created event
     *
     * @param int   $user_id
     * @param array $userdata
     */
    public function on_user_created( $user_id, $userdata ) {
        $this->dispatcher->dispatch( 'user.created', [
            'user_id'    => $user_id,
            'email'      => $userdata['user_email'],
            'first_name' => $userdata['first_name'],
            'last_name'  => $userdata['last_name'],
        ] );
    }
}
