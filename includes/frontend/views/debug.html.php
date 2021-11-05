<div id="wcs-debug">
	<div id="wcs-debug-header">
		<div class="wcs-debug-title"><?php _e( 'Conditional Shipping Debug', 'woo-conditional-shipping' ); ?></div>
		<div class="wcs-debug-toggle"></div>
	</div>

	<div id="wcs-debug-contents">
		<?php if ( empty( $debug ) ) { ?>
			<p><?php _e( 'No rulesets were run.', 'woo-conditional-shipping' ); ?></p>
		<?php } ?>

		<?php foreach ( $debug as $ruleset_id => $data ) { ?>
			<div class="wcs-debug-<?php echo $ruleset_id; ?>">
				<h3 class="ruleset-title">
					<a href="<?php echo wcs_get_ruleset_admin_url( $data['ruleset_id'] ); ?>">
						<?php echo $data['ruleset_title']; ?>
					</a>
				</h3>

				<table class="wcs-debug-table wcs-debug-conditions">
					<thead>
						<tr>
							<th colspan="2"><?php _e( 'Conditions', 'woo-conditional-shipping' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data['conditions'] as $condition ) { ?>
							<tr class="result-<?php echo ( $condition['result'] ? 'fail' : 'pass' ); ?>">
								<td><?php echo $condition['desc']; ?></td>
								<td class="align-right"><?php echo ( $condition['result'] ? __( 'Fail', 'woo-conditional-shipping' ) : __( 'Pass', 'woo-conditional-shipping' ) ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr class="result-<?php echo ( $data['result'] ? 'pass' : 'fail' ); ?>">
							<th colspan="2" class="align-right"><?php echo ( $data['result'] ? __( 'Pass', 'woo-conditional-shipping' ) : __( 'Fail', 'woo-conditional-shipping' ) ); ?></th>
						</tr>
					</tfoot>
				</table>

				<table class="wcs-debug-table wcs-debug-actions">
					<thead>
						<tr>
							<th><?php _e( 'Actions', 'woo-conditional-shipping' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $data['actions'] as $action ) { ?>
							<tr class="status-<?php echo $action['status']; ?>">
								<td>
									<?php echo implode( ' - ', $action['cols'] ); ?>

									<?php if ( $action['desc'] ) { ?>
										<br><small><?php echo $action['desc']; ?></small>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
						<?php if ( empty( $data['actions'] ) ) { ?>
							<tr>
								<td><?php _e( 'No actions were run for this ruleset', 'woo-conditional-shipping' ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php } ?>
	</div>
</div>
