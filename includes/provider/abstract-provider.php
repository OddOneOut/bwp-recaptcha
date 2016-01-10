<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * @author Khang Minh <contact@betterwp.net>
 * @since 2.0.0
 * @package BWP reCAPTCHA
 */
abstract class BWP_Recaptcha_Provider
{
	protected $options;

	protected $domain;

	protected $bridge;

	public function __construct(array $options, $domain, BWP_WP_Bridge $bridge)
	{
		$this->options = $options;
		$this->domain  = $domain;
		$this->bridge  = $bridge;
	}

	public static function create(BWP_RECAPTCHA $plugin)
	{
		$options = $plugin->options;
		$domain  = $plugin->domain;

		$providerOptions = array(
			'site_key'                     => $options['input_pubkey'],
			'secret_key'                   => $options['input_prikey'],
			'request_method'               => $options['select_request_method'],
			'theme'                        => $options['select_theme'],
			'language'                     => $options['select_lang'],
			'tabindex'                     => $options['input_tab'],
			'invalid_response_message'     => $options['input_error'],
			'invalid_response_message_cf7' => $options['input_error_cf7'],
			'use_custom_styles'            => $options['enable_custom_styles'],
		);

		// if instructed to use recaptcha v1, or the current PHP version is
		// less than 5.3.2, we need to use v1 provider
		if ($plugin->should_use_old_recaptcha()) {
			$providerOptions = array_merge($providerOptions, array(
				'use_ssl'       => $options['enable_v1_https'],
				'custom_styles' => $options['input_v1_styles']
			));

			return new BWP_Recaptcha_Provider_V1($providerOptions, $domain, $plugin->get_bridge());
		} else {
			$providerOptions = array_merge($providerOptions, array(
				'language'      => $options['select_v2_lang'],
				'theme'         => $options['select_v2_theme'],
				'size'          => $options['select_v2_size'],
				'position'      => $options['select_v2_jsapi_position'],
				'custom_styles' => $options['input_v2_styles']
			));

			return new BWP_Recaptcha_Provider_V2($providerOptions, $domain, $plugin->get_bridge());
		}
	}

	/**
	 * Render the recaptcha
	 *
	 * @param WP_ERROR $errors
	 * @param string $formId id of the form this recaptcha belongs to, this
	 *                       should basically be a unique identifier
	 */
	abstract public function renderCaptcha(WP_Error $errors = null, $formId = null);

	/**
	 * Verify a captcha response
	 *
	 * @param string $userResponse if null check from $_POST
	 * @return array of errors, an empty array means there are no error
	 *                          keys are actual error codes, values are
	 *                          processed error codes
	 */
	abstract public function verify($userResponse = null);

	public function getOption($key)
	{
		return isset($this->options[$key]) ? $this->options[$key] : '';
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function getErrorMessage($error)
	{
		if ('invalid-response' == $error) {
			return $this->options['invalid_response_message'];
		} elseif ('invalid-response-cf7' == $error) {
			return $this->options['invalid_response_message_cf7'];
		} elseif ('invalid-keys' == $error && current_user_can('manage_options')) {
			return __('There is some problem with your reCAPTCHA API keys, '
				. 'please double check them.', $this->domain);
		} else {
			return sprintf(
				__('Unknown error (%s). Please contact an administrator '
				. 'for more info.', $this->domain),
				$error
			);
		}
	}

	public function getErrorMessageFromCode($errorCode)
	{
		$error = $this->processError($errorCode);
		return $this->getErrorMessage($error);
	}

	protected function getIpAddress()
	{
		return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	protected function processError($errorCode)
	{
		$errorMaps = array(
			// v2 errors
			'missing-input-secret'   => 'invalid-keys',
			'invalid-input-secret'   => 'invalid-keys',
			'missing-input-response' => 'invalid-response',
			'invalid-input-response' => 'invalid-response',
			// v1 errors
			'incorrect-captcha-sol'  => 'invalid-response'
		);

		if (isset($errorMaps[$errorCode])) {
			return $errorMaps[$errorCode];
		}

		return $errorCode;
	}

	protected function processErrors(array $errorCodes)
	{
		$processedErrorCodes = array();

		foreach ($errorCodes as $code) {
			$processedErrorCodes[$code] = $this->processError($code);
		}

		return $processedErrorCodes;
	}
}
