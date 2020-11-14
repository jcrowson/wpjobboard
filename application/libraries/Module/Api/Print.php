<?php
/**
 * Description of Plain
 *
 * @author greg
 * @package 
 */

class Wpjb_Module_Api_Print extends Daq_Controller_Abstract
{
    public function indexAction() {
        
        $id = absint( $_GET['id'] );
        $application = new Wpjb_Model_Application( $id );
        
        if( !$application->exists() ) {
            throw new Exception( sprintf( "Object with ID %d does not exist.", $id ) );
        }
        
        $job = new Wpjb_Model_Job($application->job_id);
        $company = Wpjb_Model_Company::current();
        
        if( ( $company == null || $company->id != $job->employer_id ) && !current_user_can( 'manage_options' ) ) {
            throw new Exception( sprintf( "You do not own Application with ID %d.", $id ) );
        }

        $current_status = wpjb_get_application_status($application->status);
        
        $view = new Wpjb_Shortcode_Dynamic();
        $view->view->application = $application;
        $view->view->company = $company;
        $view->view->current_status = $current_status;
        $view->view->job = $job;
        
        $render = $view->render("default", "print");
        
        echo apply_filters( "wpjb_print_application", $render, $application );
        exit;       
    }
    
    public function multipleAction() {
        
        $ids = json_decode( base64_decode( $_GET['id'] ) );

        if( !current_user_can( 'manage_options' ) ) {
            throw new Exception( sprintf( "You do not own Application with ID %d.", $id ) );
        }

        $view = new Wpjb_Shortcode_Dynamic();
        $view->view->ids = $ids;
        
        $render = $view->render("default", "print_multiple");
        
        echo $render;
        exit;      
    }

}

?>
