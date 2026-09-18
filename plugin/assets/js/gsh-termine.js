/*
 * [gsh_termine] — Progressive Enhancement für gecachte Startseiten.
 *
 * Der Server rendert absolute Daten plus „Heute/Morgen“ für seinen Tag
 * (data-today). Liefert ein Seiten-Cache das HTML an einem späteren Tag aus,
 * korrigiert dieses Skript die Markierungen und blendet abgelaufene Tage aus.
 * Ohne JS bleibt die Ausgabe vollständig und korrekt datiert.
 */
( function () {
	'use strict';

	function pad( n ) {
		return ( n < 10 ? '0' : '' ) + n;
	}
	function iso( d ) {
		return d.getFullYear() + '-' + pad( d.getMonth() + 1 ) + '-' + pad( d.getDate() );
	}

	function refresh() {
		var now = new Date();
		var today = iso( now );
		var next = new Date( now.getFullYear(), now.getMonth(), now.getDate() + 1 );
		var tomorrow = iso( next );

		var roots = document.querySelectorAll( '.gtp-up[data-today]' );
		Array.prototype.forEach.call( roots, function ( root ) {
			if ( root.getAttribute( 'data-today' ) === today ) {
				return; // Server-Stand ist aktuell.
			}

			Array.prototype.forEach.call( root.querySelectorAll( '.gtp-up-day[data-date]' ), function ( li ) {
				var date = li.getAttribute( 'data-date' );
				var until = li.getAttribute( 'data-until' ) || date;
				var rel = date === today ? 'Heute' : ( date === tomorrow ? 'Morgen' : '' );
				var relEl = li.querySelector( '.gtp-up-rel' );
				if ( relEl ) {
					relEl.textContent = rel;
				}
				li.classList.toggle( 'is-today', date === today );
				// Beendete Termine einzeln ausblenden; ein Tag bleibt nur, solange
				// dort noch etwas läuft (z. B. ein mehrtägiges Praktikum).
				var anyLeft = false;
				Array.prototype.forEach.call( li.querySelectorAll( '.gtp-up-ev[data-end]' ), function ( ev ) {
					ev.hidden = ev.getAttribute( 'data-end' ) < today;
					anyLeft = anyLeft || ! ev.hidden;
				} );
				li.hidden = until < today || ! anyLeft;
			} );

			Array.prototype.forEach.call( root.querySelectorAll( '.gtp-up-cell[data-date], .gtp-mo-cell[data-date]' ), function ( cell ) {
				var date = cell.getAttribute( 'data-date' );
				cell.classList.toggle( 'is-past', date < today );
				cell.classList.toggle( 'is-today', date === today );
			} );

			// Wochen-Überschriften ohne sichtbare Einträge mit ausblenden.
			Array.prototype.forEach.call( root.querySelectorAll( '.gtp-up-days' ), function ( list ) {
				var visible = list.querySelector( '.gtp-up-day:not([hidden]), .gtp-up-hol' );
				var head = list.previousElementSibling;
				list.hidden = ! visible;
				if ( head && head.classList.contains( 'gtp-up-wk' ) ) {
					head.hidden = ! visible;
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', refresh );
	} else {
		refresh();
	}
}() );
