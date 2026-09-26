<?php
/**
 * File doc comment.
 *
 * @package Zeko_ZEKO_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$tags      = $args['tags'] ?? array();
$canonical = $args['canonical'] ?? null;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tags = $tags ?? array();
?>
<div class="zeko-qa-tag-archive">
	<header class="zeko-qa-archive-header">
		<h1 class="zeko-qa-archive-title"><?php esc_html_e( 'Tags', 'zeko-qa' ); ?></h1>
		<p class="zeko-qa-archive-subtitle"><?php esc_html_e( 'Browse questions by tag', 'zeko-qa' ); ?></p>
	</header>

	<?php if ( empty( $tags ) ) : ?>
		<div class="zeko-qa-empty-state">
			<p><?php esc_html_e( 'No tags found.', 'zeko-qa' ); ?></p>
		</div>
	<?php else : ?>
		<div class="zeko-qa-tag-cloud">
			<?php
			foreach ( $tags as $term_tag ) :
				$size    = max( 12, min( 24, 12 + ( $term_tag->question_count / 5 ) ) );
				$opacity = max( 0.5, min( 1, 0.5 + ( $term_tag->question_count / 20 ) ) );
				?>
				<a href="<?php echo esc_url( home_url( '/qa-tags/' . $term_tag->slug . '/' ) ); ?>" class="zeko-qa-tag-link" style="font-size:<?php echo esc_attr( $size ); ?>px; opacity:<?php echo esc_attr( $opacity ); ?>">
					<?php echo esc_html( $term_tag->name ); ?>
					<span class="zeko-qa-tag-count"><?php echo absint( $term_tag->question_count ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
