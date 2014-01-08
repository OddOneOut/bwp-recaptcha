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
 * This function is used to output custom reCAPTCHA theme
 *
 * By defining this function in your theme/plugin, you can override this 
 * and thus changing the html codes to suit your needs.
 */
if (!function_exists('bwp_capt_custom_theme_widget'))
{
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
				<a href="javascript:Recaptcha.reload()" title="<?php _e('Get another challenge', 'bwp-recaptcha'); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_refresh.png'; ?>" alt="<?php _e('Get another challenge', 'bwp-recaptcha'); ?>" /></a>
				<span class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type('audio')" title="<?php _e('Get audio reCAPTCHA', 'bwp-recaptcha'); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_sound.png'; ?>" alt="<?php _e('Get audio reCAPTCHA', 'bwp-recaptcha'); ?>" /></a></span>
				<span class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type('image')" title="<?php _e('Get image reCAPTCHA', 'bwp-recaptcha'); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_image.png'; ?>" alt="<?php _e('Get image reCAPTCHA', 'bwp-recaptcha'); ?>" /></a></span>
				<span><a href="javascript:Recaptcha.showhelp()" title="<?php _e('About reCAPTCHA', 'bwp-recaptcha'); ?>"><img src="<?php echo BWP_CAPT_IMAGES . '/icon_help.png'; ?>" alt="<?php _e('About reCAPTCHA', 'bwp-recaptcha'); ?>" /></a></span>
			</div>

			<div class="recaptcha_text">
				<span class="recaptcha_only_if_image"><label for="recaptcha_response_field"><em><small><?php _e('Enter the two words in the box:', 'bwp-recaptcha'); ?></small></em></label></span>
				<span class="recaptcha_only_if_audio"><label for="recaptcha_response_field"><em><small><?php _e('Enter the numbers you hear:', 'bwp-recaptcha'); ?></small></em></label></span>
				<input type="text" id="recaptcha_response_field" tabindex="<?php echo (int) $bwp_capt->options['input_tab']; ?>" class="input" name="recaptcha_response_field" />
			</div>
		</div>
<?php
}
}

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

if (!class_exists('BWP_FRAMEWORK'))
	require_once('class-bwp-framework.php');

class BWP_RECAPTCHA extends BWP_FRAMEWORK {

	/**
	 * Language
	 */
	var $lang;

	/**
	 * Capabilities
	 */
	var $caps;
	
	/**
	 * Is registering
	 */
	var $is_reg = false;

	/**
	 * Is signing up (multi-site only)
	 */
	var $is_signup = false;

	/**
	 * Is login 
	 */
	var $is_login = false;

	/**
	 * Constructor
	 */
	function __construct($version = '1.1.0')
	{
		// Plugin's title
		$this->plugin_title = 'BetterWP reCAPTCHA';
		// Plugin's version
		$this->set_version($version);
		// Basic version checking
		if (!$this->check_required_versions())
			return;
		
		// Default options
		$options = array(
			'input_pubkey' => '',
			'input_prikey' => '',
			'input_error' => __('<strong>ERROR:</strong> Incorrect or empty reCAPTCHA response, please try again.', 'bwp-recaptcha'),
			'input_back' => __('Error: Incorrect or empty reCAPTCHA response, please click the back button on your browser\'s toolbar or click on %s to go back.', 'bwp-recaptcha'),
			'input_approved' => 1,
			'input_tab'	=> 0,
			'enable_comment' => 'yes',
			'enable_registration' => 'yes',
			'enable_login' => '',
			'enable_akismet' => '',
			'enable_selective' => 'yes',
			'enable_css' => 'yes',
			'use_global_keys' => 'yes',
			'select_lang' => 'en',
			'select_theme' => 'red',
			'select_cap' => 'manage_options',
			'select_response' => 'redirect',
			'select_akismet_react' => 'hold',
			'hide_registered' => 'yes',
			'hide_cap' => '',
			'hide_approved' => 'yes'
		);

		$this->build_properties('BWP_CAPT', 'bwp-recaptcha', $options, 'BetterWP reCAPTCHA', dirname(dirname(__FILE__)) . '/bwp-recaptcha.php', 'http://betterwp.net/wordpress-plugins/bwp-recaptcha/', false);
		
		$this->add_option_key('BWP_CAPT_OPTION_GENERAL', 'bwp_capt_general', __('General Options', 'bwp-recaptcha'));
		$this->add_option_key('BWP_CAPT_OPTION_THEME', 'bwp_capt_theme', __('Theme Options', 'bwp-recaptcha'));

		$this->lang = array(
			__('English', 'bwp-recaptcha') => 'en',
			__('Dutch', 'bwp-recaptcha') => 'nl',
			__('French', 'bwp-recaptcha') => 'fr',
			__('German', 'bwp-recaptcha') => 'de',
			__('Portuguese', 'bwp-recaptcha') => 'pt',
			__('Russian', 'bwp-recaptcha') => 'ru',
			__('Spanish', 'bwp-recaptcha') => 'es',
			__('Turkish', 'bwp-recaptcha') => 'tr'
		);

		$this->caps = apply_filters('bwp_capt_bypass_caps', array(
			__('Read Profile', 'bwp-recaptcha') => 'read',
			__('Manage Options', 'bwp-recaptcha') => 'manage_options'
		));

		if ((strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && !empty($_GET['action']) && 'register' == $_GET['action'])
			|| (strpos($_SERVER['REQUEST_URI'], 'wp-signup.php') !== false)) // WPMS compatible
			$this->is_reg = true;

		if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && empty($_GET['action']))
			$this->is_login = true;

		session_start();

		$this->init();

		// only load style when needed
		add_action('template_redirect', array($this, 'enqueue_media'));
	}

	function load_captcha_library()
	{
		if (!function_exists('recaptcha_get_html')) {
			require_once(dirname(__FILE__) . '/recaptcha/recaptchalib.php');
		}
	}

	function add_hooks()
	{
		if ((!defined('AKISMET_VERSION') || $this->options['enable_akismet'] != 'yes') && isset($_SESSION['bwp_capt_akismet_needed']))
			unset($_SESSION['bwp_capt_akismet_needed']);

		// add support for Contact Form 7 (CF7) automatically
		if (defined('WPCF7_VERSION')) {
			include_once(dirname(__FILE__) . '/class-bwp-recaptcha-cf7.php');
			BWP_RECAPTCHA_CF7::init($this);
		}

		// init public and private keys based on multi-site setting, @since 1.1.0
		$this->init_captcha_keys();

		if (!empty($this->options['input_pubkey']) && !empty($this->options['input_prikey']))
		{
			if ('yes' == $this->options['enable_comment'])
			{
				// if user chooses to integrate with akismet, only show recaptcha when comment is marked as spam
				if (defined('AKISMET_VERSION') && 'yes' == $this->options['enable_akismet'] && (empty($_SESSION['bwp_capt_akismet_needed']) || 'yes' != $_SESSION['bwp_capt_akismet_needed']))
				{
					add_action('akismet_spam_caught', array($this, 'add_recaptcha_after_akismet'));
				}
				else if (!$this->user_can_bypass())
				{
					// this action needs to be added manually into your theme file, i.e. comments.php
					add_action('bwp_recaptcha_add_markups', array($this, 'add_recaptcha'));
					// action for themes using the comment_form() functions added in WordPress 3.0
					add_action('comment_form_after_fields', array($this, 'add_recaptcha'));
					add_action('comment_form_logged_in_after', array($this, 'add_recaptcha'));
					// fill the comment textarea
					add_action('comment_form', array($this, 'fill_comment_content'));
					// check the captcha
					add_action('pre_comment_on_post', array($this, 'check_recaptcha'));
				}
			}

			if ('yes' == $this->options['enable_registration'] && $this->is_reg)
			{
				// normal user registration page
				add_action('register_form', array($this, 'add_recaptcha'));
				add_action('login_head', array($this, 'enqueue_media'));
				add_filter('registration_errors', array($this, 'check_reg_recaptcha'));
				// wpms user/site registration page
				add_action('signup_extra_fields', array($this, 'add_recaptcha'));
				add_action('signup_blogform', array($this, 'add_blog_reg_recaptcha'));
				add_filter('wpmu_validate_user_signup', array($this, 'check_user_reg_recaptcha'));
				add_filter('wpmu_validate_blog_signup', array($this, 'check_blog_reg_recaptcha'));
			}

			// add captcha to login form @since 1.1.0
			if ('yes' == $this->options['enable_login'] && $this->is_login)
			{
				add_action('login_form', array($this, 'add_recaptcha'));
				add_action('login_head', array($this, 'enqueue_media'));
				// the 40 priority is to ensure that we run the filter after 
				// WordPress authenticates the user.
				add_filter('authenticate', array($this, 'authenticate_with_recaptcha'), 40);
			}
		}
	}

	function init_captcha_keys()
	{
		if (function_exists('is_multisite') && is_multisite()) {
			if ('yes' == $this->options['use_global_keys']) {
				$site_options = get_site_option(BWP_CAPT_OPTION_GENERAL);
				$this->options['input_pubkey'] = $site_options['input_pubkey'];
				$this->options['input_prikey'] = $site_options['input_prikey'];
			}
		}
	}

	function enqueue_media()
	{
		if ('yes' == $this->options['enable_css'])
		{
			if (('custom' == $this->options['select_theme'] 
			&& (is_singular() || (!is_admin() && 'yes' != $this->options['enable_selective'])))
			|| $this->is_admin_page())
				wp_enqueue_style('bwp-capt', BWP_CAPT_CSS . '/bwp-recaptcha.css');

			if (('yes' == $this->options['enable_registration'] && $this->is_reg
				&& (!function_exists('is_multisite') || !is_multisite()))
				|| ('yes' == $this->options['enable_login'] && $this->is_login)
			) {
				wp_enqueue_style('bwp-capt', apply_filters('bwp_capt_reg_css', BWP_CAPT_CSS . '/bwp-recaptcha.css', $this->options['select_theme']));
				add_action('login_head', array($this, 'print_styles_for_login'), 1); // make sure this is soon enough
				add_action('login_head', array($this, 'print_inline_styles_for_login'), 11); // make sure this is late enough
			}
		}
	}

	function print_styles_for_login()
	{
		wp_print_styles('bwp-capt');
	}
	
	function print_inline_styles_for_login()
	{
		$login_width = ('clean' == $this->options['select_theme']) ? 482 : 362;
?>
		<style type="text/css">
			#login {
				width: <?php echo $login_width; ?>px;
			}
		</style>
<?php
	}

	/**
	 * Build the Menus
	 */
	function build_menus()
	{
		add_menu_page(__('Better WordPress reCAPTCHA', 'bwp-recaptcha'), 'BWP reCAPT', BWP_CAPT_CAPABILITY, BWP_CAPT_OPTION_GENERAL, array($this, 'build_option_pages'), BWP_CAPT_IMAGES . '/icon_menu.png');
		// Sub menus
		add_submenu_page(BWP_CAPT_OPTION_GENERAL, __('BWP reCAPTCHA General Options', 'bwp-recaptcha'), __('General Options', 'bwp-recaptcha'), BWP_CAPT_CAPABILITY, BWP_CAPT_OPTION_GENERAL, array($this, 'build_option_pages'));
		add_submenu_page(BWP_CAPT_OPTION_GENERAL, __('BWP reCAPTCHA Theme Options', 'bwp-recaptcha'), __('Theme Options', 'bwp-recaptcha'), BWP_CAPT_CAPABILITY, BWP_CAPT_OPTION_THEME, array($this, 'build_option_pages'));
	}

	function modify_option_page()
	{
		global $blog_id;
?>
		<script type="text/javascript">
<?php if (function_exists('is_multisite') && is_multisite() && 1 < $blog_id) { ?>
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
	function build_option_pages()
	{
		if (!current_user_can(BWP_CAPT_CAPABILITY))
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Init the class
		$page = $_GET['page'];
		$bwp_option_page = new BWP_OPTION_PAGE($page, $this->site_options, 'bwp-recaptcha');

		$options = array();

if (!empty($page))
{
	if ($page == BWP_CAPT_OPTION_GENERAL)
	{
		$bwp_option_page->set_current_tab(1);

		// Option Structures - Form
		$form = array(
			'items'			=> array('heading', 'section', 'checkbox', 'input', 'input', 'heading', 'section', 'select', 'input', 'input', 'heading', 'checkbox', 'select'),
			'item_labels'	=> array
			(
				__('What is reCAPTCHA?', 'bwp-recaptcha'),
				__('This plugin will be', 'bwp-recaptcha'),
				__('Use main site\'s keys', 'bwp-recaptcha'),
				__('Public Key', 'bwp-recaptcha'),
				__('Private Key', 'bwp-recaptcha'),
				__('Visibility Options (applied to comment forms)', 'bwp-recaptcha'),
				__('Hide the CAPTCHA for', 'bwp-recaptcha'),
				__('If wrong or empty response', 'bwp-recaptcha'),
				__('Show the error message', 'bwp-recaptcha'),
				__('Show the error message', 'bwp-recaptcha'),
				__('Akismet Integration (applied to comment forms)', 'bwp-recaptcha'),
				__('Integrate with Akismet?', 'bwp-recaptcha'),
				__('If correct CAPTCHA response', 'bwp-recaptcha')
			),
			'item_names'	=> array('h1', 'sec1', 'cb7', 'input_pubkey', 'input_prikey', 'h2', 'sec2', 'select_response', 'input_error', 'input_back', 'h3', 'cb6', 'select_akismet_react'),
			'heading'			=> array(
				'h1'	=> __('reCAPTCHA is a free CAPTCHA service that helps to digitize books, newspapers and old time radio shows. You can read more about reCAPTCHA <a href="http://www.google.com/recaptcha/learnmore" target="_blank">here</a>.', 'bwp-recaptcha'),
				'h2'	=> __('<em>This section allows you to determine when to show reCAPTCHA and how this plugin reacts to errors.</em>', 'bwp-recaptcha'),
				'h3'	=> __('<em>Integrate with Akismet for better end-user experience. reCAPTCHA is optional and <strong>NO</strong> spam comment will be added to the spam/moderation queue, except likely legitimate comments. This makes the task of identifying sincere comments much easier. This integration is currently in beta stage.</em>', 'bwp-recaptcha')
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
				'select_response' => array(
					__('Redirect commenter back to the comment form', 'bwp-recaptcha') => 'redirect',
					__('Show an error page just like WordPress does', 'bwp-recaptcha') => 'back'
				),
				'select_akismet_react' => array(
					__('Approve comment immediately', 'bwp-recaptcha') => '1',
					__('Hold comment in moderation queue', 'bwp-recaptcha') => 'hold',
					__('Put comment in spam queue', 'bwp-recaptcha') => 'spam'
				)
			),
			'checkbox'	=> array(
				'cb1' => array(__('enabled for comment forms.', 'bwp-recaptcha') => 'enable_comment'),
				'cb2' => array(__('enabled for registration form (user/site registration).', 'bwp-recaptcha') => 'enable_registration'),
				'cb8' => array(__('enabled for login form.', 'bwp-recaptcha') => 'enable_login'),
				'cb3' => array(__('registered users <em>(even without any capabilities)</em>.', 'bwp-recaptcha') => 'hide_registered'),
				'cb4' => array(__('users who can', 'bwp-recaptcha') => 'hide_cap'),
				'cb5' => array(__('visitors who have at least', 'bwp-recaptcha') => 'hide_approved'),
				'cb6' => array(__('reCAPTCHA will only show when Akismet identifies a comment as spam. Highly recommended if you do not want to force your visitors to type the captcha every time.', 'bwp-recaptcha') => 'enable_akismet'),
				'cb7' => array(__('uncheck to use different key pairs for this site.', 'bwp-recaptcha') => 'use_global_keys')
			),
			'input'	=> array(
				'input_pubkey' 	=> array('size' => 30, 'label' => __('A public key used to request captchas from reCAPTCHA server.', 'bwp-recaptcha')),
				'input_prikey' 	=> array('size' => 30, 'label' => __('A private (secret) key used to authenticate user\'s response.', 'bwp-recaptcha')),
				'input_error' 	=> array('size' => 90, 'label' => __('when redirect commenter back to the comment form (or when used in other forms such as contact forms).', 'bwp-recaptcha')),
				'input_back' 	=> array('size' => 90, 'label' => __('when show the normal error page with no redirection.', 'bwp-recaptcha')),
				'input_approved' => array('size' => 3, 'label' => __('approved comment(s).', 'bwp-recaptcha')),
			),
			'container' => array(
				'cb2' => __('<em><strong>Note:</strong> For this plugin to work, you will need a pair of API keys (public and private), which is available for free <a href="https://www.google.com/recaptcha/admin/create" target="_blank">here</a>. Once you have created those two keys for this domain, simply paste them below.</em>', 'bwp-recaptcha'),
				'select_akismet_react' => __('<em><strong>Note:</strong> Now you may wonder, why put the comment in the spam queue? The benefit is Akismet will be able to mark the comment as False Positive, and thus will not possibly block that comment in the future. However, it is best to just put the comment in moderation queue as next time Akismet will put such comment in moderation queue immediately without the need of a CAPTCHA.</em>', 'bwp-recaptcha')
			),
			'inline_fields' => array(
				'cb4' => array('select_cap' => 'select'),
				'cb5' => array('input_approved' => 'input')
			)
		);
		
		// Get the default options
		$options = $bwp_option_page->get_options(array('use_global_keys', 'input_pubkey', 'input_prikey', 'input_error', 'input_approved', 'select_cap', 'hide_registered', 'hide_cap', 'hide_approved', 'enable_registration', 'enable_login', 'enable_comment', 'input_back', 'select_response', 'enable_akismet', 'select_akismet_react'), $this->options);

		// Get option from the database
		$options = $bwp_option_page->get_db_options($page, $options);

		$option_formats = array('input_approved' => 'int', 'input_error' => 'html', 'input_back' => 'html');
		$option_super_admin = array();

		// show appropriate fields based on multi-site setting
		add_action('bwp_option_action_before_submit_button', array($this, 'modify_option_page'));
	}
	else if ($page == BWP_CAPT_OPTION_THEME)
	{
		$bwp_option_page->set_current_tab(2);
		
		// Option Structures - Form
		$form = array(
			'items'			=> array('select', 'checkbox', 'checkbox', 'select', 'input', 'heading'),
			'item_labels'	=> array
			(
				__('Choose a theme', 'bwp-recaptcha'),
				__('Use CSS provided by this plugin?', 'bwp-recaptcha'),
				__('Load CSS, JS selectively?', 'bwp-recaptcha'),
				__('Choose a language for built-in themes', 'bwp-recaptcha'),
				__('Tabindex for captcha input field', 'bwp-recaptcha'),
				__('Preview your reCAPTCHA', 'bwp-recaptcha')
			),
			'item_names'	=> array('select_theme', 'cb1', 'cb2', 'select_lang', 'input_tab', 'h1'),
			'heading' => array(
				'h1' => __('<em>Below you will see how your reCAPTCHA will look. Note that this might differ on your actual pages.<br /></em>', 'bwp-recaptcha')
			),
			'select' => array(
				'select_theme' => array(
					__('Default Theme (Red)', 'bwp-recaptcha') => 'red',
					__('White Theme', 'bwp-recaptcha') => 'white',
					__('Black Theme', 'bwp-recaptcha') => 'blackglass',
					__('Clean Theme', 'bwp-recaptcha') => 'clean',
					__('Custom Theme (use CSS)', 'bwp-recaptcha') => 'custom'
				),
				'select_lang' => $this->lang
			),
			'checkbox'	=> array(
				'cb1' => array(__('This stylesheet is used to style the custom theme as well as the registration page. You can disable this or add appropriate filters to use your own.', 'bwp-recaptcha') => 'enable_css'),
				'cb2' => array(__('This is only useful when you do not use any minify or cache plugin.', 'bwp-recaptcha') => 'enable_selective')
			),
			'input'	=> array(
				'input_tab' 	=> array('size' => 3, 'label' => __('Basically, this should be 4 if you place the captcha before the textarea, and 5 if you put it after. Set to 0 to disable.', 'bwp-recaptcha'))
			),
			'container' => array(
				'select_theme' => sprintf(__('<em><strong>Note:</strong> The four built-in captcha themes will look OK in most WordPress themes; However, some times it is better to control how reCAPTCHA looks using CSS. Please read <a href="%s#customization" target="_blank">this guide</a> if you would like to do so.</em>', 'bwp-recaptcha'), BWP_CAPT_PLUGIN_URL),
				'select_lang' => sprintf(__('<em><strong>Note:</strong> Above you can select some built-in languages. If you would like to add your own language, please read <a href="%s#customization" target="_blank">this guide</a>.</em>', 'bwp-recaptcha'), BWP_CAPT_PLUGIN_URL)
			)
		);

		// Get the default options
		$options = $bwp_option_page->get_options(array('select_lang', 'select_theme', 'input_tab', 'enable_css', 'enable_selective'), $this->options);

		// Get option from the database
		$options = $bwp_option_page->get_db_options($page, $options);
		
		$option_formats = array('input_tab' => 'int');
		$option_super_admin = array();
		
		// preview reCAPTCHA
		add_action('bwp_option_action_before_submit_button', array($this, 'add_recaptcha'));
	}
}
		// Get option from user input
		if (isset($_POST['submit_' . $bwp_option_page->get_form_name()]) && isset($options) && is_array($options))
		{
			check_admin_referer($page);

			foreach ($options as $key => &$option)
			{
				if ($this->is_normal_admin() && in_array($key, $option_super_admin))
				{
				}
				else
				{
					if (isset($_POST[$key]))
						$bwp_option_page->format_field($key, $option_formats);
					if (!isset($_POST[$key]))
						$option = '';
					else if (isset($option_formats[$key]) && 0 == $_POST[$key] && 'int' == $option_formats[$key])
						$option = 0;
					else if (isset($option_formats[$key]) && empty($_POST[$key]) && 'int' == $option_formats[$key])
						$option = $this->options_default[$key];
					else if (!empty($_POST[$key])) // should add more validation here though
						$option = trim(stripslashes($_POST[$key]));
					else
						$option = '';
				}
			}

			update_option($page, $options);

			// Update site options if is super admin and is on main site
			global $blog_id;
			if (!$this->is_normal_admin() && $blog_id == 1) {
				update_site_option($page, $options);
			}

			// Update options successfully
			$this->add_notice(__('All options have been saved.', 'bwp-recaptcha'));
		}

		// show notice if one of the api keys is missing
		$this->options = array_merge($this->options, $options);
		if (empty($this->options['input_pubkey']) || empty($this->options['input_prikey']))
			$this->add_notice('<strong>' . __('Warning') . ':</strong> ' . __("API key(s) missing. Please get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a> (free!)", 'bwp-recaptcha'));
		if ('yes' == $this->options['enable_akismet'] && !defined('AKISMET_VERSION'))
			$this->add_notice('<strong>' . __('Notice') . ':</strong> ' . __('You are enabling Akismet integration but Akismet is not currently active. Please activate Akismet for the integration to work.', 'bwp-recaptcha'));

		// Assign the form and option array
		$bwp_option_page->init($form, $options, $this->form_tabs);

		// Build the option page
		$bwp_option_page->generate_html_form();
	}

	/**
	 * Get the link to the current comment page
	 */
	function get_current_comment_page_link()
	{
		global $wp_rewrite;
	
		if ( !is_singular() || !get_option('page_comments') )
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
	 */
	function user_can_bypass()
	{
		global $wpdb;

		if ('yes' == $this->options['hide_registered'] && is_user_logged_in())
			return true;
		if ('yes' == $this->options['hide_cap'] && !empty($this->options['select_cap']) && current_user_can($this->options['select_cap']))
			return true;
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
			// should we use comment_author_url?
			$bwp_query = 
				'SELECT COUNT(comment_ID)
					FROM ' . $wpdb->comments . '
					WHERE comment_approved = 1' . "
						AND comment_author = %s AND comment_author_email = %s AND comment_author_url = %s
						AND comment_type = ''
				";
			$approved_count = $wpdb->get_var($wpdb->prepare($bwp_query, $commenter['comment_author'], $commenter['comment_author_email'], $commenter['comment_author_url']));
			// has more approved comments than required?
			if ($approved_count >= $this->options['input_approved'])
				return true;
		}

		return false;
	}

	function fill_comment_content_callback($matches)
	{
		return '%' . dechex(ord($matches[1]));
	}

	function fill_comment_content()
	{
		// put the comment content back if possible
		if (!empty($_SESSION['bwp_capt_comment']))
		{
			// borrow from the plugin wp-recaptcha, wp-recaptcha.php:582
			$comment = preg_replace_callback('/([\\/\(\)\+\;\'])/', array($this, 'fill_comment_content_callback'), $_SESSION['bwp_capt_comment']);
			$comment = preg_replace('/\\r\\n/m', '\\\n', $comment);
?>
		<script type="text/javascript">
			if (document.getElementById('comment'))
				document.getElementById('comment').value = unescape('<?php echo $comment; ?>');
		</script>
<?php
			unset($_SESSION['bwp_capt_comment']);
		}
	}

	/**
	 * Output the reCAPTCHA HTML, use theme if specified
	 * @param $errors @since 1.1.0
	 */
	function add_recaptcha($errors = false)
	{
		$this->load_captcha_library();

		if (!defined('BWP_CAPT_ADDED'))
		{
			// make sure we add only one recaptcha instance
			define('BWP_CAPT_ADDED', true);

			// captcha error can comes from $_GET variable or passed via 
			// hooks' parameters.
			$captcha_error = '';
			$extra_class = '';
			if (!empty($_GET['cerror']) && 'incorrect-captcha-sol' == $_GET['cerror']) {
				$captcha_error = $_GET['cerror'];
			} else if (is_wp_error($errors)) {
				// right now only registration errors are passed this way
				$captcha_error = $errors->get_error_message('reg-recaptcha-error');
				$extra_class = ' error';
			}

			if (!empty($_SESSION['bwp_capt_akismet_needed']) && 'yes' == $_SESSION['bwp_capt_akismet_needed']) {
?>
		<p class="bwp-capt-spam-identified"><?php _e('Your comment was identified as spam, please complete the CAPTCHA below:', 'bwp-recaptcha'); ?></p>
<?php
			} else if (!empty($captcha_error)) {
?>
		<p class="recaptcha_only_if_incorrect_sol<?php echo $extra_class; ?>"><?php echo $this->options['input_error']; ?></p>
<?php
			}

			do_action('bwp_capt_before_add_captcha');

			if ($this->options['select_theme'] != 'custom')
			{
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
				bwp_capt_custom_theme_widget();
			
			if ('redirect' == $this->options['select_response']  && !is_admin())
			{
?>
			<input type="hidden" name="error_redirect_to" value="<?php esc_attr_e($this->get_current_comment_page_link()); ?>" />
<?php
			}

			$use_ssl = (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) ? true : false;

			if (!empty($this->options['input_pubkey']))
				echo recaptcha_get_html($this->options['input_pubkey'], $captcha_error, $use_ssl, $this->options['select_lang']);
			else if (current_user_can('manage_options'))
				_e("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>", 'bwp-recaptcha');
			else
				echo '';
		}
	}

	function add_blog_reg_recaptcha($errors)
	{
		// if user is not logged in, they can choose between signup for account 
		// or sign up for blog after account creation.
		if (!is_user_logged_in()) {
			// if user chooses to signup for blog also, we don't show recaptcha 
			// again because they have already provided the captcha once.
		} else {
			// user is logged in and wants to add a new blog, show captcha if 
			// needed.
			$this->add_recaptcha($errors);
		}
	}

	/**
	 * The system must authenticate while checking reCAPTCHA.
	 * @filter authenticate, @see package WordPress/pluggable.php
	 * @since 1.1.0
	 */
	function authenticate_with_recaptcha($user)
	{
		if (!isset($_POST['wp-submit'])) {
			return $user;
		}

		// if the $user object itself is a WP_Error object, we simply append 
		// errors to it, otherwise we create a new one.
		$errors = is_wp_error($user) ? $user : new WP_Error();

		if (!isset($_POST['recaptcha_challenge_field']) || !isset($_POST["recaptcha_response_field"])) {

			$errors->add('login-recaptcha-error', $this->options['input_error']);

		} else {

			$this->load_captcha_library();

			if (function_exists('recaptcha_check_answer')) {
				$response = recaptcha_check_answer($this->options['input_prikey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
				if (!$response->is_valid) {
					if ($response->error == 'incorrect-captcha-sol')
						$errors->add('login-recaptcha-error', $this->options['input_error']);
					else
						$errors->add('login-recaptcha-error', __('<strong>ERROR</strong>: Unknown captcha error.', 'bwp-recaptcha'));
				}
			}

		}

		// WordPress actually logs the user in regardless of captcha response
		// (which is kinda dumb), so we have to work around a bit here.
		$errorCodes = $errors->get_error_codes();
		if (0 < sizeof($errorCodes) && !is_wp_error($user)) {
			$user = $errors;
			wp_clear_auth_cookie();
		}

		return $user;
	}

	/**
	 * Check whether the captcha response for registration page is valid or not
	 */
	function check_reg_recaptcha($errors)
	{
		$this->load_captcha_library();

		// if $errors is an array, we're probably checking recaptcha for Multi-Site WP
		if (is_array($errors) && isset($errors['errors']))
		{
			$temp = $errors;
			$errors = $errors['errors'];
		}

		if (!isset($_POST['recaptcha_challenge_field']) || !isset($_POST["recaptcha_response_field"])) {
			$errors->add('reg-recaptcha-error', $this->options['input_error']);
			$stop = true;
		}

		if (empty($stop) && function_exists('recaptcha_check_answer'))
		{
			$response = recaptcha_check_answer($this->options['input_prikey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

			if (!$response->is_valid)
			{
				if ($response->error == 'incorrect-captcha-sol')
					$errors->add('reg-recaptcha-error', $this->options['input_error']);
				else
					$errors->add('reg-recaptcha-error', __('<strong>ERROR</strong>: Unknown captcha error.', 'bwp-recaptcha'));
			}
		}

		if (isset($temp))
		{
			$temp['errors'] = $errors;
			$errors = $temp;
		}

		return $errors;
	}

	function check_user_reg_recaptcha($errors)
	{
		// user is registering a new blog, we don't need to check anything here.
		if (isset($_POST['stage']) && 'validate-blog-signup' == $_POST['stage']) {
			return $errors;
		} else {
			return $this->check_reg_recaptcha($errors);
		}
	}

	function check_blog_reg_recaptcha($errors)
	{
		// (maybe) check for recaptcha error if user is logged in
		if (!is_user_logged_in()) {
			return $errors;
		} else {
			return $this->check_reg_recaptcha($errors);
		}
	}

	function akismet_comment_status()
	{
		$bwp_capt_cs = (!empty($this->options['select_akismet_react'])) ? $this->options['select_akismet_react'] : '0';
		$bwp_capt_cs = ('hold' == $bwp_capt_cs) ? '0' : $bwp_capt_cs;
		return $bwp_capt_cs;
	}

	/**
	 * Check whether the captcha response for comment form is valid or not
	 */
	function check_recaptcha($comment_post_ID)
	{
		$this->load_captcha_library();

		if (function_exists('recaptcha_check_answer'))
		{
			$response = recaptcha_check_answer($this->options['input_prikey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

			if (!$response->is_valid)
			{
				session_regenerate_id();

				// @todo need improvement
				if (!empty($_POST['comment']))
					$_SESSION['bwp_capt_comment'] = trim($_POST['comment']);

				if ('redirect' == $this->options['select_response'])
				{
					// since we haven't add the comment yet, we need to find the link to the current comment page the visitor is on
					$location = (!empty($_POST['error_redirect_to'])) ? $_POST['error_redirect_to'] : get_permalink($comment_post_ID) . '#respond';
					$location = add_query_arg('cerror', $response->error, $location);
					wp_safe_redirect($location);
					exit;
				}
				else if ('back' == $this->options['select_response'])
				{
					if ('incorrect-captcha-sol' == $response->error)
						wp_die(sprintf($this->options['input_back'], '<a href="javascript:history.go(-1);">' . __('this link', 'bwp-recaptcha') . '</a>'));
					else if (current_user_can('manage_options'))
						wp_die(__('There is some problem with your reCAPTCHA API keys, please double check them.', 'bwp-recaptcha'));
					else
						wp_die(__('Unknown error. Please contact the administrator for more info.', 'bwp-recaptcha'));
				}
			}
			else
			{
				if (isset($_SESSION['bwp_capt_akismet_needed']) && $_SESSION['bwp_capt_akismet_needed'] == 'yes')
				{
					// override akismet functions
					add_filter('akismet_spam_count_incr', create_function('', 'return 0;'), 11);
					// workaround for remove_filter function
					add_filter('pre_comment_approved',  array($this, 'akismet_comment_status'), 10);
					add_filter('pre_comment_approved',  array($this, 'akismet_comment_status'), 11);
				}
				if (isset($_SESSION['bwp_capt_comment'])) unset($_SESSION['bwp_capt_comment']);
				if (isset($_SESSION['bwp_capt_akismet_needed'])) unset($_SESSION['bwp_capt_akismet_needed']);
			}
		}
	}

	/**
	 * Attempt to integrate Akismet with this plugin
	 */
	function add_recaptcha_after_akismet()
	{
		$comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;

		session_regenerate_id();
		$_SESSION['bwp_capt_akismet_needed'] = 'yes';

		// need improvement
		if (!empty($_POST['comment']))
			$_SESSION['bwp_capt_comment'] = trim($_POST['comment']);

		$location = (!empty($_POST['error_redirect_to'])) ? $_POST['error_redirect_to'] : get_permalink($comment_post_ID) . '#respond';
		wp_safe_redirect($location);
		exit;
	}
}

