<?= wc_ukr_shipping_render_view('partial/top_panel'); ?>

<div id="wcus-ttn-form"></div>
<script>
    (function ($) {
        $(function () {
            window.WcusTtn.init({
                autoTracking: <?= (int)wc_ukr_shipping_get_option('wcus_tracking_auto_send'); ?>
            });
        });
    })(jQuery);
</script>