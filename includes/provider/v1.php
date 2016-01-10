<?php

/**
 * Copyright (c) 2015 Khang Minh <contact@betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * This is the provider used for recaptcha v1
 *
 * @author Khang Minh <contact@betterwp.net>
 * @since 2.0.0
 * @package BWP reCAPTCHA
 */
class BWP_Recaptcha_Provider_V1 extends BWP_Recaptcha_Provider
{
	/**
	 * {@inheritDoc}
	 */
	public function renderCaptcha(WP_Error $errors = null, $formId = null)
	{
		$this->loadCaptchaLibrary();

		if (!defined('BWP_CAPT_ADDED')) {
			// make sure we add only one recaptcha instance
			define('BWP_CAPT_ADDED', true);

			// captcha error can comes from $_GET variable or passed via
			// hooks' parameters.
			$captchaError = '';
			$captchaErrorCode = null;

			if (!empty($_GET['cerror'])) {
				$captchaError     = $this->getErrorMessageFromCode($_GET['cerror']);
				$captchaErrorCode = $_GET['cerror'];
			} elseif (isset($errors)) {
				$captchaError = $errors->get_error_message('recaptcha-error');
			}

			do_action('bwp_capt_before_add_captcha');
?>
		<style type="text/css">
			/* this is to prevent the iframe from showing up in Chrome */
			iframe[src="about:blank"] { display: none; }
			/* make sure the captcha uses auto table layout */
			.recaptchatable { table-layout: auto; }
<?php
			// @since 2.0.3 allow adding custom styles to captcha instances
			if ($this->options['use_custom_styles'] && !empty($this->options['custom_styles'])) {
				echo  esc_html($this->options['custom_styles']);
			}
?>
		</style>
<?php
			if ($this->options['theme'] != 'custom') {
				if (!empty($captchaError)) {
?>
		<p class="recaptcha_only_if_incorrect_sol error">
			<?php echo $captchaError; ?>
		</p>
<?php
				}
?>
		<script type="text/javascript">
			// @tododoc
			var RecaptchaOptions = RecaptchaOptions || {
				theme: '<?php echo $this->options['theme']; ?>',
				lang: '<?php echo $this->options['language']; ?>',
<?php
				if (!empty($this->options['tabindex'])) {
?>
				tabindex: <?php echo (int) $this->options['tabindex']; echo "\n"; ?>
<?php
				};
?>
			};
		</script>
<?php
			} else {
				$this->loadTemplateFunctions();
				bwp_capt_custom_theme_widget();
			}

			$isSecured = is_ssl() ? true : false;
			$isSecured = $this->options['use_ssl'] ? true : $isSecured;

			if (!empty($this->options['secret_key'])) {
				echo recaptcha_get_html(
					$this->options['site_key'],
					$captchaErrorCode,
					$isSecured,
					$this->options['language']
				);
			} else if (current_user_can('manage_options')) {
				// if user is an admin show the actual error
				printf(__('To use reCAPTCHA you must get an API key from '
					. '<a href="%1$s">%1$s</a>', $this->domain),
					'https://www.google.com/recaptcha/admin/create');
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function verify($userResponse = null)
	{
		$this->loadCaptchaLibrary();

		$challengeField = !empty($_POST['recaptcha_challenge_field'])
			? $_POST['recaptcha_challenge_field']
			: null;

		$userResponse = $userResponse ? $userResponse : (!empty($_POST['recaptcha_response_field'])
			? $_POST['recaptcha_response_field']
			: null);

		$response = recaptcha_check_answer(
			$this->options['secret_key'],
			$this->getIpAddress(),
			$challengeField,
			$userResponse
		);

		if (!$response->is_valid) {
			return $this->processErrors(array($response->error));
		}
	}

	protected function loadTemplateFunctions()
	{
		if (!function_exists('bwp_capt_custom_theme_widget')) :

		/**
		 * Output custom reCAPTCHA theme
		 *
		 * By defining this function in your theme/plugin, you can override this
		 * and thus changing the html codes to suit your needs.
		 *
		 * @return void
		 */
		function bwp_capt_custom_theme_widget()
		{
			global $bwp_capt;

			$provider = $bwp_capt->get_captcha_provider();
?>
	<script type="text/javascript">
		var RecaptchaOptions = {
			theme : 'custom',
			custom_theme_widget: 'recaptcha_widget',
			tabindex: <?php echo (int) $provider->getOption('tabindex'); echo "\n"; ?>
		};
	</script>

	<div id="recaptcha_widget" style="display: none;">
		<p class="recaptcha_only_if_incorrect_sol">
			<?php echo $provider->getOption('invalid_response_message'); ?>
		</p>
		<div id="recaptcha_image"></div>
		<div class="recaptcha_control">
			<a href="javascript:Recaptcha.reload()" title="<?php _e('Get another challenge', $provider->getDomain()); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_refresh.png'; ?>" alt="<?php _e('Get another challenge', $provider->getDomain()); ?>" /></a>
			<span class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')" title="<?php _e('Get audio reCAPTCHA', $provider->getDomain()); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_sound.png'; ?>" alt="<?php _e('Get audio reCAPTCHA', $provider->getDomain()); ?>" /></a></span>
			<span class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')" title="<?php _e('Get image reCAPTCHA', $provider->getDomain()); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_image.png'; ?>" alt="<?php _e('Get image reCAPTCHA', $provider->getDomain()); ?>" /></a></span>
			<span><a href="javascript:Recaptcha.showhelp()" title="<?php _e('About reCAPTCHA', $provider->getDomain()); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_help.png'; ?>" alt="<?php _e('About reCAPTCHA', $provider->getDomain()); ?>" /></a></span>
		</div>

		<div class="recaptcha_text">
			<span class="recaptcha_only_if_image"><label for="recaptcha_response_field"><em><small><?php _e('Type what you see', $provider->getDomain()); ?>:</small></em></label></span>
			<span class="recaptcha_only_if_audio"><label for="recaptcha_response_field"><em><small><?php _e('Type what you hear', $provider->getDomain()); ?>:</small></em></label></span>
			<input type="text" id="recaptcha_response_field" tabindex="<?php echo (int) $provider->getOption('tabindex'); ?>" class="input" name="recaptcha_response_field" />
		</div>
	</div>
<?php
		}

		endif;
	}

	protected function loadCaptchaLibrary()
	{
		if (!function_exists('recaptcha_get_html')
			|| !function_exists('recaptcha_check_answer')
		) {
			require_once dirname(__FILE__) . '/recaptcha/recaptchalib.php';
		}
	}
}
