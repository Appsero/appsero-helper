jQuery( document ).ready( function () {

    jQuery( '.license-toggle-info' ).click( function () {
        var parent = jQuery( this ).parents( '.appsero-license' );
        var showingInfo = parent.data('showing');

        if ( showingInfo == 1 ) {
            parent.find( '.license-key-activations' ).hide();
            jQuery( this ).removeClass( 'license-button-toggled' );
            parent.data( 'showing', 0 );
        } else {
            parent.find( '.license-key-activations' ).show();
            jQuery( this ).addClass( 'license-button-toggled' );
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
            product_id: licenseParent.data('productid'),
        };

        if ( isRemove ) {
            jQuery.ajax({
                method: "POST",
                data: data,
                url: appseroHelper.ajaxUrl,
                success: function ( response ) {
                    if ( response.success ) {
                        aTag.parents('.appsero-activation-item').hide();
                        alert("Site has been removed.");
                    } else {
                        alert("Unable to remove site.");
                    }
                },
                error: function ( xhr, status, errors ) {
                    alert("Unable to remove site.");
                }
            });
        }
    });

} );
