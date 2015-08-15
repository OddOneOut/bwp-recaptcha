<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE VERSION 3.0 OR LATER
 */

/**
 * This class provides base integration between Contact Form 7 and BWP reCAPTCHA
 * @since 1.1.0
 */
class BWP_Recaptcha_CF7_Shortcode
{
	/**
	 * The main plugin
	 *
	 * @access private
	 */
	private static $_plugin;

	/**
	 * Hold BWP_Recaptcha_Provider instance
	 *
	 * @access private
	 */
	private static $_captchaProvider;

	/**
	 * Hold BWP reCAPTCHA options
	 *
	 * @access private
	 */
	private static $_options;

	/**
	 * Text domain
	 *
	 * @access private
	 */
	private static $_domain;

	/**
	 * Private constructor
	 *
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Init the integration
	 *
	 * @access public
	 */
	public static function init(BWP_RECAPTCHA $plugin)
	{
		// make use of BWP reCAPTCHA's options and domain
		self::$_plugin          = $plugin;
		self::$_captchaProvider = $plugin->get_captcha_provider();
		self::$_options         = $plugin->options;
		self::$_domain          = $plugin->plugin_dkey;

		// register our main hooks to CF7
		self::_registerHooks();
	}

	/**
	 * Adds recaptcha to Contact Form 7
	 *
	 * Register necessary hooks so that admin can add recaptcha to forms and
	 * validate captcha properly
	 *
	 * @return void
	 * @access private
	 */
	private static function _registerHooks()
	{
		// admin hooks
		if (is_admin()) {
			add_action('admin_init', array(__CLASS__, 'registerCf7Tag'), 45);
		}

		// front-end hooks
		add_action('wpcf7_init', array(__CLASS__, 'registerCf7Shortcode'));

		// validation for all types of shortcodes, `bwp*` shortcodes are kept
		// for BC only, and should not be used anymore
		add_filter('wpcf7_validate_bwp-recaptcha', array(__CLASS__, 'validateCaptcha'), 10, 2);
		add_filter('wpcf7_validate_bwp_recaptcha', array(__CLASS__, 'validateCaptcha'), 10, 2);
		add_filter('wpcf7_validate_bwprecaptcha', array(__CLASS__, 'validateCaptcha'), 10, 2);
		add_filter('wpcf7_validate_recaptcha', array(__CLASS__, 'validateCaptcha'), 10, 2);

		// need to refresh captcha when the form is submitted via ajax
		add_filter('wpcf7_ajax_json_echo', array(__CLASS__, 'refreshCaptcha'));

		static::registerMoreHooks();
	}

	protected static function registerMoreHooks()
	{
		// to be overriden
	}

	/**
	 * Add BWP reCAPTCHA tag to CF7's tag selection pane
	 *
	 * @return void
	 * @access public
	 */
	public static function registerCf7Tag()
	{
		$tagTitle = 'BWP reCAPTCHA';

		// support for version prior to 4.2
		if (version_compare(WPCF7_VERSION, '4.2', '<')) {
			if (!function_exists('wpcf7_add_tag_generator')) {
				return false;
			}

			wpcf7_add_tag_generator(
				'recaptcha',
				$tagTitle,
				'wpcf7-tg-pane-recaptcha',
				array(__CLASS__, 'renderCf7RecaptchaTagPane41')
			);
		} else {
			$tagGenerator = WPCF7_TagGenerator::get_instance();
			$tagGenerator->add('recaptcha', $tagTitle, array(__CLASS__, 'renderCf7RecaptchaTagPane'));
		}
	}

	/**
	 * Render BWP reCAPTCHA tag pane for `recaptcha` shortcode
	 *
	 * This is used with Contact Form 7 version 4.2+
	 *
	 * @param WPCF7_ContactForm $contactForm
	 * @param array $args
	 * @access public
	 */
	public static function renderCf7RecaptchaTagPane($contactForm, $args = '')
	{
		$args = wp_parse_args($args, array());
?>
<div class="control-box">
	<fieldset>
		<legend><?php echo self::_getTagDescription(); ?></legend>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
					<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
				</tr>
			</tbody>
		</table>
	</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="recaptcha" class="tag code" readonly="readonly" onfocus="this.select()" />
	<div class="submitbox">
		<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
	</div>
</div>
<?php
	}

	/**
	 * Render BWP reCAPTCHA tag pane for `recaptcha` shortcode
	 *
	 * This is used with Contact Form 7 prior to version 4.2
	 *
	 * @param WPCF7_ContactForm $contactForm
	 * @access public
	 */
	public static function renderCf7RecaptchaTagPane41($contactForm)
	{
?>
<div id="wpcf7-tg-pane-recaptcha" class="hidden">
	<form action="">
		<table>
			<tr>
				<td colspan="2"><?php echo self::_getTagDescription(); ?></td>
			</tr>

			<tr>
				<td>
					<?php echo esc_html(__('Name', 'contact-form-7')); ?>
					<br />
					<input type="text" name="name" class="tg-name oneline" />
				</td>
				<td></td>
			</tr>
		</table>

		<div class="tg-tag">
			<?php echo esc_html(__( "Copy this code and paste it into the form left.", 'contact-form-7')); ?>
			<br />
			<input type="text" name="recaptcha" class="tag" readonly="readonly" onfocus="this.select()" />
		</div>
	</form>
</div>
<?php
	}

	private static function _getTagDescription()
	{
		$pluginUrl = self::$_plugin->plugin_url;

		$description = array(
			'<strong style="color: #e6255b">',
			esc_html(__('This reCAPTCHA tag is provided by the BWP reCAPTCHA WordPress plugin.', self::$_domain)),
			'</strong>',
			'<br />',
			sprintf('<a href="%1$s">%1$s</a>', $pluginUrl),
			'<br />',
			sprintf(__('Please refer to <a target="_blank" href="%s">'
				. 'BWP reCAPTCHA\'s documentation</a> for '
				. 'a quick guide on how to customize the look and feel '
				. 'of this tag.', self::$_domain), $pluginUrl . '/#customization')
		);

		return implode("\n", $description);
	}

	/**
	 * Register the BWP reCAPTCHA shortcode to CF7
	 *
	 * @return void
	 * @access public
	 */
	public static function registerCf7Shortcode()
	{
		if (!function_exists('wpcf7_add_shortcode')) {
			return false;
		}

		// @since 2.0.0 add support for two aliases:
		// 1. `bwp_recaptcha`
		// 2. `bwprecaptcha`
		// This is to keep BC only and is deprecated
		wpcf7_add_shortcode(array(
			'recaptcha',
			'bwp-recaptcha', // this will be converted to bwp_recaptcha in CF7 4.1+
			'bwprecaptcha',
		), array(__CLASS__, 'renderCf7Shortcode'), true);
	}

	/**
	 * Render the actual CF7 reCAPTCHA shortcode
	 *
	 * @access public
	 * @param array $tag
	 * @uses WPCF7_Shortcode class
	 */
	public static function renderCf7Shortcode($tag)
	{
		$rc = self::$_plugin;

		// some CF7-specific codes
		$tag = new WPCF7_Shortcode($tag);
		$name = $tag->name;

		// if current user can bypass the captcha, no need to render anything
		if ($rc->user_can_bypass()) {
			return '';
		}

		if (empty($name)) {
			return '';
		}

		// get validation error, if any
		$error = function_exists('wpcf7_get_validation_error')
			? wpcf7_get_validation_error($name) : '';

		ob_start();

		do_action('bwp_recaptcha_add_markups', '', 'cf7-' . $name);
		$rcOutput = ob_get_contents();

		ob_end_clean();

		// add a dummy input so that CF7's JS script can later display the
		// error message (for ajax submit only)
		$cf7Input = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s"><input type="hidden" '
			. 'name="%1$s-dummy" /></span>',
			esc_attr($tag->name)
		);

		return $rcOutput . $cf7Input . $error;
	}

	/**
	 * Validate captcha returned by the Contact Form
	 *
	 * @access public
	 * @uses WPCF7_Shortcode class
	 *
	 * @param WPCF7_Validation $result @since CF7 4.1
	 * @param array $tag
	 *
	 * @return WPCF7_Validation
	 */
	public static function validateCaptcha($result, $tag)
	{
		$rc          = self::$_plugin;
		$provider    = self::$_captchaProvider;

		// some CF7-specific codes
		$tag = new WPCF7_Shortcode($tag);
		$type = $tag->type;
		$name = $tag->name;

		// if current user can bypass the captcha, no need to validate anything
		if ($rc->user_can_bypass()) {
			return $result;
		}

		// invalid captcha
		if ($errors = $provider->verify()) {
			$errorMessage = $provider->getErrorMessage(current($errors));

			// invalid captcha response, show an error message
			if (version_compare(WPCF7_VERSION, '4.1', '>=')) {
				$result->invalidate($tag, $errorMessage);
			} else {
				// CF7 prior to 4.1 uses below codes
				$result['valid'] = false;
				$result['reason'][$name] = $errorMessage;
			}
		}

		return $result;
	}

	/**
	 * Prepare the onSubmit hook array for child classes
	 *
	 * @access public
	 * @param array $items
	 * @return array
	 */
	public static function refreshCaptcha($items)
	{
		if (!isset($items['onSubmit']) || !is_array($items['onSubmit'])) {
			$items['onSubmit'] = array();
		}

		return $items;
	}
}
