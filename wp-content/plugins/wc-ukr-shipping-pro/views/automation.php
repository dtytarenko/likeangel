<?php
    /** @var \kirillbdev\WCUkrShipping\Helpers\UIHelper $uiHelper */
?>

<?php if (isset($successMsg)) { ?>
    <div id="wcus-automation-success" class="notice inline notice-success notice-alt" style="padding-top: 10px; padding-bottom: 10px;">
        <?= $successMsg; ?>
    </div>
<?php } ?>

<form id="wcus-automation-rule-form" method="POST" action="#">
    <div class="wcus-settings wcus-settings--full">
        <div class="wcus-settings__header">
            <h1 class="wcus-settings__title">
                <?= __('Rule constructor', 'wc-ukr-shipping-pro'); ?>
            </h1>
            <button
                    type="submit"
                    class="wcus-btn wcus-btn--primary wcus-btn--md wcus-settings__submit wcus-ttn-form__submit"
                    ><?= __('Save', 'wc-ukr-shipping-pro'); ?></button>
        </div>
        <div class="wcus-settings__content">
            <input type="hidden" name="rule_id" value="<?= $model !== null ? $model->id : 0; ?>" />
            <?php
                echo $uiHelper->textField(
                    'rule_name',
                    __('Name', 'wc-ukr-shipping-pro'),
                    $model->name ?? ''
                );

                echo $uiHelper->switcherField(
                    'active',
                    __('Active', 'wc-ukr-shipping-pro'),
                    $model !== null ? (bool)$model->active : true
                );
            ?>
            <div id="wcus-automation-app"></div>
        </div>
    </div>
</form>
<script>
    (function ($) {
        $(function () {
            <?php if ($model !== null) { ?>
                window.WcusAutomation.init({
                    event: {
                        type: '<?= $model->event_name ?>',
                        params: <?= $model->event_data; ?>
                    },
                    actions: <?= json_encode($model->actions); ?>
                });
            <?php } else { ?>
                window.WcusAutomation.init();
            <?php } ?>
        });
    })(jQuery);
</script>