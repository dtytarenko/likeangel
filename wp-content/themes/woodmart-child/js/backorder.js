jQuery(function($) {
    $(document).on('show_variation', '.variations_form', function(event, variation) {
        var isOnBackorder = variation.is_on_backorder || false;

        if (isOnBackorder) {
            $('.single_add_to_cart_button').text('Передзамовлення');
            $('.single_variation_wrap .stock').html(variation.availability_html);
        } else {
            $('.single_add_to_cart_button').text('Додати в кошик');
            $('.single_variation_wrap .stock').text('Є в наявності');
        }
    });
});
