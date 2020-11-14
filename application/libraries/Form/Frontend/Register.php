<?php

/**
 * Description of Login
 *
 * @author greg
 * @package
 */

class Wpjb_Form_Frontend_Register extends Wpjb_Form_Abstract_Company
{
    public function init()
    {
        parent::init();
        
        $this->addGroup("auth", __("User Account", "wpjobboard"), -1);
        
        $e = $this->create("user_login");
        $e->setLabel(__("Username", "wpjobboard"));
        $e->setRequired(true);
        $e->addFilter(new Daq_Filter_Trim());
        $e->addValidator(new Daq_Validate_WP_Username());
        $this->addElement($e, "auth");

        $e = $this->create("user_password", "password");
        $e->setLabel(__("Password", "wpjobboard"));
        $e->addFilter(new Daq_Filter_Trim());
        $e->addValidator(new Daq_Validate_StringLength(4, 32));
        $e->addValidator(new Daq_Validate_PasswordEqual("user_password2"));
        $e->setRequired(true);
        $this->addElement($e, "auth");

        $e = $this->create("user_password2", "password");
        $e->setLabel(__("Password (repeat)", "wpjobboard"));
        $e->setRequired(true);
        $this->addElement($e, "auth");
        
        
        $e = $this->create("_wpjb_action", "hidden");
        $e->setValue("reg_employer");
        $this->addElement($e, "_internal");
        
        if($this->isNew()) {
            apply_filters("wpjb_form_init_register", $this);
        }
        
        add_filter("wpjb_form_init_company", array($this, "apply"), 9);
        apply_filters("wpjb_form_init_company", $this);
    }

    public function isValid(array $values)
    {
        $isValid = parent::isValid($values);
        
        if($this->hasElement("company_info")) {
            $e = $this->create("company_info_format", "hidden");
            $e->setValue($this->getElement("company_info")->usesEditor() ? "html" : "text");
            $e->setBuiltin(false);
            $this->addElement($e, "_internal");
        }
        
        
        return $isValid;
    }
    
    public function save($append = array())
    {
        
        if(wpjb_conf("employer_approval") == 1) {
            $active = 0; // manual approval
        } else {
            $active = 1;
        }
        
        $append["is_active"] = $active;
        $append["is_public"] = wpjb_conf("employer_is_public", 1);
        
        
        parent::save($append);

        $temp = wpjb_upload_dir("company", "", null, "basedir");
        $finl = dirname($temp)."/".$this->getId();
        wpjb_rename_dir($temp, $finl);
        
        // move transient links
        $this->moveTransients();
        
        Wpjb_Model_MetaValue::import("company", "form_code", $this->value("form_code"), $this->getId());
        
        do_action("wpjb_company_saved", $this->getObject());
        apply_filters("wpjb_form_save_company", $this);
        
    }
}

?>