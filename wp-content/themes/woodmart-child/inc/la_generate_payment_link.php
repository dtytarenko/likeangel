<?php
/**
 * Додає кнопку в адмінку замовлення WooCommerce для генерації посилання на оплату
 * та автоматично змінює статус, якщо потрібно
 */

add_action('add_meta_boxes', 'la_add_payment_link_metabox');
function la_add_payment_link_metabox() {
    add_meta_box(
        'la_payment_link_box',
        'Посилання на оплату',
        'la_payment_link_metabox_callback',
        'shop_order',
        'side',
        'default'
    );
}

function la_payment_link_metabox_callback($post) {
    $order = wc_get_order($post->ID);
    if (!$order) return;

    // Якщо статус замовлення скасований або невдалий — міняємо на очікує оплату
    if (in_array($order->get_status(), ['cancelled', 'failed'])) {
        $order->update_status('pending', 'Автоматичне оновлення для повторної оплати');
    }

    $order_id = $order->get_id();
    $order_key = $order->get_order_key();
    $payment_url = site_url("/checkout/order-pay/{$order_id}/?pay_for_order=true&key={$order_key}");

    echo '<p>Це посилання можна надіслати клієнту:</p>';
    echo '<textarea readonly style="width:100%; height:60px;">' . esc_url($payment_url) . '</textarea>';
    echo '<p><a class="button button-primary" target="_blank" href="' . esc_url($payment_url) . '">Перейти до оплати</a></p>';
}
