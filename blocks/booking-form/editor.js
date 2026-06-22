(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var createElement = wp.element.createElement;

	registerBlockType('cvp/booking-form', {
		edit: function (props) {
			var blockProps = useBlockProps({ className: 'cvp-block-placeholder' });
			var apartments = (window.cvpBlockData && window.cvpBlockData.apartments) || [];

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
							label: 'Appartamento',
							value: props.attributes.apartmentId || 0,
							options: [{ label: '— Da URL —', value: 0 }].concat(
								apartments.map(function (a) {
									return { label: a.title, value: a.id };
								})
							),
							onChange: function (val) {
								props.setAttributes({ apartmentId: parseInt(val, 10) || 0 });
							}
						})
					)
				),
				createElement('div', { className: 'cvp-block-preview' },
					createElement('strong', null, 'Form Prenotazione'),
					createElement('p', null, 'Dati cliente, date, ospiti')
				)
			);
		}
	});
})(window.wp);
