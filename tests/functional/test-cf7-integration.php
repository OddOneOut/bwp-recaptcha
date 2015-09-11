<?php

use \Symfony\Component\DomCrawler\Crawler;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_CF7_Integration_Functional_Test extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	public function setUp()
	{
		if (!self::get_wp_version('4.1')) {
			$this->markTestSkipped('CF7 4.2 requires WP 4.1 or higher');
		}

		parent::setUp();
	}

	public static function get_plugins()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-recaptcha.php' => 'bwp-recaptcha/bwp-recaptcha.php',
			$root_dir . '/vendor/wp-plugin/contact-form-7/wp-contact-form-7.php' => 'contact-form-7/wp-contact-form-7.php'
		);
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'input_pubkey'   => '6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X',
			'input_prikey'   => '6LdYGQsTAAAAAD3sarjAo5x8b8IvTqp1eU-2MFwv',
			'enable_comment' => '',
			'enable_cf7'     => 'yes'
		);

		self::update_option(BWP_CAPT_OPTION_GENERAL, $default_options);
	}

	public function test_add_captcha_to_cf7_form()
	{
		$crawler = self::get_crawler_from_post($this->create_post_with_cf7());
		$captcha = $crawler->filter('div.g-recaptcha');

		$this->assertCount(1, $captcha);

		return $crawler;
	}

	/**
	 * @depends test_add_captcha_to_cf7_form
	 */
	public function test_show_cf7_error_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_error' => 'invalid captcha'
		));

		$cf7_form = $crawler->filter('.wpcf7-form input[type="submit"]')->form();

		$client = self::get_client_clone();
		$crawler = $client->submit($cf7_form);

		$this->assertCount(1, $crawler->filter('.wpcf7-not-valid-tip:contains(invalid captcha)'));
	}

	/**
	 * @depends test_add_captcha_to_cf7_form
	 */
	public function test_submit_cf7_form_successfully_if_captcha_is_correct(Crawler $crawler)
	{
		self::ensure_correct_captcha();

		$cf7_form = $crawler->filter('.wpcf7-form input[type="submit"]')->form(array(
			'your-name'  => 'test',
			'your-email' => 'test@example.com'
		));

		$client = self::get_client_clone();
		$crawler = $client->submit($cf7_form);

		$this->assertCount(1, $crawler->filter('.wpcf7-mail-sent-ok'));
	}

	public function test_can_add_multiple_captcha_to_cf7_form()
	{
		$crawler = self::get_crawler_from_post($this->create_post_with_cf7(3));
		$captcha = $crawler->filter('div.g-recaptcha');

		$html = self::get_client()->getResponse()->getContent();

		$this->assertCount(3, $captcha);
		$this->assertContains('bwpRecaptchaWidget1 = grecaptcha.render', $html, 'should have correct js render code');
		$this->assertContains('bwpRecaptchaWidget2 = grecaptcha.render', $html, 'should have correct js render code');
		$this->assertContains('bwpRecaptchaWidget3 = grecaptcha.render', $html, 'should have correct js render code');
	}

	protected function create_post_with_cf7($captcha_count = 1)
	{
		// this needs to be here to avoid a PHP warning
		$_SERVER['SERVER_NAME'] = 'bwp';

		require_once WPCF7_PLUGIN_DIR . '/admin/admin.php';

		$cf7_post_id = wpcf7_save_contact_form();
		$cf7_form    = wpcf7_contact_form($cf7_post_id);

		$captcha_shortcodes = '';
		for ($i = 0; $i < $captcha_count; $i++) {
			$captcha_shortcodes .= "\n[recaptcha recaptcha-164$i]\n";
		}

		$_POST['wpcf7-form'] = $cf7_form->prop('form') . $captcha_shortcodes;
		wpcf7_save_contact_form($cf7_post_id);

		$post = $this->factory->post->create_and_get(array(
			'post_title'   => 'Post with contact form 7 form shortcode',
			'post_content' => '[contact-form-7 id="' . (int) $cf7_post_id . '"]'
		));

		self::commit_transaction();

		return $post;
	}

	protected static function ensure_correct_captcha()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_pubkey'   => '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI',
			'input_prikey'   => '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
		));
	}
}
