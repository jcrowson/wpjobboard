<?php
/*
Plugin Name: WPJobBoard (Upmostly Fork)
Plugin URI: http://wpjobboard.net/
Description: Upmostly's forked version
Author: James King
Version: 5.6.5
Author URI: http://wpjobboard.net
*/

if(defined("WPJOBBOARD")) {
    return;
}

global $wpdb;

define("WPJB_MAX_DATE", "9999-12-31");

if(version_compare(PHP_VERSION, "5.2.0", "<")) {
    die("<b>Cannot activate:</b> WPJobBoard requires at least PHP 5.1.6, your PHP version is ".PHP_VERSION);
}

define("WPJOBBOARD", "wpjobboard");

$basepath = dirname(__FILE__);
$wpjobboard = null;

if(!class_exists("Daq_Loader")) {
    require_once $basepath."/framework/Loader.php";
}

Daq_Loader::registerFramework($basepath."/framework");
Daq_Loader::registerAutoloader();

foreach((array)glob($basepath."/application/functions/*") as $wpjbfile) {
    include_once $wpjbfile;
}

Daq_Request::getInstance();
Daq_Db::getInstance()->setDb($wpdb);
Daq_Loader::registerLibrary("Wpjb", $basepath."/application/libraries");

$wpjobboard = Wpjb_Project::getInstance();
$wpjobboard->loadPaths(Daq_Config::parseIni($basepath."/application/config/paths.ini"));
$wpjobboard->setBaseDir($basepath);

foreach(Daq_Config::parseIni($basepath."/application/config/project.ini") as $wpjbk => $wpjbv) {
    $wpjobboard->setEnv($wpjbk, $wpjbv);
}

$wpjobboard->setEnv("template_base", $wpjobboard->path("templates")."/");

Daq_Helper::registerAll();

$wpjobboard->addUserWidgets($basepath."/widgets/*.php");
$wpjobboard->run();
