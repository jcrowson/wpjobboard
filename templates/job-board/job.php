<?php 

/**
 * Job details
 * 
 * This template is responsible for displaying job details on job details page
 * (template single.php) and job preview page (template preview.php)
 * 
 * @author Greg Winiarski
 * @package Templates
 * @subpackage JobBoard
 */

 /* @var $job Wpjb_Model_Job */
 /* @var $company Wpjb_Model_Employer */
         
?>

    <?php $company = $job->getCompany(true); ?>
    <?php $image_size = apply_filters("wpjb_singular_logo_size", "64x64", "job") ?>
    <?php $country = Wpjb_List_Country::getByCode($job->job_country); ?>
    <?php
        $url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $is_in_preview_mode = strpos($url,'post-a-job') == true;
    ?>


    <div class="row mb-3">
        <div class="col-12">
            <button type="button" onclick="window.history.back();" class="btn btn-sm btn-link m-0 p-0">← Back to jobs</button>
        </div>
    </div>

    <!--   Job title, salary, etc     -->
    <div class="row">
        <div class="wpjb-text-box col-12 ">

            <h1 class="no-underline m-0 p-0">
              <?php echo $job->job_title ?>
            </h1>
            <h3 class="sub-header">
              <?php if($job->doScheme("company_name")): ?>
              <?php else: ?>
                <?php echo esc_html($job->company_name) ?>
              <?php endif; ?>
            </h3>
            <div class="sub-header">
              <?php upmostly_jobs_render_salary($job); ?>  • <?php upmostly_jobs_render_location($job, $country); ?> • <?php upmostly_jobs_render_type($job); ?>
            </div>

            <!--     Job Description       -->
            <div class="wpjb-text job-description py-3 ">
              <?php if($job->doScheme("job_description")): else: ?>
                <?php wpjb_rich_text($job->job_description, $job->meta->job_description_format->value()) ?>
              <?php endif; ?>
            </div>

          <?php foreach($job->getMeta(array("visibility"=>0, "meta_type"=>3, "empty"=>false, "field_type"=>"ui-input-textarea")) as $k => $value): ?>
              <h3><?php esc_html_e($value->conf("title")); ?></h3>
              <div class="wpjb-text">
                <?php if($job->doScheme($k)): else: ?>
                  <?php wpjb_rich_text($value->value(), $value->conf("textarea_wysiwyg") ? "html" : "text") ?>
                <?php endif; ?>
              </div>
          <?php endforeach; ?>

          <?php do_action("wpjb_template_job_meta_richtext", $job) ?>

          <?php if(!$is_in_preview_mode) : ?>
              <a href="<?php echo upmostly_jobs_render_apply_button($job); ?>" target="_blank" class="job-apply-button mb-5">
                  👉 Apply now
              </a>
              <div class="col-12 d-flex justify-content-between align-items-center">
                  <small>Job posted <?php upmostly_jobs_render_date_posted($job); ?></small>
                  <small>Edit this job? <a href="mailto: hello@upmostly.com">Email me here</a> with your changes</small>
              </div>
          <?php endif; ?>

        </div>
    </div>


