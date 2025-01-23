<?php
/**
 * PHP version 5.3
 *
 * @category Integration
 * @author   KeyCRM <integration@keycrm.app>
 * @license  http://keycrm.app Proprietary
 * @link     http://keycrm.app
 * @see      http://help.keycrm.app
 */

abstract class WC_Keycrm_Abstracts_Settings extends WC_Integration
{
    /** @var string */
    const YES = 'yes';

    /** @var string */
    const NO = 'no';

    /** @var string */
    public static $option_key;

    /**
     * WC_Keycrm_Abstracts_Settings constructor.
     */
    public function __construct() {
        $this->id                 = 'integration-keycrm';
        $this->method_title       = __('Keycrm.app', 'keycrm');
        $this->method_description = __('Integration with Keycrm.app management system.', 'keycrm');

        static::$option_key = $this->get_option_key();

        if (isset($_GET['page']) && $_GET['page'] == 'wc-settings'
            && isset($_GET['tab']) && $_GET['tab'] == 'integration'
        ) {
            add_action('init', array($this, 'init_settings_fields'), 99);
        }
    }

    public function ajax_upload()
    {
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script type="text/javascript">
        jQuery('#uploads-keycrm').bind('click', function() {
            jQuery(this).after('<span><br />Upload is in progress... <br /> Wait around 1 minute per each 200 orders...</span>');
            jQuery.ajax({
                type: "POST",
                url: '<?php echo $ajax_url; ?>?action=do_upload',
                success: function (response) {
                    jQuery('#uploads-keycrm').next().remove();
                    alert('<?php echo __('Orders were uploaded', 'keycrm'); ?>');
                    console.log('AJAX response : ',response);
                }
            });
        });
        </script>
        <?php
    }

    public function ajax_generate_icml()
    {
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script type="text/javascript">
        jQuery('#icml-keycrm, #wp-admin-bar-keycrm_ajax_generate_icml').bind('click', function() {
            jQuery.ajax({
                type: "POST",
                url: '<?php echo $ajax_url; ?>?action=generate_icml',
                success: function (response) {
                    alert('<?php echo __('Catalog was generated', 'keycrm'); ?>');
                    console.log('AJAX response : ', response);
                }
            });
        });
        </script>
        <?php
    }

    public function ajax_selected_order()
    {
        $ajax_url = admin_url('admin-ajax.php');
        $ids = $this->plugin_id . $this->id . '_single_order';
        ?>
        <script type="text/javascript">
        jQuery('#single_order_btn').bind('click', function() {
            if (jQuery('#<?php echo $ids; ?>').val() == '') {
                alert('<?php echo __('The field cannot be empty, enter the order ID', 'keycrm'); ?>');
            } else {
                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo $ajax_url; ?>?action=order_upload&order_ids_keycrm=' + jQuery('#<?php echo $ids; ?>').val(),
                    success: function (response) {
                        alert('<?php echo __('Orders were uploaded', 'keycrm'); ?>');
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Initialize integration settings form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            array( 'title' => __( 'Main settings', 'keycrm' ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

            'api_url' => array(
                'title'             => __( 'API of URL', 'keycrm' ),
                'type'              => 'text',
                'description'       => __( 'https://openapi.keycrm.app/v1', 'keycrm' ),
                'desc_tip'          => true,
                'default'           => 'https://openapi.keycrm.app/v1',
                'custom_attributes' => array('readonly' => 'readonly'),
            ),
            'api_key' => array(
                'title'             => __( 'API key', 'keycrm' ),
                'type'              => 'text',
                'description'       => __( 'Enter your API key. You can find it in the administration section of Keycrm.app', 'keycrm' ),
                'desc_tip'          => true,
                'default'           => ''
            )
        );

/*         $this->form_fields[] = array(
            'title'       => __( 'API settings', 'keycrm' ),
            'type'        => 'title',
            'description' => '',
            'id'          => 'api_options'
        ); */

//        $this->form_fields['send_delivery_net_cost'] = array(
//            'title'       => __( 'Do not transmit the cost of delivery', 'keycrm' ),
//            'label'       => ' ',
//            'description' => '',
//            'class'       => 'checkbox',
//            'type'        => 'checkbox',
//            'desc_tip'    =>  true
//        );

//        $this->form_fields['corporate_enabled'] = array(
//            'title'       => __('Corporate customers support', 'keycrm'),
//            'label'       => __('Enabled'),
//            'description' => '',
//            'class'       => 'checkbox',
//            'type'        => 'checkbox',
//            'desc_tip'    =>  true
//        );

//        $this->form_fields['online_assistant'] = array(
//            'title'       => __( 'Online assistant', 'keycrm' ),
//            'type'        => 'textarea',
//            'id'          => 'online_assistant',
//            'placeholder' => __( 'Insert the Online consultant code here', 'keycrm' )
//        );

//        $this->form_fields[] = array(
//            'title'       => __( 'Catalog settings', 'keycrm' ),
//            'type'        => 'title',
//            'description' => '',
//            'id'          => 'catalog_options'
//        );

//        foreach (get_post_statuses() as $status_key => $status_value) {
//            $this->form_fields['p_' . $status_key] = array(
//                'title'       => $status_value,
//                'label'       => ' ',
//                'description' => '',
//                'class'       => 'checkbox',
//                'type'        => 'checkbox',
//                'desc_tip'    =>  true,
//            );
//        }

        if ($this->apiClient) {
            if (isset($_GET['page']) && $_GET['page'] == 'wc-settings'
                && isset($_GET['tab']) && $_GET['tab'] == 'integration'
            ) {
                add_action('admin_print_footer_scripts', array($this, 'show_blocks'), 99);

                /**
                 * Client roles options
                 */
//                $client_roles_option = array();
//                $client_roles_list = wp_roles()->get_names();
//
//                if (!empty($client_roles_list)) {
//                    foreach ($client_roles_list as $code => $name) {
//                        $client_roles_option[$code] = $name;
//                    }
//
//                    $this->form_fields[] = array(
//                        'title' => __('Client roles', 'keycrm'),
//                        'type' => 'heading',
//                        'description' => '',
//                        'id' => 'client_roles_options'
//                    );
//
//                    $this->form_fields['client_roles'] = array(
//                        'label'       =>  ' ',
//                        'title'       => __('Client roles available for uploading to Simla.com', 'keycrm'),
//                        'class'       => '',
//                        'type'        => 'multiselect',
//                        'description' => __('Select client roles which will be uploaded from website to Simla.com', 'keycrm'),
//                        'options'     => $client_roles_option,
//                        'css'         => 'min-height:100px;',
//                        'select_buttons' => true
//                    );
//                }

                /**
                 * Order methods options
                 */
                $order_methods_option = array();
                $order_methods_list = $this->apiClient->orderMethodsList();

                if (!empty($order_methods_list) && $order_methods_list->isSuccessful() && !empty($order_methods_list['orderMethods'])) {
                    foreach ($order_methods_list['orderMethods'] as $order_method) {
                        if ($order_method['active'] == false) {
                            continue;
                        }

                        $order_methods_option[$order_method['code']] = $order_method['name'];
                    }

                    $this->form_fields[] = array(
                        'title' => __('Order methods', 'keycrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'order_methods_options'
                    );

                    $this->form_fields['order_methods'] = array(
                        'label'       =>  ' ',
                        'title'       => __('Order source Keycrm.app', 'keycrm'),
                        'class'       => '',
                        'type'        => 'select',
                        'description' => __('Select ONE source in Keycrm settings', 'keycrm'),
                        'options'     => $order_methods_option,
//                        'css'         => 'min-height:100px;',
                        'select_buttons' => true
                    );
                }

                /**
                 * Shipping options
                 */
                $shipping_option_list = array();
                $keycrm_shipping_list = $this->apiClient->deliveryTypesList();

                if (!empty($keycrm_shipping_list) && $keycrm_shipping_list->isSuccessful()
                    && isset($keycrm_shipping_list['deliveryTypes'])) {
                    foreach ($keycrm_shipping_list['deliveryTypes'] as $keycrm_shipping_type) {
                        $shipping_option_list[$keycrm_shipping_type['code']] = $keycrm_shipping_type['name'];
                    }

                    $wc_shipping_list = get_wc_shipping_methods();

                    $this->form_fields[] = array(
                        'title' => __('Delivery types', 'keycrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'shipping_options'
                    );

                    foreach ($wc_shipping_list as  $shipping_code => $shipping) {
                        if (isset($shipping['enabled']) && $shipping['enabled'] == static::YES) {
                            $this->form_fields[$shipping_code] = array(
                                'title'          => __($shipping['title'], 'woocommerce'),
                                'description' => __($shipping['description'], 'woocommerce'),
                                'css'            => 'min-width:350px;',
                                'class'          => 'select',
                                'type'           => 'select',
                                'options'        => $shipping_option_list,
                                'desc_tip'    =>  true,
                            );
                        }
                    }
                } else {
                    $this->form_fields[] = array(
                        'title' => __('Delivery types', 'keycrm'),
                        'type' => 'heading',
                        'description' => 
                            isset($keycrm_shipping_list['deliveryTypes']) && empty($keycrm_shipping_list['deliveryTypes']) ? 
                            'Add shipment types in KeyCRM / Добавьте хотя бы одну службу доставки в KeyCRM' :
                            'API key is not valid! / API ключ - неверный!',
                    );
                }

                /**
                 * Payment options
                 */
                $payment_option_list = array();
                $keycrm_payment_list = $this->apiClient->paymentTypesList();

                if (!empty($keycrm_payment_list) && $keycrm_payment_list->isSuccessful()) {
                    foreach ($keycrm_payment_list['paymentTypes'] as $keycrm_payment_type) {
                        $payment_option_list[$keycrm_payment_type['code']] = $keycrm_payment_type['name'];
                    }

                    $wc_payment = WC_Payment_Gateways::instance();

                    $this->form_fields[] = array(
                        'title' => __('Payment types', 'keycrm'),
                        'type' => 'heading',
                        'description' => '',
                        'id' => 'payment_options'
                    );

                    foreach ($wc_payment->payment_gateways() as $payment) {
                        $this->form_fields[$payment->id] = array(
                            'title'          => __($payment->method_title, 'woocommerce'),
                            'description' => __($payment->method_description, 'woocommerce'),
                            'css'            => 'min-width:350px;',
                            'class'          => 'select',
                            'type'           => 'select',
                            'options'        => $payment_option_list,
                            'desc_tip'    =>  true,
                        );
                    }
                }

                /**
                 * Statuses options
                 */
                /*
                $statuses_option_list = array();
                $keycrm_statuses_list = $this->apiClient->statusesList();

                if (!empty($keycrm_statuses_list) && $keycrm_statuses_list->isSuccessful()) {
                    foreach ($keycrm_statuses_list['statuses'] as $keycrm_status) {
                        $statuses_option_list[$keycrm_status['code']] = $keycrm_status['name'];
                    }

                    $wc_statuses = wc_get_order_statuses();

                    $this->form_fields[] = array(
                        'title'       => __('Statuses', 'keycrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'statuses_options'
                    );

                    foreach ($wc_statuses as $idx => $name) {
                        $uid = str_replace('wc-', '', $idx);
                        $this->form_fields[$uid] = array(
                            'title'    => __($name, 'woocommerce'),
                            'css'      => 'min-width:350px;',
                            'class'    => 'select',
                            'type'     => 'select',
                            'options'  => $statuses_option_list,
                            'desc_tip' =>  true,
                        );
                    }
                }*/

                /**
                 * Inventories options
                 */
//                $this->form_fields[] = array(
//                    'title'       => __('Setting of the stock balance', 'keycrm'),
//                    'type'        => 'heading',
//                    'description' => '',
//                    'id'          => 'invent_options'
//                );
//
//                $this->form_fields['sync'] = array(
//                    'label'       => __('Synchronization of the stock balance', 'keycrm'),
//                    'title'       => __('Stock balance', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox',
//                    'description' => __('Enable this setting if you would like to get information on leftover stocks from Simla.com to the website.', 'keycrm')
//                );

                /**
                 * UA options
                 */
//                $this->form_fields[] = array(
//                    'title'       => __('UA settings', 'keycrm'),
//                    'type'        => 'heading',
//                    'description' => '',
//                    'id'          => 'ua_options'
//                );
//
//                $this->form_fields['ua'] = array(
//                    'label'       => __('Activate UA', 'keycrm'),
//                    'title'       => __('UA', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox',
//                    'description' => __('Enable this setting for uploading data to UA', 'keycrm')
//                );
//
//                $this->form_fields['ua_code'] = array(
//                    'title'       => __('UA tracking code', 'keycrm'),
//                    'class'       => 'input',
//                    'type'        => 'input'
//                );
//
//                $this->form_fields['ua_custom'] = array(
//                    'title'       => __('User parameter', 'keycrm'),
//                    'class'       => 'input',
//                    'type'        => 'input'
//                );

                /**
                 * Daemon collector settings
                 */
//                $this->form_fields[] = array(
//                    'title'       => __('Daemon Collector settings', 'keycrm'),
//                    'type'        => 'heading',
//                    'description' => '',
//                    'id'          => 'invent_options'
//                );
//
//                $this->form_fields['daemon_collector'] = array(
//                    'label'       => __('Activate Daemon Collector', 'keycrm'),
//                    'title'       => __('Daemon Collector', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox',
//                    'description' => __('Enable this setting for activate Daemon Collector on site', 'keycrm')
//                );
//
//                $this->form_fields['daemon_collector_key'] = array(
//                    'title'       => __('Site key', 'keycrm'),
//                    'class'       => 'input',
//                    'type'        => 'input'
//                );

                /**
                 * Uploads options
                 */
                $options = array_filter(get_option(static::$option_key));

                // if (!isset($options['uploads'])) {
                    $this->form_fields[] = array(
                        'title'       => __('Settings of uploading', 'keycrm'),
                        'type'        => 'heading',
                        'description' => '',
                        'id'          => 'upload_options'
                    );

                    $this->form_fields['upload-button'] = array(
                        'label'             => __('Upload', 'keycrm'),
                        'title'             => __('Uploading all orders', 'keycrm' ),
                        'type'              => 'button',
                        'description'       => __('Uploading the orders to Keycrm.app (Will take around 1 minute per each 200 orders)', 'keycrm' ),
                        'desc_tip'          => true,
                        'id'                => 'uploads-keycrm'
                    );
                // }

                /**
                 * WhatsApp options
                 */
//                $this->form_fields[] = array(
//                    'title'       => __('Settings of WhatsApp', 'keycrm'),
//                    'type'        => 'heading',
//                    'description' => '',
//                    'id'          => 'whatsapp_options'
//                );

//                $this->form_fields['whatsapp_active'] = array(
//                    'label'       => __('Activate WhatsApp', 'keycrm'),
//                    'title'       => __('WhatsApp', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox',
//                    'description' => __('Activate this setting to activate WhatsApp on the website', 'keycrm')
//                );
//
//                $this->form_fields['whatsapp_location_icon'] = array(
//                    'label'       => __('Place in the lower right corner of the website', 'keycrm'),
//                    'title'       => __('WhatsApp icon location', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox',
//                    'description' => __('By default, WhatsApp icon is located in the lower left corner of the website', 'keycrm')
//                );
//
//                $this->form_fields['whatsapp_number'] = array(
//                    'title'       => __('Enter your phone number', 'keycrm'),
//                    'class'       => '',
//                    'type'        => 'text',
//                    'description' => __('WhatsApp chat will be opened with this contact', 'keycrm')
//                );

                /**
                 * Generate icml file
                 */
//                $this->form_fields[] = array(
//                    'title'       => __('Orders uploading', 'keycrm'),
//                    'type'        => 'title',
//                    'description' => '',
//                    'id'          => 'icml_options'
//                );

//                $this->form_fields[] = array(
//                    'label'             => __('Generate now', 'keycrm'),
//                    'title'             => __('Generating ICML', 'keycrm'),
//                    'type'              => 'button',
//                    'description'       => __('This functionality allows to generate ICML products catalog for uploading to Simla.com.', 'keycrm'),
//                    'desc_tip'          => true,
//                    'id'                => 'icml-keycrm'
//                );
//
//                $this->form_fields['icml'] = array(
//                    'label'       => __('Generating ICML', 'keycrm'),
//                    'title'       => __('Generating ICML catalog by wp-cron', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox'
//                );

                /*
                 * Upload single order
                 */
                  $this->form_field[] = array(
                      'title'       => __('Upload the order by ID', 'keycrm'),
                      'type'        => 'title',
                      'description' => '',
                      'id'          => 'order_options'
                  );
  
                  $this->form_fields['single_order'] = array(
                      'label'             => __('Order identifier', 'keycrm'),
                      'title'             => __('Orders identifiers', 'keycrm'),
                      'type'              => 'input',
                      'description'       => __('Enter orders identifiers separated by a comma.', 'keycrm'),
                      'desc_tip'          => true
                  );
  
                  $this->form_fields[] = array(
                      'label'             => __('Upload', 'keycrm'),
                      'title'             => __('Uploading orders by identifiers.', 'keycrm'),
                      'type'              => 'button',
                      'description'       => __('This functionality allows to upload orders to CRM differentially.', 'keycrm'),
                      'desc_tip'          => true,
                      'id'                => 'single_order_btn'
                  );

//                $this->form_fields['history'] = array(
//                    'label'       => __('Activate history uploads', 'keycrm'),
//                    'title'       => __('Upload data from Simla.com', 'keycrm'),
//                    'class'       => 'checkbox',
//                    'type'        => 'checkbox'
//                );

//                $this->form_fields['deactivate_update_order'] = array(
//                     'label'       => __('Disable data editing in Simla.com', 'keycrm'),
//                     'title'       => __('Data updating in Simla.com', 'keycrm'),
//                     'class'       => 'checkbox',
//                     'type'        => 'checkbox'
//                );

//                $this->form_fields['bind_by_sku'] = array(
//                     'label'       => __('Activate the binding via sku (xml)', 'keycrm'),
//                     'title'       => __('Stock synchronization and link between products', 'keycrm'),
//                     'class'       => 'checkbox',
//                     'type'        => 'checkbox'
//                );

//                $this->form_fields['update_number'] = array(
//                     'label'       => __('Enable transferring the number to Keycrm.app', 'keycrm'),
//                     'title'       => __('Transferring the order number', 'keycrm'),
//                     'class'       => 'checkbox',
//                     'type'        => 'checkbox'
//                );
            }
        }
    }

    /**
     * Generate html button
     *
     * @param string $key
     * @param array $data
     *
     * @return string
     */
    public function generate_button_html($key, $data)
    {
        $field    = $this->plugin_id . $this->id . '_' . $key;
        $defaults = array(
            'class'             => 'button-secondary',
            'css'               => '',
            'custom_attributes' => array(),
            'desc_tip'          => false,
            'description'       => '',
            'title'             => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo $this->get_tooltip_html( $data ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['label'] ); ?></span></legend>
                    <button id="<?php echo $data['id']; ?>" class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['label'] ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate html title block settings
     *
     * @param string $key
     * @param array $data
     *
     * @return string
     */
    public function generate_heading_html($key, $data)
    {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title' => '',
            'class' => '',
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
            </table>
            <h2 class="wc-settings-sub-title keycrm_hidden <?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <span style="opacity: 0.5;float: right;border: solid 1px #666;display: block;padding: 0 8px 4px;line-height: 22px;font-size: 22px;border-radius: 4px;">&#11015;</span></h2>
            <?php if ( ! empty( $data['description'] ) ) : ?>
                <p><?php echo wp_kses_post( $data['description'] ); ?></p>
            <?php endif; ?>
            <table class="form-table" style="display: none;">
        <?php

        return ob_get_clean();
    }

    /**
    * Returns the original value for the online_consultant field (ignores woocommerce validation)
    * @param $key
    * @param $value
    * @return string
    */
    public function validate_online_assistant_field($key, $value)
    {
    	$onlineAssistant = $_POST['woocommerce_integration-keycrm_online_assistant'];

    	if (!empty($onlineAssistant) && is_string($onlineAssistant)) {
    	    return wp_unslash($onlineAssistant);
    	}

    	return '';
    }

    /**
     * Validate API url
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    public function validate_api_url_field($key, $value)
    {
        $post = $this->get_post_data();
        $api = new WC_Keycrm_Proxy(
            $value,
            $post[$this->plugin_id . $this->id . '_api_key'],
            $this->get_option('corporate_enabled', 'no') === 'yes'
        );

        $response = $api->apiVersions();

        if ($response == null) {
            WC_Admin_Settings::add_error(esc_html__( 'Enter the correct URL of CRM', 'keycrm'));
            $value = '';
        }

        return $value;
    }

    /**
     * Validate API key
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    public function validate_api_key_field($key, $value)
    {
        $post = $this->get_post_data();
        $api = new WC_Keycrm_Proxy(
            $post[$this->plugin_id . $this->id . '_api_url'],
            $value,
            $this->get_option('corporate_enabled', 'no') === 'yes'
        );

        $response = $api->apiVersions();

        if (!is_object($response)) {
            $value = '';
        }

        if (empty($response) || !$response->isSuccessful()) {
            WC_Admin_Settings::add_error( esc_html__( 'Enter the correct API key', 'keycrm' ) );
            $value = '';
        }

        return $value;
    }


    /**
     * Validate whatsapp phone number
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    public function validate_whatsapp_number_field($key, $value)
    {
        $post = $this->get_post_data();

        if (!empty($post['woocommerce_integration-keycrm_whatsapp_active'])) {
            $phoneNumber = preg_replace('/[^+0-9]/', '', $value);

            if (empty($value) || strlen($value) > 25 || strlen($phoneNumber) !== strlen($value)) {
                WC_Admin_Settings::add_error(esc_html__('Introduce the correct phone number', 'keycrm'));
                $value = '';
            }
        }

        return $value;
    }


    /**
     * Scritp show|hide block settings
     */
    function show_blocks()
    {
        ?>
        <script type="text/javascript">
            jQuery('h2.keycrm_hidden').hover().css({
                'cursor':'pointer',
                'width':'260px'
            });
            jQuery('h2.keycrm_hidden').bind(
                'click',
                function() {
                    if(jQuery(this).next('table.form-table').is(":hidden")) {
                        jQuery(this).next('table.form-table').show(100);
                        jQuery(this).find('span').html('&#11014;');
                    } else {
                        jQuery(this).next('table.form-table').hide(100);
                        jQuery(this).find('span').html('&#11015;');
                    }
                }
            );
        </script>
        <?php
    }

    /**
     * Add button in admin
     */
    function add_keycrm_button() {
        global $wp_admin_bar;
        if ( !is_super_admin() || !is_admin_bar_showing() || !is_admin())
            return;

        $wp_admin_bar->add_menu(
            array(
                'id' => 'keycrm_top_menu',
                'title' => __('Keycrm.app', 'keycrm')
            )
        );
//        $wp_admin_bar->add_menu(
//            array(
//                'id' => 'keycrm_ajax_generate_icml',
//                'title' => __('Generating ICML catalog', 'keycrm'),
//                'href' => '#',
//                'parent' => 'keycrm_top_menu',
//                'class' => 'keycrm_ajax_generate_icml'
//            )
//        );
        $wp_admin_bar->add_menu(
            array(
                'id' => 'keycrm_ajax_generate_setings',
                'title' => __('Settings', 'keycrm'),
                'href'=> get_site_url().'/wp-admin/admin.php?page=wc-settings&tab=integration&section=integration-keycrm',
                'parent' => 'keycrm_top_menu',
                'class' => 'keycrm_ajax_settings'
            )
        );
    }
}
