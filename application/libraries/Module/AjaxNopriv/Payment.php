<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Payment
 *
 * @author Grzegorz
 */
class Wpjb_Module_AjaxNopriv_Payment 
{
    public static function acceptAction()
    {
        $request = Daq_Request::getInstance();
        $engine = $request->getParam("engine");
        
        $class = Wpjb_Project::getInstance()->payment->getEngine($engine);
        
        $payment = new $class();
        $payment->bind($request->post(), $request->get());
        
        $object = $payment->getObject();
        
        /* @var $payment Wpjb_Payment_Interface */
        
        try {
            
            $result = $payment->processTransaction();
            
            $object->payment_paid = $result["paid"];
            $object->external_id = $result["external_id"];
            $object->paid_at = current_time('mysql', true);
            $object->status = 2;
            $object->save();
            
            $object->accepted();
            
            $object->log(__("Payment verified by remote server.", "wpjobboard"));
            
            if( isset( $result['is_recurring']) && $result['is_recurring'] == 1) {
                Wpjb_Model_MetaValue::import('membership', 'subscription_id', $result['external_id'], $object->object_id);
            }
            
            $mail = Wpjb_Utility_Message::load("notify_admin_payment_received");
            $mail->setTo(wpjb_conf("admin_email", get_option("admin_email")));
            $mail->assign("payment", $object);
            $mail->send();
            
            $result["message"] = "";
            foreach($object->successMessages() as $m) {
                $result["message"] .= "<p>" . $m . "</p>";
            }
            
        } catch(Exception $e) {

            if($object->id>0) {

                $object->status = 3;
                $object->save();
                
                $object->log($e->getMessage());
            }
            
            $result = array(
                "result" => "fail",
                "message" => $e->getMessage()
            );
            
            if( $result['redirect_after'] ) {
                wp_redirect( $result['redirect_after'] );
                exit;
            }
        }
        
        if($request->getParam("echo") == "1") {
            echo json_encode($result);
        }
        
        if( $result['redirect_after'] ) {
            wp_redirect( $result['redirect_after'] );
            exit;
        }
        
        die;
    }
    
    public static function checkAction() {
        
        $request = Daq_Request::getInstance();
        $response = new stdClass();
        $response->status = 0; // -1: stop, 0: continue, 1: done
        $response->message = null;
        
        $payment_id = $request->post("payment_id");
        
        $query = new Daq_Db_Query();
        $query->from("Wpjb_Model_Payment t");
        $query->where("id = ?", $payment_id);
        $query->limit(1);

        $payments = $query->execute();

        if( ! isset($payments[0]) ) {
            $response->status = -1;
            $response->message = sprintf( __( "Payment with ID = %d does not exist.", "wpjobboard" ), $payment_id );
            echo json_encode( $response );
            exit;
        }

        $payment = $payments[0];
        
        if( $payment->user_id > 0 && $payment->user_id != wpjb_get_current_user_id("employer") ) {
            $response->status = -1;
            $response->message = __( "This payment does not belong to you.", "wpjobboard" );
            echo json_encode( $response );
            exit;
        }

        if( $payment->status == 2 ) {
            $response->status = 1;
            $response->message = "";
        } elseif( $payment->status == 4 ) {
            $response->status = -1;
            $response->message = sprintf( __( "Payment failed with message '%s'.", "wpjobboard" ), $payment->message );
            echo json_encode( $response );
            exit;
        } else {
            echo json_encode( $response );
            exit; 
        }

        // Success!
        $response->status = 1;
        $response->message = "";
        foreach($payment->successMessages() as $m) {
            $response->message .= "<p>" . $m . "</p>";
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Renders Payment Form HTML
     * 
     * Params:
     * - gateway
     * - defaults
     * 
     * @since 4.4.0
     * @return void
     */
    public static function renderAction()
    {
        $request = Daq_Request::getInstance();
        $gateway = $request->post("gateway");
        
        $defaults = $request->post("defaults", array());
        
        if(isset($defaults["payment_hash"])) {
            $payment = Wpjb_Model_Payment::getFromHash($defaults["payment_hash"]);
            $defaults["fullname"] = $payment->fullname;
            $defaults["email"] = $payment->email;
        }
        
        $defaults = apply_filters("wpjb_payment_defaults", $defaults);
        
        if(empty($gateway)) {
            $form = new Wpjb_Form_Payment_Default();
        } else {
            $payment = new $gateway();
            $class = $payment->getFormFrontend();
            $form = new $class();
        }
        
        /* @var $form Daq_Form_Abstract */
        foreach($defaults as $key => $default) {
            if($form->hasElement($key)) {
                $form->getElement($key)->setValue($default);
            }
        }
        
        $view = new stdClass();
        $view->form = $form;
        $view->submit = null;
        $view->action = "";
        $view->shortcode = false;
        $view->page_class = "wpjb-payment-form";

        $shortcode = new Wpjb_Shortcode_Dynamic();
        $shortcode->view = apply_filters( "wpjb_payment_form", $view );
        
        $json = array(
            "html" => $shortcode->render("default", "form"),
            "load" => array()
        );
        
        echo json_encode(apply_filters("wpjb_payment_render_response", $json, $gateway));
        exit;
    }
    
    /**
     * Creates order if price is greater than 0
     * 
     * Params
     * - pricing_id
     * - discount_code
     * - form
     * - gateway
     * 
     * @since 4.4.0
     * @return void
     */
    public static function createAction()
    {
        $request = Daq_Request::getInstance();
        $gateway = $request->post("gateway");
        $discount = trim($request->post("discount_code"));
        $payment_hash = $request->post("payment_hash");
        
        $form_error = null;
        $form_errors = array();

        $pricing = new Wpjb_Model_Pricing($request->post("pricing_id"));

        if($discount) {
            $pricing->applyCoupon($discount);
            $pricing->getCoupon()->used++;
            $pricing->getCoupon()->save();
        }
        
        if($pricing->getTotal() > 0) {
            $method = new $gateway();
        } else {
            $method = new Wpjb_Payment_Free();
        }
        
        $class = $method->getFormFrontend();
        $engine = $method->getEngine();
        $form = new $class();
        
        $result = (int)$form->isValid($request->post("form"));
        
        if(!$result) {
            
            $form_error = $form->getGlobalError();
            $form_errors = $form->getErrors();
            
            $result = -1;
            
            $json = array(
                "result" => $result,
                "order_id" => null,
                "success" => null,
                "form_error" => $form_error,
                "form_errors" => $form_errors,
            );
            echo json_encode($json);
            exit;
        }

        $tdata = array(
            "id" => $request->post("object_id"),
            "type" => 0
        );
        
        switch($pricing->price_for) {
            case "101":
                $tdata["id"] = $request->post("object_id");
                $tdata["type"] = Wpjb_Model_Payment::JOB;
                break;
            case "201":
                $tdata["id"] = $request->post("object_id");
                $tdata["type"] = Wpjb_Model_Payment::RESUME;
                break;
            case "250":
                $tdata["id"] = self::purchaseMembership( $pricing, "employer" );
                $tdata["type"] = Wpjb_Model_Payment::MEMBERSHIP;
                break;
            case "150":
                $tdata["id"] = self::purchaseMembership($pricing, "candidate" );
                $tdata["type"] = Wpjb_Model_Payment::CAND_MEMBERSHIP;
                break;
        }
        
        $data = apply_filters("wpjb_payment_object", $tdata, $pricing);
        $payment = null;
        
        $taxer = new Wpjb_Utility_Taxer();
        $taxer->setPrice($pricing->getPrice());
        $taxer->setDiscount($pricing->getDiscount());
        
        if($payment_hash) {
            $payment = Wpjb_Model_Payment::getFromHash($payment_hash);
            $data["id"] = $payment->object_id;
            $data["type"] = $payment->object_type;
        }
        
        if(!is_null($payment)) {
            $payment->pricing_id = $pricing->id;
            $payment->object_type = $data["type"];
            $payment->object_id = $data["id"];
            $payment->user_ip = $_SERVER['REMOTE_ADDR'];
            $payment->user_id = wpjb_get_current_user_id("employer");
            $payment->fullname = $form->value("fullname");
            $payment->email = $form->value("email");
            $payment->engine = $engine;
            $payment->payment_sum = $taxer->value->total;
            $payment->payment_discount = $taxer->value->discount;
            $payment->payment_currency = $pricing->currency;
            $payment->params = "";
            $payment->save();
            $isNew = false;
        } else {
            $payment = new Wpjb_Model_Payment();
            $payment->pricing_id = $pricing->id;
            $payment->object_type = $data["type"];
            $payment->object_id = $data["id"];
            $payment->user_ip = $_SERVER['REMOTE_ADDR'];
            $payment->user_id = wpjb_get_current_user_id("employer");
            $payment->fullname = $form->value("fullname");
            $payment->email = $form->value("email");
            $payment->external_id = ""; 
            $payment->status = 1;
            $payment->message = "";
            $payment->created_at = current_time("mysql", 1);
            $payment->paid_at = "0000-00-00 00-00-00";
            $payment->engine = $engine;
            $payment->payment_sum = $taxer->value->total;
            $payment->payment_paid = 0;
            $payment->payment_discount = $taxer->value->discount;
            $payment->payment_currency = $pricing->currency;
            $payment->params = "";
            $payment->save();
            $isNew = true;
        }
        
        if($pricing->getTotal() == 0) {

            $payment->status = 2;
            $payment->paid_at = current_time("mysql", 1);
            $payment->save();
            
            $payment->accepted();
            
            $payment->log(sprintf(__("Used 100%% discount code [%s]. Payment accepted instantly.", "wpjobboard"), $discount));
        } 
        
        if($isNew && $taxer->isEnabled()) {
            $payment->log(sprintf(__('Applied %1$s (%2$s) Tax.', "wpjobboard"), $taxer->value->rate."%", wpjb_price($taxer->value->tax, $pricing->currency)));
        }
        
        $method->setObject($payment);
        
        do_action("wpjb_payment_created", $payment, $form, $gateway);
           
        $json = array(
            "result" => $result,
            "order_id" => null,
            "success" => $method->render(),
            "form_error" => $form_error,
            "form_errors" => $form_errors,
        );
        
        echo json_encode(apply_filters("wpjb_payment_created_response", $json));
        exit;
    }
    
    public static function messageAction() {
        
        wp_enqueue_style('wpjb-css');
        
        $request = Daq_Request::getInstance();
        $status = $request->get("status");
            
        ob_start();
        ?>
        <?php wp_head(); ?>
        <style type="text/css">
            .wpjb_single_box_bg {
                background-color: whitesmoke;
                width: 100%;
                height: 100%;
                position: absolute;
            }

            .wpjb_single_box {
                background-color: #fff;
                width: 600px;
                height: 100px;
                margin: auto;
                border-radius: 5px;
                text-align: center;
                padding: 10px;
                margin-top: 20%;
            }
            
            div.wpjb-flash-error {
                clear: both;
                overflow: hidden;
                border: 1px solid #DE5400;
                background-color: #f04124;
                padding: 5px 15px;
                margin: 0 0 5px 0;
                line-height: 2em;
                color: white;
            }

            div.wpjb-flash-error a {
                color: white;
                text-decoration: underline !important
            }
            
            div.wpjb-flash-info {
                clear: both;
                overflow: hidden;
                padding: 5px 15px;
                margin: 0px 0px 5px 0px;
                border: 1px solid #f0f0f0;
                background-color: #F0F8FF;
                line-height: 2em;
            }

            div.wpjb-flash-error .wpjb-glyphs:before,
            div.wpjb-flash-info .wpjb-glyphs:before {
                opacity: 0.85;
                font-size: 1.3em;
            }
        </style>
        <div class="wpjb_single_box_bg">
            <div class="wpjb_single_box">
                <?php if( $status == "success" ): ?>
                    <div class="wpjb-flash-info wpjb-icon-ok">
                        <span class="wpjb-glyphs"><?php _e("Your recurring payment was saved succesfully.", "wpjobboard") ?></span>
                    </div>
                <?php else: ?>
                    <div class="wpjb-flash-error">
                        <span class="wpjb-glyphs wpjb-icon-attention"><?php _e("Thre was an error on recurring payment save.", "wpjobboard") ?></span>
                    </div>
                <?php endif; ?>
                <br/>
                <a href="<?php echo get_home_url(); ?>"><?php _e("Home URL", "wpjobboard"); ?></a> | 
                <a href="<?php echo wpjb_link_to("employer_home"); ?>"><?php _e("Employer Dashboard", "wpjobboard"); ?></a> |
                <a href="<?php echo wpjr_link_to("myresume_home"); ?>"><?php _e("Candidate Dashboard", "wpjobboard"); ?></a> 
            </div>
            
        </div>
        <?php echo wp_footer(); ?>
        <?php
        $html = ob_get_clean();
        echo $html;
        exit;
    }
    
    protected static function purchaseMembership( $pricing, $user_type) 
    {
        $member = new Wpjb_Model_Membership();
        $member->user_id = wpjb_get_current_user_id( $user_type );
        $member->package_id = $pricing->id;
        $member->started_at = "0000-00-00";
        $member->expires_at = "0000-00-00";
        $member->deriveFrom($pricing);
        $member->save();

        return $member->id;
    }
    
}

