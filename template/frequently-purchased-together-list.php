<?php 

/**
 * Frequently Purchased Together
 */
 global $product;

 $frequently_purchased_together = $this->package_frequently_purchased_together_data( $product->get_id() );
 $total_price = 0;

 if( empty( $frequently_purchased_together ) ) return;

 ?>

<div class="wcfpt frequently-purchased-together">

    <h3>Frequently Bought Together</h3>

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
    
    <form class="wcfpt-add-to-cart">

        <?php for( $i = 0; $i < sizeof( $frequently_purchased_together ); $i++ ) : ?>
            
            <?php $data = $frequently_purchased_together[$i] ?>

            <input type="checkbox" for="wcfpt-<?php echo $data['id'] ?>" name="add-to-cart[]" value="<?php echo $data['id'] ?>" checked/>
            <label for="wcfpt-<?php echo $data['id'] ?>">

                <?php if( $i == 0 ) : ?>
                    <span><b>This item:</b></span>
                <?php endif; ?>

                <span class="title"><?php echo $data['title'] ?></span>
                <span class="price"><?php echo $data['price_html'] ?></span>
            </label>
            <br>

        <?php endfor; ?>

    </form>
</div>