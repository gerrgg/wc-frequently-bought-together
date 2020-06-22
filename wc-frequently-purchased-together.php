<?php 
/**
 * Plugin Name: WC Frequently Purchased Together
 * Plugin URI: http://gerrg.com/wc-frequently-purchased-together
 * Description: Group up items and allows customers to add them all to the cart in a single click.
 * Version: 1.0.0
 * Author: GREG BASTIANELLI
 * Author URI: http://gregbastianelli.com/
 * Text Domain: wcfpt
 * Domain Path: /languages
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WC_fpt{
    
    function __construct(){
        // enqueue stylesheets
        add_action( 'wp_enqueue_scripts', array( $this, '_frontend_enqueue_scripts' ) );
        // enqueue admin scripts
        add_action( 'admin_enqueue_scripts', array( $this, '_admin_enqueue_scripts' ) );

        // Adds the frequently_purchased_together select box into the link_products tab on product edit
        add_action( 'woocommerce_product_options_related', array( $this, 'frequently_purchased_together_html' ) );

        // Saves product meta
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_frequently_purchased_together_meta_data' ), 10, 2 );

        // AJAX search for products
        add_action( 'wp_ajax_wcfpt_get_products', array( $this, 'get_products' ) );

        // Add 'Frequently Purchased Together' section to the single_product page. TODO: Probally add to tabs
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'single_product_frequently_purchased_together_html' ), 1 );
    }

    public function _admin_enqueue_scripts(){
        /**
         * Enqueue select2 style and javascript - also custom file for usage.
         */
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
        
	    wp_enqueue_script('wcfpt-script', plugin_dir_url( __FILE__ ) . 'functions.js', array( 'jquery', 'select2' ), '', true ); 
 
    }

    public function _frontend_enqueue_scripts(){
        /**
         * Enqueue select2 style and javascript - also custom file for usage.
         */
        wp_enqueue_style( 'wcfpt', plugin_dir_url( __FILE__ ) . 'style.css' );
    }

    public function frequently_purchased_together_html( ){
        /**
         * Displays a select2 box for adding product ID's to wcfpt_frequently_purchased_together meta data.
         */
        global $post;

        // get any data that has already been set
        $frequently_purchased_together = get_post_meta( $post->ID, 'wcfpt_frequently_purchased_together', true );

        $html = '';
        
        $html .= '<div class="options_group">';
        
        // basic structure
        $html .= '<p class="form_field"><label style="margin: 0" for="wcfpt_frequently_purchased_together">Frequently Purchased Together:</label><br /><select id="wcfpt_frequently_purchased_together" name="wcfpt_frequently_purchased_together[]" multiple="multiple" style="width:99%;max-width:25em;">';

        // get meta and put into select box
        if( $frequently_purchased_together ){

            foreach( $frequently_purchased_together as $post_id ) {

                $title = get_the_title( $post_id );
                // if the post title is too long, truncate it and add "..." at the end
                $title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;

                $html .=  '<option value="' . $post_id . '" selected="selected">' . $title . '</option>';
            }
        }
        
        $html .= '</select></p></div>';
        
        echo $html;

    }

    public function save_frequently_purchased_together_meta_data( $post_id, $post ){
        /**
         * Saves the product ids of items frequently purchased with this product.
         * 
         * If we want reverse look ups - maybe create custom table so that A + B is shown on both product pages.
         * THEY DO have a directly relationship with eachother.
         */
        if( isset( $_POST['wcfpt_frequently_purchased_together'] ) ) update_post_meta( $post_id, 'wcfpt_frequently_purchased_together', $_POST['wcfpt_frequently_purchased_together'] );

        return $post_id;
    }

    public function get_products(){
        // we will pass post IDs and titles to this array
	    $return = array();
 
	    // you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
        $search_results = new WP_Query( array( 
            's'=> $_GET['q'], 
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => 50
        ) );

        if( $search_results->have_posts() ) :
            while( $search_results->have_posts() ) : $search_results->the_post();	
                // shorten the title a little
                $title = $this->truncate( $search_results->post->post_title );
                $return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
            endwhile;
        endif;

        echo json_encode( $return );
        
	    die;
    }

    public function single_product_frequently_purchased_together_html(){
        /**
         * Display a section for adding all FPT products to the cart with a single click
         */
        include_once( 'template/frequently-purchased-together-list.php' );
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

    private function package_frequently_purchased_together_data( $product_id ){
        /**
         * Packages product data up into a organized array of applicable data required for 'frequently bought together'
         */

         // get the ids
        $frequently_purchased_together = get_post_meta( $product_id, 'wcfpt_frequently_purchased_together', true );

        if( empty( $frequently_purchased_together ) ) return;
        
        // init array
        $packaged_data = array();

        // add currently viewed product to the FRONT of the array.
        array_unshift( $frequently_purchased_together, strval($product_id) );

        for( $i = 0; $i < sizeof($frequently_purchased_together); $i++ ){

            // get product
            $product = wc_get_product( $frequently_purchased_together[$i] );

            if( $product ){
                // add to packaged array
                array_push( $packaged_data, array(
                    'id'         => $product->get_id(),
                    'title'      => $this->truncate( $product->get_name() ),
                    'permalink'  => $product->get_permalink(),
                    'image_src'  => wp_get_attachment_image_src( $product->get_image_id() )[0],
                    'price'      => $product->get_price(),
                    'price_html' => $product->get_price_html(),
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
    new WC_fpt();
}
