<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Classes\WCUkrShipping;
use kirillbdev\WCUkrShipping\DB\TTNRepository;
use kirillbdev\WCUkrShipping\Http\Controllers\TTNController;
use kirillbdev\WCUkrShipping\Http\Middleware\VerifyAttachTTNRequest;
use kirillbdev\WCUkrShipping\Services\PrintService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;

if ( ! defined('ABSPATH')) {
    exit;
}

class Orders implements ModuleInterface
{
    /**
     * @var PrintService
     */
    private $printService;

    /**
     * @var TTNRepository
     */
    private $ttnRepository;

    public function __construct()
    {
        $this->printService = WCUkrShipping::instance()->singleton('print_service');
        $this->ttnRepository = new TTNRepository();
    }

    public function init()
    {
        add_action('init', [$this, 'maybePrintTTN']);

        // Admin orders
        add_filter('manage_edit-shop_order_columns', [$this, 'extendOrderColumns']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'renderTTNButton'], 10, 2);
        // Admin orders HPOS
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'extendOrderColumns']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'renderTTNButtonHPOS'], 10, 2);
        // Account orders
        add_filter('woocommerce_account_orders_columns', [$this, 'extendAccountOrderColumns']);
        add_action( 'woocommerce_my_account_my_orders_column_wcus_ttn_number', [$this, 'renderAccountTtnNumber']);
    }

    /**
     * @return Route[]
     */
    public function routes()
    {
        return [
            new Route('wcus_ttn_attach', TTNController::class, 'attachTTN', [
                'middleware' => [
                    VerifyAttachTTNRequest::class
                ]
            ]),
            new Route('wcus_ttn_delete', TTNController::class, 'deleteTTN')
        ];
    }

    public function extendOrderColumns($columns)
    {
        $columns['wcus_ttn_actions'] = '<span class="wcus-np-icon">' . __('Invoice', 'wc-ukr-shipping-pro') . '</span>';
        $columns['wcus_ttn_number'] = __('Invoice number', 'wc-ukr-shipping-pro');

        return $columns;
    }

    public function renderTTNButton($column, $postId)
    {
        $this->renderTtnInfo(wc_get_order($postId), $column);
    }

    public function renderTTNButtonHPOS($column, $order)
    {
        $this->renderTtnInfo($order, $column);
    }

    public function maybePrintTTN()
    {
        if (!$this->isAdminPermissions() || !$this->isTTNPrintPage() || !get_option('wc_ukr_shipping_np_api_key')) {
            return;
        }

        $ttnList = explode(',', $_GET['ttn']);
        $ttnToPrint = [];

        foreach ($ttnList as $id) {
            $ttn = $this->ttnRepository->getTTNById($id);

            if ($ttn) {
                $ttnToPrint[] = $ttn['ttn_id'];
            }
        }

        if (!$ttnToPrint) {
            return;
        }

        header('Content-Type: application/pdf');

        echo $this->printService->printTTNByType($ttnToPrint, $_GET['print_type'], $_GET['copies']);

        exit;
    }

    public function extendAccountOrderColumns(array $columns): array
    {
        $newColumns = [];
        foreach ($columns as $key => $column) {
            $newColumns[$key] = $column;
            if ($key === 'order-total') {
                $newColumns['wcus_ttn_number'] = __('Invoice number', 'wc-ukr-shipping-pro');
            }
        }

        return $newColumns;
    }

    /**
     * @param \WC_Order $order
     * @return void
     */
    public function renderAccountTtnNumber($order): void
    {
        $ttn = $this->ttnRepository->getTTNByOrderId($order->get_id());
        if ($ttn) {
            $html = sprintf(
        '<div><a target="_blank" href="https://novaposhta.ua/tracking/?cargo_number=%s">%s</a></div>',
                $ttn['ttn_id'],
                $ttn['ttn_id']
            );
            echo apply_filters('wcus_my_account_order_ttn_number_html', $html, $ttn['ttn_id'], $order);
        }
    }

    private function isAdminPermissions()
    {
        return is_admin();
    }

    private function isTTNPrintPage()
    {
        return isset($_GET['page'])
            && $_GET['page'] === 'wc_ukr_shipping_ttn_print'
            && isset($_GET['ttn'])
            && isset($_GET['print_type'])
            && isset($_GET['copies']);
    }

    private function renderTtnInfo($order, string $column): void
    {
        if ( ! $order->has_shipping_method(WC_UKR_SHIPPING_NP_SHIPPING_NAME) && ! (int)wcus_get_option('ttn_any_shipping')) {
            return;
        }

        $ttn = $this->ttnRepository->getTTNByOrderId($order->get_id());

        if ($column === 'wcus_ttn_actions') {
            if ($ttn) {
                ?>
                <div style="text-align: center;">
                    <a target="_blank"
                       href="<?= admin_url('admin.php?page=wc_ukr_shipping_ttn_print&ttn=' . $ttn['id']); ?>"
                       class="wcus-svg-btn j-wcus-print-ttn" data-ttn="<?= $ttn['id']; ?>">
                        <?= __('Print', 'wc-ukr-shipping-pro'); ?>
                    </a>
                    <a href="#" class="wcus-svg-btn wcus-svg-btn--error j-wcus-ttn-delete"
                       data-ttn="<?= $ttn['id']; ?>">
                        <?= __('Delete', 'wc-ukr-shipping-pro'); ?>
                    </a>
                </div>
                <?php
            } else {
                ?>
                <div style="text-align: center;">
                    <a href="<?= admin_url('admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order->get_id()); ?>"
                       class="wcus-svg-btn">
                        <?= __('Create', 'wc-ukr-shipping-pro'); ?>
                    </a>
                    <a href="#" class="wcus-svg-btn j-wcus-attach-ttn" data-order-id="<?= $order->get_id(); ?>">
                        <?= __('Attach', 'wc-ukr-shipping-pro'); ?>
                    </a>
                </div>
                <?php
            }
        } elseif ($column === 'wcus_ttn_number') {
            if ($ttn) {
                $html = '<div>' . $ttn['ttn_id'] . '</div>';
                echo apply_filters('wcus_admin_order_ttn_number_html', $html, $ttn['ttn_id'], $order);
            }
        }
    }
}