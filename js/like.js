jQuery(document).ready(function ($) {
    // Toggle the like/dislike options
    $('#wpl-heart-button').on('click', function () {
        $('#wpl-action-buttons').slideToggle();
    });

    // Handle like/dislike vote
    $('.wpl-action-btn').on('click', function () {
        var action = $(this).data('action');
        var productId = $('#wpl-heart-button').data('product-id');

        $.ajax({
            url: wpl_ajax.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpl_like_toggle',
                nonce: wpl_ajax.nonce,
                product_id: productId,
                like_action: action
            },
            success: function (response) {
                if (response.success) {
                    $('#wpl-like-count').text(response.data.new_likes);
                    $('#wpl-dislike-count').text(response.data.new_dislikes);
                    $('#wpl-action-buttons').hide();
                    $('#wpl-feedback-message').text(response.data.message).fadeIn().delay(2000).fadeOut();
                } else {
                    $('#wpl-feedback-message').text(response.data.message).fadeIn().delay(3000).fadeOut();
                }
            },
            error: function () {
                $('#wpl-feedback-message').text("Something went wrong. Please try again.").fadeIn().delay(3000).fadeOut();
            }
        });
    });
});
