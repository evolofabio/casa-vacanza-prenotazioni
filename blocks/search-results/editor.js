(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var createElement = wp.element.createElement;

	registerBlockType('cvp/search-results', {
		edit: function () {
			var blockProps = useBlockProps({ className: 'cvp-block-placeholder' });

			return createElement(
				'div',
				blockProps,
				createElement('div', { className: 'cvp-block-preview' },
					createElement('strong', null, 'Risultati Ricerca Appartamenti'),
					createElement('p', null, 'Barra ricerca + griglia appartamenti disponibili')
				)
			);
		}
	});
})(window.wp);
