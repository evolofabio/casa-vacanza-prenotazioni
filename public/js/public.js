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

	function parseAvailability($form) {
		var raw = $form.attr('data-availability');
		if (!raw) {
			return null;
		}

		try {
			return JSON.parse(raw);
		} catch (e) {
			return null;
		}
	}

	function datesOverlap(in1, out1, in2, out2) {
		return in1 < out2 && in2 < out1;
	}

	function countNights(checkIn, checkOut) {
		var start = Date.parse(checkIn);
		var end = Date.parse(checkOut);
		if (!start || !end || end <= start) {
			return 0;
		}
		return Math.round((end - start) / 86400000);
	}

	function isRangeBlocked(checkIn, checkOut, blockedRanges) {
		if (!checkIn || !checkOut || !blockedRanges || !blockedRanges.length) {
			return false;
		}

		for (var i = 0; i < blockedRanges.length; i++) {
			var range = blockedRanges[i];
			if (range.check_in && range.check_out && datesOverlap(checkIn, checkOut, range.check_in, range.check_out)) {
				return true;
			}
		}

		return false;
	}

	function validateSelectedDates($form, availability) {
		if (!availability) {
			return { valid: true, message: '' };
		}

		var checkIn = $form.find('[name="check_in"]').val();
		var checkOut = $form.find('[name="check_out"]').val();

		if (!checkIn || !checkOut) {
			return { valid: true, message: '' };
		}

		if (checkOut <= checkIn) {
			return {
				valid: false,
				message: cvpPublic.i18n.invalidRange
			};
		}

		var minNights = availability.min_nights || 1;
		if (countNights(checkIn, checkOut) < minNights) {
			return {
				valid: false,
				message: cvpPublic.i18n.minNights.replace('%d', minNights)
			};
		}

		if (isRangeBlocked(checkIn, checkOut, availability.blocked)) {
			return {
				valid: false,
				message: cvpPublic.i18n.datesBlocked
			};
		}

		return { valid: true, message: '' };
	}

	function applyAvailabilityConstraints($form) {
		var availability = parseAvailability($form);
		if (!availability) {
			return;
		}

		var $checkIn = $form.find('[name="check_in"]');
		var $checkOut = $form.find('[name="check_out"]');
		var today = new Date().toISOString().slice(0, 10);
		var minDate = availability.available_from && availability.available_from > today ? availability.available_from : today;

		$checkIn.attr('min', minDate);
		if (availability.available_to) {
			$checkIn.attr('max', availability.available_to);
			$checkOut.attr('max', availability.available_to);
		}

		$checkOut.attr('min', $checkIn.val() || minDate);

		var validation = validateSelectedDates($form, availability);
		showDateFeedback($form, validation.valid ? '' : validation.message, !validation.valid);
	}

	function updatePrice($form) {
		var apartmentId = $form.data('apartment-id');
		var checkIn = $form.find('[name="check_in"]').val();
		var checkOut = $form.find('[name="check_out"]').val();
		var $summary = $form.find('.cvp-price-summary');
		var availability = parseAvailability($form);
		var validation = validateSelectedDates($form, availability);

		if (!validation.valid) {
			$summary.attr('hidden', true);
			showDateFeedback($form, validation.message, true);
			return;
		}

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
		var $form = $(this).closest('.cvp-booking-form');
		applyAvailabilityConstraints($form);
		updatePrice($form);
	});

	$('.cvp-booking-form').each(function () {
		applyAvailabilityConstraints($(this));
	});

	$(document).on('submit', '.cvp-booking-form', function (e) {
		e.preventDefault();

		var $form = $(this);
		var availability = parseAvailability($form);
		var validation = validateSelectedDates($form, availability);

		if (!validation.valid) {
			showDateFeedback($form, validation.message, true);
			return;
		}

		if (!$form.find('[name="privacy_consent"]').is(':checked')) {
			showMessage($form, cvpPublic.i18n.privacyRequired, 'error');
			return;
		}

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
					applyAvailabilityConstraints($form);
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
		applyAvailabilityConstraints($modal.find('.cvp-booking-form'));
		updatePrice($modal.find('.cvp-booking-form'));
	});

	$(document).on('click', '.cvp-booking-modal__close, .cvp-booking-modal__overlay', function () {
		closeModal($(this).closest('.cvp-booking-modal'));
	});
})(jQuery);
