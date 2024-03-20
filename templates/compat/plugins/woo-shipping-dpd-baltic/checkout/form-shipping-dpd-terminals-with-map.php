<?php
/**
 * Form shipping dpd terminals with map template.
 *
 * @category Form shipping
 * @package  Dpd
 * @author   DPD
 */

defined( 'ABSPATH' ) || exit;
?>

<?php // CHANGE: Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>
<div class="wc_shipping_dpd_terminals form-row form-row-wide validate-required fc-no-validation-icon">
	<?php // CHANGE: Add label to use `label` element and fix markup for required attribute ?>
	<label for="<?php echo $field_id ?>"><?php echo esc_html( __( 'Choose a Pickup Point', 'woo-shipping-dpd-baltic' ) ); ?>&nbsp;<abbr class="required" aria-label="<?php echo esc_attr( __( '(Required)', 'fluid-checkout' ) ); ?>" title="<?php echo esc_attr( __( 'required', 'woocommerce' ) ); ?>">*</abbr></label>
	<div class="woocommerce-input-wrapper">
		<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $selected ); ?>">
		<?php // CHANGE: Remove `<br>` element adding unnecessary spacing ?>
		<span id="dpd-selected-parcel"><?php echo esc_html( $selected_name ); ?></span>
		<a href="#" id="dpd-show-parcel-modal"><?php esc_html_e( 'Show Pickup Points', 'woo-shipping-dpd-baltic' ); ?></a>

		<style>
			#dpd-parcel-modal {
				display: none;
				position: fixed;
				z-index: 9999;
				padding-top: 100px;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				overflow: auto;
				background-color: rgb(0,0,0);
				background-color: rgba(0,0,0,0.4);
				font-size: 14px;
			}

			#dpd-parcel-modal h3 {
				font-size: 18px;
			}

			#dpd-parcel-modal .modal-content {
				background-color: #fefefe;
				margin: auto;
				padding: 20px;
				border: 1px solid #888;
				width: 80%;
				max-width: 900px;
			}

			#dpd-parcel-modal .dpd-city-label {
				padding-right: 10px;
				padding-left: 5px;
				text-transform: capitalize;
			}

			#dpd-parcel-modal .close {
				color: #aaaaaa;
				float: right;
				font-size: 28px;
				font-weight: bold;
			}

			#dpd-parcel-modal .close:hover,
			#dpd-parcel-modal .close:focus {
				color: #000;
				text-decoration: none;
				cursor: pointer;
			}

			#dpd-parcel-modal .modal-map{
				height: 400px;
				margin-top: 20px;
				position: relative;
			}

			#dpd-parcel-modal-map {
				height: 100%;
			}

			#dpd-parcel-modal-info {
				position: absolute;
				top: 10px;
				bottom: 10px;
				width: 300px;
				right: 10px;
				background-color: #ffffff;
			// background-color: rgba(255, 255, 255, 0.9);
				display: none;
			}

			#dpd-parcel-modal-info .working-hours {
				padding: 0;
				margin: 0;
				list-style: none inside;
				font-size: 11px;
			}
			#dpd-parcel-modal-info .working-hours span {
				width: 80px;
				margin-right: 5px;
				display: inline-block;
			}

			#dpd-parcel-modal-info .info-wrap {
				position: relative;
				padding: 10px;
				height: 100%;
			}

			#dpd-parcel-modal-info .select-terminal {
				position: absolute;
				bottom: 10px;
				left: 10px;
				right: 10px;
				text-align: center;
			}
		</style>

		<div id="dpd-parcel-modal">
			<div class="modal-content">
				<span class="close" id="dpd-close-parcel-modal">&times;</span>

				<div class="form-inline">
					<div class="form-group">
						<input name="dpd-modal-address" value="<?php echo esc_attr( WC()->customer->get_shipping_address() ); ?>" type="text" class="form-control" placeholder="<?php echo esc_attr_e( 'Address', 'woo-shipping-dpd-baltic' ); ?>">
						<label class="dpd-city-label"><?php echo esc_html( WC()->customer->get_shipping_city() ); ?></label>
						<input type="hidden" name="dpd-modal-city" value="<?php echo esc_attr( WC()->customer->get_shipping_city() ); ?>">
						<a href="#" class="button search-location"><?php echo esc_html_e( 'Search', 'woo-shipping-dpd-baltic' ); ?></a>
					</div>
				</div>

				<div class="modal-map">
					<!-- Map -->
					<div id="dpd-parcel-modal-map"></div>

					<!-- Info block -->
					<div id="dpd-parcel-modal-info">
						<div class="info-wrap">
							<h3></h3>
							<p>
								<strong><?php echo esc_html_e( 'Address', 'woo-shipping-dpd-baltic' ); ?></strong>
								<br>
								<span class="info-address"></span>
							</p>
							<div class="working-hours-wrapper">
								<p>
									<strong><?php echo esc_html_e( 'Working hours', 'woo-shipping-dpd-baltic' ); ?></strong>
								</p>

								<ul class="working-hours">
									<li class="mon"><span><?php echo esc_html_e( 'Monday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="tue"><span><?php echo esc_html_e( 'Tuesday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="wed"><span><?php echo esc_html_e( 'Wednesday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="thu"><span><?php echo esc_html_e( 'Thursday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="fri"><span><?php echo esc_html_e( 'Friday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="sat"><span><?php echo esc_html_e( 'Saturday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
									<li class="sun"><span><?php echo esc_html_e( 'Sunday:', 'woo-shipping-dpd-baltic' ); ?></span> <span class="morning"></span> <span class="afternoon"></span></li>
								</ul>
							</div>

							<p style="display: none;">
								<strong><?php echo esc_html_e( 'Contact', 'woo-shipping-dpd-baltic' ); ?></strong>
								<br>
								<span class="info-email"></span>
								<br>
								<span class="info-phone"></span>
							</p>

							<a href="#" class="button alt select-terminal" data-method="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html_e( 'Select', 'woo-shipping-dpd-baltic' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php // CHANGE: END - Replace `tr > td` elements with `div > span` as a form field as this section is moved out of the order summary table on the checkout page ?>
