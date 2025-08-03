<?php
/**
 * Look taxonomy and sync with Woodmart bundles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register custom taxonomy for product looks.
add_action( 'init', function() {
    register_taxonomy(
        'product_look',
        'product',
        array(
            'labels' => array(
                'name'          => __( 'Looks', 'likeangel' ),
                'singular_name' => __( 'Look', 'likeangel' ),
            ),
            'public'       => true,
            'show_ui'      => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'rewrite'      => array( 'slug' => 'look' ),
        )
    );
} );

/**
 * Sync product look terms when Woodmart bundle is saved.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function la_sync_bundle_look_terms( $post_id, $post ) {
    if ( wp_is_post_revision( $post_id ) || 'woodmart_woo_fbt' !== $post->post_type ) {
        return;
    }

    // Ensure term exists for this bundle.
    $term_slug = 'look-' . $post_id;
    $term_name = $post->post_title;

    $term      = term_exists( $term_slug, 'product_look' );
    if ( $term ) {
        $term_id = (int) $term['term_id'];
        wp_update_term( $term_id, 'product_look', array( 'name' => $term_name ) );
    } else {
        $term_id = wp_insert_term( $term_name, 'product_look', array( 'slug' => $term_slug ) );
        if ( is_wp_error( $term_id ) ) {
            return;
        }
        $term_id = (int) $term_id['term_id'];
    }

    // Collect product IDs from bundle meta.
    $products    = get_post_meta( $post_id, '_woodmart_fbt_products', true );
    $product_ids = array();

    if ( is_array( $products ) ) {
        foreach ( $products as $product ) {
            if ( ! empty( $product['id'] ) ) {
                $product_ids[] = (int) $product['id'];
            }
        }
    }

    // Assign term to all products in bundle.
    foreach ( $product_ids as $pid ) {
        wp_set_object_terms( $pid, $term_id, 'product_look', true );
    }

    // Remove term from products no longer in bundle.
    $attached = get_objects_in_term( $term_id, 'product_look' );
    foreach ( $attached as $object_id ) {
        if ( ! in_array( (int) $object_id, $product_ids, true ) ) {
            wp_remove_object_terms( $object_id, $term_id, 'product_look' );
        }
    }

    // Delete term if no products left.
    $attached = get_objects_in_term( $term_id, 'product_look' );
    if ( empty( $attached ) ) {
        wp_delete_term( $term_id, 'product_look' );
    }
}
add_action( 'save_post_woodmart_woo_fbt', 'la_sync_bundle_look_terms', 10, 2 );

/**
 * Cleanup term when bundle deleted.
 *
 * @param int $post_id Post ID.
 */
function la_delete_bundle_look_term( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'woodmart_woo_fbt' !== $post->post_type ) {
        return;
    }

    $term_slug = 'look-' . $post_id;
    $term      = term_exists( $term_slug, 'product_look' );
    if ( $term ) {
        $term_id = (int) $term['term_id'];

        $attached = get_objects_in_term( $term_id, 'product_look' );
        foreach ( $attached as $object_id ) {
            wp_remove_object_terms( $object_id, $term_id, 'product_look' );
        }
        wp_delete_term( $term_id, 'product_look' );
    }
}
add_action( 'before_delete_post', 'la_delete_bundle_look_term' );

// Elementor query integration.
add_filter( 'elementor/woocommerce/product_query_type', function ( $types ) {
    $types['look'] = __( 'Look', 'likeangel' );
    return $types;
} );

add_action( 'elementor/element/woocommerce-products/section_query/before_section_end', function( $element ) {
    $terms   = get_terms( array( 'taxonomy' => 'product_look', 'hide_empty' => false ) );
    $options = array();
    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term ) {
            $options[ $term->slug ] = $term->name;
        }
    }

    $element->add_control(
        'look_tag',
        array(
            'label'     => __( 'Look', 'likeangel' ),
            'type'      => \Elementor\Controls_Manager::SELECT2,
            'options'   => $options,
            'label_block' => true,
            'condition' => array( 'query_type' => 'look' ),
        )
    );
} , 10, 2 );

add_action( 'elementor/query/look', function( $wp_query, $widget ) {
    $slug = $widget->get_settings( 'look_tag' );
    if ( empty( $slug ) ) {
        return;
    }

    $tax_query = array(
        array(
            'taxonomy' => 'product_look',
            'field'    => 'slug',
            'terms'    => $slug,
        ),
    );
    $wp_query->set( 'tax_query', $tax_query );
}, 10, 2 );
