<?php
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        __('COD Settings', 'scffw'),
        __('COD Settings', 'scffw'),
        'manage_woocommerce',
        'scffw-cod-settings',
        'scffw_cod_settings_menu_render'
    );
}, 90);

function scffw_cod_settings_menu_render() {
    echo '<div class="wrap">';
    echo '<h1>Simple Cash on Delivery Fees</h1>';
    
    $gateways = WC()->payment_gateways()->payment_gateways();

    if ( !array_key_exists('cod', $gateways) || $gateways['cod']->settings['enabled'] == 'no' ) {
        echo '<div class="notice error"><p>' . __('Cash On Delivery is not enabled. Please enable it from WooCommerce settings and come back for advanced settings.') . ' <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=cod') . '">Click here to enable Cash On Delivery</a></p></div>';
        return;
    }

    $settings               = get_option(SCFFW_COD_SETTINGS) ?: [];
    $shipping_zones         = WC_Shipping_Zones::get_zones();

    $all_zones_with_cod = [];
    foreach ( $shipping_zones as $zone ) {
        foreach ( $zone['shipping_methods'] as $shipping_method ) {
            if ( scffw_shipping_method_is_enabled_for_cod_fee($shipping_method) ) {
                $all_zones_with_cod[$zone['zone_id']][] = $shipping_method;
            }
        }
    }
    
    $enabled                = array_key_exists('enabled', $settings) ? $settings['enabled'] : false;
    $fee_title              = array_key_exists('fee_title', $settings) ? $settings['fee_title'] : '';
    $show_on_cart           = array_key_exists('show_on_cart', $settings) ? $settings['show_on_cart'] : '';
    $cod_conditionals       = array_key_exists('cod_conditionals', $settings) ? $settings['cod_conditionals'] : false;
    $cod_disable_min        = array_key_exists('cod_disable_min', $settings) ? $settings['cod_disable_min'] : '';
    $cod_disable_max        = array_key_exists('cod_disable_max', $settings) ? $settings['cod_disable_max'] : '';
    $cart_percent_enabled   = array_key_exists('cart_percent_enabled', $settings) ? $settings['cart_percent_enabled'] : false;
    $cod_fee_conditionals   = array_key_exists('cod_fee_conditionals', $settings) ? $settings['cod_fee_conditionals'] : false;
    $cod_fee_disable_min    = array_key_exists('cod_fee_disable_min', $settings) ? $settings['cod_fee_disable_min'] : '';
    $cod_fee_disable_max    = array_key_exists('cod_fee_disable_min', $settings) ? $settings['cod_fee_disable_max'] : '';
    $cod_fee                = array_key_exists('cod_fee', $settings) ? $settings['cod_fee'] : '';
    $fee_taxable            = array_key_exists('enable_tax', $settings) ? $settings['enable_tax'] : '';
    $tax_rates              = scffw_get_all_standard_tax_rates();
    $different_fees_enabled = array_key_exists('different_fees_per_shipping_method_enabled', $settings) ? $settings['different_fees_per_shipping_method_enabled'] : false;
    $exchange_rates_enabled = array_key_exists('exchange_rates_enabled', $settings) ? $settings['exchange_rates_enabled'] : '';
    $shipping_methods_cart_percents = array_key_exists('shipping_methods_cart_percents', $settings) ? $settings['shipping_methods_cart_percents'] : [];

    $percent_cart = array_key_exists('percent_cart', $settings) ? $settings['percent_cart'] : [];

    if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
        $exchange_rates = scffw_get_exchange_rates();
    }

    // if ( scffw_get_active_wpml_languages() ) {
    //     $fee_title_languages = [];
    //     foreach ( scffw_get_active_wpml_languages() as $language ) {
    //         $fee_title_languages[$language['code']] = 
    //     }
    // }
    
    ?>
    <div class="scffw-settings-wrapper">
        
        <div class="enable-cod-fee big-switch">
            <label>
                <input class="toggle-checkbox scffw-user-option" <?php checked( $enabled, true ); ?> type="checkbox" id="cod-fee-enabled" name="enable">
                <div class="toggle-slot">
                    <div class="toggle-button"></div>
                </div>
                <div class="label"><?= __('Enabled COD Fee?', 'scffw'); ?></div>
            </label>
        </div>
        <div class="scffw-settings" style="<?php if ( !$enabled ) { echo 'display:none;'; } ?>">
            <table class="table scffw-settings-table">
                <tbody>

                    <!-- Show COD Fee on cart -->
                    <tr>
                        <th>
                            <div class="th">
                            <?= __('Show COD fee on cart page?', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" class="scffw-user-option" id="show-on-cart" <?php checked( $show_on_cart, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Enable this option if you want to display COD Fee in cart page, disable to hide it', 'scffw'); ?></div>
                                </label>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Disable COD -->
                    <tr>
                        <th>
                            <div class="th">
                            <?= __('Completely disable <em>COD</em> depending on cart total?', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" id="cod-conditionals" class="scffw-user-option" <?php checked( $cod_conditionals, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Completely remove COD as payment method if cart total is less/more than a specific amount', 'scffw'); ?></div>
                                </label>

                                <div class="conditional-box subsection disable-cod-settings" style="<?php if ( !$cod_conditionals ) { echo 'display:none'; } ?>">
                                    <div class="two-columns">
                                        <div class="disable-cod-min-wrapper column">
                                            <label for="disable-cod-min" class="is-bold"><?= __('Less than', 'scffw'); ?></label>
                                            <input type="text" id="disable-cod-min" value="<?= $cod_disable_min; ?>" />
                                        </div>
                                        <div class="or"><?= __('or', 'scffw'); ?></div>
                                        <div class="disable-cod-max-wrapper column">
                                            <label for="disable-cod-max" class="is-bold"><?= __('Greater than', 'scffw'); ?></label>
                                            <input type="text" id="disable-cod-max" value="<?= $cod_disable_max; ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <div class="th">
                            <?= __('Disable <em>COD Fee</em> depending on cart total?', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" id="cod-fee-conditionals" class="scffw-user-option" <?php checked( $cod_fee_conditionals, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Disable COD Fee if cart total is less/more than a specific amount', 'scffw'); ?></div>
                                </label>

                                <div class="conditional-box subsection disable-cod-fee-settings" style="<?php if ( !$cod_fee_conditionals ) { echo 'display:none'; } ?>">
                                    <div class="two-columns">
                                        <div class="disable-cod-min-wrapper column">
                                            <label for="disable-cod-fee-min" class="is-bold"><?= __('Less than', 'scffw'); ?></label>
                                            <input type="text" id="disable-cod-fee-min" value="<?= $cod_fee_disable_min; ?>" />
                                        </div>
                                        <div class="or"><?= __('or', 'scffw'); ?></div>
                                        <div class="disable-cod-max-wrapper column">
                                            <label for="disable-cod-fee-max" class="is-bold"><?= __('Greater than', 'scffw'); ?></label>
                                            <input type="text" id="disable-cod-fee-max" value="<?= $cod_fee_disable_max; ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <div class="th">
                                <?= __('Add percentage of cart\'s subtotal as COD Fee', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" id="cart-percent-enabled" class="scffw-user-option" <?php checked( $cart_percent_enabled, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Add percentage of user\'s cart subtotal to the extra COD fee. You can use it by it\'s own or in addition with a fixed fee', 'scffw'); ?></div>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <div class="th">
                            <?= __('Different fees per shipping method?', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" class="scffw-user-option" id="different-cod-per-shipping" <?php checked( $different_fees_enabled, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Enable this option if you want different COD fees for each shipping methods', 'scffw'); ?></div>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <div class="th different-fees" style="<?php if ( !$different_fees_enabled ) { echo 'display:none;';} ?>">
                                <?= __('Fees per shipping method', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td different-fees" style="<?php if ( !$different_fees_enabled ) { echo 'display:none;';} ?>">
                            <?php foreach ( $all_zones_with_cod as $zone_id => $shipping_methods ) : ?>
                                <?php $zone = new WC_Shipping_Zone( $zone_id ); ?>
                                <fieldset class="shipping-zones subsection">
                                    <legend><?= $zone->get_zone_name(); ?></legend>
                                    <div class="shipping-methods">
                                        <?php foreach ( $shipping_methods as $shipping_method ) : ?>
                                            <div class="shipping-method">
                                                <div class="extra-fee-cost-cell-label is-bold"><?= $shipping_method->get_title(); ?></div>

                                                <div class="shipping-method-fields">
                                                <?php if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) : ?>
                                                    <div class="currencies-flex">
                                                    <?php foreach ( scffw_wpml_get_all_currencies() as $key => $currency ) : ?>
                                                        <?php $shipping_method_extra_fee = $settings['shipping_methods_fees'][$shipping_method->id . '_' . $shipping_method->get_instance_id() . '_' .mb_strtolower($currency) ] ?? null; ?>
                                                        <div class="extra-fee-cost-cell">
                                                            <div class="floating-label currency">
                                                                <input 
                                                                    type="text" 
                                                                    class="zone-shipping-method-fee scffw-user-option scffw-input scffw-validate-price-format <?php if ( $key == 0 ) { echo 'main-currency-fee'; } ?> " 
                                                                    name="<?= $shipping_method->id . '_' . $shipping_method->get_instance_id() . '_' . mb_strtolower($currency); ?>" 
                                                                    value="<?= $shipping_method_extra_fee; ?>" <?php if ( $key > 0 && $exchange_rates_enabled ) { echo 'readonly'; } ?> 
                                                                    data-exchange-rate="<?= $exchange_rates[$currency]; ?>" 
                                                                    placeholder="<?= $currency; ?> (<?= get_woocommerce_currency_symbol($currency); ?>)" 
                                                                />
                                                                <div class="label"><?= $currency; ?> (<?= get_woocommerce_currency_symbol($currency); ?>)</div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    </div>
                                                <?php else : ?>
                                                    <?php $shipping_method_extra_fee = $settings['shipping_methods_fees'][$shipping_method->id . '_' . $shipping_method->get_instance_id()] ?? null; ?>
                                                    <div class="extra-fee-cost-cell">
                                                        <input 
                                                            type="text" 
                                                            id="<?= $shipping_method->id . '-' . $shipping_method->get_instance_id(); ?>" 
                                                            class="zone-shipping-method-fee scffw-user-option scffw-input scffw-validate-price-format" 
                                                            name="<?= $shipping_method->id . '_' . $shipping_method->get_instance_id(); ?>" 
                                                            placeholder="<?= get_woocommerce_currency(); ?> (<?= get_woocommerce_currency_symbol(); ?>)" 
                                                            value="<?= $shipping_method_extra_fee; ?>" 
                                                        />
                                                    </div>
                                                <?php endif; ?>

                                                <?php $shipping_method_cart_percent = array_key_exists($shipping_method->id . '_' . $shipping_method->get_instance_id(), $shipping_methods_cart_percents) ? $shipping_methods_cart_percents[$shipping_method->id . '_' . $shipping_method->get_instance_id()] : null; ?>
                                                    <div class="shipping-zone-percentage cart-percentage" style="<?php if ( !$cart_percent_enabled ) { echo 'display:none'; }; ?>">
                                                        <div class="floating-label">
                                                            <input 
                                                                type="text" 
                                                                class="scffw-user-option scffw-percentage-input scffw-input w-100" 
                                                                name="<?= $shipping_method->id . '_' . $shipping_method->get_instance_id(); ?>" 
                                                                placeholder="<?= __('PERCENT', 'scffw'); ?> (%)" 
                                                                value="<?= $shipping_method_cart_percent; ?>" 
                                                            />
                                                            <div class="label"><?= __('PERCENT', 'scffw'); ?> (%)</div>
                                                        </div>
                                                        <div class="help-text"><?= __('If you want the fee to have an additional percentage of the users\' cart, enter the percentage here without the symbol %', 'scffw'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>

                    <?php if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) : ?>
                    <tr>
                        <th>
                            <div class="th">
                                <?= __('Use exchange rates?', 'scffw'); ?> 
                                <div class="compatibility">(<?= __('You are seeing this options because you are using WPML Multicurrency'); ?>)</div>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" class="scffw-user-option" id="use-exchange-rates" <?php checked( $exchange_rates_enabled, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('By enabling this option you only set the extra fee cost for the main currency then all the others are calculated automatically using your saved exchange rates', 'scffw'); ?></div>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <th>
                            <div class="th universal-fee" style="<?php if ( $different_fees_enabled ) { echo 'display:none;';} ?>">
                                <?= __('Fee Cost', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td universal-fee" style="<?php if ( $different_fees_enabled ) { echo 'display:none;';} ?>">
                            <div class="currencies-flex">
                            <?php if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) : ?>
                                
                                    <?php foreach ( scffw_wpml_get_all_currencies() as $key => $currency ) : ?>
                                        <?php $shipping_method_extra_fee = $settings['shipping_methods_fees'][$shipping_method->id . '_' . $shipping_method->get_instance_id() . '_' .mb_strtolower($currency) ] ?? null; ?>
                                        <div class="extra-fee-cost-cell">
                                            <div class="floating-label">
                                            <?php $cod_fee = array_key_exists('cod_fee_' . mb_strtolower($currency), $settings) ? $settings['cod_fee_' . mb_strtolower($currency)] : ''; ?>
                                                <input 
                                                    type="text" 
                                                    class="scffw-user-option cod-fee-input scffw-input scffw-validate-price-format <?php if ( $key == 0 ) { echo 'main-currency-fee'; } ?>" 
                                                    name="cod_fee_<?= mb_strtolower($currency); ?>" 
                                                    value="<?= $cod_fee; ?>" <?php if ( $key > 0 && $exchange_rates_enabled ) { echo 'readonly'; } ?> 
                                                    data-exchange-rate="<?= $exchange_rates[$currency]; ?>" 
                                                    placeholder="<?= $currency; ?> (<?= get_woocommerce_currency_symbol($currency); ?>)" 
                                                />
                                                <div class="label"><?= $currency; ?> (<?= get_woocommerce_currency_symbol($currency); ?>)</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                
                                    <?php else : ?>
                                        <?php $cod_fee = array_key_exists('cod_fee', $settings) ? $settings['cod_fee'] : ''; ?>
                                        <input type="text" id="cod-fee" class="scffw-user-option scffw-input cod-fee-input scffw-validate-price-format" name="cod_fee" value="<?= $cod_fee; ?>" placeholder="<?= get_woocommerce_currency(); ?> (<?= get_woocommerce_currency_symbol(); ?>)" />
                                        <div class="help-text"><?= __('The extra amount the user must pay if he uses COD as payment method', 'scffw'); ?></div>
                                    <?php endif; ?>
                                    <?php $universal_cart_percent = array_key_exists('percent_cart_universal', $shipping_methods_cart_percents) && $shipping_methods_cart_percents['percent_cart_universal'] ? $shipping_methods_cart_percents['percent_cart_universal'] : null; ?>
                                    <div class="cod-fee-percentage cart-percentage" style="<?php if ( !$cart_percent_enabled ) { echo 'display:none'; }; ?>">
                                        <div class="floating-label">
                                            <input 
                                                type="text" 
                                                class="scffw-user-option scffw-percentage-input scffw-input w-100" 
                                                name="percent_cart_universal" 
                                                value="<?= $universal_cart_percent; ?>" 
                                                placeholder="<?= __('PERCENT', 'scffw'); ?> (%)" 
                                            />
                                            <div class="label"><?= __('PERCENT', 'scffw'); ?> (%)</div>
                                        </div>
                                        <div class="help-text"><?= __('If you want the fee to have an additional percentage of the users\' cart, enter the percentage here without the symbol %', 'scffw'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <?php if ( scffw_get_active_wpml_languages() ) : ?>
                            <?php foreach ( scffw_get_active_wpml_languages() as $language ) : ?>
                                <tr class="fee-title">
                                <?php 
                                    
                                    $language_title = '';
                                    if ( array_key_exists('fee_title_languages', $settings) && array_key_exists( 'fee_title_' . $language['code'], $settings['fee_title_languages'] ) ) {
                                        $language_title = $settings['fee_title_languages']['fee_title_' . $language['code']];
                                    }
                                ?>
                                <th>
                                    <div class="th"><?= __('Fee title', 'scffw'); ?> - <?= strtoupper($language['code']); ?></div>
                                </th>
                                <td>
                                    <div class="td">
                                        <input type="text" id="fee-title-<?= $language['code']; ?>" class="fee-title-language-input scffw-user-option scffw-input w-100" name="fee_title_<?= $language['code']; ?>" value="<?= $language_title; ?>" placeholder="<?= __('eg. Extra COD Fee', 'scffw'); ?>" />
                                        <div class="help-text"><?= __('The label for the extra cost, the user will see during checkout', 'scffw'); ?></div>
                                    </div>
                                </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr class="fee-title">
                            <th>
                                <div class="th"><?= __('Fee title', 'scffw'); ?></div>
                            </th>
                            <td>
                                <div class="td">
                                    <input type="text" id="fee-title" class="scffw-user-option scffw-input w-100" name="fee_title" value="<?= $fee_title; ?>" placeholder="<?= __('eg. Extra COD Fee', 'scffw'); ?>" />
                                        <div class="help-text"><?= __('The label for the extra cost, the user will see during checkout', 'scffw'); ?></div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ( is_array($tax_rates) && !empty($tax_rates) ) : ?>
                    <tr>
                        <th>
                            <div class="th">
                                <?= __('Is COD fee taxable?', 'scffw'); ?>
                            </div>
                        </th>
                        <td>
                            <div class="td">
                                <label class="input-checkbox">
                                    <input type="checkbox" class="scffw-user-option" id="enable-tax" <?php checked( $fee_taxable, true ); ?>>
                                    <div class="checkmark"></div>
                                    <div class="label"><?= __('Enable this option if you want the fee to be taxable', 'scffw'); ?></div>
                                </label>
                                <?php 
                                    $tax_rates_options = [];

                                    foreach ( $tax_rates as $tax ) {
                                        $tax_rates_options[$tax->tax_rate_id] = $tax->tax_rate_name;
                                    }
                                ?>
                                <div class="input-row input-select subsection" id="select-tax-rates" style="<?php if ( !$fee_taxable ) { echo 'display:none'; } ?>">
                                    <label for="tax-rates" class="is-bold"><?= __('Select tax', 'scffw'); ?></label>
                                    <select name="tax_rates" id="tax-rates" class="w-100">
                                    <?php foreach ( $tax_rates_options as $rate => $label ) : ?>
                                        <option value="<?= $rate; ?>"><?= $label; ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="scffw-settings-actions">
            <?php wp_nonce_field('save_settings', 'security_nonce'); ?>
            <button class="js-save-scffw-settings-btn save-btn"><?= __('Save', 'scffw'); ?></button>
            <div class="messages">
                <div class="success-message"><?= __('COD Settings have been saved', 'scffw'); ?></div>
                <div class="error-message"><?= __('Something went wrong, try again', 'scffw'); ?></div>
            </div>
        </div>
    </div>
    <?php
}

add_action('wp_ajax_scffw_save_settings', function() {

    $fields = $_POST['fields'];
    // write_log($fields);
    if ( !wp_verify_nonce($fields['nonce'], 'save_settings') ) {
        wp_send_json([
            'status' => 'fail'
        ]);
    }

    foreach ( $fields as &$value ) {
        if ( $value === 'true' ) {
            $value = true;
        }
        elseif ( $value === 'false' ) {
            $value = false;
        }
    }

    $update_options = update_option(SCFFW_COD_SETTINGS, $fields, false);

    $result = [
        'status' => 'success',
        'fields' => $fields
    ];

    wp_send_json($result);
});

add_filter('admin_body_class', function($classes) {
    
    $screen = get_current_screen();
    if ( $screen->id && isset($_GET['section']) && $_GET['section'] == 'cod' ) {
        $classes .= " wc-cod-settings";
    }
    return $classes;
}, 100, 1);