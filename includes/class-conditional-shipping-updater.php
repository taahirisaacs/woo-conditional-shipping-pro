<?php

/**
 * Plugin update functions
 */
if ( ! class_exists( 'Woo_Conditional_Shipping_Updater_Pro' ) ) {
class Woo_Conditional_Shipping_Updater_Pro {
  public $db_version;
  public $db_version_option;
  public $version;

  public function __construct() {
    if ( class_exists( 'Woo_Conditional_Shipping_Pro' ) ) {
      $this->db_version_option = 'woo_conditional_shipping_pro_version';
      $this->version = WOO_CONDITIONAL_SHIPPING_PRO_VERSION;
    } else {
      $this->db_version_option = 'woo_conditional_shipping_version';
      $this->version = WOO_CONDITIONAL_SHIPPING_VERSION;
    }

    $this->db_version = get_option( $this->db_version_option, '0.0.0' );
  }

  public function run_updates() {
    if ( version_compare( '1.1.0', $this->db_version ) >= 1 ) {
      $this->run_110();
    }

    if ( version_compare( '2.0.0', $this->db_version ) >= 1 ) {
      $this->run_200();
    }

    // Set version to the latest version
    if ( $this->db_version != $this->version ) {
      update_option( $this->db_version_option, $this->version );
    }
  }

  /**
   * Run 2.0.0 update
   *
   * In 2.0.0 conditions were moved from WordPress options table to custom post types.
   * UI was also refactored to make stronger base for further development.
   */
  private function run_200() {
    // Check if new conditions has been already mapped (e.g. Free version before Pro)
    $already_done = get_option( 'wcs_updated_200', 'no' );
    if ( $already_done === 'yes' ) {
      return;
    }

    $conditions = get_option( 'wcs_conditions', array() );

    // Create new ruleset for all conditions
    foreach ( $conditions as $instance_id => $conditions ) {
      // Skip if conditions are empty
      if ( empty( $conditions ) ) {
        continue;
      }

      // Get title
      $method = WC_Shipping_Zones::get_shipping_method( $instance_id );
      $method_title = false;
      if ( $method && is_object( $method ) && method_exists( $method, 'get_title' ) ) {
        $method_title = $method->get_title();
      }

      if ( ! $method_title ) {
        $method_title = sprintf( __( 'Disable shipping method %s', 'woo-conditional-shipping' ), $instance_id );
      }

      $post_id = wp_insert_post( array(
        'post_type' => 'wcs_ruleset',
        'post_title' => $method_title,
        'post_status' => 'publish',
      ) );

      // Add conditions
      update_post_meta( $post_id, '_wcs_conditions', $conditions );

      // Add enable actions (pre 2.0.0 all actions were enable actions)
      $actions = array(
        array(
          'type' => 'enable_shipping_methods',
          'shipping_method_ids' => array( strval( $instance_id ) ),
        ),
      );
      update_post_meta( $post_id, '_wcs_actions', $actions );
    }

    update_option( 'wcs_updated_200', 'yes' );
  }

  /**
   * Run 1.1.0 update
   *
   * In 1.1.0 conditions were moved from instance settings to the WordPress options table.
   * Conditions were also refactored for simpler user interface.
   */
  private function run_110() {
    // Check if new conditions has been already mapped (e.g. Free version before Pro)
    $already_done = get_option( 'wcs_conditions', 'no' );
    if ( $already_done != 'no' ) {
      return;
    }

    $conditions = array();

    // Get all shipping method instances
    $data_store = WC_Data_Store::load( 'shipping-zone' );
		$zones = $data_store->get_zones();
    $zones[] = (object) array(
      'zone_id' => 0,
    );
    foreach ( $zones as $zone ) {
      $methods = $data_store->get_methods( $zone->zone_id, FALSE );
      foreach ( $methods as $method ) {
        $method = WC_Shipping_Zones::get_shipping_method( $method->instance_id );

        if ( $method && is_object( $method ) ) {
          if ( ( ! isset( $method->instance_settings ) || empty( $method->instance_settings ) ) && method_exists( $method, 'init_instance_settings' ) ) {
            $method->init_instance_settings();
          }

          // Fix for WooCommerce Services
          if ( strpos( $method->id, 'wc_services' ) !== FALSE && empty( $method->instance_settings ) && ! empty( $method->instance_id ) ) {
            $option_key = $method->plugin_id . $method->id . '_' . $method->instance_id . '_settings';
            $method->instance_settings = get_option( $option_key, array() );
          }

          if ( isset( $method->instance_settings['wcs_conditions'] ) && is_array( $method->instance_settings['wcs_conditions'] ) ) {
            $conditions[$method->instance_id] = $this->map_conditions_110( $method->instance_settings['wcs_conditions'] );
          }
        }
      }
    }

    update_option( 'wcs_conditions', $conditions );
  }

  private function map_conditions_110( $conditions ) {
    $new_conditions = array();

    foreach ( $conditions as $condition ) {
      $mappings = $this->mappings_110();
      if ( isset( $mappings[$condition['type']] ) ) {
        $new_condition = $mappings[$condition['type']];
        $new_condition['shipping_class_ids'] = $condition['shipping_class_ids'];
        $new_condition['category_ids'] = $condition['category_ids'];
        $new_condition['product_ids'] = $condition['product_ids'];
        $new_condition['value'] = $condition['value'];

        $new_conditions[] = $new_condition;
      }
    }

    return $new_conditions;
  }

  private function mappings_110() {
    return array(
      'min_weight' => array(
        'type' => 'weight',
        'operator' => 'gte',
      ),
      'max_weight' => array(
        'type' => 'weight',
        'operator' => 'lte',
      ),
      'max_height' => array(
        'type' => 'height_total',
        'operator' => 'lte',
      ),
      'max_length' => array(
        'type' => 'length_total',
        'operator' => 'lte',
      ),
      'max_width' => array(
        'type' => 'width_total',
        'operator' => 'lte',
      ),
      'min_volume' => array(
        'type' => 'volume_total',
        'operator' => 'gte',
      ),
      'max_volume' => array(
        'type' => 'volume_total',
        'operator' => 'lte',
      ),
      'min_subtotal' => array(
        'type' => 'subtotal',
        'operator' => 'gte',
      ),
      'max_subtotal' => array(
        'type' => 'subtotal',
        'operator' => 'lte',
      ),
      'min_items' => array(
        'type' => 'items',
        'operator' => 'gte',
      ),
      'max_items' => array(
        'type' => 'items',
        'operator' => 'lte',
      ),
      'product_include' => array(
        'type' => 'products',
        'operator' => 'in',
      ),
      'product_exclude' => array(
        'type' => 'products',
        'operator' => 'notin',
      ),
      'product_exclusive' => array(
        'type' => 'products',
        'operator' => 'exclusive',
      ),
      'shipping_class_include' => array(
        'type' => 'shipping_class',
        'operator' => 'in',
      ),
      'shipping_class_exclude' => array(
        'type' => 'shipping_class',
        'operator' => 'notin',
      ),
      'shipping_class_exclusive' => array(
        'type' => 'shipping_class',
        'operator' => 'exclusive',
      ),
      'shipping_class_all_present' => array(
        'type' => 'shipping_class',
        'operator' => 'allin',
      ),
      'category_include' => array(
        'type' => 'category',
        'operator' => 'in',
      ),
      'category_exclude' => array(
        'type' => 'category',
        'operator' => 'notin',
      ),
      'category_exclusive' => array(
        'type' => 'category',
        'operator' => 'exclusive',
      ),
      'category_all_present' => array(
        'type' => 'category',
        'operator' => 'allin',
      ),
    );
  }
}

add_action( 'init', 'woo_conditional_shipping_updater_pro', 1000 );
function woo_conditional_shipping_updater_pro() {
  // WooCommerce not activated, abort
  if ( ! defined( 'WC_VERSION' ) ) {
    return;
  }

  $updater = new Woo_Conditional_Shipping_Updater_Pro();
  $updater->run_updates();
}
}
