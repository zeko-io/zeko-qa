<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$term_tag       = $args['tag'] ?? null;
$questions = $args['questions'] ?? array();
$canonical = $args['canonical'] ?? null;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$term_tag       = $term_tag ?? null;
$questions = $questions ?? array();

if ( ! $term_tag ) {
	return;
}
?>
<div class="zeko-qa-single-tag">
	<header class="zeko-qa-topic-header">
		<div class="zeko-qa-topic-header-info">
			<h1 class="zeko-qa-topic-name"><?php echo esc_html( $term_tag->name ); ?></h1>
			<?php if ( $term_tag->description ) : ?>
				<p class="zeko-qa-topic-desc"><?php echo esc_html( $term_tag->description ); ?></p>
			<?php endif; ?>
			<div class="zeko-qa-topic-stats">
				<span><?php /* translators: %d: number of questions */ printf( esc_html__( '%d questions', 'zeko-qa' ), absint( $term_tag->question_count ) ); ?></span>
			</div>
		</div>
	</header>

	<div class="zeko-qa-questions-list" role="feed">
		<?php if ( empty( $questions ) ) : ?>
			<div class="zeko-qa-empty-state">
				<p><?php esc_html_e( 'No questions with this tag yet.', 'zeko-qa' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="zeko-qa-btn zeko-qa-btn-primary"><?php esc_html_e( 'Ask the first question', 'zeko-qa' ); ?></a>
			</div>
		<?php else : ?>
			<?php foreach ( $questions as $question ) : ?>
				<article class="zeko-qa-question-card" data-question-id="<?php echo absint( $question->id ); ?>" role="article">
					<div class="zeko-qa-vote-column" aria-label="<?php esc_attr_e( 'Voting', 'zeko-qa' ); ?>">
						<button class="zeko-qa-vote-btn zeko-qa-vote-up" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="up" aria-label="<?php esc_attr_e( 'Upvote', 'zeko-qa' ); ?>">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
						</button>
						<span class="zeko-qa-vote-count" aria-live="polite"><?php echo absint( $question->total_upvotes ); ?></span>
						<button class="zeko-qa-vote-btn zeko-qa-vote-down" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="down" aria-label="<?php esc_attr_e( 'Downvote', 'zeko-qa' ); ?>">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
						</button>
					</div>
					<div class="zeko-qa-content-column">
						<h2 class="zeko-qa-question-title">
							<a href="<?php echo esc_url( home_url( '/questions/' . $question->slug . '/' ) ); ?>" rel="bookmark">
								<?php echo esc_html( $question->title ); ?>
							</a>
						</h2>
						<p class="zeko-qa-question-excerpt"><?php echo esc_html( wp_trim_words( $question->content, 40 ) ); ?></p>
						<div class="zeko-qa-question-meta">
							<span class="zeko-qa-meta-item">
								<?php esc_html_e( 'Asked by', 'zeko-qa' ); ?>
								<?php $q_author = get_userdata( $question->user_id ); ?>
								<a href="<?php echo esc_url( home_url( '/users/' . ( $q_author ? $q_author->user_nicename : '' ) . '/' ) ); ?>" class="zeko-qa-author-link" rel="author">
									<?php echo esc_html( get_the_author_meta( 'display_name', $question->user_id ) ); ?>
								</a>
							</span>
							<span class="zeko-qa-meta-divider" aria-hidden="true">&middot;</span>
							<span class="zeko-qa-meta-item">
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $question->created_at ) ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $question->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
								</time>
							</span>
							<span class="zeko-qa-meta-divider" aria-hidden="true">&middot;</span>
							<span class="zeko-qa-meta-item zeko-qa-answer-badge <?php echo absint( $question->answer_count ) > 0 ? 'zeko-qa-answer-badge--has-answers' : 'zeko-qa-answer-badge--no-answers'; ?>">
								<?php echo absint( $question->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?>
							</span>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
