jQuery(function($) {
	$('.simba_tfa_user_get_codes').on('click', function(e) {
		e.preventDefault();
		var $area = $(this);
		var whichuser = $(this).siblings('.simba_tfa_choose_user').val();
		if (null == whichuser || '' == whichuser) {
			alert(simbatfa_administrate_other_users.choose_valid_user);
			return;
		};
		$.post(ajaxurl, {
			action: "simbatfa_user_get_codes",
		 userid: whichuser,
		 nonce: simbatfa_administrate_other_users.get_codes_nonce
		}, function(response) {
			$area.parents('.simba_tfa_users').find('.simba_tfa_user_results').html(response);
			$('.simba_tfa_user_results .simbaotp_qr_container').qrcode({
				'render': 'image',
				'text': $('.simbaotp_qr_container:first').data('qrcode'),
			});
		});
	});
	
	$('.simba_tfa_user_deactivate').on('click', function(e) {
		e.preventDefault();
		var $area = $(this);
		var whichuser = $(this).siblings('.simba_tfa_choose_user').val();
		if (null == whichuser || '' == whichuser) {
			alert(simbatfa_administrate_other_users.choose_valid_user);
			return;
		};
		$.post(ajaxurl, {
			action: "simbatfa_user_activation",
		 userid: whichuser,
		 activate: 0,
		 nonce: simbatfa_administrate_other_users.user_activation_nonce
		}, function(response) {
			$area.parents('.simba_tfa_users').find('.simba_tfa_user_results').html(response);
		});
	});
	$('.simba_tfa_user_results').on('click', '#tfa-reset-privkey-for-user', function(e) {
		e.preventDefault();
		if (!confirm(simbatfa_administrate_other_users.warning_reset)) { return; }
		var user_id = $(this).data('user_id');
		var $area = $(this);
		if (!user_id) {
			console.log("TFA: Error: user_id not found for privkey reset click");
			return;
		}
		$.post(ajaxurl, {
			action: "simbatfa_user_privkey_reset",
			user_id: user_id,
			nonce: simbatfa_administrate_other_users.privkey_reset_nonce
		}, function(response) {
			$area.parents('.simba_tfa_users').find('.simba_tfa_user_results').html(response);
			//$area.parents('.simba_tfa_users').find('.simba_tfa_user_get_codes').on('click', );
		});
	});
	$('.simba_tfa_user_activate').on('click', function(e) {
		e.preventDefault();
		var $area = $(this);
		var whichuser = $(this).siblings('.simba_tfa_choose_user').val();
		if (null == whichuser || '' == whichuser) {
			alert(simbatfa_administrate_other_users.choose_valid_user);
			return;
		};
		$.post(ajaxurl, {
			action: "simbatfa_user_activation",
			userid: whichuser,
			activate: 1,
			nonce: simbatfa_administrate_other_users.user_activation_nonce
		}, function(response) {
			$area.parents('.simba_tfa_users').find('.simba_tfa_user_results').html(response);
		});
	});
	$('.simba_tfa_choose_user').select2({
		ajax: {
			url: simbatfa_administrate_other_users.choose_user_url,
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
					q: params.term, // search term
					page: params.page
				};
			},
			processResults: function (data) {
				return data;
			},
			cache: true
		},
		
		// 					escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
		minimumInputLength: 2,
		// 					templateResult: formatRepo, // omitted for brevity, see the source of this page
		// 					templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
	});
});
