<?php

class Wpjb_Upgrade_553 extends Wpjb_Upgrade_Abstract
{
    public function getVersion()
    {
        return "5.5.3";
    }

    public function execute()
    {
        global $wp_rewrite;
        
        if($wp_rewrite instanceof WP_Rewrite) {
            $wp_rewrite->flush_rules();
        }
        return;
    }

}
