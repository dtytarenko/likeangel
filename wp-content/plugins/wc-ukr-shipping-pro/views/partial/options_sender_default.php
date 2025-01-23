<div class="wcus-control-group">
  <div class="wcus-control-group__title"><?= __('Default sender', 'wc-ukr-shipping-pro'); ?></div>
  <div class="wcus-control-group__content">
    <?php if ($counterparty_api_error) { ?>
      <div class="wcus-alert wcus-alert--error">
        <?= $counterparty_api_error; ?>
      </div>
    <?php } else { ?>
      <div class="wcus-form-group">
        <label for="wcus_np_sender_ref"><?= __('Sender identifier', 'wc-ukr-shipping-pro'); ?></label>
        <select id="wcus_np_sender_ref" name="wc_ukr_shipping[np_sender_ref]" class="wcus-form-control">
          <option value=""><?= __('Select sender', 'wc-ukr-shipping-pro'); ?></option>
          <?php foreach ($counterparties as $counterparty) { ?>
            <option value="<?= $counterparty['Ref']; ?>" <?= $counterparty['Ref'] === $sender_ref ? 'selected' : ''; ?>><?= $counterparty['Description']; ?></option>
          <?php } ?>
        </select>
      </div>
      <div class="wcus-form-group">
        <label for="wcus_np_sender_contact_ref"><?= __('Sender contact', 'wc-ukr-shipping-pro'); ?></label>
        <select id="wcus_np_sender_contact_ref" name="wc_ukr_shipping[np_sender_contact_ref]" class="wcus-form-control">
          <option value=""><?= __('Select contact', 'wc-ukr-shipping-pro'); ?></option>
          <?php foreach ($sender_contacts as $contact) { ?>
            <option value="<?= $contact['Ref']; ?>" <?= $contact['Ref'] === $sender_contact_ref ? 'selected' : ''; ?>><?= $contact['Description']; ?></option>
          <?php } ?>
        </select>
      </div>
    <?php } ?>
  </div>
</div>