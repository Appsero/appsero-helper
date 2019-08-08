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

    jQuery( 'a.remove-activation-button' ).click( function ( event ) {
        event.preventDefault();
        var aTag = jQuery( this );
        var licenseParent = aTag.parents('.appsero-license');
        var isRemove = confirm("Are you want to remove this activation?");

        var data = {
            action: "appsero_remove_activation",
            activation_id: aTag.data('activationid'),
            source_id: licenseParent.data('sourceid'),
            license_id: licenseParent.data('licenseid'),
            product_id: licenseParent.data('productid'),
        };

        if ( isRemove ) {
            console.log('Yes');
            // TODO: send delete request
            jQuery.ajax({
                method: "POST",
                data: data,
                url: woocommerce_params.ajax_url,
                success: function ( response ) {
                    if ( response.success ) {
                        window.location.reload();
                    }
                }
            });
        }
    });

} );
