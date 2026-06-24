(function ($) {
	'use strict';

	$(document).on('click', '#the-list .editinline', function () {
		var postId = parseInt($(this).closest('tr').attr('id').replace('post-', ''), 10);
		var $data = $('#cvp_inline_' + postId);

		if (!$data.length) {
			return;
		}

		var $row = $('tr.inline-edit-row');
		$row.find('input.cvp-qe-price').val($data.find('.cvp-qe-price').text());
		$row.find('input.cvp-qe-guests').val($data.find('.cvp-qe-guests').text());
		$row.find('input.cvp-qe-beds').val($data.find('.cvp-qe-beds').text());
	});
})(jQuery);
