<?php

class Wpjb_Form_Frontend_ListSearch extends Daq_Form_Abstract 
{
    
    protected $_custom = "wpjb_form_job_search";
    
    public function __construct( $id = null, $options = array() )
    {
        if( isset( $options['custom'] ) ) {
            if( $options['custom'] == "wpjb_form_job-list-search") {
                $this->_custom = "wpjb_form_job_list_search";
            } elseif( strpos( $options['custom'], "wpjb_form_job-list-search" ) !== 0 ) {
                $this->_custom = "wpjb_form_job_list_search_" . $options['custom'];
            } else {
                $this->_custom = $options['custom'];
            }
        } elseif( is_admin() && Daq_Request::getInstance()->get("form_code", null) ) {
            $this->_custom = Daq_Request::getInstance()->get("form_code", null);
        } else {
            $forms_list = get_option( "wpjb_forms_list" ); 
            $this->_custom = "wpjb_form_job_list_search";
            
            if( isset( $forms_list['job_list_search'] ) ) {
                foreach( $forms_list['job_list_search'] as $form_code ) {

                    $form_detail = maybe_unserialize( get_option( $form_code ) );

                    if( /*$form_detail['config']['form_is_default'] && */ $form_detail['config']['form_is_active'] ) {
                        $this->_custom = "wpjb_form_job_list_search_" . $form_detail['config']['form_code'];
                        break;
                    }
                } 
            }
        }
        
        parent::__construct($id, $options);
    }
    
    public function init() 
    {
        $this->addGroup("visible", "");
        $this->addGroup("_internal", "");
        
        $e = $this->create("show_results", "hidden");
        $e->setValue("1");
        $this->addElement($e, "_internal");
        
        $e = $this->create("query");
        $e->setLabel("");
        $e->addMeta("classes", "wpjb-input wpjb-input-type-half wpjb-input-type-half-left");
        $e->addClass("wpjb-top-search-query wpjb-ls-query");
        $e->setAttr("autocomplete", "off");
        $e->setAttr("placeholder", __("Keyword ...", "wpjobboard"));
        $this->addElement($e, "visible");
        
        $e = $this->create("location");
        $e->setLabel("");
        $e->addMeta("classes", "wpjb-input wpjb-input-type-half wpjb-input-type-half-right");
        $e->addClass("wpjb-top-search-location wpjb-ls-location");
        $e->setAttr("autocomplete", "off");
        $e->setAttr("placeholder", __("Location ...", "wpjobboard"));
        $this->addElement($e, "visible");
        
        $e = $this->create("type", "checkbox");
        $e->setLabel("");
        $e->addMeta("classes", "wpjb-input wpjb-input-type-full");
        $e->addOptions(wpjb_form_get_jobtypes());
        $e->addClass("wpjb-ls-type");
        $e->setCute(true);
        $this->addElement($e, "visible");
        
        
        add_filter("wpjb_form_init_list_search", array($this, "customFields"), 9);
        apply_filters("wpjb_form_init_list_search", $this);
    }
    
    public function customFields()
    {
        if(empty($this->_custom)) {
            return $this;
        }
        
        $this->loadGroups();
        
        $resume_fields = new Wpjb_Form_AddJob();
        foreach( $resume_fields->getFields() as $field ) {                       
            $field->setBuiltin(false);
            $field->setTrashed(true);
            $field->setRequired(false);
            $this->addElement($field, "_trashed");
        }
        
        if(!isset($this->fieldset["_trashed"])) {
            $this->addGroup("_trashed");
        }
        
        $key = "job";
        $list = array(
            "ui-input-text" => "text",
            "ui-input-radio" => "radio",
            "ui-input-checkbox" => "checkbox",
            "ui-input-select" => "select",
            "ui-input-file" => "file",
            "ui-input-textarea" => "textarea",
            "ui-input-hidden" => "hidden",
            "ui-input-password" => "password",
        );
        
        $query = Daq_Db_Query::create();
        $query->from("Wpjb_Model_Meta t");
        $query->where("meta_object = ?", $key);
        $query->where("meta_type = 3");
        $row = $query->execute();
        
        foreach($row as $meta) {
            $data = unserialize($meta->meta_value);
            $data["is_trashed"] = true;
            $data["group"] = "_trahsed";
            if($this->_upload) {
                $data["upload_path"] = $this->_upload;
            }
            
            $tag = $list[$data["type"]];
            
            if(in_array($tag, array("hidden", "file"))) {
                continue;
            } elseif($tag == "textarea") {
                $tag = "text";
            }
            
            if($this->hasElement($meta->name)) {
                continue;
            }
            
            $e = $this->create("$meta->name", $tag);
            $e->overload($data);
            $e->setBuiltin(false);
            $e->setTrashed(true);
            $e->setRequired(false);
            $this->addElement($e, "_trashed");

        }
        
        return $this;
    }

}
