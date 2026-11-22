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

// Sub-menus on wide screens (see .site-nav in main.less) open on hover or
// focus, or with the arrow button after their item, for touch screens and
// screen readers. Escape closes one until the pointer or focus leaves its
// item. Phones show sub-items as rows in the menu, and hide the buttons.
{
	const items = document.querySelectorAll( '.site-nav .menu-item-has-children' );
	const shown = li => li.matches( ':hover, :focus-within, .is-open' ) && ! li.classList.contains( 'is-closed' );
	const button = li => li.querySelector( ':scope > .sub-menu-toggle' );
	// After the browser has caught up with where the pointer and focus are
	const update = li => requestAnimationFrame( () => button( li ).setAttribute( 'aria-expanded', shown( li ) ) );
	const open = li => {
		li.classList.remove( 'is-closed' );
		li.classList.add( 'is-open' );
		update( li );
	};
	const close = li => {
		li.classList.remove( 'is-open' );
		li.classList.add( 'is-closed' );
		update( li );
	};

	items.forEach( li => {
		const link = li.querySelector( ':scope > a' );
		const toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'sub-menu-toggle';
		toggle.setAttribute( 'aria-label', link.textContent.trim() + ' sub-menu' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		// Its icon, from slms_icon() (see functions.php)
		toggle.innerHTML = slmsMenu.chevron;
		link.after( toggle );

		// A tap counts only the button's own open state: tapping also hovers
		// over and focuses the item, which would otherwise open it first
		let tapped = null;
		toggle.addEventListener( 'pointerdown', e => {
			tapped = e.pointerType === 'mouse' ? null : li.classList.contains( 'is-open' );
		} );
		toggle.addEventListener( 'click', () => {
			( tapped ?? shown( li ) ) ? close( li ) : open( li );
			tapped = null;
		} );

		li.addEventListener( 'mouseenter', () => update( li ) );
		li.addEventListener( 'focusin', e => {
			// Tabbing on into a sub-menu closed with Escape shows it again, so
			// focus is never on a link that can't be seen
			if ( li.querySelector( '.sub-menu' ).contains( e.target ) ) {
				li.classList.remove( 'is-closed' );
			}
			update( li );
		} );
		li.addEventListener( 'mouseleave', () => {
			// Closed with Escape: shown again on the next hover, unless the
			// keyboard is still in it
			if ( ! li.contains( document.activeElement ) ) {
				li.classList.remove( 'is-closed' );
			}
			update( li );
		} );
		li.addEventListener( 'focusout', e => {
			if ( ! li.contains( e.relatedTarget ) ) {
				li.classList.remove( 'is-open', 'is-closed' );
			}
			update( li );
		} );
	} );

	// Escape closes any sub-menu that's showing, and from inside one takes
	// focus back to its button
	document.addEventListener( 'keydown', e => {
		if ( e.key === 'Escape' ) {
			items.forEach( li => {
				if ( shown( li ) ) {
					const inside = li.querySelector( '.sub-menu' ).contains( document.activeElement );
					close( li );
					inside && button( li ).focus();
				}
			} );
		}
	} );

	// Tapping anywhere else closes one opened with its button
	document.addEventListener( 'click', e => items.forEach( li => {
		if ( li.classList.contains( 'is-open' ) && ! li.contains( e.target ) ) {
			li.classList.remove( 'is-open' );
			update( li );
		}
	} ) );

	// Near the right edge of the screen, a sub-menu lines up with the right of
	// its item instead, so it doesn't run off
	const fit = () => items.forEach( li => {
		li.classList.remove( 'sub-menu-end' );
		if ( li.querySelector( '.sub-menu' ).getBoundingClientRect().right > document.documentElement.clientWidth - 16 ) {
			li.classList.add( 'sub-menu-end' );
		}
	} );
	fit();
	document.fonts.ready.then( fit );
	addEventListener( 'resize', fit );
}
