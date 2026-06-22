(function ($) {
	'use strict';

	function updateGalleryInput($container) {
		var ids = [];
		$container.find('.cvp-gallery-list li').each(function () {
			ids.push($(this).data('id'));
		});
		$container.find('#cvp_gallery').val(ids.join(','));
	}

	$(document).on('click', '#cvp-add-gallery-images', function (e) {
		e.preventDefault();

		var $container = $(this).closest('.cvp-gallery-admin');
		var frame = wp.media({
			title: 'Seleziona immagini',
			button: { text: 'Usa immagini' },
			multiple: true
		});

		frame.on('select', function () {
			var selection = frame.state().get('selection');
			selection.each(function (attachment) {
				attachment = attachment.toJSON();
				if ($container.find('[data-id="' + attachment.id + '"]').length) {
					return;
				}
				var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				$container.find('.cvp-gallery-list').append(
					'<li data-id="' + attachment.id + '">' +
						'<img src="' + thumb + '" alt="" />' +
						'<button type="button" class="cvp-remove-image">&times;</button>' +
					'</li>'
				);
			});
			updateGalleryInput($container);
		});

		frame.open();
	});

	$(document).on('click', '.cvp-remove-image', function () {
		var $container = $(this).closest('.cvp-gallery-admin');
		$(this).closest('li').remove();
		updateGalleryInput($container);
	});
})(jQuery);
