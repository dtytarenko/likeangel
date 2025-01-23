<?php
/**
 * KeyCRM Integration.
 *
 * @package  WC_Keycrm_Inventories
 * @category Integration
 * @author   KeyCRM
 */

if (!class_exists('WC_Keycrm_Inventories')) :

    /**
     * Class WC_Keycrm_Inventories
     */
    class WC_Keycrm_Inventories
    {
        /** @var WC_Keycrm_Client_V5 */
        protected $keycrm;

        /** @var array  */
        protected $keycrm_settings;

        /** @var string */
        protected $bind_field = 'externalId';

        /**
         * WC_Keycrm_Inventories constructor.
         * @param bool $keycrm
         */
        public function __construct($keycrm = false)
        {
            $this->keycrm_settings = get_option(WC_Keycrm_Base::$option_key);
            $this->keycrm = $keycrm;

            if (isset($this->keycrm_settings['bind_by_sku'])
                && $this->keycrm_settings['bind_by_sku'] == WC_Keycrm_Base::YES
            ) {
                $this->bind_field = 'xmlId';
            }
        }

        /**
         * Load stock from keyCRM
         *
         * @return mixed
         */
        public function load_stocks()
        {
            $success = array();

            if (!$this->keycrm) {
                return null;
            }

            $page = 1;
            $variationProducts = array();

            do {
                /** @var WC_Keycrm_Response $result */
                $result = $this->keycrm->storeInventories(array(), $page, 250);

                if (!$result->isSuccessful()) {
                    return null;
                }

                $totalPageCount = $result['pagination']['totalPageCount'];
                $page++;

                foreach ($result['offers'] as $offer) {
                    if (isset($offer[$this->bind_field])) {
                        $product = keycrm_get_wc_product($offer[$this->bind_field], $this->keycrm_settings);

                        if ($product instanceof WC_Product) {
                            if ($product->get_type() == 'variation' || $product->get_type() == 'variable') {
                                $parentId = $product->get_parent_id();

                                if (isset($variationProducts[$parentId])) {
                                    $variationProducts[$parentId] += $offer['quantity'];
                                } else {
                                    $variationProducts[$parentId] = $offer['quantity'];
                                }
                            }

                            $product->set_manage_stock(true);
                            $product->set_stock_quantity($offer['quantity']);
                            $success[] = $product->save();
                        }
                    }
                }

                foreach ($variationProducts as $id => $quantity) {
                    $variationProduct = wc_get_product($id);
                    $variationProduct->set_manage_stock(true);
                    $variationProduct->set_stock($quantity);
                    $success[] = $variationProduct->save();
                }
            } while ($page <= $totalPageCount);

            return $success;
        }

        /**
         * Update stock quantity in WooCommerce
         *
         * @return mixed
         */
        public function updateQuantity()
        {
            if ($this->keycrm_settings['sync'] == WC_Keycrm_Base::YES) {
                return $this->load_stocks();
            }

            return false;
        }
    }
endif;
