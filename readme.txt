=== Better WordPress reCAPTCHA (support Akismet) ===
Contributors: OddOneOut
Donate link: http://betterwp.net/wordpress-plugins/bwp-recaptcha/
Tags: anti-spam, recaptcha, akismet, akismet integration
Requires at least: 2.8
Tested up to: 3.7
Stable tag: 1.0.2

This plugin utilizes reCAPTCHA (with support for Akismet) to help your blog stay clear of spams.

== Description ==

In the 21th century, spamming could be one of the most annoying problems for a website. Especially if you use some publishing platforms like WordPress, your blog WILL be spammed and no default methods will save you from such nightmare. This plugin utilizes the popular anti-spam library, reCAPTCHA, to help your blog stay clear of spams (especially when you integrate it with Akismet). Unlike the current WP-reCAPTCHA plugin, this one has a different approach and allows you to customize how the captcha looks using CSS.

**Some Features**

* Hide reCAPTCHA for qualified visitors
* Choose how the plugin reacts when an answer is wrong or empty
* Choose between 4 default themes or create your own (sample CSS and images provided)
* Choose a language you prefer, possibility to add more language if needed
* Option to load media files (CSS, JS, etc.) only when needed
* Possibility to integrate with Akismet, allowing better protection against spam and better end-users experience, i.e. "only force a CAPTCHA when a comment looks like spam".
* WordPress Multi-site compatible (not tested with WPMU)
* And more...

**Languages**

* English (default)
* Spanish - Espanol (es_ES) - Thanks to [Ivan Leomuro]!
* Hungarian (hu_HU). Thanks to [Attila Porvay](http://helloftranslations.net)
* French (fr_FR). Thansk to [Christophe GUILLOUX](http://christophe.guilloux.info)

Please [help translate](http://betterwp.net/wordpress-tips/create-pot-file-using-poedit/) this plugin!

The installation of this plugin will require additional work if you don't use `comment_form()` by default. Please read this plugin's [usage note](http://betterwp.net/wordpress-plugins/bwp-recaptcha/#usage) for more information.

== Installation ==

1. Upload the `bwp-recaptcha` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the Plugins menu in WordPress. After activation, you should see a menu of this plugin on your left. If you can not locate it, click on Settings under the plugin's name.
3. If you don't use `comment_form()` to print the comment form, please read [this usage note](http://betterwp.net/wordpress-plugins/bwp-recaptcha/#usage). Also, if you would like to have reCAPTCHA appearing below the textarea where your visitors type their comments, simply replace `comment_form()` in `comments.php` with this:
<pre><code>
if (function_exists('bwp_capt_comment_form')) {bwp_capt_comment_form();}
</code></pre>
4. Configure the plugin, and add your API keys. You must have API keys for the current domain for this plugin to work.
5. Say goodbye to spam!

[View instructions with images](http://betterwp.net/wordpress-plugins/bwp-recaptcha/installation/).

== Frequently Asked Questions ==

[Check plugin news and ask questions](http://betterwp.net/topic/bwp-recaptcha/).

== Screenshots ==

1. reCAPTCHA with custom theme

== Changelog ==

= 1.0.2 =
* Marked as WordPress 3.7 compatible.
* Added a Hungarian translation. Thanks to Attila Porvay!
* Added a French translation. Thansk to Christophe GUILLOUX!
* Updated BWP Framework to fix a possible bug that caues BWP setting pages to go blank.
* Removed the `frameborder` attribute within the noscript tag of the PHP reCAPTCHA library for W3C compliance, thanks to Jools!
* **Good news**: ManageWP.com has become the official sponsor for BWP reCAPTCHA - [Read more](http://betterwp.net/319-better-wordpress-plugins-updates-2013/).

= 1.0.1 =
* Added a template function that allows you to display reCAPTCHA below the textarea in a comment form. Check the installation tab out if you would like to know how to use the new template function. Thanks to Jo�o Bruni!
* Made the public key and private key site-wide options. If you use BWP reCAPTCHA on a multi-site installation, you will only need to input this once.
* Added Spanish - Espanol translation, thanks to Ivan Leomuro!
* Marked this plugin as compatible with WordPress 3.2.x.
* Other minor bugfixes and improvements.

= 1.0.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
* Enjoy the plugin!