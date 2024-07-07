jQuery(document).ready(function($) {
    $('#refresh-bookmark-count').on('click', function() {
        var post_id = $('#post_ID').val();

        $.ajax({
            url: bp_admin_ajax.ajax_url,
            type: 'post',
            data: {
                action: 'refresh_bookmark_count',
                post_id: post_id,
                nonce: bp_admin_ajax.nonce
            },
            success: function(response) {
                $('#bookmark-count').text(response);
            }
        });
    });
});
