(function ($) {
	'use strict';

	function getNextIndex($tbody) {
		var max = -1;
		$tbody.find('.cvp-manual-block-row').each(function () {
			var name = $(this).find('input').first().attr('name') || '';
			var match = name.match(/\[(\d+)\]/);
			if (match) {
				max = Math.max(max, parseInt(match[1], 10));
			}
		});
		return max + 1;
	}

	function addRow($container, data) {
		data = data || {};
		var $tbody = $container.find('#cvp-manual-blocks-body');
		var index = getNextIndex($tbody);
		var row =
			'<tr class="cvp-manual-block-row">' +
				'<td><input type="date" name="cvp_manual_blocks[' + index + '][check_in]" value="' + (data.check_in || '') + '" /></td>' +
				'<td><input type="date" name="cvp_manual_blocks[' + index + '][check_out]" value="' + (data.check_out || '') + '" /></td>' +
				'<td><input type="text" class="regular-text" name="cvp_manual_blocks[' + index + '][note]" value="' + (data.note || '') + '" /></td>' +
				'<td><button type="button" class="button cvp-remove-manual-block">&times;</button></td>' +
			'</tr>';
		$tbody.append(row);
	}

	$(document).on('click', '#cvp-add-manual-block', function (e) {
		e.preventDefault();
		addRow($(this).closest('.cvp-manual-blocks'));
	});

	$(document).on('click', '.cvp-remove-manual-block', function () {
		$(this).closest('tr').remove();
	});
})(jQuery);
