<?php

use \Mockery as Mockery;

use \ReCaptcha as ReCaptcha;
use \ReCaptcha\RequestMethod\Post;
use \ReCaptcha\RequestMethod\CurlPost;
use \ReCaptcha\RequestMethod\SocketPost;

/**
 * @covers BWP_Recaptcha_Provider_V2
 */
class BWP_Recaptcha_Provider_V2_Test extends \PHPUnit_Framework_TestCase
{
	protected $bridge;

	protected $recaptcha;

	protected function setUp()
	{
		$this->bridge = Mockery::mock('BWP_WP_Bridge');

		$this->bridge
			->shouldReceive('add_action')
			->byDefault();

		$this->recaptcha = Mockery::mock('overload:Recaptcha');
	}

	/**
	 * @covers BWP_Recaptcha_Provider_V2::verify
	 * @dataProvider get_request_method_settings
	 */
	public function test_verify_should_use_correct_request_method(
		$request_method,
		$expected_request_method_class = null
	) {
		$provider = $this->create_provider(array(
			'request_method' => $request_method
		));

		if ($request_method == 'auto') {
			$method_class = function_exists('fsockopen')
				? '\ReCaptcha\RequestMethod\SocketPost'
				: null;
			$method_class = ! $method_class && extension_loaded('curl')
				? '\ReCaptcha\RequestMethod\CurlPost'
				: $method_class;
			$method_class = ! $method_class && ini_get('allow_url_fopen')
				? '\ReCaptcha\RequestMethod\Post'
				: $method_class;
		} else {
			$method_class = $expected_request_method_class;
		}

		$this->recaptcha
			->shouldReceive('__construct')
			->with(Mockery::any(), Mockery::type($method_class))
			->once();

		$provider->verify(null);
	}

	public function get_request_method_settings()
	{
		return array(
			array('auto'),
			array('socket', '\ReCaptcha\RequestMethod\SocketPost'),
			array('curl',   '\ReCaptcha\RequestMethod\CurlPost'),
			array('fileio', '\ReCaptcha\RequestMethod\Post')
		);
	}

	protected function create_provider($options)
	{
		return new BWP_Recaptcha_Provider_V2(array_merge(array(
			'secret_key' => 'key'
		), $options), 'domain', $this->bridge);
	}
}
