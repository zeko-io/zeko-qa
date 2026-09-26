(function($) {
    'use strict';

    var quillEditors = {};

    function initQuillEditor(containerId, textareaId) {
        if (quillEditors[containerId]) {
            return quillEditors[containerId];
        }
        var $container = $('#' + containerId);
        if (!$container.length || typeof Quill === 'undefined') {
            return null;
        }
        $container.empty();
        var editor = new Quill('#' + containerId, {
            theme: 'snow',
            placeholder: containerId === 'zeko_qa_question_editor'
                ? 'Explain your question in detail...'
                : 'Write your answer here...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });
        editor.on('text-change', function() {
            var text = editor.getText();
            var $textarea = $('#' + textareaId);
            $textarea.val(editor.root.innerHTML);
            var len = $.trim(text).length;
            var $counter = $textarea.closest('.zeko-qa-form-field, .zeko-qa-editor-wrapper').find('.zeko-qa-counter-current');
            if ($counter.length) {
                $counter.text(len);
            }
        });
        quillEditors[containerId] = editor;
        return editor;
    }

    function initAskForm() {
        if (!$('#zeko_qa_question_editor').length) {
            return;
        }
        initQuillEditor('zeko_qa_question_editor', 'zeko_qa_question_content');
    }

    function initAnswerEditor() {
        if (!$('#zeko_qa_answer_editor').length) {
            return;
        }
        initQuillEditor('zeko_qa_answer_editor', 'zeko_qa_answer_content');
    }

    function bindAskFormEvents() {
        $(document).on('click', '.zeko-qa-tag-chip', function(e) {
            e.preventDefault();
            var $input = $('#zeko_qa_question_tags');
            var tag = $(this).data('tag');
            if (!$input.length || !tag) {
                return;
            }
            var current = $input.val();
            var tags = current ? current.split(',').map(function(t) { return $.trim(t); }).filter(Boolean) : [];
            if (tags.indexOf(tag) === -1 && tags.length < 5) {
                tags.push(tag);
                $input.val(tags.join(', '));
            }
        });

        $(document).on('input', '#zeko_qa_question_title', function() {
            var len = $(this).val().length;
            $('#zeko_qa_title_counter .zeko-qa-counter-current').text(len);
        });

        $(document).on('input', '#zeko_qa_question_content', function() {
            var len = $(this).val().length;
            $('#zeko_qa_content_counter .zeko-qa-counter-current').text(len);
        });
    }

    $(document).ready(function() {
        if (!window.zekoQAPublic) {
            return;
        }

        initAskForm();
        initAnswerEditor();
        bindAskFormEvents();

        $(document).on('submit', '.zeko-qa-ask-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $button = $form.find('.zeko-qa-submit-btn');
            var $message = $form.find('.zeko-qa-form-message');
            if (quillEditors['zeko_qa_question_editor']) {
                $form.find('#zeko_qa_question_content').val(quillEditors['zeko_qa_question_editor'].root.innerHTML);
            }
            var formData = $form.serialize();

            $button.addClass('loading').prop('disabled', true);
            $message.text('').css('color', '#0073aa');

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: formData + '&action=zeko_qa_submit_question',
                success: function(response) {
                    $button.removeClass('loading').prop('disabled', false);
                    if (response.success) {
                        $message.text(response.data.message).css('color', '#46b450');
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 800);
                    } else {
                        $message.text(response.data.message || 'Error.').css('color', '#dc3232');
                    }
                },
                error: function() {
                    $button.removeClass('loading').prop('disabled', false);
                    $message.text('Network error.').css('color', '#dc3232');
                },
            });
        });

        $(document).on('click', '.zeko-qa-vote-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            var type = $btn.data('type');
            var vote = $btn.data('vote');

            if (!id || !type) {
                return;
            }

            var $card = $btn.closest('[data-question-id], [data-answer-id]');
            var $count = $card.find('.zeko-qa-vote-count');
            var $siblings = $btn.siblings('.zeko-qa-vote-btn');

            $btn.addClass('loading');
            $siblings.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_vote',
                    nonce: zekoQAPublic.nonce,
                    item_id: id,
                    item_type: type,
                    vote_type: vote,
                },
                success: function(response) {
                    $btn.removeClass('loading');
                    $siblings.prop('disabled', false);

                    if (response.success) {
                        var voteState = response.data.vote_state || '';
                        var netVotes = typeof response.data.net_votes === 'number' ? response.data.net_votes : null;

                        $card.find('.zeko-qa-vote-btn').removeClass('active zeko-qa-voted').attr('aria-pressed', 'false');

                        if (voteState === 'up') {
                            $btn.addClass('active zeko-qa-voted').attr('aria-pressed', 'true');
                        } else if (voteState === 'down') {
                            $btn.addClass('active zeko-qa-voted').attr('aria-pressed', 'true');
                        }

                        if (netVotes !== null) {
                            $count.text(netVotes);
                        } else {
                            var current = parseInt($count.text(), 10) || 0;
                            if (voteState === 'up') {
                                current = ($btn.hasClass('zeko-qa-vote-up')) ? current + 1 : current - 1;
                            } else if (voteState === 'down') {
                                current = ($btn.hasClass('zeko-qa-vote-down')) ? current - 1 : current + 1;
                            } else {
                                if ($btn.hasClass('zeko-qa-vote-up')) {
                                    current -= 1;
                                } else {
                                    current += 1;
                                }
                            }
                            $count.text(current);
                        }
                    } else {
                        alert(response.data.message || zekoQAPublic.strings.loginToVote);
                    }
                },
                error: function() {
                    $btn.removeClass('loading');
                    $siblings.prop('disabled', false);
                },
            });
        });

        $(document).on('submit', '.zeko-qa-answer-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var questionId = $form.data('question-id');
            var $textarea = $form.find('textarea[name="content"]');
            if (quillEditors['zeko_qa_answer_editor']) {
                $textarea.val(quillEditors['zeko_qa_answer_editor'].root.innerHTML);
            }
            var content = $.trim($textarea.val());
            var $message = $form.find('.zeko-qa-form-message');
            var $button = $form.find('button[type="submit"]');

            if (!content) {
                $message.text('Please write an answer.').css('color', '#dc3232');
                return;
            }

            $button.prop('disabled', true).text('Submitting...');
            $message.text('').css('color', '#0073aa');

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_submit_answer',
                    nonce: zekoQAPublic.nonce,
                    question_id: questionId,
                    content: content,
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        $button.prop('disabled', false).text('Submit Answer');
                        $message.text(response.data.message || 'Error.').css('color', '#dc3232');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Submit Answer');
                    $message.text('Network error.').css('color', '#dc3232');
                },
            });
        });

        $(document).on('click', '.zeko-qa-accept-answer', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var answerId = $btn.data('answer-id');
            var questionId = $btn.data('question-id');

            if (!confirm('Accept this answer?')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_accept_answer',
                    nonce: zekoQAPublic.nonce,
                    answer_id: answerId,
                    question_id: questionId,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error.');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    alert('Network error.');
                },
            });
        });

        $(document).on('click', '.zeko-qa-report-question', function(e) {
            e.preventDefault();
            var questionId = $(this).data('question-id');
            var reason = prompt('Please describe the issue:');
            if (!reason) {
                return;
            }

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_report',
                    nonce: zekoQAPublic.nonce,
                    item_id: questionId,
                    item_type: 'question',
                    reason: reason,
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || 'Error.');
                    }
                },
                error: function() {
                    alert('Network error.');
                },
            });
        });

        var searchTimer;
        var $searchInput = $('#zeko_qa_search_input');
        var $suggestionsContainer = $('<div class="zeko-qa-search-suggestions" role="listbox" aria-label="Search suggestions"></div>');
        $searchInput.after($suggestionsContainer);
        $searchInput.attr('role', 'combobox').attr('aria-autocomplete', 'list').attr('aria-expanded', 'false');

        $searchInput.on('input', function() {
            var term = $.trim($(this).val());
            clearTimeout(searchTimer);

            if (term.length < 2) {
                $suggestionsContainer.empty().hide();
                $searchInput.attr('aria-expanded', 'false');
                return;
            }

            searchTimer = setTimeout(function() {
                $.ajax({
                    url: zekoQAPublic.ajaxUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'zeko_qa_search_suggestions',
                        term: term,
                    },
                    success: function(response) {
                        if (response.success && response.data.suggestions.length > 0) {
                            var html = '';
                            $.each(response.data.suggestions, function(i, item) {
                                html += '<div class="zeko-qa-suggestion-item" role="option" data-slug="' + item.slug + '">';
                                html += '<span class="zeko-qa-suggestion-title">' + item.title + '</span>';
                                html += '<span class="zeko-qa-suggestion-meta">' + item.answer_count + ' answers</span>';
                                html += '</div>';
                            });
                            $suggestionsContainer.html(html).show();
                            $searchInput.attr('aria-expanded', 'true');
                        } else {
                            $suggestionsContainer.empty().hide();
                            $searchInput.attr('aria-expanded', 'false');
                        }
                    },
                });
            }, 250);
        });

        $(document).on('click', '.zeko-qa-suggestion-item', function() {
            var slug = $(this).data('slug');
            window.location.href = zekoQAPublic.ajaxUrl.replace('admin-ajax.php', 'questions/' + slug + '/');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.zeko-qa-search-form').length) {
                $suggestionsContainer.empty().hide();
                $searchInput.attr('aria-expanded', 'false');
            }
        });

        $searchInput.on('keydown', function(e) {
            var $items = $suggestionsContainer.find('.zeko-qa-suggestion-item');
            var $active = $items.filter('.zeko-qa-suggestion-active');
            var idx = $items.index($active);

            if (e.keyCode === 40) {
                e.preventDefault();
                $active.removeClass('zeko-qa-suggestion-active');
                idx = idx < $items.length - 1 ? idx + 1 : 0;
                $items.eq(idx).addClass('zeko-qa-suggestion-active');
            } else if (e.keyCode === 38) {
                e.preventDefault();
                $active.removeClass('zeko-qa-suggestion-active');
                idx = idx > 0 ? idx - 1 : $items.length - 1;
                $items.eq(idx).addClass('zeko-qa-suggestion-active');
            } else if (e.keyCode === 13 && $active.length) {
                e.preventDefault();
                $active.trigger('click');
            } else if (e.keyCode === 27) {
                $suggestionsContainer.empty().hide();
                $searchInput.attr('aria-expanded', 'false');
            }
        });

        $(document).on('click', '.zeko-qa-topic-follow-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var topicId = $btn.data('topic-id');

            if (!topicId) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_toggle_topic_follow',
                    nonce: zekoQAPublic.nonce,
                    topic_id: topicId,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (response.data.following) {
                            $btn.removeClass('zeko-qa-btn-primary').addClass('zeko-qa-btn-secondary').text('Following');
                        } else {
                            $btn.removeClass('zeko-qa-btn-secondary').addClass('zeko-qa-btn-primary').text('Follow');
                        }
                    } else {
                        alert(response.data.message || zekoQAPublic.strings.loginToVote);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                },
            });
        });

        $(document).on('click', '.zeko-qa-question-follow-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var questionId = $btn.data('question-id');

            if (!questionId) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_toggle_question_follow',
                    nonce: zekoQAPublic.nonce,
                    question_id: questionId,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (response.data.following) {
                            $btn.removeClass('zeko-qa-btn-primary').addClass('zeko-qa-btn-secondary').text('Following');
                        } else {
                            $btn.removeClass('zeko-qa-btn-secondary').addClass('zeko-qa-btn-primary').text('Follow');
                        }
                    } else {
                        alert(response.data.message || zekoQAPublic.strings.loginToVote);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                },
            });
        });

        $(document).on('click', '.zeko-qa-bookmark-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var questionId = $btn.data('question-id');

            if (!questionId) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_toggle_bookmark',
                    nonce: zekoQAPublic.nonce,
                    question_id: questionId,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (response.data.bookmarked) {
                            $btn.addClass('zeko-qa-bookmarked').attr('aria-pressed', 'true');
                            $btn.find('svg').attr('fill', 'currentColor');
                            $btn.find('span').text('Saved');
                        } else {
                            $btn.removeClass('zeko-qa-bookmarked').attr('aria-pressed', 'false');
                            $btn.find('svg').attr('fill', 'none');
                            $btn.find('span').text('Save');
                        }
                    } else {
                        alert(response.data.message || zekoQAPublic.strings.loginToVote);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                },
            });
        });

        $(document).on('submit', '.zeko-qa-comment-form', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $input = $form.find('input[name="comment_content"]');
            var content = $.trim($input.val());
            var itemId = $form.data('item-id');
            var itemType = $form.data('item-type');
            var $list = $form.closest('.zeko-qa-comments-section').find('.zeko-qa-comments-list');

            if (!content) {
                return;
            }

            $form.find('button').prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_add_comment',
                    nonce: zekoQAPublic.nonce,
                    item_id: itemId,
                    item_type: itemType,
                    content: content,
                },
                success: function(response) {
                    $form.find('button').prop('disabled', false);
                    if (response.success) {
                        var c = response.data.comment;
                        var html = '<div class="zeko-qa-comment-item" data-comment-id="' + c.id + '">';
                        html += '<a href="/users/' + (c.author_nicename || '') + '/" class="zeko-qa-comment-author">' + c.author_name + '</a>';
                        html += '<span class="zeko-qa-comment-text">' + c.content + '</span>';
                        html += '<span class="zeko-qa-comment-time">' + c.created_at + '</span>';
                        if (c.author_id === zekoQAPublic.userId) {
                            html += ' <button class="zeko-qa-comment-delete" data-comment-id="' + c.id + '">&times;</button>';
                        }
                        html += '</div>';
                        $list.append(html);
                        $input.val('');
                    } else {
                        alert(response.data.message || 'Error.');
                    }
                },
                error: function() {
                    $form.find('button').prop('disabled', false);
                },
            });
        });

        $(document).on('click', '.zeko-qa-comment-delete', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var commentId = $btn.data('comment-id');

            if (!confirm('Delete this comment?')) {
                return;
            }

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_delete_comment',
                    nonce: zekoQAPublic.nonce,
                    comment_id: commentId,
                },
                success: function(response) {
                    if (response.success) {
                        $btn.closest('.zeko-qa-comment-item').fadeOut(200, function() { $(this).remove(); });
                    } else {
                        alert(response.data.message || 'Error.');
                    }
                },
                error: function() {
                    alert('Network error.');
                },
            });
        });

        $(document).on('click', '.zeko-qa-toggle-comments', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $section = $btn.closest('.zeko-qa-comments-section');
            var $list = $section.find('.zeko-qa-comments-list');
            var $form = $section.find('.zeko-qa-comment-form');

            $list.toggle();
            $form.toggle();
            $btn.text($list.is(':visible') ? 'Hide comments' : 'Show comments');
        });

        $(document).on('click', '.zeko-qa-notifications-bell', function(e) {
            e.preventDefault();
            var $bell = $(this);
            var $dropdown = $('#zeko-qa-notifications-dropdown');
            var isOpen = $dropdown.is(':visible');

            if (isOpen) {
                $dropdown.hide();
                $bell.attr('aria-expanded', 'false');
                return;
            }

            $bell.attr('aria-expanded', 'true');
            $dropdown.show();

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_get_notifications',
                    nonce: zekoQAPublic.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        var $list = $dropdown.find('.zeko-qa-notifications-list');
                        $list.empty();

                        if (response.data.notifications.length === 0) {
                            $list.html('<p class="zeko-qa-notifications-empty">No notifications yet.</p>');
                            return;
                        }

                        $.each(response.data.notifications, function(i, n) {
                            var readClass = n.is_read ? '' : ' zeko-qa-unread';
                            var html = '<div class="zeko-qa-notification-item' + readClass + '">';
                            if (n.link) {
                                html += '<a href="' + n.link + '" class="zeko-qa-notification-link">';
                            }
                            html += '<span class="zeko-qa-notification-actor">' + n.actor_name + '</span> ';
                            html += '<span class="zeko-qa-notification-action">' + n.action + '</span>';
                            if (n.link) {
                                html += '</a>';
                            }
                            html += '<span class="zeko-qa-notification-time">' + n.created_at + '</span>';
                            html += '</div>';
                            $list.append(html);
                        });

                        var badge = $bell.find('.zeko-qa-notifications-badge');
                        if (response.data.unread_count === 0) {
                            badge.remove();
                        } else {
                            badge.text(response.data.unread_count > 99 ? '99+' : response.data.unread_count);
                        }
                    }
                },
            });
        });

        $(document).on('click', '.zeko-qa-notifications-mark-read', function(e) {
            e.preventDefault();
            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_mark_notifications_read',
                    nonce: zekoQAPublic.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        $('.zeko-qa-notifications-badge').remove();
                        $('.zeko-qa-notification-item').removeClass('zeko-qa-unread');
                    }
                },
            });
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.zeko-qa-notifications-wrapper').length) {
                $('#zeko-qa-notifications-dropdown').hide();
                $('.zeko-qa-notifications-bell').attr('aria-expanded', 'false');
            }
        });

        $(document).on('click', '.zeko-qa-space-join-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var spaceId = $btn.data('space-id');
            var isMember = $btn.hasClass('zeko-qa-btn-secondary');

            $btn.prop('disabled', true);

            $.ajax({
                url: zekoQAPublic.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: isMember ? 'zeko_qa_leave_space' : 'zeko_qa_join_space',
                    nonce: zekoQAPublic.nonce,
                    space_id: spaceId,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (isMember) {
                            $btn.removeClass('zeko-qa-btn-secondary').addClass('zeko-qa-btn-primary').text('Join Space');
                        } else {
                            $btn.removeClass('zeko-qa-btn-primary').addClass('zeko-qa-btn-secondary').text('Leave Space');
                        }
                    } else {
                        alert(response.data.message || 'Error.');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                },
            });
        });
    });

    // Re-initialize editors and bindings for content loaded dynamically
    // (e.g. the business portal loads the ask form via AJAX).
    $(document).on('zeko_qa_content_loaded', function() {
        if (!window.zekoQAPublic) {
            return;
        }
        bindAskFormEvents();
        if (window.zekoQAPublic.userId) {
            initAskForm();
        }
        initAnswerEditor();
    });

})(jQuery);
