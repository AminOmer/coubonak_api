<?php
/*
Plugin Name: Coubonak API
Description: Coubonak API! no more explanation!!!.
Version: 1.0.1
Author: AminOmer
Author URI: https://www.AminOmer.com
*/

define('CAPI_DIR', dirname(__FILE__));
define('CAPI_URL', rtrim(rtrim(plugin_dir_url(__FILE__),'/'),'\\'));

require_once(CAPI_DIR . '/classes/rooh.php');
require_once(CAPI_DIR . '/functions.php');

add_action('init', function(){
    rooh::start();
});
