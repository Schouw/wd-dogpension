jQuery(function($) {
    $(document).on('click', '.wddp-close-notice', function() {
        $(this).closest('.wc-block-components-notice-banner').fadeOut(200);
    });
});
