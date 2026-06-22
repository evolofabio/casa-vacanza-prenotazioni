(function ($) {
	'use strict';

	function showMessage($form, message, type) {
		var $msg = $form.find('.cvp-form-message');
		$msg.removeAttr('hidden').removeClass('is-success is-error').addClass(type === 'success' ? 'is-success' : 'is-error').text(message);
	}

	function updatePrice($form) {
		var apartmentId = $form.data('apartment-id');
		var checkIn = $form.find('[name="check_in"]').val();
		var checkOut = $form.find('[name="check_out"]').val();
		var $summary = $form.find('.cvp-price-summary');

		if (!apartmentId || !checkIn || !checkOut) {
			$summary.attr('hidden', true);
			return;
		}

		$.post(cvpPublic.ajaxUrl, {
			action: 'cvp_calculate_price',
			nonce: cvpPublic.nonce,
			apartment_id: apartmentId,
			check_in: checkIn,
			check_out: checkOut
		}).done(function (response) {
			if (response.success) {
				$summary.removeAttr('hidden').find('.cvp-price-summary__value').text(response.data.total);
			} else {
				$summary.attr('hidden', true);
			}
		});
	}

	$(document).on('change', '.cvp-booking-form .cvp-date-input, .cvp-booking-form [name="check_in"], .cvp-booking-form [name="check_out"]', function () {
		updatePrice($(this).closest('.cvp-booking-form'));
	});

	$(document).on('submit', '.cvp-booking-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var $btn = $form.find('[type="submit"]');
		var originalText = $btn.text();

		$btn.prop('disabled', true).text(cvpPublic.i18n.sending);
		$form.find('.cvp-form-message').attr('hidden', true);

		var data = $form.serializeArray();
		data.push({ name: 'action', value: 'cvp_submit_booking' });
		data.push({ name: 'nonce', value: cvpPublic.nonce });

		$.post(cvpPublic.ajaxUrl, data)
			.done(function (response) {
				if (response.success) {
					showMessage($form, response.data.message, 'success');
					$form[0].reset();
					$form.find('.cvp-price-summary').attr('hidden', true);
				} else {
					showMessage($form, response.data && response.data.message ? response.data.message : cvpPublic.i18n.error, 'error');
				}
			})
			.fail(function () {
				showMessage($form, cvpPublic.i18n.error, 'error');
			})
			.always(function () {
				$btn.prop('disabled', false).text(originalText);
			});
	});

	$(document).on('click', '.cvp-gallery-thumb', function () {
		var $thumb = $(this);
		var url = $thumb.data('url');
		$thumb.closest('.cvp-apartment-card__gallery').find('.cvp-gallery-main img').attr('src', url);
		$thumb.siblings().removeClass('is-active');
		$thumb.addClass('is-active');
	});

	$(document).on('click', '.cvp-open-booking', function () {
		var apartmentId = $(this).data('apartment-id');
		var $modal = $('#cvp-booking-modal-' + apartmentId);
		var checkIn = $(this).data('check-in');
		var checkOut = $(this).data('check-out');
		var guests = $(this).data('guests');

		if (checkIn) {
			$modal.find('[name="check_in"]').val(checkIn);
		}
		if (checkOut) {
			$modal.find('[name="check_out"]').val(checkOut);
		}
		if (guests) {
			$modal.find('[name="guests"]').val(guests);
		}

		$modal.removeAttr('hidden');
		updatePrice($modal.find('.cvp-booking-form'));
	});

	$(document).on('click', '.cvp-booking-modal__close, .cvp-booking-modal__overlay', function () {
		$(this).closest('.cvp-booking-modal').attr('hidden', true);
	});
})(jQuery);
