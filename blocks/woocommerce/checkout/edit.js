/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';



const edit = ( props ) => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<div class="components-placeholder wp-block-woocommerce-classic-shortcode__placeholder is-large">
				<div class="components-placeholder__label"></div>
				<div class="components-placeholder__fieldset">
					<div class="wp-block-woocommerce-classic-shortcode__placeholder-wireframe"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 892 726" aria-hidden="true" focusable="false"><g fill="currentColor" transform="translate(-1)"><rect width="203" height="38" x="1" rx="2"></rect><rect width="434" height="38" x="1" y="62" rx="2"></rect><rect width="434" height="38" y="124" rx="2"></rect><rect width="434" height="38" x="1" y="186" rx="2"></rect><rect width="434" height="38" x="2" y="248" rx="2"></rect><rect width="434" height="38" x="3" y="310" rx="2"></rect><rect width="434" height="38" x="3" y="372" rx="2"></rect><rect width="892" height="204" x="2" y="434" rx="2"></rect><rect width="203" height="38" x="231" rx="2"></rect><rect width="203" height="38" x="514" rx="2"></rect><rect width="427" height="100" x="466" y="62" rx="2"></rect><rect width="304" height="64" x="588" y="662" rx="2"></rect><rect width="38" height="38" x="466" rx="2"></rect><rect width="203" height="38" x="48" y="662" rx="2"></rect><rect width="38" height="38" y="662" rx="2"></rect></g></svg></div>
					<div class="wp-block-woocommerce-classic-shortcode__placeholder-copy" style={ { opacity: 1 } }>
						<div class="wp-block-woocommerce-classic-shortcode__placeholder-copy__icon-container">{ __( 'Fluid Checkout', 'fluid-checkout' ) }<span>{ __( 'Checkout Block', 'fluid-checkout' ) }</span></div>
						<p>{ __( 'This block replaces the original WooCommerce Checkout block and will render the classic checkout shortcode, which is required for Fluid Checkout to work. If Fluid Checkout is deactivated, this block will fall back to render the original WooCommerce Checkout block.', 'fluid-checkout' ) }</p>
						<div class="wp-block-woocommerce-classic-shortcode__placeholder-migration-button-container"><a href="https://fluidcheckout.com/docs/getting-started-fluid-checkout/#shortcode-vs-blocks" target="_blank" tabindex="0" class="components-button is-secondary">{ __( 'Read the documentation.', 'fluid-checkout' ) }</a></div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default edit;