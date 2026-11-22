( function ( wp ) {
	const el = wp.element.createElement;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, ToggleControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'slms/directors', {
		edit: function ( props ) {
			const { attributes, setAttributes } = props;
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Directors' },
						el( SelectControl, {
							label: 'Show',
							value: attributes.show,
							options: [
								{ label: 'Past and present', value: 'all' },
								{ label: 'Current only', value: 'current' },
								{ label: 'Past only', value: 'past' },
							],
							onChange: ( show ) => setAttributes( { show } ),
						} ),
						el( SelectControl, {
							label: 'Order',
							value: attributes.order,
							options: [
								{ label: 'Oldest first', value: 'asc' },
								{ label: 'Newest first', value: 'desc' },
							],
							onChange: ( order ) => setAttributes( { order } ),
						} ),
						el( ToggleControl, {
							label: 'Show dates',
							checked: attributes.dates,
							onChange: ( dates ) => setAttributes( { dates } ),
						} )
					)
				),
				el( ServerSideRender, { block: 'slms/directors', attributes } )
			);
		},
		save: () => null,
	} );
} )( window.wp );
