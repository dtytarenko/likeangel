jQuery(function($) {
	var maxmind_input = $('#aiowps_premium_maxmind_key');
	var aiowps_show_hide_text = $('.aiowps_show_hide_text');
	var aiowps_show_hide_icon = $('#aiowps_show_hide_icon');

	$('#aiowps-maxmind-show-hide-key').on('click', function() {
		if ('password' == maxmind_input.attr('type')) {
			maxmind_input.attr('type', 'text');
			aiowps_show_hide_text.text(aiowps_premium_integration_data.maxmind_hide);
			aiowps_show_hide_icon.addClass('dashicons-hidden').removeClass('dashicons-visibility');
			$(this).attr('aria-label', aiowps_premium_integration_data.maxmind_hide_password);
		} else {
			maxmind_input.attr('type', 'password');
			aiowps_show_hide_text.text(aiowps_premium_integration_data.maxmind_show);
			aiowps_show_hide_icon.addClass('dashicons-visibility').removeClass('dashicons-hidden');
			$(this).attr('aria-label', aiowps_premium_integration_data.maxmind_show_password);
		}
	});
} (jQuery));
