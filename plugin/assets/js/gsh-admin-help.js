/*
 * Shortcode-Generator auf dem Tab „Shortcodes & Hilfe“ (Einstellungen → Schul-Terminplan).
 * Baut aus Ansicht, Gruppe, Kategorien und Optionen den fertigen Shortcode.
 */
( function () {
	'use strict';

	var form = document.getElementById( 'gtp-adm-gen' );
	if ( ! form ) {
		return;
	}
	var out = document.getElementById( 'gtp-gen-out' );
	var copyBtn = document.getElementById( 'gtp-gen-copy' );
	var status = document.getElementById( 'gtp-gen-copied' );

	function clean( value ) {
		// Anführungszeichen und eckige Klammern würden den Shortcode zerbrechen.
		return String( value ).replace( /["\[\]]/g, '' ).trim();
	}

	function currentTag() {
		var checked = form.querySelector( 'input[name="gtp_gen_tag"]:checked' );
		return checked ? checked.value : 'gsh_termine';
	}

	function build() {
		var tag = currentTag();
		var parts = [ tag ];

		Array.prototype.forEach.call( form.querySelectorAll( '[data-for]' ), function ( row ) {
			row.hidden = row.getAttribute( 'data-for' ).split( ' ' ).indexOf( tag ) === -1;
		} );

		Array.prototype.forEach.call( form.querySelectorAll( '[data-attr]' ), function ( el ) {
			var row = el.closest( '[data-for]' );
			if ( row && row.hidden ) {
				return;
			}
			var value = el.type === 'checkbox' ? ( el.checked ? el.getAttribute( 'data-value' ) : '' ) : clean( el.value );
			if ( value === '' || value === el.getAttribute( 'data-default' ) ) {
				return;
			}
			parts.push( el.getAttribute( 'data-attr' ) + '="' + value + '"' );
		} );

		if ( tag !== 'gsh_terminplan' ) {
			[ [ 'only', 'kategorien' ], [ 'also', 'auch_kategorien' ] ].forEach( function ( pair ) {
				var values = Array.prototype.map.call(
					form.querySelectorAll( 'input[data-cat="' + pair[ 0 ] + '"]:checked' ),
					function ( cb ) {
						return clean( cb.value ).replace( /,/g, ' ' );
					}
				);
				if ( values.length ) {
					parts.push( pair[ 1 ] + '="' + values.join( ',' ) + '"' );
				}
			} );
		}

		out.value = '[' + parts.join( ' ' ) + ']';
		status.textContent = '';
	}

	function copy() {
		out.select();
		var done = function () {
			status.textContent = 'Kopiert – jetzt in einen Text-Block einfügen.';
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( out.value ).then( done, function () {
				document.execCommand( 'copy' );
				done();
			} );
		} else {
			document.execCommand( 'copy' );
			done();
		}
	}

	form.addEventListener( 'input', build );
	form.addEventListener( 'change', build );
	copyBtn.addEventListener( 'click', copy );
	build();
}() );
