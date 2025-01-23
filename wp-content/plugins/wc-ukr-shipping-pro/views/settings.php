<?php
    /** @var \kirillbdev\WCUkrShipping\Helpers\UIHelper $uiHelper */
?>

<?= wc_ukr_shipping_render_view('partial/top_panel'); ?>

<div id="wc-ukr-shipping-settings" class="wcus-settings">
  <div class="wcus-settings__header">
    <h1 class="wcus-settings__title"><?= __('Settings', 'wc-ukr-shipping-pro'); ?> - WC Ukraine Shipping</h1>
    <button type="submit" form="wc-ukr-shipping-settings-form" class="wcus-settings__submit wcus-btn wcus-btn--primary wcus-btn--md">
        <?= __('Save', 'wc-ukr-shipping-pro'); ?>
    </button>
    <div id="wcus-settings-success-msg" class="wcus-settings__success wcus-message wcus-message--success">
      <?= wc_ukr_shipping_import_svg('success.svg'); ?>
        <?= __('Settings saved successfully', 'wc-ukr-shipping-pro'); ?>
    </div>
  </div>
  <div class="wcus-settings__content">
    <form id="wc-ukr-shipping-settings-form" action="/" method="POST">
      <?php wp_nonce_field('wp_rest'); ?>

      <ul id="wcus-settings-tabs" class="wcus-tabs">
        <li data-pane="wcus-pane-general" class="active"> <?= __('General', 'wc-ukr-shipping-pro'); ?></li>
        <li data-pane="wcus-pane-shipping"> <?= __('Shipping', 'wc-ukr-shipping-pro'); ?></li>
        <li data-pane="wcus-pane-translates"> <?= __('Translates', 'wc-ukr-shipping-pro'); ?></li>
        <li data-pane="wcus-pane-ttn"> <?= __('Invoice', 'wc-ukr-shipping-pro'); ?></li>
        <li data-pane="wcus-pane-updates"> <?= __('Updates', 'wc-ukr-shipping-pro'); ?></li>
      </ul>
      <div id="wcus-pane-general" class="wcus-tab-pane active">
          <?php
            echo $uiHelper->textField(
                'wc_ukr_shipping[np_api_key]',
                __('API key of Nova Poshta', 'wc-ukr-shipping-pro'),
                get_option('wc_ukr_shipping_np_api_key', '')
            );

            echo $uiHelper->textField(
                'wc_ukr_shipping[spinner_color]',
                __('Color of spinner in frontend', 'wc-ukr-shipping-pro'),
                get_option('wc_ukr_shipping_spinner_color', '#dddddd')
            );
          ?>

        <div class="wcus-form-group wcus-form-group--horizontal">
          <label class="wcus-switcher">
            <input type="hidden" name="wcus[checkout_new_ui]" value="0">
            <input type="checkbox" name="wcus[checkout_new_ui]" value="1" <?= (int)wcus_get_option('checkout_new_ui') === 1 ? 'checked' : ''; ?>>
            <span class="wcus-switcher__control"></span>
          </label>
          <div class="wcus-control-label"><?= __('Use new UI', 'wc-ukr-shipping-pro'); ?></div>
        </div>

        <div class="wcus-form-group wcus-form-group--horizontal">
          <label class="wcus-switcher">
              <input type="hidden" name="wcus[only_cargo_warehouses]" value="0">
              <input type="checkbox" name="wcus[only_cargo_warehouses]" value="1" <?= (int)get_option(WCUS_OPTION_ONLY_CARGO_WAREHOUSES) === 1 ? 'checked' : ''; ?>>
              <span class="wcus-switcher__control"></span>
          </label>
          <div class="wcus-control-label"><?= __('Show only cargo warehouses', 'wc-ukr-shipping-pro'); ?></div>
        </div>

        <div class="wcus-form-group wcus-form-group--horizontal">
          <label class="wcus-switcher">
            <input type="hidden" name="wcus[show_poshtomats]" value="0">
            <input type="checkbox" name="wcus[show_poshtomats]" value="1" <?= (int)get_option(WCUS_OPTION_SHOW_POSHTOMATS, 1) === 1 ? 'checked' : ''; ?>>
            <span class="wcus-switcher__control"></span>
          </label>
          <div class="wcus-control-label"><?= __('Show poshtomats', 'wc-ukr-shipping-pro'); ?></div>
        </div>

        <div class="wcus-form-group">
          <label for="wc_ukr_shipping_np_translates_type"><?= __('Load translates from', 'wc-ukr-shipping-pro'); ?></label>
          <select id="wc_ukr_shipping_np_translates_type"
                  name="wc_ukr_shipping[np_translates_type]"
                  class="wcus-form-control">
            <option value="<?= WCUS_TRANSLATE_TYPE_PLUGIN; ?>" <?= WCUS_TRANSLATE_TYPE_PLUGIN === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_translates_type') ? 'selected' : ''; ?>><?= __('Plugin settings', 'wc-ukr-shipping-pro'); ?></option>
            <option value="<?= WCUS_TRANSLATE_TYPE_MO_FILE; ?>" <?= WCUS_TRANSLATE_TYPE_MO_FILE === (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_translates_type') ? 'selected' : ''; ?>><?= __('Wordpress localization files', 'wc-ukr-shipping-pro'); ?></option>
          </select>
          <div class="wcus-form-group__tooltip">
              <?= __('If you are using language plugins such as WPML or Polylang - select "Wordpress localization files" option', 'wc-ukr-shipping-pro'); ?>
          </div>
        </div>

        <div id="wcus-warehouse-loader"></div>
      </div>

        <?=
            \kirillbdev\WCUkrShipping\Classes\View::render('partial/options_shipping', [
                'uiHelper' => $uiHelper,
            ]);
        ?>

      <div id="wcus-pane-translates" class="wcus-tab-pane">
          <?php
              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_method_title]',
                  __('Shipping method name', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_method_title')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_block_title]',
                  __('Shipping block title', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_block_title')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_placeholder_area]',
                  __('Placeholder of select area field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_area')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_placeholder_city]',
                  __('Placeholder of select city field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_city')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_placeholder_warehouse]',
                  __('Placeholder of select warehouse field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_placeholder_warehouse')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_address_title]',
                  __('Label of address shipping', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_title')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_address_placeholder]',
                  __('Placeholder of address shipping', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_address_placeholder')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_settlement_label]',
                  __('Label of settlement field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_label')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_settlement_placeholder]',
                  __('Placeholder of settlement field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_settlement_placeholder')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_street_label]',
                  __('Label of street field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_label')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_street_placeholder]',
                  __('Placeholder of street field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_street_placeholder')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_house_label]',
                  __('Label of house field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_house_label')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_house_placeholder]',
                  __('Placeholder of house field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_house_placeholder')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_flat_label]',
                  __('Label of flat field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat_label')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_flat_placeholder]',
                  __('Placeholder of flat field', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_flat_placeholder')
              );

              echo $uiHelper->textField(
                  'wc_ukr_shipping[np_not_found_text]',
                  __('Empty result text', 'wc-ukr-shipping-pro'),
                  wc_ukr_shipping_get_option('wc_ukr_shipping_np_not_found_text')
              );

              echo $uiHelper->textField(
                  'wcus[np_validate_error]',
                  __('Validation error text', 'wc-ukr-shipping-pro'),
                  esc_attr(wc_ukr_shipping_get_option('wcus_np_validate_error'))
              );

              echo $uiHelper->textField(
                  'wcus[l10n_error_settlement]',
                  __('Settlement Validation error text', 'wc-ukr-shipping-pro'),
                  esc_attr(wcus_get_option('l10n_error_settlement'))
              );

              echo $uiHelper->textField(
                  'wcus[l10n_error_street]',
                  __('Street Validation error text', 'wc-ukr-shipping-pro'),
                  esc_attr(wcus_get_option('l10n_error_street'))
              );

              echo $uiHelper->textField(
                  'wcus[l10n_error_house]',
                  __('House Validation error text', 'wc-ukr-shipping-pro'),
                  esc_attr(wcus_get_option('l10n_error_house'))
              );
          ?>

      </div>

      <div id="wcus-pane-ttn" class="wcus-tab-pane">

        <?= wc_ukr_shipping_render_view('partial/options_sender_default', [
          'counterparties' => $sender_counterparties,
          'sender_contacts' => $sender_contacts,
          'sender_ref' => $sender_ref,
          'sender_contact_ref' => $sender_contact_ref,
          'counterparty_api_error' => isset($counterparty_api_error) ? $counterparty_api_error : null
        ]); ?>

        <div id="wcus-settings-ttn-warehouse"></div>
        <div id="wcus-settings-ttn-doors"></div>

        <div class="wcus-form-group">
          <label for="wc_ukr_shipping_ttn_payer_default"><?= __('Default payer', 'wc-ukr-shipping-pro'); ?></label>
          <select id="wc_ukr_shipping_ttn_payer_default" name="wc_ukr_shipping[np_ttn_payer_default]" class="wcus-form-control">
            <option value="Sender" <?= 'Sender' === wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default') ? 'selected' : '' ?>><?= __('Sender', 'wc-ukr-shipping-pro'); ?></option>
            <option value="Recipient" <?= 'Recipient' === wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_payer_default') ? 'selected' : '' ?>><?= __('Recipient', 'wc-ukr-shipping-pro'); ?></option>
          </select>
        </div>

        <div class="wcus-form-group">
          <label for="wcus_send_from_default"><?= __('Send as default from', 'wc-ukr-shipping-pro'); ?></label>
          <select id="wcus_send_from_default" name="wcus[send_from_default]" class="wcus-form-control">
            <option value="Warehouse" <?= 'Warehouse' === wc_ukr_shipping_get_option('wcus_send_from_default') ? 'selected' : ''; ?>><?= __('Warehouse', 'wc-ukr-shipping-pro'); ?></option>
            <option value="Address" <?= 'Address' === wc_ukr_shipping_get_option('wcus_send_from_default') ? 'selected' : ''; ?>><?= __('Address', 'wc-ukr-shipping-pro'); ?></option>
          </select>
        </div>

        <div class="wcus-form-group">
          <label for="wc_ukr_shipping_np_ttn_description"><?= __('Default shipment description', 'wc-ukr-shipping-pro'); ?></label>
          <input type="text" id="wc_ukr_shipping_np_ttn_description"
                 name="wc_ukr_shipping[np_ttn_description]"
                 class="wcus-form-control"
                 value="<?= wc_ukr_shipping_get_option('wc_ukr_shipping_np_ttn_description'); ?>">
        </div>

        <div class="wcus-form-group">
          <label for="wcus_ttn_weight_default"><?= __('Default weight (kg)', 'wc-ukr-shipping-pro'); ?></label>
          <input type="text" min="0" step="0.01" id="wcus_ttn_weight_default"
                 name="wcus[ttn_weight_default]"
                 class="wcus-form-control"
                 value="<?= (float)wcus_get_option('ttn_weight_default'); ?>">
          <div class="wcus-form-group__tooltip">
              <?= __('To disable this feature set value to 0', 'wc-ukr-shipping-pro'); ?>
          </div>
        </div>

      <?php
          echo $uiHelper->switcherField(
              'wcus[ttn_pay_control_default]',
              __('Payment control', 'wc-ukr-shipping-pro'),
              (int)wcus_get_option('ttn_pay_control_default') === 1
          );

          echo $uiHelper->switcherField(
              'wcus[ttn_any_shipping]',
              __('Allow create invoice for any shipping methods', 'wc-ukr-shipping-pro'),
              (int)wcus_get_option('ttn_any_shipping') === 1
          );

          echo $uiHelper->textField(
              'wc_ukr_shipping[np_mail_subject]',
              __('Mail subject', 'wc-ukr-shipping-pro'),
              wc_ukr_shipping_get_option('wc_ukr_shipping_np_mail_subject')
          );
      ?>

        <div class="wcus-form-group">
          <label><?= __('Mail template', 'wc-ukr-shipping-pro'); ?></label>
	        <?php
            wp_editor(wp_specialchars_decode(wc_ukr_shipping_get_option('wc_ukr_shipping_np_mail_tpl')), 'wpeditor', [
              'textarea_name' => 'wc_ukr_shipping[np_mail_tpl]',
              'teeny' => true,
              'tinymce' => false,
              'media_buttons' => false
            ]);
          ?>
        </div>

          <?php
                echo $uiHelper->switcherField(
                'wc_ukr_shipping[np_auto_send_mail]',
                __('Automatically send an email when creating an invoice', 'wc-ukr-shipping-pro'),
                (int)wc_ukr_shipping_get_option('wc_ukr_shipping_np_auto_send_mail') === 1
                );
          ?>

      </div>

      <div id="wcus-pane-updates" class="wcus-tab-pane">
          <div id="wcus-settings-license"></div>
          <div style="margin-top: 15px;">
              <?php
                  echo $uiHelper->switcherField(
                      'wcus[use_cloud_address_api]',
                      __('Enable cloud address API', 'wc-ukr-shipping-pro'),
                      (int)wc_ukr_shipping_get_option('wcus_use_cloud_address_api') === 1
                  );

                  echo $uiHelper->switcherField(
                      'wcus[tracking_auto_send]',
                      __('Automatically add invoices to tracking', 'wc-ukr-shipping-pro'),
                      (int)wc_ukr_shipping_get_option('wcus_tracking_auto_send') === 1
                  );
              ?>
          </div>
      </div>

    </form>
  </div>
</div>