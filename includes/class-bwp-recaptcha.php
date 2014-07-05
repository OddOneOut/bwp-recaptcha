<?php
/**
 * Copyright (c) 2014 Khang Minh <betterwp.net>
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

	remove_action('comment_form_after_fields', array($bwp_capt, 'add_recaptcha'));
	remove_action('comment_form_logged_in_after', array($bwp_capt, 'add_recaptcha'));
	remove_filter('comment_form_defaults', array($bwp_capt, 'add_recaptcha_after_comment_field'));

	ob_start();

	do_action('bwp_recaptcha_add_markups');
	$recaptcha_html = ob_get_contents();

	ob_end_clean();

	if (isset($args['comment_notes_after']))
		$args['comment_notes_after'] .= $recaptcha_html;
	else
		$args['comment_notes_after'] = $recaptcha_html;

	comment_form($args, $post_id);
}

if (!class_exists('BWP_FRAMEWORK_IMPROVED'))
	require_once('class-bwp-framework-improved.php');

class BWP_RECAPTCHA extends BWP_FRAMEWORK_IMPROVED
{
	/**
	 * reCAPTCHA built-in languages
	 */
	var $lang;

	/**
	 * User capabilities to bypass captcha
	 */
	var $caps;

	/**
	 * Is registering via wp-login.php
	 */
	var $is_reg = false;

	/**
	 * Captcha error message when registering via wp-login.php
	 */
	var $reg_errors = false;

	/**
	 * Is signing up (multi-site only)
	 */
	var $is_signup = false;

	/**
	 * Is logging in via wp-login.php
	 */
	var $is_login = false;

	/**
	 * Constructor
	 */
	public function __construct($version = '1.1.2')
	{
		// Plugin's title
		$this->plugin_title = 'Better WordPress reCAPTCHA';
		// Plugin's version
		$this->set_version($version);
		// Plugin's language domain
		$this->domain = 'bwp-recaptcha';
		// Basic version checking
		if (!$this->check_required_versions())
			return;

		// Default options
		$options = array(
			'input_pubkey'         => '',
			'input_prikey'         => '',
			'input_error'          => __('<strong>ERROR:</strong> Incorrect or '
				. 'empty reCAPTCHA response, please try again.', $this->domain),
			'input_back'           => __('Error: Incorrect or empty reCAPTCHA response, '
				. 'please click the back button on your browser\'s toolbar or '
				. 'click on %s to go back.', $this->domain),
			'input_approved'       => 1,
			'input_tab'            => 0,
			'enable_comment'       => 'yes',
			'enable_registration'  => 'yes',
			'enable_login'         => '',
			'enable_akismet'       => '',
			'enable_css'           => 'yes',
			'use_global_keys'      => 'yes',
			'select_lang'          => 'en',
			'select_theme'         => 'red',
			'select_cap'           => 'manage_options',
			'select_cf7_tag'       => 'bwp-recaptcha',
			'select_response'      => 'redirect',
			'select_position'      => 'after_comment_field',
			'select_akismet_react' => 'hold',
			'hide_registered'      => 'yes',
			'hide_cap'             => '',
			'hide_approved'        => 'yes'
		);

		$this->add_option_key('BWP_CAPT_OPTION_GENERAL', 'bwp_capt_general',
			__('General Options', $this->domain)
		);
		$this->add_option_key('BWP_CAPT_OPTION_THEME', 'bwp_capt_theme',
			__('Theme Options', $this->domain)
		);

		$this->build_properties('BWP_CAPT', $this->domain, $options,
			'BetterWP reCAPTCHA', dirname(dirname(__FILE__)) . '/bwp-recaptcha.php',
			'http://betterwp.net/wordpress-plugins/bwp-recaptcha/', false);
	}

	private function _load_template_functions()
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
		?>
				<script type="text/javascript">
					var RecaptchaOptions = {
						theme : 'custom',
						custom_theme_widget: 'recaptcha_widget',
						tabindex: <?php echo (int) $bwp_capt->options['input_tab']; echo "\n"; ?>
					};
				</script>
				<div id="recaptcha_widget" style="display: none;">
					<p class="recaptcha_only_if_incorrect_sol">
						<?php echo $bwp_capt->options['input_error']; ?>
					</p>
					<div id="recaptcha_image"></div>
					<div class="recaptcha_control">
						<a href="javascript:Recaptcha.reload()" title="<?php _e('Get another challenge', $bwp_capt->domain); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_refresh.png'; ?>" alt="<?php _e('Get another challenge', $bwp_capt->domain); ?>" /></a>
						<span class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')" title="<?php _e('Get audio reCAPTCHA', $bwp_capt->domain); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_sound.png'; ?>" alt="<?php _e('Get audio reCAPTCHA', $bwp_capt->domain); ?>" /></a></span>
						<span class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')" title="<?php _e('Get image reCAPTCHA', $bwp_capt->domain); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_image.png'; ?>" alt="<?php _e('Get image reCAPTCHA', $bwp_capt->domain); ?>" /></a></span>
						<span><a href="javascript:Recaptcha.showhelp()" title="<?php _e('About reCAPTCHA', $bwp_capt->domain); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_help.png'; ?>" alt="<?php _e('About reCAPTCHA', $bwp_capt->domain); ?>" /></a></span>
					</div>

					<div class="recaptcha_text">
						<span class="recaptcha_only_if_image"><label for="recaptcha_response_field"><em><small><?php _e('Enter the two words in the box:', $bwp_capt->domain); ?></small></em></label></span>
						<span class="recaptcha_only_if_audio"><label for="recaptcha_response_field"><em><small><?php _e('Enter the numbers you hear:', $bwp_capt->domain); ?></small></em></label></span>
						<input type="text" id="recaptcha_response_field" tabindex="<?php echo (int) $bwp_capt->options['input_tab']; ?>" class="input" name="recaptcha_response_field" />
					</div>
				</div>
		<?php
		}

		endif;
	}

	private function _set_session_data($key, $value)
	{
		// use $_SESSION, if $_SESSION isn't available there's no alternative
		// for now
		if (!isset($_SESSION))
		{
			return;
		}

		$_SESSION[$key] = trim($value);
	}

	private function _unset_session_data($key)
	{
		if (isset($_SESSION[$key]))
		{
			unset($_SESSION[$key]);
		}
	}

	/**
	 * Checks if captcha is required based on akismet integration
	 *
	 * @since 1.1.1
	 * @return bool
	 */
	private function _is_captcha_required()
	{
		if (!defined('AKISMET_VERSION') || 'yes' != $this->options['enable_akismet'])
			return true;

		if ($this->_is_comment_spam())
			return true;

		return false;
	}

	private function _is_comment_spam()
	{
		if (isset($_SESSION['bwp_capt_comment_is_spam'])
			&& 'yes' == $_SESSION['bwp_capt_comment_is_spam']
		) {
			return true;
		}

		return false;
	}

	protected function pre_init_properties()
	{
		$this->lang = array(
			__('English', $this->domain)    => 'en',
			__('Dutch', $this->domain)      => 'nl',
			__('French', $this->domain)     => 'fr',
			__('German', $this->domain)     => 'de',
			__('Italian', $this->domain)    => 'it',
			__('Portuguese', $this->domain) => 'pt',
			__('Russian', $this->domain)    => 'ru',
			__('Spanish', $this->domain)    => 'es',
			__('Turkish', $this->domain)    => 'tr'
		);

		$this->caps = apply_filters('bwp_capt_bypass_caps', array(
			__('Read Profile', $this->domain)   => 'read',
			__('Manage Options', $this->domain) => 'manage_options'
		));

		// @since 1.1.0 init public and private keys based on multi-site setting
		$this->init_captcha_keys();

		if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false
			&& !empty($_REQUEST['action']) && 'register' == $_REQUEST['action']
		) {
			// whether user is requesting regular user registration page
			$this->is_reg = true;
		}

		if (strpos($_SERVER['REQUEST_URI'], 'wp-signup.php') !== false)
		{
			// whether user is requesting wp-signup page (multi-site page for
			// user/site registration)
			$this->is_signup = true;
		}

		if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false
			&& empty($_REQUEST['action'])
		) {
			// whether user is requesting the wp-login page
			$this->is_login = true;
		}
	}

	protected function pre_init_hooks()
	{
		if (!is_admin() && !isset($_SESSION))
		{
			// start a session to store Akismet and comment data between
			// requests, only on front end and when applicable
			if (headers_sent() && (!defined('WP_DEBUG') || !WP_DEBUG))
				return;

			session_start();
		}
	}

	protected function init_hooks()
	{
		// load custom template function at this stage to make it pluggable
		$this->_load_template_functions();

		if (defined('WPCF7_VERSION'))
		{
			// add support for Contact Form 7 (CF7) automatically if CF7 is
			// installed and activated
			include_once dirname(__FILE__) . '/class-bwp-recaptcha-cf7.php';
			BWP_RECAPTCHA_CF7::init($this);
		}

		if (!empty($this->options['input_pubkey']) && !empty($this->options['input_prikey']))
		{
			// this action needs to be added when a captcha is manually needed
			add_action('bwp_recaptcha_add_markups', array($this, 'add_recaptcha'));

			if ('yes' == $this->options['enable_comment'])
			{
				// add captcha to comment form
				if (!$this->_is_captcha_required())
				{
					// if user chooses to integrate with akismet, only show
					// recaptcha when comment is marked as spam
					add_action('akismet_spam_caught', array($this, 'add_recaptcha_after_akismet'));
				}
				else if (!$this->user_can_bypass())
				{
					if ($this->options['select_position'] == 'after_fields')
					{
						// show captcha after website field
						add_action('comment_form_after_fields', array($this, 'add_recaptcha'));
						add_action('comment_form_logged_in_after', array($this, 'add_recaptcha'));
					}
					elseif ($this->options['select_position'] == 'after_comment_field')
					{
						// show captcha after comment field (default @since 1.1.1)
						add_filter('comment_form_defaults', array($this, 'add_recaptcha_after_comment_field'));
					}

					// fill the comment textarea
					add_filter('comment_form_field_comment', array($this, 'fill_comment_content'));

					// check entered captcha for comment form
					add_action('pre_comment_on_post', array($this, 'check_recaptcha'));
				}
			}

			if ('yes' == $this->options['enable_registration'] && $this->is_reg)
			{
				// normal user registration page
				add_action('register_form', array($this, 'add_recaptcha'));
				add_filter('registration_errors', array($this, 'check_reg_recaptcha'));
			}

			if ('yes' == $this->options['enable_registration'] && $this->is_signup)
			{
				// wpms user/site registration page
				add_action('signup_extra_fields', array($this, 'add_recaptcha'));
				add_action('signup_blogform', array($this, 'add_blog_reg_recaptcha'));
				add_filter('wpmu_validate_user_signup', array($this, 'check_user_reg_recaptcha'));
				add_filter('wpmu_validate_blog_signup', array($this, 'check_blog_reg_recaptcha'));
			}

			if ('yes' == $this->options['enable_login'] && $this->is_login)
			{
				// @since 1.1.0 add captcha to login form
				add_action('login_form', array($this, 'add_recaptcha'));

				// the priority of 15 is to ensure that we run the filter before
				// WordPress authenticates the user.
				add_filter('authenticate', array($this, 'authenticate_with_recaptcha'), 15);
			}
		}
	}

	protected function init_captcha_keys()
	{
		if (self::is_multisite())
		{
			if ('yes' == $this->options['use_global_keys'])
			{
				$site_options = get_site_option(BWP_CAPT_OPTION_GENERAL);
				$this->options['input_pubkey'] = $site_options['input_pubkey'];
				$this->options['input_prikey'] = $site_options['input_prikey'];
			}
		}
	}

	protected function enqueue_media()
	{
		if ($this->is_admin_page(BWP_CAPT_OPTION_GENERAL))
		{
			// some JS to toggle fields' visibility in General Options page
			wp_enqueue_script('bwp-recaptcha', BWP_CAPT_JS . '/bwp-recaptcha.js', array('jquery'));
		}

		if ('yes' == $this->options['enable_css'])
		{
			// load default CSS if needed
			$theme        = $this->options['select_theme'];
			$bwp_capt_css = apply_filters('bwp_capt_css', BWP_CAPT_CSS . '/bwp-recaptcha.css');

			if ('custom' == $this->options['select_theme']
				&& ($this->is_admin_page(BWP_CAPT_OPTION_THEME) || !is_admin())
			) {
				// load default CSS for custom theme
				wp_enqueue_style('bwp-capt', $bwp_capt_css);
			}

			if (('yes' == $this->options['enable_registration'] && $this->is_reg)
				|| ('yes' == $this->options['enable_login'] && $this->is_login)
			) {
				add_action('login_head', array($this, 'print_inline_styles_for_login'), 11); // make sure this is late enough
			}
		}
	}

	public function load_captcha_library()
	{
		if (!function_exists('recaptcha_get_html')
			|| !function_exists('recaptcha_check_answer')
		) {
			require_once(dirname(__FILE__) . '/recaptcha/recaptchalib.php');
		}
	}

	public function print_inline_styles_for_login()
	{
		$login_width = 'clean' == $this->options['select_theme'] ? 482 : 362;
?>
		<style type="text/css">
			#login {
				width: <?php echo $login_width; ?>px;
			}
			#recaptcha_widget_div {
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
			array($this, 'build_option_pages'),
			BWP_CAPT_IMAGES . '/icon_menu.png'
		);

		add_submenu_page(
			BWP_CAPT_OPTION_GENERAL,
			__('BWP reCAPTCHA General Options', $this->domain),
			__('General Options', $this->domain),
			BWP_CAPT_CAPABILITY,
			BWP_CAPT_OPTION_GENERAL,
			array($this, 'build_option_pages')
		);

		add_submenu_page(
			BWP_CAPT_OPTION_GENERAL,
			__('BWP reCAPTCHA Theme Options', $this->domain),
			__('Theme Options', $this->domain),
			BWP_CAPT_CAPABILITY,
			BWP_CAPT_OPTION_THEME,
			array($this, 'build_option_pages')
		);
	}

	public function modify_option_page()
	{
		global $blog_id;
?>
		<script type="text/javascript">
<?php if (self::is_multisite() && 1 < $blog_id) { ?>
			var rc_readonly = <?php echo 'yes' == $this->options['use_global_keys'] ? 'true' : 'false'; ?>;
			jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', rc_readonly);
			jQuery('input[name="use_global_keys"]').on('click', function() {
				if (jQuery(this).prop('checked')) {
					jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', true);
				} else {
					jQuery('input[name="input_pubkey"], input[name="input_prikey"]').prop('readOnly', false);
				}
			});
<?php } else { ?>
			jQuery('input[name="use_global_keys"]').parents('li.bwp-clear').hide();
<?php } ?>
		</script>
<?php
	}

	/**
	 * Build the option pages
	 *
	 * Utilizes BWP Option Page Builder (@see BWP_OPTION_PAGE)
	 */
	public function build_option_pages()
	{
		if (!current_user_can(BWP_CAPT_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$page            = $_GET['page'];
		$bwp_option_page = new BWP_OPTION_PAGE($page, $this->site_options, $this->domain);

		$options = array();

		if (!empty($page))
		{
			if ($page == BWP_CAPT_OPTION_GENERAL)
			{
				$bwp_option_page->set_current_tab(1);

				// Option Structures - Form
				$form = array(
					'items' => array(
						'heading',
						'checkbox',
						'input',
						'input',
						'heading',
						'section',
						'section',
						'heading',
						'select',
						'select',
						'input',
						'input',
						'heading',
						'checkbox',
						'select',
						'heading',
						'select'
					),
					'item_labels' => array(
						__('reCAPTCHA API Keys', $this->domain),
						__('Use main site\'s keys', $this->domain),
						__('Public Key', $this->domain),
						__('Private Key', $this->domain),
						__('Plugin Functionality', $this->domain),
						__('Enable this plugin for', $this->domain),
						__('Hide captcha for', $this->domain),
						__('reCAPTCHA for comment form', $this->domain),
						__('Captcha position', $this->domain),
						__('If invalid captcha response', $this->domain),
						__('Invalid captcha error message', $this->domain),
						__('Invalid captcha error message', $this->domain),
						__('Akismet Integration for comment form', $this->domain),
						__('Integrate with Akismet?', $this->domain),
						__('If correct captcha response', $this->domain),
						__('Contact Form 7 Integration', $this->domain),
						__('Captcha shortcode tag', $this->domain)
					),
					'item_names' => array(
						'h1',
						'cb7',
						'input_pubkey',
						'input_prikey',
						'heading_func',
						'sec1',
						'sec2',
						'h2',
						'select_position',
						'select_response',
						'input_error',
						'input_back',
						'h3',
						'cb6',
						'select_akismet_react',
						'heading_cf7',
						'select_cf7_tag'
					),
					'heading' => array(
						'h1' => '<em>' . sprintf(
							__('For this plugin to work, you will need '
							. 'a pair of API keys (public and private), '
							. 'which is available for free <a href="%s" target="_blank">here</a>. '
							. 'Once you have created those two keys for this domain, '
							. 'simply paste them below.</em>', $this->domain),
							'https://www.google.com/recaptcha/admin/create'),
						'heading_func' => '<em>' . __('Control how this plugin works.', $this->domain) . '</em>',
						'h2' => '<em>' . __('Settings that are applied to '
							. 'comment forms only.', $this->domain) . '</em>',
						'h3' => '<em>' . __('Integrate with Akismet for better end-user experience.', $this->domain) . '</em>',
						'heading_cf7' => '<em>' . __('Add reCAPTCHA to Contact Form 7. '
							. 'This only works if you have Contact Form 7 activated.', $this->domain) . '</em>'
					),
					'sec1' => array(
						array('checkbox', 'name' => 'cb1'),
						array('checkbox', 'name' => 'cb2'),
						array('checkbox', 'name' => 'cb8')
					),
					'sec2' => array(
						array('checkbox', 'name' => 'cb3'),
						array('checkbox', 'name' => 'cb4'),
						array('checkbox', 'name' => 'cb5')
					),
					'select' => array(
						'select_lang' => $this->lang,
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
						),
						'select_cf7_tag' => array(
							__('Use "bwp-recaptcha" shortcode tag', $this->domain) => 'bwp-recaptcha',
							__('Use "recaptcha" shortcode tag', $this->domain) => 'recaptcha'
						)
					),
					'checkbox'	=> array(
						'cb1' => array(
							__('Comment form.', $this->domain) => 'enable_comment'
						),
						'cb2' => array(
							__('Registration form (user/site registration).', $this->domain) => 'enable_registration'
						),
						'cb8' => array(
							__('Login form.', $this->domain) => 'enable_login'
						),
						'cb3' => array(
							__('registered users <em>(even without any capabilities)</em>.', $this->domain) => 'hide_registered'
						),
						'cb4' => array(
							__('users who can', $this->domain) => 'hide_cap'
						),
						'cb5' => array(
							__('visitors who have at least', $this->domain) => 'hide_approved'
						),
						'cb6' => array(
							__('A captcha is only shown when Akismet identifies a comment as spam. '
							. 'Highly recommended if you do not want to '
							. 'force your visitors to enter a captcha every time.', $this->domain) => 'enable_akismet'
						),
						'cb7' => array(
							__('uncheck to use different key pairs for this site.', $this->domain) => 'use_global_keys'
						)
					),
					'input'	=> array(
						'input_pubkey'   => array(
							'size' => 50,
							'label' => '<br />' . __('A public key used to '
								. 'request captchas from reCAPTCHA server.', $this->domain)
						),
						'input_prikey'   => array(
							'size' => 50,
							'label' => '<br />' . __('A private (secret) key used to '
								. 'authenticate user\'s response.', $this->domain)
						),
						'input_error'    => array(
							'size' => 90,
							/* 'label' => '<br />' . __('when redirect commenter back to the comment form.', $this->domain) */
						),
						'input_back'     => array(
							'size' => 90,
							/* 'label' => '<br />' . __('when show the normal error page with no redirection.', $this->domain) */
						),
						'input_approved' => array(
							'size' => 3,
							'label' => __('approved comment(s).', $this->domain)
						),
					),
					'container' => array(
						'cb8'                  => ''
					),
					'post' => array(
						'select_akismet_react' => '<br /><span style="display:inline-block;margin-top:5px;">'
							. __('It is best to put comments identified as spam in moderation queue '
							. 'so you are able to review and instruct '
							. 'Akismet to correctly handle similar comments in the future.</em>', $this->domain)
							. '</span>'
					),
					'inline_fields' => array(
						'cb4' => array('select_cap' => 'select'),
						'cb5' => array('input_approved' => 'input')
					)
				);

				// Get the default options
				$options = $bwp_option_page->get_options(array(
					'use_global_keys',
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
					'input_back',
					'select_position',
					'select_cf7_tag',
					'select_response',
					'enable_akismet',
					'select_akismet_react'
				), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);

				$option_formats = array(
					'input_approved' => 'int',
					'input_error' => 'html',
					'input_back' => 'html'
				);

				$option_super_admin = array();

				// show appropriate fields based on multi-site setting
				add_action('bwp_option_action_before_submit_button', array($this, 'modify_option_page'));
			}
			else if ($page == BWP_CAPT_OPTION_THEME)
			{
				$bwp_option_page->set_current_tab(2);

				// Option Structures - Form
				$form = array(
					'items' => array(
						'select',
						'checkbox',
						'select',
						'input',
						'heading'
					),
					'item_labels' => array(
						__('reCAPTCHA theme', $this->domain),
						__('Use default CSS', $this->domain),
						__('Language for built-in themes', $this->domain),
						__('Tabindex for captcha input field', $this->domain),
						__('Preview your reCAPTCHA', $this->domain)
					),
					'item_names' => array(
						'select_theme',
						'cb1',
						'select_lang',
						'input_tab',
						'h1'
					),
					'heading' => array(
						'h1' => __('<em>Below you will see how your reCAPTCHA will look. '
							. 'Note that this might differ on your actual pages.<br /></em>', $this->domain)
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
						'cb1' => array(
							sprintf(__('This is for Custom Theme only. '
								. 'Disable this and add your own CSS to style the Custom Theme. '
								. 'More info <a href="%s#customization" target="_blank">here</a>.', $this->domain),
								BWP_CAPT_PLUGIN_URL) => 'enable_css'
						),
						'cb2' => array(
							__('This is only useful when you do not use any minify or cache plugin.', $this->domain) => 'enable_selective'
						)
					),
					'input'	=> array(
						'input_tab' => array(
							'size' => 3,
							'label' => '<br />' . __('This should be 4 if you '
							. 'place the captcha before the textarea, '
							. 'and 5 if you put it after. Set to 0 to disable.', $this->domain)
						)
					),
					'container' => array(
					),
					'post' => array(
						'select_lang' => sprintf(__('If you would like to add custom translations, '
							. 'please read <a href="%s" target="_blank">this dedicated tip</a>.', $this->domain),
							'http://betterwp.net/wordpress-tips/how-to-add-custom-translations-to-bwp-recaptcha/')
					)
				);

				// Get the default options
				$options = $bwp_option_page->get_options(array(
					'select_lang',
					'select_theme',
					'input_tab',
					'enable_css',
				), $this->options);

				// Get option from the database
				$options = $bwp_option_page->get_db_options($page, $options);

				$option_formats = array(
					'input_tab' => 'int'
				);

				$option_super_admin = array();

				// preview reCAPTCHA
				add_action('bwp_option_action_before_submit_button', array($this, 'add_recaptcha'));
			}
		}

		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()])
			&& isset($options) && is_array($options))
		{
			// basic security check
			check_admin_referer($page);

			foreach ($options as $key => &$option)
			{
				if (isset($_POST[$key]))
				{
					$bwp_option_page->format_field($key, $option_formats);
					$option = trim(stripslashes($_POST[$key]));
				}
				else
				{
					if (!isset($_POST[$key]))
					{
						// for checkboxes that are not checked
						$option = '';
					}
					else if (isset($option_formats[$key])
						&& 'int' == $option_formats[$key]
						&& ('' === $_POST[$key] || 0 > $_POST[$key])
					) {
						// expect integer but received empty string or negative integer
						$option = $this->options_default[$key];
					}
				}
			}

			// update per-blog options
			update_option($page, $options);

			global $blog_id;
			if (!$this->is_normal_admin() && $blog_id == 1)
			{
				// Update site options if is super admin and is on main site
				update_site_option($page, $options);
			}

			// refresh the options property to include updated options
			$this->options = array_merge($this->options, $options);

			// Update options successfully
			$this->add_notice(__('All options have been saved.', $this->domain));
		}

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

		// Assign the form and option array
		$bwp_option_page->init($form, $options, $this->form_tabs);

		// Build the option page
		$bwp_option_page->generate_html_form();
	}

	/**
	 * Get the link to the current comment page
	 */
	protected function get_current_comment_page_link()
	{
		global $wp_rewrite;

		if (!is_singular() || !get_option('page_comments'))
			return;

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

		if ('yes' == $this->options['hide_registered'] && is_user_logged_in())
			// do not show captcha to logged in users
			return true;

		if ('yes' == $this->options['hide_cap']
			&& !empty($this->options['select_cap'])
			&& current_user_can($this->options['select_cap']))
		{
			// user must have certain capabilities in order to bypass captcha
			return true;
		}

		if ('yes' == $this->options['hide_approved'])
		{
			$commenter = wp_get_current_commenter();
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
				return true;
		}

		return false;
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

	public function fill_comment_content($comment_field)
	{
		if ('back' == $this->options['select_response'])
		{
			// no need to refill the comment contents if no redirection is involved
			return $comment_field;
		}

		if (!empty($_SESSION['bwp_capt_comment']))
		{
			// put the comment content back if possible
			$comment_text  = stripslashes($_SESSION['bwp_capt_comment']);
			$comment_field = preg_replace('#(<textarea\s[^>]*>)(.*?)(</textarea>)#uis',
				'$1' . $this->_esc_textarea($comment_text) . '$3',
				$comment_field
			);

			$this->_unset_session_data('bwp_capt_comment');
		}

		return $comment_field;
	}

	/**
	 * Output the reCAPTCHA HTML, use theme if specified
	 *
	 * @param $errors @since 1.1.0
	 * @return void
	 */
	public function add_recaptcha($errors = false)
	{
		$this->load_captcha_library();

		if (!defined('BWP_CAPT_ADDED'))
		{
			// make sure we add only one recaptcha instance
			define('BWP_CAPT_ADDED', true);

			// captcha error can comes from $_GET variable or passed via
			// hooks' parameters.
			$captcha_error = '';
			$extra_class   = '';

			if (!empty($_GET['cerror']) && 'incorrect-captcha-sol' == $_GET['cerror'])
			{
				$captcha_error = $_GET['cerror'];
			}
			else if (is_wp_error($errors))
			{
				// right now only registration errors are passed this way
				$captcha_error = $errors->get_error_message('reg-recaptcha-error');
				$extra_class   = ' error';
			}

			if ($this->_is_comment_spam())
			{
?>
		<p class="bwp-capt-spam-identified">
			<?php _e('Your comment was identified as spam, '
			. 'please complete the CAPTCHA below:', $this->domain); ?>
		</p>
<?php
			}

			do_action('bwp_capt_before_add_captcha');

?>
		<style type="text/css">
			/* this is to prevent the iframe from showing up in Chrome */
			iframe[src="about:blank"] {
				display: none;
			}
		</style>
<?php
			if ($this->options['select_theme'] != 'custom')
			{

				if (!empty($captcha_error))
				{
?>
		<p class="recaptcha_only_if_incorrect_sol<?php echo $extra_class; ?>">
			<?php echo $this->options['input_error']; ?>
		</p>
<?php
				}
?>
		<script type="text/javascript">
			var RecaptchaOptions = (typeof CustomRecaptchaOptions === 'undefined') ? {
				theme: '<?php echo $this->options['select_theme']; ?>',
				lang: '<?php echo $this->options['select_lang']; ?>',
<?php
				if (!empty($this->options['input_tab'])) {
?>
				tabindex: <?php echo (int) $this->options['input_tab']; echo "\n"; ?>
<?php
				}
?>
			} : CustomRecaptchaOptions;
		</script>
<?php
			}
			else
			{
				bwp_capt_custom_theme_widget();
			}

			if ('redirect' == $this->options['select_response'] && !is_admin())
			{
?>
			<input type="hidden" name="error_redirect_to"
				value="<?php esc_attr_e($this->get_current_comment_page_link()); ?>" />
<?php
			}

			$use_ssl = is_ssl() ? true : false;

			if (!empty($this->options['input_pubkey']))
			{
				echo recaptcha_get_html(
					$this->options['input_pubkey'],
					$captcha_error,
					$use_ssl,
					$this->options['select_lang']
				);
			}
			else if (current_user_can('manage_options'))
			{
				// if user is an admin show the actual error
				printf(__('To use reCAPTCHA you must get an API key from '
					. '<a href="%1$s">%1$s</a>', $this->domain),
					'https://www.google.com/recaptcha/admin/create');
			}
			else
				echo '';
		}
	}

	/**
	 * Adds captcha to below comment field
	 *
	 * @since 1.1.1
	 * @return string
	 */
	public function add_recaptcha_after_comment_field($form_defaults)
	{
		ob_start();

		do_action('bwp_recaptcha_add_markups');
		$recaptcha_html = ob_get_contents();

		ob_end_clean();

		$form_defaults['comment_notes_after'] = !isset($form_defaults['comment_notes_after'])
			? $recaptcha_html : $form_defaults['comment_notes_after'] . $recaptcha_html;

		return $form_defaults;
	}

	/**
	 * Adds a captcha to blog registration page
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function add_blog_reg_recaptcha($errors)
	{
		// if user is not logged in, they can choose between signup for account
		// or sign up for blog after account creation.
		if (!is_user_logged_in())
		{
			// if user chooses to signup for blog also, we don't show recaptcha
			// again because they have already provided the captcha once.
		}
		elseif (!$this->user_can_bypass())
		{
			// user is logged in and wants to add a new blog, show captcha if
			// user can not bypass
			$this->add_recaptcha($errors);
		}
	}

	/**
	 * Authenticates the user using captcha when logging in
	 *
	 * @filter authenticate, @see ::wp-signon in package WordPress/pluggable.php
	 * @since 1.1.0
	 * @return WP_User
	 */
	public function authenticate_with_recaptcha($user)
	{
		if (empty($_POST['log']) && empty($_POST['pwd']))
		{
			return $user;
		}

		// if the $user object itself is a WP_Error object, we simply append
		// errors to it, otherwise we create a new one.
		$errors = is_wp_error($user) ? $user : new WP_Error();

		if (!isset($_POST['recaptcha_challenge_field'])
			|| !isset($_POST["recaptcha_response_field"])
		) {
			// captcha response is empty, show error message
			$errors->add('login-recaptcha-error', $this->options['input_error']);
		}
		else
		{
			$this->load_captcha_library();

			if (function_exists('recaptcha_check_answer'))
			{
				// check catpcha response error using recaptcha's PHP library
				$response = recaptcha_check_answer(
					$this->options['input_prikey'],
					$_SERVER['REMOTE_ADDR'],
					$_POST['recaptcha_challenge_field'],
					$_POST['recaptcha_response_field']
				);

				if (!$response->is_valid)
				{
					// incorrect captcha response show error message
					if ($response->error == 'incorrect-captcha-sol')
					{
						$errors->add('login-recaptcha-error', $this->options['input_error']);
					}
					else
					{
						$errors->add('login-recaptcha-error', '<strong>'
							. __('ERROR', $this->domain) . '</strong>: '
							. __('Unknown captcha error.', $this->domain));
					}
				}
			}
		}

		$errorCodes = $errors->get_error_codes();
		if (in_array('login-recaptcha-error', $errorCodes))
		{
			// invalid recaptcha detected, exit the login process and
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
	 * This function should be used on both user registration page (single or
	 * multisite) and blog registration page (multisite).
	 *
	 * @since 1.1.0
	 * @return WP_Error
	 */
	public function check_reg_recaptcha($errors)
	{
		$this->load_captcha_library();

		if (is_array($errors) && isset($errors['errors']))
		{
			// if $errors is an array, we're probably checking recaptcha for Multi-Site WP
			$temp = $errors;
			$errors = $errors['errors'];
		}

		if (!isset($_POST['recaptcha_challenge_field'])
			|| !isset($_POST["recaptcha_response_field"])
		) {
			$errors->add('reg-recaptcha-error', $this->options['input_error']);
			$stop = true;
		}

		if (empty($stop) && function_exists('recaptcha_check_answer'))
		{
			$response = recaptcha_check_answer(
				$this->options['input_prikey'],
				$_SERVER['REMOTE_ADDR'],
				$_POST['recaptcha_challenge_field'],
				$_POST['recaptcha_response_field']
			);

			if (!$response->is_valid)
			{
				if ($response->error == 'incorrect-captcha-sol')
				{
					$errors->add('reg-recaptcha-error', $this->options['input_error']);
				}
				else
				{
					$errors->add('reg-recaptcha-error', '<strong>'
						. __('ERROR', $this->domain) . '</strong>: '
						. __('Unknown captcha error.', $this->domain));
				}
			}
		}

		if (isset($temp))
		{
			$temp['errors'] = $errors;
			$errors = $temp;
		}

		return $errors;
	}

	public function check_user_reg_recaptcha($errors)
	{
		if (isset($_POST['stage']) && 'validate-blog-signup' == $_POST['stage'])
		{
			// user is registering a new blog, we don't need to check anything
			// because captcha is not required at this stage
			return $errors;
		}
		else
		{
			return $this->check_reg_recaptcha($errors);
		}
	}

	public function check_blog_reg_recaptcha($errors)
	{
		if (!is_user_logged_in())
		{
			return $errors;
		}
		elseif (!$this->user_can_bypass())
		{
			// check captcha response if needed
			return $this->check_reg_recaptcha($errors);
		}

		return $errors;
	}

	/**
	 * Check captcha response for comment forms
	 *
	 * @return void
	 */
	public function check_recaptcha($comment_post_ID)
	{
		$this->load_captcha_library();

		$response = recaptcha_check_answer(
			$this->options['input_prikey'],
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);

		if (!$response->is_valid)
		{
			if (isset($_POST['comment']))
				$this->_set_session_data('bwp_capt_comment', $_POST['comment']);

			if ('redirect' == $this->options['select_response'])
			{
				// since we haven't added the comment yet, we need to find
				// the link to the current comment page the visitor is on
				$location = !empty($_POST['error_redirect_to'])
					? $_POST['error_redirect_to']
					: get_permalink($comment_post_ID) . '#respond';
				$location = add_query_arg('cerror', $response->error, $location);

				wp_safe_redirect($location);
				exit;
			}
			else if ('back' == $this->options['select_response'])
			{
				if ('incorrect-captcha-sol' == $response->error)
				{
					wp_die(sprintf($this->options['input_back'],
						' <a href="javascript:history.go(-1);">'
						. __('this link', $this->domain)
						. '</a>'));
				}
				else if (current_user_can('manage_options'))
				{
					// show actual error only to admin
					wp_die(__('There is some problem with your reCAPTCHA API keys, '
						. 'please double check them.', $this->domain));
				}
				else
				{
					wp_die(__('Unknown error. Please contact an administrator '
						. 'for more info.', $this->domain));
				}
			}
		}
		else
		{
			if ($this->_is_comment_spam())
			{
				// spam comments are handled by recaptcha so we do not increase
				// Akismet spam counter
				add_filter('akismet_spam_count_incr', create_function('', 'return 0;'), 11);

				// workaround for remove_filter function
				add_filter('pre_comment_approved', array($this, 'akismet_comment_status'), 10);
				add_filter('pre_comment_approved', array($this, 'akismet_comment_status'), 11);
			}

			// reset Akistmet-related data, next comment should be checked
			// again from the beginning
			$this->_unset_session_data('bwp_capt_comment');
			$this->_unset_session_data('bwp_capt_comment_is_spam');
		}
	}

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
	 * Adds recaptcha to comment form if akismet marks a comment as spam
	 *
	 * @return void
	 */
	public function add_recaptcha_after_akismet()
	{
		$comment_post_ID = isset($_POST['comment_post_ID'])
			? (int) $_POST['comment_post_ID']
			: 0;

		$this->_set_session_data('bwp_capt_comment_is_spam', 'yes');

		if (isset($_POST['comment']))
			$this->_set_session_data('bwp_capt_comment', $_POST['comment']);

		$location_hash = $this->options['select_position'] == 'after_comment_field'
			? 'comment' : 'respond';

		$location = !empty($_POST['error_redirect_to'])
			? $_POST['error_redirect_to']
			: get_permalink($comment_post_ID) . '#' . $location_hash;

		wp_safe_redirect($location);
		exit;
	}
}
