<div class="wcus-icon-block">
    <img src="<?= WC_UKR_SHIPPING_PLUGIN_URL . 'image/nova-poshta-icon.png'; ?>" class="wcus-icon-block__icon">
    <?php if ($ttn) { ?>
        <div class="wcus-text-center wcus-mb-5">
            <?= $ttn['ttn_id']; ?>
        </div>
        <?php if (!empty($ttn['status_code']) || !empty($ttn['status'])) { ?>
            <div class="wcus-text-center wcus-mb-5" style="color: #666;">
                <?= $ttn['status']; ?> [<?= $ttn['status_code']; ?>]
            </div>
        <?php } ?>
        <div class="wcus-text-center">
            <a target="_blank" href="<?= admin_url('admin.php?page=wc_ukr_shipping_ttn_print&ttn=' . $ttn['id']); ?>"
               class="wcus-svg-btn j-wcus-print-ttn" data-ttn="<?= $ttn['id']; ?>">
                <?= __('Print', 'wc-ukr-shipping-pro'); ?>
            </a>
            <a target="_blank" href="#" class="wcus-svg-btn wcus-svg-btn--error j-wcus-ttn-delete"
               data-ttn="<?= $ttn['id']; ?>">
                <?= __('Delete', 'wc-ukr-shipping-pro'); ?>
            </a>
        </div>
    <?php } else { ?>
        <div class="wcus-text-center">
          <a href="<?= admin_url('admin.php?page=wc_ukr_shipping_ttn&order_id=' . $order_id); ?>"
             class="wcus-svg-btn">
              <?= __('Create', 'wc-ukr-shipping-pro'); ?>
          </a>
          <a href="#" class="wcus-svg-btn j-wcus-attach-ttn" data-order-id="<?= $order_id ?>">
              <?= __('Attach', 'wc-ukr-shipping-pro'); ?>
          </a>
        </div>
    <?php } ?>
</div>