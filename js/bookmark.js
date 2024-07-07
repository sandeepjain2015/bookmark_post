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
                button.toggleClass('bookmarked', response === 'added');
                button.text(response === 'added' ? 'Unbookmark' : 'Bookmark');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Refresh bookmarks button
    $(document).on('click', '#refresh-bookmark-post-button', function() {
        var button = $(this);
        button.prop('disabled', true).text('Refreshing...'); // Disable and change text

        $('#bookmark-post-loader').show(); // Show loader

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
                $('#bookmark-post-loader').hide(); // Hide loader

                button.prop('disabled', false).text('Refresh Bookmarks'); // Re-enable and reset text
            }
        });
    });
});
