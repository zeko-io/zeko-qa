<?php
/**
 * Q&A Dashboard template with advanced UX and SEO
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = get_current_user_id();
if ( ! $current_user_id ) {
	/* translators: %s: login URL */
	echo '<div class="zeko-qa-login-prompt"><p>' . wp_kses_post( sprintf( __( 'Please <a href="%s">login</a> to view your Q&A dashboard.', 'zeko-qa' ), esc_url( wp_login_url() ) ) ) . '</p></div>';
	return;
}

$user_questions = $user_questions ?? array();
$user_answers   = $user_answers ?? array();
$reputation     = $reputation ?? 0;
$notifications  = $notifications ?? array();

$canonical = home_url( '/qa-dashboard/' );
$site_name = get_bloginfo( 'name' );
?>

<div class="zeko-qa-dashboard" role="main" aria-label="<?php esc_attr_e( 'My Q&A Dashboard', 'zeko-qa' ); ?>">
	<header class="zeko-qa-dashboard-header">
		<h1 class="zeko-qa-dashboard-title"><?php esc_html_e( 'My Q&A', 'zeko-qa' ); ?></h1>
		<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="zeko-qa-btn zeko-qa-btn-primary"><?php esc_html_e( 'Ask Question', 'zeko-qa' ); ?></a>
	</header>

	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "WebPage",
		"name": "<?php echo esc_js( __( 'My Q&A', 'zeko-qa' ) ); ?>",
		"description": "<?php /* translators: 1: user display name. 2: site name */ echo esc_js( sprintf( __( '%1$s\'s Q&A activity and reputation on %2$s.', 'zeko-qa' ), get_the_author_meta( 'display_name', $current_user_id ), $site_name ) ); ?>",
		"url": "<?php echo esc_url( $canonical ); ?>",
		"isPartOf": {
			"@type": "WebSite",
			"name": "<?php echo esc_js( $site_name ); ?>",
			"url": "<?php echo esc_url( home_url( '/' ) ); ?>"
		}
	}
	</script>

	<div class="zeko-qa-dashboard-stats" role="region" aria-label="<?php esc_attr_e( 'Statistics', 'zeko-qa' ); ?>">
		<div class="zeko-qa-stat-card">
			<span class="zeko-qa-stat-value" aria-label="<?php /* translators: %d: number of reputation points */ echo esc_attr( sprintf( __( '%d reputation points', 'zeko-qa' ), absint( $reputation ) ) ); ?>"><?php echo absint( $reputation ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Reputation', 'zeko-qa' ); ?></span>
		</div>
		<div class="zeko-qa-stat-card">
			<span class="zeko-qa-stat-value"><?php echo count( $user_questions ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Questions', 'zeko-qa' ); ?></span>
		</div>
		<div class="zeko-qa-stat-card">
			<span class="zeko-qa-stat-value"><?php echo count( $user_answers ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Answers', 'zeko-qa' ); ?></span>
		</div>
		<div class="zeko-qa-stat-card">
			<span class="zeko-qa-stat-value"><?php echo count( $notifications ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Notifications', 'zeko-qa' ); ?></span>
		</div>
	</div>

	<div class="zeko-qa-dashboard-grid">
		<section class="zeko-qa-dashboard-section" aria-labelledby="zeko-qa-my-questions-heading">
			<h2 class="zeko-qa-dashboard-heading" id="zeko-qa-my-questions-heading"><?php esc_html_e( 'My Questions', 'zeko-qa' ); ?></h2>
			<?php if ( empty( $user_questions ) ) : ?>
				<p class="zeko-qa-empty-text"><?php esc_html_e( 'You have not asked any questions yet.', 'zeko-qa' ); ?></p>
			<?php else : ?>
				<ul class="zeko-qa-dashboard-list">
					<?php foreach ( $user_questions as $question ) : ?>
						<li class="zeko-qa-dashboard-item">
							<a href="<?php echo esc_url( home_url( '/questions/' . $question->slug . '/' ) ); ?>" rel="bookmark">
								<?php echo esc_html( $question->title ); ?>
							</a>
							<span class="zeko-qa-dashboard-item-meta">
								<?php echo absint( $question->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="zeko-qa-dashboard-section" aria-labelledby="zeko-qa-my-answers-heading">
			<h2 class="zeko-qa-dashboard-heading" id="zeko-qa-my-answers-heading"><?php esc_html_e( 'My Answers', 'zeko-qa' ); ?></h2>
			<?php if ( empty( $user_answers ) ) : ?>
				<p class="zeko-qa-empty-text"><?php esc_html_e( 'You have not answered any questions yet.', 'zeko-qa' ); ?></p>
			<?php else : ?>
				<ul class="zeko-qa-dashboard-list">
					<?php foreach ( $user_answers as $answer ) : ?>
						<li class="zeko-qa-dashboard-item">
							<a href="<?php echo esc_url( home_url( '/questions/' . $answer->question_slug . '/#answer-' . $answer->id ) ); ?>" rel="bookmark">
								<?php echo esc_html( $answer->question_title ); ?>
							</a>
							<span class="zeko-qa-dashboard-item-meta">
								<?php echo absint( $answer->upvotes ); ?> <?php esc_html_e( 'upvotes', 'zeko-qa' ); ?>
								·
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $answer->created_at ) ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $answer->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
								</time>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="zeko-qa-dashboard-section" aria-labelledby="zeko-qa-notifications-heading">
			<h2 class="zeko-qa-dashboard-heading" id="zeko-qa-notifications-heading"><?php esc_html_e( 'Recent Notifications', 'zeko-qa' ); ?></h2>
			<?php if ( empty( $notifications ) ) : ?>
				<p class="zeko-qa-empty-text"><?php esc_html_e( 'No new notifications.', 'zeko-qa' ); ?></p>
			<?php else : ?>
				<ul class="zeko-qa-dashboard-list">
					<?php foreach ( $notifications as $notification ) : ?>
						<li class="zeko-qa-dashboard-item <?php echo ! $notification->is_read ? 'zeko-qa-unread' : ''; ?>">
							<span><?php echo esc_html( $notification->action ); ?></span>
							<span class="zeko-qa-dashboard-item-meta">
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $notification->created_at ) ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $notification->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
								</time>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>
</div>
