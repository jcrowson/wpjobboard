<?php

/**
 * Description of Stripe
 *
 * @author Grzegorz
 */
class Wpjb_Payment_Stripe extends Wpjb_Payment_Abstract
{
    public function __construct(Wpjb_Model_Payment $data = null)
    {
        $this->_default = array(
            "disabled" => "0"
        );
        
        $this->_data = $data;
    }
    
    public function getEngine()
    {
        return "Stripe";
    }
    
    public function getForm()
    {
        return "Wpjb_Form_Admin_Config_Stripe";
    }
    
    public function getFormFrontend()
    {
        return "Wpjb_Form_Payment_Stripe";
    }
    
    public function getTitle()
    {
        return "Stripe (Credit Card)";
    }
    
    public function processTransaction()
    {
        $path = Wpjb_List_Path::getPath("vendor");
        
        if(!class_exists("\Stripe\Stripe")) {
            //include_once $path."/Stripe/Stripe.php";
            include_once $path."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey($this->conf("secret_key")); 
        
        $pricing = new Wpjb_Model_Pricing($this->_data->pricing_id);
        $plan_id = Daq_Request::getInstance()->getParam( "plan_id", null );
        
                
        if( !isset( $pricing ) || $pricing->meta->is_recurring->value() != 1 ) {
            
            $this->maybeSaveCC();
            
            $payment_intent_id = Daq_Request::getInstance()->getParam('payment_intent_id');
            //$customer_id = get_user_meta( $payment->post_author, '??', true );
            $intent = \Stripe\PaymentIntent::retrieve( $payment_intent_id );
       
            $cArr = Wpjb_List_Currency::getByCode($intent->currency);
            $amount = $intent->amount_received;
        
            // Proceed only if status succeeded
            if( $intent->status !== "succeeded" ) {
              exit (-2);
            }
        } elseif( $plan_id ) {
        
            /*$stripe_customer = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true ); 

            $intent = \Stripe\Subscription::create([
                'customer' => $stripe_customer,
                'items' => [
                  [
                    'plan' => $plan_id,
                  ],
                ],
                'expand' => ['latest_invoice.payment_intent'],
                //'default_payment_method'    => 
            ]);*/
            
            //$payment_intent_id = $sub->id; //Daq_Request::getInstance()->getParam('payment_intent_id');
            //$customer_id = get_user_meta( $payment->post_author, '??', true );
            //$intent = \Stripe\Subscription::retrieve( $payment_intent_id );
            
            //$cArr = Wpjb_List_Currency::getByCode($intent->plan->currency);
            //$amount = $intent->plan->amount;
            
             // Proceed only if status succeeded
            //if( $intent->status !== "active" ) {
            //  exit (-2);
            //}
        }
        
        return array(
            "external_id"   => $payment_intent_id,
            'is_recurring'  => $pricing->meta->is_recurring->value(),
            "paid"          => ( $amount / pow( 10, $cArr["decimal"] ) ),
        );

    }
    
    public function maybeSaveCC() {

        if( $this->getObject()->meta->stripe_save_cc && $this->getObject()->meta->stripe_save_cc->value() == "pending" ) {
            
            $customer_id = get_user_meta( $this->getObject()->user_id, '_wpjb_stripe_customer_id', true );
            
            if( ! $customer_id ) {
                $customer = \Stripe\Customer::create(array(
                    'email' => $this->getObject()->email,
                    'description' => $this->getObject()->fullname,
                ));

                $customer_id = $customer->id;

                update_user_meta( get_current_user_id(), "_wpjb_stripe_customer_id", $customer_id );
            } else {
                $customer = \Stripe\Customer::retrieve( $customer_id );
            }
 
            $intent_id = $this->getObject()->meta->stripe_payment_intent_id->value();
            $intent = \Stripe\PaymentIntent::retrieve( $intent_id );
            
            $payment_method = \Stripe\PaymentMethod::retrieve( $intent->payment_method );
            $payment_method->attach( array( 'customer' => $customer_id ) );
            
            if( empty( $customer->invoice_settings->default_payment_method ) ) {
                \Stripe\Customer::update( $customer_id , array(
                    'invoice_settings' => array(
                        'default_payment_method' => $payment_method->id
                    )
                ) );
            }
            
            Wpjb_Model_MetaValue::import( "payment", "stripe_save_cc", "saved", $this->getObject()->id );
        }
    }
    
    public function bind(array $post, array $get)
    {
        $this->setObject(new Wpjb_Model_Payment($post["id"]));
        
        parent::bind($post, $get);
    }
    
    public function render()
    {
        $request = Daq_Request::getInstance();
        $form = $request->post( "form" );
        
        if( $form["_stripe_save_card"] == 1 ) {
            Wpjb_Model_MetaValue::import( "payment", "stripe_save_cc", "pending", $this->getObject()->id );
        }
                   
        $html = '';
        //$html.= '<input type="hidden" id="wpjb-stripe-id" value="'.$data["id"].'" />';
        //$html.= '<input type="hidden" id="wpjb-stripe-type" value="'.$data["type"].'" />';
        $html.= '<input type="hidden" id="wpjb-stripe-payment-id" value="'.$this->getObject()->id.'" />';
        
        $html.= '<div class="wpjb-stripe-result">';
        
        $html.= '<div class="wpjb-stripe-pending wpjb-flash-info">';
        $html.= '<div class="wpjb-flash-icon"><span class="wpjb-glyphs wpjb-icon-spinner wpjb-animate-spin"></span></div>';
        $html.= '<div class="wpjb-flash-body">';
        $html.= '<p><strong>'.__("Placing Order", "wpjobboard").'</strong></p>';
        $html.= '<p>'.__("Waiting for payment confirmation ...", "wpjobboard").'</p>';
        $html.= '</div>';
        $html.= '</div>';
        
        $html.= '<div class="wpjb-flash-info wpjb-none">';
        $html.= '<div class="wpjb-flash-icon"><span class="wpjb-glyphs wpjb-icon-ok"></span></div>';
        $html.= '<div class="wpjb-flash-body"></div>';
        $html.= '</div>';
        
        $html.= '<div class="wpjb-flash-error wpjb-none">';
        $html.= '<div class="wpjb-flash-icon"><span class="wpjb-glyphs wpjb-icon-cancel-circled"></span></div>';
        $html.= '<div class="wpjb-flash-body"></div>';
        $html.= '</div>';
        
        $html.= '</div>';
        
        return $html;
    }
    
    public function getIcon() 
    {
        return "wpjb-icon-cc-stripe";
    }
    
    public function getIconFrontend() 
    {
        return "wpjb-icon-credit-card";
    }
    
    protected function _createCustomer($form)
    {
        if(!class_exists("Stripe")) {
            //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey($this->conf("secret_key"));
        
        $id = get_user_meta(get_current_user_id(), "_wpjb_stripe_customer_id", true);

        if(!$id) {
            $customer = \Stripe\Customer::create(array(
                "source" => $form["stripe_token"],
                "email" => $form["email"],
                "description" => $form["fullname"]
            ));
            $id = $customer->id;
            update_user_meta(get_current_user_id(), "_wpjb_stripe_customer_id", $id);
            $card = $customer->sources->data[0];
            
        } else {
            $customer = \Stripe\Customer::retrieve($id);
            $card = $customer->sources->create(array("source" => $form["stripe_token"]));
        }

        return array(
            "type" => "customer",
            "customer_id" => $customer->id,
            "id" => $card->id
        );
    }
    
    /**
     * Creates new plan in Stripe
     * 
     * @param Wpjb_Model_Pricing $pricing
     */
    public function createPlan( Wpjb_Model_Pricing $pricing, $interval ) {
        
        if(!class_exists("Stripe")) {
            //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey($this->conf("secret_key"));
                
        if( isset( $pricing->meta->stripe_id ) && is_object($pricing->meta->stripe_id) ) {
            $id = $pricing->meta->stripe_id->value();
        } else {
            $id = null;
        }
        
        if( !isset($id) || $id == null ) {
            
            $cArr = Wpjb_List_Currency::getByCode($pricing->currency);
            $amount = ($pricing->price)*pow( 10, $cArr["decimal"] );
            
            $slug = sanitize_title($pricing->title);
            $slug = preg_replace("([^A-z0-9\-]+)", "", $slug);
            
            /*$interval = $pricing->meta->is_recurring_interval->value();
            if( $interval == null ) {
                $interval = 'month';
            }
            $interval_count = $pricing->meta->is_recurring_interval_count->value();
            if( $interval_count == null ) {
                $interval_count = 1;
            }*/
            
            $plan = \Stripe\Plan::create(array(
                "amount"            => (int)$amount,
                "interval"          => 'day', // day, week, month, year
                "interval_count"    => (int)$interval, // max 1 for year, 12 for month and 52 for week
                "id"                => $slug,
                "currency"          => $pricing->currency,
                "product"           => array( "name" => $pricing->title ),
            ));
   
            $q = Daq_Db_Query::create();
            $meta_id = $q->select()->from("Wpjb_Model_Meta t")
                                   ->where("t.name = ?", "stripe_id")
                                   ->where("t.meta_object = ?", "pricing")
                                   ->fetchColumn();
            
            $q = Daq_Db_Query::create();
            $mv_id = $q->select()
                       ->from("Wpjb_Model_MetaValue t")
                       ->where("t.meta_id = ?", $meta_id)
                       ->where("t.object_id = ?", $pricing->id)
                       ->fetchColumn();

            $mv = new Wpjb_Model_MetaValue($mv_id);
            $mv->meta_id = $meta_id;
            $mv->object_id = $pricing->id;
            $mv->value = $plan['id'];
            $mv->save();
        }
    }
    
    /**
     * Removes Plan from Stripe
     * 
     * @param Wpjb_Model_Pricing $pricing
     */
    public function removePlan( Wpjb_Model_Pricing $pricing ) {
        
        if(!class_exists("Stripe")) {
            //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey($this->conf("secret_key"));
        
        $id = $pricing->meta->stripe_id->value();
        
        // Remove all subscribtions for this plan
        $subscriptions = \Stripe\Subscription::all(array('plan' => $id, 'limit' => 100, 'status' => 'active'));
        foreach($subscriptions as $sub) {
            $sub->cancel();
        }
        
        // Remove Plan
        $plan = \Stripe\Plan::retrieve( $id );
        $product_id = $plan['product'];
        $plan->delete(); 
        
        // Remove Product
        $product = \Stripe\Product::retrieve( $product_id );
        $product->delete();
        
        // Remove from meta
        $q = Daq_Db_Query::create();
        $meta_id = $q->select()->from("Wpjb_Model_Meta t")
                               ->where("t.name = ?", "stripe_id")
                               ->where("t.meta_object = ?", "pricing")
                               ->fetchColumn();

        $q = Daq_Db_Query::create();
        $mv_id = $q->select()
                   ->from("Wpjb_Model_MetaValue t")
                   ->where("t.meta_id = ?", $meta_id)
                   ->where("t.object_id = ?", $pricing->id)
                   ->fetchColumn();

        $mv = new Wpjb_Model_MetaValue($mv_id);
        $mv->delete();
    }
    
    public function getSubscription( $subscription_id ) {
        
        if( !class_exists( "Stripe" ) ) {
            //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey( $this->conf( "secret_key" ) );
        return \Stripe\Subscription::retrieve( $subscription_id );
    }
    
    public function cancelSubsctiption( $subscription_id ) {
        
        if( !class_exists( "Stripe" ) ) {
            //include_once Wpjb_List_Path::getPath("vendor")."/Stripe/Stripe.php";
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        \Stripe\Stripe::setApiKey( $this->conf( "secret_key" ) );
        $subscription = \Stripe\Subscription::retrieve( $subscription_id );
        
        return $subscription->delete( ); 
    }
}

