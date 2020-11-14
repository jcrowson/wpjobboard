<?php
/**
 * Description of Element
 *
 * @author greg
 * @package
 */

abstract class Daq_Form_Abstract
{
    private $_default = "_default";

    private $_default_forms = array( "wpjb_form_job", "wpjb_form_apply", "wpjb_form_company", "wpjb_form_resume" );
    
    protected $_options = array();
    
    protected $_field = array();

    protected $_errors = array();

    protected $_renderer = null;

    protected $_group = array();
    
    protected $_css = array();
    
    protected $_custom = null;
    
    protected $_key = null;
    
    protected $_overload = null;
    
    protected $_upload = null;
    
    private $_order = 1;

    /**
     * Form constructor
     * 
     * Allowed params:
     * - renderer: default: Daq_Form_AdminRenderer
     * - display_trashed: default=false
     *
     * @param Array $options 
     */
    public function __construct($options = array())
    {
        $defaults = array(
            "renderer" => new Daq_Form_AdminRenderer,
            "display_trashed" => false,
        );
        
        $this->_options = array_merge($defaults, (array)$options);
        $this->_renderer = $this->_options["renderer"];
        
        if(!empty($this->_custom)) {
            $this->_overload = new Daq_Form_Overload($this->_custom);
        }

        $this->init();
        
        /*
        if(!empty($this->_custom)) {
            $this->loadGroups();
            $this->loadMeta($this->_key);
        }
         * 
         */
    }
    
    public function apply()
    {
        if(!empty($this->_custom)) {
            $this->loadGroups();
            $this->loadMeta($this->_key);
        }
        
        return $this;
    }
    
    public function __get($key) 
    {
        if($key == "fieldset") {
            return $this->_group;
        }
    }

    public function loadGroups()
    {
        foreach((array)$this->_overload->getGroup() as $g) {
            if(!isset($this->fieldset[$g["name"]]) && !$g["is_builtin"]) {
                $this->addGroup($g["name"], $g["title"], $g["order"]);
            }
        }
    }
    
    public function loadMeta($key)
    {
        $list = array(
            "ui-input-label" => "label",
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
            if($this->_upload) {
                $data["upload_path"] = $this->_upload;
            }
            
            $tag = $list[$data["type"]];          
            $e = $this->create($meta->name, $tag);
            $e->overload($data);
            $e->setBuiltin(false);
            $this->addElement($e, $data["group"], in_array( $this->_custom, $this->_default_forms ) );
        }
    }
    
    public function addElement(Daq_Form_Element $field, $group = null, $force_trash = false )
    {
        
        if($group === null) {
            $group = $this->_default;
        }
        
        if($field->getOrder()<1) {
            $field->setOrder($this->_order);
            $this->_order++;
        }
        
        $ol = null;
        $visibility = null;
        $trashed = $field->isTrashed();
        if($this->_overload && $this->_overload->hasField($field)) {
            $ol = $this->_overload->getField($field);
            
            if($this->_upload) {
                $ol["upload_path"] = $this->_upload;
            }
            
            $field->overload($ol);
            $group = $ol["group"];
            $trashed = isset($ol["is_trashed"]) && $ol["is_trashed"];
            
            if(isset($ol["visibility"]) && is_numeric($ol["visibility"])) {
                $visibility = $ol["visibility"];
            }
        }

        

        if( $force_trash ) {
            $trashed = true;
        }

        if($trashed && !$this->_options["display_trashed"]) {
            return;
        }
        
        if($visibility == 2 && !is_admin()) {
            return;
        }

        if($trashed) {
            $group = "_trashed";
        } elseif(!isset($this->fieldset[$group]) && !$this->_overload) {
            $this->addGroup($group, "");
        } elseif(!isset($this->fieldset[$group]) && $this->_overload->hasGroup($group)) {
            $g = $this->_overload->getGroup($group);
            $this->addGroup($group, $g["title"], $g["order"]);
            $group = $g["name"];
        } elseif(!isset($this->fieldset[$group])) {
            $group = "_trashed";
            $field->setTrashed(true);
        } 

        if($group == "_trashed" && !isset($this->fieldset["_trashed"])) {
            $this->addGroup("_trashed", "_trashed", 9000);
        }

        $this->fieldset[$group]->add($field);
    }

    public function addGroup($key, $title = "", $order = null)
    {   
        if($key == "") {
            echo "<pre>";
            debug_print_backtrace();
            echo "</pre>";
        }
        if(is_null($order)) {
            $order = $this->_order++;
        }
        
        if($this->_overload && $this->_overload->hasGroup($key)) {
            $init = $this->_overload->getGroup($key);
        } else {
            $init = array(
                "name" => $key,
                "title" => $title,
                "order" => $order,
                "is_builtin" => true,
                "is_trashed" => false,
            );
        }
        
        $this->_group[$key] = new Daq_Form_Fieldset($init);
    }

    public function hasElement($name)
    {
        if($this->getElement($name) === null) {
            return false;
        } else {
            return true;
        }
    }

    public function getElement($name)
    {
        foreach($this->fieldset as $k => $v) {
            if($this->fieldset[$k]->has($name)) {
                return $this->fieldset[$k]->get($name);
            }
        }
        
        return null;
    }

    /**
     * Return all form elements
     *
     * @deprecated
     * @return array
     */
    public function getElements()
    {
        throw new Exception("This method is obsolate!");
    }

    public function removeElement($name)
    {
        foreach($this->fieldset as $k => $v) {
            if($this->fieldset[$k]->has($name)) {
                $this->fieldset[$k]->remove($name);
                return true;
            }
        }
        return false;
    }

    /**
     * Returns group object or null
     * 
     * @since 4.4.3
     * @param string $name Group name
     * @return Daq_Form_Fieldset Group object
     */
    public function getGroup($name)
    {
        if(isset($this->_group[$name])) {
            return $this->_group[$name];
        } else {
            return null;
        }
    }
    
    /**
     * Returns all form groups
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->_group;
    }
    
    /**
     * Remove one or more groups
     *
     * @param mixed $group String or array of group names
     */
    public function removeGroup($group)
    {
        $group = (array)$group;
        foreach($group as $g) {
            if(isset($this->_group[$g])) {
                unset($this->_group[$g]);
            }
        }
    }
    
    /**
     * Returns all groups that have at least one element
     * 
     * @return array
     */
    public function getNonEmptyGroups()
    {
        $groups = array();
        foreach($this->fieldset as $g) {
            
            if($g->hasVisibleElements()) {
                $groups[] = $g;
            }
        }

        return $groups;
    }
    
    public function getFields()
    {
        $fields = array();
        foreach($this->fieldset as $group) {
            $fields += $group->getAll();
        }
        return $fields;
    }

    /**
     * Validates the form
     *
     * @param array $values
     * @return boolean
     */
    public function isValid(array $values)
    {
        $isValid = true;

        if( ! current_user_can( 'unfiltered_html' ) ) {
            foreach( $values as $k => $val ) {
                if( $this->hasElement( $k ) && $this->getElement( $k )->getType() != 'textarea' ) {
                    if( is_array( $val ) ) {
                        $values[$k] = array_map( 'wp_kses', $val, array('post') );
                    } else {
                        $values[$k] = wp_kses( $val, 'post' );
                    }
                } else {
                    $values[$k] = $val;
                }
            }
        }
        
        foreach($this->getFields() as $field)
        {
            
            $value = null;
            if(isset($values[$field->getName()])) {
                $value = $values[$field->getName()];
            } elseif($field->getType() == "checkbox") {
                $value = null;
            }

            if($field->getType() == Daq_Form_Element::TYPE_FILE) {
                if(isset($_FILES[$field->getName()])) {
                    $field->setValue($_FILES[$field->getName()]);
                }
            } else {
                $field->setValue($value);
            }

            if(!$field->validate()) {
                $isValid = false;
                $this->_errors[$field->getName()] = array();
                foreach($field->getErrors() as $error) {
                    $this->_errors[$field->getName()][] = $error;
                }
            }
        }
        
        return $isValid;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    public function getValues()
    {
        $arr = array();
        foreach($this->getFields() as $field) {
            $arr[$field->getName()] = $field->getValue();
        }

        return $arr;
    }

    protected function _sort($a, $b)
    {
        $r1 = $a->getOrder();
        $r2 = $b->getOrder();
        
        if($r1>$r2) {
            return 1;
        } else {
            return -1;
        }
    }
    
    public function getReordered()
    {
        $fieldset = $this->fieldset;
        usort($fieldset, array($this, "_sort"));
        
        $trashed = false;
        if(isset($this->_options["display_trashed"]) && $this->_options["display_trashed"]) {
            $trashed = true;
        }

        $fs = array();
        foreach($fieldset as $f) {
            if(((!$f->isEmpty() || $f->isAlwaysVisible()) || $trashed) && $f->getName()!="_internal") {
                $fs[$f->getName()] = clone $f;
            }
        }
        
        return $fs;
    }
    
    public function render($options = array())
    {
        if(isset($options["group"])) {
            $groups = array($this->fieldset[$options["group"]]);
        } else {
            $groups = $this->getReordered();
        }
        
        return $this->renderHidden()."\r\n".daq_form_layout_config($groups);
    }

    public function renderGroup($group)
    {
        if(isset($this->fieldset[$group])) {
            return $this->render($this->fieldset[$group]);
        }

        return null;
    }

    public function renderHidden( $exclude_hidden = array() )
    {
        $html = "";
        foreach($this->fieldset as $fieldset) {
            foreach($fieldset->getAll() as $field) {
                if($field->getType() === Daq_Form_Element::TYPE_HIDDEN && !in_array( $field->getName(), $exclude_hidden ) ) {
                    $html .= $field->render();
                } 
            }
        }
        return $html;
    }

    public function setRenderer($renderer)
    {
        $this->_renderer = $renderer;
    }

    public function getRenderer()
    {
        return $this->_renderer;
    }
    
    public function dump()
    {
        $arr = array();
        foreach($this->getReordered() as $group) {
            /* @var $group Daq_Form_Fieldset */
            
            if($group->getName() == "_internal") {
                continue;
            }

            $std = new stdClass();
            $std->title = $group->title;
            $std->name = $group->getName();
            $std->order = $group->getOrder();
            $std->type = "ui-input-group";
            $std->is_builtin = (int)$group->meta->is_builtin;
            $std->is_trashed = $group->isTrashed();
            $std->field = array();
            
            foreach($group->getReordered() as $field) {
                /* @var $field Daq_Form_Element */
                $std->field[] = $field->dump();
            }
            
            $arr[] = $std;
        }
        
        return $arr;
    }
    
    /**
     * Fail safe method to get field value
     *
     * @param string $element
     * @return mixed String or array depending on the field type
     */
    public function value($element)
    {
        if(!$this->hasElement($element)) {
            return null;
        }
        
        return $this->getElement($element)->getValue();
    }
    
    /**
     *
     * @param string $name
     * @param string $type
     * @return Daq_Form_Element 
     */
    public function create($name, $type = "text")
    {
        
       if(!$type) {
            $type = "text";
        }
        
        $type = str_replace("_", " ", $type);
        $type = ucwords($type);
        $type = str_replace(" ", "_", $type);
        
        $class = "Daq_Form_Element_".$type;
        
        return new $class($name);
    }

    public function getFieldValue($field, $default = null) 
    {
        if($this->hasElement($field)) {
            return $this->getElement($field)->getValue();
        } else {
            return $default;
        }
    }
    
    public function getOptions() {
        return $this->_options;
    }
    
    public function getOption($name) {
        foreach($this->_options as $key => $option) {
            if($name == $key) {
                return $option;
            }
        }
        
        return null;
    }
    
    public function getGlobalError() {
        return apply_filters("wpjb_form_global_error", __("There are errors in your form.", "wpjobboard"), $this);
    }
    

    public function isDefaultForm() {
        return in_array( $this->_custom, $this->_default_forms );
    }

    public function getDefaultForm() {

        switch( $this->_custom ) {
            case "wpjb_form_job": 
                $form = 'a:3:{s:5:"field";a:15:{s:9:"job_title";a:16:{s:5:"title";s:5:"Title";s:4:"name";s:9:"job_title";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:20:"Enter Job Title Here";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:0;s:5:"group";s:3:"job";}s:15:"job_description";a:15:{s:5:"title";s:11:"Description";s:4:"name";s:15:"job_description";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:18:"wpjb-textarea-wide";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-textarea";s:16:"textarea_wysiwyg";i:1;s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:1;s:5:"group";s:3:"job";}s:4:"type";a:18:{s:5:"title";s:8:"Job Type";s:4:"name";s:4:"type";s:4:"hint";s:0:"";s:7:"default";a:0:{}s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:2;s:5:"group";s:3:"job";}s:8:"category";a:18:{s:5:"title";s:8:"Category";s:4:"name";s:8:"category";s:4:"hint";s:0:"";s:7:"default";a:0:{}s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:3;s:5:"group";s:3:"job";}s:12:"company_name";a:16:{s:5:"title";s:12:"Company Name";s:4:"name";s:12:"company_name";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:5;s:5:"group";s:7:"company";}s:13:"company_email";a:17:{s:5:"title";s:13:"Contact Email";s:4:"name";s:13:"company_email";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:16:"validation_rules";s:18:"Daq_Validate_Email";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:6;s:5:"group";s:7:"company";}s:11:"company_url";a:17:{s:5:"title";s:7:"Website";s:4:"name";s:11:"company_url";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:16:"validation_rules";s:16:"Daq_Validate_Url";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:7;s:5:"group";s:7:"company";}s:12:"company_logo";a:16:{s:5:"title";s:4:"Logo";s:4:"name";s:12:"company_logo";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-file";s:8:"file_ext";s:19:"jpg, jpeg, gif, png";s:9:"file_size";i:300000;s:8:"file_num";i:1;s:8:"is_saved";i:1;s:5:"order";i:8;s:5:"group";s:7:"company";s:8:"renderer";s:22:"wpjb_form_field_upload";}s:11:"job_country";a:18:{s:5:"title";s:7:"Country";s:4:"name";s:11:"job_country";s:4:"hint";s:0:"";s:7:"default";s:3:"840";s:3:"css";s:21:"wpjb-location-country";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:9;s:5:"group";s:8:"location";}s:9:"job_state";a:16:{s:5:"title";s:5:"State";s:4:"name";s:9:"job_state";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:19:"wpjb-location-state";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:10;s:5:"group";s:8:"location";}s:12:"job_zip_code";a:16:{s:5:"title";s:8:"Zip-Code";s:4:"name";s:12:"job_zip_code";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:11;s:5:"group";s:8:"location";}s:8:"job_city";a:16:{s:5:"title";s:4:"City";s:4:"name";s:8:"job_city";s:4:"hint";s:62:"For example: "Chicago", "London", "Anywhere" or "Telecommute".";s:7:"default";s:0:"";s:3:"css";s:18:"wpjb-location-city";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:12;s:5:"group";s:8:"location";}s:7:"listing";a:16:{s:5:"title";s:12:"Listing Type";s:4:"name";s:7:"listing";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:14:"ui-input-radio";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:8:"is_saved";i:1;s:5:"order";i:13;s:5:"group";s:6:"coupon";}i:0;a:16:{s:5:"title";s:6:"Adress";s:4:"name";s:11:"job_address";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:21:"wpjb-location-address";s:11:"is_required";s:1:"0";s:10:"is_trashed";s:1:"0";s:10:"is_builtin";s:1:"0";s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_isze";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:12;s:5:"group";s:8:"location";}s:11:"job_address";a:16:{s:5:"title";s:6:"Adress";s:4:"name";s:11:"job_address";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:21:"wpjb-location-address";s:11:"is_required";s:1:"0";s:10:"is_trashed";s:1:"0";s:10:"is_builtin";s:1:"0";s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_isze";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:12;s:5:"group";s:8:"location";}}s:5:"group";a:4:{s:3:"job";a:7:{s:5:"title";s:15:"Job Information";s:4:"name";s:3:"job";s:5:"order";i:0;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:7:"company";a:7:{s:5:"title";s:19:"Company Information";s:4:"name";s:7:"company";s:5:"order";i:1;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:8:"location";a:7:{s:5:"title";s:8:"Location";s:4:"name";s:8:"location";s:5:"order";i:2;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:6:"coupon";a:7:{s:5:"title";s:7:"Listing";s:4:"name";s:6:"coupon";s:5:"order";i:3;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}}s:6:"config";a:3:{s:10:"form_label";s:0:"";s:9:"form_code";s:0:"";s:14:"form_is_active";s:1:"0";}}';
                break;
            case "wpjb_form_company":
                $form = 'a:3:{s:5:"field";a:11:{s:10:"user_email";a:16:{s:5:"title";s:6:"E-mail";s:4:"name";s:10:"user_email";s:4:"hint";s:0:"";s:7:"default";s:17:"admin@example.com";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:1;s:5:"group";s:7:"default";}s:12:"company_name";a:16:{s:5:"title";s:12:"Company Name";s:4:"name";s:12:"company_name";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:2;s:5:"group";s:7:"default";}s:14:"company_slogan";a:16:{s:5:"title";s:14:"Company Slogan";s:4:"name";s:14:"company_slogan";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:3;s:5:"group";s:7:"default";}s:12:"company_logo";a:16:{s:5:"title";s:12:"Company Logo";s:4:"name";s:12:"company_logo";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-file";s:8:"file_ext";s:19:"jpg, jpeg, gif, png";s:9:"file_size";i:300000;s:8:"file_num";i:1;s:8:"is_saved";i:1;s:5:"order";i:4;s:5:"group";s:7:"default";s:8:"renderer";s:22:"wpjb_form_field_upload";}s:15:"company_website";a:17:{s:5:"title";s:15:"Company Website";s:4:"name";s:15:"company_website";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:16:"validation_rules";s:16:"Daq_Validate_Url";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:5;s:5:"group";s:7:"default";}s:12:"company_info";a:15:{s:5:"title";s:12:"Company Info";s:4:"name";s:12:"company_info";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-textarea";s:16:"textarea_wysiwyg";i:1;s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:6;s:5:"group";s:7:"default";}s:9:"is_public";a:16:{s:5:"title";s:15:"Publish Profile";s:4:"name";s:9:"is_public";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-checkbox";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:8:"is_saved";i:1;s:5:"order";i:7;s:5:"group";s:7:"default";}s:15:"company_country";a:18:{s:5:"title";s:15:"Company Country";s:4:"name";s:15:"company_country";s:4:"hint";s:0:"";s:7:"default";s:3:"840";s:3:"css";s:21:"wpjb-location-country";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:8;s:5:"group";s:8:"location";}s:13:"company_state";a:16:{s:5:"title";s:13:"Company State";s:4:"name";s:13:"company_state";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:19:"wpjb-location-state";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:9;s:5:"group";s:8:"location";}s:16:"company_zip_code";a:16:{s:5:"title";s:16:"Company Zip-Code";s:4:"name";s:16:"company_zip_code";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:10;s:5:"group";s:8:"location";}s:16:"company_location";a:16:{s:5:"title";s:16:"Company Location";s:4:"name";s:16:"company_location";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:18:"wpjb-location-city";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:11;s:5:"group";s:8:"location";}}s:5:"group";a:2:{s:7:"default";a:7:{s:5:"title";s:7:"Company";s:4:"name";s:7:"default";s:5:"order";i:0;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:8:"location";a:7:{s:5:"title";s:8:"Location";s:4:"name";s:8:"location";s:5:"order";i:1;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}}s:6:"config";a:3:{s:10:"form_label";s:0:"";s:9:"form_code";s:0:"";s:14:"form_is_active";s:1:"0";}}';
                break;
            case "wpjb_form_apply":
                $form = 'a:3:{s:5:"field";a:4:{s:14:"applicant_name";a:16:{s:5:"title";s:9:"Your name";s:4:"name";s:14:"applicant_name";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:0;s:5:"group";s:5:"apply";}s:5:"email";a:17:{s:5:"title";s:19:"Your e-mail address";s:4:"name";s:5:"email";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:16:"validation_rules";s:18:"Daq_Validate_Email";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:1;s:5:"group";s:5:"apply";}s:7:"message";a:15:{s:5:"title";s:7:"Message";s:4:"name";s:7:"message";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-textarea";s:16:"textarea_wysiwyg";i:0;s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:2;s:5:"group";s:5:"apply";}s:4:"file";a:15:{s:5:"title";s:11:"Attachments";s:4:"name";s:4:"file";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-file";s:8:"file_ext";s:19:"pdf, doc, docx, txt";s:8:"file_num";i:10;s:8:"is_saved";i:1;s:5:"order";i:3;s:5:"group";s:5:"apply";s:8:"renderer";s:22:"wpjb_form_field_upload";}}s:5:"group";a:1:{s:5:"apply";a:7:{s:5:"title";s:5:"Apply";s:4:"name";s:5:"apply";s:5:"order";i:0;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}}s:6:"config";a:3:{s:10:"form_label";s:0:"";s:9:"form_code";s:0:"";s:14:"form_is_active";s:1:"0";}}';
                break;
            case "wpjb_form_resume":
                $form = 'a:3:{s:5:"field";a:14:{s:10:"first_name";a:16:{s:5:"title";s:10:"First Name";s:4:"name";s:10:"first_name";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:1;s:5:"group";s:7:"default";}s:9:"last_name";a:16:{s:5:"title";s:9:"Last Name";s:4:"name";s:9:"last_name";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:2;s:5:"group";s:7:"default";}s:10:"user_email";a:16:{s:5:"title";s:13:"Email Address";s:4:"name";s:10:"user_email";s:4:"hint";s:54:"This field will be shown only to registered employers.";s:7:"default";s:17:"admin@example.com";s:3:"css";s:0:"";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:3;s:5:"group";s:7:"default";}s:5:"phone";a:16:{s:5:"title";s:12:"Phone Number";s:4:"name";s:5:"phone";s:4:"hint";s:54:"This field will be shown only to registered employers.";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:4;s:5:"group";s:7:"default";}s:8:"user_url";a:17:{s:5:"title";s:7:"Website";s:4:"name";s:8:"user_url";s:4:"hint";s:54:"This field will be shown only to registered employers.";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:16:"validation_rules";s:16:"Daq_Validate_Url";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:5;s:5:"group";s:7:"default";}s:9:"is_public";a:16:{s:5:"title";s:7:"Privacy";s:4:"name";s:9:"is_public";s:4:"hint";s:0:"";s:7:"default";i:1;s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-checkbox";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:8:"is_saved";i:1;s:5:"order";i:6;s:5:"group";s:7:"default";}s:5:"image";a:16:{s:5:"title";s:10:"Your Photo";s:4:"name";s:5:"image";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-file";s:8:"file_ext";s:19:"jpg, jpeg, gif, png";s:9:"file_size";i:300000;s:8:"file_num";i:1;s:8:"is_saved";i:1;s:5:"order";i:7;s:5:"group";s:7:"default";s:8:"renderer";s:22:"wpjb_form_field_upload";}s:17:"candidate_country";a:18:{s:5:"title";s:7:"Country";s:4:"name";s:17:"candidate_country";s:4:"hint";s:0:"";s:7:"default";s:3:"840";s:3:"css";s:21:"wpjb-location-country";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:8;s:5:"group";s:8:"location";}s:15:"candidate_state";a:16:{s:5:"title";s:5:"State";s:4:"name";s:15:"candidate_state";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:19:"wpjb-location-state";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:9;s:5:"group";s:8:"location";}s:18:"candidate_zip_code";a:16:{s:5:"title";s:8:"Zip-Code";s:4:"name";s:18:"candidate_zip_code";s:4:"hint";s:0:"";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:10;s:5:"group";s:8:"location";}s:18:"candidate_location";a:16:{s:5:"title";s:4:"City";s:4:"name";s:18:"candidate_location";s:4:"hint";s:62:"For example: "Chicago", "London", "Anywhere" or "Telecommute".";s:7:"default";s:0:"";s:3:"css";s:18:"wpjb-location-city";s:11:"is_required";i:1;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:11;s:5:"group";s:8:"location";}s:8:"category";a:18:{s:5:"title";s:8:"Category";s:4:"name";s:8:"category";s:4:"hint";s:0:"";s:7:"default";a:0:{}s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:15:"ui-input-select";s:11:"fill_method";s:0:"";s:12:"fill_choices";s:0:"";s:13:"fill_callback";s:0:"";s:14:"select_choices";i:1;s:12:"empty_option";s:0:"";s:17:"empty_option_text";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:12;s:5:"group";s:6:"resume";}s:8:"headline";a:16:{s:5:"title";s:21:"Professional Headline";s:4:"name";s:8:"headline";s:4:"hint";s:70:"Describe yourself in few words, for example: Experienced Web Developer";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:13:"ui-input-text";s:11:"placeholder";s:0:"";s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:10:"url_target";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:13;s:5:"group";s:6:"resume";}s:11:"description";a:15:{s:5:"title";s:15:"Profile Summary";s:4:"name";s:11:"description";s:4:"hint";s:69:"Use this field to list your skills, specialities, experience or goals";s:7:"default";s:0:"";s:3:"css";s:0:"";s:11:"is_required";i:0;s:10:"is_trashed";s:1:"0";s:10:"is_builtin";i:1;s:4:"type";s:17:"ui-input-textarea";s:16:"textarea_wysiwyg";i:1;s:19:"validation_min_size";s:0:"";s:19:"validation_max_size";s:0:"";s:8:"is_saved";i:1;s:5:"order";i:14;s:5:"group";s:6:"resume";}}s:5:"group";a:5:{s:7:"default";a:7:{s:5:"title";s:19:"Account Information";s:4:"name";s:7:"default";s:5:"order";i:0;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:8:"location";a:7:{s:5:"title";s:7:"Address";s:4:"name";s:8:"location";s:5:"order";i:1;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:6:"resume";a:7:{s:5:"title";s:6:"Resume";s:4:"name";s:6:"resume";s:5:"order";i:2;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"is_saved";i:1;}s:10:"experience";a:8:{s:5:"title";s:10:"Experience";s:4:"name";s:10:"experience";s:5:"order";i:3;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"editable";i:0;s:8:"is_saved";i:1;}s:9:"education";a:8:{s:5:"title";s:9:"Education";s:4:"name";s:9:"education";s:5:"order";i:4;s:4:"type";s:14:"ui-input-group";s:10:"is_builtin";i:1;s:10:"is_trashed";s:1:"0";s:8:"editable";i:0;s:8:"is_saved";i:1;}}s:6:"config";a:3:{s:10:"form_label";s:0:"";s:9:"form_code";s:0:"";s:14:"form_is_active";s:1:"0";}}';
                break;
        }

        return $form;
    }

    abstract function init();
}

?>