<?php

namespace kirillbdev\WCUkrShipping\Services\Checkout;

use kirillbdev\WCUkrShipping\DB\NovaPoshtaRepository;
use kirillbdev\WCUkrShipping\Helpers\HtmlHelper;
use kirillbdev\WCUkrShipping\Services\StorageService;
use kirillbdev\WCUkrShipping\Services\TranslateService;

if (!defined('ABSPATH')) {
    exit;
}

class LegacyCheckoutService
{
    /**
     * @var TranslateService
     */
    private $translator;

    /**
     * Cache translates of shipping block.
     *
     * @var array
     */
    private $translates;

    /**
     * Cache area select attributes of shipping block.
     *
     * @var array
     */
    private $areaAttributes;

    /**
     * Cache city select attributes of shipping block.
     *
     * @var array
     */
    private $cityAttributes;

    /**
     * Cache warehouse select attributes of shipping block.
     *
     * @var array
     */
    private $warehouseAttributes;

    /**
     * CheckoutService constructor.
     */
    public function __construct()
    {
        $this->translator = new TranslateService();
    }

    public function renderCheckoutFields($type)
    {
        $this->initShippingBlockAttributes();

        $hideBilling = 'billing' === $type && 'shipping' === get_option('woocommerce_ship_to_destination')
            ? 'style="display:none;"'
            : '';

        $rowClass = $this->checkoutValidationActive() ? 'form-row validate-required' : 'form-row';
        $labelSuffix = $this->checkoutValidationActive()
            ? '&nbsp;<abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>'
            : '';
      ?>
      <div id="wcus_np_<?= $type; ?>_fields" class="wc-ukr-shipping-np-fields" <?= $hideBilling; ?>>
          <?php if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) { ?>
        <div class="j-wcus-warehouse-block">
            <?php } ?>
          <h3><?= $this->translates['block_title']; ?></h3>
            <?php
            $this->renderAreaField($type);
            $this->renderCityField($type);
            ?>
            <?php if (0 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) { ?>
          <div class="j-wcus-warehouse-block">
              <?php } ?>
              <?php $this->renderWarehouseField($type); ?>
              <?php if (0 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) { ?>
          </div>
        <?php } ?>
            <?php if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) { ?>
        </div>
      <?php } ?>

          <?php if ((int)get_option('wc_ukr_shipping_address_shipping', 1) === 1) { ?>
            <div class="wc-urk-shipping-form-group" style="padding: 10px 5px;">
              <label class="wc-ukr-shipping-checkbox">
                <input id="wcus_np_<?= $type; ?>_custom_address_active"
                       type="checkbox"
                       name="wcus_np_<?= $type; ?>_custom_address_active"
                       class="j-wcus-np-custom-address"
                       data-relation-select="<?= 'billing' === $type ? 'wcus_np_shipping_custom_address_active' : 'wcus_np_billing_custom_address_active'; ?>"
                       value="1">
                  <?= $this->translates['address_title']; ?>
              </label>
            </div>

            <div class="wcus-np-api-address-block j-wcus-np-custom-address-block" style="display: none;">
                <?php if (1 === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_api_ui')) { ?>
                  <div class="<?= $rowClass; ?>">
                    <label><?= $this->translates['settlement_label'] . $labelSuffix; ?></label>
                    <div class="woocommerce-input-wrapper">
                        <?php HtmlHelper::renderSettlementControl('wcus_np_' . $type . '_settlement', [
                            'placeholder' => $this->translates['settlement_placeholder']
                        ]); ?>
                    </div>
                  </div>

                  <div class="<?= $rowClass; ?>">
                    <label><?= $this->translates['street_label'] . $labelSuffix; ?></label>
                    <div class="woocommerce-input-wrapper">
                        <?php HtmlHelper::renderStreetControl('wcus_np_' . $type . '_street', [
                            'placeholder' => $this->translates['street_placeholder']
                        ]); ?>
                    </div>
                  </div>

                  <div class="<?= $rowClass; ?>">
                    <label for="wcus_np_<?= $type; ?>_house"><?= $this->translates['house_label'] . $labelSuffix; ?></label>
                    <div class="woocommerce-input-wrapper">
                      <input type="text" name="wcus_np_<?= $type; ?>_house" id="wcus_np_<?= $type; ?>_house"
                             class="input-text" placeholder="<?= $this->translates['house_placeholder']; ?>">
                    </div>
                  </div>

                  <div class="form-row">
                    <label for="wcus_np_<?= $type; ?>_flat"><?= $this->translates['flat_label']; ?></label>
                    <div class="woocommerce-input-wrapper">
                      <input type="text" name="wcus_np_<?= $type; ?>_flat" id="wcus_np_<?= $type; ?>_flat"
                             class="input-text" placeholder="<?= $this->translates['flat_placeholder']; ?>">
                    </div>
                  </div>
                <?php } else { ?>
                    <?php
                    // Custom address field
                    woocommerce_form_field('wcus_np_' . $type . '_custom_address', [
                        'type' => 'text',
                        'input_class' => [
                            'input-text'
                        ],
                        'label' => '',
                        'placeholder' => $this->translates['address_placeholder'],
                        'default' => StorageService::getValue('wc_ukr_shipping_np_custom_address', '')
                    ]);
                    ?>
                <?php } ?>
            </div>
          <?php } ?>
      </div>
        <?php
    }

    private function initShippingBlockAttributes()
    {
        if ($this->translates) {
            return;
        }

        $this->translates = $this->translator->getTranslates();
        $this->areaAttributes = $this->getAreaSelectAttributes($this->translates['placeholder_area']);
        $this->cityAttributes = $this->getCitySelectAttributes($this->translates['placeholder_city']);
        $this->warehouseAttributes = $this->getWarehouseSelectAttributes($this->translates['placeholder_warehouse']);
    }

    private function getAreaSelectAttributes($placeholder)
    {
        $options = [
            '' => $placeholder
        ];

        $repository = new NovaPoshtaRepository();
        $areas = $this->translator->translateAreas($repository->getAreas());

        foreach ($areas as $area) {
            $options[$area['ref']] = $area['description'];
        }

        return [
            'options' => $options,
            'default' => StorageService::getValue('wc_ukr_shipping_np_selected_area', '')
        ];
    }

    private function getCitySelectAttributes($placeholder)
    {
        $options = [
            '' => $placeholder
        ];

        if (StorageService::getValue('wc_ukr_shipping_np_selected_area')) {
            $repository = new NovaPoshtaRepository();
            $cities = $repository->getCities(StorageService::getValue('wc_ukr_shipping_np_selected_area'));

            foreach ($cities as $city) {
                $options[$city['ref']] = 'uk' === $this->translator->getCurrentLanguage() ?
                    $city['description'] :
                    $city['description_ru'];
            }
        }

        return [
            'options' => $options,
            'default' => StorageService::getValue('wc_ukr_shipping_np_selected_city', '')
        ];
    }

    private function getWarehouseSelectAttributes($placeholder)
    {
        $options = [
            '' => $placeholder
        ];

        if (StorageService::getValue('wc_ukr_shipping_np_selected_city')) {
            $repository = new NovaPoshtaRepository();
            $warehouses = $repository->getWarehouses(StorageService::getValue('wc_ukr_shipping_np_selected_city'));

            foreach ($warehouses as $warehouse) {
                $options[$warehouse['ref']] = 'uk' === $this->translator->getCurrentLanguage() ?
                    $warehouse['description'] :
                    $warehouse['description_ru'];
            }
        }

        return [
            'options' => $options,
            'default' => StorageService::getValue('wc_ukr_shipping_np_selected_warehouse', '')
        ];
    }

    private function renderAreaField($type)
    {
        ?>
        <p class="form-row" id="wcus_np_<?= $type; ?>_area_field">
          <span class="woocommerce-input-wrapper">
            <select name="wcus_np_<?= $type; ?>_area" id="wcus_np_<?= $type; ?>_area"
                    class="select wc-ukr-shipping-select j-wcus-np-area-select"
                    data-relation-select="<?= 'billing' === $type ? 'wcus_np_shipping_area' : 'wcus_np_billing_area'; ?>">
              <?php foreach ($this->areaAttributes['options'] as $ref => $option) { ?>
                <option
                    value="<?= $ref; ?>" <?= $this->areaAttributes['default'] === $ref ? 'selected' : ''; ?>><?= $option; ?></option>
              <?php } ?>
            </select>
          </span>
        </p>
        <?php
    }

    private function renderCityField($type)
    {
        ?>
        <p class="form-row" id="wcus_np_<?= $type; ?>_city_field">
          <span class="woocommerce-input-wrapper">
            <select name="wcus_np_<?= $type; ?>_city" id="wcus_np_<?= $type; ?>_city"
                    class="select wc-ukr-shipping-select j-wcus-np-city-select"
                    data-relation-select="<?= 'billing' === $type ? 'wcus_np_shipping_city' : 'wcus_np_billing_city'; ?>">
              <?php foreach ($this->cityAttributes['options'] as $ref => $option) { ?>
                <option
                    value="<?= $ref; ?>" <?= $this->cityAttributes['default'] === $ref ? 'selected' : ''; ?>><?= $option; ?></option>
              <?php } ?>
            </select>
          </span>
        </p>
        <?php
    }

    private function renderWarehouseField($type)
    {
        ?>
        <p class="form-row" id="wcus_np_<?= $type; ?>_warehouse_field">
          <span class="woocommerce-input-wrapper">
            <select name="wcus_np_<?= $type; ?>_warehouse" id="wcus_np_<?= $type; ?>_warehouse"
                    class="select wc-ukr-shipping-select j-wcus-np-warehouse-select"
                    data-relation-select="<?= 'billing' === $type ? 'wcus_np_shipping_warehouse' : 'wcus_np_billing_warehouse'; ?>">
              <?php foreach ($this->warehouseAttributes['options'] as $ref => $option) { ?>
                <option
                    value="<?= $ref; ?>" <?= $this->warehouseAttributes['default'] === $ref ? 'selected' : ''; ?>><?= $option; ?></option>
              <?php } ?>
            </select>
          </span>
        </p>
        <?php
    }

    /**
     * @return bool
     */
    private function checkoutValidationActive()
    {
        return true === apply_filters('wcus_checkout_validation_active', true);
    }
}