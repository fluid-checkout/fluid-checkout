<?php // CHANGE: Change table elements with `div` elements ?>

<div class="wc_shipping_fan_collectpoint_header shipping" style="display: none">
	<div style="padding-bottom:0"><b><?= esc_html_e('Alege punct CollectPoint', 'curiero-plugin') ?> <abbr class="required" title="required">*</abbr></b></div>
</div>
<div class="wc_shipping_fan_collectpoint shipping" style="display: none">
	<div>
		<style scoped>
			.woocommerce-checkout-review-order-table tfoot .wc_shipping_sameday_lockers td {padding-bottom: 25px;}
		</style>
		<select name="curiero_fan_collectpoint" id="curiero_fan_collectpoint_select" style="width: 100%;">
			<option disabled <?= selected(!$current_collectpoint_exists || empty($selected_collectpoint), true, true) ?>>Alege un CollectPoint</option>
			<?php foreach ($collectpoints as $collectpoint) : ?>
				<option <?= selected($collectpoint['Site_id'], $selected_collectpoint['Site_id'] ?? null, true) ?> value="<?= esc_html($collectpoint['Site_id']) ?>"><?= ucwords(strtolower($collectpoint['Strada'])) ?> - <?= $collectpoint['Distanta'] ?> km</option>
			<?php endforeach; ?>
		</select>
		<script>
			jQuery($ => {
				const collect_point_element = $('.wc_shipping_fan_collectpoint'),
					collect_point_element_header = $('.wc_shipping_fan_collectpoint_header');
				(typeof $().selectWoo == 'function') && collect_point_element.find('#curiero_fan_collectpoint_select').selectWoo();
				collect_point_element.not(':first').remove() && collect_point_element.show();
				collect_point_element_header.not(':first').remove() && collect_point_element_header.show();
			});
		</script>
	</div>
</div>

<?php // CHANGE: END - Change table elements with `div` elements ?>
