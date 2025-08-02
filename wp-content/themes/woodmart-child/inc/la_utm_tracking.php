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
            } catch (err) {
            }
        }
    })();
    </script>
    <?php
});

add_action( 'wp_footer', function () {
    if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
        return;
    }
    if ( ! isset( $_GET['key'] ) ) {
        return;
    }

    $order_id = wc_get_order_id_by_order_key( sanitize_text_field( $_GET['key'] ) );
    $order    = $order_id ? wc_get_order( $order_id ) : null;
    if ( ! $order ) {
        return;
    }

    // --- готуємо payload ----
    $items = [];
    foreach ( $order->get_items() as $item ) {
        $product   = $item->get_product();
        $items[] = [
            'id'    => $product ? $product->get_id() : $item->get_product_id(),
            'name'  => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'price' => $product ? $product->get_price() : 0,
        ];
    }

    $payload = [
        'transaction_id' => $order->get_order_number(),
        'value'          => $order->get_total(),
        'currency'       => $order->get_currency() ?: 'UAH',
        'items'          => $items,
        // підтягуємо UTM із localStorage / cookie на клієнті ↓
    ];
    ?>
    <script>
        (function () {
            try {
                // 1️⃣ UTM (traffic_source → localStorage)  
                const traffic = JSON.parse(localStorage.getItem('traffic_source') || '{}');

                // 2️⃣ Google Client ID та Session ID
                const cid = document.cookie.match(/_ga=GA\d\.\d\.(\d+\.\d+)/)?.[1];
                const sid = document.cookie.match(/_ga_[A-Z0-9]+=GS\d\.\d\.(\d+)/)?.[1];

                const params = {
                    ...<?php echo wp_json_encode( $payload ); ?>,
                    ...traffic,
                    client_id: cid || undefined,
                    session_id: sid || undefined
                };

                window.__purchaseParams = params;
            } catch (e) {
            }
        })();
    </script>
    <?php
} );
