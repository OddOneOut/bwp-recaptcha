<?php

use \Symfony\Component\DomCrawler\Crawler;

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_Add_Recaptcha_To_WP_Forms_Functional_Test extends BWP_Recaptcha_PHPUnit_WP_Functional_TestCase
{
	protected static $wp_options = array(
		'users_can_register' => 1
	);

	public function tearDown()
	{
		self::reset_users();
		self::reset_comments();

		if (isset($_SERVER['HTTPS'])) {
			unset($_SERVER['HTTPS']);
		}

		parent::tearDown();
	}

	protected static function set_plugin_default_options()
	{
		$default_options = array(
			'input_pubkey'        => '6LdYGQsTAAAAAFwLgIpzaIBQibeTQRG8qqk6zK-X',
			'input_prikey'        => '6LdYGQsTAAAAAD3sarjAo5x8b8IvTqp1eU-2MFwv',
			'use_recaptcha_v1'    => '', // only test recaptcha v2
			'enable_comment'      => 'yes',
			'enable_login'        => 'yes',
			'enable_registration' => 'yes',
			'hide_registered'     => '',
			'hide_approved'       => '',
			'hide_cap'            => ''
		);

		self::update_option(BWP_CAPT_OPTION_GENERAL, $default_options);
	}

	public function get_extra_plugins()
	{
		$fixtures_dir = dirname(__FILE__) . '/data/fixtures';

		return array(
			$fixtures_dir . '/filters.php' => 'bwp-recaptcha-fixtures/filters.php'
		);
	}

	public function test_add_captcha_to_comment_form_after_author_fields()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_position' => 'after_fields'
		));

		$crawler = self::get_crawler_from_post($this->create_post());
		$captcha = $crawler->filter('div.g-recaptcha');

		$this->assertCount(1, $captcha);

		$this->assertCount(1, $captcha->previousAll()->filter('p > input[name="author"]'), 'captcha added after author fields (author)');
		$this->assertCount(1, $captcha->previousAll()->filter('p > input[name="email"]'), 'captcha added after author fields (email)');
	}

	public function test_add_captcha_to_comment_form_after_comment_field()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_position' => 'after_comment_field'
		));

		$crawler = self::get_crawler_from_post($this->create_post());

		$captcha = $crawler->filter('div.g-recaptcha');

		$this->assertCount(1, $captcha);
		$this->assertCount(1, $captcha->previousAll()->filter('p > textarea[name="comment"]'), 'captcha added after comment field');

		return $crawler;
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_redirect_back_to_comment_form_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_response' => 'redirect'
		));

		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form();

		$client = self::get_client_clone();
		$post_uri = self::get_uri_from_client($client);

		// don't follow redirect so we can check the actual redirect response
		$client->followRedirects(false);
		$client->submit($comment_form);

		$this->assertEquals(302, $client->getResponse()->getStatus());
		$this->assertEquals(
			add_query_arg(
				array('cerror' => 'missing-input-response'),
				$post_uri . '#respond'
			),
			$client->getResponse()->getHeader('Location')
		);
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_redirect_back_to_comment_form_with_correct_error_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_response' => 'redirect',
			'input_error'     => 'invalid captcha'
		));

		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form();

		$client = self::get_client_clone();
		$crawler = $client->submit($comment_form);

		$this->assertCount(1, $crawler->filter('.bwp-recaptcha-error:contains(invalid captcha)'));
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_fill_comment_field_when_redirected_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_response' => 'redirect',
			'enable_auto_fill_comment' => true
		));

		$comment = '<p>my comment</p>';
		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form(array(
			'comment' => $comment
		));

		$client = self::get_client_clone();
		$crawler = $client->submit($comment_form);

		$this->assertEquals(htmlspecialchars($comment, ENT_QUOTES, get_option('blog_charset')), $crawler->filter('#comment')->html());
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_wp_die_with_correct_error_if_wrong_captcha(Crawler $crawler)
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'select_response' => 'back',
			'input_back' => 'invalid captcha go back'
		));

		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form();

		$client = self::get_client_clone();
		$crawler = $client->submit($comment_form);

		$this->assertEquals(500, $client->getResponse()->getStatus(), 'should be a wp_die() page');
		$this->assertCount(1, $crawler->filter('body > p:contains(invalid captcha go back)'));
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_successfully_post_comment_if_captcha_is_correct(Crawler $crawler)
	{
		self::ensure_correct_captcha();

		$email = 'test' . self::uniqid() . '@example.com';

		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form(array(
			'author'  => $email,
			'email'   => $email,
			'comment' => 'a valid comment',
		));

		$client = self::get_client_clone();
		$client->submit($comment_form);

		$this->assertCount(1, get_comments(array(
			'author_email' => $email,
			'number' => 1
		)));
	}

	/**
	 * @depends test_add_captcha_to_comment_form_after_comment_field
	 */
	public function test_should_not_add_captcha_to_comment_form_if_user_can_bypass_approved_comments(Crawler $crawler)
	{
		self::ensure_correct_captcha();

		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'hide_approved'  => 'yes',
			'input_approved' => 2
		));

		$email   = 'test' . self::uniqid() . '@example.com';
		$comment = 'a valid comment %s';

		$comment_form = $crawler->filter('#commentform input[type="submit"]')->form(array(
			'author'  => $email,
			'email'   => $email
		));

		$client = self::get_client_clone();
		$post_uri = self::get_uri_from_client($client);

		// first comment
		$client->submit($comment_form, array(
			'comment' => sprintf($comment, self::uniqid()) . ' from ' . getenv('WP_VERSION')
		));

		// second comment
		$crawler = $client->submit($comment_form, array(
			'comment' => sprintf($comment, self::uniqid()) . ' from ' . getenv('WP_VERSION')
		));

		// make sure the two comments are approved
		global $wpdb;
		$wpdb->query($wpdb->prepare(
			"UPDATE $wpdb->comments SET comment_approved = 1 WHERE comment_author_email = %s",
			$email
		));
		self::commit_transaction();

		// visit the post with comment page again
		$crawler = $client->request('GET', $post_uri);

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(
			0,
			$captcha,
			'users who have "input_approved" of approved comments should not have to fill captcha'
		);
	}

	/**
	 * Order matters here
	 */
	public function test_should_not_add_captcha_to_comment_form_if_user_can_bypass_is_logged_in()
	{
		self::ensure_correct_captcha();

		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'hide_registered' => 'yes'
		));

		$user_login = 'test';
		$user = $this->factory->user->create_and_get(array(
			'user_login' => $user_login
		));

		self::commit_transaction();

		$client = self::get_client(false);
		$client->request('POST', wp_login_url(), array(
			'log' => $user_login,
			'pwd' => 'password'
		));

		$crawler = $client->request('GET', get_permalink($this->create_post()));

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(0, $captcha);
	}

	/**
	 * Order matters here
	 */
	public function test_should_not_add_captcha_to_comment_form_if_user_can_bypass_has_capabilities()
	{
		self::ensure_correct_captcha();

		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'hide_cap'   => 'yes',
			'select_cap' => 'manage_options'
		));

		$client = self::get_client(false);
		$client->request('POST', wp_login_url(), array(
			'log' => 'admin',
			'pwd' => 'password'
		));

		$crawler = $client->request('GET', get_permalink($this->create_post()));

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(0, $captcha);
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_add_captcha_to_login_form($is_ssl)
	{
		if ($is_ssl) {
			$_SERVER['HTTP'] = 'on';
		}

		$crawler = self::get_crawler_from_url(wp_login_url());

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(1, $captcha);

		return $crawler;
	}

	/**
	 * @depends test_add_captcha_to_login_form
	 */
	public function test_show_only_captcha_error_in_login_error_if_wrong_captcha()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_error' => 'invalid captcha'
		));

		$client = self::get_client_clone();
		$crawler = $client->getCrawler();

		$login_form = $crawler->filter('#loginform input[type="submit"]')->form(array(
			'log' => 'test'
		));

		$crawler = $client->submit($login_form);

		$this->assertEquals('invalid captcha', trim($crawler->filter('#login_error')->text()));
	}

	/**
	 * @depends test_add_captcha_to_login_form
	 */
	public function test_login_successfully_if_captcha_is_correct()
	{
		self::ensure_correct_captcha();

		$user_login = 'test';
		$user = $this->factory->user->create_and_get(array(
			'user_login' => $user_login
		));

		self::commit_transaction();

		$client = self::get_client_clone();
		$crawler = $client->getCrawler();

		$login_form = $crawler->filter('#loginform input[type="submit"]')->form(array(
			'log' => $user_login,
			'pwd' => 'password'
		));

		$client->followRedirects(false);
		$crawler = $client->submit($login_form);

		$this->assertEquals(302, $client->getResponse()->getStatus(), 'a successful login means a redirection');
	}

	/**
	 * @dataProvider is_ssl
	 */
	public function test_add_captcha_to_registration_form($is_ssl)
	{
		if ($is_ssl) {
			$_SERVER['HTTP'] = 'on';
		}

		$crawler = self::get_crawler_from_url(wp_registration_url());

		$captcha = $crawler->filter('div.g-recaptcha');
		$this->assertCount(1, $captcha);

		return $crawler;
	}

	public function is_ssl()
	{
		return array(
			array(false),
			array(true)
		);
	}

	/**
	 * @depends test_add_captcha_to_registration_form
	 */
	public function test_show_registration_error_if_wrong_captcha()
	{
		self::set_options(BWP_CAPT_OPTION_GENERAL, array(
			'input_error' => 'invalid captcha'
		));

		$client = self::get_client_clone();
		$crawler = $client->getCrawler();

		$register_form = $crawler->filter('#registerform input[type="submit"]')->form(array(
			'user_login' => 'test',
			'user_email' => 'test@example.com'
		));

		$crawler = $client->submit($register_form);

		$this->assertCount(1, $crawler->filter('#login_error:contains(invalid captcha)'));
	}

	/**
	 * @depends test_add_captcha_to_registration_form
	 */
	public function test_register_successfully_if_captcha_is_correct()
	{
		self::ensure_correct_captcha();

		$user_login = 'test' . self::uniqid();

		$client = self::get_client_clone();
		$crawler = $client->getCrawler();

		$register_form = $crawler->filter('#registerform input[type="submit"]')->form(array(
			'user_login' => $user_login,
			'user_email' => $user_login . '@example.com'
		));

		$crawler = $client->submit($register_form);

		$this->assertInstanceOf('WP_User', get_user_by('login', $user_login));
	}
}
