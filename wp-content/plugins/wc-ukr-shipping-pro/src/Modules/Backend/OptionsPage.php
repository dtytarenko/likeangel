<?php

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Api\NovaPoshtaApi;
use kirillbdev\WCUkrShipping\Classes\View;
use kirillbdev\WCUkrShipping\Component\ListTable\AutomationListTable;
use kirillbdev\WCUkrShipping\DB\Repositories\AutomationRulesRepository;
use kirillbdev\WCUkrShipping\DB\TTNRepository;
use kirillbdev\WCUkrShipping\Exceptions\ApiServiceException;
use kirillbdev\WCUkrShipping\Exceptions\TTNServiceException;
use kirillbdev\WCUkrShipping\Helpers\UIHelper;
use kirillbdev\WCUkrShipping\Http\Controllers\AddressBookController;
use kirillbdev\WCUkrShipping\Http\Controllers\AutomationController;
use kirillbdev\WCUkrShipping\Http\Controllers\OptionsController;
use kirillbdev\WCUkrShipping\Services\StateService;
use kirillbdev\WCUkrShipping\States\SettingsState;
use kirillbdev\WCUkrShipping\States\WarehouseLoaderState;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;
use kirillbdev\WCUSCore\Http\Routing\Route;
use kirillbdev\WCUkrShipping\Model\Document\TTNStore;

if ( ! defined('ABSPATH')) {
    exit;
}

class OptionsPage implements ModuleInterface
{
    private AutomationListTable $table;
    private AutomationRulesRepository $automationRulesRepository;

    public function __construct(AutomationRulesRepository $automationRulesRepository)
    {
        $this->automationRulesRepository = $automationRulesRepository;
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'registerOptionsPage'], 99);
    }

    public function routes(): array
    {
        return [
            new Route('wcus_save_options', OptionsController::class, 'saveOptions'),
            new Route('wcus_load_areas', AddressBookController::class, 'loadAreas'),
            new Route('wcus_load_cities', AddressBookController::class, 'loadCities'),
            new Route('wcus_load_warehouses', AddressBookController::class, 'loadWarehouses'),
            new Route('wcus_automation_save_rule', AutomationController::class, 'saveRule'),
        ];
    }

    public function registerOptionsPage(): void
    {
        $permissionsMap = [
            'manage_options' => 'manage_options',
            'manage_ttn' => 'manage_woocommerce',
            'manage_bulk_ttns' => 'manage_woocommerce',
            'manage_automation' => 'manage_woocommerce',
        ];

        /**
         * Allows to change plugin permission map on admin panel.
         *
         * @since 1.16.7
         */
        $permissionsMap = apply_filters('wcus_admin_permission_map', $permissionsMap);

        add_menu_page(
            __('Settings', 'wc-ukr-shipping-pro'),
            'WC Ukr Shipping',
            $permissionsMap['manage_options'],
            'wc_ukr_shipping_options',
            [$this, 'html'],
            WC_UKR_SHIPPING_PLUGIN_URL . 'image/menu-icon.png',
            '56.15'
        );

        add_submenu_page(
            null,
            __('Create TTN', 'wc-ukr-shipping-pro'),
            __('Create TTN', 'wc-ukr-shipping-pro'),
            $permissionsMap['manage_ttn'],
            'wc_ukr_shipping_ttn',
            [$this, 'ttnHtml']
        );

        add_submenu_page(
            'wc_ukr_shipping_options',
            __('Orders', 'wc-ukr-shipping-pro'),
            __('Orders', 'wc-ukr-shipping-pro'),
            $permissionsMap['manage_bulk_ttns'],
            'wc_ukr_shipping_ttn_list',
            [$this, 'orderListHtml']
        );

        $automationPage = add_submenu_page(
            'wc_ukr_shipping_options',
            __('Automation', 'wc-ukr-shipping-pro'),
            __('Automation', 'wc-ukr-shipping-pro'),
            $permissionsMap['manage_automation'],
            'wcus_automation',
            [$this, 'automationHtml']
        );
        add_action("load-$automationPage", function () {
            $this->table = new AutomationListTable($this->automationRulesRepository);
        });

        add_submenu_page(
            '',
            __('Create', 'wc-ukr-shipping-pro'),
            __('Create', 'wc-ukr-shipping-pro'),
            $permissionsMap['manage_automation'],
            'wcus_automation_rule_create',
            [$this, 'automationRuleFormHtml']
        );

        add_submenu_page(
            '',
            __('Edit', 'wc-ukr-shipping-pro'),
            __('Edit', 'wc-ukr-shipping-pro'),
            $permissionsMap['manage_automation'],
            'wcus_automation_rule_edit',
            [$this, 'automationRuleFormHtml']
        );
    }

    public function html()
    {
        StateService::addState('settings', SettingsState::class);
        StateService::addState('warehouse_loader', WarehouseLoaderState::class);

        /**
         * @var $api NovaPoshtaApi
         */
        $api = wcus_container_singleton('api');
        $data['uiHelper'] = new UIHelper();

        try {
            $response = $api->getCounterParties('Sender');

            if ($response['success']) {
                $data['sender_counterparties'] = $response['data'];
            } else {
                $data['sender_counterparties'] = [];
            }

            $data['sender_ref'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_ref');
            $data['sender_contact_ref'] = wc_ukr_shipping_get_option('wc_ukr_shipping_np_sender_contact_ref');
            $data['sender_contacts'] = [];

            if ($data['sender_ref']) {
                $response = $api->getCounterpartyContacts($data['sender_ref']);

                if ($response['success']) {
                    $data['sender_contacts'] = $response['data'];
                }
            }
        } catch (ApiServiceException $e) {
            $data['counterparty_api_error'] = $e->getMessage();
        }

        echo View::render('settings', $data);
    }

    public function ttnHtml()
    {
        if ( ! isset($_GET['order_id']) || ! (int)$_GET['order_id']) {
            return;
        }

        $ttnRepository = new TTNRepository();
        $maybeTTNExist = $ttnRepository->getTTNByOrderId($_GET['order_id']);

        if ($maybeTTNExist) {
            return;
        }

        wp_enqueue_script(
            'wcus_ttn_form_js',
            WC_UKR_SHIPPING_PLUGIN_URL . 'assets/js/ttn-form.min.js',
            [ 'wcus_core_js' ],
            filemtime(WC_UKR_SHIPPING_PLUGIN_DIR . 'assets/js/ttn-form.min.js'),
            true
        );

        try {
            $store = new TTNStore($_GET['order_id']);
            wp_localize_script('wcus_ttn_form_js', 'wcus_ttn_form_state', $store->collect());

            echo View::render('ttn');
        } catch (ApiServiceException $e) {
            echo $e->getMessage();
        } catch (TTNServiceException $e) {
            echo $e->getMessage();
        }
    }

    public function orderListHtml()
    {
        echo View::render('order-list');
    }

    public function automationHtml()
    {
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wcus_automation_delete')) {
                $this->automationRulesRepository->delete((int)$_GET['id']);
            }
        }

        $this->table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?= get_admin_page_title(); ?></h1>
            <a href="<?= admin_url('admin.php?page=wcus_automation_rule_create'); ?>" class="page-title-action"><?= __('Add rule', 'wc-ukr-shipping-pro'); ?></a>
            <hr class="wp-header-end">
            <form action="" method="POST">
                <?php $this->table->display(); ?>
            </form>
        </div>
        <?php
    }

    public function automationRuleFormHtml(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $model = null;
        if ($id > 0) {
            $model = $this->automationRulesRepository->findById($id);
            if ($model === null) {
                echo sprintf(
                        '<div class="notice notice-error">%s</div>',
                        __('Rule not found', 'wc-ukr-shipping-pro')
                );
                return;
            }
        }

        echo View::render('automation', [
            'uiHelper' => new UIHelper(),
            'model' => $model,
            'successMsg' => isset($_GET['success']) && $_GET['success'] === '1'
                ? __('Rule saved successfully', 'wc-ukr-shipping-pro')
                : null,
        ]);
    }
}
