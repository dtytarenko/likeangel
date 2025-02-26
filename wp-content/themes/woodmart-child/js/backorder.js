jQuery(function($){
        // Коли обрали варіацію, WooCommerce викликає show_variation:
        $(document).on('show_variation', '.variations_form', function(event, variation) {

            // Зчитуємо кастомні поля, які ми сформували у PHP:
            var buttonText       = variation.my_custom_button_text       || 'Додати в кошик';
            var availabilityHtml = variation.my_custom_availability_html || '<p class="stock in-stock">Є в наявності</p>';

            // Оновлюємо кнопку:
            $('.single_add_to_cart_button')
                .text(buttonText)
                .removeClass('disabled')
                .prop('disabled', false);

            // Записуємо текст наявності у блок .woocommerce-variation-availability
            // (Woodmart зазвичай використовує саме цей селектор)
            $('.woocommerce-variation-availability').html(availabilityHtml);

            // Якщо товар справді "Відсутній у продажу", блокуємо кнопку
            if ( variation.is_out_of_stock ) {
                $('.single_add_to_cart_button')
                    .addClass('disabled')
                    .prop('disabled', true);
            }

            // Якщо хочете власне відображення для backorder,
            // можна додатково перевірити variation.is_on_backorder тут.
        });
});