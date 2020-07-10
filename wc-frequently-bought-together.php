<?php 
/**
 * Plugin Name: Frequently Bought Together for WooCommerce
 * Plugin URI: http://gerrg.com/wc-frequently-bought-together
 * Description: Group up items frequently purchased together and add to cart with a single click.
 * Version: 1.1.0
 * Author: GREG BASTIANELLI
 * Author URI: http://gerrg.com/
 * Text Domain: wcfbt
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class wcfbt_frequently_bought_together{
    
    function __construct(){
        // enqueue stylesheets
        add_action( 'wp_enqueue_scripts', array( $this, '_frontend_enqueue_scripts' ) );
        // enqueue admin scripts
        add_action( 'admin_enqueue_scripts', array( $this, '_admin_enqueue_scripts' ) );

        // Adds the frequently_bought_together select box into the link_products tab on product edit
        add_action( 'woocommerce_product_options_related', array( $this, 'frequently_bought_together_html' ) );

        // Saves product meta
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_frequently_bought_together_meta_data' ), 10, 2 );

        // AJAX search for products
        add_action( 'wp_ajax_wcfbt_get_products', array( $this, 'get_products' ) );
        add_action( 'wp_ajax_wcfbt_get_variation_id', array( $this, 'get_variation_id' ) );

        // Add 'Frequently Purchased Together' section to the single_product page. TODO: Probally add to tabs
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'single_product_frequently_bought_together_html' ), 1 );
    }


    public function _admin_enqueue_scripts(){
        /**
         * Enqueue select2 style and javascript - also custom file for usage.
         */

         // use woocommerce select2
        wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css' );
        wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.min.js', array('jquery') );

	    wp_enqueue_script('wcfbt-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'select2' ), '', true ); 
 
    }

    public function _frontend_enqueue_scripts(){
        /**
         * Enqueue select2 style and javascript - also custom file for usage.
         */
        
        wp_enqueue_style( 'wcfbt', plugin_dir_url( __FILE__ ) . 'style.css' );
        
        wp_enqueue_script( 'wcfbt-frontend', plugin_dir_url( __FILE__ ) . 'js/functions.js', array(), '', true ); 

        // make ajax url available to functions.js
        wp_localize_script( 'wcfbt-frontend', 'wp_ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function frequently_bought_together_html( ){
        /**
         * Displays a select2 box for adding product ID's to wcfbt_frequently_bought_together meta data.
         */
        global $post;

        // get any data that has already been set
        $frequently_bought_together = get_post_meta( $post->ID, 'wcfbt_frequently_bought_together', true );

        $html = '';
        
        $html .= '<div class="options_group">';
        
        // basic structure
        $html .= '<p class="form_field"><label style="margin: 0" for="wcfbt_frequently_bought_together">Frequently Purchased Together:</label><br /><select id="wcfbt_frequently_bought_together" name="wcfbt_frequently_bought_together[]" multiple="multiple" style="width:99%;max-width:25em;">';

        // get meta and put into select box
        if( $frequently_bought_together ){

            foreach( $frequently_bought_together as $post_id ) {

                $title = get_the_title( $post_id );
                // if the post title is too long, truncate it and add "..." at the end
                $title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;

                $html .=  '<option value="' . $post_id . '" selected="selected">' . $title . '</option>';
            }
        }
        
        $html .= '</select></p></div>';
        
        echo $html;

    }

    public function save_frequently_bought_together_meta_data( $post_id, $post ){
        /**
         * Saves the product ids of items frequently purchased with this product.
         */
        if( isset( $_POST['wcfbt_frequently_bought_together'] ) ) {

            // check for posts
            $posts = isset( $_POST['wcfbt_frequently_bought_together'] ) ? (array) $_POST['wcfbt_frequently_bought_together'] : array();

            // sanitize the array
            $posts = array_map( 'sanitize_text_field', $posts );

            // update
            update_post_meta( $post_id, 'wcfbt_frequently_bought_together', $posts );
        }

        return $post_id;
    }

    public function get_products(){
        /**
         * Returns products - doesnt work with variable products.
         * @return string 
         */
	    $return = array();
 
	    // you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
        $search_results = new WP_Query( array( 
            's'=> sanitize_text_field( $_GET['q'] ), 
            'post_type' => array( 'product', 'product_variation'),
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => 50,
        ) );


        if( $search_results->have_posts() ) :
            while( $search_results->have_posts() ) : $search_results->the_post();	
                // shorten the title a little
                $sku = get_post_meta( $search_results->post->ID, '_sku', true );
                $title = sprintf( '%s - (SKU: %s)', $this->truncate( $search_results->post->post_title ), $sku );
                $return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
            endwhile;
        endif;

        echo json_encode( $return );
        
	    die;
    }

    public function get_variation_id(){
        /**
         * Get variation ID based on attribute selection
         */
        $options = ( ! empty( $_POST['options'] ) ) ? $_POST['options'] : array();
        $parent_id = ( ! empty( $_POST['parent_id'] ) ) ? $_POST['parent_id'] : 0;

        $product = wc_get_product( $parent_id );

        if( $product && ! empty( $options ) ){

            // loop through available variations
            foreach( $product->get_available_variations() as $variation ){
                // if there is a MATCH between the selected attributes and a variation's - return ID.
                if( empty( array_diff( $options, $variation['attributes'] ) ) ){

                    // send variation_id and variation_price
                    wp_send_json( array( 
                        $variation['variation_id'], 
                        $variation['price_html'] 
                    )  );
                    
                }

            }

        }

        die;
    }

    public function single_product_frequently_bought_together_html(){
        /**
         * Display a section for adding all FPT products to the cart with a single click
         */
        include_once( 'template/frequently-bought-together-list.php' );
    }

    private function truncate( $str, $limit = 50 ){
        /**
         * Limits the number of characters in a string
         * @param string $str
         * @param int $limit
         * @return string
         */
        return ( mb_strlen( $str ) > $limit ) ? mb_substr( $str, 0, ($limit - 1) ) . '...' : $str;
    }

    private function package_frequently_bought_together_data( $product_id ){
        /**
         * Packages product data up into a organized array of applicable data required for 'frequently bought together'
         */

         // get the ids
        $frequently_bought_together = get_post_meta( $product_id, 'wcfbt_frequently_bought_together', true );

        // dont do anything if not set
        if( empty( $frequently_bought_together ) ) return;
        
        // init array
        $packaged_data = array();

        // add currently viewed product to the FRONT of the array.
        array_unshift( $frequently_bought_together, strval($product_id) );

        for( $i = 0; $i < sizeof($frequently_bought_together); $i++ ){

            // get product
            $product = wc_get_product( $frequently_bought_together[$i] );

            // check if item is in stock.
            if( $product && $product->get_stock_status() === 'instock' ){

                // add to packaged array
                array_push( $packaged_data, array(
                    'id'             => $product->get_id(),
                    'is_variable'       => $product->is_type( 'variable' ),
                    'title'          => $this->truncate( $product->get_name() ),
                    'permalink'      => $product->get_permalink(),
                    'image_src'      => wp_get_attachment_image_src( $product->get_image_id() )[0],
                    'price'          => $product->get_price(),
                    'price_html'     => $product->get_price_html(),
                ) );
            }
        }

        return $packaged_data;
    }
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    include_once( 'functions.php' );

    new wcfbt_frequently_bought_together();
}

