<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


if( ! function_exists( 'woocommerce_maybe_add_multiple_products_to_cart' ) ){

    function woocommerce_maybe_add_multiple_products_to_cart( $url = false ) {
        // Make sure WC is installed, and add-to-cart qauery arg exists, and contains at least one comma.
        if ( ! class_exists( 'WC_Form_Handler' ) || empty( $_REQUEST['add-to-cart'] ) || false === strpos( $_REQUEST['add-to-cart'], ',' ) ) {
            return;
        }
     
        // Remove WooCommerce's hook, as it's useless (doesn't handle multiple products).
        remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );
     
        $product_ids = explode( ',', $_REQUEST['add-to-cart'] );
        $count       = count( $product_ids );
        $number      = 0;
     
        foreach ( $product_ids as $id_and_quantity ) {
            // Check for quantities defined in curie notation (<product_id>:<product_quantity>)
            // https://dsgnwrks.pro/snippets/woocommerce-allow-adding-multiple-products-to-the-cart-via-the-add-to-cart-query-string/#comment-12236
            $id_and_quantity = explode( ':', $id_and_quantity );
            $product_id = $id_and_quantity[0];
     
            $_REQUEST['quantity'] = ! empty( $id_and_quantity[1] ) ? absint( $id_and_quantity[1] ) : 1;
     
            if ( ++$number === $count ) {
                // Ok, final item, let's send it back to woocommerce's add_to_cart_action method for handling.
                $_REQUEST['add-to-cart'] = $product_id;
     
                return WC_Form_Handler::add_to_cart_action( $url );
            }
     
            $product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );
            $was_added_to_cart = false;
            $adding_to_cart    = wc_get_product( $product_id );
     
            if ( ! $adding_to_cart ) {
                continue;
            }
     
            $add_to_cart_handler = apply_filters( 'woocommerce_add_to_cart_handler', $adding_to_cart->get_type(), $adding_to_cart );
     
            // Variable product handling
            if ( 'variable' === $add_to_cart_handler ) {
                woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_variable', $product_id );
     
            // Grouped Products
            } elseif ( 'grouped' === $add_to_cart_handler ) {
                woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_grouped', $product_id );
     
            // Custom Handler
            } elseif ( has_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler ) ){
                do_action( 'woocommerce_add_to_cart_handler_' . $add_to_cart_handler, $url );
     
            // Simple Products
            } else {
            // woo_hack_invoke_private_method( 'WC_Form_Handler', 'add_to_cart_handler_simple', $product_id );
                WC()->cart->add_to_cart($product_id, $_REQUEST['quantity']);
            
            }
        }
     
    }
}

// Fire before the WC_Form_Handler::add_to_cart_action callback.
add_action( 'wp_loaded', 'woocommerce_maybe_add_multiple_products_to_cart', 15 );
     
     
if( ! function_exists( 'woo_hack_invoke_private_method' ) ){
    /**
     * Invoke class private method
     *
     * @since   0.1.0
     *
     * @param   string $class_name
     * @param   string $methodName
     *
     * @return  mixed
     */

    function woo_hack_invoke_private_method( $class_name, $methodName ) {
        if ( version_compare( phpversion(), '5.3', '<' ) ) {
            throw new Exception( 'PHP version does not support ReflectionClass::setAccessible()', __LINE__ );
        }
        
        $args = func_get_args();
        unset( $args[0], $args[1] );
        $reflection = new ReflectionClass( $class_name );
        $method = $reflection->getMethod( $methodName );
        $method->setAccessible( true );
        
        $args = array_merge( array( $class_name ), $args );
        return call_user_func_array( array( $method, 'invoke' ), $args );
    }

}