<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$reports = $reports ?? array();
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Zeko QA — Moderation', 'zeko-qa' ); ?></h1>

	<?php if ( empty( $reports ) ) : ?>
		<p><?php esc_html_e( 'Pending reports.', 'zeko-qa' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped" id="zeko-qa-reports-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Reporter', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Type', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Item ID', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Date', 'zeko-qa' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'zeko-qa' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $reports as $report ) : ?>
					<tr data-report-id="<?php echo absint( $report->id ); ?>">
						<td><?php echo absint( $report->id ); ?></td>
						<td><?php echo esc_html( $report->reporter_name ); ?></td>
						<td><?php echo esc_html( $report->item_type ); ?></td>
						<td>
							<?php if ( 'question' === $report->item_type ) : ?>
								<a href="<?php echo esc_url( home_url( '/questions/' . $report->item_id . '/' ) ); ?>" target="_blank">#<?php echo absint( $report->item_id ); ?></a>
							<?php else : ?>
								#<?php echo absint( $report->item_id ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $report->reason ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $report->created_at ) ); ?></td>
						<td>
							<button class="button zeko-qa-moderate-btn" data-action="dismiss" data-report-id="<?php echo absint( $report->id ); ?>"><?php esc_html_e( 'Dismiss', 'zeko-qa' ); ?></button>
							<button class="button zeko-qa-moderate-btn" data-action="resolve" data-report-id="<?php echo absint( $report->id ); ?>"><?php esc_html_e( 'Resolve', 'zeko-qa' ); ?></button>
							<button class="button button-link-delete zeko-qa-moderate-btn" data-action="delete_<?php echo esc_attr( $report->item_type ); ?>" data-report-id="<?php echo absint( $report->id ); ?>" data-item-id="<?php echo absint( $report->item_id ); ?>" data-item-type="<?php echo esc_attr( $report->item_type ); ?>"><?php esc_html_e( 'Delete', 'zeko-qa' ); ?></button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$(document).on('click', '.zeko-qa-moderate-btn', function(e) {
		e.preventDefault();
		var $btn = $(this);
		var action = $btn.data('action');
		var reportId = $btn.data('report-id');
		var itemId = $btn.data('item-id') || 0;
		var itemType = $btn.data('item-type') || '';

		if (action.indexOf('delete') !== -1 && !confirm('<?php echo esc_js( __( 'Are you sure you want to delete this content?', 'zeko-qa' ) ); ?>')) {
			return;
		}

		$btn.prop('disabled', true);

		$.post(ajaxurl, {
			action: 'zeko_qa_moderate_action',
			nonce: '<?php echo esc_attr( wp_create_nonce( 'zeko_qa_admin_nonce' ) ); ?>',
			mod_action: action,
			report_id: reportId,
			item_id: itemId,
			item_type: itemType,
		}, function(response) {
			if (response.success) {
				$btn.closest('tr').fadeOut(300, function() { $(this).remove(); });
			} else {
				alert(response.data.message || '<?php echo esc_js( __( 'Error.', 'zeko-qa' ) ); ?>');
				$btn.prop('disabled', false);
			}
		}).fail(function() {
			alert('<?php echo esc_js( __( 'Network error.', 'zeko-qa' ) ); ?>');
			$btn.prop('disabled', false);
		});
	});
});
</script>
