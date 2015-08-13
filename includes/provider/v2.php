<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

require_once __DIR__ . '/abstract-provider.php';

/**
 * This is the provider used for recaptcha v2
 *
 * @author Khang Minh <contact@betterwp.net>
 * @since 2.0.0
 * @package BWP reCAPTCHA
 */
class BWP_Recaptcha_Provider_V2 extends BWP_Recaptcha_Provider
{
	// this must always be a secured URL
	// @see https://developers.google.com/recaptcha/docs/display
	protected $jsSrc = 'https://www.google.com/recaptcha/api.js';

	/**
	 * Number of recaptcha instances rendered
	 */
	protected $instanceCount;

    /**
     * {@inheritdoc}
     */
	public function renderCaptcha(WP_Error $errors = null)
    {
		$this->instanceCount++;

		$output = array();

		// include the script tag to load recaptcha's api js if needed, but
		// make sure that we only do that once
		if ($this->options['position'] == 'on_demand' && $this->instanceCount == 1) {
			$src = $this->jsSrc . '?hl=' . $this->options['language'];
			$output[] = '<script src="' . $src . '" async defer></script>';
		}

		if (!empty($_GET['cerror'])) {
			$captchaError = $this->getErrorMessage($_GET['cerror']);
		} elseif (isset($errors) && is_wp_error($errors)) {
			$captchaError = $errors->get_error_message('recaptcha-error');
		}

		// @todo support for multiple recaptcha widget
		if (!empty($captchaError)) {
			$output[] = '<p class="bwp-recaptcha-error error">' . $captchaError . '</p>';
		}

		$output[] = implode('', array(
			'<div class="g-recaptcha" ',
				'data-sitekey="' . esc_attr($this->options['site_key']) . '" ',
				'data-theme="' . esc_attr($this->options['theme']) . '" ',
				'data-size="' . esc_attr($this->options['size']) . '" ',
				'data-tabindex="' . esc_attr($this->options['tabindex']) . '"',
				'>',
			'</div>'
		));

		do_action('bwp_capt_before_add_captcha');

		echo implode("\n", $output);
    }

    /**
     * {@inheritdoc}
     */
	public function verify($userResponse = null)
	{
		$userResponse = $userResponse ?: (!empty($_POST['g-recaptcha-response'])
			? $_POST['g-recaptcha-response']
			: null);

		$recaptcha = new ReCaptcha\ReCaptcha($this->options['secret_key']);
		$response = $recaptcha->verify($userResponse, $this->getIpAddress());

		if ($response->isSuccess()) {
			return array();
		} else {
			return $this->processErrors($response->getErrorCodes());
		}
	}
}
