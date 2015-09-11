<?php

use \Symfony\Component\DomCrawler\Crawler;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_Akismet_Integration_Functional_Test extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	public static function get_plugins()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/vendor/wp-plugin/akismet/akismet.php' => 'akismet/akismet.php',
			$root_dir . '/bwp-recaptcha.php' => 'bwp-recaptcha/bwp-recaptcha.php'
		);
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'input_pubkey'   => '6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X',
			'input_prikey'   => '6LdYGQsTAAAAAD3sarjAo5x8b8IvTqp1eU-2MFwv',
			'enable_comment' => 'yes',
			'enable_akismet' => 'yes'
		);

		self::update_option(BWP_CAPT_OPTION_GENERAL, $default_options);
	}

	public function test_should_not_add_captcha_when_comment_is_not_spam()
	{
		$crawler = self::get_crawler_from_post($this->create_post());
		$captcha = $crawler->filter('div.g-recaptcha');

		$this->assertCount(0, $captcha);

		return $crawler;
	}

	/**
	 * @depends test_should_not_add_captcha_when_comment_is_not_spam
	 */
	public function test_add_captcha_when_comment_is_spam(Crawler $crawler)
	{
		// @todo implement this test

		/* $comment_form = $crawler->filter('#commentform input[type="submit"]')->form(array( */
		/* 	'author'  => 'viagra-test-123', */
		/* 	'email'   => 'test@example.com', */
		/* 	'comment' => 'a spam comment' */
		/* )); */

		/* $client = self::get_client(); */
		/* $crawler = $client->submit($comment_form); */

		/* $this->assertCount(1, $crawler->filter('div.g-recaptcha')); */
	}

	protected function create_post()
	{
		$post = $this->factory->post->create_and_get(array(
			'post_title'     => 'Post with comment opened',
			'comment_status' => 'open'
		));

		self::commit_transaction();

		return $post;
	}

	protected static function ensure_correct_captcha()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_pubkey' => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
			'input_prikey' => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
		));
	}
}
