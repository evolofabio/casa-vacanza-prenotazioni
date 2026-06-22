(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var createElement = wp.element.createElement;

	registerBlockType('cvp/search-bar', {
		edit: function (props) {
			var blockProps = useBlockProps({ className: 'cvp-block-placeholder' });
			var pages = (window.cvpBlockData && window.cvpBlockData.pages) || [];

			return createElement(
				'div',
				blockProps,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: 'Impostazioni' },
						createElement(SelectControl, {
							label: 'Pagina risultati',
							value: props.attributes.resultsPage || 0,
							options: [{ label: '— Default —', value: 0 }].concat(
								pages.map(function (p) {
									return { label: p.title, value: p.id };
								})
							),
							onChange: function (val) {
								props.setAttributes({ resultsPage: parseInt(val, 10) || 0 });
							}
						})
					)
				),
				createElement('div', { className: 'cvp-block-preview' },
					createElement('strong', null, 'Barra Ricerca Casa Vacanza'),
					createElement('p', null, 'Check-in · Check-out · Ospiti · Cerca')
				)
			);
		}
	});
})(window.wp);
