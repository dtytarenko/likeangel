<?php
/**
 * Bi-directional sync between bundles (Образи) and products
 * Maintains compatibility with existing DB keys
 */

class LA_Looks_Sync {

    public function __construct() {
        add_action( 'save_post_woodmart_woo_fbt', [ $this, 'sync_bundle_members' ], 20, 3 );
        add_action( 'before_delete_post', [ $this, 'detach_on_delete' ], 10 );
    }

    /**
     * Sync bundle members when bundle is saved
     *
     * @param int $post_ID
     * @param WP_Post $post
     * @param bool $update
     */
    public function sync_bundle_members( $post_ID, $post, $update ) {
        // Skip autosaves and revisions
        if ( wp_is_post_autosave( $post_ID ) || wp_is_post_revision( $post_ID ) ) {
            return;
        }

        // Get current bundle members
        $new = (array) maybe_unserialize( get_post_meta( $post_ID, '_woodmart_fbt_products', true ) );
        $new = array_filter( $new ); // Remove empty values
        
        // Get previous bundle members
        $old = (array) get_post_meta( $post_ID, '__la_prev_members', true );
        
        // Store current members for next comparison
        update_post_meta( $post_ID, '__la_prev_members', $new );

        // Find differences
        $added = array_diff( $new, $old );
        $removed = array_diff( $old, $new );

        // Update product relationships
        foreach ( $added as $pid ) {
            $this->attach_bundle( $pid, $post_ID );
        }
        
        foreach ( $removed as $pid ) {
            $this->detach_bundle( $pid, $post_ID );
        }
    }

    /**
     * Clean up relationships when bundle is deleted
     *
     * @param int $post_ID
     */
    public function detach_on_delete( $post_ID ) {
        if ( get_post_type( $post_ID ) !== 'woodmart_woo_fbt' ) {
            return;
        }

        $members = (array) maybe_unserialize( get_post_meta( $post_ID, '_woodmart_fbt_products', true ) );
        foreach ( $members as $pid ) {
            $this->detach_bundle( $pid, $post_ID );
        }
    }

    /**
     * Attach bundle to product
     *
     * @param int $product_id
     * @param int $bundle_id
     */
    private function attach_bundle( $product_id, $bundle_id ) {
        if ( ! $product_id || ! $bundle_id ) {
            return;
        }

        // Get current bundles for this product
        $bundles = (array) maybe_unserialize( get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true ) );
        
        // Add bundle if not already present
        if ( ! in_array( $bundle_id, $bundles ) ) {
            $bundles[] = $bundle_id;
            update_post_meta( $product_id, 'woodmart_fbt_bundles_id', array_unique( $bundles ) );
        }
    }

    /**
     * Detach bundle from product
     *
     * @param int $product_id
     * @param int $bundle_id
     */
    private function detach_bundle( $product_id, $bundle_id ) {
        if ( ! $product_id || ! $bundle_id ) {
            return;
        }

        // Get current bundles for this product
        $bundles = (array) maybe_unserialize( get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true ) );
        
        // Remove bundle from array
        $bundles = array_diff( $bundles, [ $bundle_id ] );
        
        if ( empty( $bundles ) ) {
            delete_post_meta( $product_id, 'woodmart_fbt_bundles_id' );
        } else {
            update_post_meta( $product_id, 'woodmart_fbt_bundles_id', $bundles );
        }
    }

    /**
     * Get all products in bundles for a specific product
     *
     * @param int $product_id
     * @return array Array of product IDs
     */
    public static function get_look_products( $product_id ) {
        $bundle_ids = (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true );
        $product_ids = [];

        foreach ( $bundle_ids as $bid ) {
            $bundle_products = (array) maybe_unserialize( get_post_meta( $bid, '_woodmart_fbt_products', true ) );
            $product_ids = array_merge( $product_ids, $bundle_products );
        }

        // Remove current product from results and duplicates
        $product_ids = array_unique( array_diff( $product_ids, [ $product_id ] ) );
        
        return $product_ids;
    }

    /**
     * Get all bundles (looks) that contain a specific product
     *
     * @param int $product_id
     * @return array Array of bundle post objects
     */
    public static function get_product_looks( $product_id ) {
        $bundle_ids = (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true );
        $bundles = [];

        foreach ( $bundle_ids as $bundle_id ) {
            $bundle = get_post( $bundle_id );
            if ( $bundle && $bundle->post_status === 'publish' ) {
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    /**
     * Check if a product has any looks associated
     *
     * @param int $product_id
     * @return bool
     */
    public static function product_has_looks( $product_id ) {
        $bundle_ids = (array) get_post_meta( $product_id, 'woodmart_fbt_bundles_id', true );
        return ! empty( $bundle_ids );
    }
}

new LA_Looks_Sync();