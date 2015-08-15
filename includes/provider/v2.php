<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

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
	 * A list of instances created
	 *
	 * This array should contain ids of form that a recaptcha instance belongs
	 * to, e.g. 'cf7-form1', the key of the array will then be used to
	 * construct the widget id to be used with `grecaptcha.render()` and
	 * `grecaptcha.reset()`. @see
	 * https://developers.google.com/recaptcha/docs/display#example
	 *
	 * @var array
	 */
	protected $instances = array();

	public function __construct(array $options, $domain)
	{
		parent::__construct($options, $domain);

		$this->_registerHooks();
	}

	/**
	 * {@inheritDoc}
	 */
	public function renderCaptcha(WP_Error $errors = null, $formId = null)
	{
		$output = array();
		$formId = $this->_getUniqueFormId($formId);

		$this->instances[] = $formId;

		if (!empty($_GET['cerror'])) {
			$captchaError = $this->getErrorMessageFromCode($_GET['cerror']);
		} elseif (isset($errors) && is_wp_error($errors)) {
			$captchaError = $errors->get_error_message('recaptcha-error');
		}

		if (!empty($captchaError)) {
			$output[] = '<p class="bwp-recaptcha-error error">' . $captchaError . '</p>';
		}

		$output[] = implode('', array(
			'<input type="hidden" name="bwp-recaptcha-widget-id" value="' . esc_attr($this->_getWidgetId($formId)) . '" />',
			'<div id="' . $this->_getWidgetHtmlId($formId) . '" class="g-recaptcha" ',
				/* 'data-sitekey="' . esc_attr($this->options['site_key']) . '" ', */
				/* 'data-theme="' . esc_attr($this->options['theme']) . '" ', */
				/* 'data-size="' . esc_attr($this->options['size']) . '" ', */
				/* 'data-tabindex="' . esc_attr($this->options['tabindex']) . '"', */
			'>',
			'</div>'
		));

		do_action('bwp_capt_before_add_captcha');

		echo implode("\n", $output);
	}

	/**
	 * {@inheritDoc}
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

	public function printRecaptchaJS()
	{
		$args = array(
			'onload' => 'bwpRecaptchaCallback',
			'render' => 'explicit'
		);

		if (!empty($this->options['language'])) {
			$args['hl'] = urlencode($this->options['language']);
		}

		$src = add_query_arg($args, $this->jsSrc);
?>
		<script type="text/javascript">
<?php foreach ($this->instances as $key => $formId) : ?>
			var bwpRecaptchaWidget<?php echo $key + 1; ?>;
<?php endforeach; ?>
			var bwpRecaptchaCallback = function() {
				// render all collected recaptcha instances
<?php foreach ($this->instances as $key => $formId) : ?>
				bwpRecaptchaWidget<?php echo $key + 1; ?> = grecaptcha.render('<?php echo $this->_getWidgetHtmlId($formId); ?>', {
					sitekey: '<?php echo esc_js($this->options['site_key']); ?>',
					theme: '<?php echo esc_js($this->options['theme']); ?>',
					size: '<?php echo esc_js($this->options['size']); ?>',
					tabindex: '<?php echo esc_js($this->options['tabindex']); ?>'
				});
<?php endforeach; ?>
			};
		</script>

		<script src="<?php echo esc_url($src) ?>" async defer></script>
<?php
	}

	private function _getWidgetId($formId)
	{
		return 'bwpRecaptchaWidget' . (array_search($formId, $this->instances, true) + 1);
	}

	private function _registerHooks()
	{
		$priority = 99999;

		// regular pages
		add_action('wp_footer', array($this, 'printRecaptchaJS'), $priority);

		// login/register page
		add_action('login_footer', array($this, 'printRecaptchaJS'), $priority);

		// admin theme preview page
		add_action('admin_footer-bwp-recapt_page_bwp_capt_theme', array($this, 'printRecaptchaJS'), $priority);
	}

	private function _getUniqueFormId($formId)
	{
		$formId = $formId ?: 'form';

		if (!in_array($formId, $this->instances)) {
			return $formId;
		}

		// non-unique form id, append the total number of instances plus one
		return $formId . '-' . (count($this->instances) + 1);
	}

	private function _getWidgetHtmlId($formId)
	{
		return 'bwp-recaptcha-' . md5($formId);
	}
}
