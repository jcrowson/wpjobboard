var WPJB = WPJB || {};

WPJB.stripe = {
    
    stripe: null,
    
    card: null,
    
    intent: {
        setup: null,
        payment: null
    },
    
    Cards: [],
    
    loadOnce: function() {
        var $ = jQuery;
        
        if($(".wpjb-script-external-stripe").length > 0) {
            return;
        }
        
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = "https://js.stripe.com/v3/"; 
        script.className = "wpjb-script-external-stripe";
        document.getElementsByTagName("body")[0].appendChild(script);
    },
    
    loadVariables: function( stripe, card ) {
        this.stripe = stripe;
        this.card = card;
    },
    
    error: function(error) {
        var field = null;
        
        switch(error.code) {
            case "invalid_cvc":
            case "incorrect_cvc":
                field = "cvc";
                break;
            case "invalid_expiry_month":
            case "invalid_expiry_year":
            case "expired_card":
                field = "expiration";
                break;
            default:
                field = "card_number";
        }
        
        return field;
    },
    
    response: function(status, response) {
        var $ = jQuery;
        var $form = $('.wpjb-payment-form');

        $form.find(".wpjb-flash-error").remove();
        $form.find(".wpjb-flash-info").remove();

        if (response.error) {
            
            var field = WPJB.stripe.error(response.error);
            var form = new WPJB.form(".wpjb-payment-form");
            
            form.addError(wpjb_payment_lang.form_error);
            form.addFieldError(".wpjb-element-name-"+field, response.error.message);

            $(".wpjb-place-order-wrap .wpjb-place-order").show();
            $(".wpjb-place-order-wrap .wpjb-icon-spinner").css("visibility", "hidden");
            
            return;
        } 

        $form.find("form").append($("<input />").attr("type", "hidden").attr("name", "stripe_token").val(response.id));
            
        WPJB.order.placeOrder(undefined, {context: WPJB.stripe});
    },
    
    charge: function(response) {    
        var $ = jQuery;
        var data = {
            echo: "1",
            action: "wpjb_payment_accept",
            engine: "Stripe",
            id: response.payment_id,
            payment_intent_id: response.payment_intent_id,
            plan_id: response.plan_id,
            //: response.token_id,
            //token_type: response.token_type
        };

        $.ajax({
            url: wpjb_payment_lang.ajaxurl,
            cache: false,
            type: "POST",
            data: data,
            dataType: "json",
            success: function(response) {
                var result = $("#wpjb-checkout-success");
                
                result.find(".wpjb-stripe-pending").hide();
                
                if(response.external_id) {
                    result.find(".wpjb-flash-info").removeClass("wpjb-none");
                    result.find(".wpjb-flash-info .wpjb-flash-body").html(response.message);
                } else {
                    result.find(".wpjb-flash-error").removeClass("wpjb-none");
                    result.find(".wpjb-flash-error .wpjb-flash-body").html(response.message);
                }

            }
        });
        
    },
    
    placeOrder: function(e) {
        
        e.preventDefault();
        var $ = jQuery;
        
        $("#recurring-agree-box").css( "border", "" );
        $("#recurring-agree-box").css( "background-color", "" );
        
        if( $("#recurring-agree").is(':checked') === false && $("#recurring-agree").val() == 5 ) {
            $("#recurring-agree-box").css("border", "1px solid red");
            $("#recurring-agree-box").css("background-color", "#FFF8DC");

            $('html, body').animate({
                scrollTop: $("#recurring-agree-box").offset().top - 50
            }, 100);
            
            return;
        }
        
        // Check if default card is set. 
        var $form = $('.wpjb-payment-form');
        
        $(".wpjb-place-order-wrap .wpjb-place-order").fadeOut("fast");
        $(".wpjb-place-order-wrap .wpjb-icon-spinner").css("visibility", "visible");
        
        var form = new WPJB.form(".wpjb-payment-form");
        var cc = $("#saved_credit_card").val();
        
        form.clearErrors();
        
        WPJB.stripe.intent.setup = $("#card-element").data("setup-intent");
        WPJB.stripe.intent.payment = $("#card-element").data("secret");
        
        WPJB.order.placeOrder(undefined, {context: WPJB.stripe});
        
    },
    
    placeOrderSuccess: function(response) {

        if( jQuery("#is_recurring").val() ) {
            this.placeOrderRecurring();
            return;
        } else {
            this.placeOrderSingle();
        }
    },
    
    placeOrderRecurring( ) {

        var card = jQuery("input[name='_stripe_card']:checked");
        var card_id = null;
        
        if( card.length > 0 ) {
            card_id = card.val();
        }

        if( card_id ) {
            WPJB.stripe.createSubscription( card_id, 0 ); 
            return;
        }
        
        var $ = jQuery;
        var $form = $('.wpjb-payment-form');
        
        this.stripe.confirmCardSetup(
            this.intent.setup,
            {
                payment_method: {
                    type: 'card',
                    card: this.card,
                    billing_details: {
                        name: $form.find("#fullname").val(),
                        email: $form.find("#email").val()
                    }
                }
            }
        ).then(WPJB.stripe.cardSetupConfirmed);
    },
    
    placeOrderSingle() {
        
        var $ = jQuery;
        var $form = $('.wpjb-payment-form');
        
        
        var card = jQuery("input[name='_stripe_card']:checked");
        var card_id = null;
        
        if( card.length > 0 ) {
            card_id = card.val();
        }
        
        if( $("#card-element").data("newcard") == "0" && card_id ) {
             // Saved Card
            this.stripe.handleCardPayment(
                jQuery("#card-element").data("secret"),
                {
                    payment_method: card_id,
                    receipt_email: $form.find("#email").val()
                }
            ).then(WPJB.stripe.placeOrderHandle); //.than( WPJB.stripe.tryCreateSubscription );
        } else {
            this.stripe.handleCardPayment(
                jQuery( "#card-element" ).data("secret"),
                this.card,
                {
                    payment_method_data: {
                        billing_details: {   
                            name: $form.find("#fullname").val(),
                            email: $form.find("#email").val()
                            //phone: '',
                            //address: ''
                        }
                    },
                    receipt_email: $form.find("#email").val()
                }
            ).then(WPJB.stripe.placeOrderHandle); //.than( WPJB.stripe.tryCreateSubscription );
        }
    },
    
    cardSetupConfirmed: function( result ) {
        WPJB.stripe.createSubscription(result.setupIntent.payment_method, result.setupIntent.id);
    },
    
    createCardToken: function( status, response ) {
        
        var $ = jQuery;
        var $form = $('.wpjb-payment-form');

        $form.find(".wpjb-flash-error").remove();
        $form.find(".wpjb-flash-info").remove();

        if (response.error) {
            
            var field = WPJB.stripe.error(response.error);
            var form = new WPJB.form(".wpjb-payment-form");
            
            form.addError(wpjb_payment_lang.form_error);
            form.addFieldError(".wpjb-element-name-"+field, response.error.message);

            $(".wpjb-place-order-wrap .wpjb-place-order").show();
            $(".wpjb-place-order-wrap .wpjb-icon-spinner").css("visibility", "hidden");
            
            return;
        } 

        jQuery.proxy( WPJB.stripe.createSubscription, response.id );
    },
    
    createSubscription: function( token, setup_intent ) {

        var data = {
            action: 'wpjb_stripe_createSubscription',
            plan_id: jQuery("#plan_id").val(),
            payment_id: jQuery("#wpjb-stripe-payment-id").val(),
            setup_intent: setup_intent,
            token: token
        };

        jQuery.ajax({
            url: wpjb_payment_lang.ajaxurl,
            data: data,
            dataType: 'json',
            type: 'post',
            success: function( response ) {

                var result = jQuery("#wpjb-checkout-success");
                
                result.find(".wpjb-stripe-pending").hide();
                
                if(response.external_id) {
                    result.find(".wpjb-flash-info").removeClass("wpjb-none");
                    result.find(".wpjb-flash-info .wpjb-flash-body").html(response.message);
                } else {
                    result.find(".wpjb-flash-error").removeClass("wpjb-none");
                    result.find(".wpjb-flash-error .wpjb-flash-body").html(response.message);
                }
                
            }
        });
        
    },
    
    placeOrderHandle: function( result ) {
        if(result.error) {

            var ckout = jQuery("#wpjb-checkout-success");

            ckout.find(".wpjb-stripe-pending").hide();
            ckout.find(".wpjb-flash-error").removeClass("wpjb-none");
            ckout.find(".wpjb-flash-error .wpjb-flash-body").html(result.error.message);
            return;
        }

        var charge = jQuery.extend(result, {
            payment_intent_id: result.paymentIntent.id,
            payment_id: jQuery("#wpjb-stripe-payment-id").val()
        });

        WPJB.stripe.charge(charge); 
    }
}


WPJB.stripe.Card = function( item ) {
    this.item = item;

    this.radio = this.item.find("input[type=radio]");
    this.loader = this.item.find(".wpjb-stripe-cc-actions-loader");
    
    this.card_actions = this.item.find(".wpjb-stripe-cc-actions");

    this.card_default = this.item.find(".wpjb-stripe-cc-actions-default");
    this.card_trash = this.item.find(".wpjb-stripe-cc-actions-trash");
    
    this.card_trash_confirm = this.item.find(".wpjb-stripe-cc-actions-trash-confirm");
    this.card_trash_confirm_yes = this.item.find(".wpjb-stripe-cc-actions-trash-confirm-yes");
    this.card_trash_confirm_no = this.item.find(".wpjb-stripe-cc-actions-trash-confirm-no");
    
    
    this.card_trash.click(jQuery.proxy(this.card_trash_click, this));
    this.card_trash_confirm_yes.click(jQuery.proxy(this.card_trash_confirm_yes_click, this));
    this.card_trash_confirm_no.click(jQuery.proxy(this.card_trash_confirm_no_click, this));
    
    this.card_default.click(jQuery.proxy(this.card_default_click, this));
    
    this.card_trash_confirm.hide();
    this.loader.hide();
};

WPJB.stripe.Card.prototype.card_trash_click = function(e) {
    if(typeof e !== 'undefined') {
        e.preventDefault();
    }
    this.card_actions.hide();
    this.card_trash_confirm.show();
    this.loader.hide();
};

WPJB.stripe.Card.prototype.card_trash_confirm_no_click = function(e) {
    if(typeof e !== 'undefined') {
        e.preventDefault();
    }
    this.card_actions.show();
    this.card_trash_confirm.hide();
    this.loader.hide();
};

WPJB.stripe.Card.prototype.card_trash_confirm_yes_click = function(e) {
    if(typeof e !== 'undefined') {
        e.preventDefault();
    }
    
    this.card_trash_confirm.hide();
    this.loader.show();
    
    var data = {
        action: 'wpjb_stripe_trash',
        nonce: '_wpadverts_stripe_cc_nonce',
        card_id: this.radio.attr("value")
    };
    
    jQuery.ajax({
        url: wpjb_payment_lang.ajaxurl,
        data: data,
        dataType: 'json',
        type: 'post',
        success: jQuery.proxy(this.card_trash_ajax_success, this),
        error: jQuery.proxy(this.card_trash_ajax_error, this)
    });
};

WPJB.stripe.Card.prototype.card_default_click = function(e) {
    if(typeof e !== 'undefined') {
        e.preventDefault();
    }
    
    this.card_actions.hide();
    this.loader.show();
    
    var data = {
        action: 'wpjb_stripe_source',
        nonce: '_wpadverts_stripe_cc_nonce',
        card_id: this.radio.attr("value")
    };
    
    jQuery.ajax({
        url: wpjb_payment_lang.ajaxurl,
        data: data,
        dataType: 'json',
        type: 'post',
        success: jQuery.proxy(this.card_default_success, this),
        error: jQuery.proxy(this.ajax_error, this)
    });
};

WPJB.stripe.Card.prototype.card_default_success = function(response) {
    
    this.card_actions.show();
    this.loader.hide();
    
    if(typeof response !== 'object') {
        this.ajax_error(response);
        return;
    }
    
    if(response.result != 1) {
        alert(response.message)
        return;
    }
    
    var card_id = this.radio.attr("value");
    
    jQuery.each(WPJB.stripe.Cards, function(index, item) {
        item.toggle_default( card_id );
    });
};

WPJB.stripe.Card.prototype.card_trash_ajax_success = function(response) {
    
    this.card_trash_confirm_no_click();
    
    if(typeof response !== 'object') {
        this.ajax_error(response);
        return;
    }
    
    if(response.result != 1) {
        alert(response.message)
        return;
    }
    
    this.item.fadeOut("fast").remove();
};

WPJB.stripe.Card.prototype.ajax_error = function(response) {
    
};

WPJB.stripe.Card.prototype.toggle_default = function(card_id) {
    var card = this.radio.attr("value");
    if(card == card_id) {
        this.item.addClass("wpjb-card-is-default");
    } else {
        this.item.removeClass("wpjb-card-is-default");
    }
};


jQuery(function($) {
    
    
    function waitForElementToDisplay(selector, time) {
        if(document.querySelector(selector)!=null) {
            
            var payment_id = $("#wpjb-stripe-payment-id").val();
            //var payment_id = $("#payment_id").val();
            //var payment_intent = $("#payment_intent_id").val();

            stripe.redirectToCheckout({
                items: [{
                    // Define the product and plan in the Dashboard first, and use the plan
                    // ID in your client-side code.       
                    plan: $("#stripe_plan_id").val(),
                    quantity: 1
                }],
                clientReferenceId: payment_id,
                successUrl: wpjb_payment_lang.success_page, // + "&id="+payment_id+"&engine=Stripe&echo=1", //&payment_intent_id="+payment_intent,
                cancelUrl: wpjb_payment_lang.fail_page
            });
            
        }
        else {
            setTimeout(function() {
                waitForElementToDisplay(selector, time);
            }, time);
        }
    }
    
    
    $(".wpjb-place-order").unbind("click").bind("click", WPJB.stripe.placeOrder);
    
    $(".wpjb-add-credit-card").click(function(e) {
        e.preventDefault();
        $(".wpjb-card-details").show();
        $("#card-errors").hide();
        $(".wpjb-add-credit-card-cancel").show();
        
        $(".wpjb-add-credit-card").hide();
        $(".wpjb-credit-card-list").hide();
        
        $("#card-element").data("newcard", "1");
    });
    
    $(".wpjb-add-credit-card-cancel").click(function(e) {
        e.preventDefault();
        $(".wpjb-card-details").hide();
        $("#card-errors").hide();
        $(".wpjb-add-credit-card-cancel").hide();
        
        $(".wpjb-add-credit-card").show();
        $(".wpjb-credit-card-list").show();
        
        $("#card-element").data("newcard", "0");
    });


    // Create a Stripe client.
    var stripe_publisher_key = $("#stripe_publishable_key").val();
    var stripe = Stripe( stripe_publisher_key );
    
    $(".wpjb-credit-card-single").each(function(index, item) {
        WPJB.stripe.Cards.push( new WPJB.stripe.Card( $(item) ) );
    });
    
    /*if ( $( "#checkout-button" ).length ) {
        
        $(".wpjb-place-order").hide();
        $(".wpjb-fieldset-default").hide();
        
        $('#checkout-button').on('click', function (e) {
            e.preventDefault();
            
            if( $("#recurring-agree").is(":checked") ) {
                $("#recurring-agree-box").hide();
                WPJB.order.placeOrder(undefined, {context: WPJB.stripe}); 
                waitForElementToDisplay( "#wpjb-stripe-payment-id", 500 );
            } else {
                $("#recurring-agree-box").hide();
                $("#recurring-agree-box").css("border", "1px solid #DE5400").css("background-color", "#f04124").css("color", "#ffffff");
                $("#recurring-agree-box").fadeIn();
                $([document.documentElement, document.body]).animate({
                    scrollTop: $("#recurring-agree-box").offset().top
                }, 400);
                
            } 
        });
    } else { */

        // Create an instance of Elements.
        var elements = stripe.elements();

        var style = {
            base: {
              // Add your base input styles here. For example:
              fontSize: '16px',
              color: "#32325d",
            }
        };

        // Create an instance of the card Element.
        var card = elements.create('card', {style: style});

        // Add an instance of the card Element into the `card-element` <div>.
        card.mount('#card-element');

        WPJB.stripe.loadVariables(stripe, card);

        card.addEventListener('change', function(event) {
            var displayError = $(".wpjb-stripe-result").find(".wpjb-flash-error");
            if (event.error) {
                displayError.find(".wpjb-flash-body").text = event.error.message;
                displayError.show();
            } else {
                displayError.find(".wpjb-flash-body").text = '';
                displayError.hide();
            }
        });
    //}
});
