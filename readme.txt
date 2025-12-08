=== Tweaker ===
Contributors: nabatech
Tags: woocommerce, checkout, security, login, customization
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A modular WordPress plugin that gives you granular control over your WooCommerce checkout and WordPress security settings.

== Description ==

Look, we get it. You're tired of installing ten different plugins just to customize your checkout form or hide your login page. That's exactly why we built Tweaker.

Tweaker is a clean, modular plugin system that puts powerful customization tools right at your fingertips. Each module is built with one goal in mind: make complex tasks stupidly simple.

**What makes Tweaker different?**

* **Modular Architecture** - Only activate what you need. No bloat, no clutter.
* **Professional Grade** - Built with modern PHP 8.1+ and best practices in mind.
* **Developer Friendly** - Clean, maintainable code that's actually a joy to work with.
* **Zero Guesswork** - Intuitive interfaces that just make sense.

== Current Modules ==

**Checkout Fields Manager** (WooCommerce Required)

Remember the last time you wanted to change "Billing Address" to something friendlier? Or maybe you needed to make the phone field optional? With most plugins, you're stuck wrestling with cryptic settings or editing theme files.

Not anymore.

This module gives you complete control over every single field on your WooCommerce checkout page:

* Change labels, placeholders, and field order with a few clicks
* Toggle required/optional status instantly
* Enable/disable entire sections (billing, shipping, order notes) independently
* Customize validation messages so they actually match your brand voice
* Hide the shipping section completely if you don't need it
* Individual field controls - disable specific fields while keeping their section active

The interface? Think of it as your checkout's control center. Everything is organized, visual, and exactly where you'd expect it to be.

**WP Secret Login**

Here's a fun fact: bots hammer wp-login.php on every WordPress site, all day, every day. It's annoying, it wastes server resources, and it's a security risk.

This module kills that problem dead.

Instead of exposing your login page at the usual wp-login.php, you create your own secret URL. Something like yoursite.com/private-entrance or yoursite.com/staff-only - whatever you want.

Features:

* Custom login URL - choose any slug you like
* Smart redirects - anyone trying to access wp-login.php gets sent elsewhere
* Flexible redirect options - send unauthorized visitors to a 404 error or any page you choose
* Automatic URL rewriting - WordPress will use your secret URL everywhere (password reset emails, admin redirects, etc.)
* No more brute force attempts on the default login page

It's simple: if someone doesn't know your secret URL, they're not even seeing your login form.

== Installation ==

**Automatic Installation**

1. Log into your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Tweaker"
4. Click "Install Now" and then "Activate"

**Manual Installation**

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

**After Activation**

Head over to **Tweaker** in your admin menu. You'll see your module dashboard where you can:

* Activate/deactivate individual modules
* Access each module's settings
* View module status at a glance

Each module has its own dedicated settings page - no more hunting through endless tabs.

== Frequently Asked Questions ==

= Will this work with my theme? =

Yes. Tweaker is theme-agnostic and works with any properly coded WordPress theme.

= Does Checkout Fields Manager work with WooCommerce? =

The Checkout Fields module requires WooCommerce to be installed and active. If WooCommerce isn't detected, you'll see a notice and the module won't load.

= What happens if I forget my secret login URL? =

You can access it via FTP or your hosting file manager. Go to wp-content/plugins/tweaker/modules/WPSecretLogin and temporarily rename the module folder. This will deactivate the module and restore access to wp-login.php.

= Can I use this on a multisite installation? =

Currently, Tweaker is designed for single-site WordPress installations. Multisite support is being considered for future versions.

= Is this plugin actively maintained? =

Absolutely. We're constantly working on improvements and new modules.

== Screenshots ==

1. Tweaker dashboard - Module overview and quick access
2. Checkout Fields Manager - Complete field customization interface
3. WP Secret Login - Simple, effective login URL protection

== Changelog ==

= 1.0.0 =
* Initial release
* Checkout Fields Manager module - Full WooCommerce checkout customization
* WP Secret Login module - Custom login URL and security protection
* Modular architecture with independent module activation
* Clean, modern admin interface
* Professional codebase with WordPress coding standards
* PHP 8.1+ and WordPress 6.9+ compatibility

== Upgrade Notice ==

= 1.0.0 =
Welcome to Tweaker! This is the initial release. Install, activate your modules, and start customizing.

== What's Coming Next ==

We're not going to bore you with a typical roadmap. Instead, here's what we're cooking up:

Remember how annoying it is to manage email templates? Or when you just want to add a simple custom field without learning ACF? What about those times when you wish you could tweak WooCommerce's order emails without touching code?

Yeah, we're working on that.

But here's the thing - we're not just building features for the sake of features. Every module we add has to pass one test: Would I actually use this on my own projects?

If the answer isn't a resounding "hell yes," it doesn't make the cut.

Stay tuned. We've got some genuinely useful stuff in the pipeline.

== Support ==

Need help? Found a bug? Got a feature request?

Visit [nabatech.com](https://nabatech.com) to get in touch with our team.

== Privacy ==

This plugin does not collect, store, or transmit any personal data. All settings are stored locally in your WordPress database.

== Credits ==

Built with ☕ by the team at Naba Tech Ltd.
