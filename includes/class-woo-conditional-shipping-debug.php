<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Debug {
  private static $instance = null;

  private $blown_debug = false;
  private $product_attrs = [];
  private $customer_roles = [];
  private $states = [];
  private $countries = [];
  private $weekdays = [];
  private $hours = [];
  private $mins = [];
  private $shipping_methods = [];

  /**
   * Constructor
   */
  public function __construct() {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // Output debug information
    add_action( 'woocommerce_before_checkout_form', [ $this, 'output_debug_checkout' ], 10, 0 );

    // Add debug info to fragments
    add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'debug_fragment' ], 10, 1 );

    // Blow cache at every page load so we can get fresh shipping info
    WC_Cache_Helper::get_transient_version( 'shipping', true );

    // Reset debug info when starting to calculate shipping
    add_action( 'woocommerce_before_get_rates_for_package', function() {
      if ( ! $this->blown_debug ) {
        WC()->session->set( 'wcs_debug', [] );
        $this->blown_debug = true;
      }
    }, 0, 0 );
  }

  /**
   * Get instance
   */
  public static function instance() {
    if ( self::$instance == null ) {
      self::$instance = new Woo_Conditional_Shipping_Debug();
    }
 
    return self::$instance;
  }

  /**
   * Get debug mode status
   */
  public function is_enabled() {
    return (bool) get_option( 'wcs_debug_mode', false );
  }

  /**
   * Add debug info to fragments
   */
  public function debug_fragment( $fragments ) {
    $fragments['#wcs-debug'] = $this->output_debug_checkout( false );

    return $fragments;
  }

  /**
   * Output debug information
   */
  public function output_debug_checkout( $echo = true ) {
    $debug = (array) WC()->session->get( 'wcs_debug', [] );

    ob_start();

    include 'frontend/views/debug.html.php';

    $contents = ob_get_clean();

    if ( $echo ) {
      echo $contents;
    } else {
      return $contents;
    }
  }

  /**
   * Get debug information for ruleset
   */
  public function get_debug_info( $ruleset_id ) {
    $debug = (array) WC()->session->get( 'wcs_debug', [] );

    if ( ! isset( $debug[$ruleset_id] ) ) {
      $debug[$ruleset_id] = [
        'conditions' => [],
        'actions' => [],
        'ruleset_id' => $ruleset_id,
        'ruleset_title' => get_the_title( $ruleset_id ),
      ];
    }

    return $debug;
  }

  /**
   * Save debug information for ruleset
   */
  public function save_debug_info( $debug ) {
    WC()->session->set( 'wcs_debug', $debug );
  }

  /**
   * Add condition result
   */
  public function add_condition( $ruleset_id, $condition_index, $condition, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $desc = $this->translate_condition( $condition );

    $debug = $this->get_debug_info( $ruleset_id );

    $debug[$ruleset_id]['conditions'][$condition_index] = [
      'desc' => $desc,
      'result' => $result,
    ];

    $this->save_debug_info( $debug );
  }

  /**
   * Add action result
   */
  public function add_action( $ruleset_id, $passes, $action_index, $action ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $debug = $this->get_debug_info( $ruleset_id );

    $debug[$ruleset_id]['actions'][$action_index] = $this->translate_action( $action, $passes );

    $this->save_debug_info( $debug );
  }

  /**
   * Translate action into human-readable format
   */
  public function translate_action( $action, $passes ) {
    $actions = woo_conditional_shipping_actions();

    $cols = [
      isset( $actions[$action['type']] ) ? $actions[$action['type']]['title'] : __( 'N/A', 'woo-conditional-shipping' ),
    ];

    $desc = false;
    $status = 'pass';

    switch ( $action['type'] ) {
      case 'disable_shipping_methods':
      case 'enable_shipping_methods':
        $cols['methods'] = implode( ', ', $this->get_shipping_method_titles( $action ) );
        break;
      case 'set_price':
      case 'increase_price':
      case 'decrease_price':
        $cols['methods'] = implode( ', ', $this->get_shipping_method_titles( $action ) );
        $cols['value'] = $action['price'];
        break;
      case 'custom_error_msg':
        $cols['value'] = $action['error_msg'];
        break;
      case 'shipping_notice':
        $cols['value'] = $action['notice'];
        break;
    }

    if ( ! $passes && $action['type'] === 'enable_shipping_methods' ) {
      $desc = __( 'Shipping methods were disabled by "Enable shipping methods" because conditions did not pass', 'woo-conditional-shipping' );
      $status = 'notify';
    }

    return [
      'cols' => $cols,
      'desc' => $desc,
      'status' => $status
    ];
  }

  /**
   * Get shipping method titles
   */
  public function get_shipping_method_titles( $action ) {
    if ( ! $this->shipping_methods ) {
      $options = woo_conditional_shipping_get_shipping_method_options();

      foreach ( $options as $zone_id => $zone ) {
        foreach ( $zone['options'] as $instance_id => $data ) {
          $this->shipping_methods[$instance_id] = $data['title'];
        }
      }
    }

    return $this->ids_to_list( $action['shipping_method_ids'], $this->shipping_methods );
  }

  /**
   * Add total result for ruleset
   */
  public function add_result( $ruleset_id, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $debug = $this->get_debug_info( $ruleset_id );

    $debug[$ruleset_id]['result'] = $result;

    $this->save_debug_info( $debug );
  }

  /**
   * Translate condition to human-readable format
   */
  private function translate_condition( $condition ) {
    $operators = woo_conditional_shipping_operators();
    $filters = woo_conditional_shipping_filters();

    $filter = isset( $filters[$condition['type']] ) ? $filters[$condition['type']]['title'] : __( 'N/A', 'woo-conditional-shipping' );
    $operator = isset( $operators[$condition['operator']] ) ? $operators[$condition['operator']] : __( 'N/A', 'woo-conditional-shipping' );

    $value = $this->translate_condition_value( $condition );

    $cols = [ $filter, $operator ];

    // Some conditions only has operator and not value (e.g. customer logged in condition)
    if ( $value !== null ) {
      $cols[] = $value;
    }

    return implode( ' - ', $cols );
  }

  /**
   * Get condition value depending on the type
   */
  private function translate_condition_value( $condition ) {
    switch( $condition['type'] ) {
      case 'subtotal':
      case 'items':
      case 'weight':
      case 'height_total':
      case 'length_total':
      case 'width_total':
      case 'volume':
      case 'product_weight':
      case 'product_height':
      case 'product_length':
      case 'product_width':
        return $condition['value'];
      case 'products':
        return implode( ', ', array_map( 'get_the_title', (array) $condition['product_ids'] ) );
      case 'shipping_class':
        return implode( ', ', $this->get_term_titles( (array) $condition['shipping_class_ids'], 'product_shipping_class' ) );
      case 'category':
        return implode( ', ', $this->get_term_titles( (array) $condition['category_ids'], 'product_cat' ) );
      case 'product_attrs':
        return implode( ', ', $this->get_attr_titles( (array) $condition['product_attrs'] ) );
      case 'coupon':
        return implode( ', ', array_map( 'get_the_title', (array) $condition['coupon_ids'] ) );
      case 'customer_authenticated':
        return null; // This condition doesn't has value, only operator
      case 'customer_role':
        return implode( ', ', $this->get_role_titles( $condition['user_roles'] ) );
      case 'billing_postcode':
      case 'shipping_postcode':
        return $condition['postcodes'];
      case 'billing_state':
      case 'shipping_state':
        return implode( ', ', $this->get_state_titles( $condition['states'] ) );
      case 'billing_country':
      case 'shipping_country':
        return implode( ', ', $this->get_country_titles( $condition['countries'] ) );
      case 'weekdays':
        return implode( ', ', $this->get_weekday_titles( $condition['weekdays'] ) );
      case 'time':
        return $this->get_time_title( $condition );
      default:
        return 'N/A';
    }
  }

  /**
   * Get term titles
   */
  private function get_term_titles( $ids, $taxonomy ) {
    $titles = [];
    foreach ( $ids as $id ) {
      $term = get_term_by( 'id', $id, $taxonomy );

      $titles[] = $term ? $term->name : __( 'N/A', 'woo-conditional-shipping' );
    }

    return $titles;
  }

  /**
   * Get attribute titles
   */
  private function get_attr_titles( $condition_attrs ) {
    if ( ! $this->product_attrs ) {
      // Flatten attrs
      $this->product_attrs = [];
      foreach ( woo_conditional_product_attr_options() as $taxonomy_id => $attrs ) {
        foreach ( $attrs['attrs'] as $id => $label ) {
          $this->product_attrs[$id] = $label;
        }
      }
    }

    return $this->ids_to_list( $condition_attrs, $this->product_attrs );
  }

  /**
   * Get role titles
   */
  private function get_role_titles( $role_ids ) {
    if ( ! $this->customer_roles ) {
      $this->customer_roles = woo_conditional_shipping_role_options();
    }

    return $this->ids_to_list( $role_ids, $this->customer_roles );
  }

  /**
   * Get state titles
   */
  private function get_state_titles( $state_ids ) {
    if ( ! $this->states ) {
      $options = woo_conditional_shipping_state_options();
      foreach ( $options as $country_id => $states ) {
        foreach ( $states['states'] as $state_id => $state ) {
          $this->states["{$country_id}:{$state_id}"] = $state;
        }
      }
    }

    return $this->ids_to_list( $state_ids, $this->states );
  }

  /**
   * Get country titles
   */
  private function get_country_titles( $country_ids ) {
    if ( ! $this->countries ) {
      $this->countries = woo_conditional_shipping_country_options();
    }

    return $this->ids_to_list( $country_ids, $this->countries );
  }

  /**
   * Get weekday titles
   */
  private function get_weekday_titles( $weekdays ) {
    if ( ! $this->weekdays ) {
      $this->weekdays = woo_conditional_shipping_weekdays_options();
    }

    return $this->ids_to_list( $weekdays, $this->weekdays );
  }

  /**
   * Get time title
   */
  private function get_time_title( $condition ) {
    if ( ! $this->hours ) {
      $this->hours = woo_conditional_shipping_time_hours_options();
    }

    if ( ! $this->mins ) {
      $this->mins = woo_conditional_shipping_time_mins_options();
    }

    $hours = isset( $condition['time_hours'] ) ? $condition['time_hours'] : '0';
    $mins = isset( $condition['time_mins'] ) ? $condition['time_mins'] : '0';


    return sprintf( '%s:%s', $this->hours[$hours], $this->mins[$mins] );
  }

  /**
   * Convert IDs to human-readable list from options
   */
  private function ids_to_list( $values, $options ) {
    $titles = [];

    foreach ( $values as $value ) {
      $titles[] = isset( $options[$value] ) ? $options[$value] : __( 'N/A', 'woo-conditional-shipping' );
    }

    return $titles;
  }
}
