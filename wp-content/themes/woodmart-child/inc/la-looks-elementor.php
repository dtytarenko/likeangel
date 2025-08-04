<?php
/**
 * Elementor integration for Looks (Образи) functionality
 * Adds query filter and new control option
 */

class LA_Looks_Elementor {

    public function __construct() {
        add_filter( 'woodmart_elementor_products_query', [ $this, 'looks_query_filter' ], 10, 2 );
        add_action( 'elementor/element/woodmart-products/section_query/after_section_end', [ $this, 'add_looks_control' ], 10, 2 );
    }

    /**
     * Filter products query for "look_bundle" post type
     *
     * @param array $args WP_Query arguments
     * @param array $settings Widget settings
     * @return array Modified query arguments
     */
    public function looks_query_filter( $args, $settings ) {
        // Only apply on single product pages
        if ( ! is_product() ) {
            return $args;
        }

        // Only for look_bundle post type setting
        if ( empty( $settings['post_type'] ) || 'look_bundle' !== $settings['post_type'] ) {
            return $args;
        }

        // Get current product ID
        $current_product_id = get_the_ID();
        
        // Get all bundles that contain this product
        $bundle_ids = (array) get_post_meta( $current_product_id, 'woodmart_fbt_bundles_id', true );
        $product_ids = [];

        // Collect all products from these bundles
        foreach ( $bundle_ids as $bid ) {
            $bundle_products = (array) maybe_unserialize( get_post_meta( $bid, '_woodmart_fbt_products', true ) );
            $product_ids = array_merge( $product_ids, $bundle_products );
        }

        // Remove duplicates and current product
        $product_ids = array_unique( array_diff( $product_ids, [ $current_product_id ] ) );

        // If no products found, return empty result
        if ( empty( $product_ids ) ) {
            $product_ids = [ 0 ];
        }

        // Set query to get specific products in specific order
        $args['post__in'] = $product_ids;
        $args['orderby'] = 'post__in';
        $args['posts_per_page'] = count( $product_ids );

        return $args;
    }

    /**
     * Add "Образ" control to Elementor Products widget
     *
     * @param object $element
     * @param array $args
     */
    public function add_looks_control( $element, $args ) {
        // Get existing post_type control options
        $post_type_control = $element->get_controls( 'post_type' );
        
        if ( ! $post_type_control ) {
            return;
        }

        // Add our new option to existing options
        $options = $post_type_control['options'];
        
        // Add look_bundle option
        $options['look_bundle'] = get_locale() === 'uk' ? 'Образ' : 'Look Bundle';

        // Update the control
        $element->update_control( 'post_type', [
            'options' => $options,
        ]);

        // Add description for the new option
        $element->add_control(
            'look_bundle_description',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => get_locale() === 'uk' 
                    ? '<p><strong>Образ:</strong> Показує товари з того ж образу (набору), що і поточний товар.</p>'
                    : '<p><strong>Look Bundle:</strong> Shows products from the same look bundle as the current product.</p>',
                'condition' => [
                    'post_type' => 'look_bundle',
                ],
            ]
        );
    }

    /**
     * Helper method to get products by look bundle
     * Can be used by other parts of the theme
     *
     * @param int $product_id
     * @param int $limit
     * @return WP_Query
     */
    public static function get_look_products_query( $product_id, $limit = -1 ) {
        // Get bundle IDs for this product
        $bundle_ids = (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true );
        $product_ids = [];

        // Get all products from these bundles
        foreach ( $bundle_ids as $bid ) {
            $bundle_products = (array) maybe_unserialize( get_post_meta( $bid, '_woodmart_fbt_products', true ) );
            $product_ids = array_merge( $product_ids, $bundle_products );
        }

        // Remove duplicates and current product
        $product_ids = array_unique( array_diff( $product_ids, [ $product_id ] ) );

        // If no products found, return empty query
        if ( empty( $product_ids ) ) {
            return new WP_Query([
                'post_type' => 'product',
                'post__in' => [ 0 ],
                'posts_per_page' => 0,
            ]);
        }

        return new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'post__in' => $product_ids,
            'orderby' => 'post__in',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => '_visibility',
                    'value' => [ 'catalog', 'visible' ],
                    'compare' => 'IN',
                ],
            ],
        ]);
    }
}

new LA_Looks_Elementor();