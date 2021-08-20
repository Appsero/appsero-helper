function appseroSetupPaddle() {
    Paddle.Setup({
        vendor: appseroPaddleAffWP.vendor_id,
        eventCallback: function(data) {
            appseroPaddleEvent(data);
        },
    });
}

jQuery( document ).ready( function() {
    appseroSetupAffiliatePaddle();
});

function appseroSetupAffiliatePaddle() {
    if( window.Paddle === undefined || !window.Paddle ) {
        return;
    }

    if( PaddleCompletedSetup === undefined || !PaddleCompletedSetup ) {
        appseroSetupPaddle();
        return;
    }

    Paddle.Options({
        eventCallback: function(data) {
            appseroPaddleEvent(data);
        }
    });
}

function appseroPaddleEvent(data) {
    console.log( data );

    if( data.event === 'Checkout.Complete' ) {
        appseroPaddleCheckoutComplete(data.eventData);
    }
}


function appseroPaddleCheckoutComplete(data) {

    const checkoutId = data.checkout.id;

    if( !checkoutId ) {
        return;
    }

    Paddle.Order.details(checkoutId, function(data) {
        // Order data, downloads, receipts etc... available within 'data' variable.
        console.log(data);
    });

    jQuery.ajax({
        url: appseroPaddleAffWP.ajaxUrl,
        type: "POST",
        data: {
            checkout_id: checkoutId,
            action: 'appsero_affwp_paddle_completed'
        },
    })/*.always(function() {
        window.location.href = appseroFastSpringAffwp.thankYouPage;
    })*/;
}
