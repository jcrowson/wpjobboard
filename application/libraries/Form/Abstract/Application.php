<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Application
 *
 * @author Grzegorz
 */
class Wpjb_Form_Abstract_Application extends Daq_Form_ObjectAbstract 
{
    protected $_custom = "wpjb_form_apply";
    
    protected $_key = "apply";

    protected $_model = "Wpjb_Model_Application";
    
    public function __construct( $id = null, $options = array() )
    {
        $application = new Wpjb_Model_Application($id);
        
        if( isset( $application->meta->form_code ) && $application->meta->form_code->value() != null ) {
            $this->_custom = $application->meta->form_code->value();
        } elseif( is_admin() && Daq_Request::getInstance()->get("form_code", null) ) {
            $this->_custom = Daq_Request::getInstance()->get("form_code", null);
        } elseif( isset( $options['custom'] ) ) {
            if( $options['custom'] == "wpjb_form_apply") {
                $this->_custom = "wpjb_form_apply";
            } elseif( strpos( $options['custom'], "wpjb_form_apply" ) !== 0 ) {
                $this->_custom = "wpjb_form_apply_" . $options['custom'];
            } else {
                $this->_custom = $options['custom'];
            }
        } else {
            $forms_list = get_option( "wpjb_forms_list" ); 
            $this->_custom = "wpjb_form_apply";
            
            if( isset( $forms_list['apply'] ) ) {
                foreach( $forms_list['apply'] as $form_code ) {

                    $form_detail = maybe_unserialize( get_option( $form_code ) );

                    if( /*$form_detail['config']['form_is_default'] && */ $form_detail['config']['form_is_active'] ) {
                        $this->_custom = "wpjb_form_apply_" . $form_detail['config']['form_code'];
                        break;
                    }
                } 
            }
        }
        
        parent::__construct($id, $options);
    }
    
    public function init()
    {
        $this->_upload = array(
            "path" => wpjb_upload_dir("{object}", "{field}", "{id}", "basedir"),
            "object" => "application",
            "field" => null,
            "id" => wpjb_upload_id($this->getId())
        );
        
        $this->addGroup("apply", __("Apply", "wpjobboard"));
        
        $e = $this->create("form_code", "hidden");
        $e->setValue( $this->_custom );
        $this->addElement( $e, "apply" );

        $e = $this->create("applicant_name");
        $e->addFilter(new Daq_Filter_Trim());
        $e->setLabel(__("Your name", "wpjobboard"));
        $e->setRequired(true);
        $e->setValue($this->_object->applicant_name);
        $this->addElement($e, "apply");

        $e = $this->create("email");
        $e->setLabel(__("Your e-mail address", "wpjobboard"));
        $e->setRequired(true);
        $e->addValidator(new Daq_Validate_Email());
        $e->setValue($this->_object->email);
        $this->addElement($e, "apply");
        
        $e = $this->create("message", "textarea");
        $e->setLabel(__("Message", "wpjobboard"));
        $e->setValue($this->_object->message);
        $this->addElement($e, "apply");
                
        $e = $this->create("file", "file");
        /* @var $e Daq_Form_Element_File */
        $e->setLabel(__("Attachments", "wpjobboard"));
        $e->setUploadPath($this->_upload);
        $e->setRenderer("wpjb_form_field_upload");
        $e->addValidator(new Daq_Validate_File_Ext("pdf,doc,docx,txt"));
        $e->setMaxFiles(10);
        $this->addElement($e, "apply");
    }
}

?>
