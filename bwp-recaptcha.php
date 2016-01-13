<?php
/*
Plugin Name: Better WordPress reCAPTCHA
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-recaptcha/
Description: This plugin utilizes Google reCAPTCHA to help your blog stay clear of spams. BWP reCAPTCHA supports no CAPTCHA reCAPTCHA, Contact Form 7 and Akismet.
Version: 2.0.3
Text Domain: bwp-recaptcha
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// in case someone integrates this plugin or calling this directly
global $bwp_capt;

if ((isset($bwp_capt) && $bwp_capt instanceof BWP_RECAPTCHA) || !defined('ABSPATH'))
	return;

$bwp_capt_meta = array(
	'title'   => 'Better WordPress reCAPTCHA',
	'version' => '2.0.3',
	'domain'  => 'bwp-recaptcha'
);

// require libs manually if PHP version is lower than 5.3.2
// @todo remove this when WordPress drops support for PHP version < 5.3.2
if (version_compare(PHP_VERSION, '5.3.2', '<'))
{
	require_once dirname(__FILE__) . '/autoload.php';
}
else
{
	// load dependencies using composer autoload
	require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// @since 2.0.0 we hook to 'init' action with a priority of 9 to make sure the
// plugin loads before Contact Form 7 loads
add_filter('bwp_capt_init_priority', create_function('', 'return 9;'));

// init the plugin
$bwp_capt = new BWP_RECAPTCHA($bwp_capt_meta);
