jQuery(document).ready(function($) {
	//send parent product view
	if (!isNaN(wpadminpro_ajax_object.product_id)) {
		console.log('prodid='+wpadminpro_ajax_object.product_id) ;
		wpadmin_sendAjaxRequest(wpadminpro_ajax_object.product_id, 0);
	}
	
	//send parent default or changed variation view
	$(document).on( 'found_variation', 'form.cart', function( event, variation ) {
		
		if (!isNaN(variation['variation_id'])) {
			console.log('varid='+variation['variation_id']) ;
			wpadmin_sendAjaxRequest(0, variation['variation_id']);	 
		}
		
	});


	// Функція, яка відправляє AJAX-запит на сервер WordPress
	function wpadmin_sendAjaxRequest(product_id, varid) {
		// Створення об'єкту з даними для відправки
		var data = {
			action: 'wpadmin_item_view', // Назва дії AJAX-запиту, збігається з назвою у функції handle_ajax_request()
			product_id: product_id,
			variation_id: varid, // id варіації
			location: $(location).attr('href'), 
		};

		// Відправка AJAX-запиту
		$.ajax({
			url: wpadminpro_ajax_object.ajaxurl, // Глобальна змінна ajaxurl містить URL для AJAX-запитів в WordPress
			method: 'POST',
			data: data,
			success: function(response) {
				// Обробка відповіді від сервера
				console.log(response);
				// Тут можна виконати додаткові дії з отриманими даними
			},
			error: function(xhr, status, error) {
				// Обробка помилок під час виконання AJAX-запиту
				console.error(error);
			}
		});
	}



});
