<?php
/*
Plugin Name: Better WordPress reCAPTCHA
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-recaptcha/
Description: This plugin utilizes reCAPTCHA (with support for Akismet) to help your blog stay clear of spams. This plugin, however, has a different approach from the current WP-reCAPTCHA plugin and allows you to customize how the captcha looks using CSS.
Version: 1.1.0
Text Domain: bwp-recaptcha
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// Backend
add_action('admin_menu', 'bwp_recaptcha_init_admin', 1);

// Frontend
add_action('init', 'bwp_recaptcha_init');

function bwp_recaptcha_init()
{
	global $bwp_capt;

	require_once('includes/class-bwp-recaptcha.php');
	$bwp_capt = new BWP_RECAPTCHA();
}

function bwp_recaptcha_init_admin()
{
	global $bwp_capt;

	$bwp_capt->init_admin();
}
