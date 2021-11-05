<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get rulesets
 */
function woo_conditional_shipping_get_rulesets( $only_enabled = false ) {
	$args = array(
		'post_status' => array( 'publish' ),
		'post_type' => 'wcs_ruleset',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
	);

  $posts = get_posts( $args );
  
  $rulesets = array();
  foreach ( $posts as $post ) {
    $ruleset = new Woo_Conditional_Shipping_Ruleset( $post->ID );

    if ( ! $only_enabled || $ruleset->get_enabled() ) {
      $rulesets[] = $ruleset;
    }
  }

  return $rulesets;
}

/**
 * Get a list of operators
 */
function woo_conditional_shipping_operators() {
  return array(
    'gt' => __( 'greater than', 'woo-conditional-shipping' ),
    'gte' => __( 'greater than or equal', 'woo-conditional-shipping' ),
    'lt' => __( 'less than', 'woo-conditional-shipping' ),
    'lte' => __( 'less than or equal', 'woo-conditional-shipping' ),
    'in' => __( 'includes', 'woo-conditional-shipping' ),
    'exclusive' => __( 'includes (exclusive)', 'woo-conditional-shipping' ),
    'notin' => __( 'excludes', 'woo-conditional-shipping' ),
    'allin' => __( 'all present', 'woo-conditional-shipping' ),
    'is' => __( 'is', 'woo-conditional-shipping' ),
    'isnot' => __( 'is not', 'woo-conditional-shipping' ),
    'exists' => __( 'is not empty', 'woo-conditional-shipping' ),
    'notexists' => __( 'is empty', 'woo-conditional-shipping' ),
    'contains' => __( 'contains', 'woo-conditional-shipping' ),
    'loggedin' => __( 'logged in', 'woo-conditional-shipping' ),
    'loggedout' => __( 'logged out', 'woo-conditional-shipping' ),
  );
}

/**
 * Get a list of subset filters
 */
function woo_conditional_shipping_subset_filters() {
  return apply_filters( 'woo_conditional_shipping_subset_filters', array() );
}

/**
 * Get a list of filter groups
 */
function woo_conditional_shipping_filter_groups() {
  return apply_filters( 'woo_conditional_shipping_filters', array(
    'cart' => array(
      'title' => __( 'Cart', 'woo-conditional-shipping' ),
      'filters' => array(
        'subtotal' => array(
          'title' => __( 'Subtotal', 'woo-conditional-shipping' ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'products' => array(
          'title' => __( 'Products', 'woo-conditional-shipping' ),
          'operators' => array( 'in', 'notin', 'exclusive', 'allin' ),
        ),
      )
    ),
    'package_measurements' => array(
      'title' => __( 'Package Measurements', 'woo-conditional-shipping' ),
      'filters' => array(
        'weight' => array(
          'title' => sprintf( __( 'Total Weight (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_weight_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'height_total' => array(
          'title' => sprintf( __( 'Total Height (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'length_total' => array(
          'title' => sprintf( __( 'Total Length (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'width_total' => array(
          'title' => sprintf( __( 'Total Width (%s)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
        'volume' => array(
          'title' => sprintf( __( 'Total Volume (%s&sup3;)', 'woo-conditional-shipping' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => array( 'gt', 'gte', 'lt', 'lte' ),
        ),
      )
    ),
  ) );
}

/**
 * Get a list of filters
 */
function woo_conditional_shipping_filters() {
  $groups = woo_conditional_shipping_filter_groups();

  $filters = array();
  foreach ( $groups as $group ) {
    foreach ( $group['filters'] as $key => $filter ) {
      $filters[$key] = $filter;
    }
  }

  return $filters;
}

/**
 * Get a list of actions
 */
function woo_conditional_shipping_actions() {
  return apply_filters( 'woo_conditional_shipping_actions', array(
    'disable_shipping_methods' => array(
      'title' => __( 'Disable shipping methods', 'woo-conditional-shipping' ),
    ),
    'enable_shipping_methods' => array(
      'title' => __( 'Enable shipping methods', 'woo-conditional-shipping' ),
    ),
  ) );
}

/**
 * Country options
 */
function woo_conditional_shipping_country_options() {
  $countries_obj = new WC_Countries();

  return $countries_obj->get_countries();
}

/**
 * State options
 */
function woo_conditional_shipping_state_options() {
  $countries_obj = new WC_Countries();
  $countries = $countries_obj->get_countries();
  $states = array_filter( $countries_obj->get_states() );

  $options = [];

  foreach ( $states as $country_id => $state_list ) {
    $options[$country_id] = [
      'states' => $state_list,
      'country' => $countries[$country_id],
    ];
  }

  // Move US as first as it is the most commonly used
  $us = $options['US'];
  unset( $options['US'] );
  $options = ['US' => $us] + $options;

  return $options;
}

/**
 * Get shipping method options
 */
function woo_conditional_shipping_get_shipping_method_options() {
  $shipping_zones = WC_Shipping_Zones::get_zones();
  $shipping_zones[] = new WC_Shipping_Zone( 0 );

  $zones_count = count( $shipping_zones );

  $options = array();

  foreach ( $shipping_zones as $shipping_zone ) {
    if ( is_array( $shipping_zone ) && isset( $shipping_zone['zone_id'] ) ) {
      $shipping_zone = WC_Shipping_Zones::get_zone( $shipping_zone['zone_id'] );
    } else if ( ! is_object( $shipping_zone ) ) {
      // Skip
      continue;
    }

    $zone_id = $shipping_zone->get_id();
    $options[$zone_id] = array(
      'title' => $shipping_zone->get_zone_name(),
      'options' => array(),
    );

    foreach ( $shipping_zone->get_shipping_methods() as $instance_id => $shipping_method ) {
      if ( $zones_count > 1 ) {
        $title = sprintf( '%s (%s)', $shipping_method->title, $shipping_zone->get_zone_name() );
      } else {
        $title = $shipping_method->title;
      }

      $options[$zone_id]['options'][$instance_id] = array(
        'title' => $title,
      );
    }
  }

  // Remove zones with no shipping methods
  $options = array_filter( $options, function( $option ) {
    return ! empty( $option['options'] );
  } );

  $options = apply_filters( 'woo_conditional_shipping_method_options', $options );

  return $options;
}

/**
 * Get product attribute options
 */
function woo_conditional_product_attr_options() {
  $options = array();

  $taxonomies = wc_get_attribute_taxonomies();

  foreach ( $taxonomies as $key => $taxonomy ) {
    $options[$taxonomy->attribute_id] = array(
      'label' => $taxonomy->attribute_label,
      'attrs' => array(),
    );

    $taxonomy_id = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
    if ( taxonomy_exists( $taxonomy_id ) ) {
      $terms = get_terms( $taxonomy_id, 'hide_empty=0' );

      foreach ( $terms as $term ) {
        $attribute_id = sprintf( 'pa_%s:%s', $taxonomy->attribute_name, $term->slug );
        $options[$taxonomy->attribute_id]['attrs'][$attribute_id] = $term->name;
      }
    }
  }

  return $options;
}

/**
 * Get shipping class options
 */
function woo_conditional_shipping_get_shipping_class_options() {
  $shipping_classes = WC()->shipping->get_shipping_classes();
  $shipping_class_options = array();
  foreach ( $shipping_classes as $shipping_class ) {
    $shipping_class_options[$shipping_class->term_id] = $shipping_class->name;
  }

  return $shipping_class_options;
}

/**
 * Get category options
 */
function woo_conditional_shipping_get_category_options() {
  $categories = get_terms( 'product_cat', array(
    'hide_empty' => false,
    'suppress_filter' => true,
  ) );

  $sorted = array();
  woo_conditional_shipping_sort_terms_hierarchicaly( $categories, $sorted );

  // Flatten hierarchy
  $options = array();
  woo_conditional_shipping_flatten_terms( $options, $sorted );

  return $options;
}

/**
 * Output term tree into a select field options
 */
function woo_conditional_shipping_flatten_terms( &$options, $cats, $depth = 0 ) {
  foreach ( $cats as $cat ) {
    if ( $depth > 0 ) {
      $prefix = str_repeat( ' - ', $depth );
      $options[$cat->term_id] = "{$prefix} {$cat->name}";
    } else {
      $options[$cat->term_id] = "{$cat->name}";
    }

    if ( isset( $cat->children ) && ! empty( $cat->children ) ) {
      woo_conditional_shipping_flatten_terms( $options, $cat->children, $depth + 1 );
    }
  }
}

/**
 * Sort categories hierarchically
 */
function woo_conditional_shipping_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
  foreach ( $cats as $i => $cat ) {
    if ( $cat->parent == $parentId ) {
      $into[$cat->term_id] = $cat;
      unset( $cats[$i] );
    }
  }

  foreach ( $into as $topCat ) {
    $topCat->children = array();
    woo_conditional_shipping_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
  }
}

/**
 * Get coupon options
 */
function woo_conditional_shipping_get_coupon_options() {
  $args = array(
    'posts_per_page' => 100, // Only get 100 latest coupons for performance reasons
    'orderby' => 'ID',
    'order' => 'desc',
    'post_type' => 'shop_coupon',
    'post_status' => 'publish',
  );

  $coupons = get_posts( $args );

  $options = array(
    '_all' => __( '- All coupons -', 'woo-conditional-shipping' ),
    '_free_shipping' => __( '- Free shipping coupons -', 'woo-conditional-shipping' ),
  );
  foreach ( $coupons as $coupon ) {
    $options[$coupon->ID] = $coupon->post_title;
  }

  // Order by code / title
  asort( $options );

  return $options;
}

/**
 * Load all roles to be used in a select field
 */
function woo_conditional_shipping_role_options() {
  global $wp_roles;
  
  $options = array();

  if ( is_a( $wp_roles, 'WP_Roles' ) && isset( $wp_roles->roles ) ) {
    $roles = $wp_roles->roles;

    foreach ( $roles as $role => $details ) {
      $name = translate_user_role( $details['name'] );
      $options[$role] = $name;
    }
  }

  return $options;
}

/**
 * Options for weekday filter
 */
function woo_conditional_shipping_weekdays_options() {
  $options = array();

  for ( $i = 0; $i < 7; $i++ ) {
    $timestamp = strtotime( 'monday' ) + $i * 86400;

    $options[$i + 1] = date_i18n( 'l', $timestamp );
  }

  return $options;
}

/**
 * Options for time hours filter
 */
function woo_conditional_shipping_time_hours_options() {
  $options = array();

  for ( $i = 0; $i < 24; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 3600;

    $options[$i] = date_i18n( 'H', $timestamp );
  }

  return $options;
}

/**
 * Options for time minutes filter
 */
function woo_conditional_shipping_time_mins_options() {
  $options = array();

  for ( $i = 0; $i < 60; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 60;

    $options[$i] = date_i18n( 'i', $timestamp );
  }

  return $options;
}

/**
 * Get shipping method title by instance ID
 */
function woo_conditional_shipping_get_method_title( $instance_id ) {
  // Simple caching mechanism as this can take quite a while
  static $options = [];
  if ( empty( $options ) ) {
    $options = woo_conditional_shipping_get_shipping_method_options();
  }

  foreach ( $options as $zone_id => $data ) {
    foreach ( $data['options'] as $id => $option ) {
      if ( $instance_id == $id ) {
        return $option['title'];
      }
    }
  }

  return $instance_id;
}

/**
 * Get shipping zone ULR
 */
function woo_conditional_shipping_get_zone_url( $zone_id ) {
  return add_query_arg( array(
    'page' => 'wc-settings',
    'tab' => 'shipping',
    'zone_id' => $zone_id,
  ), admin_url( 'admin.php' ) );
}

/**
 * Get shipping method instance
 */
function woo_conditional_shipping_method_get_instance( $instance_id ) {
  if ( ! ctype_digit( strval( $instance_id ) ) ) {
    return null;
  }

  global $wpdb;
  $results = $wpdb->get_results( $wpdb->prepare( "SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1;", $instance_id ) );

  if ( count( $results ) <> 1 ) {
    return null;
  }

  return reset( $results );
}

/**
 * Get action title by action ID
 */
function woo_conditional_shipping_action_title( $action_id ) {
  $actions = woo_conditional_shipping_actions();

  if ( isset( $actions[$action_id] ) ) {
    return $actions[$action_id]['title'];
  }

  return __( 'N/A', 'woo-conditional-shipping' );
}

/**
 * Format ruleset IDs into a list of links
 */
function woo_conditional_shipping_format_ruleset_ids( $ids ) {
  $items = array();

  foreach ( $ids as $id ) {
    $ruleset = new Woo_Conditional_Shipping_Ruleset( $id );

    if ( $ruleset->get_post() ) {
      $items[] = sprintf( '<a href="%s" target="_blank">%s</a>', $ruleset->get_admin_edit_url(), $ruleset->get_title() );
    }
  }

  return implode( ', ', $items );
}

/**
 * Get ruleset admin edit URL
 */
function wcs_get_ruleset_admin_url( $ruleset_id ) {
  $url = add_query_arg( array(
    'ruleset_id' => $ruleset_id,
  ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

  return $url;
}

/**
 * Get product categories
 */
function woo_conditional_shipping_get_product_cats( $product_id ) {
  $cat_ids = array();

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $cat_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product categories
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_cat' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $cat_ids[$term->term_id] = true;
        }
      }
    }

    // Finally add all parent terms
    if ( apply_filters( 'woo_conditional_shipping_incl_parent_cats', true ) ) {
      foreach ( array_keys( $cat_ids ) as $term_id ) {
        $ancestors = (array) get_ancestors( $term_id, 'product_cat', 'taxonomy' );

        foreach ( $ancestors as $ancestor_id ) {
          $cat_ids[$ancestor_id] = true;
        }
      }
    }
  }

  return array_keys( $cat_ids );
}

/**
 * Get cart function
 * 
 * In some cases cart is not always available so we cannot trust WC()->cart
 * to exist. If cart doesn't exist, return sensible default value
 */
function wcs_get_cart_func( $func = 'get_cart' ) {
  $cart = WC()->cart;
  $default = false;

  switch ( $func ) {
    case 'get_cart':
    case 'get_applied_coupons':
      $default = array();
      break;
    case 'display_prices_including_tax':
      $default = false;
      break;
    case 'get_displayed_subtotal':
    case 'get_discount_total':
    case 'get_discount_tax':
    case 'get_cart_contents_count':
      $default = 0;
      break;
  }

  return $cart ? call_user_func( [$cart, $func] ) : $default;
}
