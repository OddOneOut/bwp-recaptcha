<?php

use \Symfony\Component\DomCrawler\Crawler;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_Add_Recaptcha_To_WP_Forms_Multisite_Functional_Test extends BWP_Framework_PHPUnit_WP_Multisite_Functional_TestCase
{
	public static function get_plugins()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-recaptcha.php' => 'bwp-recaptcha/bwp-recaptcha.php',
		);
	}

	protected static function set_wp_default_options()
	{
		self::update_site_option('registration', 'all');
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'input_pubkey'        => '6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X',
			'input_prikey'        => '6LdYGQsTAAAAAD3sarjAo5x8b8IvTqp1eU-2MFwv',
			'enable_registration' => 'yes'
		);

		self::update_option(BWP_CAPT_OPTION_GENERAL, $default_options);
	}

	public function test_add_captcha_to_signup_form()
	{
		$crawler = self::get_crawler_from_url(site_url('wp-signup.php'));

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(1, $captcha);

		return $crawler;
	}

	/**
	 * @depends test_add_captcha_to_signup_form
	 */
	public function test_show_signup_user_error_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_error' => 'invalid captcha'
		));

		$signup_form = $crawler->filter('#setupform input[type="submit"]')->form();

		$client = self::get_client_clone();
		$crawler = $client->submit($signup_form);

		$this->assertCount(1, $crawler->filter('.bwp-recaptcha-error:contains(invalid captcha)'));
	}

	/**
	 * @depends test_add_captcha_to_signup_form
	 */
	public function test_signup_user_successfully_if_captcha_is_correct(Crawler $crawler)
	{
		self::ensure_correct_captcha();

		$user_login = 'test' . self::uniqid();

		$signup_form = $crawler->filter('#setupform input[type="submit"]')->form(array(
			'user_name'  => $user_login,
			'user_email' => $user_login . '@example.com',
			'signup_for' => 'user'
		));

		$client = self::get_client_clone();
		$crawler = $client->submit($signup_form);

		global $wpdb;
		$this->assertCount(1, $wpdb->get_results($wpdb->prepare("SELECT user_login FROM $wpdb->signups WHERE user_login = %s", $user_login)));
	}

	public function test_should_not_add_captcha_to_blog_signup_form_when_user_is_not_logged_in()
	{
		self::ensure_correct_captcha();

		$crawler = self::get_crawler_from_url(site_url('wp-signup.php'));

		$user_login = 'test' . self::uniqid();

		$signup_form = $crawler->filter('#setupform input[type="submit"]')->form(array(
			'user_name'  => $user_login,
			'user_email' => $user_login . '@example.com',
			'signup_for' => 'blog'
		));

		$client = self::get_client_clone();
		$crawler = $client->submit($signup_form);

		$this->assertCount(0, $crawler->filter('div.g-recaptcha'), 'no captcha should be shown');

		self::set_plugin_default_options();

		$blog_name = 'blog' . self::uniqid();

		$blog_signup_form = $crawler->filter('#setupform input[type="submit"]')->form(array(
			'blogname'   => $blog_name,
			'blog_title' => $blog_name
		));

		$client->submit($blog_signup_form);

		global $wpdb;
		$this->assertCount(1, $wpdb->get_results($wpdb->prepare("SELECT title FROM $wpdb->signups WHERE title = %s", $blog_name)));
	}

	protected static function ensure_correct_captcha()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_pubkey' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
			'input_prikey' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
		));
	}
}
