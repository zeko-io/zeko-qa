<?php
/**
 * Spaces archive template
 *
 * @package Zeko_QA
 */

// Data scalar from Zeko_QA_Public::render_qa_template().
$spaces = $args['spaces'] ?? array();

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$spaces    = $spaces ?? array();
$page_url  = home_url( '/spaces/' );
$canonical = $page_url;
$site_name = get_bloginfo( 'name' );
?>
<div class="zeko-qa-spaces-wrapper">
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "CollectionPage",
		"name": "<?php echo esc_js( __( 'Spaces', 'zeko-qa' ) ); ?>",
		"description": "<?php echo esc_js( __( 'Browse community spaces on Zeko.', 'zeko-qa' ) ); ?>",
		"url": "<?php echo esc_url( $canonical ); ?>"
	}
	</script>

	<div class="zeko-qa-page-header">
		<h1 class="zeko-qa-page-title"><?php esc_html_e( 'Spaces', 'zeko-qa' ); ?></h1>
		<p class="zeko-qa-page-subtitle"><?php esc_html_e( 'Join communities to share and discover knowledge.', 'zeko-qa' ); ?></p>
		<?php if ( is_user_logged_in() ) : ?>
			<button class="zeko-qa-btn zeko-qa-btn-primary" id="zeko-qa-create-space-btn"><?php esc_html_e( 'Create Space', 'zeko-qa' ); ?></button>
		<?php endif; ?>
	</div>

	<?php if ( empty( $spaces ) ) : ?>
		<div class="zeko-qa-empty-state">
			<p><?php esc_html_e( 'No spaces yet. Be the first to create one!', 'zeko-qa' ); ?></p>
		</div>
	<?php else : ?>
		<div class="zeko-qa-spaces-grid">
			<?php foreach ( $spaces as $space ) : ?>
				<div class="zeko-qa-space-card">
					<h2 class="zeko-qa-space-name">
						<a href="<?php echo esc_url( home_url( '/spaces/' . $space->slug . '/' ) ); ?>"><?php echo esc_html( $space->name ); ?></a>
					</h2>
					<?php if ( ! empty( $space->description ) ) : ?>
						<p class="zeko-qa-space-desc"><?php echo esc_html( wp_trim_words( $space->description, 20 ) ); ?></p>
					<?php endif; ?>
					<div class="zeko-qa-space-meta">
						<span><?php echo absint( $space->member_count ); ?> <?php esc_html_e( 'members', 'zeko-qa' ); ?></span>
						<span><?php echo absint( $space->question_count ); ?> <?php esc_html_e( 'posts', 'zeko-qa' ); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
