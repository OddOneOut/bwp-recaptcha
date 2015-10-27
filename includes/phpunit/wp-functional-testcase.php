<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 */
abstract class BWP_Recaptcha_PHPUnit_WP_Functional_TestCase extends BWP_Framework_PHPUnit_WP_Functional_TestCase
{
	protected $plugin;

	public function setUp()
	{
		parent::setUp();

		global $bwp_capt;
		$this->plugin = $bwp_capt;
	}

	public function get_plugin_under_test()
	{
		$root_dir = dirname(dirname(dirname(__FILE__)));

		return array(
			$root_dir . '/bwp-recaptcha.php' => 'bwp-recaptcha/bwp-recaptcha.php'
		);
	}

	protected function create_post()
	{
		$post = $this->factory->post->create_and_get(array(
			'post_title'     => 'Post to test captcha',
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
