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
class Wpjb_Form_Frontend_EditJob extends Wpjb_Form_Abstract_Job
{
    protected $_model = "Wpjb_Model_Job";

    public function  __construct($id)
    {
        parent::__construct($id);
    }

    public function init()
    {
        parent::init();

        $this->addGroup("other", __("Other", "wpjobboard"));

        /*$e = $this->create("is_filled", "checkbox");
        $e->setLabel(__("Is Filled", "wpjobboard"));
        $e->setValue($this->getObject()->is_filled);
        $e->addOption(1, 1, __("Yes, this position is taken", "wpjobboard"));
        $e->addFilter(new Daq_Filter_Int());
        $this->addElement($e, "other");*/


        $e = $this->create("is_filled", "select");
        $e->setLabel(__("Is Filled", "wpjobboard"));
        $e->setValue($this->getObject()->is_filled);
        $e->addOption("0", "0", __("Job is open", "wpjobboard"));
        $e->addOption("1", "1", __("Job is taken. Show notification that job maybe taken.", "wpjobboard"));
        $e->addOption("2", "2", __("Job is taken. Show notification & hide application form", "wpjobboard"));
        $e->addOption("3", "3", __("Job is taken. Hide job on jobs list and job details.", "wpjobboard"));
        $this->addElement($e, "other");

        add_filter("wpjb_form_init_job", array($this, "apply"), 9);
        apply_filters("wpjb_form_init_job", $this);
        
    }
    
    public function save($append = array()) 
    {
        parent::save($append);
        
        Wpjb_Model_MetaValue::import("job", "form_code", $this->value("form_code"), $this->getId());
        
        apply_filters("wpjb_form_save_job", $this);
    }
	
}
?>