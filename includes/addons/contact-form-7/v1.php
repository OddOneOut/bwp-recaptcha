<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * Shortcode for recaptcha v1
 *
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_CF7_V1 extends BWP_Recaptcha_CF7_Shortcode
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
		$items['onSubmit'][] = 'if (typeof Recaptcha !== "undefined") { Recaptcha.reload(); }';

		return $items;
	}
}
