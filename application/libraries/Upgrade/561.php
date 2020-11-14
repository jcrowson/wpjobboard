<?php

class Wpjb_Upgrade_561 extends Wpjb_Upgrade_Abstract
{
    public function getVersion()
    {
        return "5.6.1";
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
