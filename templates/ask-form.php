<?php
/**
 * Ask a question form template with advanced UX, SEO schema, and accessibility
 *
 * @package Zeko_QA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = get_current_user_id();
if ( ! $current_user_id ) {
	/* translators: %s: login URL */
	echo '<div class="zeko-qa-login-prompt"><p>' . wp_kses_post( sprintf( __( 'Please <a href="%s">login</a> to ask a question.', 'zeko-qa' ), esc_url( wp_login_url() ) ) ) . '</p></div>';
	return;
}

$topics    = $topics ?? array();
$tags      = $tags ?? array();
$page_id   = get_the_ID();
$canonical = get_permalink( $page_id );
$site_name = get_bloginfo( 'name' );
$site_url  = home_url( '/' );
?>

<div class="zeko-qa-ask-form-wrapper">
	<h1 class="zeko-qa-ask-form-title"><?php esc_html_e( 'Ask a Question', 'zeko-qa' ); ?></h1>
	<p class="zeko-qa-ask-form-subtitle"><?php esc_html_e( 'Get answers from the community.', 'zeko-qa' ); ?></p>

	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "WebPage",
		"name": "<?php echo esc_js( __( 'Ask a Question', 'zeko-qa' ) ); ?>",
		"description": "<?php echo esc_js( __( 'Ask a question and get answers from the community.', 'zeko-qa' ) ); ?>",
		"url": "<?php echo esc_url( $canonical ); ?>",
		"isPartOf": {
			"@type": "WebSite",
			"name": "<?php echo esc_js( $site_name ); ?>",
			"url": "<?php echo esc_url( $site_url ); ?>"
		},
		"potentialAction": {
			"@type": "AskAction",
			"target": "<?php echo esc_url( $canonical ); ?>",
			"query-input": "required name=question"
		}
	}
	</script>

	<form class="zeko-qa-ask-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<input type="hidden" name="action" value="zeko_qa_submit_question" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'zeko_qa_ask_nonce' ) ); ?>" />

		<div class="zeko-qa-form-field">
			<label for="zeko_qa_question_title" class="zeko-qa-form-label">
				<?php esc_html_e( 'Question Title', 'zeko-qa' ); ?>
				<span class="zeko-qa-required" aria-hidden="true">*</span>
			</label>
			<input 
				type="text" 
				id="zeko_qa_question_title" 
				name="title" 
				class="zeko-qa-form-input" 
				value="<?php echo esc_attr( isset( $defaults['title'] ) ? $defaults['title'] : '' ); ?>"
				placeholder="<?php esc_attr_e( 'What is your question? Be specific.', 'zeko-qa' ); ?>" 
				required 
				maxlength="255" 
				aria-describedby="zeko_qa_title_help zeko_qa_title_counter"
				autocomplete="off"
			/>
			<div class="zeko-qa-form-help" id="zeko_qa_title_help">
				<?php esc_html_e( 'Summarize your problem in one sentence.', 'zeko-qa' ); ?>
			</div>
			<div class="zeko-qa-form-counter" id="zeko_qa_title_counter" aria-live="polite">
				<span class="zeko-qa-counter-current">0</span>/255
			</div>
		</div>

		<div class="zeko-qa-form-field">
			<label class="zeko-qa-form-label">
				<?php esc_html_e( 'Details', 'zeko-qa' ); ?>
				<span class="zeko-qa-required" aria-hidden="true">*</span>
			</label>
			<div class="zeko-qa-editor-wrapper">
				<div id="zeko_qa_question_editor"></div>
				<textarea
					id="zeko_qa_question_content"
					name="content"
					class="zeko-qa-form-textarea zeko-qa-hidden"
					required
					aria-describedby="zeko_qa_content_help zeko_qa_content_counter"
				></textarea>
				<?php if ( class_exists( 'Zeko_AI_Writer_UI' ) ) : ?>
					<?php
					echo wp_kses_post(
						(string) Zeko_AI_Writer_UI::button(
							array(
								'preset' => 'question_draft',
								'target' => '#zeko_qa_question_content',
							)
						)
					);
					?>
				<?php endif; ?>
			</div>
			<div class="zeko-qa-form-help" id="zeko_qa_content_help">
				<?php esc_html_e( 'The more detail you provide, the better answers you will receive.', 'zeko-qa' ); ?>
			</div>
			<div class="zeko-qa-form-counter" id="zeko_qa_content_counter" aria-live="polite">
				<span class="zeko-qa-counter-current">0</span> <?php esc_html_e( 'characters', 'zeko-qa' ); ?>
			</div>
		</div>

		<?php if ( ! empty( $topics ) ) : ?>
			<div class="zeko-qa-form-field">
				<fieldset class="zeko-qa-form-fieldset">
					<legend class="zeko-qa-form-label"><?php esc_html_e( 'Categories / Topics', 'zeko-qa' ); ?></legend>
					<div class="zeko-qa-checkbox-grid">
						<?php foreach ( $topics as $topic ) : ?>
							<label class="zeko-qa-checkbox-label">
								<input type="checkbox" name="topic_ids[]" value="<?php echo absint( $topic->id ); ?>" />
								<span><?php echo esc_html( $topic->name ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<div class="zeko-qa-form-help">
						<?php esc_html_e( 'Select all categories that apply to your question.', 'zeko-qa' ); ?>
					</div>
				</fieldset>
			</div>
		<?php endif; ?>

		<div class="zeko-qa-form-field">
			<label for="zeko_qa_question_tags" class="zeko-qa-form-label">
				<?php esc_html_e( 'Tags', 'zeko-qa' ); ?>
			</label>
			<input 
				type="text" 
				id="zeko_qa_question_tags" 
				name="tags" 
				class="zeko-qa-form-input" 
				placeholder="<?php esc_attr_e( 'e.g. WordPress, php, api (comma separated)', 'zeko-qa' ); ?>" 
				autocomplete="off"
				aria-describedby="zeko_qa_tags_help"
			/>
			<div class="zeko-qa-form-help" id="zeko_qa_tags_help">
				<?php esc_html_e( 'Add up to 5 tags to help others find your question.', 'zeko-qa' ); ?>
			</div>
			<?php if ( ! empty( $tags ) ) : ?>
				<div class="zeko-qa-tag-suggestions">
					<?php foreach ( array_slice( $tags, 0, 20 ) as $term_tag ) : ?>
						<button type="button" class="zeko-qa-tag-chip" data-tag="<?php echo esc_attr( $term_tag->name ); ?>"><?php echo esc_html( $term_tag->name ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="zeko-qa-form-field">
			<fieldset class="zeko-qa-form-fieldset">
				<legend class="zeko-qa-form-label"><?php esc_html_e( 'Visibility', 'zeko-qa' ); ?></legend>
				<div class="zeko-qa-radio-group">
					<label class="zeko-qa-radio-label">
						<input type="radio" name="visibility" value="public" checked />
						<span><?php esc_html_e( 'Public - Everyone can see', 'zeko-qa' ); ?></span>
					</label>
					<label class="zeko-qa-radio-label">
						<input type="radio" name="visibility" value="anonymous" />
						<span><?php esc_html_e( 'Anonymous - Hide my name', 'zeko-qa' ); ?></span>
					</label>
				</div>
			</fieldset>
		</div>

		<div class="zeko-qa-form-actions">
			<button type="submit" class="zeko-qa-btn zeko-qa-btn-primary zeko-qa-submit-btn">
				<span class="zeko-qa-btn-text"><?php esc_html_e( 'Post Question', 'zeko-qa' ); ?></span>
				<span class="zeko-qa-btn-loading" aria-hidden="true"><?php esc_html_e( 'Posting...', 'zeko-qa' ); ?></span>
			</button>
			<span class="zeko-qa-form-message" role="status" aria-live="polite"></span>
		</div>
	</form>
</div>
