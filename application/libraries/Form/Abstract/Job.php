<?php
/**
 * Description of Abstract_Job
 *
 * @author greg
 * @package 
 */

abstract class Wpjb_Form_Abstract_Job extends Daq_Form_ObjectAbstract
{
    protected $_model = "Wpjb_Model_Job";

    protected $_tags = array();
    
    protected $_custom = null;
    
    protected $_key = "job";
    
    public function __construct( $id = null, $options = array() )
    {
        $job = new Wpjb_Model_Job($id);
        
        if( isset( $job->meta->form_code ) && $job->meta->form_code->value() != null ) {
            $this->_custom = $job->meta->form_code->value();
        } elseif( is_admin() && Daq_Request::getInstance()->get("form_code", null) ) {
            $this->_custom = Daq_Request::getInstance()->get("form_code", null);
        } elseif( isset( $options['custom'] ) ) {
            if( $options['custom'] == "wpjb_form_job") {
                $this->_custom = "wpjb_form_job";
            } elseif( strpos( $options['custom'], "wpjb_form_job" ) !== 0 ) {
                $this->_custom = "wpjb_form_job_" . $options['custom'];
            } else {
                $this->_custom = $options['custom'];
            }
        } else {
            $forms_list = get_option( "wpjb_forms_list" ); 
            $this->_custom = "wpjb_form_job";
                   
            if( isset( $forms_list['job'] ) ) {
                foreach( $forms_list['job'] as $form_code ) {

                    $form_detail = maybe_unserialize( get_option( $form_code ) );

                    if( /*$form_detail['config']['form_is_default'] && */ $form_detail['config']['form_is_active'] ) {
                        $this->_custom = "wpjb_form_job_" . $form_detail['config']['form_code'];
                        break;
                    }
                } 
            }
        }
            
        parent::__construct($id, $options);
    }
    
    public function _exclude()
    {
        if($this->_object->getId()) {
            return array("id" => $this->_object->getId());
        } else {
            return array();
        }
    }

    protected function _getListingArr()
    {
        $query = new Daq_Db_Query();
        return $query->select("t.*")
            ->from("Wpjb_Model_Pricing t")
            ->order("title")
            ->where("price_for = ?", Wpjb_Model_Pricing::PRICE_SINGLE_JOB)
            ->execute();
    }

    public function init()
    {
        $this->_upload = array(
            "path" => wpjb_upload_dir("{object}", "{field}", "{id}", "basedir"),
            "object" => "job",
            "field" => null,
            "id" => wpjb_upload_id($this->getId())
        );
        
        if($this->isNew()) {
            $types = array();
            $categories = array();
        } else {
            $types = $this->_object->getTagIds("type");
            $categories = $this->_object->getTagIds("category");
        }
        
        $this->addGroup("job", __("Job Information", "wpjobboard"));
        $this->addGroup("company", __("Company Information", "wpjobboard"));
        $this->addGroup("location", __("Location", "wpjobboard"));
        $this->addGroup("coupon", __("Listing", "wpjobboard"));
        
        $e = $this->create("form_code", "hidden");
        $e->setValue( $this->_custom );
        $this->addElement( $e, "job" );

        $e = $this->create("company_name");
        $e->setRequired(true);
        $e->setLabel(__("Company Name", "wpjobboard"));
        $e->setValue($this->_object->company_name);
        $this->addElement($e, "company");

        $e = $this->create("company_email");
        $e->setRequired(true);
        $e->setLabel(__("Contact Email", "wpjobboard"));
        $e->addValidator(new Daq_Validate_Email());
        $e->setValue($this->_object->company_email);
        $this->addElement($e, "company");

        $e = $this->create("company_url");
        $e->setLabel(__("Website", "wpjobboard"));
        $e->addValidator(new Daq_Validate_Url());
        $e->addFilter(new Daq_Filter_WP_Url());
        $e->setValue($this->_object->company_url);
        $this->addElement($e, "company");

        $e = $this->create("company_logo", "file");
        $e->setLabel(__("Logo", "wpjobboard"));
        $e->addValidator(new Daq_Validate_File_Default());
        $e->addValidator(new Daq_Validate_File_Ext("jpg,jpeg,gif,png"));
        $e->addValidator(new Daq_Validate_File_Size(300000));
        $e->setUploadPath($this->_upload);
        $e->setRenderer("wpjb_form_field_upload");
        $this->addElement($e, "company");

        $e = $this->create("id", "hidden");
        $e->setValue($this->_object->id);
        $e->addFilter(new Daq_Filter_Int());
        $this->addElement($e, "job");

        $e = $this->create("job_title");
        $e->setRequired(true);
        $e->setLabel(__("Title", "wpjobboard"));
        $e->setValue($this->_object->job_title);
        $e->addValidator(new Daq_Validate_StringLength(1, 120));
        $e->setAttr("placeholder", __("Enter Job Title Here", "wpjobboard"));
        $this->addElement($e, "job");

        $e = $this->create("job_description", "textarea");
        $e->setEditor(Daq_Form_Element_Textarea::EDITOR_TINY);
        $e->setRequired(true);
        $e->setLabel(__("Description", "wpjobboard"));
        $e->addClass("wpjb-textarea-wide");
        $e->setValue($this->_object->job_description);
        $this->addElement($e, "job");

        $e = $this->create("type", "select");
        $e->setLabel(__("Job Type", "wpjobboard"));
        $e->setValue($types);
        $e->addOptions(wpjb_form_get_jobtypes());
        $this->addElement($e, "job");
        $this->addTag($e);

        $e = $this->create("category", "select");
        $e->setLabel(__("Category", "wpjobboard"));
        $e->setValue($categories);
        $e->addOptions(wpjb_form_get_categories());
        $this->addElement($e, "job");
        $this->addTag($e);

        $def = wpjb_locale();
        $e = $this->create("job_country", "select");
        $e->setLabel(__("Country", "wpjobboard"));
        $e->setValue(($this->_object->job_country) ? $this->_object->job_country : $def);
        $e->addOptions(wpjb_form_get_countries());
        $e->addClass("wpjb-location-country");
        $this->addElement($e, "location");

        $e = $this->create("job_state");
        $e->setLabel(__("State", "wpjobboard"));
        $e->setValue($this->_object->job_state);
        $e->addClass("wpjb-location-state");
        $this->addElement($e, "location");

        $e = $this->create("job_zip_code");
        $e->setLabel(__("Zip-Code", "wpjobboard"));
        $e->addValidator(new Daq_Validate_StringLength(null, 20));
        $e->setValue($this->_object->job_zip_code);
        $this->addElement($e, "location");

        $e = $this->create("job_city");
        $e->setValue($this->_object->job_city);
        $e->setRequired(true);
        $e->setLabel(__("City", "wpjobboard"));
        $e->setHint(__('For example: "Chicago", "London", "Anywhere" or "Telecommute".', "wpjobboard"));
        $e->addValidator(new Daq_Validate_StringLength(null, 120));
        $e->addClass("wpjb-location-city");
        $this->addElement($e, "location");

        $e = $this->create("job_address");
        $e->setValue($this->_object->job_address);
        $e->setRequired(false);
        $e->setLabel(__("Address", "wpjobboard"));
        $e->addValidator(new Daq_Validate_StringLength(null, 255));
        $e->addClass("wpjb-location-address");
        $this->addElement($e, "location");
        
    }
    
    public function getCustom() {
        return $this->_custom;
    }

}

