<?php
/**
 * Display Looks (Frequently Bought Together) products as a slider.
 *
 * Replaces default Woodmart FBT form with a simple carousel of bundled products.
 */
class LA_Looks_Slider {
    public function __construct() {
        add_action( 'init', array( $this, 'disable_default_fbt' ), 20 );
        add_action( 'woodmart_after_product_tabs', array( $this, 'render_slider' ), 10 );
    }

    /**
     * Remove original FBT form output from Woodmart theme.
     */
    public function disable_default_fbt() {
        if ( class_exists( '\XTS\Modules\Frequently_Bought_Together\Frontend' ) ) {
            $instance = \XTS\Modules\Frequently_Bought_Together\Frontend::get_instance();
            remove_action( 'woodmart_after_product_tabs', array( $instance, 'get_bought_together_products' ) );
        }
    }

    /**
     * Get bundled product IDs for the current product.
     *
     * @param int $product_id Main product ID.
     * @return array
     */
    protected function get_product_ids( $product_id ) {
        $meta = get_post_meta( $product_id, '_woodmart_fbt_products', true );
        $ids  = array();

        if ( empty( $meta ) || ! is_array( $meta ) ) {
            return $ids;
        }

        foreach ( $meta as $item ) {
            if ( is_array( $item ) && ! empty( $item['id'] ) ) {
                $ids[] = (int) $item['id'];
            } elseif ( is_numeric( $item ) ) {
                $ids[] = (int) $item;
            }
        }

        $ids = array_filter( array_unique( $ids ), function ( $id ) use ( $product_id ) {
            return $id && $id !== $product_id;
        } );

        return $ids;
    }

    /**
     * Render carousel with bundled products.
     */
    public function render_slider() {
        if ( ! is_product() ) {
            return;
        }

        global $product;

        if ( ! $product ) {
            return;
        }

        $ids = $this->get_product_ids( $product->get_id() );

        if ( empty( $ids ) ) {
            return;
        }

        $title = apply_filters( 'la_looks_slider_title', __( 'ЗБЕРІТЬ ВЕСЬ ОБРАЗ', 'woodmart' ) );

        $atts = array(
            'query_post_type'              => array( 'product', 'product_variation' ),
            'post_type'                    => 'ids',
            'include'                      => implode( ',', $ids ),
            'layout'                       => 'carousel',
            'orderby'                      => 'post__in',
            'slides_per_view'              => woodmart_get_opt( 'bought_together_column', 3 ),
            'slides_per_view_tablet'       => woodmart_get_opt( 'bought_together_column_tablet', 'auto' ),
            'slides_per_view_mobile'       => woodmart_get_opt( 'bought_together_column_mobile', 'auto' ),
            'hide_pagination_control'      => '',
            'hide_prev_next_buttons'       => '',
            'products_color_scheme'        => woodmart_get_opt( 'products_color_scheme' ),
            'products_bordered_grid'       => woodmart_get_opt( 'products_bordered_grid' ),
            'products_bordered_grid_style' => woodmart_get_opt( 'products_bordered_grid_style' ),
            'products_with_background'     => woodmart_get_opt( 'products_with_background' ),
            'products_shadow'              => woodmart_get_opt( 'products_shadow' ),
            'spacing'                      => 30,
        );

        echo '<div class="container wd-fbt-wrap">';

        if ( $title ) {
            echo '<h4 class="wd-el-title slider-title">' . esc_html( $title ) . '</h4>';
        }

        if ( 'elementor' === woodmart_get_current_page_builder() ) {
            echo woodmart_elementor_products_template( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo woodmart_shortcode_products( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo '</div>';
    }
}

new LA_Looks_Slider();
