<div class="wrap wpjb">
   
<script type="text/javascript">wpjb_slug_ui();</script>

<h1>
<?php if($form->getId()>0): ?>
    <?php printf(__("Edit Employer '<strong>%s</strong>'", "wpjobboard"), esc_html( $form->getObject()->company_name ) ) ?>
<?php else: ?>
    <?php _e("Add New Employer", "wpjobboard"); ?>
<?php endif; ?>
    <a class="add-new-h2" href="<?php echo wpjb_admin_url("employers", "add") ?>"><?php _e("Add New", "wpjobboard") ?></a> 
</h1>
    
<?php $this->_include("flash.php"); ?>

<form action="" method="post" class="wpjb-form" enctype="multipart/form-data">

<?php if($form->getId()>0): ?>
<div id="edit-slug-box" class="hide-if-no-js" style="padding:0 0 1.5em 0; font-size:1.1em">
    
    <strong><?php _e("Permalink") ?>:</strong>
    <span id="sample-permalink" tabindex="-1"><?php echo $url ?></span>

    &lrm;
    
    <span id="edit-slug-buttons" class="wpjb-slug-buttons">
        <a class="save button button-small" href="#"><?php _e("OK") ?></a> 
        <a href="#" class="cancel button-small"><?php _e("Cancel") ?></a>

        <?php if(get_option('permalink_structure')): ?>
        <span id="edit-slug-btn"><a href="#post_name" class="edit-slug button button-small hide-if-no-js" title="<?php _e("Edit Permalink", "wpjobboard") ?>"><?php _e("Edit", "wpjobboard") ?></a></span>
        <?php endif; ?>
        
        
        <input type="hidden" id="wpjb-current-object-id" value="<?php echo $form->getId() ?>" />
        <span id="view-slug-btn"><a href="<?php esc_attr_e($permalink) ?>" class="button button-small" title="<?php _e("View Employer", "wpjobboard") ?>"><?php _e("View Employer", "wpjobboard") ?></a></span>
        
        <?php if(Wpjb_Project::getInstance()->env("uses_cpt") && $shortlink): ?>
        <span id="gets-slug-btn"><a href="#" class="button button-small" onclick="prompt('URL:', jQuery('#shortlink').val()); return false;"><?php _e("Get Shortlink", "wpjobboard") ?></a></span>
        <input id="shortlink" type="hidden" value="<?php esc_attr_e($shortlink)  ?>" />
        <?php endif; ?>
        
    
    </span>
</div>      
<?php endif; ?>
    
<div id="poststuff" >

    <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
            <?php echo daq_form_layout($form, array( "exclude_hidden" => "form_code") ) ?>

            <p class="submit">
                <input type="submit" value="<?php _e("Save company profile", "wpjobboard") ?>" class="button-primary button" name="Submit"/>
            </p>
        </div>
        
        <div id="postbox-container-1" class="postbox-container">
            
            <div class="wpjb-sticky" id="side-info-column" style="">
                <div class="meta-box-sortables ui-sortable" id="side-sortables"><div class="postbox " id="submitdiv">
                <div class="handlediv"><br></div><h3 class="hndle"><span><?php _e("Employer Account", "wpjobboard") ?></span></h3>
                <div class="inside">
                <div id="submitpost" class="submitbox">

                <div id="minor-publishing">
                <?php if($form->getId()>0): ?>
                <div class="misc-pub-section wpjb-mini-profile">
                    <div class="wpjb-avatar">
                    <?php echo get_avatar($form->getObject()->user_id) ?>
                    </div>
                    <strong><?php esc_html_e($user->display_name) ?></strong><br/>
                    <p><?php _e("Login", "wpjobboard") ?>: <b><?php esc_html_e($user->user_login) ?></b></p>
                    <p><?php _e("ID", "wpjobboard") ?>: <b><?php echo $user->ID ?></b></p>


                    <br class="clear" />

                    <p><a href="<?php esc_attr_e(admin_url("user-edit.php?user_id={$user->ID}")) ?>" class="button"><?php _e("view linked user account", "wpjobboard") ?></a></p>
                    <p><a href="<?php esc_attr_e(wpjb_admin_url("job", "index", null, array("employer"=>$form->getId()))) ?>" class="button"><?php printf(__("view employer jobs (%d)", "wpjobboard"), wpjb_find_jobs(array("filter"=>"all", "employer_id"=>$form->getId(), "count_only"=>true))) ?></a></p>
                    <p><a href="<?php esc_attr_e(wpjb_admin_url("memberships", "index", null, array("user_id"=>$user->ID))) ?>" class="button"><?php printf(__("view memberships (%d)", "wpjobboard"), Wpjb_Model_Membership::search(array("count_only"=>1, "user_id"=>$user->ID))) ?></a></p>
                    <p><a href="<?php esc_attr_e(wpjb_link_to("company", $form->getObject())) ?>" class="button"><?php _e("view profile") ?></a></p>

                </div>
                    
                <div class="misc-pub-section curtime ">
                    <span id="form_code_span"><?php _e("Used Form:") ?> <b class=""> 
                        <?php if( isset( $form->getObject()->meta->form_code ) && $form->getObject()->meta->form_code->value() != null ): ?>
                                                        
                            <?php $full_form = maybe_unserialize( get_option( $form->getObject()->meta->form_code->value(), null ) ); ?>
                            <?php $form_detail = $full_form['config']; ?>
                            
                            <span class="form_code_used"><?php echo esc_html( $form_detail['form_label'] ) ?></span>
                            <?php if( !wpjb_if_form_exist( $form->getObject()->meta->form_code->value(), "company" ) ): ?>
                                <span class="form_code_used"><?php _e( "(Form No Longer Exist)", "wpjobboard" ); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="form_code_used"><?php _e( "No Form Saved", "wpjobboard" ); ?></span>
                        <?php endif; ?>
                    </b></span>

                    
                    <a id="form_code_link" class="edit-timestamp hide-if-no-js" href="#"><?php _e("Edit") ?></a>

                    <div class="form_code_box hide-if-js">
                        <?php $forms_list = get_option( "wpjb_forms_list" ); ?>
                        <select id="form_code" name="form_code" size="1" data-initial-value="<?php echo $form->getObject()->meta->form_code->value() ?>">
                        <?php foreach( $forms_list['company']  as $f ): ?>
                        <?php $f_details = maybe_unserialize( get_option( $f, null ) ); ?>
                        <?php if( $form->getObject()->meta->form_code->value() == $f): ?>
                        <option selected="selected" value="<?php echo $f; ?>"><?php echo $f_details['config']['form_label']; ?></option>
                        <?php else: ?>
                        <option value="<?php echo $f; ?>"><?php echo $f_details['config']['form_label']; ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        </select>
                        <a href="#" id="form_code-cancel"><?php _e("Cancel", "wpjobboard") ?></a>
                        <small class="wpjb-autosuggest-help" ><?php _e("if you will change the form, and restore the previous one, old data will be restored.", "wpjobboard") ?></small>
                    </div>
                </div>
                <?php endif; ?>

                <!--div class="misc-pub-section wpjb-inline-section">
                    <span id="timestamp"><?php //_e("Employer Status", "wpjobboard") ?>: <b class="wpjb-inline-label">&nbsp;</b></span>
                    <a class="wpjb-inline-edit hide-if-no-js" href="#"><?php //_e("Edit") ?></a>
                    <div class="wpjb-inline-field wpjb-inline-select hide-if-js">
                        <?php //echo $form->getElement("is_verified")->render(); ?>
                        <a href="#" class="wpjb-inline-cancel"><?php //_e("Cancel", "wpjobboard") ?></a>
                    </div>
                </div-->
                <div class="misc-pub-section misc-pub-section-last ">
                    <?php echo $form->getElement("is_active")->render(); ?><br/>
                </div>

                </div>


                <div id="major-publishing-actions">   
                    <?php if($form->getId()): ?>
                    <div id="delete-action">
                        <a href="<?php esc_attr_e(wpjb_admin_url("employers", "remove")."&".http_build_query(array("users"=>array($form->getId())))) ?>" class="submitdelete deletion wpjb-delete-item-confirm"><?php _e("Delete", "wpjobboard") ?></a>
                    </div>
                    <div id="publishing-action">
                        <?php if(Wpjb_Project::getInstance()->env("uses_cpt") && $form->getObject()->post_id): ?>
                        <a href="<?php esc_attr_e(admin_url(sprintf('post.php?post=%d&action=edit', $form->getObject()->post_id))) ?>" class="button"><?php _e("Advanced Options", "wpjobboard") ?></a>
                        <?php endif; ?>
                        <input type="submit" accesskey="p" tabindex="5" value="<?php _e("Update", "wpjobboard") ?>" class="button-primary" id="publish" name="publish">
                    </div>
                    <?php else: ?>
                    <div id="publishing-action">
                        <input type="submit" accesskey="p" tabindex="5" value="<?php _e("Add Employer", "wpjobboard") ?>" class="button-primary" id="publish" name="publish">
                    </div>
                    <?php endif; ?>
                    <div class="clear"></div>
                </div>
                </div>

                </div>
                </div>
            </div>

            </div> <!-- #side-info-column --> 
            
        </div>
    </div>
    
</div>
</form>


</div>