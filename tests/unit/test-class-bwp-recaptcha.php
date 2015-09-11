<?php

use \Mockery as Mockery;

/**
 * @covers BWP_RECAPTCHA
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_RECAPTCHA_Test extends BWP_Framework_PHPUnit_Unit_TestCase
{
	protected $provider;

	protected function setUp()
	{
		parent::setUp();

		$this->provider = Mockery::mock('alias:BWP_Recaptcha_Provider');
		$this->provider->shouldReceive('create')->andReturn(null)->byDefault();

		$this->plugin = Mockery::mock('BWP_RECAPTCHA', array(
			array(
				'title'   => 'BWP recaptcha',
				'version' => '1.0.0',
				'domain'  => 'bwp-capt'
			), $this->bridge
		))
		->makePartial()
		->shouldAllowMockingProtectedMethods();
	}

	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @covers BWP_RECAPTCHA::user_can_bypass
	 */
	public function test_authorized_users_can_bypass_captcha_if_allowed()
	{
		$this->plugin->options['hide_registered'] = 'yes';
		$this->bridge->shouldReceive('is_user_logged_in')->andReturn(true)->byDefault();

		$this->assertTrue($this->plugin->user_can_bypass(), 'logged in user can bypass captcha');

		$this->plugin->options['hide_registered'] = '';
		$this->plugin->options['hide_cap'] = 'yes';
		$this->plugin->options['select_cap'] = 'manage_options';
		$this->bridge->shouldReceive('current_user_can')->andReturn(true)->byDefault();

		$this->assertTrue($this->plugin->user_can_bypass(), 'users with selected capabilities can bypass captcha');
	}

	/**
	 * @covers BWP_RECAPTCHA::user_can_bypass
	 * @dataProvider get_approved_users_info
	 */
	public function test_approved_commenters_can_bypass_captcha_if_allowed($required, $approved)
	{
		$this->plugin->options['hide_approved'] = 'yes';
		$this->plugin->options['input_approved'] = $required;

		$this->bridge->shouldReceive('wp_get_current_commenter')->andReturn(array(
			'comment_author'       => 'test',
			'comment_author_email' => 'test@example.com',
			'comment_author_url'   => ''
		))->byDefault();

		global $wpdb;

		$wpdb = Mockery::mock('wpdb');
		$wpdb->comments = 'wp_comments';
		$wpdb->shouldReceive('prepare')->andReturn('')->byDefault();
		$wpdb->shouldReceive('get_var')->andReturn($approved)->byDefault();

		if ($approved >= $required) {
			$this->assertTrue($this->plugin->user_can_bypass(), 'commenters with a number of approved comments can bypass captcha');
		} else {
			$this->assertFalse($this->plugin->user_can_bypass());
		}
	}

	public function get_approved_users_info()
	{
		return array(
			array(3, 2),
			array(3, 3),
			array(3, 4)
		);
	}
}
