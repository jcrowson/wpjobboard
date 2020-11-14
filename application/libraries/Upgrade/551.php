<?php

class Wpjb_Upgrade_551 extends Wpjb_Upgrade_Abstract
{
    public function getVersion()
    {
        return "5.5.1";
    }

    public function execute()
    {
        global $wp_rewrite;
        
        //$this->sql();

        if($wp_rewrite instanceof WP_Rewrite) {
            $wp_rewrite->flush_rules();
        }
        return;
    }
}
