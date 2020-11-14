<div class="wpjb wpjb-page-preview">

    <?php wpjb_flash(); ?>

    <?php if($canPost): ?>

    <?php include $this->getTemplate("job-board", "job"); ?>

    <div class="row">
        <div class="col-6">
            <a id="wpjb_submit" class="wpjb-button" href="<?php esc_attr_e($urls->add) ?>">&#171; <?php _e("Edit Listing", "wpjobboard") ?></a>
        </div>
        <div class="col-6">
            <a id="wpjb_submit" class="wpjb-button" href="<?php esc_attr_e($urls->save); ?>"><?php _e("Publish Listing", "wpjobboard") ?> &raquo;</a>
        </div>
    </div>
    <?php endif; ?>

</div>
