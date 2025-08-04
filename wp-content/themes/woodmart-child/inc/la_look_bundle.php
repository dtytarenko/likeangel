<?php
/**
 * Extend Elementor products widget with "Look" query type based on Woodmart bundles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: get look IDs for a product.
 */
function la_get_product_looks( $product_id ) {
    $looks = get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true );
    $looks = array_map( 'intval', (array) $looks );
    return array_values( array_unique( array_filter( $looks ) ) );
}

// Add option to Elementor query types.
add_filter( 'elementor/woocommerce/product_query_type', function( $types ) {
    $types['look_bundle'] = __( 'Образ', 'likeangel' );
    return $types;
} );

// Modify WP_Query for look bundles.
add_action( 'elementor/query/look_bundle', function( $query ) {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }

    $look_ids = la_get_product_looks( $post_id );
    if ( empty( $look_ids ) ) {
        $query->set( 'post__in', array( 0 ) );
        return;
    }

    $ids = array();
    foreach ( $look_ids as $look_id ) {
        $products = get_post_meta( $look_id, '_woodmart_fbt_products', true );
        if ( empty( $products ) || ! is_array( $products ) ) {
            continue;
        }
        foreach ( $products as $product ) {
            if ( empty( $product['id'] ) ) {
                continue;
            }
            $pid = (int) $product['id'];

            if ( 'product_variation' === get_post_type( $pid ) ) {
                $pid = (int) wp_get_post_parent_id( $pid );
            }

            if ( ! $pid || $pid === $post_id || in_array( $pid, $ids, true ) ) {
                continue;
            }

            $ids[] = $pid;
        }
    }

    if ( $ids ) {
        $query->set( 'post__in', $ids );
        $query->set( 'orderby', 'post__in' );
        $query->set( 'posts_per_page', count( $ids ) );
    } else {
        $query->set( 'post__in', array( 0 ) );
    }
} );
