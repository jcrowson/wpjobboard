<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Stripe
 *
 * @author Grzegorz
 */
class Wpjb_Form_Payment_Stripe extends Daq_Form_Abstract 
{

    public function __construct($options = array()) 
    {
        $request = Daq_Request::getInstance();
        
        if(DOING_AJAX && $request->post("action") == "wpjb_payment_render") {
            add_filter("wpjb_payment_render_response", array($this, "script"));
        }
        
        parent::__construct($options);
    }
    
    public function script($response) 
    {
        $response["load"] = array();
        
        //$scripts = wp_scripts()->registered["wpjb-stripe-main"];
        //$response["load"][] = $scripts->src."?time=".time();
        $scripts = wp_scripts()->registered["wpjb-stripe"];
        $response["load"][] = $scripts->src."?time=".time();
        
        return $response;
    }
    
    public function init() 
    {  
        $stripe = new Wpjb_Payment_Stripe;
        
        $e = $this->create("stripe_publishable_key", "hidden");
        $e->setValue($stripe->conf("publishable_key"));
        $this->addElement($e, "_internal");
             
        $e = $this->create("fullname");
        $e->setLabel(__("Full Name", "wpjobboard"));
        $e->setRequired(true);
        $this->addElement($e, "default");
        
        $e = $this->create("email");
        $e->setLabel(__("Email", "wpjobboard"));
        $e->setRequired(true);
        $this->addElement($e, "default");
        
        $this->addGroup("stripe", __("Credit Card", "wpjobboard"));
        
        $id = get_user_meta(get_current_user_id(), "_wpjb_stripe_customer_id", true);
        $cards = array();
        
        if($id) {
            if(!class_exists("Stripe")) {
                //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
                include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
            }
            $stripe = new Wpjb_Payment_Stripe();
            \Stripe\Stripe::setApiKey($stripe->conf("secret_key"));
            $customer = \Stripe\Customer::retrieve($id);
            foreach($customer->sources->data as $cc) {
                $cards[] = array(
                    "id" => $cc->id,
                    "desc" => sprintf("%s ****-****-****-%s (%s/%s)", $cc->brand, $cc->last4, $cc->exp_month, $cc->exp_year)
                );
            }
        }
        
        $defaults = Daq_Request::getInstance()->post("defaults");
        if( !isset( $defaults['payment_hash'] ) ) {
            $pricing = new Wpjb_Model_Pricing( $defaults['pricing_id'] );
        }
        
        $e = $this->create( "is_recurring", "hidden" );
        if( !isset( $pricing ) || $pricing->meta->is_recurring->value() != 1 ) {
            $e->setValue( 0 );
        } else {
            $e->setValue( 1 );
        }
        $this->addElement($e, "stripe");
        
        if( isset( $pricing ) && $pricing->meta->is_recurring->value() == 1 ) {
            
            $slug = sanitize_title($pricing->title);
            $slug = preg_replace("([^A-z0-9\-]+)", "", $slug);
            
            $e = $this->create( "plan_id", "hidden" );
            $e->setValue( $slug );
            $this->addElement($e, "stripe");
        }
        
        $e = $this->create("card_number");
        if( !isset( $pricing ) || $pricing->meta->is_recurring->value() != 1 ) {
            $e->setLabel(__("Card Number", "wpjobboard"));
        } else {
            $e->setLabel(__("Card", "wpjobboard"));
        }
        $e->addClass("wpjb-stripe-cc");
        $e->setAttr("data-stripe", "number");
        $e->setRenderer(array($this, "inputStripe"));
        $this->addElement($e, "stripe");
        
        apply_filters("wpjb_form_init_payment_stripe", $this);
    }
    
    public function inputStripe($input) 
    {
        
        $defaults = Daq_Request::getInstance()->post("defaults");
        if( isset( $defaults['payment_hash'] ) ) {
            $payment = Wpjb_Model_Payment::getFromHash( $defaults['payment_hash'] );
            $cArr = Wpjb_List_Currency::getByCode( $payment->payment_currency );
            $amount = ( $payment->payment_sum * pow( 10, $cArr["decimal"] ) - $payment->payment_paid * pow( 10, $cArr["decimal"] ) );
        } else {
            $pricing = new Wpjb_Model_Pricing( $defaults['pricing_id'] );
            $cArr = Wpjb_List_Currency::getByCode( $pricing->currency );
            $amount = ( $pricing->price * pow( 10, $cArr["decimal"] ) );
        }

        $stripe_customer = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true ); 
        $stripe_default_card = "";
        $cards = array();

        if(!class_exists("Stripe")) {
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }

        $stripe = new Wpjb_Payment_Stripe();
        \Stripe\Stripe::setApiKey($stripe->conf("secret_key"));

        if( get_current_user_id() && $stripe_customer ) {
            $cards = \Stripe\PaymentMethod::all(["customer" => $stripe_customer, "type" => "card"]);
            $customer = \Stripe\Customer::retrieve($stripe_customer);

            if( isset( $customer->invoice_settings->default_payment_method ) && ! empty( $customer->invoice_settings->default_payment_method ) ) {
                $stripe_default_card = $customer->invoice_settings->default_payment_method;
            } else if( $customer->default_source ) {
                $stripe_default_card = $customer->default_source;
            }
        }


        $intent = $this->getPaymentIntent();

        if( $stripe_default_card === "" && isset( $cards->data[0] ) ) {
            $stripe_default_card = $cards->data[0]->id;
        }

        $this->creditCardField($cards, $intent, $stripe_default_card);
    }
    
    public function getPaymentIntent( ) {
        
        $defaults = Daq_Request::getInstance()->post("defaults");
        if( isset( $defaults['payment_hash'] ) ) {
            $payment = Wpjb_Model_Payment::getFromHash( $defaults['payment_hash'] );
            $pricing = new Wpjb_Model_Pricing($payment->pricing_id);
            $cArr = Wpjb_List_Currency::getByCode( $payment->payment_currency );
            $currency = strtolower( $cArr['code'] );
            //$amount = ( $payment->payment_sum * pow( 10, $cArr["decimal"] ) - $payment->payment_paid * pow( 10, $cArr["decimal"] ) );
        } else {
            $payment = null;
            $pricing = new Wpjb_Model_Pricing( $defaults['pricing_id'] );
            $cArr = Wpjb_List_Currency::getByCode( $pricing->currency );
            $currency = strtolower( $cArr['code'] );
            

        }
        
        $discount = Daq_Request::getInstance()->post("discount");

        if($discount) {
            try {
                $pricing->applyCoupon($discount);
            } catch(Wpjb_Model_PricingException $e) {
                // do nothing
            }
        }

        $taxer = new Wpjb_Utility_Taxer();
        $taxer->setPrice($pricing->getPrice());
        $taxer->setDiscount($pricing->getDiscount());

        $amount = ( $taxer->value->total * pow( 10, $cArr["decimal"] ) );
        
        if( $payment && $payment->meta->stripe_payment_intent_id && $payment->meta->stripe_payment_intent_id->value() ) {
            $pi = \Stripe\PaymentIntent::retrieve( $payment->meta->stripe_payment_intent_id->value() );

            if( $pi->amount !== $amount ) {
                $pi = \Stripe\PaymentIntent::update( $pi->id, array( "amount" => $amount ) );
            }
            
            return $pi;
        }
        
        $stripe_customer = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true ); 
        
        $intent_array = array(
            'amount' => $amount,
            'currency' => $currency,
            'setup_future_usage' => 'off_session'
        );
        
        if( $stripe_customer ) {
            $intent_array["customer"] = $stripe_customer;
        }   

        $intent = \Stripe\PaymentIntent::create( apply_filters( "wpjb_stripe_data_single_payment", $intent_array, $payment, $defaults ) );

        if( $payment ) {
            Wpjb_Model_MetaValue::import( "payment", "stripe_payment_intent_id", $intent->id, $payment->id );
        }
   
        return $intent;
    }
    
    public function getSetupIntent() {
        
        $stripe_customer = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true ); 
        
        $intent_array = array(
            'usage' => 'off_session'
        );
        
        if( $stripe_customer ) {
            $intent_array["customer"] = $stripe_customer;
        }   

        
        $intent = \Stripe\SetupIntent::create( apply_filters( "wpjb_stripe_data_recurring_payment", $intent_array ) );
        
        return $intent;
    }
    
    public function lockCheckbox() {
        
        $defaults = Daq_Request::getInstance()->post("defaults");
        if( ! isset( $defaults['payment_hash'] ) ) {
            $pricing = new Wpjb_Model_Pricing( $defaults['pricing_id'] );
        }
        
        if( isset( $pricing ) && $pricing->exists() && $pricing->meta->is_recurring->value() == "1" ) {
            echo ' checked="checked" disabled="disabled" ';
        }
    }
    
    public function creditCardField($cards, $intent, $stripe_default_card ) {
        
        $setup = $this->getSetupIntent();
        $icons = $this->icons();
        
        ?>
        <div class="wpjb-credit-card-wrap">
            <?php if( get_current_user_id() > 0 && ! empty( $cards->data ) ): ?>

                <div class="wpjb-credit-card-list">
                    <?php foreach( $cards->data as $card ): ?>
                    
                    <div class="wpjb-credit-card-single <?php if($card->id==$stripe_default_card): ?>wpjb-card-is-default<?php endif; ?>">
                        <input type="radio" name="_stripe_card" value="<?php echo esc_attr($card->id) ?>" <?php checked( $card->id, $stripe_default_card ) ?> />

                        <span class="wpjb-glyphs <?php echo $icons[$card->card->brand] ?> wpjb-stripe-cc-icon"></span>

                        <span class="wpjb-stripe-cc-details">
                            <span class="wpjb-stripe-cc-brand"><?php echo $card->card->brand ?></span>
                            <span class="wpjb-stripe-cc-last4">(<?php echo str_repeat( "*", 4 ) . $card->card->last4 ?>)</span>
                            <span class="wpjb-stripe-cc-exp"><?php echo str_pad($card->card->exp_month, 2, "0", STR_PAD_LEFT ) . '/' . substr( $card->card->exp_year, 2) ?></span>
                        </span>

                        <span class="wpjb-stripe-cc-actions">
                            <a href="#" class="wpjb-stripe-cc-actions-default" title="<?php echo esc_attr_e( "Make Default", "wpjobboard" ) ?>"><span class="wpjb-glyphs wpjb-icon-check"></span></a>
                            <a href="#" class="wpjb-stripe-cc-actions-trash" title="<?php echo esc_attr_e( "Delete This Credit Card", "wpjobboard") ?>"><span class="wpjb-glyphs wpjb-icon-trash"></span></a>
                        </span>

                        <span class="wpjb-stripe-cc-actions-trash-confirm">
                            <?php _e( "Delete?", "wpjobboard") ?>
                            <a href="#" class="wpjb-stripe-cc-actions-trash-confirm-yes"><?php _e( "Yes", "wpjobboard" ) ?></a>
                            <a href="#" class="wpjb-stripe-cc-actions-trash-confirm-no"><?php _e( "No", "wpjobboard" ) ?></a>

                        </span>

                        <span class="wpjb-stripe-cc-actions-loader wpjb-glyphs wpjb-icon-spinner wpjb-animate-spin"></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <a href="#" class="wpjb-add-credit-card"><?php _e( "+ Use Different Credit Card", "wpjobboard") ?></a>

                <div class="wpjb-card-details" style="display: none">
                    <div id="card-element" data-secret="<?php echo esc_attr( $intent->client_secret ) ?>" data-setup-intent="<?php echo esc_attr( $setup->client_secret ) ?>" data-newcard="0"></div>

                    <label for="_stripe_save_card" style="margin:0">
                        <input type="checkbox" name="_stripe_save_card" id="_stripe_save_card" value="1" <?php $this->lockCheckbox() ?> /> 
                        <?php _e( "Save card for later use", "wpjobboard" ) ?>
                    </label>

                    <a href="#" class="wpjb-add-credit-card-cancel"><?php _e( "Cancel", "wpjobboard" ) ?></a>
                </div>

                <div id="card-errors" class="wpjb-card-errors">

                </div>

            <?php else: ?>

                <div class="wpjb-card-details">
                    <div id="card-element" data-secret="<?php echo esc_attr( $intent->client_secret ) ?>" data-setup-intent="<?php echo esc_attr( $setup->client_secret ) ?>" data-newcard="1"></div>

                    <?php if(get_current_user_id() > 0): ?>
                    <label for="_stripe_save_card" style="margin:0">
                        <input type="checkbox" name="_stripe_save_card" id="_stripe_save_card" value="1" <?php $this->lockCheckbox() ?> /> 
                        <?php _e( "Save card for later use", "wpjobboard" ) ?>
                    </label>
                    <?php endif; ?>

                    <div id="card-errors" class="wpjb-card-errors"></div>
                </div>

            <?php endif; ?>

        </div>
        <?php
    }
    
    public function icons() {
        return array(
            "American Express" => 'adverts-icon-cc-amex',
            "Diners Club" => 'adverts-icon-cc-diners-club',
            "Discover" => 'adverts-icon-cc-discover',
            "JCB" => 'adverts-icon-cc-jcb',
            "MasterCard" => 'adverts-icon-cc-mastercard',
            "UnionPay" => 'adverts-icon-credit-card',
            "Visa" => 'wpjb-icon-cc-visa',
            "Unknown" => 'adverts-icon-credit-card',

            "American Express" => 'adverts-icon-cc-amex',
            "Diners Club" => 'adverts-icon-cc-diners-club',
            "discover" => 'adverts-icon-cc-discover',
            "jcb" => 'adverts-icon-cc-jcb',
            "mastercard" => 'adverts-icon-cc-mastercard',
            "unionpay" => 'adverts-icon-credit-card',
            "visa" => 'wpjb-icon-cc-visa',
            "unknown" => 'adverts-icon-credit-card'
        );
    }
    
    public function inputExpiration()
    {
        $month = new Daq_Form_Element_Text("");
        $month->addClass("wpjb-stripe-cc");
        $month->setAttr("data-stripe", "exp-month");
        
        
        $year = new Daq_Form_Element_Text("");
        $year->addClass("wpjb-stripe-cc");
        $year->setAttr("data-stripe", "exp-year");
        
        echo '<div class="wpjb-stripe-expiration">'.$month->render() . "<strong>/</strong>" . $year->render().'</div>';
    }
}


?>
