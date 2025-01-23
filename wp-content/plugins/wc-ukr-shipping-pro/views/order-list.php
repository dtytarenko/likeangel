<?= wc_ukr_shipping_render_view('partial/top_panel'); ?>

<div id="wcus-order-list"></div>
<script>
    (function ($) {
        $(function () {
            window.WcusOrders.init({
                autoTracking: <?= (int)wc_ukr_shipping_get_option('wcus_tracking_auto_send'); ?>
            });
        });
    })(jQuery);
</script>