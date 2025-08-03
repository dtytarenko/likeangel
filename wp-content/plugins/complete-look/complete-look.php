<?php
/**
 * Plugin Name: Complete the Look
 * Description: Adds Complete the Look products for WooCommerce and Elementor widget integration.
 * Version: 1.0.0
 * Author: OpenAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CAL_Complete_Look {
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        add_action( 'save_post_product', [ $this, 'save_metabox' ] );
    }

    /**
     * Register metabox.
     */
    public function add_metabox() {
        add_meta_box(
            'cal-complete-look',
            __( 'Complete the Look', 'complete-look' ),
            [ $this, 'render_metabox' ],
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render metabox content.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_metabox( $post ) {
        wp_nonce_field( 'cal_save_look_products', 'cal_look_products_nonce' );

        $selected = get_post_meta( $post->ID, 'look_products', true );
        $selected = is_array( $selected ) ? $selected : [];
        ?>
        <select class="wc-product-search" multiple="multiple" style="width:100%" name="look_products[]" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations">
            <?php
            foreach ( $selected as $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                }
            }
            ?>
        </select>
        <p class="description"><?php esc_html_e( 'Select products that complete the look.', 'complete-look' ); ?></p>
        <?php
    }

    /**
     * Save metabox data.
     *
     * @param int $post_id Product ID.
     */
    public function save_metabox( $post_id ) {
        if ( ! isset( $_POST['cal_look_products_nonce'] ) || ! wp_verify_nonce( $_POST['cal_look_products_nonce'], 'cal_save_look_products' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( isset( $_POST['look_products'] ) && is_array( $_POST['look_products'] ) ) {
            $ids = array_filter( array_map( 'intval', (array) $_POST['look_products'] ) );
            update_post_meta( $post_id, 'look_products', $ids );
        } else {
            delete_post_meta( $post_id, 'look_products' );
        }
    }
}

new CAL_Complete_Look();
