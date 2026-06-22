(function ($) {
	'use strict';

	$(document).on('click', '.cvp-action', function () {
		var $btn = $(this);
		var bookingId = $btn.data('id');
		var action = $btn.data('action');
		var note = '';

		if ($btn.data('ask-note')) {
			var promptText = action === 'rifiutata' ? cvpAdmin.i18n.confirmReject : cvpAdmin.i18n.confirmCancel;
			note = window.prompt(promptText, '');
			if (note === null) {
				return;
			}
		}

		$btn.prop('disabled', true);

		$.post(cvpAdmin.ajaxUrl, {
			action: 'cvp_update_booking_status',
			nonce: cvpAdmin.nonce,
			booking_id: bookingId,
			status: action,
			note: note
		})
			.done(function (response) {
				if (response.success) {
					window.location.reload();
				} else {
					alert(response.data && response.data.message ? response.data.message : cvpAdmin.i18n.error);
					$btn.prop('disabled', false);
				}
			})
			.fail(function () {
				alert(cvpAdmin.i18n.error);
				$btn.prop('disabled', false);
			});
	});

	$(document).on('click', '.cvp-copy-shortcode', function () {
		var text = $(this).data('copy');
		var $btn = $(this);
		var original = $btn.text();

		function showCopied() {
			$btn.text(cvpAdmin.i18n.copied || 'Copiato!');
			setTimeout(function () {
				$btn.text(original);
			}, 1500);
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(showCopied);
			return;
		}

		var $temp = $('<textarea>').val(text).appendTo('body').select();
		try {
			document.execCommand('copy');
			showCopied();
		} catch (e) {
			window.prompt('Copia questo shortcode:', text);
		}
		$temp.remove();
	});
})(jQuery);
