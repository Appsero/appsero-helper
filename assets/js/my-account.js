jQuery( document ).ready( function () {

    jQuery( '.license-toggle-info' ).click( function () {
        var parent = jQuery( this ).parents( '.appsero-license' );
        var showingInfo = parent.data('showing');

        if ( showingInfo == 1 ) {
            parent.find( '.license-key-activations' ).hide();
            jQuery( this ).children( 'i.fas' ).removeClass( 'fa-angle-up' ).addClass( 'fa-angle-down' );
            parent.data( 'showing', 0 );
        } else {
            parent.find( '.license-key-activations' ).show();
            jQuery( this ).children( 'i.fas' ).removeClass( 'fa-angle-down' ).addClass( 'fa-angle-up' );
            parent.data( 'showing', 1 );
        }
    } );

} );
