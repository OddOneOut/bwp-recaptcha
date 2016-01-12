<?php

/**
 * Copyright (c) 2011 Khang Minh <betterwp.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Helper function to display the captcha below the comment input field in themes using comment_form() function
 *
 * Copyright (c) 2011 Jono Bruni <jbruni.com.br> - Free software, in the terms of the GNU General Public License.
 */
function bwp_capt_comment_form($args = array(), $post_id = null)
{
	global $bwp_capt;

	remove_action('comment_form_after_fields', array($bwp_capt, 'add_comment_recaptcha'));
	remove_action('comment_form_logged_in_after', array($bwp_capt, 'add_comment_recaptcha'));
	remove_filter('comment_form_defaults', array($bwp_capt, 'add_recaptcha_after_comment_field'), 11);
	remove_filter('comment_form_submit_field', array($bwp_capt, 'add_recaptcha_before_comment_submit_field'));

	ob_start();

	/**
	 * Fire where a recaptcha should be rendered.
	 *
	 * This action hooks is mostly used to render the captcha in certain forms.
	 */
	do_action('bwp_recaptcha_add_markups');
	$recaptcha_html = ob_get_contents();

	ob_end_clean();

	if (isset($args['comment_notes_after']))
		$args['comment_notes_after'] .= "\n" . $recaptcha_html;
	else
		$args['comment_notes_after'] = $recaptcha_html;

	comment_form($args, $post_id);
}

class BWP_RECAPTCHA extends BWP_Framework_V3
{
	/**
	 * reCAPTCHA built-in languages
	 */
	public $lang;

	/**
	 * @since 2.0.0 languages for recaptcha v2
	 */
	public $v2_lang;

	/**
	 * User capabilities to bypass captcha
	 */
	public $caps;

	/**
	 * Is registering via wp-login.php
	 */
	public $is_reg = false;

	/**
	 * Captcha error message when registering via wp-login.php
	 */
	public $reg_errors = false;

	/**
	 * Is signing up (multi-site only)
	 */
	public $is_signup = false;

	/**
	 * Is logging in via wp-login.php
	 */
	public $is_login = false;

	/**
	 * User has enough approved comments, no captcha needed
	 */
	public $user_is_approved = false;

	/**
	 * The recaptcha provider, which takes care of rendering and verifying
	 *
	 * @var BWP_Recaptcha_Provider
	 */
	protected $provider;

	/**
	 * {@inheritDoc}
	 */
	public function __construct(array $meta, BWP_WP_Bridge $bridge = null)
	{
		parent::__construct($meta, $bridge);

		// Basic version checking
		if (!$this->check_required_versions())
			return;

		// Default options
		$options = array(
			'input_pubkey'             => '',
			'input_prikey'             => '',
			'input_error'              => $this->bridge->t('<strong>ERROR:</strong> Incorrect or '
				. 'empty reCAPTCHA response, please try again.', $this->domain),
			'input_back'               => $this->bridge->t('Error: Incorrect or empty reCAPTCHA response, '
				. 'please click the back button on your browser\'s toolbar or '
				. 'click on %s to go back.', $this->domain),
			'input_approved'           => 1,
			'input_tab'                => 0,
			'input_error_cf7'          => $this->bridge->t('Incorrect or empty reCAPTCHA response, '
				. 'please try again.', $this->domain),
			'input_v1_styles'          => $this->get_default_v1_custom_styles(), // @since 2.0.3
			'input_v2_styles'          => $this->get_default_v2_custom_styles(), // @since 2.0.3
			'enable_comment'           => 'yes',
			'enable_registration'      => '',
			'enable_login'             => '',
			'enable_akismet'           => '',
			'enable_cf7'               => 'yes', // @since 2.0.0
			'enable_cf7_spam'          => 'yes', // @since 2.0.2
			'enable_auto_fill_comment' => '',
			'enable_css'               => 'yes',
			'enable_v1_https'          => '', // @since 2.0.0, force recaptcha v1 to use https
			'enable_custom_styles'     => '', // @since 2.0.3
			'use_recaptcha_v1'         => '', // @since 2.0.0 whether to use recaptcha v1
			'use_global_keys'          => 'yes',
			'select_lang'              => 'en',
			'select_theme'             => 'red',
			'select_cap'               => 'manage_options',
			'select_response'          => 'redirect',
			'select_position'          => 'after_comment_field',
			'select_v2_lang'           => '', // @since 2.0.0, default to empty (auto detected)
			'select_v2_theme'          => 'light', // @since 2.0.0 'light' or 'dark'
			'select_v2_size'           => 'normal', // @since 2.0.0
			'select_v2_jsapi_position' => 'on_demand', // @since 2.0.0 load on all pages or only when needed
			'select_akismet_react'     => 'hold',
			'select_request_method'    => 'auto', // @since 2.0.3
			'hide_registered'          => '',
			'hide_cap'                 => '',
			'hide_approved'            => '',
			'nag_only_recaptcha_v1'    => 'yes'
		);

		$this->add_option_key('BWP_CAPT_OPTION_GENERAL', 'bwp_capt_general',
			$this->bridge->t('General Options', $this->domain)
		);
		$this->add_option_key('BWP_CAPT_OPTION_THEME', 'bwp_capt_theme',
			$this->bridge->t('Theme Options', $this->domain)
		);

		$this->build_properties('BWP_CAPT', $options,
			dirname(dirname(__FILE__)) . '/bwp-recaptcha.php',
			'http://betterwp.net/wordpress-plugins/bwp-recaptcha/', false);
	}

	private function get_default_v1_custom_styles()
	{
		$styles = array(
			'#recaptcha_widget_div {',
			'    display: block;',
			'    clear: both;',
			'    margin-bottom: 1em;',
			'}'
		);

		return implode("\n", $styles);
	}

	private function get_default_v2_custom_styles()
	{
		$styles = array(
			'.g-recaptcha {',
			'    display: block;',
			'    clear: both;',
			'    margin-bottom: 1em;',
			'}'
		);

		return implode("\n", $styles);
	}

	/**
	 * Whether we should use the old recaptcha
	 *
	 * @return bool
	 */
	public function should_use_old_recaptcha()
	{
		if ('yes' == $this->options['use_recaptcha_v1'] || !BWP_Version::get_current_php_version('5.3.2'))
			return true;

		return false;
	}

	/**
	 * Init a PHP session
	 *
	 * @since 2.0.0
	 */
	private function _init_session()
	{
		if (('redirect' == $this->options['select_response']
				&& 'yes' == $this->options['enable_auto_fill_comment']
			|| $this->_is_akismet_integration_enabled())
		) {
			// should init session
		}
		else
		{
			// session not needed
			return;
		}

		// do not init session in admin or when session is already active
		if (!is_admin() && !isset($_SESSION))
		{
			// do not init a session if headers are already sent, but we will
			// still start the session in debug mode
			if (headers_sent() && (!defined('WP_DEBUG') || !WP_DEBUG))
				return;

			session_start();
		}
	}

	private function _set_session_data($key, $value)
	{
		// init session on demand
		$this->_init_session();

		if (!isset($_SESSION))
			return;

		$_SESSION[$key] = trim($value);
	}

	private function _get_session_data($key)
	{
		// init session on demand
		$this->_init_session();

		if (isset($_SESSION[$key]))
			return $_SESSION[$key];
	}

	private function _unset_session_data($key)
	{
		if (isset($_SESSION[$key]))
			unset($_SESSION[$key]);
	}

	/**
	 * Whether akismet integration is enabled
	 *
	 * @since 1.1.1
	 * @return bool
	 */
	private function _is_akismet_integration_enabled()
	{
		if (defined('AKISMET_VERSION') && 'yes' == $this->options['enable_akismet'])
			return true;

		return false;
	}

	/**
	 * Whether the system has marked a previous comment as spam
	 *
	 * @return bool
	 */
	private function _is_previous_comment_spam()
	{
		$is_spam = $this->_get_session_data('bwp_capt_previous_comment_is_spam');

		if ($is_spam && 'yes' == $is_spam)
			return true;

		return false;
	}

	protected function pre_init_properties()
	{
		$this->lang    = include_once dirname(__FILE__) . '/provider/v1-languages.php';
		$this->v2_lang = include_once dirname(__FILE__) . '/provider/v2-languages.php';

		/**
		 * Filter WordPress capabilities that can bypass a recaptcha.
		 *
		 * @see https://codex.wordpress.org/Roles_and_Capabilities
		 *
		 * @param array $capabilities The capabilities to filter.
		 *
		 * @return array Example:
		 * ```
		 * return $caps = array(
		 *     'Read Profile' => 'read',
		 *     'Manage Options' => 'manage_options'
		 * );
		 * ```
		 */
		$this->caps = $this->bridge->apply_filters('bwp_capt_bypass_caps', array(
			$this->bridge->t('Read Profile', $this->domain)   => 'read',
			$this->bridge->t('Manage Options', $this->domain) => 'manage_options'
		));

		// @since 1.1.0 init public and private keys based on multi-site setting
		$this->init_captcha_keys();
	}

	protected function load_libraries()
	{
		$this->provider = BWP_Recaptcha_Provider::create($this);
	}

	protected function init_hooks()
	{
		// determine the current page to behave correctly
		$this->determine_current_page();

		// init addons
		$this->init_addons();

		// user can bypass captcha, nothing else to do
		if ($this->user_can_bypass()) {
			return;
		}

		if (!empty($this->options['input_pubkey']) && !empty($this->options['input_prikey']))
		{
			// this action needs to be added when a captcha is manually needed
			add_action('bwp_recaptcha_add_markups', array($this, 'add_recaptcha'));

			if ('yes' == $this->options['enable_comment'])
				$this->init_comment_form_captcha();

			if ('yes' == $this->options['enable_login'] && $this->is_login)
				$this->init_login_form_captcha();

			if ('yes' == $this->options['enable_registration'] && $this->is_reg)
				$this->init_registration_form_captcha();

			if ('yes' == $this->options['enable_registration'] && $this->is_signup)
				$this->init_multisite_registration_form_captcha();
		}
	}

	/**
	 * Copied from wp-includes/general-template.php:wp_registration_url
	 * because we still have to support 3.0
	 */
	private function _wp_registration_url()
	{
		return $this->bridge->apply_filters('register_url', $this->bridge->site_url('wp-login.php?action=register', 'login'));
	}

	protected function determine_current_page()
	{
		// @since 2.0.3 only strip the host and scheme (including https), so
		// we can properly compare with REQUEST_URI later on.
		$login_path    = preg_replace('#https?://[^/]+/#i', '', $this->bridge->wp_login_url());
		$register_path = preg_replace('#https?://[^/]+/#i', '', $this->_wp_registration_url());

		global $pagenow;

		$request_uri = ltrim($_SERVER['REQUEST_URI'], '/');
		if (strpos($request_uri, $register_path) === 0)
		{
			// whether user is requesting regular user registration page
			$this->is_reg = true;
		}
		elseif (strpos($request_uri, $login_path) === 0)
		{
			// whether user is requesting the wp-login page
			$this->is_login = true;
		}
		elseif (!empty($pagenow) && $pagenow == 'wp-signup.php')
		{
			// whether user is requesting wp-signup page (multi-site page for
			// user/site registration)
			$this->is_signup = true;
		}
	}

	/**
	 * Init cf7 addon if applicable
	 */
	protected function init_cf7_addon()
	{
		if (defined('WPCF7_VERSION') && 'yes' == $this->options['enable_cf7'])
		{
			// add support for Contact Form 7 (CF7) automatically if CF7 is
			// installed and activated
			// @since 2.0.0 this should use appropriate class for current
			// version of recaptcha
			if ($this->should_use_old_recaptcha())
				BWP_Recaptcha_CF7_V1::init($this);
			else
				BWP_Recaptcha_CF7_V2::init($this);
		}
	}

	protected function init_addons()
	{
		$this->init_cf7_addon();
	}

	protected function init_comment_form_captcha()
	{
		// if user chooses to integrate with akismet, and previous comment is
		// not marked as spam
		if ($this->_is_akismet_integration_enabled() && !$this->_is_previous_comment_spam())
		{
			// only add a recaptcha once akismet has identified a comment as spam
			add_action('akismet_spam_caught', array($this, 'add_recaptcha_after_akismet'));
		}
		else
		{
			if ($this->options['select_position'] == 'after_fields')
			{
				// show captcha after website field
				add_action('comment_form_after_fields', array($this, 'add_comment_recaptcha'));
				add_action('comment_form_logged_in_after', array($this, 'add_comment_recaptcha'));
			}
			elseif ($this->options['select_position'] == 'after_comment_field')
			{
				/**
				 * show captcha after comment field (default @since 1.1.1)
				 *
				 * @since 2.0.0 and @since WordPress 4.2.0
				 * there's a new filter to add recaptcha that doesn't
				 * rely on the fragile `comment_notes_after`, we will
				 * use that if possible
				 */
				if (version_compare($this->get_current_wp_version(), '4.2', '>='))
					add_filter('comment_form_submit_field', array($this, 'add_recaptcha_before_comment_submit_field'));
				else
					// otherwise use the `comment_notes_after` arg
					add_filter('comment_form_defaults', array($this, 'add_recaptcha_after_comment_field'), 11);
			}

			// fill the comment textarea with previously submitted comment
			if ('redirect' == $this->options['select_response']
				&& 'yes' == $this->options['enable_auto_fill_comment']
			) {
				add_filter('comment_form_field_comment', array($this, 'fill_comment_field_with_previous_comment'));
			}

			// check entered captcha for comment form
			add_action('pre_comment_on_post', array($this, 'check_comment_recaptcha'));
		}
	}

	protected function init_login_form_captcha()
	{
		// @since 1.1.0 add captcha to login form
		add_action('login_form', array($this, 'add_recaptcha'));

		// the priority of 15 is to ensure that we run the filter before
		// WordPress authenticates the user.
		add_filter('authenticate', array($this, 'check_login_recaptcha'), 15);
	}

	protected function init_registration_form_captcha()
	{
		// normal user registration page
		add_action('register_form', array($this, 'add_recaptcha'));
		add_filter('registration_errors', array($this, 'check_reg_recaptcha'));
	}

	protected function init_multisite_registration_form_captcha()
	{
		add_action('signup_extra_fields', array($this, 'add_multisite_user_reg_recaptcha'));
		add_action('signup_blogform', array($this, 'add_multisite_blog_reg_recaptcha'));
		add_filter('wpmu_validate_user_signup', array($this, 'check_multisite_user_reg_recaptcha'));
		add_filter('wpmu_validate_blog_signup', array($this, 'check_multisite_blog_reg_recaptcha'));
	}

	protected function init_captcha_keys()
	{
		global $blog_id;

		if (self::is_multisite())
		{
			// use api keys from main blog unless we're already on the main blog
			if ($blog_id > 1 && 'yes' == $this->options['use_global_keys'])
			{
				switch_to_blog(1);

				$options = get_option(BWP_CAPT_OPTION_GENERAL);
				$this->options['input_pubkey'] = $options['input_pubkey'];
				$this->options['input_prikey'] = $options['input_prikey'];

				restore_current_blog();
			}
		}
	}

	protected function enqueue_media()
	{
		if ($this->is_admin_page())
		{
			wp_enqueue_script('bwp-capt-admin', BWP_CAPT_JS . '/admin.js', array('bwp-op'), $this->plugin_ver, true);

			if ($this->is_admin_page(BWP_CAPT_OPTION_THEME))
			{
				wp_enqueue_style('bwp-codemirror');
				wp_enqueue_script('bwp-codemirror-css');
				wp_enqueue_script('bwp-op-codemirror');
			}
		}

		if ('yes' == $this->options['enable_css'])
		{
			if ('custom' == $this->options['select_theme']
				&& ($this->is_admin_page(BWP_CAPT_OPTION_THEME) || !is_admin())
			) {
				/**
				 * Filter the CSS file used for Custom Theme.
				 *
				 * This filter is used for **reCAPTCHA version 1** only.
				 *
				 * @param string $css_file An absolute URL to the CSS file.
				 */
				$custom_theme_css = apply_filters('bwp_capt_css', BWP_CAPT_CSS . '/custom-theme.css');
				wp_enqueue_style('bwp-capt', $custom_theme_css);
			}
		}

		// additional css to make the captcha fit into the login/register form
		if (('yes' == $this->options['enable_registration'] && $this->is_reg)
			|| ('yes' == $this->options['enable_login'] && $this->is_login)
		) {
			// priority 11 so our inline styles are printed after other styles
			add_action('login_head', array($this, 'print_inline_styles_for_login'), 11);
		}
	}

	/**
	 * @return BWP_Recaptcha_Provider
	 */
	public function get_captcha_provider()
	{
		return $this->provider;
	}

	public function print_inline_styles_for_login()
	{
		$login_width = 'clean' == $this->options['select_theme'] ? 482 : 362;

		// recaptcha v2 requires a different width
		$login_width = ! $this->should_use_old_recaptcha() ? 350 : $login_width;
?>
		<style type="text/css">
			#login {
				width: <?php echo $login_width; ?>px;
			}
			#recaptcha_widget_div, .g-recaptcha {
				margin-bottom: 15px;
				zoom: 1;
			}
		</style>
<?php
	}

	/**
	 * Build the Menus
	 */
	protected function build_menus()
	{
		add_menu_page(
			__('Better WordPress reCAPTCHA', $this->domain),
			'BWP reCAPT',
			BWP_CAPT_CAPABILITY,
			BWP_CAPT_OPTION_GENERAL,
			array($this, 'show_option_page'),
			BWP_CAPT_IMAGES . '/icon_menu.png'
		);

		add_submenu_page(
			BWP_CAPT_OPTION_GENERAL,
			__('BWP reCAPTCHA General Options', $this->domain),
			__('General Options', $this->domain),
			BWP_CAPT_CAPABILITY,
			BWP_CAPT_OPTION_GENERAL,
			array($this, 'show_option_page')
		);

		add_submenu_page(
			BWP_CAPT_OPTION_GENERAL,
			__('BWP reCAPTCHA Theme Options', $this->domain),
			__('Theme Options', $this->domain),
			BWP_CAPT_CAPABILITY,
			BWP_CAPT_OPTION_THEME,
			array($this, 'show_option_page')
		);
	}

	public function modify_option_page()
	{
		global $blog_id;

		if (self::is_multisite() && 1 < $blog_id) :
?>
		<script type="text/javascript">
			var rc_readonly = <?php echo 'yes' == $this->options['use_global_keys'] ? 'true' : 'false'; ?>;
			jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', rc_readonly);
			jQuery('input[name="use_global_keys"]').on('click', function() {
				if (jQuery(this).prop('checked')) {
					jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', true);
				} else {
					jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', false);
				}
			});
		</script>
<?php
		endif;
	}

	private function _get_available_request_methods()
	{
		$methods = array(
			__('Auto detected', $this->domain) => 'auto'
		);

		if (function_exists('fsockopen'))
			$methods['Socket (fsockopen)'] = 'socket';

		if (extension_loaded('curl'))
			$methods['cURL'] = 'curl';

		if (ini_get('allow_url_fopen'))
			$methods['file_get_contents'] = 'fileio';

		return $methods;
	}

	protected function build_option_page()
	{
		$page         = $this->get_current_admin_page();
		$option_page  = $this->current_option_page;
		$form_options = array();

		if (!empty($page))
		{
			if ($page == BWP_CAPT_OPTION_GENERAL)
			{
				$option_page->set_current_tab(1);

				$form = array(
					'items' => array(
						'heading', // recaptcha api keys
						'checkbox',
						'input',
						'input',
						'heading', // recaptcha setting
						'checkbox',
						'checkbox',
						'select',
						'heading', // main functionality
						'section',
						'section',
						'heading', // comment form
						'select',
						'select',
						'checkbox',
						'input',
						'input',
						'heading', // akistmet integration
						'checkbox',
						'select',
						'heading', // contact form 7 integration
						'checkbox',
						'checkbox',
						'input'
					),
					'item_labels' => array(
						__('reCAPTCHA API Keys', $this->domain),
						__('Use main site\'s keys', $this->domain),
						__('Site Key', $this->domain),
						__('Secret Key', $this->domain),
						__('reCAPTCHA Settings', $this->domain),
						__('Use reCAPTCHA version 1', $this->domain),
						__('Force https', $this->domain),
						__('Request method', $this->domain),
						__('Main Functionality', $this->domain),
						__('Enable this plugin for', $this->domain),
						__('Hide captcha for', $this->domain),
						__('reCAPTCHA for comment form', $this->domain),
						__('Captcha position', $this->domain),
						__('If invalid captcha response', $this->domain),
						__('Auto fill comment field', $this->domain),
						__('Invalid captcha error message', $this->domain),
						__('Invalid captcha error message', $this->domain),
						__('Akismet Integration for comment form', $this->domain),
						__('Integrate with Akismet', $this->domain),
						__('If correct captcha response', $this->domain),
						__('Contact Form 7 Integration', $this->domain),
						__('Integrate with Contact Form 7', $this->domain),
						__('Treat invalid captcha as spam', $this->domain),
						__('Invalid captcha error message', $this->domain)
					),
					'item_names' => array(
						'h1',
						'use_global_keys',
						'input_pubkey',
						'input_prikey',
						'heading_recaptcha',
						'use_recaptcha_v1',
						'enable_v1_https',
						'select_request_method',
						'heading_func',
						'sec1',
						'sec2',
						'heading_comment',
						'select_position',
						'select_response',
						'enable_auto_fill_comment',
						'input_error',
						'input_back',
						'h3',
						'enable_akismet',
						'select_akismet_react',
						'heading_cf7',
						'enable_cf7',
						'enable_cf7_spam',
						'input_error_cf7'
					),
					'heading' => array(
						'h1' => '<em>' . sprintf(
							__('For this plugin to work, you will need '
							. 'a pair of API keys, which is available for free <a href="%s" target="_blank">here</a>. '
							. 'Once you have created those two keys for the current domain, '
							. 'simply paste them below.</em>', $this->domain),
							'https://www.google.com/recaptcha/admin/create'),
						'heading_recaptcha' => '',
						'heading_func' => '',
						'heading_comment' => '<em>' . __('Settings that are applied to '
							. 'comment forms only.', $this->domain) . '</em>',
						'h3' => '<em>' . __('Integrate the comment form with Akismet for better end-user experience.', $this->domain) . ' '
							. sprintf(__('This feature requires an active <a target="_blank" href="%s">PHP session</a>.', $this->domain), 'http://php.net/manual/en/intro.session.php')
							. '</em>',
						'heading_cf7' => '<em>' . __('Add reCAPTCHA to Contact Form 7. '
							. 'This only works if you have Contact Form 7 activated.', $this->domain) . '</em>'
					),
					'sec1' => array(
						array('checkbox', 'name' => 'enable_comment'),
						array('checkbox', 'name' => 'enable_registration'),
						array('checkbox', 'name' => 'enable_login')
					),
					'sec2' => array(
						array('checkbox', 'name' => 'hide_registered'),
						array('checkbox', 'name' => 'hide_cap'),
						array('checkbox', 'name' => 'hide_approved')
					),
					'select' => array(
						'select_request_method' => $this->_get_available_request_methods(),
						'select_cap' => $this->caps,
						'select_position' => array(
							__('After comment field', $this->domain) => 'after_comment_field',
							__('After form fields (name, email, website)', $this->domain) => 'after_fields'
						),
						'select_response' => array(
							__('Redirect commenter back to the comment form with error message', $this->domain) => 'redirect',
							__('Show an error page just like WordPress does', $this->domain) => 'back'
						),
						'select_akismet_react' => array(
							__('Approve comment immediately', $this->domain) => '1',
							__('Hold comment in moderation queue', $this->domain) => 'hold'
						)
					),
					'checkbox'	=> array(
						'enable_comment' => array(
							__('Comment form', $this->domain) => ''
						),
						'enable_registration' => array(
							__('Registration form (user/site registration)', $this->domain) => ''
						),
						'enable_login' => array(
							__('Login form', $this->domain) => ''
						),
						'hide_registered' => array(
							__('registered users <em>(even without any capabilities)</em>.', $this->domain) => ''
						),
						'hide_cap' => array(
							__('users who can', $this->domain) => ''
						),
						'hide_approved' => array(
							__('visitors who have at least', $this->domain) => ''
						),
						'enable_akismet' => array(
							__('Show captcha only when Akismet identifies a comment as spam. '
							. 'Highly recommended if you do not want to '
							. 'force your visitors to enter a captcha every time.', $this->domain) => ''
						),
						'use_global_keys' => array(
							__('Uncheck to use different key pairs for this site.', $this->domain) => ''
						),
						'use_recaptcha_v1' => array(
							__('Use the oldschool recaptcha instead of the new <em>nocaptcha</em> recaptcha.', $this->domain) => ''
						),
						'enable_v1_https' => array(
							__('Make requests to recaptcha server always secured.', $this->domain) => 'enable_v1_https'
						),
						'enable_auto_fill_comment' => array(
							__('After redirected, auto fill the comment field with previous comment.', $this->domain)
							. ' '
							. sprintf(
								__('This feature requires an active <a target="_blank" href="%s">PHP session</a>.', $this->domain),
								'http://php.net/manual/en/intro.session.php'
							) => ''
						),
						'enable_cf7' => array(
							__('Add the <code>recaptcha</code> shortcode tag to your Contact Form 7 forms.', $this->domain) => ''
						),
						'enable_cf7_spam' => array(
							__('Treat invalid captcha response as spam instead of validation error', $this->domain) => ''
						)
					),
					'input'	=> array(
						'input_pubkey'   => array(
							'size' => 50
						),
						'input_prikey'   => array(
							'size' => 50
						),
						'input_error'    => array(
							'size' => 90
						),
						'input_back'     => array(
							'size' => 90
						),
						'input_approved' => array(
							'size' => 3,
							'label' => __('approved comment(s).', $this->domain)
						),
						'input_error_cf7' => array(
							'size' => 90
						)
					),
					'inline_fields' => array(
						'hide_cap'      => array('select_cap' => 'select'),
						'hide_approved' => array('input_approved' => 'input')
					),
					'helps' => array(
						'input_pubkey' => array(
							'type'    => 'focus',
							'content' => __('A public key used to '
								. 'request captchas from reCAPTCHA server.', $this->domain),
							'placement' => 'right'
						),
						'input_prikey' => array(
							'type'    => 'focus',
							'content' => __('A private (secret) key used to '
								. 'authenticate user\'s response.', $this->domain),
							'placement' => 'right'
						),
						'select_request_method' => array(
							'target' => 'icon',
							'content' => __('To verify a captcha response, '
								. 'this plugin needs to send requests to the reCAPTCHA server, '
								. 'using a specific request method. '
								. '<br /><br />'
								. 'By default, BWP reCAPTCHA use the first available request method, '
								. 'be it Socket (<code>fsockopen</code>), cURL, or <code>file_get_contents</code>.', $this->domain)
								. '<br /><br />'
								. __('If you encounter error such as <code>Unknown error (invalid-json)</code>'
								. 'when submitting a form, try selecting a specific request method here.', $this->domain),
							'size' => 'small'
						),
						'select_akismet_react' => array(
							'target'  => 'icon',
							'content' => __('It is best to put comments identified as spam in moderation queue '
								. 'so you are able to review and instruct '
								. 'Akismet to correctly handle similar comments in the future.', $this->domain)
							),
						'input_error' => array(
							'target'  => 'icon',
							'content' => __('This is shown when the commenter is redirected '
								. 'back to the comment form.', $this->domain)
						),
						'input_back' => array(
							'target'  => 'icon',
							'content' => __('This is shown on the standard WordPress error page.', $this->domain)
						),
						'enable_cf7_spam' => array(
							'type'    => 'link',
							'content' => 'http://contactform7.com/spam-filtering-with-akismet/'
						),
						'input_error_cf7' => array(
							'target'  => 'icon',
							'content' => __('This message is shown when '
								. 'invalid captcha response is treated as '
								. 'a standard validation error. '
								. 'Leave blank to not use.', $this->domain)
						)
					),
					'attributes' => array(
						'use_recaptcha_v1' => array(
							'class'             => 'bwp-switch-select bwp-switch-on-load',
							'data-target'       => 'enable_v1_https',
						),
						'select_response' => array(
							'class'             => 'bwp-switch-select bwp-switch-on-load',
							'data-target'       => 'enable_auto_fill_comment',
							'data-toggle-value' => 'redirect'
						),
						'enable_cf7_spam' => array(
							'class'                => 'bwp-switch-select bwp-switch-on-load',
							'data-target'          => 'input_error_cf7',
							'data-checkbox-invert' => '1'
						)
					),
					'env' => array(
						'use_global_keys' => 'multisite'
					),
					'blog' => array(
						'use_global_keys' => 'sub'
					),
					'php' => array(
						'use_recaptcha_v1' => '50302'
					),
					'formats' => array(
						'input_approved'  => 'int',
						'input_error'     => 'html',
						'input_back'      => 'html',
						'input_error_cf7' => 'html'
					)
				);

				// options that should be handled by this form
				$form_options = array(
					'use_global_keys',
					'use_recaptcha_v1',
					'enable_v1_https',
					'select_request_method',
					'input_pubkey',
					'input_prikey',
					'input_error',
					'input_approved',
					'select_cap',
					'hide_registered',
					'hide_cap',
					'hide_approved',
					'enable_registration',
					'enable_login',
					'enable_comment',
					'enable_auto_fill_comment',
					'input_back',
					'select_position',
					'select_response',
					'enable_akismet',
					'select_akismet_react',
					'enable_cf7',
					'enable_cf7_spam',
					'input_error_cf7'
				);

				// show appropriate fields based on multi-site setting
				add_action('bwp_option_action_before_submit_button', array($this, 'modify_option_page'));
			}
			else if ($page == BWP_CAPT_OPTION_THEME)
			{
				$option_page->set_current_tab(2);

				// @since 2.0.0 differnet settings for different recaptcha
				$form = $this->should_use_old_recaptcha() ? array(
					'items' => array(
						'select',
						'checkbox',
						'checkbox',
						'select',
						'input',
						'heading'
					),
					'item_labels' => array(
						__('reCAPTCHA theme', $this->domain),
						__('Use default CSS', $this->domain),
						__('Enable custom CSS', $this->domain),
						__('Language for built-in themes', $this->domain),
						__('Tabindex for captcha input field', $this->domain),
						__('Preview your reCAPTCHA', $this->domain)
					),
					'item_names' => array(
						'select_theme',
						'enable_css',
						'enable_custom_styles',
						'select_lang',
						'input_tab',
						'h1'
					),
					'heading' => array(
						'h1' => __('<em>Below you will see how your reCAPTCHA will look. '
							. 'Note that this might differ on your actual pages.</em>', $this->domain)
					),
					'select' => array(
						'select_theme' => array(
							__('Default Theme (Red)', $this->domain)    => 'red',
							__('White Theme', $this->domain)            => 'white',
							__('Black Theme', $this->domain)            => 'blackglass',
							__('Clean Theme', $this->domain)            => 'clean',
							__('Custom Theme (use CSS)', $this->domain) => 'custom'
						),
						'select_lang' => $this->lang
					),
					'checkbox' => array(
						'enable_css'           => array('' => ''),
						'enable_custom_styles' => array(__('Add additional CSS rules '
							. 'to all recaptcha instances. You can edit them below.', $this->domain) . '<br />' => '')
					),
					'input'	=> array(
						'input_tab' => array(
							'size' => 3
						)
					),
					'textarea' => array(
						'input_v1_styles' => array(
							'cols' => 90,
							'rows' => 10
						)
					),
					'inline_fields' => array(
						'enable_custom_styles' => array('input_v1_styles' => 'textarea')
					),
					'container' => array(
					),
					'helps' => array(
						'enable_css' => array(
							'type'    => 'switch',
							'target'  => 'icon',
							'content' => sprintf(
								__('Read <a href="%s#recaptcha-version-1" target="_blank">here</a> '
								. 'to know how to use your own CSS for the Custom Theme.', $this->domain),
								BWP_CAPT_PLUGIN_URL
							)
						),
						'select_lang' => array(
							'type'    => 'switch',
							'target'  => 'icon',
							'content' => sprintf(
								__('If you want to add custom translations, '
								. 'please read <a href="%s" target="_blank">this tip</a>.', $this->domain),
								'http://betterwp.net/wordpress-tips/how-to-add-custom-translations-to-bwp-recaptcha/'
							)
						)
					),
					'attributes' => array(
						'select_theme' => array(
							'class'             => 'bwp-switch-select bwp-switch-on-load',
							'data-target'       => 'enable_css',
							'data-toggle-value' => 'custom'
						),
						'enable_custom_styles' => array(
							'class'       => 'bwp-code-editor-cb',
							'data-target' => 'input_v1_styles'
						),
						'input_v1_styles' => array(
							'class'     => 'bwp-form-control bwp-code-editor',
							'data-mode' => 'css'
						)
					)
				) : array(
					'items' => array(
						'select',
						'select',
						'checkbox',
						'select',
						'input',
						'heading'
					),
					'item_labels' => array(
						__('reCAPTCHA theme', $this->domain),
						__('reCAPTCHA size', $this->domain),
						__('Enable custom CSS', $this->domain),
						__('Language', $this->domain),
						__('Tabindex for captcha input field', $this->domain),
						__('Preview your reCAPTCHA', $this->domain)
					),
					'item_names' => array(
						'select_v2_theme',
						'select_v2_size',
						'enable_custom_styles',
						'select_v2_lang',
						'input_tab',
						'h1'
					),
					'heading' => array(
						'h1' => __('<em>Below you will see how your reCAPTCHA will look. '
							. 'Note that this might differ on your actual pages.</em>', $this->domain)
					),
					'select' => array(
						'select_v2_theme' => array(
							_x('Light', 'recaptcha v2 light theme', $this->domain) => 'light',
							_x('Dark', 'recaptcha v2 dark theme', $this->domain)  => 'dark'
						),
						'select_v2_size' => array(
							_x('Normal', 'recaptcha v2 normal size', $this->domain) => 'normal',
							_x('Compact', 'recaptcha v2 compact size', $this->domain) => 'compact'
						),
						'select_v2_lang' => array_merge(array(
							_x('Auto-detected', 'recaptcha v2 language', $this->domain) => ''
						), $this->v2_lang),
						/* 'select_v2_jsapi_position' => array( */
						/* 	__('When needed', $this->domain) => 'on_demand', */
						/* 	__('Globally', $this->domain)    => 'globally', */
						/* 	__('Manually', $this->domain)    => 'manually' */
						/* ) */
					),
					'checkbox' => array(
						'enable_custom_styles' => array(__('Add additional CSS rules '
							. 'to all recaptcha instances. You can edit them below.', $this->domain) . '<br />' => '')
					),
					'input'	=> array(
						'input_tab' => array(
							'size'  => 3,
							'label' => __('Set to 0 to disable.', $this->domain)
						)
					),
					'textarea' => array(
						'input_v2_styles' => array(
							'cols' => 90,
							'rows' => 10
						)
					),
					'inline_fields' => array(
						'enable_custom_styles' => array('input_v2_styles' => 'textarea')
					),
					'attributes' => array(
						'enable_custom_styles' => array(
							'class'       => 'bwp-code-editor-cb',
							'data-target' => 'input_v2_styles'
						),
						'input_v2_styles' => array(
							'class'     => 'bwp-form-control bwp-code-editor',
							'data-mode' => 'css'
						)
					)
				);

				$form = array_merge_recursive($form, array(
					'formats' => array(
						'input_tab' => 'int'
					),
					'container' => array(
						'h1' => $this->get_recaptcha()
					)
				));

				$form_options = $this->should_use_old_recaptcha()
					? array(
						'select_lang',
						'select_theme',
						'input_tab',
						'enable_custom_styles',
						'input_v1_styles',
						'enable_css'
					) : array(
						'select_v2_theme',
						'select_v2_size',
						'select_v2_lang',
						'input_tab',
						'enable_custom_styles',
						'input_v2_styles'
					);
			}
		}

		// assign the form and option array
		$option_page->init($form, $form_options);
	}

	public function show_option_page()
	{
		if (empty($this->options['input_pubkey'])
			|| empty($this->options['input_prikey'])
		) {
			// add notices if recaptcha API keys are missing
			$this->add_notice(
				'<strong>' . __('Warning') . ':</strong> '
				. sprintf(__('API key(s) missing. Please get an API key from '
				. '<a href="%1$s">%1$s</a> (free!)', $this->domain),
				'https://www.google.com/recaptcha/admin/create')
			);
		}

		// @since 2.0.3 if the current PHP version is smaller than 5.3.2, and we
		// need to nag user that only recaptcha v1 can be used
		if (! BWP_Version::get_current_php_version('5.3.2')
			&& $this->options['nag_only_recaptcha_v1'] == 'yes'
		) {
			$this->add_notice(
				'<strong>' . __('Notice') . ':</strong> '
				. sprintf(
					__('In order to use the nocaptcha recaptcha (recaptcha v2), '
					. 'you need at least <strong>PHP 5.3.2</strong> '
					. '(your current PHP version is <strong>%s</strong>), '
					. 'so only recaptcha v1 can be used. '
					. 'It is recommended to contact your host to '
					. 'know how to update your current version of PHP.', $this->domain),
					BWP_Version::get_current_php_version()
				)
			);

			$this->update_some_options(BWP_CAPT_OPTION_GENERAL, array(
				'nag_only_recaptcha_v1' => ''
			));
		}

		if ('yes' == $this->options['enable_akismet'] && !defined('AKISMET_VERSION'))
		{
			// add a notice if Akismet integration is enabled but Akismet
			// plugin is not installed
			$this->add_notice(
				'<strong>' . __('Notice') . ':</strong> '
				. __('You are enabling Akismet integration but Akismet is not currently active. '
				. 'Please activate Akismet for the integration to work.', $this->domain)
			);
		}

		$this->current_option_page->generate_html_form();
	}

	/**
	 * Get the link to the current comment page
	 */
	protected function get_current_comment_page_link()
	{
		global $wp_rewrite;

		if (!is_singular() || !get_option('page_comments'))
			return '';

		$page = get_query_var('cpage');
		if (empty($page))
			$page = 1;

		$redirect_to = '';

		if ($wp_rewrite->using_permalinks())
			$redirect_to = user_trailingslashit(trailingslashit(get_permalink()) . 'comment-page-%#%');
		else
			$redirect_to = add_query_arg('cpage','%#%', get_permalink());

		$redirect_to .= '#respond';

		return str_replace('%#%', $page, $redirect_to);
	}

	/**
	 * Check to see whether the current user can post without captcha
	 *
	 * @return bool
	 */
	public function user_can_bypass()
	{
		global $wpdb;

		if ('yes' == $this->options['hide_registered'] && $this->bridge->is_user_logged_in())
			// do not show captcha to logged in users
			return true;

		if ('yes' == $this->options['hide_cap']
			&& !empty($this->options['select_cap'])
			&& $this->bridge->current_user_can($this->options['select_cap']))
		{
			// user must have certain capabilities in order to bypass captcha
			return true;
		}

		if ($this->user_is_approved)
		{
			// save one db query
			return true;
		}

		if ('yes' == $this->options['hide_approved'])
		{
			$commenter = $this->bridge->wp_get_current_commenter();
			foreach ($commenter as $key => &$commenter_field)
			{
				$commenter_field = trim(strip_tags($commenter_field));

				// if one of the fields (except url) is empty return false
				if ($key != 'comment_author_url' && empty($commenter_field))
					return false;
			}

			// take into account comment author url as well
			$bwp_query =
				'SELECT COUNT(comment_ID)
					FROM ' . $wpdb->comments . '
					WHERE comment_approved = 1' . "
						AND comment_author = %s
						AND comment_author_email = %s
						AND comment_author_url = %s
						AND comment_type = ''
				";

			$approved_count = $wpdb->get_var($wpdb->prepare($bwp_query,
				$commenter['comment_author'],
				$commenter['comment_author_email'],
				$commenter['comment_author_url']));

			// has more approved comments than required?
			if ($approved_count >= $this->options['input_approved'])
			{
				$this->user_is_approved = true;
				return true;
			}
		}

		return false;
	}

	/**
	 * Output the reCAPTCHA HTML, use theme if specified
	 *
	 * This function is used in almost every situation where a captcha is
	 * needed, namely:
	 * 1. comment form
	 * 2. login/register form (standard WordPress)
	 * 3. user/blog registration form (multisite)
	 *    - show captcha after email address when registering for a new user
	 *      account @see wp-signup.php::show_user_form
	 *
	 * @param $errors @since 1.1.0
	 * @param $formId @since 2.0.0
	 */
	public function add_recaptcha($errors = '', $formId = null)
	{
		$errors = !is_wp_error($errors) ? new WP_Error() : $errors;
		$this->provider->renderCaptcha($errors, $formId);
	}

	/**
	 * Get rendered captcha HTML
	 *
	 * @return string
	 * @since 2.0.2
	 */
	public function get_recaptcha()
	{
		ob_start();

		$this->add_recaptcha();

		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Add captcha to the comment form
	 *
	 * @since 2.0.0
	 */
	public function add_comment_recaptcha()
	{
		if ($this->_is_previous_comment_spam())
		{
?>
		<p class="bwp-capt-spam-identified">
			<?php _e('Your comment was identified as spam, '
			. 'please complete the CAPTCHA below:', $this->domain); ?>
		</p>
<?php
		}

		$this->add_recaptcha();

		// with this we can redirect to the previous comment url, with support
		// for previous comment page
		if ('redirect' == $this->options['select_response'])
		{
?>
		<input type="hidden" name="error_redirect_to"
			value="<?php esc_attr_e($this->get_current_comment_page_link()); ?>" />
<?php
		}
	}

	/**
	 * Adds captcha to below comment field
	 *
	 * This is fragile because themes can define their own
	 * `comment_notes_after` arg which override this one. In such case one must
	 * use `bwp_capt_comment_form()` instead of `comment_form()`
	 *
	 * @since 1.1.1
	 * @return string
	 */
	public function add_recaptcha_after_comment_field($form_defaults)
	{
		$recaptcha_html = $this->get_comment_recaptcha_html();

		$form_defaults['comment_notes_after'] = !isset($form_defaults['comment_notes_after'])
			? $recaptcha_html : $form_defaults['comment_notes_after'] . "\n" . $recaptcha_html;

		return $form_defaults;
	}

	/**
	 * Adds captcha to just before the submit field
	 *
	 * This makes use of the `comment_form_submit_field` filter
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function add_recaptcha_before_comment_submit_field($submit_field)
	{
		$recaptcha_html = $this->get_comment_recaptcha_html();

		return $recaptcha_html . "\n" . $submit_field;
	}

	/**
	 * This overrides Akismet's filter on the 'pre_comment_approved' hook
	 *
	 * This allows us to control a comment's status even if it is marked as
	 * spam by Akismet.
	 *
	 * @see Akismet::last_comment_status
	 */
	public function akismet_comment_status()
	{
		$bwp_capt_cs = $this->options['select_akismet_react'];
		$bwp_capt_cs = 'hold' == $bwp_capt_cs ? '0' : $bwp_capt_cs;

		if (defined('BWP_CAPT_AKISMET_COMMENT_STATUS')
			&& BWP_CAPT_AKISMET_COMMENT_STATUS == 'spam'
		) {
			// @since 1.1.1 put comment into spam queue is no longer an option
			// but can still be forced
			return 'spam';
		}

		return $bwp_capt_cs;
	}

	/**
	 * Marks that we need a recaptcha because akismet has told us that
	 * previous comment is considered spam
	 *
	 * This should redirect the commenter back to the comment form
	 *
	 * @return void
	 */
	public function add_recaptcha_after_akismet()
	{
		$comment_post_ID = isset($_POST['comment_post_ID'])
			? (int) $_POST['comment_post_ID']
			: 0;

		$this->_set_session_data('bwp_capt_previous_comment_is_spam', 'yes');
		$this->_set_session_data('bwp_capt_previous_comment', isset($_POST['comment']) ? $_POST['comment'] : '');

		$location_hash = $this->options['select_position'] == 'after_comment_field'
			? 'comment' : 'respond';

		$location = !empty($_POST['error_redirect_to'])
			? $_POST['error_redirect_to']
			: get_permalink($comment_post_ID) . '#' . $location_hash;

		wp_safe_redirect($location);
		exit;
	}

	/**
	 * Make text safe to edit inside textarea
	 *
	 * This function will be removed once requirement is moved to WP 3.1.
	 *
	 * @since 1.1.1
	 * @return string
	 */
	private function _esc_textarea($text)
	{
		return htmlspecialchars($text, ENT_QUOTES, get_option('blog_charset'));
	}

	public function fill_comment_field_with_previous_comment($comment_field)
	{
		$previous_comment = $this->_get_session_data('bwp_capt_previous_comment');

		if (!empty($previous_comment))
		{
			// put the comment content back if possible
			$comment_text  = stripslashes($previous_comment);
			$comment_field = preg_replace('#(<textarea\s[^>]*>)(.*?)(</textarea>)#uis',
				'$1' . $this->_esc_textarea($comment_text) . '$3',
				$comment_field
			);

			$this->_unset_session_data('bwp_capt_previous_comment');
		}

		return $comment_field;
	}

	/**
	 * Check captcha response for comment forms
	 *
	 * @param int $comment_post_ID
	 */
	public function check_comment_recaptcha($comment_post_ID)
	{
		if ($errors = $this->provider->verify())
		{
			$error     = current($errors);
			$errorCode = current(array_keys($errors));

			// save the previous comment in session so we can use it to fill
			// the comment field later on, but only do this when needed
			if ('redirect' == $this->options['select_response']
				&& 'yes' == $this->options['enable_auto_fill_comment']
			) {
				$this->_set_session_data('bwp_capt_previous_comment', isset($_POST['comment']) ? $_POST['comment'] : '');
			}

			if ('redirect' == $this->options['select_response'])
			{
				// since we haven't added the comment yet, we need to find
				// the link to the current comment page the visitor is on
				$location = !empty($_POST['error_redirect_to'])
					? $_POST['error_redirect_to']
					: get_permalink($comment_post_ID) . '#respond';

				$location = add_query_arg('cerror', $errorCode, $location);

				wp_safe_redirect($location);

				exit;
			}
			else if ('back' == $this->options['select_response'])
			{
				if ('invalid-response' == $error)
				{
					wp_die(sprintf($this->options['input_back'],
						' <a href="javascript:history.go(-1);">'
						. __('this link', $this->domain)
						. '</a>'));
				}
				else
				{
					wp_die($this->provider->getErrorMessage($error));
				}
			}

			return;
		}

		if ($this->_is_akismet_integration_enabled())
		{
			// recaptcha is valid, and previous comment is considered spam
			if ($this->_is_previous_comment_spam())
			{
				// do not increase Akismet spam counter
				add_filter('akismet_spam_count_incr', create_function('', 'return 0;'), 11);

				// use the correct status for the marked-as-spam comment, use
				// workaround for remove_filter function
				add_filter('pre_comment_approved', array($this, 'akismet_comment_status'), 10);
				add_filter('pre_comment_approved', array($this, 'akismet_comment_status'), 11);
			}

			// reset Akismet-related data, next comment should be checked again
			// from the beginning
			$this->_unset_session_data('bwp_capt_previous_comment');
			$this->_unset_session_data('bwp_capt_previous_comment_is_spam');
		}
	}

	protected function get_comment_recaptcha_html()
	{
		ob_start();

		$this->add_comment_recaptcha();
		$recaptcha_html = ob_get_contents();

		ob_end_clean();

		return $recaptcha_html;
	}

	/**
	 * Check captcha response for login form
	 *
	 * @filter authenticate, @see ::wp-signon in package WordPress/pluggable.php
	 * @since 1.1.0
	 * @return WP_User if captcha is ok
	 *         WP_Error if captcha is NOT ok
	 */
	public function check_login_recaptcha($user)
	{
		if (empty($_POST['log']) && empty($_POST['pwd']))
			return $user;

		// if the $user object itself is a WP_Error object, we simply append
		// errors to it, otherwise we create a new one.
		$errors = is_wp_error($user) ? $user : new WP_Error();

		if ($captchaErrors = $this->provider->verify())
		{
			$errors->add('recaptcha-error', $this->provider->getErrorMessage(current($captchaErrors)));

			// invalid recaptcha detected, the returned $user object should be
			// a WP_Error object
			$user = is_wp_error($user) ? $user : $errors;

			// do not allow WordPress to try authenticating the user, either
			// using cookie or username/password pair
			remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
			remove_filter('authenticate', 'wp_authenticate_cookie', 30, 3);
		}

		return $user;
	}

	/**
	 * Check captcha response on registration page
	 *
	 * This function is used in two scenarios:
	 * 1. When a user registers for a new accont on a standard WordPress installation
	 * 2. When a user registers for a new account OR registers for a blog on a
	 *    multisite WordPress installation
	 *
	 * Either way we should always receive a WP_Error instance to inject our
	 * errors into.
	 *
	 * @since 1.1.0
	 * @param WP_Error $errors
	 * @return WP_Error
	 */
	public function check_reg_recaptcha(WP_Error $errors)
	{
		if ($captchaErrors = $this->provider->verify())
			$errors->add('recaptcha-error', $this->provider->getErrorMessage(current($captchaErrors)));

		return $errors;
	}

	/**
	 * Adds a captcha to multisite user registration page
	 *
	 * @since 2.0.0
	 * @param WP_Error $errors
	 */
	public function add_multisite_user_reg_recaptcha(WP_Error $errors)
	{
		$this->add_recaptcha($errors);
	}

	/**
	 * Adds a captcha to multisite blog registration page
	 *
	 * @since 1.1.0
	 * @param WP_Error $errors
	 */
	public function add_multisite_blog_reg_recaptcha(WP_Error $errors)
	{
		// only show a captcha when user is already registered and is
		// registering a new blog
		if (is_user_logged_in())
			$this->add_recaptcha($errors);
	}

	/**
	 * Check captcha when registering for a new user account on a multisite
	 * installation
	 *
	 * This should validate captcha when the user form is submitted, current
	 * stage is 'validate-user-signup' @see wp-signup.php::signup_user
	 *
	 * @param array $result use $result['errors'] to get a WP_Error instance
	 * @return array
	 */
	public function check_multisite_user_reg_recaptcha(array $result)
	{
		if (isset($_POST['stage']) && 'validate-blog-signup' == $_POST['stage'])
		{
			// user is registering a new blog, we don't need to check anything
			// because captcha is not required at this stage
			return $result;
		}
		else
		{
			$result['errors'] = $this->check_reg_recaptcha($result['errors']);
			return $result;
		}
	}

	/**
	 * Check captcha when registering for a new blog on a multisite
	 * installation
	 *
	 * This should only validate the captcha if the user is already registered
	 * and is registering a new blog, current stag is 'validate-blog-signup'
	 * @see wp-signup.php::signup_blog
	 *
	 * @param array $result use $result['errors'] to get a WP_Error instance
	 * @return array
	 */
	public function check_multisite_blog_reg_recaptcha(array $result)
	{
		if (is_user_logged_in())
			$result['errors'] = $this->check_reg_recaptcha($result['errors']);

		return $result;
	}
}
