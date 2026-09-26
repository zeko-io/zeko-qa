(function($) {
    'use strict';

    $(document).ready(function() {
        if (!window.zekoQAAdmin) {
            return;
        }

        $('#zeko-qa-generate-demo').on('click', function(e) {
            e.preventDefault();
            if (!confirm(zekoQAAdmin.strings.confirmGenerate)) {
                return;
            }

            var $btn = $(this);
            var $status = $('.zeko-qa-demo-status');

            $btn.prop('disabled', true).text(zekoQAAdmin.strings.generating);
            $status.text('').removeClass('success error');

            $.ajax({
                url: zekoQAAdmin.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_generate_demo',
                    nonce: zekoQAAdmin.nonce,
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Generate Demo Data');
                    if (response.success) {
                        $status.text(response.data.message).addClass('success');
                    } else {
                        $status.text(response.data.message || 'Error.').addClass('error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Generate Demo Data');
                    $status.text('Network error.').addClass('error');
                },
            });
        });

        $('#zeko-qa-clear-demo').on('click', function(e) {
            e.preventDefault();
            if (!confirm('Clear all demo data?')) {
                return;
            }

            var $btn = $(this);
            var $status = $('.zeko-qa-demo-status');

            $btn.prop('disabled', true);
            $status.text('').removeClass('success error');

            $.ajax({
                url: zekoQAAdmin.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'zeko_qa_clear_demo',
                    nonce: zekoQAAdmin.nonce,
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $status.text(response.data.message).addClass('success');
                    } else {
                        $status.text(response.data.message || 'Error.').addClass('error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.text('Network error.').addClass('error');
                },
            });
        });

        $('#zeko-qa-flush-rewrites').on('click', function(e) {
            e.preventDefault();
            $.post(zekoQAAdmin.ajaxUrl, {
                action: 'zeko_qa_flush_rewrites',
                nonce: zekoQAAdmin.nonce,
            }, function(response) {
                if (response.success) {
                    alert('Rewrite rules flushed.');
                } else {
                    alert('Error flushing rewrite rules.');
                }
            });
        });
    });

})(jQuery);
