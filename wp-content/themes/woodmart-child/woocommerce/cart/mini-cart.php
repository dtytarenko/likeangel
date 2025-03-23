<?php
defined( 'ABSPATH' ) || exit;

$items_to_show = apply_filters( 'woodmart_mini_cart_items_to_show', 30 );

// Перевірка наявності недоступних товарів перед виводом
$has_unavailable_products = false;
foreach ( WC()->cart->get_cart() as $cart_item ) {
    $_product = $cart_item['data'];
    if ( ! $_product->is_in_stock() ) {
        $has_unavailable_products = true;
        break;
    }
}

do_action( 'woocommerce_before_mini_cart' );

if ( $has_unavailable_products ) {
    echo '<div class="mini-cart-availability-warning" style="color: red; background: #fff8e5; border-left: 4px solid orange; padding: 10px 15px; margin: 15px 20px;">' .
        esc_html__( 'У кошику є товари, які зараз недоступні. Будь ласка, видаліть їх перед оформленням замовлення.', 'woocommerce' ) .
        '</div>';
}
?>

<div class="shopping-cart-widget-body wd-scroll">
    <div class="wd-scroll-content">

        <?php if ( ! WC()->cart->is_empty() ) : ?>

            <ul class="cart_list product_list_widget woocommerce-mini-cart <?php echo esc_attr( $args['list_class'] ); ?>">

                <?php
                    do_action( 'woocommerce_before_mini_cart_contents' );

                    $_i = 0;

                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                        $_i++;
                        if( $_i > $items_to_show ) break;

                        $_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        $product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                            $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                            $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                            $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                            ?>
                            <li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                <a href="<?php echo esc_url( $product_permalink ); ?>" class="cart-item-link wd-fill"><?php esc_html_e('Show', 'woocommerce'); ?></a>
                                <?php
                                    echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                                        '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
                                        esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                        esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
                                        esc_attr( $product_id ),
                                        esc_attr( $cart_item_key ),
                                        esc_attr( $_product->get_sku() )
                                    ), $cart_item_key );
                                ?>
                                <?php if ( empty( $product_permalink ) ) : ?>
                                    <?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $product_permalink ); ?>" class="cart-item-image">
                                        <?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
                                    </a>
                                <?php endif; ?>

                                <div class="cart-info">
                                    <span class="wd-entities-title">
                                        <?php echo $product_name; ?>
                                    </span>

                                    <?php if ( woodmart_get_opt( 'show_sku_in_mini_cart' ) ) : ?>
                                        <div class="wd-product-sku">
                                            <span class="wd-label"><?php esc_html_e( 'SKU:', 'woodmart' ); ?></span>
                                            <span>
                                                <?php echo $_product->get_sku() ? esc_html( $_product->get_sku() ) : esc_html__( 'N/A', 'woocommerce' ); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>

                                    <?php if ( ! $_product->is_in_stock() ) : ?>
                                        <div class="wd-out-of-stock" style="color:red; margin-top:5px;">
                                            <?php esc_html_e( 'Немає у наявності', 'woocommerce' ); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php
                                    if ( ! $_product->is_sold_individually() && $_product->is_purchasable() && woodmart_get_opt( 'mini_cart_quantity' ) && apply_filters( 'woodmart_show_widget_cart_item_quantity', true, $cart_item_key ) ) {
                                        woocommerce_quantity_input(
                                            array(
                                                'input_value' => $cart_item['quantity'],
                                                'input_name'  => "cart[{$cart_item_key}][qty]",
                                                'min_value'   => 0,
                                                'max_value'   => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
                                            ),
                                            $_product
                                        );
                                    }
                                    ?>

                                    <?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity">' . sprintf( '%s &times; %s', $cart_item['quantity'], $product_price ) . '</span>', $cart_item, $cart_item_key ); ?>
                                </div>
                            </li>
                            <?php
                        }
                    }

                    do_action( 'woocommerce_mini_cart_contents' );
                ?>
            </ul>

        <?php else : ?>
            <div class="wd-empty-mini-cart">
                <p class="woocommerce-mini-cart__empty-message empty title"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>
                <?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
                    <a class="btn btn-size-small btn-color-primary wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                        <?php esc_html_e( 'Return To Shop', 'woodmart' ) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="shopping-cart-widget-footer<?php echo ( WC()->cart->is_empty() ? ' wd-cart-empty' : '' ); ?>">
    <?php if ( ! WC()->cart->is_empty() ) : ?>
        <p class="woocommerce-mini-cart__total total">
            <?php do_action( 'woocommerce_widget_shopping_cart_total' ); ?>
        </p>
        <?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>
        <p class="woocommerce-mini-cart__buttons buttons"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>
        <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
    <?php endif; ?>

    <?php do_action( 'woocommerce_after_mini_cart' ); ?>
</div>