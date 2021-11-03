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

    if( window.PaddleCompletedSetup === undefined || !window.PaddleCompletedSetup ) {
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

    if( data.event === 'Checkout.Complete' ) {
        appseroPaddleCheckoutComplete(data.eventData);
    }
}


function appseroPaddleCheckoutComplete(data) {

    const checkoutId = data.checkout.id;

    if( !checkoutId ) {
        return;
    }

    jQuery.ajax({
        url: appseroPaddleAffWP.ajaxUrl,
        type: "POST",
        data: {
            checkout_id: checkoutId,
            action: 'appsero_affwp_paddle_completed'
        },
    });
}
