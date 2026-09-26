<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$user                 = $args['user'] ?? null;
$user_id              = $args['user_id'] ?? 0;
$current_user_id      = $args['current_user_id'] ?? 0;
$active_tab                  = $args['tab'] ?? 'overview';
$stats                = $args['stats'] ?? array();
$expertise_topics     = $args['expertise_topics'] ?? array();
$badges               = $args['badges'] ?? array();
$reputation_breakdown = $args['reputation_breakdown'] ?? array();
$items                = $args['items'] ?? array();
$canonical            = $args['canonical'] ?? null;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = $user ?? null;
if ( ! $user ) {
	return;
}

$active_tab              = isset( $active_tab ) ? sanitize_key( $active_tab ) : 'questions';
$current_user_id  = isset( $current_user_id ) ? absint( $current_user_id ) : get_current_user_id();
$user_id          = isset( $user_id ) ? absint( $user_id ) : $user->ID;
$stats            = isset( $stats ) ? $stats : array(
	'question_count' => 0,
	'answer_count'   => 0,
	'reputation'     => 0,
	'accepted_count' => 0,
);
$items            = isset( $items ) ? $items : array();
$expertise_topics = isset( $expertise_topics ) ? $expertise_topics : array();
$canonical        = isset( $canonical ) ? $canonical : home_url( '/users/' . $user->user_nicename . '/' . $active_tab . '/' );

$display_name         = $user->display_name;
$page_url             = home_url( '/users/' . $user->user_nicename . '/' );
$badges               = isset( $badges ) ? $badges : array();
$reputation_breakdown = isset( $reputation_breakdown ) ? $reputation_breakdown : array();
?>
<div class="zeko-qa-user-profile">
	<header class="zeko-qa-profile-header">
		<div class="zeko-qa-profile-avatar">
			<?php echo get_avatar( $user->ID, 120, '', esc_attr( $display_name ), array( 'class' => 'zeko-qa-avatar-img' ) ); ?>
		</div>
		<div class="zeko-qa-profile-info">
			<h1 class="zeko-qa-profile-name"><?php echo esc_html( $display_name ); ?></h1>
			<?php if ( $user->description ) : ?>
				<p class="zeko-qa-profile-bio"><?php echo esc_html( $user->description ); ?></p>
			<?php endif; ?>
			<p class="zeko-qa-profile-meta">
				<span><?php /* translators: %s: date the member joined */ printf( esc_html__( 'Member since %s', 'zeko-qa' ), esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) ) ); ?></span>
			</p>
		</div>
	</header>

	<div class="zeko-qa-profile-stats-bar">
		<a href="<?php echo esc_url( $page_url ); ?>" class="zeko-qa-stat-item <?php echo 'questions' === $active_tab ? 'active' : ''; ?>">
			<span class="zeko-qa-stat-number"><?php echo absint( $stats['question_count'] ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Questions', 'zeko-qa' ); ?></span>
		</a>
		<a href="<?php echo esc_url( $page_url . 'answers/' ); ?>" class="zeko-qa-stat-item <?php echo 'answers' === $active_tab ? 'active' : ''; ?>">
			<span class="zeko-qa-stat-number"><?php echo absint( $stats['answer_count'] ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Answers', 'zeko-qa' ); ?></span>
		</a>
		<div class="zeko-qa-stat-item">
			<span class="zeko-qa-stat-number"><?php echo absint( $stats['reputation'] ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Reputation', 'zeko-qa' ); ?></span>
		</div>
		<div class="zeko-qa-stat-item">
			<span class="zeko-qa-stat-number"><?php echo absint( $stats['accepted_count'] ); ?></span>
			<span class="zeko-qa-stat-label"><?php esc_html_e( 'Accepted', 'zeko-qa' ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $badges ) ) : ?>
		<div class="zeko-qa-profile-section">
			<h2 class="zeko-qa-section-title"><?php esc_html_e( 'Badges', 'zeko-qa' ); ?></h2>
			<div class="zeko-qa-profile-badges">
				<?php foreach ( $badges as $badge ) : ?>
					<div class="zeko-qa-badge-item" title="<?php echo esc_attr( $badge->description ); ?>">
						<span class="zeko-qa-badge-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#b92b27" stroke-width="2" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
						</span>
						<span class="zeko-qa-badge-name"><?php echo esc_html( $badge->name ); ?></span>
						<span class="zeko-qa-badge-date"><?php echo esc_html( human_time_diff( strtotime( $badge->awarded_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $reputation_breakdown ) ) : ?>
		<div class="zeko-qa-profile-section">
			<h2 class="zeko-qa-section-title"><?php esc_html_e( 'Reputation Breakdown', 'zeko-qa' ); ?></h2>
			<div class="zeko-qa-reputation-breakdown">
				<?php
				$reason_labels = array(
					'upvote_received' => __( 'Upvotes received', 'zeko-qa' ),
					'upvote_removed'  => __( 'Upvotes removed', 'zeko-qa' ),
					'answer_accepted' => __( 'Answers accepted', 'zeko-qa' ),
				);
				foreach ( $reputation_breakdown as $row ) :
					$label = isset( $reason_labels[ $row->reason ] ) ? $reason_labels[ $row->reason ] : $row->reason;
					?>
					<div class="zeko-qa-reputation-row">
						<span class="zeko-qa-reputation-reason"><?php echo esc_html( $label ); ?></span>
						<span class="zeko-qa-reputation-count"><?php echo absint( $row->count ); ?>x</span>
						<span class="zeko-qa-reputation-points <?php echo $row->total_points >= 0 ? 'zeko-qa-points-positive' : 'zeko-qa-points-negative'; ?>">
							<?php echo $row->total_points >= 0 ? '+' . absint( $row->total_points ) : '-' . absint( $row->total_points ); ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $expertise_topics ) ) : ?>
		<div class="zeko-qa-profile-section">
			<h2 class="zeko-qa-section-title"><?php esc_html_e( 'Topics of Expertise', 'zeko-qa' ); ?></h2>
			<div class="zeko-qa-profile-topics">
				<?php foreach ( $expertise_topics as $topic ) : ?>
					<a href="<?php echo esc_url( home_url( '/topics/' . $topic->slug . '/' ) ); ?>" class="zeko-qa-topic-chip">
						<?php echo esc_html( $topic->name ); ?>
						<span class="zeko-qa-topic-count"><?php echo absint( $topic->answer_count ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="zeko-qa-profile-section">
		<div class="zeko-qa-profile-tabs" role="tablist">
			<a href="<?php echo esc_url( $page_url ); ?>" class="zeko-qa-profile-tab <?php echo 'questions' === $active_tab ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo 'questions' === $active_tab ? 'true' : 'false'; ?>">
				<?php esc_html_e( 'Questions', 'zeko-qa' ); ?>
			</a>
			<a href="<?php echo esc_url( $page_url . 'answers/' ); ?>" class="zeko-qa-profile-tab <?php echo 'answers' === $active_tab ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo 'answers' === $active_tab ? 'true' : 'false'; ?>">
				<?php esc_html_e( 'Answers', 'zeko-qa' ); ?>
			</a>
			<?php if ( $current_user_id === $user_id ) : ?>
				<a href="<?php echo esc_url( $page_url . 'bookmarks/' ); ?>" class="zeko-qa-profile-tab <?php echo 'bookmarks' === $active_tab ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo 'bookmarks' === $active_tab ? 'true' : 'false'; ?>">
					<?php esc_html_e( 'Bookmarks', 'zeko-qa' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( empty( $items ) ) : ?>
			<div class="zeko-qa-empty-state">
				<?php if ( 'answers' === $active_tab ) : ?>
					<p><?php esc_html_e( 'No answers yet.', 'zeko-qa' ); ?></p>
				<?php elseif ( 'bookmarks' === $active_tab ) : ?>
					<p><?php esc_html_e( 'No bookmarks yet.', 'zeko-qa' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'No questions yet.', 'zeko-qa' ); ?></p>
				<?php endif; ?>
			</div>
		<?php elseif ( 'answers' === $active_tab ) : ?>
			<?php foreach ( $items as $answer ) : ?>
				<article class="zeko-qa-profile-answer-item">
					<div class="zeko-qa-profile-answer-votes">
						<span class="zeko-qa-vote-count <?php echo absint( $answer->total_upvotes ) > 0 ? 'zeko-qa-vote-positive' : ''; ?>"><?php echo absint( $answer->total_upvotes ); ?></span>
						<span class="zeko-qa-vote-label"><?php esc_html_e( 'votes', 'zeko-qa' ); ?></span>
						<?php if ( $answer->is_accepted ) : ?>
							<span class="zeko-qa-accepted-badge" title="<?php esc_attr_e( 'Accepted answer', 'zeko-qa' ); ?>">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="#0d652d" stroke="none" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
							</span>
						<?php endif; ?>
					</div>
					<div class="zeko-qa-profile-answer-content">
						<h3 class="zeko-qa-profile-answer-question">
							<a href="<?php echo esc_url( home_url( '/questions/' . $answer->question_slug . '/' ) ); ?>">
								<?php echo esc_html( $answer->question_title ); ?>
							</a>
						</h3>
						<div class="zeko-qa-profile-answer-excerpt">
							<?php echo esc_html( wp_trim_words( $answer->content, 40 ) ); ?>
						</div>
						<div class="zeko-qa-profile-item-meta">
							<time datetime="<?php echo esc_attr( mysql2date( 'c', $answer->created_at ) ); ?>">
								<?php echo esc_html( human_time_diff( strtotime( $answer->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
							</time>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		<?php else : ?>
			<?php foreach ( $items as $question ) : ?>
					<article class="zeko-qa-profile-question-item">
						<div class="zeko-qa-profile-question-votes">
							<span class="zeko-qa-vote-count <?php echo absint( $question->total_upvotes ) > 0 ? 'zeko-qa-vote-positive' : ''; ?>"><?php echo absint( $question->total_upvotes ); ?></span>
<span class="zeko-qa-vote-label"><?php esc_html_e( 'votes', 'zeko-qa' ); ?></span>
						</div>
						<div class="zeko-qa-profile-question-content">
							<h3 class="zeko-qa-profile-question-title">
								<a href="<?php echo esc_url( home_url( '/questions/' . $question->slug . '/' ) ); ?>">
									<?php echo esc_html( $question->title ); ?>
								</a>
							</h3>
							<div class="zeko-qa-profile-question-excerpt">
								<?php echo esc_html( wp_trim_words( $question->content, 40 ) ); ?>
							</div>
							<div class="zeko-qa-profile-item-meta">
								<span class="zeko-qa-answer-badge <?php echo absint( $question->answer_count ) > 0 ? 'zeko-qa-answer-badge--has-answers' : 'zeko-qa-answer-badge--no-answers'; ?>">
									<?php echo absint( $question->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?>
								</span>
								<span class="zeko-qa-meta-divider" aria-hidden="true">&middot;</span>
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $question->created_at ) ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $question->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
								</time>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "ProfilePage",
		"name": "<?php echo esc_js( $display_name ); ?> — Q&A Profile",
		"url": "<?php echo esc_url( $canonical ); ?>",
		"mainEntity": {
			"@type": "Person",
			"name": "<?php echo esc_js( $display_name ); ?>",
			"dateCreated": "<?php echo esc_js( mysql2date( 'c', $user->user_registered ) ); ?>"
		}
	}
	</script>
</div>
