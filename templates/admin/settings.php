<?php
/**
 * Admin settings template
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

if ( isset( $_POST['zeko_qa_save_email_settings'], $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'zeko_qa_email_settings' ) ) {
	update_option( 'zeko_qa_email_notifications_enabled', sanitize_key( $_POST['zeko_qa_email_notifications_enabled'] ?? '0' ) );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'zeko-qa' ) . '</p></div>';
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Zeko QA Settings', 'zeko-qa' ); ?></h1>
	<p><?php esc_html_e( 'Manage your Q&A plugin settings here.', 'zeko-qa' ); ?></p>

	<div class="zeko-qa-admin-card">
		<h2><?php esc_html_e( 'Email Notifications', 'zeko-qa' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'zeko_qa_email_settings' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="zeko_qa_email_notifications_enabled"><?php esc_html_e( 'Enable email notifications', 'zeko-qa' ); ?></label></th>
					<td>
						<select id="zeko_qa_email_notifications_enabled" name="zeko_qa_email_notifications_enabled">
							<option value="1" <?php selected( get_option( 'zeko_qa_email_notifications_enabled', 1 ), 1 ); ?>><?php esc_html_e( 'Enabled', 'zeko-qa' ); ?></option>
							<option value="0" <?php selected( get_option( 'zeko_qa_email_notifications_enabled', 1 ), 0 ); ?>><?php esc_html_e( 'Disabled', 'zeko-qa' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Send email notifications when an answer is posted on a question or when an answer is accepted.', 'zeko-qa' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'zeko-qa' ), 'primary', 'zeko_qa_save_email_settings' ); ?>
		</form>
	</div>

	<div class="zeko-qa-admin-card">
		<h2><?php esc_html_e( 'Database', 'zeko-qa' ); ?></h2>
		<p><?php esc_html_e( 'Plugin version:', 'zeko-qa' ); ?> <?php echo esc_html( ZEKO_QA_VERSION ); ?></p>
		<p><?php esc_html_e( 'Database version:', 'zeko-qa' ); ?> <?php echo esc_html( get_option( 'zeko_qa_db_version', '1.0.0' ) ); ?></p>
		<button class="button button-secondary" id="zeko-qa-flush-rewrites"><?php esc_html_e( 'Flush Rewrite Rules', 'zeko-qa' ); ?></button>
	</div>

	<div class="zeko-qa-admin-card">
		<h2><?php esc_html_e( 'Shortcodes', 'zeko-qa' ); ?></h2>
		<ul>
			<li><code>[zeko_qa_archive]</code> - <?php esc_html_e( 'Displays the question archive with filters.', 'zeko-qa' ); ?></li>
			<li><code>[zeko_qa_ask_form]</code> - <?php esc_html_e( 'Displays the ask a question form.', 'zeko-qa' ); ?></li>
			<li><code>[zeko_qa_dashboard]</code> - <?php esc_html_e( 'Displays the user Q&A dashboard.', 'zeko-qa' ); ?></li>
		</ul>
	</div>

	<div class="zeko-qa-admin-card">
		<h2><?php esc_html_e( 'Generated Pages', 'zeko-qa' ); ?></h2>
		<ul>
			<?php
			$page_list = array(
				'questions'      => __( 'Questions', 'zeko-qa' ),
				'ask-a-question' => __( 'Ask a Question', 'zeko-qa' ),
				'qa-dashboard'   => __( 'Q&A Dashboard', 'zeko-qa' ),
			);
			foreach ( $page_list as $slug => $page_title ) {
				$current_page = class_exists( 'Zeko_Core_Helpers' )
				? Zeko_Core_Helpers::get_instance()->get_page_by_slug( $slug )
				: get_page_by_path( $slug );
				if ( $current_page ) {
					echo '<li><a href="' . esc_url( get_edit_post_link( $current_page->ID ) ) . '">' . esc_html( $page_title ) . '</a></li>';
				} else {
					echo '<li>' . esc_html( $page_title ) . ' - ' . esc_html__( 'Not created', 'zeko-qa' ) . '</li>';
				}
			}
			?>
		</ul>
	</div>
</div>
