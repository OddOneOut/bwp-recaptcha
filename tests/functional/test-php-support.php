<?php

/**
 * @author Khang Minh <contact@betterwp.net>
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BWP_Recaptcha_PHP_Support_Functional_Test extends BWP_Framework_PHPUnit_WP_Legacy_Functional_TestCase
{
	public function test_can_initiate_all_classes()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));
		$class_maps = include $root_dir . '/vendor/composer/autoload_classmap.php';

		foreach ($class_maps as $class_name => $class_file) {
			if (stripos($class_name, 'recaptcha') === false) {
				continue;
			}

			$not_php_52 = array(
				'BWP_Recaptcha_PHPUnit_WP_Functional_TestCase',
			);

			// do not load certain testcase classes if PHP version is less than 5.3
			if (in_array($class_name, $not_php_52) && version_compare(PHP_VERSION, '5.3', '<')) {
				continue;
			}

			// v2 is for PHP 5.3.2+
			if (stripos($class_name, 'v2') !== false && version_compare(PHP_VERSION, '5.3.2', '<')) {
				continue;
			}

			require_once $class_file;
		}

		$this->assertTrue(true);
	}

	public function get_plugin_under_test()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-recaptcha.php' => 'bwp-recaptcha/bwp-recaptcha.php'
		);
	}

	public function test_can_boot_plugin()
	{
		$this->bootstrap_plugin();

		$this->assertTrue(true);
	}

	public function test_can_load_recaptcha_v1_library()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		if (!function_exists('recaptcha_get_html')
			|| !function_exists('recaptcha_check_answer')
		) {
			require_once $root_dir . '/includes/provider/recaptcha/recaptchalib.php';
		}

		$this->assertTrue(true, 'should be included correctly with ReCaptchaResponse class autoloaded');
	}
}
