<?php

/**
 * @author Khang Minh <contact@betterwp.net>
 */
class BWP_Recaptcha_PHP_Support_Functional_Test extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		global $_tests_dir;

		if (!function_exists('tests_add_filter')) {
			_bwp_framework_test_autoloader('WP_UnitTestCase');

			require_once $_tests_dir . '/includes/functions.php';
			require $_tests_dir . '/includes/bootstrap.php';
		}
	}

	protected function tearDown()
	{
	}

	public function test_can_initiate_all_classes()
	{
		$classes = array();
		$class_maps = include dirname(dirname(dirname(__FILE__))) . '/vendor/composer/autoload_classmap.php';

		foreach ($class_maps as $class_name => $class_file) {
			if (stripos($class_name, 'recaptcha') === false) {
				continue;
			}

			// v2 is for PHP 5.3.2+
			if (stripos($class_name, 'v2') !== false && version_compare(PHP_VERSION, '5.3.2', '<')) {
				continue;
			}

			$classes[] = $this->getMockBuilder($class_name)
				->disableOriginalConstructor()
				->getMock();
		}

		$this->assertTrue(true);
	}

	public function test_can_boot_plugin()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		include_once $root_dir . '/bwp-recaptcha.php';

		$this->assertTrue(true);
	}
}
