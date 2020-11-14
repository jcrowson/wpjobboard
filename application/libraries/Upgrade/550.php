<?php

class Wpjb_Upgrade_550 extends Wpjb_Upgrade_Abstract
{
    public function getVersion()
    {
        return "5.5.0";
    }

    public function execute()
    {
        global $wp_rewrite;
        
        $this->sql();
        $this->fixForms();


        if($wp_rewrite instanceof WP_Rewrite) {
            $wp_rewrite->flush_rules();
        }
        return;
    }
    
    public function fixForms() {
        
        $forms = get_option("wpjb_forms_list", array());

        if( empty( $forms ) ) {
            $all_forms = array(
                'job'                   => "wpjb_form_job",
                'company'               => "wpjb_form_company",
                'resume'                => "wpjb_form_resume",
                'apply'                 => "wpjb_form_apply",
                "job_search"            => "wpjb_form_job-search",
                "resume_search"         => "wpjb_form_resume-search",
                //"job_list_search"     => "wpjb_form_job_list_search"
            );

            foreach( $all_forms as $key => $form_key ) {
                $form = get_option( $form_key, null );
                if( $form != null ) {

                    $valid_key = str_replace( "-", "_", $form_key ) . "_default";
                    
                    $form['config'] = array(
                        'form_label'        => __( "Default", "wpjobboard" ),
                        'form_code'         => __( "default", "wpjobboard" ),
                        'form_is_active'    => 1,
                    );
                    
                    update_option( $valid_key, $form );

                    $forms[$key] = array();
                    $forms[$key][] = $valid_key;
                }
            }

            update_option( "wpjb_forms_list", $forms );
        }
    }
}
