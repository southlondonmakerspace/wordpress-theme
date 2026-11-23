// The hero's gallery (see base.php). Loaded with defer (see functions.php).
// Adds pause, previous, a dot for each image and next beside the caption's
// button, in the style of the map's zoom buttons (see post.less), and shows
// each image's caption. Unless someone has asked for reduced motion, it moves
// on to the next image every few seconds until it's paused or moved by hand.
{
	const hero = document.querySelector( '.hero--gallery' );
	const list = hero?.querySelector( 'ul' );

	if ( list && window.slmsGallery && list.children.length > 1 ) {
		const slides = [ ...list.children ];
		const motion = matchMedia( '(prefers-reduced-motion: no-preference)' ).matches;
		const showing = () => Math.round( list.scrollLeft / list.clientWidth );
		// The image it's moving to, if it's on its way, so pressing an arrow
		// twice goes two images on, rather than counting from halfway
		let target = null;
		const current = () => target ?? showing();
		const go = i => {
			target = ( i + slides.length ) % slides.length;
			list.scrollTo( { left: target * list.clientWidth, behavior: motion ? 'smooth' : 'instant' } );
		};

		// The caption's button, if any image has a caption (see base.php)
		let caption = hero.querySelector( 'figcaption' );
		if ( ! caption ) {
			caption = document.createElement( 'figcaption' );
			hero.append( caption );
		}
		const details = caption.querySelector( 'details' );

		const button = ( label, html, onClick ) => {
			const el = document.createElement( 'button' );
			el.type = 'button';
			el.setAttribute( 'aria-label', label );
			el.innerHTML = html;
			el.addEventListener( 'click', onClick );
			return el;
		};

		// Turning by itself: on to start with, unless someone has asked for
		// reduced motion. Moving it by hand stops it, as does the pause button.
		let playing = motion;
		const pause = button( '', '', () => play( ! playing ) );
		const play = on => {
			playing = on;
			pause.setAttribute( 'aria-label', on ? 'Pause' : 'Play' );
			pause.innerHTML = on ? slmsGallery.icons.pause : slmsGallery.icons.play;
			// Screen readers hear where it's got to only when it's not turning
			// by itself, so they're not interrupted every few seconds
			live.setAttribute( 'aria-live', on ? 'off' : 'polite' );
		};
		// A dot for each image, between previous and next, which go round
		// from the last image to the first
		const dots = document.createElement( 'div' );
		dots.className = 'dots';
		dots.append( ...slides.map( ( slide, i ) => button( 'Image ' + ( i + 1 ), '', () => {
			play( false );
			go( i );
		} ) ) );

		const arrows = document.createElement( 'div' );
		arrows.className = 'arrows';
		arrows.append(
			pause,
			button( 'Previous image', slmsGallery.icons.previous, () => {
				play( false );
				go( current() - 1 );
			} ),
			dots,
			button( 'Next image', slmsGallery.icons.next, () => {
				play( false );
				go( current() + 1 );
			} )
		);

		// Which image is showing, read out by screen readers (see play())
		const live = document.createElement( 'span' );
		live.className = 'screen-reader-text';

		caption.append( arrows, live );
		play( playing );

		// The dot, caption and read-out of the image showing
		let last = 0;
		const update = () => {
			const i = showing();
			if ( i === last && live.textContent ) {
				return;
			}
			last = i;
			[ ...dots.children ].forEach( ( dot, j ) => dot.setAttribute( 'aria-current', i === j ) );
			live.textContent = 'Image ' + ( i + 1 ) + ' of ' + slides.length;
			if ( details ) {
				const text = slides[ i ].dataset.caption || '';
				details.hidden = ! text;
				details.querySelector( 'span' ).textContent = text;
			}
		};
		update();

		// go()'s move has finished once it has been still for a moment
		let settle;
		list.addEventListener( 'scroll', () => {
			clearTimeout( settle );
			settle = setTimeout( () => target = null, 150 );
			update();
		} );

		// Moving it sideways by hand stops it turning: a sideways scroll or
		// swipe, or the keyboard's arrows. Not scrolling the page up or down
		// over it.
		const byHand = () => {
			target = null;
			play( false );
		};
		let touch;
		list.addEventListener( 'wheel', e => Math.abs( e.deltaX ) > Math.abs( e.deltaY ) && byHand(), { passive: true } );
		list.addEventListener( 'touchstart', e => touch = e.touches[0], { passive: true } );
		list.addEventListener( 'touchmove', e => {
			const t = e.touches[0];
			if ( touch && Math.abs( t.clientX - touch.clientX ) > Math.abs( t.clientY - touch.clientY ) ) {
				touch = null;
				byHand();
			}
		}, { passive: true } );
		list.addEventListener( 'keydown', e => [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( e.key ) && byHand() );

		// Waits while the images are pointed at or have focus, so they don't
		// move from under someone looking at them. Not its buttons, so Play
		// starts it straight away.
		let held = false;
		list.addEventListener( 'pointerenter', () => held = true );
		list.addEventListener( 'pointerleave', () => held = false );
		list.addEventListener( 'focus', () => held = true );
		list.addEventListener( 'blur', () => held = false );

		// Its seconds per image, set in the editor (see inc/hero.php)
		setInterval( () => playing && ! held && ! document.hidden && go( current() + 1 ), ( hero.dataset.seconds || 4 ) * 1000 );
	}
}
