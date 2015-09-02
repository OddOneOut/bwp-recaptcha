<?php

/**
 * @author Khang Minh <kminh@kdmlabs.com>
 */
class BWP_Recaptcha_Functional_Test extends PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
    }

	protected function tearDown()
	{
    }

	public function test_can_initiate_all_classes()
	{
		$classes = array();
		$class_maps = include dirname(dirname(dirname(__FILE__))) . '/vendor/composer/autoload_classmap.php';

		foreach ($class_maps as $class_name => $class_file) {
			if (strpos($class_name, 'BWP') === false) {
				continue;
			}

			$classes[] = $this->getMockBuilder($class_name)
				->disableOriginalConstructor()
				->getMock();
		}

		$this->assertTrue(true);
	}
}
