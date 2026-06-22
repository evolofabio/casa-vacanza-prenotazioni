(function (wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var createElement = wp.element.createElement;

	registerBlockType('cvp/apartment-card', {
		edit: function (props) {
			var blockProps = useBlockProps({ className: 'cvp-block-placeholder' });
			var apartments = (window.cvpBlockData && window.cvpBlockData.apartments) || [];
			var selected = apartments.find(function (a) {
				return a.id === props.attributes.apartmentId;
			});

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
							options: [{ label: '— Corrente / Seleziona —', value: 0 }].concat(
								apartments.map(function (a) {
									return { label: a.title, value: a.id };
								})
							),
							onChange: function (val) {
								props.setAttributes({ apartmentId: parseInt(val, 10) || 0 });
							}
						}),
						createElement(ToggleControl, {
							label: 'Mostra bottone prenotazione',
							checked: props.attributes.showBooking !== false,
							onChange: function (val) {
								props.setAttributes({ showBooking: val });
							}
						})
					)
				),
				createElement('div', { className: 'cvp-block-preview' },
					createElement('strong', null, 'Card Appartamento'),
					createElement('p', null, selected ? selected.title : 'Appartamento corrente o da selezionare')
				)
			);
		}
	});
})(window.wp);
