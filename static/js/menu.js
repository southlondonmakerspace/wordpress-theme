// The menu button's full-screen menu (see base.php) covers the page, so it
// closes when keyboard focus leaves it, or on Escape (back to the button),
// so focus never goes behind it where it can't be seen
{
	const menu = document.querySelector( '.site-nav details' );

	if ( menu ) {
		menu.addEventListener( 'focusout', e => {
			if ( menu.open && e.relatedTarget && ! menu.contains( e.relatedTarget ) ) {
				menu.open = false;
			}
		} );

		menu.addEventListener( 'keydown', e => {
			if ( e.key === 'Escape' && menu.open ) {
				menu.open = false;
				menu.querySelector( 'summary' ).focus();
			}
		} );
	}
}
