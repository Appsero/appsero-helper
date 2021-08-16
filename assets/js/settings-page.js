jQuery( function() {
    jQuery('select[name="selling_plugin"]').change(function( event ) {
        if ( 'fastspring' === event.target.value ) {
            jQuery('.appsero-paddle-fields').addClass('display-none');
            jQuery('.appsero-fastspring-fields').removeClass('display-none');
            jQuery('.appsero-sp-name').text('Fastspring');
        } else if ( 'paddle' === event.target.value ) {
            jQuery('.appsero-fastspring-fields').addClass('display-none');
            jQuery('.appsero-paddle-fields').removeClass('display-none');
            jQuery('.appsero-sp-name').text('Paddle');
        } else {
            jQuery('.appsero-fastspring-fields').addClass('display-none');
            jQuery('.appsero-paddle-fields').addClass('display-none');
        }
    });

    jQuery('input[name="redirect_purchases"]').change(function( event ) {
        if ( event.target.checked ) {
            jQuery('.redirect-purchases-fields').removeClass('display-none');
        } else {
            jQuery('.redirect-purchases-fields').addClass('display-none');
        }
    });

    jQuery('input[name="enable_affiliates"]').change(function( event ) {
        if ( event.target.checked ) {
            jQuery('.affiliate-wp-fields').removeClass('display-none');
        } else {
            jQuery('.affiliate-wp-fields').addClass('display-none');
        }
    });

    jQuery('button[name=local_submit]').click(function( event ) {
        event.preventDefault();
        jQuery('#appsero-local-error').show();
        return false;
    });

    jQuery('.appsero-modal-close').click(function() {
        jQuery('#appsero-local-error').hide();
    });

    jQuery('.appsero-modal').click(function() {
        jQuery('#appsero-local-error').hide();
    });

    jQuery('.appsero-modal-content').click(function( event ) {
        event.stopPropagation();
    });

} );
