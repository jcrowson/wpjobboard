<?php
/**
 * Description of ${name}
 *
 * @author ${user}
 * @package 
 */

class Wpjb_Module_Admin_Custom extends Wpjb_Controller_Admin
{
    public function init()
    {
        
        $this->_virtual = apply_filters( "wpjb_bulk_actions_functions", array(
           "redirectAction" => array(
               "accept" => array("form", "code"),
               "object" => "custom"
           ),
        ), "custom" );
        
    }

    private function _sort($a, $b)
    {
        if($a["order"]>$b["order"]) {
            return 1;
        } elseif($a["order"]==$b["order"]) {
            return 0;
        } else {
            return -1;
        }
    }

    private function _getGroup()
    {
        $group = $this->_request->getParam("group");

        uasort($group, array($this, "_sort"));
        foreach($group as $key => $gr) {
            $element = $gr["element"];
            if(is_array($element)) {
                uasort($element, array($this, "_sort"));
            }
            $group[$key]["element"] = $element;
        }

        return $group;
    }

    public function indexAction()
    {
        
    }
    
    public function deleteAction() 
    {
        $forms = get_option("wpjb_forms_list", array());
        $form = str_replace( "-", "_", $this->_request->get("form") );
        $code = str_replace( "-", "_", $this->_request->get("code") );

        if ( ( $key = array_search( "wpjb_form_" . $form . "_" . $code, $forms[$form] ) ) !== false ) {
            unset( $forms[$form][$key] );
            update_option( "wpjb_forms_list", $forms );
            delete_option( "wpjb_form_" . $form . "_" . $code );
            
            $m = sprintf(__("Form with code '%s' for %s deleted.", "wpjobboard"), $code, $form);
            $this->view->_flash->addInfo($m);
        }

        //wp_redirect( wpjb_admin_url( "custom" ) );
        $this->redirect( wpjb_admin_url( "custom" ) );
        exit;
    }

    public function editAction()
    {
        
        $form_code = rtrim( "wpjb_form_".$this->_request->get("form")."_".str_replace( "-", "_", $this->_request->get("code") ), "_" );
        
        switch($this->_request->get("form")) {
            case "job" : 
                $form = new Wpjb_Form_AddJob( null, array("display_trashed"=>true,"custom"=>$form_code)); 
                $this->view->formTitle = __("Add/Edit Job Form", "wpjobboard");
                $this->view->toolbox = "default";
                break;
            case "apply" : 
                $form = new Wpjb_Form_Apply( null, array("display_trashed"=>true,"custom"=>$form_code)); 
                $this->view->formTitle = __("Apply Online Form", "wpjobboard");
                $this->view->toolbox = "default";
                break;
            case "job-search": 
                $form = new Wpjb_Form_AdvancedSearch( array("display_trashed"=>true), array("display_trashed"=>true,"custom"=>$form_code)); 
                $form->customFields();
                $this->view->formTitle = __("Advanced Job Search Form", "wpjobboard");
                $this->view->toolbox = "search";
                break;
            case "job-list-search": 
                $form = new Wpjb_Form_Frontend_ListSearch( array("display_trashed"=>true), array("display_trashed"=>true,"custom"=>$form_code)); 
                $form->customFields();
                $this->view->formTitle = __("Job List Search Form", "wpjobboard");
                $this->view->toolbox = "search";
                break;
            case "company": 
                $form = new Wpjb_Form_Frontend_Company( null, array("display_trashed"=>true,"custom"=>$form_code));
                $this->view->formTitle = __("Company Form", "wpjobboard");
                $this->view->toolbox = "default";
                break;
            case "resume":
                $form = new Wpjb_Form_Resume(null, array("display_trashed"=>true,"custom"=>$form_code)); 
                $this->view->formTitle = __("My Resume Form", "wpjobboard");
                $this->view->toolbox = "default";
                break;
            case "resume-search": 
                $form = new Wpjb_Form_ResumesSearch(array("display_trashed"=>true), array("display_trashed"=>true,"custom"=>$form_code)); 
                $form->customFields();
                $this->view->formTitle = __("Advanced Resume Search Form", "wpjobboard");
                $this->view->toolbox = "search";
                break;
            default: 
                $this->view->formTitle = __("Incorrect Form Name", "wpjobboard");
                return;
        }
 
        $f = str_replace("-", "_", $this->_request->get("form") );
        
        //$formDetail = maybe_unserialize( get_option( rtrim( "wpjb_form_".$f."_".$this->_request->get("code"), "_" ) , array() ) );
        
        $form_code = get_option( rtrim( "wpjb_form_".$f."_".$this->_request->get("code"), "_" ) , "" );
        if( $form_code == false ) {
            $form_code = $form->getDefaultForm();
        }
        $formDetail = maybe_unserialize( $form_code );  
        
        $dump = $form->dump();

        if( $form->isDefaultForm() ) {
            foreach( $dump as $group ) {
                foreach( $group->field as $field ) {

                    $field->is_trashed = true;
                    if( array_key_exists( $field->name, $formDetail["field"] ) ) {   
                        $field->is_trashed = false;
                    }
                }
            }
        }

        $config = array(
            "form_label"        => "",
            "form_code"         => "",
            "form_is_active"    => 0,
        );
        if( isset( $formDetail['config'] ) ) {
            $config = $formDetail['config'];
        }
        
        $this->view->form = $dump;
        $this->view->formName = str_replace("-", "_", $this->_request->get("form"));
        $this->view->formConfig = $config;
       
    }

    private function _handle($form, $param)
    {
        $this->_forced($form);

        if($this->isPost() && $this->hasParam("reset")) {
            $conf = Wpjb_Project::getInstance();
            $conf->setConfigParam($param, null);
            $conf->saveConfig();
            $this->view->_flash->addInfo(__("Form layout has been reset.", "wpjobboard"));
        }
        elseif($this->isPost()) {
            $conf = Wpjb_Project::getInstance();
            $conf->setConfigParam($param, $this->_getGroup());
            $conf->saveConfig();
            $this->view->_flash->addInfo(__("Form layout has been saved.", "wpjobboard"));
        }

        $form = new $form(null, false);
        $this->view->scheme = $form->getFinalScheme();
    }

    private function _forced($form)
    {
        $arr = array(
            "Wpjb_Form_Apply" => array(),
            "Wpjb_Form_Admin_Resume" => array(),
            "Wpjb_Form_AddJob" => array("job_type", "job_category", "category_id")
        );

        $this->view->forced = $arr[$form];
    }
}

?>
