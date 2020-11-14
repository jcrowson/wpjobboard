<span class='wpjb-infobox-title'><?php esc_html_e($job->job_title) ?></span>
<p><?php esc_html_e($job->company_name) ?></p>
<p><a href="<?php esc_attr_e($job->url()) ?>"><?php _e("View Job Details", "wpjobboard")?> <span class="wpjb-glyphs wpjb-icon-right-open"></span></a></p>

<?php if($total > 1): ?>
<div class="wpjb-infobox-footer">
        <a href="#" class="wpjb-infobox-prev"><span class="footer-icon wpjb-glyphs wpjb-icon-left-open" style="padding:0px; visibility: <?php echo $prev ?>"></span></a>
        <small style="margin:0px"><?php echo $index ?> / <?php echo $total ?></small>
        <a href="#" class="wpjb-infobox-next"><span class="footer-icon wpjb-glyphs wpjb-icon-right-open" style="padding:0px; visibility: <?php echo $next ?>"></span></a>
</div>
<?php endif; ?>

<!--div class="wpjb-infobox-footer" style="background-color:<?php echo "#".$job->getTag()->type[0]->meta->color ?>">
    <span class="footer-icon wpjb-glyphs wpjb-icon-tags"></span>
    <small><?php //esc_html_e($job->tag->type[0]->title) ?></small>
    
    <?php //if($total > 1): ?>
    <span class="" style="float:right">
        <a href="#" class="wpjb-infobox-prev"><span class="footer-icon wpjb-glyphs wpjb-icon-left-open" style="padding:0px; visibility: <?php echo $prev ?>"></span></a>
        <small style="margin:0px"><?php echo $index ?> / <?php echo $total ?></small>
        <a href="#" class="wpjb-infobox-next"><span class="footer-icon wpjb-glyphs wpjb-icon-right-open" style="padding:0px; visibility: <?php echo $next ?>"></span></a>
    </span>
    <?php //endif; ?>
</div-->