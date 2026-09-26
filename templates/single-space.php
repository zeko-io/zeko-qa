<?php
/**
 * Single space template
 *
 * @package Zeko_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$space = $args['space'] ?? null;
$space_posts = $args['posts'] ?? array();

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$space = $space ?? null;
if ( ! $space ) {
	return;
}

$current_user_id = get_current_user_id();
$is_member       = $current_user_id ? Zeko_QA::instance()->get_db()->is_space_member( $space->id, $current_user_id ) : false;
$is_admin        = false;
if ( $is_member ) {
	global $wpdb;
	$table    = Zeko_QA::instance()->get_db()->get_table_space_members();
	$sql      = $wpdb->prepare( "SELECT role FROM {$table} WHERE space_id = %d AND user_id = %d LIMIT 1", $space->id, $current_user_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
	$is_admin = $wpdb->get_var( $sql ) === 'admin'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.SlowDBQuery
}
$space_posts = $space_posts ?? array();
?>
<div class="zeko-qa-space-wrapper">
	<div class="zeko-qa-space-header">
		<h1 class="zeko-qa-space-title"><?php echo esc_html( $space->name ); ?></h1>
		<?php if ( ! empty( $space->description ) ) : ?>
			<p class="zeko-qa-space-desc"><?php echo esc_html( $space->description ); ?></p>
		<?php endif; ?>
		<div class="zeko-qa-space-meta">
			<span><?php echo absint( $space->member_count ); ?> <?php esc_html_e( 'members', 'zeko-qa' ); ?></span>
		</div>
		<?php if ( is_user_logged_in() ) : ?>
			<button class="zeko-qa-btn <?php echo $is_member ? 'zeko-qa-btn-secondary' : 'zeko-qa-btn-primary'; ?> zeko-qa-space-join-btn" data-space-id="<?php echo absint( $space->id ); ?>">
				<?php echo $is_member ? esc_html__( 'Leave Space', 'zeko-qa' ) : esc_html__( 'Join Space', 'zeko-qa' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<?php if ( empty( $space_posts ) ) : ?>
		<div class="zeko-qa-empty-state">
			<p><?php esc_html_e( 'No posts in this space yet.', 'zeko-qa' ); ?></p>
		</div>
	<?php else : ?>
		<div class="zeko-qa-questions-list">
			<?php foreach ( $space_posts as $space_post ) : ?>
				<article class="zeko-qa-question-card" data-question-id="<?php echo absint( $space_post->item_id ); ?>">
					<div class="zeko-qa-content-column">
						<h2 class="zeko-qa-question-title">
							<a href="<?php echo esc_url( home_url( '/questions/' . $space_post->slug . '/' ) ); ?>"><?php echo esc_html( $space_post->title ); ?></a>
						</h2>
						<p class="zeko-qa-question-excerpt"><?php echo esc_html( wp_trim_words( $space_post->content, 40 ) ); ?></p>
						<div class="zeko-qa-question-footer">
							<span class="zeko-qa-meta-item"><?php echo absint( $space_post->upvotes ); ?> <?php esc_html_e( 'votes', 'zeko-qa' ); ?></span>
							<span class="zeko-qa-meta-divider">·</span>
							<span class="zeko-qa-meta-item"><?php echo absint( $space_post->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?></span>
							<span class="zeko-qa-meta-divider">·</span>
							<span class="zeko-qa-meta-item"><?php echo esc_html( human_time_diff( strtotime( $space_post->question_created ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?></span>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
