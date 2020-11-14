<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Stripe
 *
 * @author Mark
 */
class Wpjb_Module_AjaxNopriv_Stripe
{
    
    public function mainAction() {
        
        $input = @file_get_contents("php://input");
        $event = json_decode($input);
        
        if( $event->data->object->billing_reason == "subscription_create" ) {
            self::subscriptionPaidNew($event);
        } else if( $event->data->object->billing_reason == "subscription_cycle" ) {
            self::subscriptionPaidRenew($event);
        }
        
        exit;
    }
        
    public static function subscriptionPaidNew( $event ) {
        
        $item = $event->data->object->lines->data[0];
        
        $payment_id = $item->metadata->order_id;
        $subscription_id = $item->subscription;
        
        $amount = $item->amount;
        $currency = strtoupper( $item->currency );
        
        $object = new Wpjb_Model_Payment( $payment_id );
        $cArr = Wpjb_List_Currency::getByCode( $currency );
        
        if( $object->status == 2 ) {
            echo sprintf( __( "Membership %d was already approved.", "wpjobboard" ), $payment_id );
            return;
        }
        
        $object->payment_paid = ( $amount / pow( 10, $cArr["decimal"] ) );
        $object->external_id = $subscription_id;
        $object->paid_at = current_time('mysql', true);
        $object->status = 2;
        $object->save();

        $object->accepted();

        $object->log( sprintf( __("Payment verified by event %s.", "wpjobboard"), $event->id ) );

        echo sprintf( __("Payment verified by event %s.", "wpjobboard"), $event->id );
        
        Wpjb_Model_MetaValue::import('membership', 'subscription_id', $subscription_id, $object->object_id);

        $mail = Wpjb_Utility_Message::load("notify_admin_payment_received");
        $mail->setTo(wpjb_conf("admin_email", get_option("admin_email")));
        $mail->assign("payment", $object);
        $mail->send();
        
    }
    
    public static function subscriptionPaidRenew( $event ) {

        foreach($event->data->object->lines->data as $object) {
            $subscription_id = $object->subscription;
            
            // Find Membership
            $q = Daq_Db_Query::create();
            $m = $q->select()->from("Wpjb_Model_MetaValue t")->where("t.value = ?", $subscription_id)->execute();

            if( !isset( $m[0] ) || empty( $m[0] ) ) {
                continue;
            } 
            
            $mv = $m[0];
            
            $original_membership = new Wpjb_Model_Membership($mv->object_id);
            $new_membership = new Wpjb_Model_Membership();

            $pricing = new Wpjb_Model_Pricing($original_membership->package_id);
            $duration = $pricing->meta->visible->value();
            if(!is_numeric($duration)) {
                $duration = 30;
            }

            // Copy with new date
            $new_membership->id = null;
            $new_membership->user_id = $original_membership->user_id;
            $new_membership->package_id = $original_membership->package_id;
            $new_membership->started_at = $original_membership->expires_at;
            $new_membership->expires_at = date( "Y-m-d", wpjb_time("today +".$duration." day") );
            $new_membership->package = $original_membership->package;
            $new_membership->save();
                     
            $user = get_userdata( $new_membership->user_id );
            
            // Save Payment (For History Reason)
            $payment = new Wpjb_Model_Payment();
            $payment->pricing_id = $new_membership->package_id;
            $payment->object_type = 3;
            $payment->object_id = $new_membership->id;
            $payment->user_ip = $_SERVER['REMOTE_ADDR'];
            $payment->user_id = $new_membership->user_id;
            $payment->fullname = $user->first_name . " " . $user->last_name;
            $payment->email = $user->user_email;
            $payment->external_id = $subscription_id; 
            $payment->status = 2;
            $payment->message = __("Subcription automatic payment", "wpjobboard");
            $payment->created_at = current_time("mysql", 1);
            $payment->paid_at = current_time("mysql", 1);
            $payment->engine = "Stripe";
            $payment->payment_sum = $object->amount / 100;
            $payment->payment_paid = $object->amount / 100;
            $payment->payment_discount = 0;
            $payment->payment_currency = $object->currency;
            $payment->params = "";
            $payment->save();
        }

    }
    
    public function createSubscriptionAction() {
        
        $request = Daq_Request::getInstance();
        $response = array( "message" => "", "external_id" => null );
        
        try {
            $sub = self::createSubscription();
        } catch (Exception $ex) {
            $response["message"] = $ex->getMessage();
            echo json_encode( $response );
            exit;
        }
        
        if( $sub->status == "active" ) {
            
            $payment_id = $request->post( "payment_id" );
            $payment = new Wpjb_Model_Payment( $payment_id );
            
            self::subscriptionStart($payment_id, $sub);
            
            $response["message"] = $payment->successMessages();
            $response["external_id"] = $sub->id;
            
        } else if( $sub->status == "incomplete" ) {
            
            $payment = new Wpjb_Model_Payment( $request->post( "payment_id" ) );
            $invoice = \Stripe\Invoice::retrieve($sub->latest_invoice);
            
            $response["message"] = sprintf( __( "<strong>Subscription payment is pending.</strong><br/>Most likely your card requires 3D Secure authentication, please <a href='%s'>complete your payment manually</a>.", "wpjobboard" ), $invoice->hosted_invoice_url );
            $response["external_id"] = $sub->id;
        }
        
        echo json_encode( $response );
        exit;
    }
    
    public function createSubscription() {
        
        // https://stripe.com/docs/billing/subscriptions/payment#signup-flow
        // https://stripe.com/docs/api/subscriptions/create
        
        if(!class_exists("Stripe")) {
            include_once Wpjb_List_Path::getPath("vendor")."/stripe/init.php";
        }
        
        $request = Daq_Request::getInstance();
        $plan_id = $request->post( "plan_id", null );
        $token = $request->post( "token", null );
        
        $stripe = new Wpjb_Payment_Stripe();
        \Stripe\Stripe::setApiKey($stripe->conf("secret_key"));
        
        $payment = new Wpjb_Model_Payment( $request->post( "payment_id" ) );
        
        if( ! $payment->exists() ) {
            throw new Exception( __( "Payment does not exist.", "wpjobboard" ) );
        }
        
        $stripe_customer = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true ); 
        
        if( ! $stripe_customer ) {
            $customer = \Stripe\Customer::create(array(
                'email' => $payment->email,
                'description' => $payment->fullname,
            ));

            $stripe_customer = $customer->id;

            update_user_meta( get_current_user_id(), "_wpjb_stripe_customer_id", $stripe_customer );
        } else {
            $customer = \Stripe\Customer::retrieve( $stripe_customer );
        }
        
        if( $request->post( "setup_intent" ) != "0" ) {
            $setup_intent = \Stripe\SetupIntent::retrieve( $request->post( "setup_intent" ) );

            $payment_method = \Stripe\PaymentMethod::retrieve( $setup_intent->payment_method );
            $payment_method->attach( array( 'customer' => $stripe_customer ) );
            
            if( empty( $customer->invoice_settings->default_payment_method ) ) {
                \Stripe\Customer::update( $stripe_customer , array(
                    'invoice_settings' => array(
                        'default_payment_method' => $payment_method->id
                    )
                ) );
            }
        }
        
        $sub = \Stripe\Subscription::create(apply_filters( "wpjb_stripe_subscription_create", [
            'customer' => $stripe_customer,
            'items' => [ [ 'plan' => $plan_id ] ],
            'default_payment_method' => $token,
            //'default_tax_rates' => ['txr_1FlbOD24wZHNk6gIDTnVmTNV'],
            'metadata' => [ 'order_id' => $payment->id ]
        ] ) );
        
        return $sub;
    }
    
    public function subscriptionStart( $payment_id, $sub ) {
        
        $subscription_id = $sub->id;
        $amount = $sub->items->data[0]->plan->amount;
        $currency = $sub->items->data[0]->plan->currency;
        
        $object = new Wpjb_Model_Payment( $payment_id );
        $cArr = Wpjb_List_Currency::getByCode( $currency );
        
        $object->payment_paid = ( $amount / pow( 10, $cArr["decimal"] ) );
        $object->external_id = $subscription_id;
        $object->paid_at = current_time('mysql', true);
        $object->status = 2;
        $object->save();

        $object->accepted();

        $object->log(__("Card charged automatically.", "wpjobboard"));

        Wpjb_Model_MetaValue::import('membership', 'subscription_id', $subscription_id, $object->object_id);

        $mail = Wpjb_Utility_Message::load("notify_admin_payment_received");
        $mail->setTo(wpjb_conf("admin_email", get_option("admin_email")));
        $mail->assign("payment", $object);
        $mail->send();
    }
    


}

