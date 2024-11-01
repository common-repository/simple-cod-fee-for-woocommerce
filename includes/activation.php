<?php 

function scffw_activate_plugin() {

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
}