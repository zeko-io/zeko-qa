<?php
/**
 * Single question template with advanced UX, SEO, and accessibility
 *
 * @package Zeko_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$question        = $args['question'] ?? null;
$answers         = $args['answers'] ?? array();
$question_topics = $args['question_topics'] ?? array();
$question_tags   = $args['question_tags'] ?? array();

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = get_current_user_id();
$question        = $question ?? null;
$answers         = $answers ?? array();

if ( ! $question ) {
	return;
}

$is_owner             = $current_user_id && absint( $question->user_id ) === $current_user_id;
$is_bookmarked        = $current_user_id ? Zeko_QA::instance()->get_db()->is_bookmarked( $current_user_id, $question->id ) : false;
$is_question_followed = $current_user_id ? Zeko_QA::instance()->get_db()->is_question_followed( $question->id, $current_user_id ) : false;
$can_answer           = is_user_logged_in();

$accepted_answer = null;
$other_answers   = array();

foreach ( $answers as $answer ) {
	if ( $answer->is_accepted ) {
		$accepted_answer = $answer;
	} else {
		$other_answers[] = $answer;
	}
}

usort(
	$other_answers,
	function ( $a, $b ) {
		$score_a = absint( $a->upvotes ) - absint( $a->downvotes );
		$score_b = absint( $b->upvotes ) - absint( $b->downvotes );
		if ( $score_a === $score_b ) {
			return strtotime( $b->created_at ) - strtotime( $a->created_at );
		}
		return $score_b - $score_a;
	}
);

$question_url       = home_url( '/questions/' . $question->slug . '/' );
$site_name          = get_bloginfo( 'name' );
$author             = get_userdata( $question->user_id );
$author_name        = $author ? $author->display_name : __( 'Anonymous', 'zeko-qa' );
$author_profile_url = $author ? home_url( '/users/' . $author->user_nicename . '/' ) : '#';

$question_topics = isset( $question_topics ) ? $question_topics : array();
$question_tags   = isset( $question_tags ) ? $question_tags : array();
?>

<div class="zeko-qa-single" role="main" aria-label="<?php /* translators: %s: question title */ echo esc_attr( sprintf( __( 'Question: %s', 'zeko-qa' ), $question->title ) ); ?>">
	<article class="zeko-qa-question-single" data-question-id="<?php echo absint( $question->id ); ?>" itemscope itemtype="https://schema.org/Question">
		<div class="zeko-qa-vote-column" aria-label="<?php esc_attr_e( 'Voting', 'zeko-qa' ); ?>">
			<button class="zeko-qa-vote-btn zeko-qa-vote-up" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="up" aria-label="<?php esc_attr_e( 'Upvote question', 'zeko-qa' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
			</button>
			<span class="zeko-qa-vote-count" aria-live="polite" aria-atomic="true" itemprop="upvoteCount"><?php echo absint( $question->upvotes ) - absint( $question->downvotes ); ?></span>
			<button class="zeko-qa-vote-btn zeko-qa-vote-down" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="down" aria-label="<?php esc_attr_e( 'Downvote question', 'zeko-qa' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
			</button>
		</div>
		<div class="zeko-qa-content-column">
			<h1 class="zeko-qa-question-title" itemprop="name"><?php echo esc_html( $question->title ); ?></h1>
			<div class="zeko-qa-question-body" itemprop="text"><?php echo wp_kses_post( $question->content ); ?></div>
			
			<?php if ( ! empty( $question_topics ) || ! empty( $question_tags ) ) : ?>
				<div class="zeko-qa-question-taxonomy">
					<?php if ( ! empty( $question_topics ) ) : ?>
						<div class="zeko-qa-taxonomy-group">
							<span class="zeko-qa-taxonomy-label"><?php esc_html_e( 'Categories', 'zeko-qa' ); ?></span>
							<div class="zeko-qa-taxonomy-list">
								<?php foreach ( $question_topics as $topic ) : ?>
									<a href="<?php echo esc_url( home_url( '/questions/?topic_id=' . $topic->id ) ); ?>" class="zeko-qa-topic-chip"><?php echo esc_html( $topic->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $question_tags ) ) : ?>
						<div class="zeko-qa-taxonomy-group">
							<span class="zeko-qa-taxonomy-label"><?php esc_html_e( 'Tags', 'zeko-qa' ); ?></span>
							<div class="zeko-qa-taxonomy-list">
								<?php foreach ( $question_tags as $term_tag ) : ?>
									<a href="<?php echo esc_url( home_url( '/questions/?tag=' . $term_tag->slug ) ); ?>" class="zeko-qa-tag-chip"><?php echo esc_html( $term_tag->name ); ?></a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			
			<div class="zeko-qa-question-footer">
				<div class="zeko-qa-question-meta">
					<span class="zeko-qa-meta-item">
						<?php esc_html_e( 'Asked by', 'zeko-qa' ); ?>
						<a href="<?php echo esc_url( $author_profile_url ); ?>" class="zeko-qa-author-link" rel="author" itemprop="author">
							<?php echo esc_html( $author_name ); ?>
						</a>
					</span>
					<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
					<span class="zeko-qa-meta-item">
						<time datetime="<?php echo esc_attr( mysql2date( 'c', $question->created_at ) ); ?>" itemprop="dateCreated">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ), $question->created_at ) ); ?>
						</time>
					</span>
					<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
					<span class="zeko-qa-meta-item"><?php echo absint( $question->views ); ?> <?php esc_html_e( 'views', 'zeko-qa' ); ?></span>
				</div>
				<div class="zeko-qa-question-actions">
					<?php if ( is_user_logged_in() ) : ?>
						<button class="zeko-qa-question-follow-btn <?php echo $is_question_followed ? 'zeko-qa-btn-secondary' : 'zeko-qa-btn-primary'; ?>" data-question-id="<?php echo absint( $question->id ); ?>">
							<?php echo $is_question_followed ? esc_html__( 'Following', 'zeko-qa' ) : esc_html__( 'Follow', 'zeko-qa' ); ?>
						</button>
						<button class="zeko-qa-bookmark-btn <?php echo $is_bookmarked ? 'zeko-qa-bookmarked' : ''; ?>" data-question-id="<?php echo absint( $question->id ); ?>" aria-pressed="<?php echo $is_bookmarked ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Bookmark question', 'zeko-qa' ); ?>">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $is_bookmarked ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
							<span><?php echo $is_bookmarked ? esc_html__( 'Saved', 'zeko-qa' ) : esc_html__( 'Save', 'zeko-qa' ); ?></span>
						</button>
					<?php endif; ?>
					<?php if ( $is_owner ) : ?>
						<button class="zeko-qa-btn zeko-qa-btn-secondary zeko-qa-edit-question" data-question-id="<?php echo absint( $question->id ); ?>" aria-label="<?php esc_attr_e( 'Edit question', 'zeko-qa' ); ?>">
							<?php esc_html_e( 'Edit', 'zeko-qa' ); ?>
						</button>
					<?php endif; ?>
					<button class="zeko-qa-btn zeko-qa-btn-secondary zeko-qa-report-question" data-question-id="<?php echo absint( $question->id ); ?>" aria-label="<?php esc_attr_e( 'Report question', 'zeko-qa' ); ?>">
						<?php esc_html_e( 'Report', 'zeko-qa' ); ?>
					</button>
					</div>
					<?php if ( $accepted_answer ) : ?>
					<div class="zeko-qa-comments-section">
						<?php
						$accepted_comments      = Zeko_QA::instance()->get_db()->get_comments( $accepted_answer->id, 'answer' );
						$accepted_comment_count = count( $accepted_comments );
						?>
						<button class="zeko-qa-toggle-comments" type="button">
							<?php /* translators: %s: number of comments */ printf( esc_html( _n( '%s comment', '%s comments', $accepted_comment_count, 'zeko-qa' ) ), absint( $accepted_comment_count ) ); ?>
						</button>
						<div class="zeko-qa-comments-list" style="display:none;">
							<?php foreach ( $accepted_comments as $comment_item ) : ?>
								<div class="zeko-qa-comment-item" data-comment-id="<?php echo absint( $comment_item->id ); ?>">
									<a href="<?php echo esc_url( home_url( '/users/' . get_the_author_meta( 'user_nicename', $comment_item->user_id ) . '/' ) ); ?>" class="zeko-qa-comment-author">
										<?php echo esc_html( $comment_item->author_name ); ?>
									</a>
									<span class="zeko-qa-comment-text"><?php echo esc_html( $comment_item->content ); ?></span>
									<span class="zeko-qa-comment-time"><?php echo esc_html( human_time_diff( strtotime( $comment_item->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?></span>
									<?php if ( get_current_user_id() === absint( $comment_item->user_id ) ) : ?>
										<button class="zeko-qa-comment-delete" data-comment-id="<?php echo absint( $comment_item->id ); ?>" aria-label="<?php esc_attr_e( 'Delete comment', 'zeko-qa' ); ?>">&times;</button>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<?php if ( is_user_logged_in() ) : ?>
							<form class="zeko-qa-comment-form" data-item-id="<?php echo absint( $accepted_answer->id ); ?>" data-item-type="answer" style="display:none;">
								<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'zeko_qa_public_nonce' ) ); ?>" />
								<input type="text" name="comment_content" placeholder="<?php esc_attr_e( 'Add a comment...', 'zeko-qa' ); ?>" maxlength="2000" class="zeko-qa-comment-input" />
								<button type="submit" class="zeko-qa-btn zeko-qa-btn-primary zeko-qa-comment-submit"><?php esc_html_e( 'Post', 'zeko-qa' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</div>
			</article>

	<?php if ( $can_answer ) : ?>
		<div class="zeko-qa-answer-form-wrapper" aria-labelledby="zeko-qa-answer-form-title">
			<h2 class="zeko-qa-answer-form-title" id="zeko-qa-answer-form-title"><?php esc_html_e( 'Your Answer', 'zeko-qa' ); ?></h2>
			<form class="zeko-qa-answer-form" data-question-id="<?php echo absint( $question->id ); ?>" novalidate>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'zeko_qa_public_nonce' ) ); ?>" />
				<label class="zeko-qa-sr-only"><?php esc_html_e( 'Write your answer', 'zeko-qa' ); ?></label>
				<div class="zeko-qa-editor-wrapper">
					<div id="zeko_qa_answer_editor"></div>
					<textarea
						id="zeko_qa_answer_content"
						name="content"
						class="zeko-qa-answer-textarea zeko-qa-hidden"
						required
						aria-describedby="zeko_qa_answer_help"
					></textarea>
				</div>
				<div class="zeko-qa-form-help" id="zeko_qa_answer_help">
					<?php esc_html_e( 'Be specific, cite sources if possible, and explain your reasoning.', 'zeko-qa' ); ?>
				</div>
				<div class="zeko-qa-form-actions">
					<button type="submit" class="zeko-qa-btn zeko-qa-btn-primary zeko-qa-submit-btn">
						<span class="zeko-qa-btn-text"><?php esc_html_e( 'Submit Answer', 'zeko-qa' ); ?></span>
						<span class="zeko-qa-btn-loading" aria-hidden="true"><?php esc_html_e( 'Submitting...', 'zeko-qa' ); ?></span>
					</button>
					<span class="zeko-qa-form-message" role="status" aria-live="polite"></span>
				</div>
			</form>
		</div>
	<?php else : ?>
		<div class="zeko-qa-login-prompt" role="status">
			<p><?php /* translators: %s: login URL */ echo wp_kses_post( sprintf( __( 'Please <a href="%s">login</a> to answer this question.', 'zeko-qa' ), esc_url( wp_login_url() ) ) ); ?></p>
		</div>
	<?php endif; ?>

	<div class="zeko-qa-answers-section" aria-labelledby="zeko-qa-answers-heading">
		<h2 class="zeko-qa-answers-heading" id="zeko-qa-answers-heading">
			<?php /* translators: %d: number of answers */ printf( esc_html( _n( '%d Answer', '%d Answers', count( $answers ), 'zeko-qa' ) ), absint( count( $answers ) ) ); ?>
		</h2>

		<?php if ( $accepted_answer ) : ?>
			<article class="zeko-qa-answer-card zeko-qa-answer-accepted" data-answer-id="<?php echo absint( $accepted_answer->id ); ?>" itemscope itemtype="https://schema.org/Answer">
				<div class="zeko-qa-vote-column" aria-label="<?php esc_attr_e( 'Voting', 'zeko-qa' ); ?>">
					<button class="zeko-qa-vote-btn zeko-qa-vote-up" data-id="<?php echo absint( $accepted_answer->id ); ?>" data-type="answer" data-vote="up" aria-label="<?php esc_attr_e( 'Upvote', 'zeko-qa' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
					</button>
					<span class="zeko-qa-vote-count" aria-live="polite" aria-atomic="true"><?php echo absint( $accepted_answer->upvotes ) - absint( $accepted_answer->downvotes ); ?></span>
					<button class="zeko-qa-vote-btn zeko-qa-vote-down" data-id="<?php echo absint( $accepted_answer->id ); ?>" data-type="answer" data-vote="down" aria-label="<?php esc_attr_e( 'Downvote', 'zeko-qa' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
					</button>
				</div>
				<div class="zeko-qa-content-column">
					<div class="zeko-qa-answer-body" itemprop="text"><?php echo wp_kses_post( $accepted_answer->content ); ?></div>
					<div class="zeko-qa-answer-footer">
						<span class="zeko-qa-accepted-badge"><?php esc_html_e( 'Accepted Answer', 'zeko-qa' ); ?></span>
						<a href="<?php echo esc_url( home_url( '/users/' . get_the_author_meta( 'user_nicename', $accepted_answer->user_id ) . '/' ) ); ?>" class="zeko-qa-author-link" rel="author">
							<?php echo esc_html( get_the_author_meta( 'display_name', $accepted_answer->user_id ) ); ?>
						</a>
						<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
						<span class="zeko-qa-meta-item">
							<time datetime="<?php echo esc_attr( mysql2date( 'c', $accepted_answer->created_at ) ); ?>">
								<?php echo esc_html( mysql2date( get_option( 'date_format' ), $accepted_answer->created_at ) ); ?>
							</time>
						</span>
					</div>
				</div>
			</article>
		<?php endif; ?>

		<?php foreach ( $other_answers as $answer ) : ?>
			<article class="zeko-qa-answer-card" data-answer-id="<?php echo absint( $answer->id ); ?>" itemscope itemtype="https://schema.org/Answer">
				<div class="zeko-qa-vote-column" aria-label="<?php esc_attr_e( 'Voting', 'zeko-qa' ); ?>">
					<button class="zeko-qa-vote-btn zeko-qa-vote-up" data-id="<?php echo absint( $answer->id ); ?>" data-type="answer" data-vote="up" aria-label="<?php esc_attr_e( 'Upvote', 'zeko-qa' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
					</button>
					<span class="zeko-qa-vote-count" aria-live="polite" aria-atomic="true"><?php echo absint( $answer->upvotes ) - absint( $answer->downvotes ); ?></span>
					<button class="zeko-qa-vote-btn zeko-qa-vote-down" data-id="<?php echo absint( $answer->id ); ?>" data-type="answer" data-vote="down" aria-label="<?php esc_attr_e( 'Downvote', 'zeko-qa' ); ?>">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
					</button>
				</div>
				<div class="zeko-qa-content-column">
					<div class="zeko-qa-answer-body" itemprop="text"><?php echo wp_kses_post( $answer->content ); ?></div>
					<div class="zeko-qa-answer-footer">
						<a href="<?php echo esc_url( home_url( '/users/' . get_the_author_meta( 'user_nicename', $answer->user_id ) . '/' ) ); ?>" class="zeko-qa-author-link" rel="author" itemprop="author">
							<?php echo esc_html( get_the_author_meta( 'display_name', $answer->user_id ) ); ?>
						</a>
						<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
						<span class="zeko-qa-meta-item">
							<time datetime="<?php echo esc_attr( mysql2date( 'c', $answer->created_at ) ); ?>" itemprop="dateCreated">
								<?php echo esc_html( mysql2date( get_option( 'date_format' ), $answer->created_at ) ); ?>
							</time>
						</span>
						<?php if ( $is_owner ) : ?>
							<button class="zeko-qa-btn zeko-qa-btn-sm zeko-qa-accept-answer" data-answer-id="<?php echo absint( $answer->id ); ?>" data-question-id="<?php echo absint( $question->id ); ?>" aria-label="<?php esc_attr_e( 'Accept this answer', 'zeko-qa' ); ?>">
								<?php esc_html_e( 'Accept', 'zeko-qa' ); ?>
							</button>
						<?php endif; ?>
					</div>
					<div class="zeko-qa-comments-section">
						<?php
						$answer_comments      = Zeko_QA::instance()->get_db()->get_comments( $answer->id, 'answer' );
						$answer_comment_count = count( $answer_comments );
						?>
						<button class="zeko-qa-toggle-comments" type="button">
							<?php /* translators: %s: number of comments */ printf( esc_html( _n( '%s comment', '%s comments', $answer_comment_count, 'zeko-qa' ) ), absint( $answer_comment_count ) ); ?>
						</button>
						<div class="zeko-qa-comments-list" style="display:none;">
							<?php foreach ( $answer_comments as $comment_item ) : ?>
								<div class="zeko-qa-comment-item" data-comment-id="<?php echo absint( $comment_item->id ); ?>">
									<a href="<?php echo esc_url( home_url( '/users/' . get_the_author_meta( 'user_nicename', $comment_item->user_id ) . '/' ) ); ?>" class="zeko-qa-comment-author">
										<?php echo esc_html( $comment_item->author_name ); ?>
									</a>
									<span class="zeko-qa-comment-text"><?php echo esc_html( $comment_item->content ); ?></span>
									<span class="zeko-qa-comment-time"><?php echo esc_html( human_time_diff( strtotime( $comment_item->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?></span>
									<?php if ( get_current_user_id() === absint( $comment_item->user_id ) ) : ?>
										<button class="zeko-qa-comment-delete" data-comment-id="<?php echo absint( $comment_item->id ); ?>" aria-label="<?php esc_attr_e( 'Delete comment', 'zeko-qa' ); ?>">&times;</button>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
						<?php if ( is_user_logged_in() ) : ?>
							<form class="zeko-qa-comment-form" data-item-id="<?php echo absint( $answer->id ); ?>" data-item-type="answer" style="display:none;">
								<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'zeko_qa_public_nonce' ) ); ?>" />
								<input type="text" name="comment_content" placeholder="<?php esc_attr_e( 'Add a comment...', 'zeko-qa' ); ?>" maxlength="2000" class="zeko-qa-comment-input" />
								<button type="submit" class="zeko-qa-btn zeko-qa-btn-primary zeko-qa-comment-submit"><?php esc_html_e( 'Post', 'zeko-qa' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>

<script type="application/ld+json">
{
	"@context": "https://schema.org",
	"@type": "QAPage",
	"mainEntity": {
		"@type": "Question",
		"name": "<?php echo esc_js( $question->title ); ?>",
		"text": "<?php echo esc_js( wp_strip_all_tags( $question->content ) ); ?>",
		"url": "<?php echo esc_url( $question_url ); ?>",
		"upvoteCount": <?php echo absint( $question->upvotes ); ?>,
		"dateCreated": "<?php echo esc_attr( mysql2date( 'c', $question->created_at ) ); ?>",
		"author": {
			"@type": "Person",
			"name": "<?php echo esc_js( $author_name ); ?>"
		},
		"answerCount": <?php echo count( $answers ); ?>,
		<?php if ( $accepted_answer ) : ?>
		"acceptedAnswer": {
			"@type": "Answer",
			"text": "<?php echo esc_js( wp_strip_all_tags( $accepted_answer->content ) ); ?>",
			"upvoteCount": <?php echo absint( $accepted_answer->upvotes ); ?>,
			"dateCreated": "<?php echo esc_attr( mysql2date( 'c', $accepted_answer->created_at ) ); ?>",
			"author": {
				"@type": "Person",
				"name": "<?php echo esc_js( get_the_author_meta( 'display_name', $accepted_answer->user_id ) ); ?>"
			}
		},
		<?php endif; ?>
		"suggestedAnswer": [
			<?php foreach ( $other_answers as $idx => $answer ) : ?>
			{
				"@type": "Answer",
				"text": "<?php echo esc_js( wp_strip_all_tags( $answer->content ) ); ?>",
				"upvoteCount": <?php echo absint( $answer->upvotes ); ?>,
				"dateCreated": "<?php echo esc_attr( mysql2date( 'c', $answer->created_at ) ); ?>",
				"author": {
					"@type": "Person",
					"name": "<?php echo esc_js( get_the_author_meta( 'display_name', $answer->user_id ) ); ?>"
				}
			}<?php echo $idx < count( $other_answers ) - 1 ? ',' : ''; ?>
			<?php endforeach; ?>
		]
	}
}
</script>
