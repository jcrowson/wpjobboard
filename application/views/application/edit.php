<div class="wrap wpjb">
    
<h1>
<?php if($form->getId()>0): ?>
    <?php _e("Edit Application", "wpjobboard"); ?> (ID: <?php echo $form->getId() ?>)
<?php else: ?>
    <?php _e("Add New Application", "wpjobboard"); ?>
<?php endif; ?>
    <a class="add-new-h2" href="<?php esc_html_e(wpjb_admin_url("application", "add")) ?>"><?php _e("Add New", "wpjobboard") ?></a>
</h1>
<?php $this->_include("flash.php"); ?>

<script type="text/javascript">
    Wpjb.Id = <?php echo $form->getObject()->getId() ?>;
</script>

<style type="text/css">
    @media print {
        #adminmenuback,
        #adminmenumain,
        #wpfooter {
            display: none;
        }
        #wpcontent {
            margin-left: 0px;
        }
        #poststuff #post-body.columns-2 {
            margin-right: 0;
        }
        
        .wpjb-upload-inner,
        a.add-new-h2,
        input.button-primary {
            display: none !important;
        }
        select,
        textarea,
        input[type=text] {
            border: 1px solid transparent;
            box-shadow: none;
        }
        .postbox {
            box-shadow: none;
        }
    }
</style>

<?php 
    global $_wp_admin_css_colors;

    wp_enqueue_script("wpjb-admin-apps");

    if(isset($_wp_admin_css_colors[get_user_option('admin_color')]->colors)) {
        $star_color = $_wp_admin_css_colors[get_user_option('admin_color')]->colors[2];
    } else {
        $star_color = "#ff0000";
    }

?>
<style type="text/css">
    .wpjb-star-color:before {
        color: <?php echo $star_color; ?>;
    }
</style>

<form action="" method="post" class="wpjb-form" enctype="multipart/form-data">
    
<div id="poststuff" >
    <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
            <?php echo daq_form_layout($form, array( "exclude_hidden" => "form_code" ) ) ?>

            <p class="submit">
                <input type="submit" value="<?php _e("Save Application", "wpjobboard") ?>" class="button-primary" name="Submit"/>
                <?php if( $form->getId() > 0 ): ?>
                <a href="<?php echo wpjb_api_url("print/index"); ?>?id=<?php echo $form->getId() ?>" target="_blank" class="button">
                    <span class="wpjb-glyphs wpjb-icon-print"></span>
                    <?php _e( "Print", "wpjobboard" ) ?>
                </a>
                <?php endif; ?>
            </p>
        </div>
        
        <div id="postbox-container-1" class="postbox-container">
            <div class="wpjb-sticky" id="side-info-column" style="">
            <div class="meta-box-sortables ui-sortable" id="side-sortables"><div class="postbox " id="submitdiv">
            <div class="handlediv"><br></div><h3 class="hndle"><span><?php _e("Application", "wpjobboard") ?></span></h3>
            <div class="inside">
            <div id="submitpost" class="submitbox">


            <div id="minor-publishing">

            <?php 
                if(isset( $user ) && $user instanceof WP_User) {
                    $avatar_id_or_email = $form->getObject()->user_id;
                    $display_name = $user->display_name;
                    $user_id = $user->ID;
                } else {
                    $avatar_id_or_email = $form->getObject()->email;
                    $display_name = $form->getObject()->applicant_name;
                    $user_id = null;
                }
            ?>
            <div class="misc-pub-section wpjb-mini-profile">
                <div class="wpjb-avatar">
                    <?php echo get_avatar($avatar_id_or_email, 48) ?>
                </div>
                
                <strong><?php esc_html_e($display_name) ?></strong><br/>
                
                <p>
                    <?php $rated = absint($form->getObject()->meta->rating->value()) ?>
                    <span class="wpjb-star-ratings" data-id="<?php echo esc_html($form->getObject()->id) ?>">

                        <span class="wpjb-star-rating-bar">
                            <?php for($i=0; $i<5; $i++): ?><span class="wpjb-star-rating wpjb-star-color dashicons dashicons-star-empty <?php echo ($rated>$i) ? "wpjb-star-checked" : "" ?>" data-value="<?php echo $i+1 ?>" ></span><?php endfor ?>
                        </span>
                        <span class="wpjb-star-rating-loader" style="display:none"><img src="<?php echo esc_attr(includes_url() . "images/spinner-2x.gif") ?>" alt="" /></span>
                    </span>
                </p>

            

            </div>


            <div class="misc-pub-section wpjb-inline-section curtime">
                <span id="timestamp"><?php _e("Application Sent", "wpjobboard") ?>: <b><?php esc_html_e(wpjb_date($form->getObject()->applied_at)) ?></b></span>

            </div>
            <div class="misc-pub-section wpjb-inline-section wpjb-inline-suggest">
                <span><span class="wpjb-sidebar-dashicon dashicons dashicons-id-alt"></span> <?php _e("User", "wpjobboard") ?>: <b class="wpjb-inline-label">&nbsp;</b></span>
                <a class="wpjb-inline-edit hide-if-no-js" href="#"><?php _e("Edit") ?></a> 
                
                <?php if( isset( $user_id ) ): ?>
                    | <a href="<?php esc_attr_e(admin_url("user-edit.php?user_id={$user_id}")) ?>" title="<?php _e("view linked user account", "wpjobboard") ?>"><?php _e("Account", "wpjobboard") ?></a>
                <?php endif; ?>
                
                <?php if( isset( $resumeId ) ): ?>
                    | <a href="<?php esc_attr_e(wpjb_admin_url("resumes", "edit", $resumeId)) ?>" title="<?php _e("view user resume") ?>"><?php _e("Resume", "wpjobboard" ) ?></a>
                <?php endif; ?>
                    
                <div class="wpjb-inline-field wpjb-inline-select hide-if-js">

                    <?php echo $form->getElement("user_id_text")->render(); ?>
                    <a href="#" class="wpjb-inline-cancel"><?php _e("Cancel", "wpjobboard") ?></a>
                    <small class="wpjb-autosuggest-help" ><?php _e("start typing user: name, login or email in the box above, some suggestions will appear.", "wpjobboard") ?></small>
                </div>
            </div>
            <div class="misc-pub-section wpjb-inline-section">
                <span><span class="wpjb-sidebar-dashicon dashicons dashicons-category"></span> <?php _e("Job", "wpjobboard") ?>: <b class="wpjb-inline-label">&nbsp;</b></span>
                <a class="wpjb-inline-edit hide-if-no-js" href="#"><?php _e("Edit") ?></a> |
                <a class="hide-if-no-js wpjb-linked-job-view" href="<?php esc_attr_e(wpjb_admin_url("job", "edit", $form->getObject()->job_id)) ?>"><?php _e("View", "wpjobboard") ?></a>
                <div class="wpjb-inline-field wpjb-inline-select hide-if-js">
                    <?php echo $form->getElement("job_id")->render(); ?>
                    <a href="#" class="wpjb-inline-cancel"><?php _e("Cancel", "wpjobboard") ?></a>
                </div>
                <?php if($form->getElement("job_id")->hasErrors()): ?>
                <div style="border:1px solid #FFABA8; background-color:#FFEBE8; padding:4px; margin:4px 0 4px 0">
                    <ul style="margin:0;padding:0">
                        <?php foreach($form->getElement("job_id")->getErrors() as $err): ?>
                        <li style="margin:0"><strong><?php esc_html_e($err) ?></strong></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif ?>
            </div>
            <div class="misc-pub-section wpjb-app-status-section misc-pub-section-last">
                <span>
                    <span class="wpjb-sidebar-dashicon dashicons dashicons-dashboard"></span>
                    <?php _e("Status", "wpjobboard") ?>: 
                    <b class="wpjb-app-status-label">&nbsp;</b>
                    <span class="wpjb-glyphs wpjb-icon-paper-plane wpjb-app-status-notify-icon" title="<?php echo esc_attr("When you will update the application user will receive an email notifying him about status change.", "wpjobboard") ?>"></span>
                </span>
                <a class="hide-if-no-js wpjb-app-status-button" href="#"><?php _e("Edit") ?></a>
                <div class="hide-if-js wpjb-app-status-switch">
                    <select name="status" class="wpjb-app-status-dropdown">
                        <?php foreach(wpjb_get_application_status() as $key => $status): ?>
                        <?php 
                            try {
                                $message = Wpjb_Utility_Message::load($status["notify_applicant_email"]);
                                $is_active = $message->getTemplate()->is_active;
                            } catch(Exception $e) {
                                $is_active = 0;
                            }
                        ?>
                        <option value="<?php echo esc_attr($key) ?>" data-notify="<?php echo (isset($status["notify_applicant_email"]) ? "1" : "0") ?>" data-active="<?php echo esc_attr($is_active) ?>" <?php selected($form->getElement("status")->getValue(), $key) ?>><?php echo esc_html($status["label"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="notify-via-email" class="wpjb-app-status-notify-section"><input type="checkbox" value="1" name="_notify" class="wpjb-app-status-notify" id="notify-via-email" /> Notify applicant via email</label>
                    
                    <p>
                    <a href="#" class="button-primary wpjb-app-status-ok"><?php _e("OK", "wpjobboard") ?></a>
                    <a href="#" class="button wpjb-app-status-cancel"><?php _e("Cancel", "wpjobboard") ?></a>
                    </p>
                </div>
            </div>
                
                <?php if( $form->getId() ): ?>
                <div class="misc-pub-section curtime ">
                    <span id="form_code_span"><?php _e("Used Form:") ?> <b class=""> 
                        <?php if( isset( $form->getObject()->meta->form_code ) && $form->getObject()->meta->form_code->value() != null ): ?>
                                                        
                            <?php $full_form = maybe_unserialize( get_option( $form->getObject()->meta->form_code->value(), null ) ); ?>
                            <?php $form_detail = $full_form['config']; ?>
                            
                            <span class="form_code_used"><?php echo esc_html( $form_detail['form_label'] ) ?></span>
                            <?php if( !wpjb_if_form_exist( $form->getObject()->meta->form_code->value(), "apply" ) ): ?>
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
                        <?php foreach( $forms_list['apply']  as $f ): ?>
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



            </div>


            <div id="major-publishing-actions">  
                <?php if($form->getId()>0): ?>
                <div id="delete-action">
                    <a href="<?php esc_attr_e(wpjb_admin_url("application", "delete", $form->getObject()->id, array("noheader"=>1))) ?>" class="submitdelete deletion wpjb-delete-item-confirm"><?php _e("Delete", "wpjobboard") ?></a>
                </div>
                <div id="publishing-action">
                    <input type="submit" accesskey="p" tabindex="5" value="<?php _e("Update application", "wpjobboard") ?>" class="button-primary" id="publish" name="publish">
                </div>
                <?php else: ?>
                <div id="publishing-action">
                    <input type="submit" accesskey="p" tabindex="5" value="<?php _e("Add application", "wpjobboard") ?>" class="button-primary" id="publish" name="publish">
                </div>
                <?php endif; ?>
                <div class="clear"></div>
            </div>
            </div>

            </div>
            </div>
            </div>


            <?php if($form->getId()>0): ?> 
                <div class="postbox ">
                    <h3 class="hndle"><span><?php _e("Navigation") ?></span></h3>
                    <div class="inside">
                        <div class="submitbox wpjb-application-navigation">
                        
                            <span class="wpjb-nav-item-left">
                            <?php if($app_older): ?>
                                <a href="<?php echo esc_attr(add_query_arg("id", $app_older->id)) ?>" class="button" title="<?php _e("Older", "wpjobboard") ?>"><span class="dashicons dashicons-arrow-left-alt"></span></a>
                            <?php else: ?>
                                <a href="#" class="button" title="<?php _e("Older", "wpjobboard") ?>" style="cursor: not-allowed"><span class="dashicons dashicons-arrow-left-alt"></span></a>
                            <?php endif; ?>
                            </span>

                            <span class="wpjb-nav-item-center"><strong><?php echo absint($app_i) ?></strong> / <?php echo esc_html($apps->total) ?></span>

                            <span class="wpjb-nav-item-right">
                            <?php if($app_newer): ?>
                                <a href="<?php echo esc_attr(add_query_arg("id", $app_newer->id)) ?>" class="button" title="<?php _e("Newer", "wpjobboard") ?>"><span class="dashicons dashicons-arrow-right-alt"></span></a>
                            <?php else: ?>
                                <a href="#" class="button" title="<?php _e("Newer", "wpjobboard") ?>" style="cursor: not-allowed"><span class="dashicons dashicons-arrow-right-alt"></span></a>
                            <?php endif; ?>
                            </span>
                        
                        
                        
                        </div>

                    </div>
                </div>
            <?php endif; ?>
                          
                
                <?php do_action("wpja_minor_section_apply", $form) ?>

                       
            </div> 

        </div>
    </div>
    
</div>
</form>

</div>
