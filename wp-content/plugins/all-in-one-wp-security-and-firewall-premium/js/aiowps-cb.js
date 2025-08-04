jQuery(function($) {
	$.ajax({
		url: AIOWPSCB.ajaxurl,
		type: 'post',
		dataType: 'json',
		cache: false,
		data: {
			action: 'country_check_ajax',
			security: AIOWPSCB.security,
			post_id: AIOWPSCB.post_id
		},
		success: function( response ) {
			if (response.status == "ok") {
				jQuery('#aios-cb-style').remove(); // show content, ie, don't block
			} else if (response.status == "block") {
				if (window.location.href !== AIOWPSCB.redirect_url) {
					location.replace(response.redirect_url); // block, ie, redirect to configured URL
				}
				
			}
		}
	});
});