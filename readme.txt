=== SMTP Settings for Gravity Forms ===
Contributors: kevp75
Donate link: https://paypal.me/kevinpirnie
Tags: gravity forms, smtp, gravity forms smtp
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.12.89
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Individual GravityForm SMTP email sending settings.

== Description ==

This plugin adds a new settings page for each form created, to allow configuration for sending emails through an SMTP mail server.  It gives you a way to have different SMTP server settings for each form you create.

If you do not need it, you can simply leave the settings blank, and it will revert to the site's default mail sender.

== Installation == 

1. Download the plugin, unzip it, and upload to your sites `/wp-content/plugins/` directory
    1. You can also upload it directly to your Plugins admin
2. Activate the plugin through the 'Plugins' menu in WordPress

== Configure ==

1. Browse to the Gravity Form Admin, and click on Settings for the form.
2. Click SMTP Settings
3. Fill in the details to override the built-in Wordpress Mail sender for the form
    .1 SMTP password will be be encrypted prior to sending to the Wordpress Database.  Please note that is uses the Wordpress Built-In salts from wp-config.  If these change in anyway, you wll need to enter the SMTP password again.

== Frequently Asked Questions == 

= Do I need this plugin if I already use a SMTP plugin? =

Not necessarily.  This plugin is not meant to replace your sitewide SMTP mail sender, but rather gives you a way to utilize separate SMTP settings for each Gravity Form in your site.

= Outlook 365 SMTP Settings are not working... HELP?! =

Outlook 365 requires the From email address to be the exact same as the account used to authenticate.  Make sure you update your settings.

= Does this override the entire site's mail sending? =

No.  Instead it overrides it for the form you have the settings configured for only. 

== Screenshots == 

1. Settings 1
2. Settings 2

== Changelog ==

= 0.12.89 =
* Verify: WP Core 6.7 compliant
* Fix: CC not being added to the outbound emails
* Change: Settings defaults
    * SMTP Port: 587
    * SMTP User: the site's configured admin email address
    * From Email: the site's configured admin email address
    * those will populate if the settings are left blank now

= 0.12.11 =
* Verify: WP Core 6.5 compliant
* Bumped: Minimum Core Requirement

= 0.11.70 =
* Verify: WP Core 6.2 compliant

= 0.11.66 =
* Test: Up to 6.1.2 compliant
* Require: PHP 7.4

= 0.10.52 = 
* Test: Up to 6.0 compliant
* Test: Up to PHP 8.1 Compliant
* New: Plugin Icon =)

= 0.10.37 =
* Fix: SMTP debugging fix
    * changed the debug level, put in some exception trapping, etc...
* Fix: PHP Fatal error when SMTP info is invalid

= 0.10.21 =
* Verify: Core 5.9 ready
* New: Translation ready

= 0.9.77 =
* Fix: Die gracefully on GravityForms check
* Fix: Remove duplicated checks

= 0.9.46 = 
* fixed issue with no from address
    * was an issue with the way Form Notifications are setup
      as defaults in GravityForms itself
* added replyto field in settings
* added logging mechanism
    * debug will not longer show on front-end
      stricly logs to `wp-content/gf-smtp-log.log`
* fixed null setting
* fixed sanitization
    * probably overkill...

= 0.9.19 = 
* Verify Core 5.8.1

= 0.9.18 =
* more stringent checks for valid email addresses
* strongly type methods
* convert comments to phpdoc format
* fix proper OR for DIE for direct access

= 0.9.03 =
* Icon for settings menus
* fixed smtp password not saving
    * completely rewrote the encryption/decryption methods
    * found out what was happenning was the algorithms were not installed on every machine
    * also checking if methodology is available now, if not simply base64 encode/decode
    * uses the SECURE_AUTH_* salts from wp-config.php

= 0.8.76 =
* Update for 5.8 compliance
* Require PHP 7.3

= 0.8.13 =
* Update for 5.7.2 compliance
* Update for GF 2.5 compliance
* Method strong typing

= 0.7.22 =
* Update for 5.7 compliance
* check for existing functionality

= 0.5.0 =
* Fix deactivate if GF is not activated

= 0.4.2 =
* True autoloader
* rename class files to match
* update encrypt/decrypt methods to use WP salt
    * will need to re-save settings
* implement proper testing messages
 
= 0.3.1 =
* First public release
