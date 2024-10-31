jQuery(function ($) {
    $(document).ready(function () {
        $('#deactivate-paydock-for-woocommerce').on('click', function (e) {
            e.preventDefault();

            let urlRedirect = jQuery(this).attr('href');
            let label = jQuery(this).attr('aria-label');

            if (confirm('Are you sure ' + label + ' ?')) {
                window.location.href = urlRedirect;
            }
        });
    });
})
