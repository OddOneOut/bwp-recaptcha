<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * Shortcode for recaptcha v2
 *
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_CF7_V2 extends BWP_Recaptcha_CF7_Shortcode
{
	public static function init(BWP_RECAPTCHA $plugin)
	{
		parent::init($plugin);

		add_filter('wpcf7_ajax_json_echo', array(__CLASS__, 'refreshCaptcha'));
	}

	/**
	 * Refresh the captcha when needed
	 *
	 * @access public
	 * @param array $items
	 * @return array
	 */
	public static function refreshCaptcha($items)
	{
		if (!function_exists('wpcf7_scan_shortcode')) {
			return $items;
		}

		if (!$fields = wpcf7_scan_shortcode(array('type' => array(
			'recaptcha',
			'bwprecaptcha',
			'bwp-recaptcha',
			'bwp_recaptcha'
		)))) {
			return $items;
		};

		$codes = array();
		foreach ($fields as $field) {
			$name    = $field['name'];
			$options = $field['options'];

			if (! $widgetId = BWP_Framework_Util::get_request_var('bwp-recaptcha-widget-id')) {
				continue;
			}

			$codes[] = 'if (grecaptcha) { grecaptcha.reset(' . $widgetId . '); }';

			// right now we can only support the first captcha instance in a form
			break;
		}

		$items['onSubmit'][] = implode('', $codes);

		return $items;
	}
}
