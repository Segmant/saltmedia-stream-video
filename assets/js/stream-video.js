/**
 * Salt Media Stream Video: plays Cloudflare Stream HLS in [data-sm-stream] elements.
 *
 * Every video autoplays muted with no player controls:
 * - It starts when scrolled into view and pauses when out of view; nothing
 *   downloads before it is in view.
 * - hls.js (bundled) is loaded on first use, in browsers that can run it;
 *   otherwise the browser's native HLS is used (iPhone/Safari); otherwise the
 *   poster stays.
 * - Quality is capped per Elementor device ("Max quality"), or fitted to the
 *   player size when set to Auto.
 * - Visitors with reduced motion or data saver on get the poster only, as do
 *   phones when "Play on mobile" is off.
 *
 * Each element gets data-sm-state (loading, playing, paused, native, poster-only, error)
 * to make debugging easy.
 */
( function () {
	'use strict';

	var config = window.smStreamVideo || {};
	var hlsPromise = null;
	var DEFAULT_ORDER = [ 'widescreen', 'desktop', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile' ];

	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var saveData = !! ( navigator.connection && navigator.connection.saveData );

	function loadHls() {
		if ( window.Hls ) {
			return Promise.resolve( window.Hls );
		}
		if ( ! hlsPromise ) {
			hlsPromise = new Promise( function ( resolve, reject ) {
				var s = document.createElement( 'script' );
				s.src = config.hls;
				s.async = true;
				s.onload = function () { resolve( window.Hls ); };
				s.onerror = reject;
				document.head.appendChild( s );
			} );
		}
		return hlsPromise;
	}

	function deviceMode() {
		var ef = window.elementorFrontend;
		try {
			if ( ef && ef.getCurrentDeviceMode ) {
				var mode = ef.getCurrentDeviceMode();
				if ( mode ) {
					return mode;
				}
			}
		} catch ( e ) {}
		var w = window.innerWidth;
		return w <= 767 ? 'mobile' : ( w <= 1024 ? 'tablet' : 'desktop' );
	}

	function deviceOrder() {
		var ef = window.elementorFrontend;
		try {
			if ( ef && ef.breakpoints && ef.breakpoints.getActiveBreakpointsList ) {
				var list = ef.breakpoints.getActiveBreakpointsList( { largeToSmall: true, withDesktop: true } );
				if ( list && list.length ) {
					return list;
				}
			}
		} catch ( e ) {}
		return DEFAULT_ORDER;
	}

	/** Resolves an Elementor responsive value: empty means "same as the next larger screen". */
	function resolveQuality( q ) {
		q = q || {};
		var device = deviceMode();
		if ( 'widescreen' === device ) {
			return q.widescreen || q.desktop || 'auto';
		}
		var order = deviceOrder();
		var i = order.indexOf( device );
		var desktop = order.indexOf( 'desktop' );
		if ( i < 0 || desktop < 0 ) {
			return q.desktop || 'auto';
		}
		for ( var j = i; j >= desktop; j-- ) {
			if ( q[ order[ j ] ] ) {
				return q[ order[ j ] ];
			}
		}
		return 'auto';
	}

	function isMobile() {
		var d = deviceMode();
		return 'mobile' === d || 'mobile_extra' === d;
	}

	/** Index of the best level at or below the cap; the lowest level if all are above it. */
	function cappedLevel( levels, cap ) {
		var best = -1;
		var lowest = 0;
		for ( var i = 0; i < levels.length; i++ ) {
			var h = levels[ i ].height || 0;
			if ( h && h < ( levels[ lowest ].height || Infinity ) ) {
				lowest = i;
			}
			if ( h && h <= cap && ( best < 0 || h > levels[ best ].height ) ) {
				best = i;
			}
		}
		return best >= 0 ? best : lowest;
	}

	function setState( el, state ) {
		el.setAttribute( 'data-sm-state', state );
	}

	function attach( el ) {
		if ( el.__smStream ) {
			return;
		}
		el.__smStream = true;

		var opts;
		try {
			opts = JSON.parse( el.getAttribute( 'data-sm-stream' ) || '{}' );
		} catch ( e ) {
			return;
		}
		var video = el.querySelector( 'video' );
		if ( ! video || ! opts.src ) {
			return;
		}

		if ( reduceMotion || saveData || ( isMobile() && ! opts.mobile ) ) {
			setState( el, 'poster-only' );
			return;
		}

		video.muted = true;
		video.setAttribute( 'muted', '' );
		video.controls = false;
		video.loop = !! opts.loop;

		var ready = null;

		function setup() {
			if ( ready ) {
				return ready;
			}
			setState( el, 'loading' );

			if ( 'hls' !== opts.kind ) {
				video.src = opts.src;
				ready = Promise.resolve( true );
				return ready;
			}

			var hasMse = !! ( window.ManagedMediaSource || window.MediaSource );

			ready = ( hasMse ? loadHls().catch( function () { return null; } ) : Promise.resolve( null ) ).then( function ( Hls ) {
				var quality = resolveQuality( opts.q );
				var cap = 'auto' === quality ? 0 : parseInt( quality, 10 ) || 0;

				if ( Hls && Hls.isSupported() ) {
					var hls = new Hls( {
						capLevelToPlayerSize: ! cap,
						// Loading starts only after the quality cap is applied (below).
						autoStartLoad: false,
						// Short loops replay from memory instead of downloading again.
						backBufferLength: opts.loop ? Infinity : 30,
						maxBufferLength: 12
					} );
					hls.on( Hls.Events.MANIFEST_PARSED, function () {
						if ( cap ) {
							hls.autoLevelCapping = cappedLevel( hls.levels, cap );
						}
						hls.startLoad();
					} );
					hls.on( Hls.Events.ERROR, function ( event, data ) {
						if ( ! data.fatal ) {
							return;
						}
						if ( data.type === Hls.ErrorTypes.NETWORK_ERROR ) {
							hls.startLoad();
						} else if ( data.type === Hls.ErrorTypes.MEDIA_ERROR ) {
							hls.recoverMediaError();
						} else {
							hls.destroy();
							setState( el, 'error' );
						}
					} );
					hls.loadSource( opts.src );
					hls.attachMedia( video );
					return true;
				}

				if ( video.canPlayType( 'application/vnd.apple.mpegurl' ) ) {
					// Native HLS (iPhone, Safari): the browser picks the quality.
					video.src = opts.src;
					setState( el, 'native' );
					return true;
				}

				setState( el, 'poster-only' );
				return false;
			} );
			return ready;
		}

		video.addEventListener( 'playing', function () { setState( el, 'playing' ); } );
		video.addEventListener( 'pause', function () { setState( el, 'paused' ); } );

		observe( el, function ( visible ) {
			if ( visible ) {
				setup().then( function ( ok ) {
					if ( ! ok ) {
						return;
					}
					var p = video.play();
					if ( p && p.catch ) {
						p.catch( function () {} );
					}
				} );
			} else if ( ready && ! video.paused ) {
				video.pause();
			}
		} );
	}

	function observe( el, callback ) {
		if ( ! ( 'IntersectionObserver' in window ) ) {
			callback( true );
			return;
		}
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				callback( entry.isIntersecting );
			} );
		}, { rootMargin: '200px 0px' } );
		io.observe( el );
	}

	function scan( root ) {
		if ( ! root || ! root.querySelectorAll ) {
			return;
		}
		if ( root.matches && root.matches( '[data-sm-stream]' ) ) {
			attach( root );
		}
		root.querySelectorAll( '[data-sm-stream]' ).forEach( attach );
	}

	function start() {
		scan( document );

		// Videos added later (loop "load more", popups, the Elementor editor).
		if ( 'MutationObserver' in window ) {
			new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( m ) {
					m.addedNodes.forEach( function ( node ) {
						if ( 1 === node.nodeType ) {
							scan( node );
						}
					} );
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
