=== Plugin Name ===
Contributors: Mike_Cowobo
Donate link: http://trenvo.com/
Tags: walkthrough, welcome panel, help, getting started, multisite, beginners, easy, simple, admin
Requires at least: 3.4.2
Tested up to: 3.5beta2
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Replace WordPress' Welcome Panel with a simple but effective walkthrough

== Description ==

WP Getting Started replaces the WordPress Welcome Panel (the one you see on the Dashboard after a fresh WP installation) with a simple 4 step walkthrough: choose and/or customize your theme, add pages and add posts.

At every page in the walkthrough, users get a short explanation of what they are doing - for example what the difference between a page and a post is. When they have completed a step, they are automatically taken back to the walkthrough on the Dashboard.

WP Getting Started is fully compatible with multisite networks, to give new users an easier introduction to the WordPress admin interface, and is no more intrusive than the original Welcome Panel in WordPress (further explanations only show up when the user follows the links from WP Getting Started).

This plugin is fully compatible with [Live Theme Preview](http://wordpress.org/extend/plugins/live-theme-preview/), a drop-in replacement for WordPress' native Themes interface.

This plugin only works with WordPress version 3.4 or later.

*This plugin is only freshly released, so use with care. Please leave any comments, bugs or suggestion in the Support section of the plugin page!*

*If you want to help develop this plugin, visit the [GitHub repo](https://github.com/mgmartel/WP-Getting-Started).*

= Features =
* Multisite compatible
* Easily extendible through hooks and actions
* Internationalized
* Non intrusive - one click to dismiss
* Big Icons, Single Workflow

== Installation ==

1. Go to your WordPress Dashboard at yoursite.com/wp-admin.
1. Then go to Plugins > Add New and search for "WP Getting Started"
1. This plugin should show up in the results, click "Install Now" under the name
1. Click on "Activate" in the next screen

This plugin needs no configuration - just go to your Dashboard and follow the instructions!

== Frequently Asked Questions ==

= Can I add my own texts to this plugin? =

Yes. There is no configuration panel (yet?), but the easiest way to do it would be to make a translation file (which can be in English still, of course) using PoEdit or CodeStyling Localization.

Then load the textdomain (in a plugin or your `functions.php`):

`load_textdomain( 'wp-getting-started', ABSOLUTE_PATH_TO_TRANSLATION_FILE );`

= Why doesn't this plugin also... =

This plugin is still in its early stages, look at the version number. If you have any suggestions, please let me know via email or the support forum of this plugin.

Or better yet, contribute to the [GitHub repo](https://github.com/mgmartel/WP-Getting-Started)!

= I think the texts you have put in are a bit strange, why isn't it ... =

See the above..

== Screenshots ==

1. Welcome to WordPress
2. A bit further down the road. Description under the big icons changes according the the steps already completed.
3. WP Getting Started contains a few pointers about the admin screens in WordPress

== Changelog ==

= 0.1 =
* First version.

== Other Notes ==

The icons used in WP Getting Started come from the ["Google Plus Interface Icons"](http://www.designshock.com/google-plus-interface-icons/) icon pack by Design Shock, free for personal or commercial use. Original license file is included with the plugin.
