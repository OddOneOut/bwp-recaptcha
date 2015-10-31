# Better WordPress reCAPTCHA

This WordPress plugin supports no CAPTCHA reCAPTCHA, Contact Form 7 and
Akismet.

See http://betterwp.net/wordpress-plugins/bwp-recaptcha/ for documentation.

Report issues: https://github.com/OddOneOut/bwp-recaptcha/issues

## Installation via Composer

This plugin can be installed via composer, by running:

```
composer require kdm/bwp-recaptcha
```

By default, composer will install the plugin to `wp-content/plugins`.

## Development

Apart from `composer`, you will need `npm` and `bower` for development.

To run test, issue the following command:

```
npm test
```

To build the plugin in a development environment, issue:

```
npm run build
```

To build a production-ready plugin, issue:

```
npm run build:dist
```

A `dist` folder will be created with a version that can be used as a regular
plugin on wp.org.

Note that certain files and directories are not needed in production
environment, such as configuration files, tests, node modules, and bower
components, so they should be removed before updating the plugin's svn
repository.

A `.svnignore` file is provided for this reason, and it can be used with
existing tools out there (such as `rsync`) to remove the above mentioned files.
