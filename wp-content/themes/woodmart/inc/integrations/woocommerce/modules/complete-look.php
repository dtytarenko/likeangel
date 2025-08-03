<?php
/**
 * Complete the Look module.
 *
 * Allows selection of products that complete the look for a product.
 *
 * @package woodmart
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access not allowed.
}

if ( ! function_exists( 'woodmart_complete_look_add_metabox' ) ) {
    /**
     * Add metabox for selecting "Complete the Look" products.
     */
    function woodmart_complete_look_add_metabox() {
        add_meta_box(
            'woodmart-complete-look',
            esc_html__( 'Complete the Look', 'woodmart' ),
            'woodmart_complete_look_metabox_html',
            'product',
            'side',
            'default'
        );
    }
    add_action( 'add_meta_boxes', 'woodmart_complete_look_add_metabox' );
}

if ( ! function_exists( 'woodmart_complete_look_metabox_html' ) ) {
    /**
     * Metabox output.
     *
     * @param WP_Post $post Product post object.
     */
    function woodmart_complete_look_metabox_html( $post ) {
        $ids = (array) get_post_meta( $post->ID, '_woodmart_complete_look_ids', true );
        $ids = array_filter( $ids );
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="woodmart_complete_look_ids"><?php esc_html_e( 'Products', 'woodmart' ); ?></label>
                <input type="hidden" id="woodmart_complete_look_ids" name="woodmart_complete_look_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" class="wc-product-search" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woodmart' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-multiple="true" style="width:100%;" />
            </p>
        </div>
        <?php
    }
}

if ( ! function_exists( 'woodmart_complete_look_save_metabox' ) ) {
    /**
     * Save metabox data.
     *
     * @param int $post_id Post ID.
     */
    function woodmart_complete_look_save_metabox( $post_id ) {
        if ( ! isset( $_POST['woodmart_complete_look_ids'] ) ) {
            delete_post_meta( $post_id, '_woodmart_complete_look_ids' );
            return;
        }

        $ids = array_filter( array_map( 'intval', explode( ',', wp_unslash( $_POST['woodmart_complete_look_ids'] ) ) ) );
        update_post_meta( $post_id, '_woodmart_complete_look_ids', $ids );
    }
    add_action( 'save_post_product', 'woodmart_complete_look_save_metabox' );
}

