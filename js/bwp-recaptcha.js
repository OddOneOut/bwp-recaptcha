jQuery(function($) {
	function toggle_comment_form_message() {
		var redirect = $('#select_response').val();

		redirect = redirect == 'redirect' ? true : false;

		if (redirect) {
			$('#input_error').parents('.bwp-clear').show();
			$('#input_back').parents('.bwp-clear').hide();
		} else {
			$('#input_error').parents('.bwp-clear').hide();
			$('#input_back').parents('.bwp-clear').show();
		}
	}

	function toggle_auto_fill_switch() {
		var selected = $('#select_response').val();
		$('#enable_auto_fill_comment').parents('li.bwp-clear').toggle(selected === 'redirect');
	}

	toggle_comment_form_message();
	toggle_auto_fill_switch();

	$('#select_response').on('change', function() {
		toggle_comment_form_message();
		toggle_auto_fill_switch();
	});
});
