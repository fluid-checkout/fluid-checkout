<?php // CHANGE: Change table elements with `div` elements ?>

<div class="wc_shipping_sameday_lockers_header shipping" style="display: none">
	<div style="padding-bottom:0"><b><?= esc_html_e('Alege punct EasyBox', 'curiero-plugin') ?> <abbr class="required" title="required">*</abbr></b></div>
</div>
<div class="wc_shipping_sameday_lockers shipping" style="display: none">
	<div colspan="2">
		<style scoped>
			.woocommerce-checkout-review-order-table tfoot .wc_shipping_sameday_lockers td {padding-bottom: 25px;}
		</style>
		<select name="curiero_sameday_lockers" id="curiero_sameday_lockers_select" style="width: 100%;">
			<option disabled <?= selected(!$current_locker_exists || empty($current_locker), true, true) ?>>Alege un EasyBox</option>
			<?php
			if (!$local_box_found) :
				foreach ($lockers as $locker) : ?>
				<option <?php selected($locker['id'], $current_locker['id'] ?? null, true) ?> value="<?= esc_html($locker['id'])?>"> <?= ucwords(strtolower($locker['city'])) . ' - ' . ucwords(strtolower($locker['name'])) . ' - ' . ucwords(strtolower($locker['address']))  ?> </option>
				<?php endforeach;
			else :
				foreach ($lockers as $locker) : ?>
				<option <?php selected($locker['id'], $current_locker['id'] ?? null, true) ?> value="<?= esc_html($locker['id'])?>"> <?= ucwords(strtolower($locker['name'])) . ' - ' . ucwords(strtolower($locker['address']))  ?> </option>
				<?php endforeach;
			endif; ?>
		</select>
		<script>
			jQuery($ => {
				const easybox_element = $('.wc_shipping_sameday_lockers'),
					element_header = $('.wc_shipping_sameday_lockers_header');
				(typeof $().selectWoo == 'function') && easybox_element.find('#curiero_sameday_lockers_select').selectWoo();
				easybox_element.not(':first').remove() && easybox_element.show();
				element_header.not(':first').remove() && element_header.show();
			});
		</script>
	</div>
</div>

<div class="shipping-pickup-store" <?= ($lockers_map_active == "yes") ? '' : 'style="display: none;"' ?>>
	<div>
		<button type="button" class="button alt sameday_select_locker" style="padding: 10px; font-size: 15px;" id="select_locker_map" ><?php echo __('Arata Harta Easybox', 'curiero-plugin') ?></button>
	</div>
</div>

<?php // CHANGE: END - Change table elements with `div` elements ?>
