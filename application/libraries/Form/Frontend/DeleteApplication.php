<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EditJob
 *
 * @author greg
 */
class Wpjb_Form_Frontend_DeleteApplication extends Daq_Form_ObjectAbstract
{
    protected $_model = "Wpjb_Model_Application";

    public function  __construct($id)
    {
        parent::__construct($id);
    }
   

    public function init()
    {
        $this->addGroup("default");
        
        $e = $this->create("_wpjb_action", "hidden");
        $e->setRequired(true);
        $e->setValue("delete_application");
        $this->addElement($e, "_internal");
        
        $e = $this->create("application_id", "hidden");
        $e->setRequired(true);
        $e->setValue($this->getId());
        $this->addElement($e, "_internal");
        
        $e = $this->create("redirect_to", "hidden");
        $this->addElement($e, "_internal");

        $m = sprintf(__("Yes, please delete application '%s'.", "wpjobboard"), $this->getObject()->applicant_name);
        
        $e = $this->create("delete_application", "checkbox");
        $e->setLabel(__("Delete This Application?", "wpjobboard"));
        $e->addOption(1, 1, $m);
        $e->addFilter(new Daq_Filter_Int());
        $e->setRequired(true);
        $this->addElement($e, "default");

        apply_filters("wpjb_form_init_application_delete", $this);

    }

	
}
?>