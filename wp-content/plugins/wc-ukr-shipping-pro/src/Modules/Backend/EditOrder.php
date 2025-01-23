<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use kirillbdev\WCUkrShipping\Classes\View;
use kirillbdev\WCUkrShipping\DB\TTNRepository;
use kirillbdev\WCUkrShipping\Http\Controllers\EditOrderController;
use kirillbdev\WCUkrShipping\Model\Order\AdminEditOrderData;
use kirillbdev\WCUkrShipping\States\EditOrderState;
use kirillbdev\WCUkrShipping\Services\StateService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;
use WP_Post;

if ( ! defined('ABSPATH')) {
    exit;
}

class EditOrder implements ModuleInterface
{
    private TTNRepository $ttnRepository;

    public function __construct()
    {
        $this->ttnRepository = new TTNRepository();
    }

    /**
     * Boot function
     *
     * @return void
     */
    public function init()
    {
        add_action('add_meta_boxes', [$this, 'addTTNBlockToOrderEdit']);
        add_action('woocommerce_after_order_itemmeta', [$this, 'editBtn'], 10, 2);
        add_action('woocommerce_before_order_object_save', [$this, 'saveAdminOrder'], 10, 2);
    }

    public function routes()
    {
        return [
            new Route('wcus_save_shipping_address', EditOrderController::class, 'saveShippingAddress')
        ];
    }

    /**
     * Add TTN metabox to edit order page.
     */
    public function addTTNBlockToOrderEdit()
    {
        /** @var CustomOrdersTableController $controller */
        $controller = wcus_wc_container_safe_get(CustomOrdersTableController::class);
        $screen = $controller !== null && $controller ->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'wcus_edit_order_ttn_metabox',
            __('Invoice', 'wc-ukr-shipping-pro'),
            [$this, 'editOrderTTNMetaboxHtml'],
            $screen,
            'side'
        );
    }

    /**
     * Render edit order TTN metabox html.
     *
     * @param $post
     */
    public function editOrderTTNMetaboxHtml($editedOrder)
    {
        $order = ($editedOrder instanceof WP_Post) ? wc_get_order($editedOrder->ID) : $editedOrder;

        if ( ! $order || ( ! $order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME) && ! (int)wcus_get_option('ttn_any_shipping'))) {
            echo __('Invoice creation is unavailable for this order', 'wc-ukr-shipping-pro');

            return;
        }

        $data['ttn'] = $this->ttnRepository->getTTNByOrderId($order->get_id());
        $data['order_id'] = $order->get_id();

        echo View::render('order/edit_order_metabox', $data);
    }

    /**
     * @param int $itemId
     * @param \WC_Order_Item_Shipping $item
     */
    public function editBtn($itemId, $item)
    {
        if ( ! is_a($item, 'WC_Order_Item_Shipping') || $item->get_method_id() !== WC_UKR_SHIPPING_NP_SHIPPING_NAME) {
            return;
        }

        StateService::addState('edit_order', EditOrderState::class, [
            'order_id' => $item->get_order_id()
        ]);

        ?>
        <div class="wcus-order-shipping__edit-wrap">
            <button id="wcus-order-shipping-edit-btn" class="wcus-btn wcus-btn--outline wcus-order-shipping__edit">
                <?= __('Edit', 'wc-ukr-shipping-pro'); ?>
            </button>
        </div>
        <?php
    }

    public function saveAdminOrder(\WC_Order $order, $dataStore): void
    {
        if (!is_admin()) {
            return;
        }

        $data = new AdminEditOrderData($order, []);

        if ((int)wcus_get_option('cost_view_only')) {
            $order->set_total($data->getCalculatedTotal());
        }
    }
}