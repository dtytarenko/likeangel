jQuery(document).ready(function($) {
	//send parent product view
	if (!isNaN(wpadminpro_remarket_object.product_id) && !isNaN(wpadminpro_remarket_object.remarket_gads_code) && !isNaN(wpadminpro_remarket_object.gadsid)) {
		
		if (wpadminpro_remarket_object.remarket_gads_code == '1') { 
			product_price = $("#wp-product-data-"+wpadminpro_remarket_object.product_id).data("product_price");
			product_id = wpadminpro_remarket_object.product_id;
			
			console.log('remarket id=' + product_id);
			console.log('product_price=' + product_price);
			console.log('gadsid=' + gadsid);
			
			/*************** Remarketing page view ************/
			gtag('event','view_item', {
				
				'send_to': gadsid,
				'value': product_price,
				'items': [
				{
					'id': product_id, 
					'google_business_vertical': 'retail',
				}]
			});

			console.log("remarketing page_view");

		}
	}
	/*
	https://github.com/tlamedia/gtm-kit/blob/main/src/js/woocommerce.js#L170
	document.addEventListener('click', function (e) {
		const eventTargetElement = e.target;

		if (!eventTargetElement) {
			return true;
		}
	let event;
		let formCartElement = eventTargetElement.closest('form.cart');
	if ( formCartElement ) {
			if ( eventTargetElement.closest( '.single_add_to_cart_button:not(.disabled,.input-needed)' ) ) {
				event = 'add_to_cart';
			} 
	
	const productVariantId = formCartElement.querySelectorAll(
			'[name=variation_id]'
		);
	
	
	
	
	
	
	
	
	ads gtag
	gtag('event', 'page_view', {
    'send_to': 'AW-952670789',
    'value': 'replace with value',                    ////  цена товара
    'items': [{
      'id': 'replace with value',                    ////  id товара
      'google_business_vertical': 'retail'
    }]
  });
	
	gtag('event', 'add_to_cart', {
'send_to': 'AW-983983579',
'value': <Цена товара>,
'items': [{
'id': <Идентификатор товара>,
'google_business_vertical': 'retail'
}]
});

let PRODUCT_LIST = []; // Список товаров
PRODUCT_LIST.push(
id: <Идентификатор товара>,
google_business_vertical: 'retail'
);
gtag('event', 'purchase', {
'send_to': 'AW-983983579',
'value': <Сумма заказа>,
'items': PRODUCT_LIST
}); 
	
	
	
	tagmanager
var dataLayer = window.dataLayer || [];
  dataLayer.push({
    'event': 'view_item',
    'value': 'ПЕРЕДАТЬ_ЦЕНУ_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
    'items':[{
      'id': 'ПЕРЕДАТЬ_ID_ТОВАРА_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
      'google_business_vertical': 'retail'					
    }]
  });
  
  
  
  
  var dataLayer = window.dataLayer || [];
  dataLayer.push({
    'event': 'add_to_cart',
    'value': 'ПЕРЕДАТЬ_ЦЕНУ_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
    'items':[{
      'id': 'ПЕРЕДАТЬ_ID_ТОВАРА_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
      'google_business_vertical': 'retail'					
    }]
  });
  
  
  
  
  
  var dataLayer = window.dataLayer || [];
  dataLayer.push({
    'event': 'purchase',
    'value': 'ПЕРЕДАТЬ_ЦЕНУ_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
    'items': [{
      'id': 'ПЕРЕДАТЬ_ID_ТОВАРА_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
        'google_business_vertical': 'retail'
      },
      {
        'id': 'ПЕРЕДАТЬ_ID_ТОВАРА_ЧЕРЕЗ_ПЕРЕМЕННУЮ',
        'google_business_vertical': 'retail'
    }]
  });
  
  
*/
})