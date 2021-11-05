<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Pro_Functions {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woo_conditional_shipping_filters', array( $this, 'register_pro_filters' ), 10, 1 );
		add_filter( 'woo_conditional_shipping_actions', array( $this, 'register_pro_actions' ), 10, 1 );
		add_filter( 'woo_conditional_shipping_subset_filters', array( $this, 'register_pro_subset_filters' ), 10, 1 );
	}

	/**
	 * Register Pro subset filters
	 */
	public function register_pro_subset_filters( $filters ) {
		$non_added = true;

		$pro_filters = array(
			'' => __( 'of all products', 'woo-conditional-shipping' ),
			'shipping_class' => array(
				'title' => __( 'of products in a shipping class', 'woo-conditional-shipping' ),
				'options' => array(),
			),
			'shipping_class_not' => array(
				'title' => __( 'of products NOT in a shipping class', 'woo-conditional-shipping' ),
				'options' => array(),
			)
		);

		// Add shipping classes
		foreach ( woo_conditional_shipping_get_shipping_class_options() as $key => $shipping_class ) {
			$filter_key = sprintf( 'shipping_class_%s', strval( $key ) );
			$pro_filters['shipping_class']['options'][$filter_key] = $shipping_class;
			
			
			$filter_key = sprintf( 'shipping_class_not_%s', strval( $key ) );
			$pro_filters['shipping_class_not']['options'][$filter_key] = $shipping_class;

			$non_added = false;
		}

		// Return nothing if no additional filters were added (in this case shipping class filters)
		if ( $non_added ) {
			return $filters;
		}

		return array_merge( $filters, $pro_filters );
	}

	/**
	 * Register Pro actions
	 */
	public function register_pro_actions( $actions ) {
		$actions['set_price'] = array(
			'title' => __( 'Set shipping method price', 'woo-conditional-shipping' ),
		);

		$actions['increase_price'] = array(
			'title' => __( 'Increase shipping method price', 'woo-conditional-shipping' ),
		);

		$actions['decrease_price'] = array(
			'title' => __( 'Decrease shipping method price', 'woo-conditional-shipping' ),
		);

		$actions['custom_error_msg'] = array(
			'title' => __( 'Set custom no shipping message', 'woo-conditional-shipping' ),
		);

		$actions['shipping_notice'] = array(
			'title' => __( 'Set shipping notice', 'woo-conditional-shipping' ),
		);

		return $actions;
	}

	/**
	 * Register Pro filters
	 */
	public function register_pro_filters( $filters ) {
		$cart_filters = array(
			'items' => array(
				'title' => __( 'Number of Items', 'woo-conditional-shipping' ),
				'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
			),
			'shipping_class' => array(
				'title' => __( 'Shipping Classes', 'woo-conditional-shipping' ),
				'operators' => array( 'in', 'notin', 'exclusive', 'allin' ),
			),
			'category' => array(
				'title' => __( 'Categories', 'woo-conditional-shipping' ),
				'operators' => array( 'in', 'notin', 'exclusive', 'allin' ),
			),
			'product_attrs' => array(
				'title' => __( 'Product Attributes', 'woo-conditional-shipping' ),
				'operators' => array( 'in', 'notin', 'exclusive' ),
			),
			'coupon' => array(
				'title' => __( 'Coupons', 'woo-conditional-shipping' ),
				'operators' => array( 'in', 'notin' ),
			),
		);

		$filters['cart']['filters'] = array_merge( $filters['cart']['filters'], $cart_filters );

		$product_measurement_filters = array(
      'title' => __( 'Product Measurements', 'woo-conditional-shipping' ),
      'filters' => array(
        'product_weight' => array(
          'title' => sprintf( __( 'Weight (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_weight_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'product_height' => array(
          'title' => sprintf( __( 'Height (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'product_length' => array(
          'title' => sprintf( __( 'Length (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'product_width' => array(
          'title' => sprintf( __( 'Width (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
      )
		);
		
		$filters['product_measurements'] = $product_measurement_filters;

    $customer_filters = array(
      'title' => __( 'Customer', 'woo-conditional-shipping' ),
      'filters' => array(
        'customer_authenticated' => array(
          'title' => __( 'Logged in / out', 'woo-conditional-shipping' ),
          'operators' => array( 'loggedin', 'loggedout' ),
        ),
        'customer_role' => array(
          'title' => __( 'Role', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
        ),
      ),
		);
		
		$filters['customer'] = $customer_filters;

		$billing_address_filters = array(
      'title' => __( 'Billing Address', 'woo-conditional-shipping' ),
      'filters' => array(
				'billing_postcode' => array(
          'title' => __( 'Postcode (billing)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
				),
				'billing_state' => array(
          'title' => __( 'State (billing)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'billing_country' => array(
          'title' => __( 'Country (billing)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
        ),
      ),
		);

		$filters['billing_address'] = $billing_address_filters;

		$shipping_address_filters = array(
      'title' => __( 'Shipping Address', 'woo-conditional-shipping' ),
      'filters' => array(
				'shipping_postcode' => array(
          'title' => __( 'Postcode (shipping)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
				),
				'shipping_state' => array(
          'title' => __( 'State (shipping)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
        ),
        'shipping_country' => array(
          'title' => __( 'Country (shipping)', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
        ),
      ),
		);

		$filters['shipping_address'] = $shipping_address_filters;

		$misc_filters = array(
      'title' => __( 'Misc', 'woo-conditional-shipping' ),
      'filters' => array(
        'weekdays' => array(
          'title' => __( 'Weekday', 'woo-conditional-shipping' ),
          'operators' => array( 'is', 'isnot' ),
				),
				'time' => array(
          'title' => __( 'Time', 'woo-conditional-shipping' ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
      )
		);

		$filters['misc'] = $misc_filters;

		return $filters;
	}
}
