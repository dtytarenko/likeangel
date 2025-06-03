<?php
/**
 * Save UTM parameters to localStorage (90 днів) + додає до purchase + збереження UTM в cookie для LiqPay (без setTimeout)
 */

add_action('wp_footer', function () {
    ?>
    <script>
    (function () {
        const params = new URLSearchParams(window.location.search);
        const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'gclid'];
        const data = {};
        let hasData = false;

        utmKeys.forEach(key => {
            const val = params.get(key);
            if (val) {
                data[key] = val;
                hasData = true;
            }
        });

        if (hasData) {
            data.saved_at = Date.now();
            localStorage.setItem('traffic_source', JSON.stringify(data));
        } else {
            const saved = localStorage.getItem('traffic_source');
            if (saved) {
                try {
                    const obj = JSON.parse(saved);
                    const ninetyDays = 1000 * 60 * 60 * 24 * 90;
                    if (Date.now() - obj.saved_at > ninetyDays) {
                        localStorage.removeItem('traffic_source');
                    }
                } catch (e) {
                    localStorage.removeItem('traffic_source');
                }
            }
        }

        if (window.location.href.includes('/checkout')) {
            try {
                const traffic = JSON.parse(localStorage.getItem('traffic_source') || '{}');
                const ga = document.cookie.match(/_ga=GA\d\.\d\.(\d+\.\d+)/);
                if (ga) {
                    traffic.client_id = ga[1];
                }
                document.cookie = 'liqpay_utm=' + encodeURIComponent(JSON.stringify(traffic)) + '; path=/';
                console.log('[LA] liqpay_utm cookie written:', traffic);
            } catch (err) {
                console.warn('[LA] Failed to write liqpay_utm cookie', err);
            }
        }
    })();
    </script>
    <?php
});

add_action('wp_footer', function () {
    if (!function_exists('is_order_received_page') || !is_order_received_page()) return;
    if (!isset($_GET['key'])) return;

    $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $total = $order->get_total();
    $currency = $order->get_currency() ?: 'UAH';
    $transaction_id = $order->get_order_number();

    $items_js = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $items_js[] = [
            'id' => $product->get_id(),
            'name' => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'price' => $product->get_price(),
            'google_business_vertical' => 'retail'
        ];
    }
    $items_json = json_encode($items_js);
    ?>
    <script>
    try {
        const trafficStorage = localStorage.getItem('traffic_source');
        const utmCookie = document.cookie.match(/liqpay_utm=([^;]+)/);
        const cookieUTM = utmCookie ? JSON.parse(decodeURIComponent(utmCookie[1])) : null;

        const traffic = cookieUTM || (trafficStorage ? JSON.parse(trafficStorage) : {});
        document.cookie = 'liqpay_utm=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

        const clientId = document.cookie.match(/_ga=GA\d\.\d\.(\d+\.\d+)/)?.[1];
        const sessionCookie = document.cookie.match(/_ga_[A-Z0-9]+=GS\d\.\d\.(\d+)/);
        const sessionId = sessionCookie ? sessionCookie[1] : null;

        const params = {
            transaction_id: "<?php echo esc_js($transaction_id); ?>",
            value: <?php echo esc_js($total); ?>,
            currency: "<?php echo esc_js($currency); ?>",
            items: <?php echo $items_json; ?>,
            ...traffic,
            debug_mode: true
        };

        if (sessionId) {
            params.session_id = sessionId;
        }

        console.log('[LA] Purchase event params:', params);

        gtag('event', 'purchase', params);

    } catch (e) {
        console.warn('LA UTM Tracker: gtag failed', e);
    }
    </script>
    <?php
});
