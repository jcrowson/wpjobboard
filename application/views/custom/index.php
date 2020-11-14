<?php 

$forms = array(
        
    "job" => array(
        "label"     => __("Job Form", "wpjobboard"),
        "icon"      => "wpjb-icon-briefcase",
        "form"      => "job",
        "add_new"   => wpjb_admin_url( "custom", "edit", null, array( "form"=>"job" ) ),
    ),
    
    "job_search" => array(
        "label"     => __("Job Advanced Search Form", "wpjobboard"),
        "icon"      => "wpjb-icon-search",
        "form"      => "job-search",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"job-search" ) ),
    ),
    
    "job_list_search" => array(
        "label"     => __("Job List Search Form", "wpjobboard"),
        "icon"      => "wpjb-icon-search",
        "form"      => "job-list-search",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"job-list-search" ) ),
    ),
    
    "apply" => array(
        "label"     => __("Apply Online Form", "wpjobboard"),
        "icon"      => "wpjb-icon-inbox",
        "form"      => "apply",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"apply" ) ),
    ),
    
    "company" => array(
        "label"     => __("Company Form", "wpjobboard"),
        "icon"      => "wpjb-icon-building",
        "form"      => "company",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"company" ) ),
    ),
    
    "resume" => array(
        "label"     => __("Resume Form", "wpjobboard"),
        "icon"      => "wpjb-icon-user",
        "form"      => "resume",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"resume" ) ),
    ),
    
    "resume_search" => array(
        "label"     => __("Advanced Resume Search Form", "wpjobboard"),
        "icon"      => "wpjb-icon-search",
        "form"      => "resume-search",
        "add_new"  => wpjb_admin_url( "custom", "edit", null, array( "form"=>"resume-search" ) ),
    ),     
);


$forms_list = get_option( "wpjb_forms_list", array() );
?>


<?php wp_enqueue_style( 'wpjb-glyphs' ) ?>
<div class="wrap wpjb">

<h1><?php _e("Forms Manager", "wpjobboard") ?></h1>

<div class="clear">&nbsp;</div>

<?php foreach( $forms as $key => $form ): ?>
<h2> 
    <span class="wpjb-glyphs <?php echo $form['icon']; ?>"></span> 
    <?php echo $form['label'] ?>  
    <a href="<?php echo $form['add_new']; ?>" class="add-new-h2"><?php _e( "Add New", "wpjobboard" ) ; ?></a> 
</h2>
<table cellspacing="0" class="widefat post fixed wp-list-table">
    
    <tbody id="the-list">
        <?php $form_name = str_replace( "-", "_", $form['form'] ); ?>
        <?php $flist = ( isset( $forms_list[ $form_name ] ) ? $forms_list[ $form_name ] : array() ) ?>
        <?php foreach( $flist as $i => $form_code): ?>
        <?php $full_form = maybe_unserialize( get_option( $form_code, null ) ); ?>
        <?php if( !isset( $full_form['config'] ) ): continue; endif;?>
        <?php $form_detail = $full_form['config']; ?>
        
        <tr valign="top" class="<?php if($i%2==0): ?>alternate <?php endif; ?> iedit">
            <th class="check-column" scope="row">
                <input type="checkbox" value="<?php echo $form_detail['form_code'] ?>" name="item[]"/>
            </th>
            
            <td class="post-title column-title column-primary">
                <strong><a title="<?php _e("Edit", "wpjobboard") ?>" href="<?php echo wpjb_admin_url( "custom", "edit", null, array( "form"=>$form['form'], "code"=>$form_detail['form_code'] ) ); ?>" class="wpjb-row-title"><?php echo $form_detail['form_label'] ?></a></strong>
                <div class="row-actions">
                    <span class="edit"><a title="<?php _e("Edit", "wpjobboard") ?>" href="<?php echo wpjb_admin_url( "custom", "edit", null, array( "form"=>$form['form'], "code"=>$form_detail['form_code'] ) ); ?>"><?php _e("Edit", "wpjobboard") ?></a> | </span>
                    <span class=""><a title="<?php _e("Delete", "wpjobboard") ?>" href="<?php echo wpjb_admin_url( "custom", "delete", null, array( "form"=>$form['form'], "code"=>$form_detail['form_code'] ) ); ?>" class="wpjb-delete"><?php _e("Delete", "wpjobboard") ?></a></span>
                </div>
            </td>

            <td data-colname="<?php esc_attr_e("Code", "wpjobboard") ?>">
                <?php echo $form_detail['form_code'] ?>
            </td>
            
            <td data-colname="<?php esc_attr_e("Status", "wpjobboard") ?>">
                <?php if( isset( $form_detail['form_is_active'] ) && $form_detail['form_is_active'] ): ?>
                    <span class="wpjb-bulb wpjb-bulb-active"><?php _e("Default", "wpjobboard"); ?></span>
                <?php //else: ?>
                    <!--span class="wpjb-bulb wpjb-bulb-expired"><?php //_e("Inactive", "wpjobboard"); ?></span-->
                <?php endif; ?>
                <?php //if( isset( $form_detail['form_is_default'] ) ): ?>
                    <!--span class="wpjb-bulb wpjb-bulb-new"><?php _e("Default", "wpjobboard"); ?></span-->
                <?php //endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    
    <?php foreach(array("thead", "tfoot") as $tx): ?>
    <<?php echo $tx; ?>>
        <tr>
            <th class="manage-column column-cb check-column" scope="col"><input type="checkbox"/></th>
            
            <th class="column-primary" scope="col">  
                <?php _e("Form Name", "wpjobboard") ?>
            </th>
            
            <th class="column-primary" scope="col">  
                <?php _e("Form Code", "wpjobboard") ?>
            </th>
            
            <th class="column-primary" scope="col">  
                <?php _e("Status", "wpjobboard") ?>
            </th>
        </tr>
    </<?php echo $tx; ?>>
    <?php endforeach; ?>
</table>

<?php endforeach; ?>
