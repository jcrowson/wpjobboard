<?php

class Wpjb_Module_Ajax_Stripe {
    
    /**
     * Prints response in the browser
     * 
     * @since 1.0
     * @since 1.2   $code param
     * 
     * @param int    $status    Returns response code (0 or 1)
     * @param string $message   Response message
     * @param int    $code      Response code
     * @return void
     */
    protected static function _print( $status, $message, $code = 200 ) {
        
        if( $code !== 200 ) {
            http_response_code( $code );
        }
        
        $json = json_encode( apply_filters( "wpadverts_stripe_ajax_print", array( 
            "result" => $status, 
            "message" =>  $message
        ) ) );


        echo $json;
        exit;
    }
    
    /**
     * Prints response to screen if $condition is true
     * 
     * @since 1.0
     * @param boolean   $condition     Condition to check
     * @param int       $status        Returns response code (0 or 1)
     * @param string    $message       Response message
     * @return void
     */
    protected static function _print_if( $condition, $status, $message ) {
        if( $condition ) {
            self::_print( $status, $message );
        }
    }
    
    public static function trashAction() {

        
        if(!class_exists("\Stripe\Stripe")) {
            include_once Wpjb_List_Path::getPath("vendor") . "/stripe/init.php";
        }
        
        $stripe = new Wpjb_Payment_Stripe();
        \Stripe\Stripe::setApiKey($stripe->conf("secret_key"));
        
        self::_print_if( !get_current_user_id(), 0, __( "You need to be logged in to delete cards.", "wpjobboard" ) );
        
        $customer_id = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true );
        $request = Daq_Request::getInstance();
        $card_id = $request->post( "card_id" );
        
        self::_print_if( !$customer_id, 0, __( "Unknown Stripe User ID", "wpjobboard" ) );
        self::_print_if( !$card_id, 0, __( "You need to select card you want to delete.", "wpjobboard" ) );
        
        try {
            $customer = \Stripe\Customer::retrieve( $customer_id );
            
            if( stripos( $card_id, "pm_" ) === 0 ) {
                $payment_method = \Stripe\PaymentMethod::retrieve( $card_id );
                $payment_method->detach();
                $deleted = true;
            } else {
                $card = $customer->sources->retrieve( $card_id )->delete();
                $deleted = $card->deleted;
            }

            if( $deleted === true ) {
                self::_print( 1, __( "Card deleted", "wpjobboard" ) );
            } else {
                self::_print( 0, __( "Card count not be deleted.", "wpjobboard" ) );
            }
        } catch(\Exception $e) {
            self::_print( 0, $e->getMessage() );
        }
    }
    
    public static function sourceAction() {
        
        if(!class_exists("\Stripe\Stripe")) {
            include_once Wpjb_List_Path::getPath("vendor") . "/stripe/init.php";
        }
        
        $stripe = new Wpjb_Payment_Stripe();
        \Stripe\Stripe::setApiKey($stripe->conf("secret_key"));
        
        self::_print_if( !get_current_user_id(), 0, __( "You need to change default card.", "wpjobboard" ) );
        
        $customer_id = get_user_meta( get_current_user_id(), '_wpjb_stripe_customer_id', true );
        $request = Daq_Request::getInstance();
        $card_id = $request->post( "card_id" );
        
        self::_print_if( !$customer_id, 0, __( "Unknown Stripe User ID", "wpjobboard" ) );
        self::_print_if( !$card_id, 0, __( "You need to select card you want to delete.", "wpjobboard" ) );

        try {
            if( stripos( $card_id, "pm_" ) === 0 ) {
                
                $customer = \Stripe\Customer::retrieve( $customer_id );
                $customer->invoice_settings->default_payment_method = $card_id;
                $customer->save();
            } else {
                
                $customer = \Stripe\Customer::retrieve( $customer_id );
                $customer->default_source = $card_id;
                $customer->invoice_settings->default_payment_method = null;
                $customer->save();
            }
            
            self::_print( 1, __( "New default card set", "wpjobboard" ) );

        } catch(\Exception $e) {
            self::_print( 0, $e->getMessage() );
        }
    }
}
