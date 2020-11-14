<?php

class Wpjb_Form_Admin_Config_GoogleMaps extends Daq_Form_Abstract
{
    public $name = null;

    public function init()
    {
        $this->name = __("Google Maps", "wpjobboard");
        $instance = Wpjb_Project::getInstance();
        
        $this->addGroup( "default", __( "Google Maps", "wpjobboard" ) );
        $this->addGroup( "pins", __( "Google Maps Category Pins", "wpjobboard" ) );
        $this->addGroup( "cluster", __( "Google Maps Clusters", "wpjobboard" ) );

        
        /*$e = $this->create( "wpjb_map_on_job_list", "checkbox" );
        $e->setValue( $instance->getConfig( "wpjb_map_on_job_list" ) );
        $e->setLabel( __( "Google API Key", "wpjobboard" ) );
        $e->addOption( "1", "1", __( "Show map in [wpjb_jobs_list]", "wpjobboard" ) );
        $this->addElement($e, "default");*/

        $e = $this->create( "wpjb_map_default_pin", "medialibrary" );
        $e->setValue( $instance->getConfig( "wpjb_map_default_pin" ) );
        $e->setLabel( __( "Default Pin", "wpjobboard" ) );
        $this->addElement($e, "default");


        foreach( wpjb_get_categories() as $category ) {

            $e = $this->create( "wpjb_map_pin_" . $category->id, "medialibrary" );
            $e->setValue( $instance->getConfig( "wpjb_map_pin_" . $category->id ) );
            $e->setLabel( $category->title );
            $this->addElement($e, "pins");
        }

        for( $i = 1 ; $i < 6 ; $i++ ) {
            $e = $this->create( "wpjb_map_cluster_bg_" . $i, "medialibrary" );
            $e->setValue( $instance->getConfig( "wpjb_map_cluster_bg_" . $i ) );
            $e->setLabel( sprintf( __( "Cluster %d image", "wpjobboard" ), $i ) );
            $this->addElement($e, "cluster");

            $e = $this->create( "wpjb_map_cluster_text_color_" . $i, "text" );
            $e->setValue( $instance->getConfig( "wpjb_map_cluster_text_color_" . $i, "#ffffff" ) );
            $e->setLabel( sprintf( __( "Cluster %d text color", "wpjobboard" ), $i ) );
            $e->addFilter(new Daq_Filter_Trim("#"));
            $e->setRenderer( "wpjb_form_field_colorpicker" );
            $this->addElement($e, "cluster");
        }
        
        apply_filters("wpja_form_init_config_google_maps", $this);
    }
    
    public function fieldApiKey(Daq_Form_Element $field, $form) 
    {
        $button = new Daq_Helper_Html("a", array(
            "id" => "wpjb-google-api-validate",
            "class" => "button-secondary",
            "href" => "#"
        ), __("Check"));
        return $field->render() . $button->render();
    }
}

?>
