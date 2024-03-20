<?php defined( 'ABSPATH' ) || exit; ?>
<?php // CHANGE: Change table elements with `div` elements ?>

<div class="wc_shipping_dpd_boxes_header shipping" style="display: none">
	<div style="padding-bottom:0"><b><?= esc_html_e('Alege punct de ridicare', 'curiero-plugin') ?> <abbr class="required" title="required">*</abbr></b></div>
</div>
<div class="wc_shipping_dpd_boxes">
	<div style="text-align: left;">
		<style scoped>
			.woocommerce-checkout-review-order-table tfoot .wc_shipping_dpd_boxes td {padding-bottom: 25px;}
		</style>
		<select name="curiero_dpd_box" id="curiero_dpd_box_select" style="width: 100%;">
		<option disabled <?= selected(!$current_box_exists || empty($current_dpd_box), true, true) ?>>Alege un DPDBox</option>
			<?php 
				foreach ($dpd_boxes as $dpd_box) : ?>
				<option  <?php selected($dpd_box['id'], $current_dpd_box['id'] ?? null, true) ?> value="<?= esc_html($dpd_box['id']) ?>"> <?= ucwords(strtolower($dpd_box['address'])) ?> </option>
				<?php endforeach;
			?>
		</select>
		<script>
			jQuery($ => {
				const dpd_box_element = $('.wc_shipping_dpd_boxes'),
					dpd_box_element_header = $('.wc_shipping_dpd_boxes_header');
				(typeof $().selectWoo == 'function') && dpd_box_element.find('#curiero_dpd_box_select').selectWoo();
				dpd_box_element.not(':first').remove() && dpd_box_element.show();
				dpd_box_element_header.not(':first').remove() && dpd_box_element_header.show();
			});
		</script>
	</div>
</div>

<?php // CHANGE: END - Change table elements with `div` elements ?>
