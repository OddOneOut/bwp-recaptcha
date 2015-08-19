<?php
/*
Plugin Name: Better WordPress reCAPTCHA
Plugin URI: http://betterwp.net/wordpress-plugins/bwp-recaptcha/
Description: This plugin utilizes reCAPTCHA (with support for Akismet) to help your blog stay clear of spams. This plugin, however, has a different approach from the current WP-reCAPTCHA plugin and allows you to customize how the captcha looks using CSS.
Version: 2.0.1
Text Domain: bwp-recaptcha
Domain Path: /languages/
Author: Khang Minh
Author URI: http://betterwp.net
License: GPLv3
*/

// In case someone integrates this plugin in a theme or calling this directly
if (class_exists('BWP_RECAPTCHA') || !defined('ABSPATH'))
	return;

$bwp_capt_meta = array(
	'title'   => 'Better WordPress reCAPTCHA',
	'version' => '2.0.1',
	'domain'  => 'bwp-recaptcha'
);

// show a friendly message when PHP version is lower than required version
// @todo remove this when WordPress drops support for PHP version < 5.3.2
if (version_compare(PHP_VERSION, '5.3.2', '<'))
{
	function bwp_capt_warn_php_version()
	{
		global $bwp_capt_meta;

		require_once __DIR__ . '/vendor/kminh/bwp-framework/src/class-bwp-version.php';

		BWP_VERSION::warn_required_versions(
			$bwp_capt_meta['title'],
			$bwp_capt_meta['domain']
		);
	}

	add_action('admin_notices', 'bwp_capt_warn_php_version');
	add_action('network_admin_notices', 'bwp_capt_warn_php_version');

	return;
}

// dependencies
require_once __DIR__ . '/vendor/autoload.php';

// @since 2.0.0 we hook to 'init' action with a priority of 9 to make sure the
// plugin loads before Contact Form 7 loads
add_filter('bwp_capt_init_priority', function() {
	return 9;
});

// init the plugin
$bwp_capt = new BWP_RECAPTCHA($bwp_capt_meta);
