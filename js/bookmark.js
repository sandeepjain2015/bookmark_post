jQuery(document).ready(function($) {
    // Toggle bookmark button
    $(document).on('click', '.bookmark-post-button', function() {
        var button = $(this);
        var post_id = button.data('post_id');

        $.ajax({
            url: bp_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'toggle_bookmark',
                post_id: post_id,
                nonce: bp_ajax.nonce
            },
            success: function(response) {
                console.log(response);
                if (response === 'added') {
                    button.addClass('bookmarked');
                    button.text('Unbookmark');
                } else {
                    button.removeClass('bookmarked');
                    button.text('Bookmark');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Refresh bookmarks button.
    $(document).on('click', '#refresh-bookmark-post-button', function() {
        var button = $(this);
        button.prop('disabled', true); // Disable the button
        button.text('Refreshing...'); // Change button text

        // Show loader
        $('#bookmark-post-loader').show();
        $.ajax({
            url: bp_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'refresh_bookmarks_list',
                nonce: bp_ajax.nonce
            },
            success: function(response) {
                $('#bookmark-post-list-container').html(response + '<button id="refresh-bookmark-post-button">Refresh Bookmarks</button>');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            },
            complete: function() {
                // Hide loader
                $('#bookmark-post-loader').hide();

                // Re-enable button and reset text
                button.prop('disabled', false);
                button.text('Refresh Bookmarks');
            }
        });
    });
});
