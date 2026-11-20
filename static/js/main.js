// The front page's map. Loaded with defer (see functions.php), so it runs
// once the page has been read, after MapLibre, pmtiles and the map's style.
{
	const el = document.getElementById( 'map' );

	if ( el && window.maplibregl && window.pmtiles && window.slmsMap ) {
		// [ longitude, latitude ]
		const location = [ -0.100906, 51.451915 ];

		maplibregl.addProtocol( 'pmtiles', new pmtiles.Protocol().tile );

		const map = new maplibregl.Map( {
			container: el,
			style: {
				version: 8,
				glyphs: slmsMap.base + '/fonts/{fontstack}/{range}.pbf',
				sources: {
					protomaps: {
						type: 'vector',
						url: 'pmtiles://' + slmsMap.base + '/slms.pmtiles'
					}
				},
				layers: window.slmsMapLayers
			},
			center: location,
			zoom: 14,
			minZoom: 12,
			maxZoom: 17,
			// Centred on the pin, as far as the tiles go each way: they cover
			// -0.165, 51.425 to -0.04, 51.478 of south London
			maxBounds: [ [ -0.1618, 51.4258 ], [ -0.04, 51.478 ] ],
			scrollZoom: false,
			dragRotate: false,
			pitchWithRotate: false,
			touchPitch: false,
			// The map has its own credit (see base.php)
			attributionControl: false
		} );

		// The card overlaps the foot of the map, so leave that out when centring:
		// the pin sits in the middle of the map that can be seen
		const card = el.closest( 'header' )?.nextElementSibling;
		const fit = () => {
			const overlap = card ? el.getBoundingClientRect().bottom - card.getBoundingClientRect().top : 0;
			map.setPadding( { top: 0, right: 0, bottom: Math.max( 0, overlap ), left: 0 } );
		};
		fit();
		addEventListener( 'resize', fit );

		map.touchZoomRotate.disableRotation();

		// Zoom buttons beside the credit's button, in its style (see post.less),
		// rather than MapLibre's own control. Each is turned off at its limit.
		const caption = el.querySelector( 'figcaption' );
		if ( caption ) {
			const zoom = document.createElement( 'div' );
			zoom.className = 'zoom';
			const buttons = [ [ 'Zoom in', slmsMap.icons.plus, () => map.zoomIn(), () => map.getZoom() >= map.getMaxZoom() ],
				[ 'Zoom out', slmsMap.icons.minus, () => map.zoomOut(), () => map.getZoom() <= map.getMinZoom() ] ]
				.map( ( [ label, icon, onClick, atLimit ] ) => {
					const button = document.createElement( 'button' );
					button.type = 'button';
					button.setAttribute( 'aria-label', label );
					// Its icon, from slms_icon() (see functions.php)
					button.innerHTML = icon;
					button.addEventListener( 'click', onClick );
					zoom.append( button );
					return () => button.disabled = atLimit();
				} );
			caption.append( zoom );
			map.on( 'zoomend', () => buttons.forEach( update => update() ) );
		}

		const marker = new maplibregl.Marker( { color: '#ff0000', scale: 0.75 } )
			.setLngLat( location )
			.addTo( map );

		// The pin links to the Find us page: a real link around the pin's image,
		// so it works from the keyboard and opens in a new tab. Without the page,
		// it shows the space's name instead.
		if ( slmsMap.findUs ) {
			const pin = marker.getElement();
			const link = document.createElement( 'a' );
			link.href = slmsMap.findUs;
			link.setAttribute( 'aria-label', 'Find us' );
			link.append( ...pin.childNodes );
			pin.append( link );
		} else {
			marker.setPopup( new maplibregl.Popup( { offset: 25, closeButton: false } ).setText( 'South London Makerspace' ) );
		}

		// The credit starts shown, as OpenStreetMap asks, then closes to its button
		// after a few seconds, unless someone has already opened or closed it
		const credit = el.querySelector( 'details' );
		if ( credit ) {
			const timer = setTimeout( () => credit.open = false, 5000 );
			// Not 'toggle', which also fires for the open it starts with
			credit.querySelector( 'summary' ).addEventListener( 'click', () => clearTimeout( timer ), { once: true } );
		}
	}
}
