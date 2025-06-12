<?php
/**
 * Hide "Out of stock" badge when variations are available or backorders are allowed.
 *
 * @package Woodmart_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Removes the out-of-stock badge based on product stock state.
 *
 * Attached to the `woodmart_product_label_output` filter.
 *
 * @param array $labels Array of label HTML strings.
 * @return array
 */
function la_hide_out_of_stock_label( $labels ) {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return $labels;
    }

    $hide_label = false;

    if ( $product->is_type( 'variable' ) ) {
        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );
            if ( ! $variation ) {
                continue;
            }

            if ( $variation->is_in_stock() || $variation->backorders_allowed() ) {
                $hide_label = true;
                break;
            }
        }
    } elseif ( $product->is_type( 'simple' ) && $product->backorders_allowed() ) {
        $hide_label = true;
    }

    if ( $hide_label ) {
        foreach ( $labels as $index => $label_html ) {
            if ( false !== strpos( $label_html, 'out-of-stock product-label' ) ) {
                unset( $labels[ $index ] );
            }
        }
    }

    return $labels;
}

add_filter( 'woodmart_product_label_output', 'la_hide_out_of_stock_label', 10, 1 );
