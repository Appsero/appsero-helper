<?php

namespace Appsero\Helper\Common;

class Filter_Hook {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );
    }

    /**
     * Show label beside page title on admin pages
     */
    public function display_post_states( $post_states, $post ) {
        $stat = get_post_meta( $post->ID, 'appsero_post_state', true );

        if ( $stat ) {
            $post_states[] = $stat;
        }

        return $post_states;
    }
}
