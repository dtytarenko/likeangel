<?php
/**
 * Class for add liqpay to wordpress menu
 * 
 * */
Class MorkvaLiqpayMenu
{
    /**
     * Slug for page in Woo Tab Sections
     * 
     * */
    public $slug = 'admin.php?page=wc-settings&tab=checkout&section=morkva-liqpay';

    /**
     * Slug for page in Woo Tab Sections Payparts
     * 
     * */
    public $slug_payparts = 'admin.php?page=wc-settings&tab=checkout&section=morkva-liqpay-payparts';

    /**
     * Slug for page in Woo Tab Sections Payparts
     * 
     * */
    public $slug_prepay = 'admin.php?page=wc-settings&tab=checkout&section=morkva-liqpay-prepay';

    /**
     * Constructor for create menu
     * 
     * */
    public function __construct()
    {
        # Add menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
    }

    /**
     * Register menu page
     * 
     * */
    public function register_admin_menu()
    {
        # Add menu Liqpay
        add_menu_page(__('Morkva LiqPay Pro', 'mrkv-liqpay-extended-pro'), __('Morkva LiqPay Pro', 'mrkv-liqpay-extended-pro'), 'manage_options', $this->slug, false, plugin_dir_url(__DIR__) . 'img/morkva-liqpay-logo.svg', 26);
        # Add menu Liqpay Equiring
        add_submenu_page($this->slug, __('Internet acquiring', 'mrkv-liqpay-extended-pro'), __('Internet acquiring', 'mrkv-liqpay-extended-pro'),    'manage_options', $this->slug);
        # Add menu Liqpay PayParts 
        add_submenu_page($this->slug, __('Installment payment', 'mrkv-liqpay-extended-pro'), __('Installment payment', 'mrkv-liqpay-extended-pro'),    'manage_options', $this->slug_payparts);
        # Add menu Liqpay Prepay 
        add_submenu_page($this->slug, __('Subscription', 'mrkv-liqpay-extended-pro'), __('Subscription', 'mrkv-liqpay-extended-pro'),    'manage_options', $this->slug_prepay);
    }
}