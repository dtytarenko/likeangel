jQuery(function($) {
	
	$('form .tfa_user_roles').each(function() {
		var role = $(this).attr('id').substr(4);
		var make_available = $(this).is(':checked');
		var text = make_available ? '' : ' '+simbatfa_required_for_user_js.tfa_unavailable_for_role;
		if (make_available) {
			$('#tfa_required_'+role+', #tfa_required_'+role+'_label').css('opacity', '100%');
		} else {
			$('#tfa_required_'+role+', #tfa_required_'+role+'_label').css('opacity', '70%');
		}
		$('#tfa_required_'+role+'_label .label_append').html(text);
	});

	$('form .tfa_user_roles').change(function() {
		var role = $(this).attr('id').substr(4);
		var make_available = $(this).is(':checked');
		var text = make_available ? '' : ' '+simbatfa_required_for_user_js.tfa_unavailable_for_role;
		if (make_available) {
			$('#tfa_required_'+role+', #tfa_required_'+role+'_label').css('opacity', '100%');
		} else {
			$('#tfa_required_'+role+', #tfa_required_'+role+'_label').css('opacity', '70%');
		}
		$('#tfa_required_'+role+'_label .label_append').html(text);
	});
	
	// Set up Flatpickr
	var default_date = 'today';

	if (simbatfa_required_for_user_js.hasOwnProperty('default_date')) {
		default_date = simbatfa_required_for_user_js.default_date;
	}

	$('#tfa_require_enforce_after').flatpickr({
		minDate: 'today',
		defaultDate: default_date
	});
});
