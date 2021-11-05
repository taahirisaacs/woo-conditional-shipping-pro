jQuery(document).ready(function($) {
	var wcsConditionsTable = {
		operators: [],
		conditions: [],
		triggersInit: false,
		table: null,

		init: function() {
			var table = $( 'table.woo-conditional-shipping-conditions' );

			if ( table.length == 0 ) {
				return;
			}

			this.table = table;

			this.operators = table.data( 'operators' );
			this.conditions = table.data( 'conditions' );

			this.insertExisting();

			if ( ! this.triggersInit ) {
				this.triggerFieldUpdates();
				this.triggerRemoveConditions();
				this.triggerAddCondition();
				this.triggerToggleValueInputs();

				this.triggersInit = true;
			}
		},

		/**
		 * Show correct fields when changing condition type
		 */
		triggerFieldUpdates: function() {
			var self = this;

			$( document ).on( 'change', 'select.wcs_condition_type_select', function() {
				var row = $( this ).closest( 'tr' );

				self.toggleOperators( row );
				self.toggleValueInputs( row );
			});
		},

		/**
		 * Insert existing conditions into the table
		 */
		insertExisting: function() {
			for ( var i = 0; i < this.conditions.length; i++ ) {
				this.addCondition( this.conditions[i] );
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			this.toggleAllValueInputs();
		},

		/**
		 * Toggle all value inputs
		 */
		toggleAllValueInputs: function() {
			var self = this;

			$( 'tbody tr', this.table ).each( function() {
				self.toggleOperators( $( this ) );
				self.toggleValueInputs( $( this ) );
			});
		},

		/**
		 * Toggle value inputs for a single row
		 */
		toggleValueInputs: function( row ) {
			this.removeClassStartingWith( row, 'wcs-operator-' );
			this.removeClassStartingWith( row, 'wcs-type-' );

			var type = $( 'select.wcs_condition_type_select', row ).val();
			var operator = $( 'select.wcs_operator_select', row ).val();

			row.addClass( 'wcs-operator-' + operator );
			row.addClass( 'wcs-type-' + type );
		},

		/**
		 * Toggle operators
		 */
		toggleOperators: function( row ) {
			var operators = $( 'select.wcs_condition_type_select option:selected', row) .data( 'operators' );

			// Save current value
			var currentValue = $( 'select.wcs_operator_select', row ).val();

			// First remove all operators
			$( 'select.wcs_operator_select option', row ).remove();

			var self = this;
			$.each( operators, function( index, value ) {
				self.renderOperator( row, value );
			} );

			if ( typeof currentValue != 'undefined' ) {
				if ( $( 'select.wcs_operator_select option[value="' + currentValue + '"]', row ).length > 0 ) {
					$( 'select.wcs_operator_select', row ).val( currentValue ).trigger( 'change' );
				}
			}
		},

		/**
		 * Render operator
		 */
		renderOperator: function( row, operator ) {
			var operatorTitle = this.operators[operator];

			$( 'select.wcs_operator_select', row ).append( '<option value="' + operator + '">' + operatorTitle + '</option>' );
		},

		/**
		 * Add new condition
		 */
		addCondition: function( data ) {
			// Get index
			var index = this.table.data( 'index' );
			if (typeof index == 'undefined') { index = 0; }
			data['index'] = index;

			// Add one to conditions table index
			this.table.data( 'index', index + 1 );

			// Get template
			var row_template = wp.template( 'wcs_row_template' );

			// Add products
			var products_data = this.table.data( 'selected-products' );
			data.selected_products = [];
			if ( typeof data.product_ids !== 'undefined' && data.product_ids !== null && data.product_ids.length > 0 ) {
				jQuery.each( data.product_ids, function( index, product_id ) {
					if ( typeof products_data[product_id] !== 'undefined' ) {
						data.selected_products.push({
							'id': product_id,
							'title': products_data[product_id]
						});
					}
				});
			}

			// Render template and add to the table
			$( 'tbody', this.table ).append( row_template( data ) );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			this.toggleAllValueInputs();
		},

		/**
		 * Remove selected conditions when clicking the button
		 */
		triggerRemoveConditions: function() {
			$( document ).on( 'click', 'button#wcs-remove-conditions', function() {
				$( '.condition_row input.remove_condition:checked', this.table ).closest( 'tr.condition_row' ).remove();
			});
		},

		/**
		 * Add new condition when clicking the Add button
		 */
		triggerAddCondition: function() {
			var self = this;

			$( document ).on( 'click', 'button#wcs-add-condition', function() {
				self.addCondition( {} );
			});
		},

		/**
		 * Update value inputs when changing operator type
		 */
		triggerToggleValueInputs: function() {
			var self = this;

			$( document ).on('change', 'select.wcs_operator_select', function() {
				var row = $( this ).closest( 'tr' );
				self.toggleValueInputs( row );
			});
		},

		removeClassStartingWith: function(el, filter) {
			el.removeClass(function (index, className) {
				return (className.match(new RegExp("\\S*" + filter + "\\S*", 'g')) || []).join(' ');
			});
		}
	};

	wcsConditionsTable.init();

	var wcsActionsTable = {
		actions: [],
		triggersInit: false,
		table: null,

		init: function() {
			var table = $( 'table.woo-conditional-shipping-actions' );

			if ( table.length == 0 ) {
				return;
			}

			this.table = table;

			this.actions = table.data( 'actions' );

			this.insertExisting();

			if ( ! this.triggersInit ) {
				this.triggerFieldUpdates();
				this.triggerAddAction();
				this.triggerRemoveActions();

				this.triggersInit = true;
			}
		},

		/**
		 * Show correct fields when changing action type
		 */
		triggerFieldUpdates: function() {
			var self = this;

			$( document ).on( 'change', 'select.wcs_action_type_select', function() {
				var row = $( this ).closest( 'tr' );

				self.toggleValueInputs( row );
			});
		},

		/**
		 * Insert existing actions into the table
		 */
		insertExisting: function() {
			for ( var i = 0; i < this.actions.length; i++ ) {
				this.addAction( this.actions[i] );
			}

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			this.toggleAllValueInputs();
		},

		/**
		 * Toggle all value inputs
		 */
		toggleAllValueInputs: function() {
			var self = this;

			$( 'tbody tr', this.table ).each( function() {
				self.toggleValueInputs( $( this ) );
			});
		},

		/**
		 * Toggle value inputs for a single row
		 */
		toggleValueInputs: function( row ) {
			this.removeClassStartingWith( row, 'wcs-action-type-' );

			var type = $( 'select.wcs_action_type_select', row ).val();

			row.addClass( 'wcs-action-type-' + type );
		},

		/**
		 * Add action
		 */
		addAction: function( data ) {
			// Get index
			var index = this.table.data( 'index' );
			if (typeof index == 'undefined') { index = 0; }
			data['index'] = index;

			// Add one to conditions table index
			this.table.data( 'index', index + 1 );

			// Get template
			var row_template = wp.template( 'wcs_action_row_template' );

			// Render template and add to the table
			$( 'tbody', this.table ).append( row_template( data ) );

			$( document.body ).trigger( 'wc-enhanced-select-init' );

			this.toggleAllValueInputs();
		},

		/**
		 * Remove selected actions when clicking the button
		 */
		triggerRemoveActions: function() {
			$( document ).on( 'click', 'button#wcs-remove-actions', function() {
				$( '.action_row input.remove_action:checked', this.table ).closest( 'tr.action_row' ).remove();
			});
		},

		/**
		 * Add new action when clicking the Add button
		 */
		triggerAddAction: function() {
			var self = this;

			$( document ).on( 'click', 'button#wcs-add-action', function() {
				self.addAction( {} );
			});
		},

		removeClassStartingWith: function(el, filter) {
			el.removeClass(function (index, className) {
				return (className.match(new RegExp("\\S*" + filter + "\\S*", 'g')) || []).join(' ');
			});
		}
	};

	wcsActionsTable.init();

	/**
	 * Warn when deleting ruleset
	 */
	$( document ).on( 'click', '.woo-conditional-shipping-ruleset-delete', function( e ) {
		return confirm( "Are you sure?" );
	} );

	/**
	 * Open health check issue
	 */
	$( document ).on( 'click', '.woo-conditional-shipping-health-check .issue-container .title', function( e ) {
		var container = $( this ).closest( '.issue-container' );

		$( '.details', container ).slideToggle();
		$( '.toggle-indicator' ).toggleClass( 'open' );
	} );

	/**
	 * AJAX toggle
	 */
	$( document ).on( 'click', '.woo-conditional-shipping-ruleset-status .woocommerce-input-toggle', function( e ) {
		e.preventDefault();

		var self = this;

		var data = {
			id: $( this ).data( 'id' ),
		};

		$.ajax( {
			type: 'post',
			url: woo_conditional_shipping.ajax_url,
			data: data,
			dataType: 'json',
			beforeSend: function() {
				$( self ).removeClass( 'woocommerce-input-toggle--enabled woocommerce-input-toggle--disabled' );
				$( self ).addClass( 'woocommerce-input-toggle--loading' );
			},
			success: function( response ) {
				$( self ).removeClass( 'woocommerce-input-toggle--loading' );

				if ( response.enabled ) {
					var cssClass = 'woocommerce-input-toggle--enabled';
				} else {
					var cssClass = 'woocommerce-input-toggle--disabled';
				}

				$( self ).addClass( cssClass );
			},
			error: function() {
				alert( 'Unknown error' );
			},
			complete: function() {

			}
		} );
	} );
});
