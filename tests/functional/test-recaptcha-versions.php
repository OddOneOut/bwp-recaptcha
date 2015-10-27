<?php

use \Symfony\Component\DomCrawler\Crawler;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_Recaptcha_Version_Functional_Test extends BWP_Recaptcha_PHPUnit_WP_Functional_TestCase
{
	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'input_pubkey'   => '6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X',
			'input_prikey'   => '6LdYGQsTAAAAAD3sarjAo5x8b8IvTqp1eU-2MFwv',
			'enable_comment' => 'yes'
		);

		self::update_option(BWP_CAPT_OPTION_GENERAL, $default_options);
	}

	public function test_add_captcha_v1_when_selected()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'use_recaptcha_v1' => 'yes'
		));

		$crawler = self::get_crawler_from_post($this->create_post());

		$this->assertCount(0, $crawler->filter('div.g-recaptcha'));
		$this->assertCount(1, $crawler->filter('script[src="http://www.google.com/recaptcha/api/challenge?k=6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X&hl=en"]'));
	}
}
