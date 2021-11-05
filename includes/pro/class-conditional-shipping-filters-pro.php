<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Filters_Pro {
	/**
	 * Filter product attributes
	 */
	public static function filter_product_attrs( $condition, $package ) {
		if ( isset( $condition['product_attrs'] ) && ! empty( $condition['product_attrs'] ) ) {
			if ( $condition['operator'] == 'exclusive' ) {
				return self::filter_product_attrs_exclusive( $condition, $package );
			}

			// Flatten array
			$product_attrs = self::get_cart_product_attr_ids();
			$attrs = array();
			foreach ( $product_attrs as $product_attr ) {
				$attrs = array_merge( $attrs, $product_attr );
			}
			$attrs = array_unique( $attrs );

			return ! self::group_comparison( $attrs, $condition['product_attrs'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by exclusive product attribute
	 *
	 * All products must contain at least one of the exclusive product attributes.
	 */
	private static function filter_product_attrs_exclusive( $condition, $package ) {
		if ( isset( $condition['product_attrs'] ) && ! empty( $condition['product_attrs'] ) ) {
			$exclusive_attrs = $condition['product_attrs'];

			$product_attrs = self::get_cart_product_attr_ids();

			foreach ( $product_attrs as $attrs ) {
				$array_intersect = array_intersect( $exclusive_attrs, $attrs );
				if ( empty( $array_intersect ) ) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	public static function filter_time( $condition, $package ) {
		if ( isset( $condition['time_hours'] ) && strlen( strval( $condition['time_hours'] ) ) > 0 ) {
			$hours = intval( $condition['time_hours'] );
			$mins = intval( $condition['time_mins'] );
			$seconds = $hours * 3600 + $mins * 60;

			$current_seconds = intval( date_i18n( 'G' ) ) * 3600 + intval( date_i18n( 'i' ) ) * 60 + intval( date_i18n( 's' ) );

			return ! self::compare_numeric_value( $current_seconds, $seconds, $condition['operator'] );
		}

		return FALSE;
	}

	public static function filter_weekdays( $condition, $package ) {
		$current_weekday = date_i18n( 'N' );

		if ( isset( $condition['weekdays'] ) && ! empty( $condition['weekdays'] ) ) {
			return ! self::is_array_comparison( $current_weekday, $condition['weekdays'], $condition['operator'] );
		}

		return FALSE;
	}

	public static function filter_product_measurement( $condition, $package, $dimensions ) {
		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) && ! empty( $dimensions ) ) {
			$value = self::parse_number( $condition['value'] );

			if ( $condition['product_measurement_mode'] === 'highest' ) {
				$compare_value = max( $dimensions );
			} else if ( $condition['product_measurement_mode'] === 'lowest' ) {
				$compare_value = min( $dimensions );
			} else {
				error_log( "Unknown product measurement mode: " . $condition['product_measurement_mode'] );
			}

			return ! self::compare_numeric_value( $compare_value, $value, $condition['operator'] );
		}

		return FALSE;
	}
	
	public static function filter_product_weight( $condition, $package ) {
		$weights = self::get_product_measurements( $package, 'weights' );

		return self::filter_product_measurement( $condition, $package, $weights );
	}
	
	public static function filter_product_height( $condition, $package ) {
		$heights = self::get_product_measurements( $package, 'heights' );
		
		return self::filter_product_measurement( $condition, $package, $heights );
	}

	public static function filter_product_width( $condition, $package ) {
		$widths = self::get_product_measurements( $package, 'widths' );
		
		return self::filter_product_measurement( $condition, $package, $widths );
	}

	public static function filter_product_length( $condition, $package ) {
		$lengths = self::get_product_measurements( $package, 'lengths' );
		
		return self::filter_product_measurement( $condition, $package, $lengths );
	}

  public static function filter_items( $condition, $package ) {
		$cart_items = self::get_items_count( $condition );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$items = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $cart_items, $items, $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Get order attribute
	 */
	public static function get_order_attr( $attr ) {
		return Woo_Conditional_Shipping_Filters::get_order_attr( $attr );
	}

	/**
	 * Postcode filtering
	 */
	public static function postcode_filtering( $attr, $condition ) {
		$value = self::get_order_attr( $attr );

		// Get also country as it's needed for postcode formatting
		$country_prefix = strpos( $attr, 'billing_' ) !== false ? 'billing_' : 'shipping_';
		$country = self::get_order_attr( $country_prefix . 'country' );

		if ( $value !== NULL ) {
			// Postcode range or wildcard handling
			if ( isset( $condition['postcodes'] ) && ! empty( trim( $condition['postcodes'] ) ) ) {
				// Convert postcodes to cleaned array
				$postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $condition['postcodes'] ) ) ) );

				// Convert postcodes to objects for wc_postcode_location_matcher
				$postcodes_obj = array();
				foreach ( $postcodes as $key => $postcode ) {
					$postcodes_obj[] = (object) array(
						'id' => $key + 1,
						'value' => $postcode,
					);
				}

				// Check if postcode matches
				$matches = wc_postcode_location_matcher( $value, $postcodes_obj, 'id', 'value', strval( $country ) );

				// If there were any matches, postcode passes the condition
				if ( $condition['operator'] === 'is' ) {
					return ! empty( $matches );
				} else if ( $condition['operator'] === 'isnot' ) {
					return empty( $matches );
				}
			}
		}

		return FALSE;
	}

	/**
	 * Filter by shipping postcode
	 */
	public static function filter_shipping_postcode( $condition, $package ) {
		return ! self::postcode_filtering( 'shipping_postcode', $condition );
	}

	/**
	 * Filter by billing postcode
	 */
	public static function filter_billing_postcode( $condition, $package ) {
		return ! self::postcode_filtering( 'billing_postcode', $condition );
	}

	/**
	 * Filter by billing state
	 */
	public static function filter_billing_state( $condition, $package ) {
		if ( isset( $condition['states'] ) && ! empty( $condition['states'] ) ) {
			$country = self::get_order_attr( 'billing_country' );
			$state = self::get_order_attr( 'billing_state' );
			$value = sprintf( '%s:%s', $country, $state );

			return ! self::is_array_comparison( $value, $condition['states'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by shipping state
	 */
	public static function filter_shipping_state( $condition, $package ) {
		if ( isset( $condition['states'] ) && ! empty( $condition['states'] ) ) {
			$country = self::get_order_attr( 'shipping_country' );
			$state = self::get_order_attr( 'shipping_state' );
			$value = sprintf( '%s:%s', $country, $state );

			return ! self::is_array_comparison( $value, $condition['states'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by billing country
	 */
	public static function filter_billing_country( $condition, $package ) {
		if ( isset( $condition['countries'] ) && ! empty( $condition['countries'] ) ) {
			$value = self::get_order_attr( 'billing_country' );

			return ! self::is_array_comparison( $value, $condition['countries'], $condition['operator'] );
		}

		return FALSE;
	}

	/**
	 * Filter by shipping country
	 */
	public static function filter_shipping_country( $condition, $package ) {
		if ( isset( $condition['countries'] ) && ! empty( $condition['countries'] ) ) {
			$value = self::get_order_attr( 'shipping_country' );

			return ! self::is_array_comparison( $value, $condition['countries'], $condition['operator'] );
		}

		return FALSE;
	}
	
	/**
	 * Get cart contents count
	 */
	private static function get_items_count( $condition ) {
		if ( isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
			if ( strpos( $condition['subset_filter'], 'shipping_class_' ) !== false ) {
				return self::get_items_count_for_shipping_class( $condition );
			} 
		}

		return wcs_get_cart_func( 'get_cart_contents_count' );
	}

	/**
	 * Get cart contents count for a shipping class
	 */
	private static function get_items_count_for_shipping_class( $condition ) {
		$count = 0;
		
		$items = Woo_Conditional_Shipping_Filters::get_subset_of_items_by_shipping_class( $condition );
		foreach ( $items as $key => $item ) {
			$count += $item['quantity'];
		}

		return $count;
	}

  public static function filter_shipping_class( $condition, $package ) {
		if ( isset( $condition['shipping_class_ids'] ) && ! empty( $condition['shipping_class_ids'] ) ) {
			$shipping_class_ids = self::get_cart_shipping_class_ids();

			// Cast to integers
			$shipping_class_ids = array_map( 'intval', $shipping_class_ids );
			$condition['shipping_class_ids'] = array_map( 'intval', $condition['shipping_class_ids'] );

			return ! self::group_comparison( $shipping_class_ids, $condition['shipping_class_ids'], $condition['operator'] );
		}

		return FALSE;
  }

  public static function filter_category( $condition, $package ) {
		if ( isset( $condition['category_ids'] ) && ! empty( $condition['category_ids'] ) ) {
			if ( $condition['operator'] == 'exclusive' ) {
				return self::filter_category_exclusive( $condition, $package );
			}

			$cat_ids = self::get_cart_product_cat_ids();

			return ! self::group_comparison( $cat_ids, $condition['category_ids'], $condition['operator'] );
		}

		return FALSE;
  }

	/**
	 * Filter by exclusive category
	 *
	 * All products must contain at least one of the exclusive categories.
	 */
	private static function filter_category_exclusive( $condition, $package ) {
		if ( isset( $condition['category_ids'] ) && ! empty( $condition['category_ids'] ) ) {
			$exclusive_category_ids = $condition['category_ids'];

			foreach ( $package['contents'] as $key => $item ) {
				$cat_ids = woo_conditional_shipping_get_product_cats( $item['product_id'] );

				$array_intersect = array_intersect( $exclusive_category_ids, $cat_ids );
				if ( empty( $array_intersect ) ) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Filter customer logged in / out
	 */
	public static function filter_customer_authenticated( $condition ) {
		switch ( $condition['operator'] ) {
			case 'loggedin':
				return ! is_user_logged_in();
			case 'loggedout':
				return is_user_logged_in();
		}

		error_log( "Invalid operator for customer authenticated" );

		return FALSE;
	}

	/**
	 * Filter customer role
	 */
	public static function filter_customer_role( $condition ) {
		if ( empty( $condition['user_roles'] ) ) {
			return FALSE;
		}

		// User not logged in, should filter out
		if ( ! is_user_logged_in() && $condition['operator'] === 'is' ) {
			return TRUE;
		}

		// User is not authenticated and doesn't has role, thus we can pass for "is not" operator
		if ( ! is_user_logged_in() && $condition['operator'] === 'isnot' ) {
			return FALSE;
		}

		$user = wp_get_current_user();
		$roles = (array) $user->roles;
		$roles = array_values( array_filter( $roles ) ); // Remove empty values just in case

		// Originally this function only supported one role per user. However,
		// some 3rd party plugins might add support for multiple roles per user
		// so we will switch operators to group operators
		if ( $condition['operator'] === 'is' ) {
			$condition['operator'] = 'in';
		} else if ( $condition['operator'] === 'isnot' ) {
			$condition['operator'] = 'notin';
		}

		return ! self::group_comparison( $roles, $condition['user_roles'], $condition['operator'] );
	}

	/**
	 * Filter coupons
	 */
	public static function filter_coupon( $condition ) {
		$cart_coupon_ids = self::get_cart_coupon_ids();

		// Special handling for "All coupons" option
		if ( in_array( '_all', $condition['coupon_ids'] ) ) {
			if ( $condition['operator'] === 'in' ) {
				return count( $cart_coupon_ids ) === 0;
			} else if ( $condition['operator'] === 'notin' ) {
				return count( $cart_coupon_ids ) > 0;
			}
		}

		// Special handling for "Free shipping coupons" option
		if ( in_array( '_free_shipping', $condition['coupon_ids'] ) ) {
			// Check if there is a free shipping coupon
			$has_free_shipping_coupon = false;
			foreach ( $cart_coupon_ids as $coupon_id ) {
				$coupon = new WC_Coupon( $coupon_id );

				if ( $coupon && is_a( $coupon, 'WC_Coupon' ) && $coupon->get_free_shipping() ) {
					$has_free_shipping_coupon = true;
					break;
				}
			}

			if ( $condition['operator'] === 'in' ) {
				return ! $has_free_shipping_coupon;
			} else if ( $condition['operator'] === 'notin' ) {
				return $has_free_shipping_coupon;
			}
		}

		if ( empty( $condition['coupon_ids'] ) ) {
			return FALSE;
		}

		return ! self::group_comparison( $cart_coupon_ids, $condition['coupon_ids'], $condition['operator'] );
	}

	/**
	 * Get product attribute IDs in the order
	 */
	private static function get_cart_product_attr_ids() {
		$attr_ids = array();

		foreach ( wcs_get_cart_func( 'get_cart' ) as $key => $item ) {
			if ( isset( $item['data'] ) ) {
				$product = $item['data'];
			} else {
				continue;
			}

			$product_attr_ids = [];

			if ( $product->get_type() === 'simple' ) {
				$attrs = $product->get_attributes();

				foreach ( $attrs as $attr ) {
					if ( ! method_exists( $attr, 'get_options' ) ) {
						continue;
					}

					$options = $attr->get_options();

					foreach ( $options as $option ) {
						$term = get_term( $option );

						if ( $term ) {
							$attr_id = sprintf( '%s:%s', $attr->get_name(), $term->slug );
							$product_attr_ids[$attr_id] = true;
						}
					}
				}
			} else if ( $product->get_type() === 'variation' ) {
				$attr_data = (array) $item['variation'];

				foreach ( $attr_data as $key => $value ) {
					if ( strpos( $key, 'attribute_' ) !== false ) {
						$key = str_replace( 'attribute_', '', $key );

						$attr_id = sprintf( '%s:%s', $key, $value );
						$product_attr_ids[$attr_id] = true;
					}
				}
			}

			$attr_ids[] = array_keys( $product_attr_ids );
		}

		return $attr_ids;
	}

	/**
	 * Get product category IDs in the order
	 */
	private static function get_cart_product_cat_ids() {
		$products = self::get_cart_products();
		$cat_ids = array();

		foreach ( $products as $product ) {
			$product_cat_ids = woo_conditional_shipping_get_product_cats( $product->get_id() );
			$cat_ids = array_merge( $cat_ids, $product_cat_ids );
		}

		return array_unique( $cat_ids );
	}

	/**
	 * Get coupon IDs used in the cart
	 *
	 * Coupons are saved by post ID while WooCommerce provides cart coupons by codes.
	 * We need to transform codes into post IDs.
	 */
	private static function get_cart_coupon_ids() {
		$codes = wcs_get_cart_func( 'get_applied_coupons' );
		$ids = array();

		foreach ( $codes as $code ) {
			$id = wc_get_coupon_id_by_code( $code );
			if ( $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Get shipping class IDs in the cart
	 */
 	private static function get_cart_shipping_class_ids() {
 		$products = self::get_cart_products();
 		$shipping_class_ids = array();

 		foreach ( $products as $product ) {
			$shipping_class_id = Woo_Conditional_Shipping_Filters::get_product_shipping_class_id( $product );

 			$shipping_class_ids[$shipping_class_id] = TRUE;
 		}

 		return array_keys( $shipping_class_ids );
 	}

	/**
	 * Get product IDs in the cart
	 */
	private static function get_cart_products() {
		$products = array();

		foreach ( wcs_get_cart_func( 'get_cart' ) as $key => $item ) {
			if ( isset( $item['data'] ) ) {
				if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
					$products[$item['variation_id']] = $item['data'];
				} else if ( isset( $item['product_id'] ) && ! empty( $item['product_id'] ) ) {
					$products[$item['product_id']] = $item['data'];
				}
			}
		}

		return $products;
	}

	/**
	 * Get product measurements (weight, height, length, width)
	 */
	private static function get_product_measurements( $package, $dimension ) {
		$weights = array();
		$heights = array();
		$lengths = array();
		$widths = array();

		foreach ( $package['contents'] as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() ) {
				continue;
			}

			$weights[] = (float) $product->get_weight();
			$heights[] = (float) $product->get_height();
			$widths[] = (float) $product->get_width();
			$lengths[] = (float) $product->get_length();
		}

		switch ( $dimension ) {
			case 'weights':
				return $weights;
			case 'heights':
				return $heights;
			case 'widths':
				return $widths;
			case 'lengths':
				return $lengths;
		}

		error_log( "Unknown dimension requested in get_product_measurements(): " . $dimension );
	}

	/**
	 * Parse string number into float
	 */
	private static function parse_number($number) {
		$number = str_replace( ',', '.', $number );

		if ( is_numeric( $number ) ) {
			return floatval( $number );
		}

		return FALSE;
	}

	/**
	 * Compare value with given operator
	 */
	private static function compare_numeric_value( $a, $b, $operator ) {
		switch ( $operator ) {
			case 'gt':
				return $a > $b;
			case 'gte':
				return $a >= $b;
			case 'lt':
				return $a < $b;
			case 'lte':
				return $a <= $b;
		}

		error_log( "Invalid operator given" );

		return NULL;
	}

	/**
	 * Check inclusiveness or exclusiveness in an array
	 */
	public static function group_comparison( $a, $b, $operator ) {
		$a = array_unique( $a );
		$b = array_unique( $b );

		switch ( $operator ) {
			case 'in':
				return count( array_intersect( $a, $b ) ) > 0;
			case 'notin':
				return count( array_intersect( $a, $b ) ) == 0;
			case 'exclusive':
				return count( array_diff( $a, $b ) ) == 0;
			case 'allin':
				return count( array_diff( $b, $a ) ) == 0;
		}

		error_log( "Invalid operator given in group comparison" );

		return NULL;
	}

	/**
	 * Check is / is not in an array
	 */
	private static function is_array_comparison( $needle, $haystack, $operator ) {
		if ( $operator == 'is' ) {
			return in_array( $needle, $haystack );
		} else if ( $operator == 'isnot' ) {
			return ! in_array( $needle, $haystack );
		}

		error_log( "Invalid operator given in array comparison" );

		return NULL;
	}
}
