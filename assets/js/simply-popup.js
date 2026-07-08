( function () {

	var popup = document.getElementById( 'spu-popup' );
	if ( ! popup ) return;

	var cookieSetting = popup.dataset.cookie || 'always';
	var cookieName    = 'spu_dismissed';

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

	// ── Should we show? ───────────────────────────────────────────────────────

	if ( cookieSetting !== 'always' && getCookie( cookieName ) ) {
		return; // cookie set — don't show
	}

	// ── Open / close ──────────────────────────────────────────────────────────

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
			setCookie( cookieName, null ); // session cookie
		} else if ( cookieSetting === '7' || cookieSetting === '30' ) {
			setCookie( cookieName, cookieSetting );
		}
		// 'always' → no cookie, will show again next visit
	}

	// ── Event listeners ───────────────────────────────────────────────────────

	popup.querySelectorAll( '.spu-close' ).forEach( function ( el ) {
		el.addEventListener( 'click', closePopup );
		el.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); closePopup(); }
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) closePopup();
	} );

	// ── Show after delay ──────────────────────────────────────────────────────

	setTimeout( openPopup, 1000 );

} )();
