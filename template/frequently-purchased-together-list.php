<?php 

/**
 * Frequently Purchased Together
 */
 global $product;

 $frequently_purchased_together = $this->package_frequently_purchased_together_data( $product->get_id() );
 $total_price = 0;

 if( empty( $frequently_purchased_together ) ) return;

 ?>

<div id="wcfpt-frequently-purchased-together">

    <h3>Frequently Bought Together</h3>
    
    <div class="wrapper">

        <div class="list">

            <?php for( $i = 0; $i < sizeof( $frequently_purchased_together ); $i++ ) : ?>

                <div class="item">
                
                    <?php 
                        $data = $frequently_purchased_together[$i];
                        $total_price += $data['price'];
                    ?>

                    <?php if( $i != 0 ) : ?>
                        <a href="<?php echo $data['permalink'] ?>">
                    <?php endif; ?>

                        <img src="<?php echo $data['image_src'] ?>" />

                    <?php if( $i != 0 ) : ?>
                        </a>
                    <?php endif; ?>  
                
                </div>

            <?php endfor; ?>  

        </div>

        <div class="form-actions">
            <p>Total Price: <span id="wcfpt-price-total" class="woocommerce-Price-amount amount"><?php echo get_woocommerce_currency_symbol() . number_format( $total_price, 2); ?></span></p>
            <a id="wcfpt-add-to-cart-button" href="#">Add to cart</a>
        </div>
        

    </div>
    
    <form id="wcfpt-add-to-cart-form">

        <?php for( $i = 0; $i < sizeof( $frequently_purchased_together ); $i++ ) : ?>
            
            <?php $data = $frequently_purchased_together[$i] ?>

            <input type="checkbox" for="wcfpt-<?php echo $data['id'] ?>" name="add-to-cart[]" value="<?php echo $data['id'] ?>" checked/>
            <label for="wcfpt-<?php echo $data['id'] ?>">

                <?php if( $i == 0 ) : ?>
                    <span><b>This item:</b></span>
                    <span class="title"><?php echo $data['title'] ?></span>

                <?php else : ?>
                    <a href="<?php echo $data['permalink'] ?>"><span class="title"><?php echo $data['title'] ?></span></a>
                <?php endif; ?>



                <span class="price"><?php echo $data['price_html'] ?></span>
            </label>
            <br>

        <?php endfor; ?>

    </form>
</div>