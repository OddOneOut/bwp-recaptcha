/*global jQuery*/
jQuery(function($) {
	'use strict';

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

	toggle_comment_form_message();

	$('#select_response').on('change', function() {
		toggle_comment_form_message();
	});
});
