<?php

function _bwp_recaptcha_autoloader($class_name)
{
	$class_maps = include dirname(__FILE__) . '/vendor/composer/autoload_classmap.php';

	if (stripos($class_name, 'BWP') === false && $class_name !== 'ReCaptchaResponse') {
		return;
	}

	if (array_key_exists($class_name, $class_maps)) {
		require $class_maps[$class_name];
	}
}

spl_autoload_register('_bwp_recaptcha_autoloader');
