=== Appsero Helper ===
Contributors: tareq1988, wedevs, nizamuddinbabu
Donate link: https://tareq.co/donate/
Tags: licensing, release, analytics, deactivation
Requires at least: 4.0
Tested up to: 6.0
Stable tag: 1.2.2
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your website with Appsero Helper plugin to start managing your licenses, create new account from each, manage affiliates and do more with Appsero

== Description ==

> **Note:** This plugin is only really of use to you if you have an Appsero account. You can set one up for free. [**Join Appsero**](https://appsero.com/?ref=wporg) if you are interested in managing your plugin/theme without any hassle.

Appsero is a top-class WordPress Analytics, Licensing & Deployment Tool for free/premium plugins and themes.

Appsero Helper provides a connection between Appsero service and your site. Which opens effective ways of plugin management from Appsero.

## WHY YOU SHOULD USE APPSERO TO MANAGE YOUR WORDPRESS PRODUCTS' LICENSE

1. Appsero provides you with enhanced analytics like you’ve never seen! 
Get ***plugin and theme deactivation data***, [***comprehensive WordPress analytics***](https://appsero.com/features/analytics/), ***automatic release deployment***, ***product licensing*** & more exciting features.
2. Appsero is ***affordable*** for all WordPress developers. You can choose the platform where you want to sell your plugins ([***Easy Digital Downloads***](https://appsero.com/integrations/easy-digital-downloads/), [***WooCommerce***](https://appsero.com/integrations/woocommerce/), [***FastSpring***](https://appsero.com/integrations/fastspring/), [***Paddle***](https://appsero.com/integrations/paddle/), or [***Gumroad***](https://appsero.com/integrations/gumroad/) while saving time & money.
3. Appsero comes with a hassle-free [***software license management system***](https://appsero.com/features/licensing/). Generate and check licenses, like - WooCommerce license, EDD License, and more with Appsero. Or you can keep using your existing license management system. 
4. Want to ***push a new release?*** You can ***push your code to Git*** (GitHub, Bitbucket, or Gitlab) - and ***appsero will [automatically deploy](https://appsero.com/features/deployment/)*** it to WordPress and other channels.
5. Get accurate data about the ***increase/decrease of installations & deactivations*** from Appsero’s Dashboard.


## INTEGRATIONS

Here are the tools and services you can seamlessly [***integrate with Appsero***](https://appsero.com/integrations/) - 

1. WooCommerce
2. Easy Digital Downloads
3. Themeforest
4. CodeCanyon
5. Fastspring
6. Paddle
7. Gumroad
8. Mailchimp
9. Mailchimp eCommerce

## SHORTCODES

To help you easily access important information related to your plugins/themes, you can create shortcodes on any page.

Here are the shortcodes -

- [appsero_my_account] - This shortcode outputs a full featured My Account page where users can find their licenses, downloads and more
- [appsero_licenses] - this shortcode shows the licenses of products a user
- [appsero_orders] this shortcode shows the orders placed by a user 
- [appsero_downloads] this shortcode shows the product download links of a user 


##### For Example: Configuring My Account page on Appsero Dashboard
Copy the link to the Appsero/EDD/WooCommerce My Account page and update it on your Appsero Dashboard. Go to your Product > Email > Email Branding and paste my account page link there.

= HOOKS =

Please visit https://github.com/Appsero/appsero-helper to check the hook’s documentation.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `appsero-helper` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the 'Settings > Appsero Helper' menu item in your admin sidebar

On Appsero Helper settings page you have to connect your store with AppSero using API key.

== Frequently Asked Questions ==

= What is Appsero? =

Appsero is a SaaS solution that provides Analytics, Software Licensing, Automatic Updates
for WordPress Plugins and Themes.

= Is it free? =

It has both free and premium services which you can avail by [registering](https://appsero.com) an account.


== Screenshots ==


== Changelog ==

= 1.2.2 - (24th October, 2022) =
- **New:** Full compatibility with Easy Digital Downloads V3
- **Improvement:** Bug fixes & code improvements

= 1.2.1 - (18th March, 2022) =
- **Fix:** WooCommerce order `updated_at` column error.

= v1.2.0 - (21st January, 2022) =
- **New:** Sync orders with Woo & EDD automatically.
- **Fix:** Login form rendering issue in some sites.
- **Fix:** PHP Notice in affiliate.
- **Improvement:** Hide download button in thank you page if file is not available.

= v1.1.17 - (3rd October, 2021) =
- **New:** Added purchased variation name in download list.
- **New:** Added download button in license list.
- **Improvement:** Added "local" badge in activated sites list.

= v1.1.16 - (2nd September, 2021) =
- **New:** Added Paddle Affiliate feature using AffiliateWP.

= v1.1.15 - (30th July, 2021) =
- **Fix:** Update plugin version and tested up to.

= v1.1.14 - (30th July, 2021) =
- **New:** Copy license key by clicking on it.
- **Fix:** Updated Appsero customer creation process.
- **Fix:** Hide license & download box in thank you page for pending orders.

= v1.1.13 - (25th Jun, 2021) =
- **New:** Added hooks to extend Appsero my account pages.
- **New:** Download link and license key with Woocommerce and EDD successful purchase email.

= v1.1.12 – (19th Nov, 2020) =
- **Fix:** [License API] WooCommerce Software Add-On activations site column change to `instance`

= v1.1.11 – (18th Nov, 2020) =
- **Fix:** [License API] WooCommerce Software Add-On get variation id from order variation
- **Fix:** [Product API] Variation recurring and period value

= v1.1.10 – (7th Sep, 2020) =
- **New:** Appsero My Account page
- **New:** AffiliateWP integration

= v1.1.9 – (19th Aug, 2020) =
- **Fix:** WooCommerce Software Add-On renewal license expire date issue

= v1.1.8 – (4th Jun, 2020) =
- **New:** Add option to view order in Appsero from WooCommerce order details
- **Fix:** Lifetime license on thank you page

= v1.1.7 – (5th Apr, 2020) =
- **Improvement:** Update settings page UI
- **New:** Choose selling plugin when WooCommerce and Easy Digital Downloads both installed
- **Fix:** Easy Digital Downloads customer creation issue

= v1.1.6 – (1st Apr, 2020) =
- **New:** Add filter hook to WooCommerce order and license API data
- **New:** Add filter hook to Easy Digital Downloads order and license API data

= v1.1.5 – (25th Feb, 2020) =
- **New:** Show licnese and download on WooCommerce thank you page
- **New:** Show licnese and download on Easy Digital Downloads thank you page

= v1.1.4 – (19th Feb, 2020) =
- **Improvement:** WooCommerce My Account page downloads move to appsero downloads
- **Improvement:** Enable activations list during order sync

= v1.1.3 – (19th Nov, 2019) =
- **Fix:** Variation receiving issue if WC Software active

= v1.1.2 – (18th Nov, 2019) =
- **Fix:** Variations not showing for variable subscription product

= v1.1.1 – (26th Sep, 2019) =
- **New:** Show orders using shortcode
- **New:** Show licneses using shortcode
- **New:** Show downloads using shortcode

= v1.0.2 - (29th Aug, 2019) =

 * **Fix:** Error on save Appsero API key

= v1.0.1 - (4th Feb, 2019) =

 * **Fix:** API key connection issue fixed.
 * **Fix:** WooCommerce namespace, it was EDD before mistakenly.

= v1.0 - (30 Jan, 2019)

 * Initial release

== Upgrade Notice ==

Nothing here right now.
