<?php
    /** @var \kirillbdev\WCUkrShipping\Helpers\UIHelper $uiHelper */
?>

<div id="wcus-pane-shipping" class="wcus-tab-pane">
  <?php /* Address shipping */ ?>
  <div class="wcus-form-group wcus-form-group--horizontal">
    <label class="wcus-switcher">
      <input type="checkbox" name="wc_ukr_shipping[address_shipping]" value="1" <?= (int)get_option('wc_ukr_shipping_address_shipping', 1) === 1 ? 'checked' : ''; ?>>
      <span class="wcus-switcher__control"></span>
    </label>
    <div class="wcus-control-label"><?= __('Enable address shipping', 'wc-ukr-shipping-pro'); ?></div>
  </div>

  <?php /* Total sum */ ?>
  <div class="wcus-form-group wcus-form-group--horizontal">
    <label class="wcus-switcher">
      <input type="checkbox" name="wcus[cost_view_only]" value="1" <?= (int)wcus_get_option('cost_view_only') === 1 ? 'checked' : ''; ?>>
      <span class="wcus-switcher__control"></span>
    </label>
    <div class="wcus-control-label"><?= __('Calculate shipping cost for view only', 'wc-ukr-shipping-pro'); ?></div>
  </div>

    <?php
        echo $uiHelper->switcherField(
            'wcus[rates_use_dimensions]',
            __('Consider dimensions when calculating shipping cost', 'wc-ukr-shipping-pro'),
            (int)wc_ukr_shipping_get_option('wcus_rates_use_dimensions') === 1
        );
    ?>

  <?= \kirillbdev\WCUkrShipping\Classes\View::render('partial/settings/warehouse_cost'); ?>
  <?= \kirillbdev\WCUkrShipping\Classes\View::render('partial/settings/address_cost'); ?>
  <?= \kirillbdev\WCUkrShipping\Classes\View::render('partial/settings/cod_delivery'); ?>

    <div id="wcus-settings-free-shipping"></div>

  <?php /* Language */ ?>
  <?=
    \kirillbdev\WCUkrShipping\Helpers\AdminUIHelper::selectField('wc_ukr_shipping[np_lang]', [
      'label' => __('Display language of cities and departments', 'wc-ukr-shipping-pro'),
      'options' => [
        'ru' => __('Russian', 'wc-ukr-shipping-pro'),
        'uk' => __('Ukrainian', 'wc-ukr-shipping-pro'),
      ],
      'value' => get_option('wc_ukr_shipping_np_lang', 'uk'),
    ]);
  ?>

  <?php /* Position */ ?>
  <?=
    \kirillbdev\WCUkrShipping\Helpers\AdminUIHelper::selectField('wc_ukr_shipping[np_block_pos]', [
      'label' => __('Shipping block position on checkout page', 'wc-ukr-shipping-pro'),
      'options' => [
        'billing' => __('Default section', 'wc-ukr-shipping-pro'),
        'additional' => __('Additional section', 'wc-ukr-shipping-pro'),
      ],
      'value' => wc_ukr_shipping_get_option('wc_ukr_shipping_np_block_pos'),
    ]);
  ?>

    <?php /* Store last warehouse */ ?>
    <div class="wcus-form-group wcus-form-group--horizontal">
        <label class="wcus-switcher">
            <input type="hidden" name="wc_ukr_shipping[np_save_warehouse]" value="0">
            <input type="checkbox" name="wc_ukr_shipping[np_save_warehouse]" value="1" <?= (int)get_option(WCUS_OPTION_SAVE_CUSTOMER_ADDRESS) === 1 ? 'checked' : ''; ?>>
            <span class="wcus-switcher__control"></span>
        </label>
        <div class="wcus-control-label"><?= __('Save last customer address', 'wc-ukr-shipping-pro'); ?></div>
    </div>

    <?php /* API shipping */ ?>
    <div class="wcus-form-group wcus-form-group--horizontal" style="flex-wrap:wrap;">
        <label class="wcus-switcher">
            <input type="hidden" name="wc_ukr_shipping[np_address_api_ui]" value="0">
            <input type="checkbox" name="wc_ukr_shipping[np_address_api_ui]" value="1" <?= (int)get_option('wc_ukr_shipping_np_address_api_ui') === 1 ? 'checked' : ''; ?>>
            <span class="wcus-switcher__control"></span>
        </label>
        <div class="wcus-control-label"><?= __('Use Nova Poshta API for address shipping', 'wc-ukr-shipping-pro'); ?></div>
    </div>

    <?php /* Inject additional fields */ ?>
    <div class="wcus-form-group wcus-form-group--horizontal" style="flex-wrap:wrap;">
        <label class="wcus-switcher">
            <input type="hidden" name="wcus[inject_additional_fields]" value="0">
            <input type="checkbox" name="wcus[inject_additional_fields]" value="1" <?= (int)get_option('wcus_inject_additional_fields') === 1 ? 'checked' : ''; ?>>
            <span class="wcus-switcher__control"></span>
        </label>
        <div class="wcus-control-label"><?= __('Inject additional shipping fields', 'wc-ukr-shipping-pro'); ?></div>
        <div class="wcus-form-group__tooltip" style="width:100%;">
            <?= __('This option allows you to inject Middle name and Phone fields for delivery to another address.', 'wc-ukr-shipping-pro'); ?>
        </div>
    </div>

</div>