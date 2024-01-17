<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
	<?php
	// Render the block using the shortcode-based checkout.
	echo do_shortcode( '[woocommerce_checkout]' );
	?>
</div>
