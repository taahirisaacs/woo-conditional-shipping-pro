<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Post_Type {
  /**
   * Constructor
   */
  public function __construct() {
    // Register custom post type
    add_action( 'init', array( $this, 'register_post_type' ), 10, 0 );
  }

  /**
   * Register custom post type
   */
  public function register_post_type() {
    register_post_type( 'wcs_ruleset',
      array(
        'labels' => array(
          'name' => __( 'Conditional Shipping Rulesets', 'woo-conditional-shipping' ),
          'singular_name' => __( 'Conditional Shipping Ruleset', 'woo-conditional-shipping' )
        ),
        'public' => false,
        'publicly_queryable' => false,
				'show_ui' => false,
        'has_archive' => false,
				'supports' => array(
					'title',
				),
      )
    );
  }
}
