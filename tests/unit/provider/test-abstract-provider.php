<?php

/**
 * @covers BWP_Recaptcha_Provider
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_Recaptcha_Provider_Test extends PHPUnit_Framework_TestCase
{
	protected $plugin;

	protected function setUp()
	{
		global $_tests_dir;

		if (!function_exists('tests_add_filter')) {
			_bwp_framework_test_autoloader('WP_UnitTestCase');

			require_once $_tests_dir . '/includes/functions.php';
			require $_tests_dir . '/includes/bootstrap.php';
		}

		$options = array(
			'input_pubkey'             => '',
			'input_prikey'             => '',
			'select_theme'             => '',
			'select_lang'              => '',
			'input_tab'                => '',
			'input_error'              => '',
			'enable_v1_https'          => '',
			'select_v2_lang'           => '',
			'select_v2_theme'          => '',
			'select_v2_size'           => '',
			'select_v2_jsapi_position' => ''
		);

		$this->plugin = $this->getMockBuilder('BWP_RECAPTCHA')
			->disableOriginalConstructor()
			->getMock();

		$this->plugin->options = $options;
		$this->plugin->domain  = 'bwp-capt';
	}

	/**
	 * @covers BWP_Recaptcha_Provider::create
	 * @dataProvider get_captcha_v1_setting
	 */
	public function test_should_create_correct_version_of_provider_based_on_setting($use_v1, $provider_class_name)
	{
		if (version_compare(PHP_VERSION, '5.3.2', '<')) {
			$this->markTestSkipped();
		}

		$this->plugin->method('should_use_old_recaptcha')->willReturn($use_v1);

		$provider = BWP_Recaptcha_Provider::create($this->plugin);

		$this->assertInstanceOf($provider_class_name, $provider);
	}

	public function get_captcha_v1_setting()
	{
		return array(
			array('yes', 'BWP_Recaptcha_Provider_V1'),
			array('', 'BWP_Recaptcha_Provider_V2')
		);
	}
}
