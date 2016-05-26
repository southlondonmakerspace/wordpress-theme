var map;
var resizeTimer;
var normalCenter, wideCenter;

jQuery( document ).ready( function() {

	if (!document.getElementById('map')) {
		return;
	}

	L.mapbox.accessToken = 'pk.eyJ1Ijoic291dGhsb25kb25tYWtlcnNwYWNlIiwiYSI6ImNpZXQxY3FmeDAwMTZ0Y2tzb2UwMzV1dDgifQ.5YDoc_mkFc0wcrhPJLWE3Q';

	normalCenter = L.latLng( 51.45192095662713, -0.10089397430419922 );
	wideCenter = L.latLng( 51.4496209487744, -0.10102272033691406 );

	map = L.mapbox.map( 'map', 'mapbox.streets', {
		zoom: 15,
		zoomControl: false,
		center: normalCenter
	} );

	map.scrollWheelZoom.disable();

	handleSize();

	new L.Control.Zoom( { position: 'topright' } ).addTo( map );

	new L.mapbox.featureLayer({
		type: 'Feature',
		geometry: {
			type: 'Point',
			coordinates: [ -0.100906, 51.451915 ]
		},
		properties: {
			'title'  	  : 'South London Makerspace',
			'marker-size' : 'small',
			'marker-color': '#ff0000'
		}
	} ).addTo( map );
} );

function handleSize() {
	if ( window.matchMedia( "(min-width: 48em)" ).matches ) {
		map.setView( normalCenter );
	} else {
		map.setView( wideCenter );
	}
}

jQuery( window ).on( 'resize', function ( e ) {
	clearTimeout( resizeTimer );
	resizeTimer = setTimeout( handleSize, 250 );
} );