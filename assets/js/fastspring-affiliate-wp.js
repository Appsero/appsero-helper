function appseroFastSpringPopupClosed( order ) {
    console.log('popup closed');
    if ( ! order ) {
        return;
    }

    if ( ! order.id ) {
        return;
    }

    jQuery.ajax({
        url: appseroFastSpringAffwp.ajaxUrl,
        type: "POST",
        data: {
            id: order.id,
            action: 'appsero_affwp_fastspring_completed'
        },
    }).always(function() {
        window.location.href = appseroFastSpringAffwp.thankYouPage;
    });
}
