// The Hero setting's Choose images button (see inc/hero.php): opens the media
// library with the gallery's images ticked, to upload, pick and reorder them,
// and keeps their IDs in its hidden field and their thumbnails beside it
document.addEventListener( 'click', e => {
	const button = e.target.closest( '.slms-hero-choose' );

	if ( ! button ) {
		return;
	}

	const box = button.parentElement;
	const field = box.querySelector( '[name="slms_hero_gallery"]' );
	const frame = wp.media( {
		title: 'Choose images',
		button: { text: 'Use these images' },
		library: { type: 'image' },
		multiple: 'add',
	} );

	frame.on( 'open', () => {
		const selection = frame.state().get( 'selection' );
		field.value.split( ',' ).filter( Boolean ).forEach( id => selection.add( wp.media.attachment( id ) ) );
	} );

	frame.on( 'select', () => {
		const images = frame.state().get( 'selection' ).toJSON();
		field.value = images.map( image => image.id ).join( ',' );
		box.querySelector( '.slms-hero-images' ).replaceChildren( ...images.map( image => {
			const img = document.createElement( 'img' );
			img.src = image.sizes?.thumbnail?.url || image.url;
			img.alt = '';
			return img;
		} ) );
	} );

	frame.open();
} );
