<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Ruleset {
  private $post_id;
  private $debug;

  /**
   * Constructor
   */
  public function __construct( $post_id = false ) {
    $this->post_id = $post_id;

    $this->debug = Woo_Conditional_Shipping_Debug::instance();
  }

  /**
   * Get ID
   */
  public function get_id() {
    return $this->post_id;
  }

  /**
   * Get title
   */
  public function get_title( $context = 'view' ) {
    $post = $this->get_post();

    if ( $post && $post->post_title ) {
      return $post->post_title;
    }

    if ( $context === 'edit' ) {
      return '';
    }

    return __( 'Ruleset', 'woo-conditional-shipping' );
  }

  /**
   * Get admin edit URL
   */
  public function get_admin_edit_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
    ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

    return $url;
  }

  /**
   * Get admin delete URL
   */
  public function get_admin_delete_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
      'action' => 'delete',
    ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

    return $url;
  }

  /**
   * Get post
   */
  public function get_post() {
    if ( $this->post_id ) {
      return get_post( $this->post_id );
    }

    return false;
  }

  /**
   * Get whether or not ruleset is enabled
   */
  public function get_enabled() {
    $enabled = get_post_meta( $this->post_id, '_wcs_enabled', true );
    $enabled_exists = metadata_exists( 'post', $this->post_id, '_wcs_enabled' );

    // Metadata doesn't exist yet so we assume it's enabled
    if ( ! $enabled_exists ) {
      return true;
    }

    return $enabled === 'yes';
  }

  /**
	 * Get products which are selected in conditions
	 */
	public function get_products() {
    $product_ids = array();

		foreach ( $this->get_conditions() as $condition ) {
			if ( isset( $condition['product_ids'] ) && is_array( $condition['product_ids'] ) ) {
				$product_ids = array_merge( $product_ids, $condition['product_ids'] );
			}
		}

		$products = array();
		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$products[$product_id] = wp_kses_post( $product->get_formatted_name() );
			}
		}

		return $products;
  }
  
  /**
   * Get conditions for the ruleset
   */
  public function get_conditions() {
    $conditions = get_post_meta( $this->post_id, '_wcs_conditions', true );

    if ( ! $conditions ) {
      return array();
    }

    return (array) $conditions;
  }

  /**
   * Get actions for the ruleset
   */
  public function get_actions() {
    $actions = get_post_meta( $this->post_id, '_wcs_actions', true );

    if ( ! $actions ) {
      return array();
    }

    return (array) $actions;
  }

  /**
   * Check if conditions pass for the given package
   */
  public function validate( $package ) {
    // Some 3rd party plugins may deliver empty $package which causes
    // error notices. If so, we fill array manually.
    if ( ! is_array( $package ) ) {
      $package = array();
    }

    if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
      $package['contents'] = array();
    }

    // Not all shipping plugins provide package contents, in that case we will assume
    // there is only one package and that is equal to cart contents
    if ( empty( $package['contents'] ) && WC()->cart ) {
      $package['contents'] = WC()->cart->get_cart();
    }

    $filters = woo_conditional_shipping_filters();

    $passed = true;
    foreach ( $this->get_conditions() as $index => $condition ) {
      if ( isset( $condition['type'] ) && ! empty( $condition['type'] ) ) {
        $type = $condition['type'];

        $function = "filter_{$type}";

        if ( isset( $filters[$type] ) && isset( $filters[$type]['callback'] ) ) {
          $callable = $filters[$type]['callback'];
        } else if ( class_exists( 'Woo_Conditional_Shipping_Filters_Pro' ) && method_exists( 'Woo_Conditional_Shipping_Filters_Pro', $function ) ) {
          $callable = array( 'Woo_Conditional_Shipping_Filters_Pro', $function );
        } else {
          $callable = array( 'Woo_Conditional_Shipping_Filters', $function );
        }

        $result = call_user_func( $callable, $condition, $package );
        if ( $result ) {
          $passed = false;
        }

        $this->debug->add_condition( $this->get_id(), $index, $condition, $result );
      }
    }

    $this->debug->add_result( $this->get_id(), $passed );

    return $passed;
  }
}
