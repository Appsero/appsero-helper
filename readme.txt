=== Appsero Helper ===
Contributors: tareq1988, wedevs, nizamuddinbabu
Donate link: https://tareq.co/donate/
Tags: licensing, release, analytics, deactivation
Requires at least: 4.0
Tested up to: 6.3
Stable tag: 1.3.2
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your website with Appsero Helper plugin to start managing your licenses, create a new account from each, manage affiliates, and do more with Appsero.

== Description ==

Appsero is a world-class **WordPress Analytics, Licensing & Deployment Tool** for free/premium plugins and themes. It’s one of the best **plugin management software** for WordPress plugin owners and developers.

Appsero is **affordable** for all WordPress developers. You can choose the platform where you want to sell your plugins (Easy Digital Downloads, WooCommerce, FastSpring, Paddle, or Gumroad) while saving time & money.

If Appsero is a SaaS product, then why does it need a helper plugin?
Because **Appsero Helper acts as a bridge between your website and Appsero service.** Which opens effective ways of plugin management from Appsero.

> **Note:** The following plugin is only useful to those who have an Appsero account, which can be created for free. [**Join Appsero**](https://appsero.com/?ref=wporg) if you want to manage your WordPress plugin and theme without hassle.


## WHY DO YOU NEED APPSERO?

By cloning the [**Appsero SDK**](https://github.com/appsero/client/) into your project, and adding a few lines of code to your primary function; you can unlock every feature of Appsero! You get amazing features like - license management, continuous deployment, email integrations, deactivation data, and many more! Making Appsero the perfect plugin management software you can get.

#### FEATURES

1. Appseros' most notable feature is its **hassle-free [License Management](https://appsero.com/features/licensing/).** Appsero generates licenses as soon as your customer completes their purchase, making the whole licensing process easier than ever! You can manage and verify licenses, such as - *WooCommerce license, EDD license,* and more with Appsero.
2. Appsero comes with **advanced [WordPress Analytics](https://appsero.com/features/analytics/)**; providing you with product insights like you’ve never seen! With this WordPress analytics, you can get product data like - *customer behavior data, sales & revenue data, product usage reporting, WordPress version used by users, server software analytics,* and many more!
3. Managing releases for premium products is hard. To ease your hardship, Appsero can do an automatic update that works hand in hand with the licensing engine. You can now **push your code to Git** *(GitHub, Bitbucket, or Gitlab)* - and **Appsero will [automatically deploy](https://appsero.com/features/deployment/)** it to WordPress.org and other channels.
4. With Appsero, now you can get an **in-depth [Deactivation Data Analysis](https://appsero.com/features/deactivations/)** to know what your customer wants! From Appsero’s dashboard, you can find out *deactivation stats, deactivation reasons,* and many more.
5. Have multiple WordPress products and want to sell them together? Lucky for you, Appsero has now the feature of **Bundle Product Selling.** If you have *more than one product in Appsero,* then you can combine both plugins and themes for a bundle using this Appsero feature; bringing in higher revenue in the process.
6. Appsero can even help you with team collaboration! Supercharge your team with **superior permission management: [Teams](https://appsero.com/features/teams/).** With this, make sure *your team is in sync,* just with the right amount of your WordPress product information.

## INTEGRATIONS

Appsero works seamlessly with your favorite web services to skyrocket your business growth. Find the tools you already use or discover new ways to step things up.

Here are all the tools & services Appsero has [**seamless integration**](https://appsero.com/integrations/) with -

- **Selling Platform**
	- WooCommerce
	- Easy Digital Downloads (EDD)
	- Envato
	- Paddle
	- Gumroad
	- FastSpring

- **Git Integration**
	- Github
	- GitLab
	- Bitbucket
- **Marketing & Emails Integration**
	- [weMail](https://getwemail.io/)
	- Mailchimp
	- Mailjet

- **Others**
	- WordPress.org integration for pushing updates to products directly from GitHub
	- Mailchimp eCommerce for advanced marketing
	- Help Scout to provide email-based customer support



#### SHORTCODES

You can create shortcodes on any page to easily access essential information related to your plugins/themes.

Here are the shortcodes -

- [appsero_my_account] - This shortcode outputs a full featured My Account page where users can find their licenses, downloads and more
- [appsero_licenses] - this shortcode shows the licenses of products a user
- [appsero_orders] this shortcode shows the orders placed by a user
- [appsero_downloads] this shortcode shows the product download links of a user


##### For Example: Configuring My Account page on Appsero Dashboard
Copy the link to the Appsero/EDD/WooCommerce My Account page and update it on your Appsero Dashboard. Go to your Product > Email > Email Branding and paste my account page link there.

= HOOKS =

Please visit <https://github.com/Appsero/appsero-helper> to check the hook’s documentation.

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

== Frequently Asked Questions ==

= What is Appsero? =

Appsero is a SaaS solution that provides Analytics, Software Licensing, Automatic Updates
for WordPress Plugins and Themes.

= Is it free? =

It has both free and premium services which you can avail by [registering](https://appsero.com) an account.


== Screenshots ==


== Changelog ==

= 1.3.2 - (26th September, 2024) =
- **Fix:** Moved user profile update hook to main file for frontend access.
- **Note:** Previously, the update hook only triggered when editing in the admin panel, not when customers updated profiles from the My Account page.

= 1.3.1 - (25th September, 2024) =
- **Fix:** Updated changelog for version 1.3.1 to correct version name and details.

= 1.3.0 - (25th September, 2024) =
- **New:** Restricted release access for expired licenses.
- **New:** Notify Appsero on customer updates.
- **Improvement: Updated style and display for license status in a separate row.

= 1.2.4 - (16th August, 2023) =
- **New:** Full compatibility with WordPress 6.3
- **Improvement:** Fixed license link for WooCommerce email
- **Improvement:** Fixed release version

= 1.2.3 - (16th August, 2023) =
- **New:** Full compatibility with WordPress 6.3
- **Improvement:** Fixed license link for WooCommerce email

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
