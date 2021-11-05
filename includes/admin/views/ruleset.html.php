<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="woo-conditional-shipping-heading">
	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ); ?>">
		<?php _e( 'Conditions', 'woo-conditional-shipping' ); ?>
	</a>
	 &gt; 
	<?php echo $ruleset->get_title(); ?>
</h2>

<table class="form-table woo-conditional-shipping-ruleset-settings">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Enable / Disable', 'woo-conditional-shipping' ); ?>
				</label>
			</th>
			<td class="forminp">
				<input type="checkbox" name="ruleset_enabled" id="ruleset_enabled" value="1" <?php checked( $ruleset->get_enabled() ); ?> />
				<label for="ruleset_enabled"><?php _e( 'Enable ruleset', 'woo-conditional-shipping' ); ?></label>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Ruleset name', 'woo-conditional-shipping' ); ?>
					<?php echo wc_help_tip( __( 'This is the name of the ruleset for your reference.', 'woo-conditional-shipping' ) ); ?>
				</label>
			</th>
			<td class="forminp">
				<input type="text" name="ruleset_name" id="ruleset_name" value="<?php echo esc_attr( $ruleset->get_title( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'Ruleset name', 'woo-conditional-shipping' ); ?>" />
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Conditions', 'woo-conditional-shipping' ); ?>
					<?php echo wc_help_tip( __( 'The following conditions define whether or not actions are run.', 'woo-conditional-shipping' ) ); ?>
				</label>
			</th>
			<td class="">
				<table
					class="woo-conditional-shipping-conditions widefat"
					data-operators="<?php echo htmlspecialchars( json_encode( woo_conditional_shipping_operators() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-selected-products="<?php echo htmlspecialchars( json_encode( $ruleset->get_products() ), ENT_QUOTES, 'UTF-8' ); ?>"
					data-conditions="<?php echo htmlspecialchars( json_encode( $ruleset->get_conditions() ), ENT_QUOTES, 'UTF-8' ); ?>"
				>
					<tbody class="woo-conditional-shipping-condition-rows">
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4" class="forminp">
								<button type="button" class="button" id="wcs-add-condition"><?php _e( 'Add Condition', 'woo-conditional-shipping' ); ?></button>
								<button type="button" class="button" id="wcs-remove-conditions"><?php _e( 'Remove Selected', 'woo-conditional-shipping' ); ?></button>
							</td>
						</tr>
					</tfoot>
				</table>
				<?php if ( ! class_exists( 'Woo_Conditional_Shipping_Pro' ) ) { ?>
					<p class="description conditions-desc">
						<?php printf( __( 'More conditions available in <a href="%s" target="_blank">the Pro version</a>.', 'woo-conditional-shipping' ), 'https://wooelements.com/products/conditional-shipping' ); ?>
					</p>
				<?php } ?>
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Actions', 'woo-conditional-shipping' ); ?>
					<?php echo wc_help_tip( __( 'Actions which are run if all conditions pass.', 'woo-conditional-shipping' ) ); ?>
				</label>
			</th>
			<td class="">
				<table
					class="woo-conditional-shipping-actions widefat"
					data-actions="<?php echo htmlspecialchars( json_encode( $ruleset->get_actions() ), ENT_QUOTES, 'UTF-8' ); ?>"
				>
					<tbody class="woo-conditional-shipping-action-rows">
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4" class="forminp">
								<button type="button" class="button" id="wcs-add-action"><?php _e( 'Add Action', 'woo-conditional-shipping' ); ?></button>
								<button type="button" class="button" id="wcs-remove-actions"><?php _e( 'Remove Selected', 'woo-conditional-shipping' ); ?></button>
							</td>
						</tr>
					</tfoot>
				</table>
				<p class="description actions-desc">
					<?php _e( '<strong>Enable shipping methods</strong>: Shipping methods will be enabled if all conditions pass. If conditions do not pass, shipping methods will be disabled.', 'woo-conditional-shipping' ); ?><br>
					<?php _e( '<strong>Disable shipping methods</strong>: Shipping methods will be disabled if all conditions pass.', 'woo-conditional-shipping' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>

<p class="submit">
	<button type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save changes', 'woo-conditional-shipping' ); ?>"><?php esc_html_e( 'Save changes', 'woo-conditional-shipping' ); ?></button>

	<input type="hidden" value="<?php echo $ruleset->get_id(); ?>" name="ruleset_id" />
	<input type="hidden" value="1" name="save" />

	<?php wp_nonce_field( 'woocommerce-settings' ); ?>
</p>

<script type="text/html" id="tmpl-wcs_row_template">
	<tr valign="top" class="condition_row">
		<th class="condition_remove">
			<input type="checkbox" class="remove_condition">
		</th>
		<th scope="row" class="titledesc">
			<fieldset>
				<select name="wcs_conditions[{{data.index}}][type]" class="wcs_condition_type_select">
					<?php foreach ( woo_conditional_shipping_filter_groups() as $filter_group ) { ?>
						<optgroup label="<?php echo $filter_group['title']; ?>">
							<?php foreach ( $filter_group['filters'] as $key => $filter ) { ?>
								<option
									value="<?php echo $key; ?>"
									data-operators="<?php echo htmlspecialchars( json_encode( $filter['operators'] ), ENT_QUOTES, 'UTF-8'); ?>"
									<# if ( data.type == '<?php echo $key; ?>' ) { #>selected<# } #>
								>
									<?php echo $filter['title']; ?>
								</option>
							<?php } ?>
						</optgroup>
					<?php } ?>
				</select>
			</fieldset>
		</th>
		<td class="forminp">
			<div class="value_input wcs_product_measurement_mode_input">
				<select name="wcs_conditions[{{data.index}}][product_measurement_mode]" class="">
					<option value="highest" <# if ( data.product_measurement_mode && data.product_measurement_mode == 'highest' ) { #>selected<# } #>><?php _e( 'highest', 'woo-conditional-shipping' ); ?></option>
					<option value="lowest" <# if ( data.product_measurement_mode && data.product_measurement_mode == 'lowest' ) { #>selected<# } #>><?php _e( 'lowest', 'woo-conditional-shipping' ); ?></option>
				</select>
			</div>

			<?php $subset_filters = woo_conditional_shipping_subset_filters(); ?>

			<?php if ( ! empty( $subset_filters ) ) { ?>
				<div class="value_input wcs_subset_filter_input">
					<select name="wcs_conditions[{{data.index}}][subset_filter]" class="wcs_subset_filter_input_select">
						<?php foreach ( woo_conditional_shipping_subset_filters() as $key => $filter ) { ?>
							<?php if ( is_array( $filter ) ) { ?>
								<optgroup label="<?php esc_attr_e( $filter['title'] ); ?>">
									<?php foreach ( $filter['options'] as $filter_key => $filter_label ) { ?>
										<option
											value="<?php echo $filter_key; ?>"
											class="wcs-subset-filter wcs-subset-filter-<?php echo $filter_key; ?>"
											<# if ( data.subset_filter == '<?php echo $filter_key; ?>' ) { #>selected<# } #>
										>
											<?php echo $filter_label; ?>
										</option>
									<?php } ?>
								</optgroup>
							<?php } else { ?>
								<option
									value="<?php echo $key; ?>"
									class="wcs-subset-filter wcs-subset-filter-<?php echo $key; ?>"
									<# if ( data.subset_filter == '<?php echo $key; ?>' ) { #>selected<# } #>
								>
									<?php echo $filter; ?>
								</option>
							<?php } ?>
						<?php } ?>
					</select>
				</div>
			<?php } ?>

			<select class="wcs_operator_select" name="wcs_conditions[{{data.index}}][operator]">
				<?php foreach ( woo_conditional_shipping_operators() as $key => $operator ) { ?>
					<option
						value="<?php echo $key; ?>"
						class="wcs-operator wcs-operator-<?php echo $key; ?>"
						<# if ( data.operator == '<?php echo $key; ?>' ) { #>selected<# } #>
					>
						<?php echo $operator; ?>
					</option>
				<?php } ?>
			</select>
		</td>
		<td class="forminp">
			<fieldset class="wcs_condition_value_inputs">
				<input class="input-text value_input regular-input wcs_text_value_input" type="text" name="wcs_conditions[{{data.index}}][value]" value="{{data.value}}" />

				<div class="value_input wcs_subtotal_value_input">
					<input type="checkbox" id="wcs-subtotal-includes-coupons-{{data.index}}" value="1" name="wcs_conditions[{{data.index}}][subtotal_includes_coupons]" <# if ( data.subtotal_includes_coupons ) { #>checked<# } #> />
					<label for="wcs-subtotal-includes-coupons-{{data.index}}"><?php _e( 'Subtotal includes coupons', 'woo-conditional-shipping' ); ?></label>
				</div>

				<div class="value_input wcs_shipping_class_value_input">
					<select name="wcs_conditions[{{data.index}}][shipping_class_ids][]" multiple class="select wc-enhanced-select">
						<?php foreach ( woo_conditional_shipping_get_shipping_class_options() as $key => $label ) { ?>
							<option value="<?php echo $key; ?>" <# if ( data.shipping_class_ids && data.shipping_class_ids.indexOf("<?php echo $key; ?>") !== -1 ) { #>selected<# } #>><?php echo $label; ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_category_value_input">
					<select name="wcs_conditions[{{data.index}}][category_ids][]" multiple class="select wc-enhanced-select">
						<?php foreach ( woo_conditional_shipping_get_category_options() as $key => $label) { ?>
							<option value="<?php echo $key; ?>" <# if ( data.category_ids && data.category_ids.indexOf("<?php echo $key; ?>") !== -1 ) { #>selected<# } #>><?php echo $label; ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_product_value_input">
					<select class="wc-product-search" multiple="multiple" name="wcs_conditions[{{data.index}}][product_ids][]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
						<# if ( data.selected_products && data.selected_products.length > 0 ) { #>
							<# _.each(data.selected_products, function(product) { #>
								<option value="{{ product['id'] }}" selected>{{ product['title'] }}</option>
							<# }) #>
						<# } #>
					</select>
				</div>

				<div class="value_input wcs_coupon_value_input">
					<select name="wcs_conditions[{{data.index}}][coupon_ids][]" multiple class="select wc-enhanced-select">
						<?php foreach ( woo_conditional_shipping_get_coupon_options() as $key => $label ) { ?>
							<option value="<?php echo $key; ?>" <# if ( data.coupon_ids && data.coupon_ids.indexOf("<?php echo $key; ?>") !== -1 ) { #>selected<# } #>><?php echo $label; ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_user_role_value_input">
					<select class="wc-enhanced-select" name="wcs_conditions[{{data.index}}][user_roles][]" class="select" multiple>
						<?php foreach ( woo_conditional_shipping_role_options() as $role_id => $name ) { ?>
							<option
								value="<?php echo $role_id; ?>"
								<# if ( data.user_roles && jQuery.inArray( '<?php echo $role_id; ?>', data.user_roles ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo $name; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_postcode_value_input">
					<textarea name="wcs_conditions[{{data.index}}][postcodes]" class="" placeholder="<?php esc_attr_e( 'List 1 postcode per line', 'woocommerce' ); ?>">{{ data.postcodes }}</textarea>

					<div class="description"><?php _e( 'Postcodes containing wildcards (e.g. CB23*) or fully numeric ranges (e.g. <code>90210...99000</code>) are also supported.', 'woo-conditional-shipping' ); ?></div>
				</div>

				<div class="value_input wcs_country_value_input">
					<select class="wc-enhanced-select" name="wcs_conditions[{{data.index}}][countries][]" class="select" multiple>
						<?php foreach ( woo_conditional_shipping_country_options() as $code => $country ) { ?>
							<option
								value="<?php echo $code; ?>"
								<# if ( data.countries && jQuery.inArray( '<?php echo $code; ?>', data.countries ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo $country; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_state_value_input">
					<select class="wc-enhanced-select" name="wcs_conditions[{{data.index}}][states][]" class="select" multiple>
						<?php foreach ( woo_conditional_shipping_state_options() as $country_id => $states ) { ?>
							<optgroup label="<?php echo esc_attr( $states['country'] ); ?>">
								<?php foreach ( $states['states'] as $state_id => $state ) { ?>
									<option
										value="<?php echo "{$country_id}:{$state_id}"; ?>"
										<# if ( data.states && jQuery.inArray( '<?php echo "{$country_id}:{$state_id}"; ?>', data.states ) !== -1 ) { #>
											selected
										<# } #>
									>
										<?php echo $state; ?>
									</option>
								<?php } ?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_product_attrs_input">
					<select class="wc-enhanced-select" name="wcs_conditions[{{data.index}}][product_attrs][]" class="select" multiple>
						<?php foreach ( woo_conditional_product_attr_options() as $taxonomy_id => $attrs ) { ?>
							<optgroup label="<?php echo esc_attr( $attrs['label'] ); ?>">
								<?php foreach ( $attrs['attrs'] as $attr_id => $label ) { ?>
									<option
									value="<?php echo $attr_id; ?>"
									<# if ( data.product_attrs && jQuery.inArray( '<?php echo $attr_id; ?>', data.product_attrs ) !== -1 ) { #>
										selected
										<# } #>
										>
										<?php echo $label; ?>
									</option>
								<?php } ?>
							</optgroup>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_weekdays_value_input">
					<select class="wc-enhanced-select" name="wcs_conditions[{{data.index}}][weekdays][]" class="select" multiple>
						<?php foreach ( woo_conditional_shipping_weekdays_options() as $weekday_id => $weekday ) { ?>
							<option
								value="<?php echo $weekday_id; ?>"
								<# if ( data.weekdays && jQuery.inArray( '<?php echo $weekday_id; ?>', data.weekdays ) !== -1 ) { #>
									selected
								<# } #>
							>
								<?php echo $weekday; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<div class="value_input wcs_time_value_input">
					<select name="wcs_conditions[{{data.index}}][time_hours]" class="select">
						<?php foreach ( woo_conditional_shipping_time_hours_options() as $hours => $label ) { ?>
							<option
								value="<?php echo $hours; ?>"
								<# if ( data.time_hours && '<?php echo $hours; ?>' == data.time_hours ) { #>
									selected
								<# } #>
							>
								<?php echo $label; ?>
							</option>
						<?php } ?>
					</select>
					&nbsp;:&nbsp;
					<select name="wcs_conditions[{{data.index}}][time_mins]" class="select">
						<?php foreach ( woo_conditional_shipping_time_mins_options() as $mins => $label ) { ?>
							<option
								value="<?php echo $mins; ?>"
								<# if ( data.time_mins && '<?php echo $mins; ?>' == data.time_mins ) { #>
									selected
								<# } #>
							>
								<?php echo $label; ?>
							</option>
						<?php } ?>
					</select>
				</div>

				<?php do_action( 'woo_conditional_shipping_ruleset_value_inputs', $ruleset ); ?>
			</fieldset>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wcs_action_row_template">
	<tr valign="top" class="action_row">
		<th class="action_remove">
			<input type="checkbox" class="remove_action">
		</th>
		<th scope="row" class="titledesc">
			<fieldset>
				<select name="wcs_actions[{{data.index}}][type]" class="wcs_action_type_select">
					<?php foreach ( woo_conditional_shipping_actions() as $key => $action ) { ?>
						<option
							value="<?php echo $key; ?>"
							<# if ( data.type == '<?php echo $key; ?>' ) { #>selected<# } #>
						>
							<?php echo $action['title']; ?>
						</option>
					<?php } ?>
				</select>
			</fieldset>
		</th>
		<td class="forminp shipping-methods">
			<select name="wcs_actions[{{data.index}}][shipping_method_ids][]" multiple class="select wc-enhanced-select" placeholder="<?php _e( 'Shipping methods', 'woo-conditional-shipping' ); ?>">
				<?php foreach ( woo_conditional_shipping_get_shipping_method_options() as $zone_id => $zone ) { ?>
					<optgroup label="<?php esc_attr_e( $zone['title'] ); ?>">
						<?php foreach ( $zone['options'] as $instance_id => $method ) { ?>
							<option value="<?php echo $instance_id; ?>" <# if ( data.shipping_method_ids && data.shipping_method_ids.indexOf("<?php echo $instance_id; ?>") !== -1 ) { #>selected<# } #>><?php echo $method['title']; ?></option>
						<?php } ?>
					</optgroup>
				<?php } ?>
			</select>
		</td>
		<td class="forminp values">
			<fieldset class="wcs_action_value_inputs">
				<div class="value_input wcs_price_value_input">
					<input name="wcs_actions[{{data.index}}][price]" type="number" step="any" value="{{ data.price }}" />

					<select name="wcs_actions[{{data.index}}][price_mode]">
						<option value="fixed" <# if ( data.price_mode === "fixed" ) { #>selected<# } #>><?php echo get_woocommerce_currency_symbol(); ?></option>
						<option value="pct" <# if ( data.price_mode === "pct" ) { #>selected<# } #>>% of subtotal</option>
					</select>
				</div>

				<div class="value_input wcs_error_msg_input">
					<textarea name="wcs_actions[{{data.index}}][error_msg]" rows="4" cols="40" placeholder="<?php esc_attr_e( __( 'Custom "no shipping methods available" message', 'woo-conditional-shipping' ) ); ?>">{{ data.error_msg }}</textarea>
				</div>

				<div class="value_input wcs_notice_input">
					<textarea name="wcs_actions[{{data.index}}][notice]" rows="4" cols="40" placeholder="<?php esc_attr_e( __( 'Shipping notice', 'woo-conditional-shipping' ) ); ?>">{{ data.notice }}</textarea>
				</div>
			</fieldset>
		</td>
	</tr>
</script>
