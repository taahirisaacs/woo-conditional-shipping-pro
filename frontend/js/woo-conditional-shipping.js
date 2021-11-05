jQuery(document).ready(function($) {
	$( document.body ).on( 'updated_checkout', function() {
		// Clear existing notices
		$( '.conditional-shipping-notice' ).remove();

		if ( typeof conditionalShippingNotices !== 'undefined' ) {
			var shippingRow = $( 'tr.woocommerce-shipping-totals td:eq(0)' );

			if ( shippingRow.length > 0 ) {
				// Add notices
				$.each( conditionalShippingNotices, function( index, notice ) {
					shippingRow.append( notice );
				} );
			}
		}
	} );


	var wcsDebug = {
		init: function() {
			this.toggleDebug();
			this.setInitial();

			var self = this;
			$( document.body ).on( 'updated_checkout', function( data ) {
				self.setInitial();
			} );
		},

		/**
		 * Toggle debug on click
		 */
		toggleDebug: function() {
			var self = this;

			$( document.body ).on( 'click', '#wcs-debug-header', function( e ) {
				if ( $( '#wcs-debug-contents' ).is( ':visible' ) ) {
					$( '#wcs-debug' ).toggleClass( 'closed', true );
				} else {
					$( '#wcs-debug' ).toggleClass( 'closed', false );
				}

				$( '#wcs-debug-contents' ).slideToggle( 200, function() {
					self.saveStatus();
				} );
			} );
		},

		/**
		 * Save debug open / closed status to cookies
		 */
		saveStatus: function() {
			Cookies.set( 'wcs_debug_status', $( '#wcs-debug-contents' ).is( ':visible' ) );
		},

		/**
		 * Set initial stage for debug
		 */
		setInitial: function() {
			var status = Cookies.get( 'wcs_debug_status' );

			$( '#wcs-debug-contents' ).toggle( status === 'true' );
			$( '#wcs-debug' ).toggleClass( 'closed', $( '#wcs-debug-contents' ).is( ':hidden' ) );
		}
	}

	wcsDebug.init();
});
