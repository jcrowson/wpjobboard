<div class="wpjb wpjr-page-my-applications">

    <?php wpjb_flash() ?>
    <?php wpjb_breadcrumbs($breadcrumbs) ?>
    
    <div class="wpjb-grid wpjb-grid wpjb-grid-compact">
        <?php if ($result->count > 0): ?>
        <div class="wpjb-grid-row wpjb-grid-head">
            <div class="wpjb-col-50"><?php _e("Job", "wpjobboard") ?></div>
            <div class="wpjb-col-20"><?php _e("Sent", "wpjobboard") ?></div>
            <div class="wpjb-col-15 wpjb-grid-col-right"><?php _e("Status", "wpjobboard") ?></div>
            <div class="wpjb-col-15 wpjb-grid-col-right"><?php _e("Actions", "wpjobboard") ?></div>
        </div>
        <?php foreach($result->application as $app): ?>
        <?php /* @var $app Wpjb_Model_Application */ ?>
        <div class="wpjb-grid-row">
            <div class="wpjb-col-50">
                <a href="<?php esc_attr_e(wpjb_link_to("job", $app->getJob())) ?>"><?php esc_html_e($app->getJob()->job_title) ?></a>
                <?php _e("at", "wpjobboard") ?>
                <?php esc_html_e($app->getJob()->company_name) ?>
            </div>
            <div class="wpjb-col-20">
                <?php echo esc_html(sprintf(__("%s ago", "wpjobboard"), daq_distance_of_time_in_words($app->time->applied_at))) ?>
            </div>
            <div class="wpjb-col-15 wpjb-grid-col-right">
                <?php echo (wpjb_application_status($app->status, true)) ?>
            </div>
            <div class="wpjb-col-15 wpjb-grid-col-right">
                <a href="#" class="wpjb-button wpjb-candidate-remove-application-btn" data-id="<?php echo $app->id; ?>">
                <span class="wpjb-glyphs wpjb-icon-trash"></span>
                </a>
            </div>
        </div>
        <div class="wpjb-grid-row wpjb-candidate-remove-application-box wpjb-candidate-remove-application-box-<?php echo $app->id; ?>">
            <div class="wpjb-col-100 wpjb-grid-col-right">
                <?php $diff = round( ( time() - strtotime( $app->applied_at ) ) / ( 60 * 60 ) );  ?>
                <?php if( !$allow_remove ): ?>
                    <?php _e("Removing application was disbaled by administrator.", "wpjobboard"); ?>
                    | <a href="#" class="wpjb-candidate-remove-application-cancel" data-id="<?php echo $app->id; ?>"><?php _e("Hide this box", "wpjobboard"); ?></a>
                <?php elseif( !in_array( $app->status, $allow_statuses ) ): ?>
                    <?php _e("You can not remove application with this status", "wpjobboard"); ?>
                    | <a href="#" class="wpjb-candidate-remove-application-cancel" data-id="<?php echo $app->id; ?>"><?php _e("Hide this box", "wpjobboard"); ?></a>
                <?php elseif( $allow_delay > 0 && $allow_delay < $diff ): ?>
                    <?php if( $allow_delay > 23 ): ?>
                        <?php printf( __("You had %d days to remove application and this time passed.", "wpjobboard"), ( $allow_delay / 24 ) ); ?>
                    <?php else: ?>
                        <?php printf( __("You had %d hours to remove application and this time passed.", "wpjobboard"), $allow_delay ); ?>
                    <?php endif; ?>
                    | <a href="#" class="wpjb-candidate-remove-application-cancel" data-id="<?php echo $app->id; ?>"><?php _e("Hide this box", "wpjobboard"); ?></a>
                <?php elseif( $allow_delay == -1 && strtotime( $app->getJob()->job_expires_at ) < time() ): ?>
                    <?php _e("You can not remove application for expired job.", "wpjobboard"); ?>
                    | <a href="#" class="wpjb-candidate-remove-application-cancel" data-id="<?php echo $app->id; ?>"><?php _e("Hide this box", "wpjobboard"); ?></a>
                <?php else: ?>
                    <?php _e("Are you sure, that you want to remove this application?", "wpjobboard"); ?>
                    <form action="" method="POST">
                        <input type="hidden" name="remov_app_id" value="<?php echo $app->id; ?>" />
                        <input type="hidden" name="remov_app_nonce" value="<?php echo wp_create_nonce( "wpjb_remove_application" ); ?>" />
                        <input type="submit" value="<?php _e("Yes", "wpjobboard"); ?>" />
                        <!--a href="#"><?php _e("Yes", "wpjobboard"); ?></a--> | 
                    </from>
                    <a href="#" class="wpjb-candidate-remove-application-cancel" data-id="<?php echo $app->id; ?>"><?php _e("Cancel", "wpjobboard"); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="wpjb-grid-row">
            <div class="wpjb-col-100 wpjb-grid-col-center"><?php _e("You haven't sent any applications.", "wpjobboard"); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="wpjb-paginate-links">
        <?php wpjb_paginate_links($url, $result->pages, $result->page, $query) ?>
    </div>

</div>