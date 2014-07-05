<?php
/*
Plugin Name: Better WordPress reCAPTCHA
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-recaptcha/
Description: This plugin utilizes reCAPTCHA (with support for Akismet) to help your blog stay clear of spams. This plugin, however, has a different approach from the current WP-reCAPTCHA plugin and allows you to customize how the captcha looks using CSS.
Version: 1.1.2
Text Domain: bwp-recaptcha
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// In case someone integrates this plugin in a theme or calling this directly
if (class_exists('BWP_RECAPTCHA') || !defined('ABSPATH'))
	return;

// Init the plugin
require_once dirname(__FILE__) . '/includes/class-bwp-recaptcha.php';
$bwp_capt = new BWP_RECAPTCHA();
