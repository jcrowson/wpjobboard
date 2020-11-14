<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Text
 *
 * @author greg
 */
class Daq_Form_Element_Medialibrary extends Daq_Form_Element implements Daq_Form_Element_Interface
{

    public final function getType()
    {
        return "medialibrary";
    }
    
    public function dump()
    {
        $data = parent::dump();
        
        $allowed = array(
            "Daq_Validate_Email",
            "Daq_Validate_Url",
            "Daq_Validate_Float",
            "Daq_Validate_Int",
            "Daq_Validate_Date",
        );
        
        foreach($this->getValidators() as $v) {
            /* @var $v Daq_Validate_Abstract */

            $class = get_class($v);
            if(in_array($class, $allowed)) {
                $data->validation_rules = $class;
            }
        }
        
        $data->placeholder = $this->getAttr("placeholder");
        $data->validation_min_size = $this->getAttr("validation_min_size");
        $data->validation_max_size = $this->getAttr("validation_max_size");
        $data->url_target = $this->getAttr("url_target");

        return $data;
    }
    
    public function overload(array $data) 
    {
        if(isset($data["validation_rules"]) && class_exists($data["validation_rules"])) {
            $class = $data["validation_rules"];

            if($class == "Daq_Validate_Date") {
                $object = new $class(wpjb_date_format());
            } else {
                $object = new $class;
            }
            
            $this->addValidator($object);
        }
        
        if(isset($data["validation_rules"]) && $data["validation_rules"]=="Daq_Validate_Date") {
            $this->addClass("daq-date-picker");
            wp_enqueue_script("wpjb-vendor-datepicker", false, array(), false, true);
            wp_enqueue_style("wpjb-vendor-datepicker", false, array(), false, true);
        }
        
        if(!empty($data["placeholder"])) {
            $this->setAttr("placeholder", $data["placeholder"]);
        }
        
        if(!empty($data["validation_min_size"])) {
            $this->setAttr("validation_min_size", $data["validation_min_size"]);
        }
        
        if(!empty($data["validation_max_size"])) {
            $this->setAttr("validation_max_size", $data["validation_max_size"]);
        }
        
        if(!empty($data["url_target"])) {
            $this->setAttr("url_target", $data["url_target"]);
        }
        
        if( !empty( $data['validation_min_size'] ) || !empty( $data['validation_max_size'] ) ) {
            $this->addValidator( new Daq_Validate_StringLength( $data['validation_min_size'], $data['validation_max_size'] ) );
        }
        
        parent::overload($data);
    }
    
    public function render() 
    {

        $options = array(
            "id" => $this->getName(),
            "name" => $this->getName(),
            "class" => $this->getClasses(),
            "value" => $this->getValue(),
            "type" => "hidden"
        );
        
        $options += $this->getAttr();
        
        $input = new Daq_Helper_Html("input", $options);
        
        $image = wp_get_attachment_image_src( $this->getValue() );
        $image_url = null;
        if( isset( $image[0] ) ) {
            $image_url = $image[0];
        }

        ob_start();
        ?>
            
            <?php if( !$image_url ): ?>
                <img src="" id="<?php echo $this->getName() ?>_image_holder" style="display: none;" /> 
                <a href="#" style="display: none;" class="wpjb-config-image-remove button-secondary" id="<?php echo $this->getName() ?>_remove_btn" data-name="<?php echo $this->getName() ?>"> <?php _e( "Remove Image", "wpjobboard" ); ?> </a>
                <a href="#" class="wpjb-config-image-upload button-secondary" id="<?php echo $this->getName() ?>_upload_btn" data-name="<?php echo $this->getName() ?>"> <?php _e( "Choose Image", "wpjobboard" ); ?> </a>
            <?php else: ?>

                <img src="<?php echo $image_url; ?>" id="<?php echo $this->getName() ?>_image_holder" /> 
                <a href="#" class="wpjb-config-image-remove button-secondary" id="<?php echo $this->getName() ?>_remove_btn" data-name="<?php echo $this->getName() ?>"> <?php _e( "Remove Image", "wpjobboard" ); ?> </a>
                <a href="#" style="display: none;" class="wpjb-config-image-upload button-secondary" id="<?php echo $this->getName() ?>_upload_btn" data-name="<?php echo $this->getName() ?>"> <?php _e( "Choose Image", "wpjobboard" ); ?> </a>
            <?php endif; ?>

            <?php echo $input->render(); ?>

          <?php 
        $html = ob_get_clean();

        return $html;
    }
    
    public function validate()
    {
        $this->_hasErrors = false;
        
        $value = $this->getValue();
        $value = trim($value);
        $this->setValue($value);
        
        if(empty($value) && !$this->isRequired()) {
            return true;
        } elseif($this->isRequired()) {
            $this->addValidator(new Daq_Validate_Required());
        }
        
        foreach($this->getFilters() as $filter) {
            $value = $filter->filter($value);
        }
        
        $this->setValue($value);
        
        foreach($this->getValidators() as $validator) {
            if(!$validator->isValid($value)) {
                $this->_hasErrors = true;
                $this->_errors = $validator->getErrors();
            }
        }

        return !$this->_hasErrors;
    }
    
}

?>
