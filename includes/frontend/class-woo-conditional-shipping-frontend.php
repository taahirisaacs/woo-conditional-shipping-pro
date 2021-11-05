<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Frontend {
  private $passed_rule_ids = array();
  private $debug;

  /**
   * Constructor
   */
  public function __construct() {
    $this->debug = Woo_Conditional_Shipping_Debug::instance();

    // Load frontend styles and scripts
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );

    if ( ! get_option( 'wcs_disable_all', false ) ) {
      add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods' ), 100, 2 );

      // For price related actions we need to use woocommerce_shipping_method_add_rate_args filter
      add_filter( 'woocommerce_shipping_method_add_rate_args', array( $this, 'shipping_method_price_actions' ), 100, 2 );
  
      // Custom "no shipping methods available" message
      add_filter( 'woocommerce_cart_no_shipping_available_html', array( $this, 'no_shipping_message' ), 100, 1 );
      add_filter( 'woocommerce_no_shipping_available_html', array( $this, 'no_shipping_message' ), 100, 1 );
      
      // Custom "shipping notice" message
      add_action( 'woocommerce_review_order_before_shipping', array( $this, 'shipping_notice' ), 100, 0 );
    }
  }

  /**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'woo-conditional-shipping-js',
			plugin_dir_url( __FILE__ ) . '../../frontend/js/woo-conditional-shipping.js',
			array( 'jquery', 'jquery-cookie' ),
			WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION
    );

    wp_enqueue_style( 'woo_conditional_shipping_css', plugin_dir_url( __FILE__ ) . '../../frontend/css/woo-conditional-shipping.css', array(), WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION );
  }

  /**
   * Adjust shipping method prices
   */
  public function shipping_method_price_actions( $args, $shipping_method ) {
    $rulesets = woo_conditional_shipping_get_rulesets( true );

    $instance_id = apply_filters( 'woo_conditional_shipping_get_instance_id_from_method', $shipping_method->instance_id, $args, $shipping_method );

    // If we cant get instance ID due to non-standard shipping method, we are out of luck
    if ( ! $instance_id ) {
      return $args;
    }

    foreach ( $rulesets as $ruleset ) {
      $passes = $ruleset->validate( $args['package'] );

      if ( $passes ) {
        foreach ( $ruleset->get_actions() as $action_index => $action ) {
          if ( $instance_id !== false && isset( $action['shipping_method_ids'] ) && in_array( $instance_id, (array) $action['shipping_method_ids'] ) ) {
            $this->debug->add_action( $ruleset->get_id(), $passes, $action_index, $action );

            if ( $action['price_mode'] === 'pct' ) {
              $subtotal = apply_filters( 'woo_conditional_shipping_price_action_subtotal', Woo_Conditional_Shipping_Filters::get_cart_subtotal(), $action, $ruleset );
              $amount = $subtotal * ( floatval( $action['price'] ) / 100 );
            } else {
              $amount = floatval( $action['price'] );
            }

            if ( $action['type'] === 'set_price' ) {
              $args['cost'] = $amount;
            }

            if ( $action['type'] === 'increase_price' ) {
              if ( is_array( $args['cost'] ) ) {
                $args['cost'][] = $amount;
              } else {
                $args['cost'] += $amount;
              }
            }

            if ( $action['type'] === 'decrease_price' ) {
              if ( is_array( $args['cost'] ) ) {
                $args['cost'][] = $amount * -1;

                // Do not allow shipping cost go negative, add negative amount as positive to set price to 0
                if ( array_sum( $args['cost'] ) < 0 ) {
                  $args['cost'][] = array_sum( $args['cost'] ) * -1;
                }
              } else {
                $args['cost'] -= $amount;

                if ( $args['cost'] < 0 ) {
                  $args['cost'] = 0;
                }
              }
            }
          }
        }
      }
    }

    return $args;
  }
  
  /**
   * Filter shipping methods
   */
  public function filter_shipping_methods( $rates, $package ) {
    $rulesets = woo_conditional_shipping_get_rulesets( true );
    $this->passed_rule_ids = array();

    $disable_keys = array();
    $enable_keys = array();

    foreach ( $rulesets as $ruleset ) {
      $passes = $ruleset->validate( $package );

      if ( $passes ) {
        $this->passed_rule_ids[] = $ruleset->get_id();
      }

      foreach ( $ruleset->get_actions() as $action_index => $action ) {
        if ( $action['type'] === 'disable_shipping_methods' ) {
          if ( $passes ) {
            foreach ( $rates as $key => $rate ) {
              $instance_id = $this->get_rate_instance_id( $rate );
  
              if ( $instance_id !== false && in_array( $instance_id, (array) $action['shipping_method_ids'] ) ) {
                $disable_keys[$key] = true;
                unset( $enable_keys[$key] );
              }
            }

            $this->debug->add_action( $ruleset->get_id(), $passes, $action_index, $action );
          }
        }

        if ( $action['type'] === 'enable_shipping_methods' ) {
          foreach ( $rates as $key => $rate ) {
            $instance_id = $this->get_rate_instance_id( $rate );

            if ( $instance_id !== false && in_array( $instance_id, (array) $action['shipping_method_ids'] ) ) {
              if ( $passes ) {
                $enable_keys[$key] = true;
                unset( $disable_keys[$key] );
              } else {
                $disable_keys[$key] = true;
                unset( $enable_keys[$key] );
              }
            }
          }

          $this->debug->add_action( $ruleset->get_id(), $passes, $action_index, $action );
        }
      }
    }

    foreach ( $rates as $key => $rate ) {
      if ( isset( $disable_keys[$key] ) && ! isset( $enable_keys[$key] ) ) {
        unset( $rates[$key] );
      }
    }

    // Store passed rule IDs into the session for later use
    // We cannot use $this->passed_rule_ids directly since this function is not evaluated
    // if rates are fetched from WC cache. Thus we use session which will always contain
    // passed_rule_ids
    WC()->session->set( 'wcp_passed_rule_ids', $this->passed_rule_ids );

    return $rates;
  }

  /**
   * Get passed rules from session
   */
  private function get_passed_rules() {
    $passed_rule_ids = WC()->session->get( 'wcp_passed_rule_ids' );
    $passed_rules = array();

    if ( ! empty( $passed_rule_ids ) ) {
      $rulesets = woo_conditional_shipping_get_rulesets( true );

      foreach ( $rulesets as $ruleset ) {
        if ( in_array( $ruleset->get_id(), $passed_rule_ids, true ) ) {
          $passed_rules[] = $ruleset;
        }
      }
    }

    return $passed_rules;
  }

  /**
   * Shipping notice message
   */
  public function shipping_notice() {
    $notices = array();

    foreach ( $this->get_passed_rules() as $ruleset ) {
      foreach ( $ruleset->get_actions() as $action_index => $action ) {
        if ( $action['type'] === 'shipping_notice' && ! empty( $action['notice'] ) ) {
          $notice = do_shortcode( $action['notice'] );

          $notices[] = sprintf( '<div class="conditional-shipping-notice">%s</div>', $notice );

          $this->debug->add_action( $ruleset->get_id(), true, $action_index, $action );
        }
      }
    }

    echo '<script type="text/javascript">var conditionalShippingNotices = ' . json_encode( $notices ) . ';</script>';
  }

  /**
   * Custom "no shipping methods available" message
   */
  public function no_shipping_message( $orig_msg ) {
    $msgs = array();
    $i = 1;

    foreach ( $this->get_passed_rules() as $ruleset ) {
      foreach ( $ruleset->get_actions() as $action_index => $action ) {
        if ( $action['type'] === 'custom_error_msg' && ! empty( $action['error_msg'] ) ) {
          $msgs[] = sprintf( '<div class="conditional-shipping-custom-error-msg i-%d">%s</div>', $i, $action['error_msg'] );
          $i++;

          $this->debug->add_action( $ruleset->get_id(), true, $action_index, $action );
        }
      }
    }

    if ( ! empty( $msgs ) ) {
      return implode( '', $msgs );
    }

    return $orig_msg;
  }

  /**
   * Helper function for getting rate instance ID
   */
  public function get_rate_instance_id( $rate ) {
    $instance_id = false;

    if ( method_exists( $rate, 'get_instance_id' ) && strlen( strval( $rate->get_instance_id() ) ) > 0 ) {
      $instance_id = $rate->get_instance_id();
    } else {
      if ( $rate->method_id == 'oik_weight_zone_shipping' ) {
        $ids = explode( '_', $rate->id );
        $instance_id = end( $ids );
      } else {
        $ids = explode( ':', $rate->id );
        if ( count($ids) >= 2 ) {
          $instance_id = $ids[1];
        }
      }
    }

    $instance_id = apply_filters( 'woo_conditional_shipping_get_instance_id', $instance_id, $rate );

    return $instance_id;
  }
}
