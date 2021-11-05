<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Pro_License {
	public $id = 'woo_conditional_shipping_pro';
	public $slug = 'woo-conditional-shipping-pro';
	public $plugin_file = WOO_CONDITIONAL_SHIPPING_PRO_FILE; 

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add license to the settings
		add_action( 'woo_conditional_shipping_after_settings', [ $this, 'add_license_settings' ] );

		// Save and update license
		add_action( 'woocommerce_settings_save_shipping', [ $this, 'update_license_status' ], 20, 0 );

		// Schedule license ping
		if ( ! wp_next_scheduled ( $this->id . '_license_ping' ) ) {
			wp_schedule_event( time(), 'daily', $this->id . '_license_ping' );
		}

		// Hook into license ping schedule
		add_action( $this->id . '_license_ping', [ $this, 'ping' ] );

		// Deactivation hook for license ping
		register_deactivation_hook( $this->plugin_file, function() {
			wp_clear_scheduled_hook( $this->id . '_license_ping' );
		} );
	}

	/**
	 * Add license settings
	 */
	public function add_license_settings( $settings ) {
		$license_key = get_option( 'license_' . $this->id, '' );
		$status = get_option( 'license_' . $this->id . '_status', null );
		$error = get_option( 'license_' . $this->id . '_error', '' );
		$last_checked = get_option( 'license_' . $this->id . '_last_checked', false );
		$status_unknown = ( $status === null );

		include 'views/license.html.php';
	}

	/**
	 * Update license status after saving the settings
	 */
	public function update_license_status() {
		if ( isset( $_POST['license_' . $this->id] ) ) {
			$license_key = $_POST['license_' . $this->id];
			update_option( 'license_' . $this->id, $license_key );

			$status = $this->get_license_status( $license_key );
	
			if ( $status === true ) {
				update_option( 'license_' . $this->id . '_status', '1' );
				update_option( 'license_' . $this->id . '_error', '' );
			} else {
				update_option( 'license_' . $this->id . '_status', '0' );
				update_option( 'license_' . $this->id . '_error', $status );
			}
	
			update_option( 'license_' . $this->id . '_last_checked', time() );
		}
	}

	/**
	 * Get license status
	 */
	private function get_license_status( $license_key ) {
		$response = wp_remote_post( 'https://wooelements.com/api/licenses/check', [
			'sslverify' => false,
			'timeout' => 10,
			'body' => [
				'license_key' => $license_key,
				'product' => $this->slug,
				'site_url' => get_site_url(),
			]
		] );

		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $response_code === 200 || $response_code === 204 ) {
				return true;
			} else if ( $response_code === 401 ) {
				if ( $response_body && isset( $response_body->error ) ) {
					return $response_body->error;
				}
			}
		} else {
			return $response->get_error_message();
		}

		return __( 'Unknown error', 'woo-conditional-shipping' );
	}

	/**
	 * Ping license
	 */
	public function ping() {
		wp_remote_post( 'https://wooelements.com/api/licenses/ping', [
			'sslverify' => false,
			'timeout' => 10,
			'blocking' => false,
			'body' => [
				'license_key' => get_option( 'license_' . $this->id, '' ),
				'product' => $this->slug,
				'site_url' => get_site_url(),
			]
		] );
	}
}
