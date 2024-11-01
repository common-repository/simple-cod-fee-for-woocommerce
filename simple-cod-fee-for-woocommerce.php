<?php
/*
Plugin Name: Simple COD Fee for WooCommerce
Description: Simply add extra fee for WooCommerce Cash On Delivery payment method
Version: 2.1
Author: Andreas Sofantzis
Author URI: https://83pixel.com
Text Domain: scffw
Domain Path: /languages
License: GPL v2 or later
*/

require_once('includes/admin-menu.php');
require_once('includes/order-repeat.php');

define('SCFFW_COD_SETTINGS', 'scffw_cod_settings');
define('SCFFW_VERSION', '2.1');
define('SCFFW_VERSION_OPTIONS', 'scffw_version');

register_activation_hook( __FILE__, function() {
    require_once('includes/activation.php');
    scffw_activate_plugin();
});

add_action('admin_enqueue_scripts', function() {
        
    wp_enqueue_script( 
        'scffw-admin', 
        plugin_dir_url( __FILE__ ) . '/scffw-admin.min.js', 
        array('jquery'), 
        filemtime(plugin_dir_path( __FILE__ ) . 'scffw-admin.min.js'), 
        true
    );

    wp_localize_script( 'scffw-admin', 'main_vars', array(
        'search' => __('Search', 'scffw'),
        'simple_cod_settings_btn_text' => __('Go to Simple Cash On Delivery Settings', 'scffw'),
        'simple_cod_settings_btn_url' => admin_url('admin.php?page=scffw-cod-settings')
    ));

}, 100);

add_action('wp_enqueue_scripts', function() {
    
    wp_enqueue_script( 
        'scffw', 
        plugin_dir_url( __FILE__ ) . 'scffw.min.js', 
        array('jquery'), 
        filemtime(plugin_dir_path( __FILE__ ) . 'scffw.min.js'), 
        true
    );

}, 100);

// deactivate plugin if user deactivates woocommerce
// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_init', 'deactivate_if_woocommerce_deactivated');
    function deactivate_if_woocommerce_deactivated() {
        // deactivate plugin
      deactivate_plugins(plugin_basename(__FILE__));

      // show user a message
      add_action('admin_notices', function () {
        echo '<div class="error"><p><strong>Simple COD Fee for WooCommerce</strong> has been deactivated because WooCommerce is not active.</p></div>';
      });
    }
}

// custom languages
add_action( 'init', function() {
    load_plugin_textdomain( 'scffw', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
} );

// plugin inline settings buttons
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links )
{
    $links['settings'] = '<a href="' . admin_url('admin.php?page=scffw-cod-settings') . '">' . __('Settings', 'woocommerce') . '</a>';
    $links['donate'] = '<a href="https://www.paypal.com/donate/?hosted_button_id=6D23GEU4YKT38" target="_blank">☕ ' . __('Buy me a coffee', 'scffw') . '</a>';

    return $links;
} );

// Apply custom COD fees
add_action( 'woocommerce_cart_calculate_fees', function($cart)
{
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    $chosen_payment_method = WC()->session->get( 'chosen_payment_method' );
    
    if ( $chosen_payment_method != 'cod' ) 
        return;
    
    
    $cod_options = get_option(SCFFW_COD_SETTINGS);

    if ( !is_array($cod_options) || !array_key_exists('enabled', $cod_options) || $cod_options['enabled'] == false ) {
        return;
    }
    
    $show_on_cart = array_key_exists('show_on_cart', $cod_options) && $cod_options['show_on_cart'] == true ? true : false;
    
    if ( is_cart() && !$show_on_cart ) {
        return;
    }

    if ( is_cart() ) {
        if ( array_key_exists('cod_conditionals', $cod_options ) && $cod_options['cod_conditionals'] ) {

            $cart = WC()->cart;
            $cart_subtotal = $cart->get_subtotal();
    
            // check if the cod is enabled by checking if settings for min/max are set
            $cod_is_disabled = true;
    
            //if user enter no value then we set min to 0 (because there are no negative prices)
            $min = $cod_options['cod_disable_min'] != '' ? $cod_options['cod_disable_min'] : 0;
    
            // if user enters no value then we set max to an unlikely high price
            $max = $cod_options['cod_disable_max'] != '' ? $cod_options['cod_disable_max'] : 99999999;
    
            if ( $cart_subtotal > $max || $cart_subtotal < $min ) {
                $cod_is_disabled = false;
            }
    
            if ( !$cod_is_disabled ) {
                return 0;
            }
        }
    }
    
    if ( scffw_get_active_wpml_languages() ) {
        $language_code = apply_filters( 'wpml_current_language', null );
        $fee_title = $cod_options['fee_title_languages']['fee_title_' . $language_code];
    }
    else {
        $fee_title = $cod_options['fee_title'] ?: __('COD Fee', 'scffw');
    }

    // get chosen shipping method type eg. flat_rate, local_pickup etc
    $chosen_shipping_method_type = wc_get_chosen_shipping_method_ids();

    $chosen_shipping_method = WC()->session->get( 'chosen_shipping_methods' );
    
    // get COD payment method settings
    $payment_gateway = WC()->payment_gateways->payment_gateways()['cod'];
    $enabled_cod_shipping_methods = $payment_gateway->settings['enable_for_methods'];
    
    $fee = scffw_get_shipping_method_cod_fee($chosen_shipping_method);

    // added filter so the user can change the fee 
    $real_fee = apply_filters('scffw_cod_fee', $fee, $cart);

    if ( !is_null($real_fee) ) {
        if ( array_key_exists('enable_tax', $cod_options) && $cod_options['enable_tax'] ) {
            $tax_rates = scffw_get_all_standard_tax_rates();
            $tax_rate_class = $tax_rates[$cod_options['tax_rate']]->tax_rate_class;
            $cart->add_fee( $fee_title, $real_fee, true, $tax_rate_class );
        }
        else {
            $cart->add_fee( $fee_title, $real_fee, false );
        }
    }

}, 10 );


function scffw_get_shipping_method_cod_fee($chosen_shipping_method) {

    $cod_options = get_option(SCFFW_COD_SETTINGS);
    $cart = WC()->cart;
    if ( array_key_exists('cod_fee_conditionals', $cod_options) && $cod_options['cod_fee_conditionals'] ) {
        
        $cart_subtotal = $cart->get_subtotal();
        
        //if user enter no value then we set min to 0 (because there are no negative prices)
        $min = $cod_options['cod_fee_disable_min'] != '' ? $cod_options['cod_fee_disable_min'] : 0;

        // if user enters no value then we set max to an unlikely high price
        $max = $cod_options['cod_fee_disable_max'] != '' ? $cod_options['cod_fee_disable_max'] : 99999999;

        if ( $cart_subtotal < $min || $cart_subtotal > $max ) {
            return 0;
        }
    }

    $fee = 0;
    // convert : to _
    $chosen_shipping_method_underscores = is_array($chosen_shipping_method) ? str_replace(":", "_", $chosen_shipping_method[0]) : '';
    
    if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) { 
        $current_currency = get_woocommerce_currency();

        if ( $cod_options['exchange_rates_enabled'] ) {
            $exchange_rates = scffw_get_exchange_rates();
            $main_wcml_currency = wcml_get_woocommerce_currency_option();
            if ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options) && !$cod_options['different_fees_per_shipping_method_enabled'] ) {
                // $fee = $default_fee;
                $fee = $cod_options['cod_fee_' . mb_strtolower($main_wcml_currency)] * $exchange_rates[$current_currency];
            }
            elseif ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options) && $cod_options['different_fees_per_shipping_method_enabled'] ) {
                $fee = array_key_exists($chosen_shipping_method_underscores . '_' . mb_strtolower($current_currency), $cod_options['shipping_methods_fees']) ? $cod_options['shipping_methods_fees'][$chosen_shipping_method_underscores . '_' . mb_strtolower($current_currency)] : 0;
            }
        }
        else {
            if ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options) && !$cod_options['different_fees_per_shipping_method_enabled'] ) {
                $fee = isset($cod_options['cod_fee_' . mb_strtolower($current_currency)]) ? $cod_options['cod_fee_' . mb_strtolower($current_currency)] : 0;
            }
            elseif ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options) && $cod_options['different_fees_per_shipping_method_enabled'] ) {
                if ( !array_key_exists($chosen_shipping_method_underscores . '_' . mb_strtolower($current_currency), $cod_options['shipping_methods_fees']) ) {
                    $fee = 0;
                }
                else {
                    $fee = $cod_options['shipping_methods_fees'][$chosen_shipping_method_underscores . '_' . mb_strtolower($current_currency)] ?: 0;
                }
            }
        }
        
    }
    else {
        $fee = $cod_options['cod_fee'];
        if ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options)  ) {
            if ( $cod_options['different_fees_per_shipping_method_enabled'] ) {
                if ( scffw_shipping_method_is_enabled_for_cod_fee($chosen_shipping_method[0]) ) {
                    if ( isset($cod_options['shipping_methods_fees'][$chosen_shipping_method_underscores]) && $cod_options['shipping_methods_fees'][$chosen_shipping_method_underscores] != '' ) {
                        $fee = $cod_options['shipping_methods_fees'][$chosen_shipping_method_underscores];
                    }
                    else {
                        $fee = $cod_options['cod_fee'];
                    }
                }
            }
        }
        
    }

    if ( array_key_exists('cart_percent_enabled', $cod_options) && $cod_options['cart_percent_enabled'] ) {
        $cart_subtotal = $cart->get_subtotal();
        $percent = 0;
        if ( array_key_exists('different_fees_per_shipping_method_enabled', $cod_options) && $cod_options['different_fees_per_shipping_method_enabled'] ) {
            if ( array_key_exists($chosen_shipping_method_underscores, $cod_options['shipping_methods_cart_percents']) && $cod_options['shipping_methods_cart_percents'][$chosen_shipping_method_underscores] != '' ) {
                $percent = $cod_options['shipping_methods_cart_percents'][$chosen_shipping_method_underscores];
            }
        }
        else {
            $percent = $cod_options['shipping_methods_cart_percents']['percent_cart_universal'];
        }

        if ( $percent != 0 ) {
            $fee = $fee + ( $cart_subtotal * $percent / 100);
        }
        
    }

    return $fee;
}

function scffw_wpml_get_all_currencies() {
    $wcml_options = get_option( '_wcml_settings' );
    
    $enabled_currencies = $wcml_options['currency_options'];
    
    $currencies = [];

    foreach ( $enabled_currencies as $key => $currency ) {
        $currencies[] = $key;
    }
    return $currencies;
}

add_action( 'admin_enqueue_scripts', function()
{
	// Styles
	wp_enqueue_style(
		'scffw',  
		plugin_dir_url( __FILE__ ) . 'scffw.css', 
		array(), 
		filemtime(plugin_dir_path( __FILE__ ) . 'scffw.css')
	);
});

function scffw_get_all_standard_tax_rates() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'woocommerce_tax_rates';
    $tax_rates_from_db = $wpdb->get_results(
        "
            SELECT *
            FROM $table_name
        "
    );

    $tax_rates = [];
    foreach ( $tax_rates_from_db as $tax_rate ) {
        $tax_rates[$tax_rate->tax_rate_id] = $tax_rate;
    }

    return $tax_rates;
}

function scffw_get_exchange_rates() {
    $wcml_multi_currency = new WCML_Multi_Currency();
    return $wcml_multi_currency->get_exchange_rates();
}

function scffw_get_all_currencies() {
    $wcml_multi_currency = new WCML_Multi_Currency();
    return $wcml_multi_currency->get_currencies();
}

add_filter('woocommerce_available_payment_gateways', function($available_gateways) {
    if ( ( is_checkout() || is_cart() ) ) {
        $cod_options = get_option(SCFFW_COD_SETTINGS);
        
        if ( array_key_exists('cod_conditionals', $cod_options ) && $cod_options['cod_conditionals'] ) {

            $cart = WC()->cart;
            $cart_subtotal = $cart->get_subtotal();

            // check if the cod is enabled by checking if settings for min/max are set
            $cod_is_disabled = true;

            //if user enter no value then we set min to 0 (because there are no negative prices)
            $min = $cod_options['cod_disable_min'] != '' ? $cod_options['cod_disable_min'] : 0;

            // if user enters no value then we set max to an unlikely high price
            $max = $cod_options['cod_disable_max'] != '' ? $cod_options['cod_disable_max'] : 99999999;

            if ( $cart_subtotal > $max || $cart_subtotal < $min ) {
                $cod_is_disabled = false;
            }

            if ( !$cod_is_disabled ) {
                unset($available_gateways['cod']);
            }
        }
    }

    return $available_gateways;
});

function scffw_shipping_method_is_enabled_for_cod_fee($shipping_method) {

    $woocommerce_cod_settings = get_option('woocommerce_cod_settings');
    $cod_enabled_for_methods = $woocommerce_cod_settings['enable_for_methods'];

    // cod is enabled for all methods
    if ( $cod_enabled_for_methods == '' ) {
        return true;
    }

    if ( is_object($shipping_method ) ) {
        // cod is enabled for shipping method eg. flat_rate
        if ( in_array($shipping_method->id, $cod_enabled_for_methods) ) {
            return true;
        }

        // cod is enabled for specific shipping method eg. flat_rate:1
        if ( in_array($shipping_method->id . ':' . $shipping_method->instance_id, $cod_enabled_for_methods) ) {
            return true;
        }
    }
    else {
        foreach ( $cod_enabled_for_methods as $enabled_method ) {
            if ( strpos($enabled_method, ':') !== false ) {
                if ( $enabled_method == $shipping_method ) {
                    return true;
                }
            }
            else {
                $shipping_method_array = explode(':', $shipping_method);
                if ( $shipping_method_array[0] == $enabled_method ) {
                    return true;
                }
            }
        }
    }

    return false;
}

function scffw_get_currency_symbol($currency_code) {
    $currency_symbol = null;

    // Get the currency information
    $currencies = apply_filters('wcml_currencies', array());
    foreach ($currencies as $currency) {
        if ($currency['code'] === $currency_code) {
            $currency_symbol = $currency['symbol'];
            break;
        }
    }

    return $currency_symbol;
}

// notification before updating to version 2.x from versions 1.x
add_action('in_plugin_update_message-simple-cod-fees-for-woocommerce/simple-cod-fee-for-woocommerce.php', function($data, $response) {
    $current_version = $data['Version'];
    $new_version = $data['new_version'];

    if ( $dcurrent_version < $new_version ) {
        echo '<br/><strong>' . __('If you are updating from version 1.xx to 2.xx please visit the new settings area, after plugin update, and save your new settings', 'scffw') . '</strong>';
    }
    
}, 10, 2);

// notice if user hasn't updated to version2
add_action('admin_notices', function() {
    $cod_options = get_option(SCFFW_COD_SETTINGS) ?: []; ?>
    
    <?php if ( !array_key_exists('updated_to_v_2_settings_saved', $cod_options ) || !$cod_options['updated_to_v_2_settings_saved'] ) : ?>
    <div class="notice notice-warning is-dismissible scffw-update-notice">
        <p>
            <?php
                printf(
                    /* translators: %s: Name of a city */
                    __( 'Congratulations, you updated <strong>Simple COD Fee for WooCommerce</strong> to version 2.0! The plugin will try to copy previous settiings but to ensure that everything is working visit the <a href=%1$s>new settings here</a>', 'scffw' ),
                    esc_url(admin_url('admin.php?page=scffw-cod-settings'))
                );
            ?>
        </p>
    </div>
    <?php endif; ?>
    <?php
});

add_action('admin_init', function() {
    $current_version_installed = get_option(SCFFW_VERSION_OPTIONS);

    if ( !$current_version_installed ) {

        $settings = get_option(SCFFW_COD_SETTINGS);
    
        // if no current settings exists it means that it is a new installation on ver 2.0 and above
        if ( !$settings ) {
            $old_settings = get_option('woocommerce_cod_settings');

            // initialize a blank $settings array
            $settings = [];
            $settings['exchange_rates_enabled'] = 0;
            // check if old settings exist in native woocommerce cod options in woocommerce_cod_settings cell in wp_options
            if ( $old_settings && is_array($old_settings) ) {
            
                if ( array_key_exists('scffw_enable_on_cart', $old_settings) ) {
                    if ( $old_settings['scffw_enable_on_cart'] == 'yes' ) {
                        $settings['show_on_cart'] = true;
                    }
                    else {
                        $settings['show_on_cart'] = false;
                    }
                }
                
                if ( array_key_exists('fee_title', $old_settings) ) {
                    $settings['fee_title'] = $old_settings['fee_title'];
                }
        
                if ( array_key_exists('scffw_disable_cod_conditions', $old_settings) ) {
                    if ( $old_settings['scffw_disable_cod_conditions'] == 'yes' ) {
                        $settings['cod_conditionals'] = true;
                    }
                    else {
                        $settings['cod_conditionals'] = false;
                    }
                }
        
                if ( array_key_exists('scffw_disable_cod_if_less', $old_settings) ) {
                    $settings['cod_disable_min'] = $old_settings['scffw_disable_cod_if_less'];
                }
        
                if ( array_key_exists('scffw_disable_cod_if_greater', $old_settings) ) {
                    $settings['cod_disable_max'] = $old_settings['scffw_disable_cod_if_greater'];
                }
        
                if ( array_key_exists('scffw_disable_cod_fee_conditions', $old_settings) ) {
                    if ( $old_settings['scffw_disable_cod_fee_conditions'] == 'yes' ) {
                        $settings['cod_fee_conditionals'] = true;
                    }
                    else {
                        $settings['cod_fee_conditionals'] = false;
                    }
                }
        
                if ( array_key_exists('scffw_disable_cod_fee_if_less', $old_settings) ) {
                    $settings['cod_fee_disable_min'] = $old_settings['scffw_disable_cod_fee_if_less'];
                }
        
                if ( array_key_exists('scffw_disable_cod_fee_if_greater', $old_settings) ) {
                    $settings['cod_fee_disable_max'] = $old_settings['scffw_disable_cod_fee_if_greater'];
                }
        
                if ( array_key_exists('fee', $old_settings) ) {
                    $settings['cod_fee'] = $old_settings['fee'];
                }
        
                if ( array_key_exists('scffw_cod_fee_tax_status', $old_settings) ) {
                    if ( $old_settings['scffw_cod_fee_tax_status'] == 'taxable' ) {
                        $settings['enable_tax'] = true;
                    }
                    else {
                        $settings['enable_tax'] = false;
                    }
                }
                
                $shipping_zones         = WC_Shipping_Zones::get_zones();
                $shipping_option_names = [];

                foreach ( $shipping_zones as $zone ) {
                    foreach ( $zone['shipping_methods'] as $shipping_method ) {
                        if ( scffw_shipping_method_is_enabled_for_cod_fee($shipping_method) ) {
                            $shipping_id = $shipping_method->id . '_' . $shipping_method->instance_id;
                            
                            $maybe_options = get_option('woocommerce_' . $shipping_id . '_settings');
                            
                            if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
                                foreach ( scffw_wpml_get_all_currencies() as $key => $currency ) {
                                    if ( array_key_exists('scffw_cod_fee_' . $currency, $maybe_options ) ) {
                                        $settings['shipping_methods_fees'][$shipping_id . '_' . mb_strtolower($currency)] = $maybe_options['scffw_cod_fee_' . $currency];
                                        $settings['different_fees_per_shipping_method_enabled'] = true;
                                    }
                                }
                            }
                            else {

                            }
                        }
                    }
                }

                unset($old_settings['scffw_enable_on_cart']);
                unset($old_settings['scffw_disable_cod_conditions']);
                unset($old_settings['scffw_disable_cod_if_greater']);
                unset($old_settings['scffw_disable_cod_if_less']);
                unset($old_settings['scffw_disable_cod_fee_conditions']);
                unset($old_settings['scffw_disable_cod_fee_if_greater']);
                unset($old_settings['scffw_disable_cod_fee_if_less']);
                unset($old_settings['fee']);
                unset($old_settings['scffw_cod_fee_tax_status']);
                unset($old_settings['scffw_cod_fee_tax_rates']);
                unset($old_settings['fee_title']);
                
                update_option('woocommerce_cod_settings', $old_settings, true);
                // if there is at least one setting in the $settings array enable the new settings
                $settings['enabled'] = true;
                update_option(SCFFW_COD_SETTINGS, $settings, false);
            }
        }

        update_option(SCFFW_VERSION_OPTIONS, SCFFW_VERSION, false);
    }
});

function scffw_get_active_wpml_languages() {
    // Check if WPML is active by checking for a WPML specific function
    if (function_exists('icl_object_id')) {
        // Retrieve active languages using WPML function
        $languages = icl_get_languages('skip_missing=0&orderby=code');
        if (!empty($languages)) {
            // If languages are found, return the array of languages
            return $languages;
        } else {
            // If no languages are found, return an empty array (could indicate a misconfiguration)
            return array();
        }
    } else {
        // If WPML is not active, return false
        return false;
    }
}