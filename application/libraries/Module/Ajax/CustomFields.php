<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CustomFields
 *
 * @author greg
 */
class Wpjb_Module_Ajax_CustomFields 
{
    protected static function _stdToArray($object)
    {
        if (is_object($object)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $object = get_object_vars($object);
        }

        if (is_array($object)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(array(__CLASS__, __METHOD__), $object);
        } else {
            // Return array
            return $object;
        }
	
    }
    
    public function saveAction()
    {
        $request = Daq_Request::getInstance();
        
        //$form_id = $request->post("form_id");
        $form_label = $request->post("form_label");
        $form_code = $request->post("form_code");
        $form_is_active = $request->post("form_is_active");
        //$form_is_default = $request->post("form_is_default");
        
        $form = $request->post("form");
        $formName = $request->post("form_name");

        $form = stripslashes_deep( $_POST['form'] );

        if($request->post("is_string") == 1 && !is_array($form)) {
            $form = self::_stdToArray( json_decode( $form ) );
        }

        if( ! is_array( $form ) ) {
           echo json_encode( array( "result" => false, "message" => __( "The form scheme could not be decoded.", "wpjobboard") ) );
           exit;
        }
        
        $fields = $form["field"];

        foreach($fields as $key => $field) {
            
            $select = Daq_Db_Query::create();
            $select->from("Wpjb_Model_Meta t");
            $select->where("meta_object = ?", $formName);
            $select->where("name = ?", $field["name"]);
            $select->limit(1);
            $result = $select->execute();

            if($field["is_builtin"]) {
                continue;
            }

            if(!empty($result)) {
                $meta = $result[0];
            } else {
                $meta = new Wpjb_Model_Meta;
                $meta->meta_object = $formName;
                $meta->name = $field["name"];
                $meta->meta_type = 3;
            }

            $meta->meta_value = serialize($field);

            if(isset($field["delete_forever"])) {
                $id = $meta->id;
                
                if($meta->conf("type") == "ui-input-file") {
                    $upload = wpjb_upload_dir($meta->meta_object, "", null, "basedir");
                    $upload = dirname($upload)."/*/".$meta->name;
                    Wpjb_Utility_Log::debug($upload);
                    foreach((array)glob($upload) as $rdir) {
                        wpjb_recursive_delete($rdir);
                    }
                    
                } else {
                    $query = Daq_Db_Query::create();
                    $query->from("Wpjb_Model_MetaValue t");
                    $query->where("meta_id = ?", $id);
                    $list = $query->execute();
                    foreach($list as $mv) {
                        $mv->delete();
                    }
                }
                
                if($formName == "job") {
                    $query = Daq_Db_Query::create();
                    $query->from("Wpjb_Model_Meta t");
                    $query->where("meta_object = ?", "job_search");
                    $query->where("name = ?", $field["name"]);
                    $list = $query->execute();
                    
                    foreach($list as $m) {
                        $m->delete();
                        $o = get_option("wpjb_form_job_search");
                        unset($o["field"][$key]);
                        update_option("wpjb_form_job_search", $o);
                    }
                }
                
                unset($form["field"][$key]);
                $meta->delete();
                
            } else {
                $meta->save();
            } 
        }
        
        $form['config'] = array(
            //"form_id"           => $form_id,
            "form_label"        => $form_label,
            "form_code"         => $form_code,
            "form_is_active"    => $form_is_active,
            //"form_is_default"   => $form_is_default,
        );
        
        $old_form = maybe_unserialize( get_option("wpjb_form_".$formName."_".$form_code ) );
        
        if ( $form === $old_form || maybe_serialize( $form ) === maybe_serialize( $old_form ) ) {
            echo json_encode( array( "result" => true, "message" => __( "The forms are the same.", "wpjobboard" ) ));
            exit;
        }
        
        $forms = get_option( "wpjb_forms_list", array() );
        $update = update_option("wpjb_form_".$formName."_".$form_code, serialize( $form ) );
         
        if( !in_array( "wpjb_form_".$formName."_".$form_code, $forms[$formName] ) ) {
            if( !is_array($forms[$formName] ) ) {
                $forms[$formName] = array();
            }
            $forms[$formName][] = "wpjb_form_".$formName."_".$form_code; 
            update_option("wpjb_forms_list", $forms);
        }
        
        echo json_encode(array("result"=>$update ));
        
        die;
    }
    
    public function checknameAction() {
        
        $request = Daq_Request::getInstance();
        $code = $request->post("code");
        $label = $request->post("label");
        $form = $request->post("form_name");
        
        $code_exists = false;
        $empty_label = true;
        
        $forms = get_option( "wpjb_forms_list", array() );
        if ( isset( $forms[$form] ) && ( $key = array_search( $code, $forms[$form] ) ) !== false ) {
            $code_exists = true;
        }
        
        if( strlen( $label ) > 0 ) {
            $empty_label = false;
        }
        
        $result = new stdClass();
        $result->code = 200;
        $result->message = array();
        
        if( $code_exists ) {
            $result->code = 300;
            $result->message[] = __("This code already exists, please change the form code.", "wpjobboard");
        }
        
        if( $empty_label ) {
            $result->code = 300;
            $result->message[] = __("Form label canot be empty.", "wpjobboard");
        }
        
        echo json_encode( $result );
        die;
    }
}

?>
