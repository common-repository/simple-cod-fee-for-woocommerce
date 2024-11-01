<?php


add_filter('woocommerce_my_account_my_orders_actions', function($actions, $order) {
    if ( array_key_exists('pay', $actions) ) {
        unset($actions['pay']);
        $actions['repeat'] = [
            'url' => wc_get_checkout_url() . 'order-repeat/' . $order->get_id() ,
            'name' => __('Repeat order', 'scffw')
        ];
    }
    return $actions;
}, 10, 2);

// Repeat order
add_action( 'template_redirect', function () {
    // if the user is not logged in bail early
    if( ! is_user_logged_in() ) return;
    
    $path = ltrim($_SERVER['REQUEST_URI'], '/');
    $path = explode('/', $path);

    if ( $path[0] === 'checkout' && $path[1] === 'order-repeat' && is_numeric($path[2]) ) {
        $order_id = intval($path[2]);
        $order = wc_get_order($order_id);

        // check if the order id provided belongs to the loggedin user
        if ( $order && $order->get_user_id() === get_current_user_id() ) {
            $cart = WC()->cart;
            $cart->empty_cart();

            foreach($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $variation_id = $item->get_variation_id();
                $variation_data = array();
                
                if ($variation_id) {
                    $variation_data = $item->get_meta( '_variation_data' );
                }

                $cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
            }

            // If you want to redirect to the checkout page immediately
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }
} );
