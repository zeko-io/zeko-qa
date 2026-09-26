<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$topics    = $args['topics'] ?? array();
$canonical = $args['canonical'] ?? null;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$topics = $topics ?? array();
?>
<div class="zeko-qa-topic-archive">
	<header class="zeko-qa-archive-header">
		<h1 class="zeko-qa-archive-title"><?php esc_html_e( 'Topics', 'zeko-qa' ); ?></h1>
		<p class="zeko-qa-archive-subtitle"><?php esc_html_e( 'Browse questions by topic', 'zeko-qa' ); ?></p>
	</header>

	<?php if ( empty( $topics ) ) : ?>
		<div class="zeko-qa-empty-state">
			<p><?php esc_html_e( 'No topics found.', 'zeko-qa' ); ?></p>
		</div>
	<?php else : ?>
		<div class="zeko-qa-topic-grid">
			<?php foreach ( $topics as $topic ) : ?>
				<a href="<?php echo esc_url( home_url( '/topics/' . $topic->slug . '/' ) ); ?>" class="zeko-qa-topic-card">
					<h2 class="zeko-qa-topic-card-name"><?php echo esc_html( $topic->name ); ?></h2>
					<?php if ( $topic->description ) : ?>
						<p class="zeko-qa-topic-card-desc"><?php echo esc_html( wp_trim_words( $topic->description, 15 ) ); ?></p>
					<?php endif; ?>
					<div class="zeko-qa-topic-card-stats">
						<span><?php /* translators: %d: number of questions */ printf( esc_html__( '%d questions', 'zeko-qa' ), absint( $topic->question_count ) ); ?></span>
						<span class="zeko-qa-meta-divider" aria-hidden="true">&middot;</span>
						<span><?php /* translators: %d: number of followers */ printf( esc_html__( '%d followers', 'zeko-qa' ), absint( $topic->followers ) ); ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
