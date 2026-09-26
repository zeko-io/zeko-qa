<?php
/**
 * Admin demo data template
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Zeko QA Demo Data', 'zeko-qa' ); ?></h1>
	<p><?php esc_html_e( 'Generate demo data to preview the Q&A system.', 'zeko-qa' ); ?></p>

	<div class="zeko-qa-admin-card">
		<h2><?php esc_html_e( 'Actions', 'zeko-qa' ); ?></h2>
		<p><?php esc_html_e( 'This will generate 5 topics, 15 questions, 30 answers, and mock votes.', 'zeko-qa' ); ?></p>
		<?php wp_nonce_field( 'zeko_qa_admin_nonce', 'zeko_qa_admin_nonce_field' ); ?>
		<button class="button button-primary button-large" id="zeko-qa-generate-demo"><?php esc_html_e( 'Generate Demo Data', 'zeko-qa' ); ?></button>
		<button class="button button-secondary button-large" id="zeko-qa-clear-demo"><?php esc_html_e( 'Clear Demo Data', 'zeko-qa' ); ?></button>
		<span class="zeko-qa-demo-status" style="margin-left: 10px;"></span>
	</div>
</div>
