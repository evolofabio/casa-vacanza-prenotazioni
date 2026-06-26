(function ($) {
	'use strict';

	var lastFocused = null;

	function showMessage($form, message, type) {
		var $msg = $form.find('.cvp-form-message');
		$msg.removeAttr('hidden').removeClass('is-success is-error').addClass(type === 'success' ? 'is-success' : 'is-error').text(message);
	}

	function showDateFeedback($form, message, isError) {
		var $feedback = $form.find('.cvp-date-feedback');
		if (!message) {
			$feedback.attr('hidden', true).text('');
			return;
		}
		$feedback.removeAttr('hidden').toggleClass('is-error', !!isError).text(message);
	}

	function applyAvailabilityConstraints($form) {
		var raw = $form.attr('data-availability');
		if (!raw) {
			return;
		}

		var availability;
		try {
			availability = JSON.parse(raw);
		} catch (e) {
			return;
		}

		var $checkIn = $form.find('[name="check_in"]');
		var $checkOut = $form.find('[name="check_out"]');
		var today = new Date().toISOString().slice(0, 10);
		var minDate = availability.available_from && availability.available_from > today ? availability.available_from : today;

		$checkIn.attr('min', minDate);
		if (availability.available_to) {
			$checkOut.attr('max', availability.available_to);
		}
	}

	function updatePrice($form) {
		var apartmentId = $form.data('apartment-id');
		var checkIn = $form.find('[name="check_in"]').val();
		var checkOut = $form.find('[name="check_out"]').val();
		var $summary = $form.find('.cvp-price-summary');

		if (!apartmentId || !checkIn || !checkOut) {
			$summary.attr('hidden', true);
			showDateFeedback($form, '', false);
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
				showDateFeedback($form, '', false);
			} else {
				$summary.attr('hidden', true);
				showDateFeedback($form, response.data && response.data.message ? response.data.message : cvpPublic.i18n.error, true);
			}
		});
	}

	function getModalFocusable($modal) {
		return $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
	}

	function closeModal($modal) {
		$modal.attr('hidden', true).attr('aria-hidden', 'true');
		$(document).off('keydown.cvpModal');
		if (lastFocused && typeof lastFocused.focus === 'function') {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	function openModal($modal) {
		lastFocused = document.activeElement;
		$modal.removeAttr('hidden').attr('aria-hidden', 'false');
		getModalFocusable($modal).first().focus();

		$(document).on('keydown.cvpModal', function (e) {
			if (e.key === 'Escape') {
				closeModal($modal);
				return;
			}

			if (e.key !== 'Tab') {
				return;
			}

			var focusable = getModalFocusable($modal);
			if (!focusable.length) {
				return;
			}

			var first = focusable.first()[0];
			var last = focusable.last()[0];

			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		});
	}

	$(document).on('change', '.cvp-booking-form .cvp-date-input, .cvp-booking-form [name="check_in"], .cvp-booking-form [name="check_out"]', function () {
		updatePrice($(this).closest('.cvp-booking-form'));
	});

	$('.cvp-booking-form').each(function () {
		applyAvailabilityConstraints($(this));
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

		openModal($modal);
		updatePrice($modal.find('.cvp-booking-form'));
	});

	$(document).on('click', '.cvp-booking-modal__close, .cvp-booking-modal__overlay', function () {
		closeModal($(this).closest('.cvp-booking-modal'));
	});
})(jQuery);
