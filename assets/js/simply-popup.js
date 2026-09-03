( function () {

	// ── Cookie helpers ────────────────────────────────────────────────────────

	function getCookie( name ) {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + name + '=([^;]*)' ) );
		return match ? match[1] : null;
	}

	function setCookie( name, days ) {
		var expires = '';
		if ( days ) {
			var d = new Date();
			d.setTime( d.getTime() + ( parseInt( days, 10 ) * 24 * 60 * 60 * 1000 ) );
			expires = '; expires=' + d.toUTCString();
		}
		// days === null → session cookie (no expires)
		document.cookie = name + '=1' + expires + '; path=/; SameSite=Lax';
	}

	// ── Modal popup ───────────────────────────────────────────────────────────

	var popup = document.getElementById( 'spu-popup' );
	if ( popup ) {
		var cookieSetting = popup.dataset.cookie || 'always';
		var cookieName    = 'spu_dismissed';

		if ( cookieSetting === 'always' || ! getCookie( cookieName ) ) {

			function openPopup() {
				popup.removeAttribute( 'hidden' );
				popup.offsetHeight; // force reflow so transition fires
				popup.classList.add( 'is-visible' );
				document.body.style.overflow = 'hidden';
			}

			function closePopup() {
				popup.classList.remove( 'is-visible' );
				document.body.style.overflow = '';
				setTimeout( function () {
					popup.setAttribute( 'hidden', '' );
				}, 400 );

				if ( cookieSetting === 'session' ) {
					setCookie( cookieName, null );
				} else if ( cookieSetting === '7' || cookieSetting === '30' ) {
					setCookie( cookieName, cookieSetting );
				}
			}

			popup.querySelectorAll( '.spu-close' ).forEach( function ( el ) {
				el.addEventListener( 'click', closePopup );
				el.addEventListener( 'keydown', function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); closePopup(); }
				} );
			} );

			document.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) closePopup();
			} );

			setTimeout( openPopup, 1000 );
		}
	}

	// ── Sticky bar ────────────────────────────────────────────────────────────

	var sticky = document.getElementById( 'spu-sticky' );
	if ( sticky ) {
		var stickyCookieSetting = sticky.dataset.cookie || 'always';
		var stickyCookieName    = 'spu_sticky_dismissed';

		if ( stickyCookieSetting === 'always' || ! getCookie( stickyCookieName ) ) {
			sticky.removeAttribute( 'hidden' );

			sticky.querySelectorAll( '.spu-close' ).forEach( function ( el ) {
				el.addEventListener( 'click', function () {
					sticky.setAttribute( 'hidden', '' );
					if ( stickyCookieSetting === 'session' ) {
						setCookie( stickyCookieName, null );
					} else if ( stickyCookieSetting === '7' || stickyCookieSetting === '30' ) {
						setCookie( stickyCookieName, stickyCookieSetting );
					}
				} );
				el.addEventListener( 'keydown', function ( e ) {
					if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); el.click(); }
				} );
			} );
		}
	}

} )();
