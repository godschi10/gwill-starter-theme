/**
 * GWill Starter — service worker.
 *
 * PORTED from gwill-finance-theme (tech-theme lineage). Conservative
 * caching: navigations are network-first with a cached shell fallback for
 * offline; static assets use stale-while-revalidate. Dynamic HTML is NEVER
 * precached (content stays fresh); admin/API/uploads/preview URLs are
 * excluded outright. VERSION busts the cache on deploy (the file carries
 * the theme version at publish time).
 *
 * Publish-time token: inc/pwa.php replaces gwill-starter-@PUBLISH@ with
 * gwill-starter-<version>-<mtime> on every deploy, so each release ships a
 * UNIQUE VERSION -> fresh cache namespaces -> activate purges the old ones.
 * (A hardcoded VERSION pins stale assets on clients.)
 *
 * LAW L2 (docs/LAWS.md): the published root /sw.js must be served
 * no-cache — see the nginx block in the law.
 *
 * @package GWill_Starter
 * @since   1.4.0
 */

/*
Table of Contents
1. constants — publish token, caches, exclusions
2. install — pre-cache the offline shell
3. activate — purge stale cache namespaces
4. fetch strategies — SWR assets, network-first pages
5. push — show a notification for a new post
6. notificationclick — open the post (or focus an open tab)
*/

/* eslint-disable no-restricted-globals */
'use strict';

// ── 1. constants ──────────────────────────────────────────────────────
const VERSION   = 'gwill-starter-@PUBLISH@';
const SHELL     = `${self.location.origin}/`;
const ASSET_CACHE = `gwill-assets-${VERSION}`;
const PAGE_CACHE  = `gwill-pages-${VERSION}`;

// Never intercept these.
const EXCLUDE = [
	/wp-admin/,
	/wp-login\.php/,
	/wp-json/,
	/xmlrpc\.php/,
	/feed/,
	/uploads\//,
	/[?&](preview|nocache|action|noamp)=/,
];

// ── 2. install — pre-cache the offline shell ──────────────────────────
self.addEventListener( 'install', function ( event ) {
	event.waitUntil(
		caches.open( ASSET_CACHE )
			.then( function ( cache ) {
				// The offline shell only. Nothing dynamic precached.
				return cache.add( SHELL );
			} )
			.then( function () {
				return self.skipWaiting();
			} )
	);
} );

// ── 3. activate — purge stale cache namespaces ────────────────────────
self.addEventListener( 'activate', function ( event ) {
	event.waitUntil(
		caches.keys().then( function ( keys ) {
			return Promise.all(
				keys.filter( function ( k ) {
					return k !== ASSET_CACHE && k !== PAGE_CACHE;
				} ).map( function ( k ) {
					return caches.delete( k );
				} )
			).then( function () {
				return self.clients.claim();
			} );
		} )
	);
} );

// ── 4. fetch strategies ───────────────────────────────────────────────
self.addEventListener( 'fetch', function ( event ) {
	const req = event.request;

	// Only GET, same-origin.
	if ( req.method !== 'GET' || new URL( req.url ).origin !== self.location.origin ) {
		return;
	}
	if ( EXCLUDE.some( function ( re ) { return re.test( req.url ); } ) ) {
		return;
	}

	// Navigations: network-first, cached shell when offline.
	if ( req.mode === 'navigate' ) {
		event.respondWith(
			fetch( req ).catch( function () {
				return caches.match( SHELL ).then( function ( cached ) {
					return cached || Response.error();
				} );
			} )
		);
		return;
	}

	// Static assets: stale-while-revalidate.
	if ( /\.(?:css|js|mjs|woff2?|ttf|png|jpe?g|webp|gif|svg|ico)$/i.test( new URL( req.url ).pathname ) ) {
		event.respondWith(
			caches.open( ASSET_CACHE ).then( function ( cache ) {
				return cache.match( req ).then( function ( cached ) {
					const network = fetch( req, { cache: 'no-cache' } ).then( function ( res ) {
						if ( res && res.ok ) {
							cache.put( req, res.clone() );
						}
						return res;
					} ).catch( function () {
						// Offline + never cached -> hard error (browser default).
					} );
					return cached || network;
				} );
			} )
		);
	}
} );

// ── 5. push — show a notification for a new post ──────────────────────
self.addEventListener( 'push', function ( event ) {
	var data = null;
	try {
		data = event.data ? event.data.json() : null;
	} catch ( e ) { /* non-JSON payload */ }
	if ( ! data || ! data.title ) {
		return;
	}
	var opts = {
		body: data.body || '',
		icon: data.icon || '',
		badge: data.badge || '',
		data: { url: data.url || self.location.origin, ts: event.timeStamp },
		renotify: true,
		tag: 'gwill-post-' + ( data.url || '' ).replace( /[^a-z0-9_-]/gi, '-' ),
		requireInteraction: false,
		vibrate: [ 120, 80, 120 ]
	};
	event.waitUntil( self.registration.showNotification( data.title, opts ) );
} );

// ── 6. notificationclick — open the post (or focus an open tab) ───────
self.addEventListener( 'notificationclick', function ( event ) {
	event.notification.close();
	var target = ( event.notification.data && event.notification.data.url ) || self.location.origin;
	event.waitUntil(
		self.clients.matchAll( { type: 'window', includeUncontrolled: true } ).then( function ( clients ) {
			for ( var i = 0; i < clients.length; i++ ) {
				if ( 'focus' in clients[ i ] ) {
					return clients[ i ].focus().then( function () {
						clients[ i ].navigate( target );
					} );
				}
			}
			if ( clients.length && 'navigate' in clients[ 0 ] && 'focus' in clients[ 0 ] ) {
				return clients[ 0 ].navigate( target );
			}
			return self.clients.openWindow( target );
		} )
	);
} );

self.addEventListener( 'notificationclose', function ( event ) {
	event.notification.close();
} );
