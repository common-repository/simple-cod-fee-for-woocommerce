=== Simple COD Fees for WooCommerce ===
Contributors: asofantzis
Tags: woocommerce, admin, cod, fee
Requires at least: 4.0
Tested up to: 6.4.3
Requires PHP: 7.0
Stable tag: 2.1
License: GPLv2 or later

This plugin will add a custom fee for Cash On Delivery payment method in WooCommerce. It is a very simple plugin with only two options. Fee amount and Fee title (what the user will see as explanation for the extra charge).

== Description ==
Most of the times real Cash On Delivery payment methods charge an amount of money. WooCommerce by default doesn't have the option to add this extra fee to the user. With this plugin you can easily do just that.

== Screenshots ==
1. Only two simple options, fee and label
2. How the options are shown in frontend
3. Different cod fee for each shipping method
4. Different cod fee for each currency per shipping method

== Frequently Asked Questions ==
= Where can I find the settings? =
Now the settings can be found under the menu Woocommerce > COD Settings. In WooCommerce COD payment gateway settings you will also find a link to this page.
= Can I enable Cash on Delivery for specific shipping options? =
Of course, this is a native WooCommerce function. Under WooCommerce settings you can add the shipping options under the option "Enable for shipping methods"
= How can I add different cod fees per shipping method?
Simply click the "Different fees per shipping method?" option and all the shipping methods that are enabled for COD will be displayed allowing you to add specific prices for each one.
= Does this plugin work with WPML and multiple currencies?
Yes, if you use WPML and have multicurrency support enabled, you can now have multiple COD fee per currency.
= Can I adjust COD Fee in main currency and then use the exchange rates to automatically adjust other currencies?
Yes, from version 2.0 onwards, you can find a new option in the settings menu called "Use exchange rates?". Enable this option will let you only enter the price for the cod fee in the main currency, and all other currencies ara automatically calculated during checkout.
= Does this plugin offers an option to Disable COD or COD Fee depending on user's cart subtotal?
Yes, you can configure this plugin to completely disable COD or just the COD Fee depending on user's total amount in cart
= I am a developer, can I manipulate the final fee before it show up in the cart?
Yes you can use filter **`scffw_cod_fee`** which take as parameters the fee and the cart, so you can manipulate the fee cost as you wish

== Changelog ==
= 2.1 =
* Security fix (vulnerable to XSS)
* Fixed error when you couldn't use cart subtotal percentage to multiple shipping methods
* Added feature, when using WPML different options for COD Fee title per active languages
* Added feature, when trying to repeat a failed attempt it will apply COD fee

= 2.0.2 = 
* Fixed errors when settings from version 1.7.x never migrated to 2.0 and fatal errors occured
* Fixed error when cod needed to have virtual orders enabled for the options to show up

= 2.0 = 
* Completely refactored code
* Added specific page for all the Cod Fee settings, moved away from native WooCommerce COD payment method settings. The new settings page can be found under menu Woocommerce / COD Settings
* Added custom filter scffw_cod_fee for easy manipulation of the extra cod fee
* Added WPML exchange rates compatibility

= 1.7.1 =
* Fix error when sometimes cod fee was never added

= 1.7 =
* **FIXED** when sometimes if WPML was enabled, some payment methods disappeared from admin menu
* Added option to enable tax status for the extra cod fee
* Added option to completely remove COD or COD Fee if cart subtotal is less/more than a specific amount
* Added option to disable COD Fee in cart page

= 1.6 =
* Added WPML Multicurrency support
* If there is WPML Multicurrency enabled then different fields will show up to declare different fee per currency (currently not support exchange rates, only fixed fees)

= 1.5 =
* Fixed problem when user has selected COD fee for specific shipping methods and extra fee didn't shown up properly
* Validate dot as decimal separator
* Automatically deactivate plugin when WooCommerce is deactivated

= 1.4 =
* Changed the way for checking if COD is installed that caused some payment methods to throw an error

= 1.3 =
* Added option for different COD fees per shipping methods
* Added validation when entering fee to allow only two decimal numbers
* Several fixes

= 1.2 =
* Tested up to version 6.0.1 bump
* Fix check if WooCommerce is installed causing plugin to throw errors

= 1.1 =
* Tested up to version bump