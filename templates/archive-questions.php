<?php
/**
 * Archive questions template with advanced UX, SEO, and accessibility
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$filter   = isset( $atts['filter'] ) ? sanitize_key( $atts['filter'] ) : 'trending';
$search_query   = isset( $atts['search'] ) ? sanitize_text_field( $atts['search'] ) : '';
$topic_id = isset( $atts['topic_id'] ) ? absint( $atts['topic_id'] ) : 0;

$user_id   = get_current_user_id();
$page_url  = home_url( '/questions/' );
$canonical = add_query_arg(
	array(
		'filter'   => $filter,
		's'        => $search_query,
		'topic_id' => $topic_id,
	),
	$page_url
);
$site_name = get_bloginfo( 'name' );

$questions  = $questions ?? array();
$user_votes = $user_votes ?? array();
$atts       = $atts ?? array();
?>

<div class="zeko-qa-archive" role="main" aria-label="<?php esc_attr_e( 'Questions archive', 'zeko-qa' ); ?>">
	<div class="zeko-qa-archive-header">
		<h1 class="zeko-qa-archive-title"><?php esc_html_e( 'Questions', 'zeko-qa' ); ?></h1>
		<div class="zeko-qa-archive-actions">
			<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="zeko-qa-btn zeko-qa-btn-primary"><?php esc_html_e( 'Ask Question', 'zeko-qa' ); ?></a>
		</div>
	</div>
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "CollectionPage",
		"name": "<?php echo esc_js( __( 'Questions', 'zeko-qa' ) ); ?>",
		"description": "<?php echo esc_js( __( 'Browse questions and answers from the community.', 'zeko-qa' ) ); ?>",
		"url": "<?php echo esc_url( $canonical ); ?>",
		"isPartOf": {
			"@type": "WebSite",
			"name": "<?php echo esc_js( $site_name ); ?>",
			"url": "<?php echo esc_url( home_url( '/' ) ); ?>"
		},
		"mainEntity": {
			"@type": "ItemList",
			"numberOfItems": <?php echo count( $questions ); ?>
		}
	}
	</script>

	<nav class="zeko-qa-archive-toolbar" aria-label="<?php esc_attr_e( 'Question filters', 'zeko-qa' ); ?>">
		<div class="zeko-qa-archive-filters" role="tablist">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'filter'   => 'feed',
							's'        => '',
							'topic_id' => 0,
						),
						$page_url
					)
				);
				?>
							" 
					class="zeko-qa-filter-link <?php echo 'feed' === $filter ? 'active' : ''; ?>" 
					role="tab" 
					aria-selected="<?php echo 'feed' === $filter ? 'true' : 'false'; ?>">
					<?php esc_html_e( 'Feed', 'zeko-qa' ); ?>
				</a>
			<?php endif; ?>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'filter'   => 'trending',
						's'        => '',
						'topic_id' => 0,
					),
					$page_url
				)
			);
			?>
			" 
				class="zeko-qa-filter-link <?php echo 'trending' === $filter ? 'active' : ''; ?>" 
				role="tab" 
				aria-selected="<?php echo 'trending' === $filter ? 'true' : 'false'; ?>">
				<?php esc_html_e( 'Trending', 'zeko-qa' ); ?>
			</a>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'filter'   => 'unanswered',
						's'        => '',
						'topic_id' => 0,
					),
					$page_url
				)
			);
			?>
			" 
				class="zeko-qa-filter-link <?php echo 'unanswered' === $filter ? 'active' : ''; ?>" 
				role="tab" 
				aria-selected="<?php echo 'unanswered' === $filter ? 'true' : 'false'; ?>">
				<?php esc_html_e( 'Unanswered', 'zeko-qa' ); ?>
			</a>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'filter'   => 'newest',
						's'        => '',
						'topic_id' => 0,
					),
					$page_url
				)
			);
			?>
			" 
				class="zeko-qa-filter-link <?php echo 'newest' === $filter ? 'active' : ''; ?>" 
				role="tab" 
				aria-selected="<?php echo 'newest' === $filter ? 'true' : 'false'; ?>">
				<?php esc_html_e( 'Newest', 'zeko-qa' ); ?>
			</a>
		</div>
		<form class="zeko-qa-search-form" method="get" action="<?php echo esc_url( $page_url ); ?>" role="search">
			<label for="zeko_qa_search_input" class="zeko-qa-sr-only"><?php esc_html_e( 'Search questions', 'zeko-qa' ); ?></label>
			<input 
				type="text" 
				id="zeko_qa_search_input" 
				name="s" 
				value="<?php echo esc_attr( $search_query ); ?>" 
				placeholder="<?php esc_attr_e( 'Search questions...', 'zeko-qa' ); ?>" 
				class="zeko-qa-search-input" 
				aria-label="<?php esc_attr_e( 'Search questions', 'zeko-qa' ); ?>"
			/>
			<input type="hidden" name="filter" value="search" />
			<button type="submit" class="zeko-qa-btn zeko-qa-btn-secondary"><?php esc_html_e( 'Search', 'zeko-qa' ); ?></button>
		</form>
	</nav>

	<div class="zeko-qa-questions-list" role="feed" aria-busy="false">
		<?php if ( empty( $questions ) ) : ?>
			<div class="zeko-qa-empty-state" role="status">
				<p><?php esc_html_e( 'No questions found.', 'zeko-qa' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/ask-a-question/' ) ); ?>" class="zeko-qa-btn zeko-qa-btn-primary"><?php esc_html_e( 'Ask the first question', 'zeko-qa' ); ?></a>
			</div>
		<?php else : ?>
			<?php
			foreach ( $questions as $question ) :
				$user_vote = isset( $user_votes[ $question->id ] ) ? $user_votes[ $question->id ] : '';
				?>
				<article class="zeko-qa-question-card" data-question-id="<?php echo absint( $question->id ); ?>" role="article">
					<div class="zeko-qa-vote-column" aria-label="<?php esc_attr_e( 'Voting', 'zeko-qa' ); ?>">
						<button class="zeko-qa-vote-btn zeko-qa-vote-up <?php echo 'up' === $user_vote ? 'zeko-qa-voted' : ''; ?>" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="up" aria-label="<?php esc_attr_e( 'Upvote', 'zeko-qa' ); ?>" aria-pressed="<?php echo 'up' === $user_vote ? 'true' : 'false'; ?>">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
						</button>
						<span class="zeko-qa-vote-count" aria-live="polite" aria-atomic="true"><?php echo absint( $question->upvotes ) - absint( $question->downvotes ); ?></span>
						<button class="zeko-qa-vote-btn zeko-qa-vote-down <?php echo 'down' === $user_vote ? 'zeko-qa-voted' : ''; ?>" data-id="<?php echo absint( $question->id ); ?>" data-type="question" data-vote="down" aria-label="<?php esc_attr_e( 'Downvote', 'zeko-qa' ); ?>" aria-pressed="<?php echo 'down' === $user_vote ? 'true' : 'false'; ?>">
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
								<?php $question_author = get_userdata( $question->user_id ); ?>
								<a href="<?php echo esc_url( home_url( '/users/' . ( $question_author ? $question_author->user_nicename : '' ) . '/' ) ); ?>" class="zeko-qa-author-link" rel="author">
									<?php echo esc_html( get_the_author_meta( 'display_name', $question->user_id ) ); ?>
								</a>
							</span>
							<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
							<span class="zeko-qa-meta-item">
								<time datetime="<?php echo esc_attr( mysql2date( 'c', $question->created_at ) ); ?>" title="<?php echo esc_attr( mysql2date( get_option( 'date_format' ), $question->created_at ) ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $question->created_at ), time() ) ); ?> <?php esc_html_e( 'ago', 'zeko-qa' ); ?>
								</time>
							</span>
							<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
							<span class="zeko-qa-meta-item zeko-qa-answer-badge <?php echo absint( $question->answer_count ) > 0 ? 'zeko-qa-answer-badge--has-answers' : 'zeko-qa-answer-badge--no-answers'; ?>">
								<?php echo absint( $question->answer_count ); ?> <?php esc_html_e( 'answers', 'zeko-qa' ); ?>
							</span>
							<span class="zeko-qa-meta-divider" aria-hidden="true">·</span>
							<span class="zeko-qa-meta-item"><?php echo absint( $question->views ); ?> <?php esc_html_e( 'views', 'zeko-qa' ); ?></span>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
