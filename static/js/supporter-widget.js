// The Supporter widget's Choose image button (see
// inc/widget-slms-supporter.php): opens the media library to upload or pick an
// image, and puts its URL in the widget's Image URL field
document.addEventListener( 'click', e => {
	const button = e.target.closest( '.slms-supporter-choose' );

	if ( ! button ) {
		return;
	}

	const field = document.getElementById( button.dataset.target );
	const frame = wp.media( {
		title: 'Choose image',
		button: { text: 'Use this image' },
		library: { type: 'image' },
		multiple: false,
	} );

	frame.on( 'select', () => {
		field.value = frame.state().get( 'selection' ).first().get( 'url' );
		// So the widget editor sees the change and can save it
		field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		field.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

	frame.open();
} );
