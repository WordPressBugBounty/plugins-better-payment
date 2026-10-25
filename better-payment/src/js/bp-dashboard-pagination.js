/**
 * Better Payment – User Dashboard Pagination
 *
 * Shared script for Transactions and Subscriptions tabs.
 * Works for both Elementor widget and Gutenberg block frontend renders.
 *
 * Security  : every REST request is authenticated via the X-WP-Nonce header.
 * Config    : reads restUrl + nonce from window.betterPaymentUserDash (localized by PHP).
 * Data      : reads per-table config from data-bp-settings JSON attribute on the wrapper.
 * Ellipsis  : clicking "..." navigates to the midpoint of the hidden page range, allowing
 *             progressive discovery of pages without cluttering the UI.
 */
/* jshint esversion: 5 */
( function () {
	'use strict';

	/* ── Global config ───────────────────────────────────────────────────── */
	var dash = window.betterPaymentUserDash || {};
	var restUrl = dash.restUrl || '';
	var nonce = dash.nonce || '';

	/* ── Helpers ─────────────────────────────────────────────────────────── */
	function esc( s ) {
		return String( s == null ? '' : s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function formatDate( ts ) {
		if ( ! ts ) return '';
		var d = new Date( Number( ts ) * 1000 );
		var mo = [
			'Jan',
			'Feb',
			'Mar',
			'Apr',
			'May',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Oct',
			'Nov',
			'Dec',
		];
		return (
			( '0' + d.getDate() ).slice( -2 ) +
			' ' +
			mo[ d.getMonth() ] +
			', ' +
			d.getFullYear()
		);
	}

	function sourceImg( src, assetsUrl ) {
		var s = ( src || '' ).toLowerCase();
		if ( s === 'paypal' )
			return (
				'<img class="source--img" src="' +
				esc( assetsUrl + '/img/paypal.png' ) +
				'" alt="PayPal">'
			);
		if ( s === 'stripe' )
			return (
				'<img class="source--img" src="' +
				esc( assetsUrl + '/img/stripe.svg' ) +
				'" alt="Stripe">'
			);
		if ( s === 'paystack' )
			return (
				'<img class="source--img" src="' +
				esc( assetsUrl + '/img/paystack.svg' ) +
				'" alt="Paystack">'
			);
		return '';
	}

	/* ── Pagination HTML ─────────────────────────────────────────────────── */
	/**
	 * Build the pagination <ul> HTML.
	 * Mirrors the admin bp_paginate_links() structure so existing plugin CSS applies.
	 * Ellipsis items are rendered as clickable <a> tags that jump to the midpoint
	 * of the hidden page range, letting users progressively discover all pages.
	 *
	 * @param {number} current  - current page (1-based)
	 * @param {number} total    - total number of pages
	 * @returns {string} HTML string
	 */
	function buildPaginationHtml( current, total ) {
		if ( total <= 1 ) return '';

		var html = '<ul>';

		// ── Prev button ───────────────────────────────────────────────────
		if ( current > 1 ) {
			html +=
				'<li><a href="#" class="pagination-previous bp--prev" aria-label="Previous">' +
				'<i class="bp-icon bp-caret-left"></i></a></li>';
		} else {
			html +=
				'<li><a class="pagination-previous bp--prev disabled" aria-disabled="true" aria-label="Previous">' +
				'<i class="bp-icon bp-caret-left"></i></a></li>';
		}

		// ── Page numbers with smart ellipsis ─────────────────────────────
		if ( total <= 7 ) {
			// Show every page when the total is small enough
			for ( var i = 1; i <= total; i++ ) {
				var ac = i === current ? ' is-current active' : '';
				html +=
					'<li><a href="#" class="pagination-link bp--page-num' +
					ac +
					'" data-page="' +
					i +
					'" aria-label="Page ' +
					i +
					'"> ' +
					i +
					' </a></li>';
			}
		} else {
			// Smart window: always show first, last, and current ± 2.
			// Render an interactive "..." between gaps.
			var rendered = {}; // tracks pages already output
			var ellipsisLeft = false; // have we output a left  "..." yet?
			var ellipsisRight = false; // have we output a right "..." yet?

			for ( var p = 1; p <= total; p++ ) {
				var inWindow =
					p === 1 ||
					p === total ||
					( p >= current - 2 && p <= current + 2 );

				if ( inWindow ) {
					var cls = p === current ? ' is-current active' : '';
					html +=
						'<li><a href="#" class="pagination-link bp--page-num' +
						cls +
						'" data-page="' +
						p +
						'" aria-label="Page ' +
						p +
						'"> ' +
						p +
						' </a></li>';
					rendered[ p ] = true;
				} else {
					// Gap between last rendered page and this one — output one "..."
					var prevRendered = p - 1;
					while ( prevRendered >= 1 && ! rendered[ prevRendered ] ) {
						prevRendered--;
					}

					var inLeftGap = p < current - 2 && ! ellipsisLeft;
					var inRightGap = p > current + 2 && ! ellipsisRight;

					if ( inLeftGap ) {
						// Midpoint of hidden range: from (last always-shown = 1+1=2) to current-3
						var leftHiddenStart = 2;
						var leftHiddenEnd = current - 3;
						var leftMid = Math.max(
							1,
							Math.round(
								( leftHiddenStart + leftHiddenEnd ) / 2
							)
						);
						html +=
							'<li><a href="#" class="pagination-link bp--ellipsis bp--page-num" data-page="' +
							leftMid +
							'" title="Jump to page ' +
							leftMid +
							'">...</a></li>';
						ellipsisLeft = true;
					} else if ( inRightGap ) {
						// Midpoint of hidden range: from current+3 to total-1
						var rightHiddenStart = current + 3;
						var rightHiddenEnd = total - 1;
						var rightMid = Math.min(
							total,
							Math.round(
								( rightHiddenStart + rightHiddenEnd ) / 2
							)
						);
						html +=
							'<li><a href="#" class="pagination-link bp--ellipsis bp--page-num" data-page="' +
							rightMid +
							'" title="Jump to page ' +
							rightMid +
							'">...</a></li>';
						ellipsisRight = true;
					}
				}
			}
		}

		// ── Next button ───────────────────────────────────────────────────
		if ( current < total ) {
			html +=
				'<li><a href="#" class="pagination-next bp--next" aria-label="Next">' +
				'<i class="bp-icon bp-caret-right"></i></a></li>';
		} else {
			html +=
				'<li><a class="pagination-next bp--next disabled" aria-disabled="true" aria-label="Next">' +
				'<i class="bp-icon bp-caret-right"></i></a></li>';
		}

		html += '</ul>';
		return html;
	}

	/* ── Row builders ────────────────────────────────────────────────────── */
	function noItemsRow( label ) {
		return (
			'<div class="flex justify-center m-5"><p class="bp-no_subscription-text">' +
			esc( label ) +
			'</p></div>'
		);
	}

	function buildTransactionRows( txns, s ) {
		if ( ! txns.length ) return noItemsRow( s.no_items_label );

		return txns
			.map( function ( t, i ) {
				var c = i + 1;
				var row =
					'<div class="better-payment-user-dashboard-table-body bp--table-body bp-min_width-1300 flex items-center justify-between gap-1">';

				if ( s.name ) {
					var imp = t.is_imported
						? '<span class="bp-icon bp-imported imported-icon"></span> '
						: '';
					row +=
						'<div class="td details user-name flex items-center gap-3"><p>' +
						imp +
						esc( t.customer_name ) +
						'</p></div>';
				}
				if ( s.email ) {
					row +=
						'<div class="td details email flex items-center gap-3"><p>' +
						'<span id="bp_email_copy_clipboard_input_' +
						c +
						'">' +
						esc( t.customer_email ) +
						'</span> ' +
						'<span id="bp_email_copy_clipboard_' +
						c +
						'" class="bp-icon bp-copy-square bp-email-copy-clipboard" data-bp_txn_counter="' +
						c +
						'" title="Copy"></span>' +
						'</p></div>';
				}
				if ( s.amount ) {
					row +=
						'<div class="td details amount flex items-center gap-3"><p>' +
						esc( t.currency ) +
						' ' +
						esc( t.amount ) +
						'</p></div>';
				}
				if ( s.payment_type ) {
					row +=
						'<div class="td details flex items-center gap-3"><p>' +
						esc( t.is_subscription ) +
						'</p></div>';
				}
				if ( s.transaction_id ) {
					row +=
						'<div class="td details transaction flex items-center gap-3">';
					if ( t.transaction_id ) {
						row +=
							'<p><span id="bp_copy_clipboard_input_' +
							c +
							'">' +
							esc( t.transaction_id ) +
							'</span> ' +
							'<span id="bp_copy_clipboard_' +
							c +
							'" class="bp-icon bp-copy-square bp-copy-clipboard" data-bp_txn_counter="' +
							c +
							'" title="Copy"></span></p>';
					}
					row += '</div>';
				}
				if ( s.source ) {
					row +=
						'<div class="td details flex justify-center gap-3">' +
						sourceImg( t.source, s.assets_url ) +
						'</div>';
				}
				if ( s.status ) {
					row +=
						'<div class="td details flex justify-center gap-3"><p data-id="' +
						t.id +
						'">' +
						'<span style="color:#fff;padding:7px 15px;border-radius:20px;background:' +
						esc( t.status_color ) +
						'">' +
						esc( t.status_label ) +
						'</span>' +
						'</p></div>';
				}
				if ( s.date ) {
					row +=
						'<div class="td details flex justify-center gap-3"><p>' +
						esc( t.payment_date ) +
						'</p></div>';
				}
				if ( s.action ) {
					row +=
						'<div class="td details flex justify-center d-none">' +
						'<a href="' +
						esc( s.view_url + '&id=' + t.id ) +
						'" class="button button--sm view-button" data-id="' +
						t.id +
						'">' +
						'<span class="bp-icon bp-view font-bold"></span></a></div>';
				}
				row += '</div>';
				return row;
			} )
			.join( '' );
	}

	/* ── Per-table init ──────────────────────────────────────────────────── */
	function initTable( wrapper ) {
		// Guard against double-init (Elementor can fire element_ready multiple times)
		if ( wrapper.dataset.bpInitialized ) return;
		wrapper.dataset.bpInitialized = '1';
		var paginationEl = wrapper.querySelector( '.bp--pagination' );
		var tbody = wrapper.querySelector( '.bp--table-tbody' );
		if ( ! tbody ) return;

		var tab = wrapper.dataset.bpTab || 'transactions';
		var totalPages = parseInt( wrapper.dataset.bpTotalPages, 10 ) || 1;
		var perPage = parseInt( wrapper.dataset.bpPerPage, 10 ) || 10;
		var curPage = 1;
		var settings = {};
		try {
			settings = JSON.parse( wrapper.dataset.bpSettings || '{}' );
		} catch ( e ) {
			/* ignore */
		}

		function buildRows( txns ) {
			if ( tab !== 'subscriptions' ) {
				return buildTransactionRows( txns, settings );
			}

			// Subscriptions are Pro-only, and Pro builds their rows: it registers its row
			// builder on window.betterPaymentUserDashRows. Lite ships no copy of it, so
			// without one there is nothing to draw and the current page stays as it is.
			var proRows =
				window.betterPaymentUserDashRows &&
				window.betterPaymentUserDashRows.subscriptions;
			if ( typeof proRows === 'function' ) {
				return proRows( txns, settings, {
					esc: esc,
					formatDate: formatDate,
					noItemsRow: noItemsRow,
				} );
			}

			return null;
		}

		function renderPagination() {
			if ( ! paginationEl ) return;
			if ( totalPages <= 1 ) {
				paginationEl.innerHTML = '';
				paginationEl.style.display = 'none';
				return;
			}
			paginationEl.style.display = '';
			paginationEl.innerHTML = buildPaginationHtml( curPage, totalPages );
		}

		function loadPage( page ) {
			if ( ! restUrl ) return;
			if ( page < 1 || page > totalPages ) return;
			tbody.style.opacity = '0.5';
			var url =
				restUrl +
				'?tab=' +
				encodeURIComponent( tab ) +
				'&page=' +
				encodeURIComponent( page ) +
				'&per_page=' +
				encodeURIComponent( perPage );

			fetch( url, {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': nonce },
			} )
				.then( function ( r ) {
					if ( ! r.ok ) throw new Error( 'HTTP ' + r.status );
					return r.json();
				} )
				.then( function ( data ) {
					var rows = buildRows( data.transactions || [] );
					if ( rows === null ) {
						tbody.style.opacity = '';
						return;
					}
					tbody.innerHTML = rows;
					curPage = data.page || page;
					totalPages = data.pages || totalPages;
					renderPagination();
					tbody.style.opacity = '';

					// Scroll table into view smoothly on page change
					wrapper.scrollIntoView( {
						behavior: 'smooth',
						block: 'nearest',
					} );
				} )
				.catch( function () {
					tbody.style.opacity = '';
				} );
		}

		// Build initial pagination from PHP-supplied data attributes
		renderPagination();

		// Event delegation — works for both the initial render and JS-rebuilt HTML
		if ( paginationEl ) {
			paginationEl.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var a = e.target.closest( 'a' );
				if ( ! a ) return;
				if (
					a.classList.contains( 'disabled' ) ||
					a.getAttribute( 'aria-disabled' ) === 'true'
				)
					return;

				if ( a.classList.contains( 'bp--prev' ) ) {
					if ( curPage > 1 ) loadPage( curPage - 1 );
				} else if ( a.classList.contains( 'bp--next' ) ) {
					if ( curPage < totalPages ) loadPage( curPage + 1 );
				} else if ( a.classList.contains( 'bp--page-num' ) ) {
					// Handles both normal page links and ellipsis jump links (both use bp--page-num)
					var p = parseInt( a.dataset.page, 10 );
					if ( p && p !== curPage ) loadPage( p );
				}
			} );
		}
	}

	/* ── Bootstrap ───────────────────────────────────────────────────────── */
	function init() {
		document
			.querySelectorAll( '.bp--table-wrapper[data-bp-tab]' )
			.forEach( initTable );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	/* ── MutationObserver — Elementor editor AJAX re-renders ─────────────── */
	// When Elementor re-renders a widget (settings change), it injects new DOM
	// into the preview. DOMContentLoaded has already fired, so we watch for the
	// new .bp--table-wrapper elements and init them when they appear.
	if ( typeof MutationObserver !== 'undefined' && document.body ) {
		var observer = new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( m ) {
				m.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) return;
					if (
						node.matches &&
						node.matches( '.bp--table-wrapper[data-bp-tab]' )
					) {
						// Elementor may re-render with fresh localization data
						var freshDash = window.betterPaymentUserDash || {};
						restUrl = freshDash.restUrl || restUrl;
						nonce = freshDash.nonce || nonce;
						initTable( node );
					}
					if ( node.querySelectorAll ) {
						node.querySelectorAll(
							'.bp--table-wrapper[data-bp-tab]'
						).forEach( function ( w ) {
							var freshDash = window.betterPaymentUserDash || {};
							restUrl = freshDash.restUrl || restUrl;
							nonce = freshDash.nonce || nonce;
							initTable( w );
						} );
					}
				} );
			} );
		} );
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	/* ── Elementor frontend hook — element_ready ─────────────────────────── */
	// Fires after each widget is rendered in the Elementor editor preview.
	// Ensures pagination is init'd even when elementorFrontend loads after this script.
	function bindElementorHook() {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/global',
				function ( $scope ) {
					if ( ! $scope || ! $scope[ 0 ] ) return;
					var freshDash = window.betterPaymentUserDash || {};
					restUrl = freshDash.restUrl || restUrl;
					nonce = freshDash.nonce || nonce;
					$scope[ 0 ]
						.querySelectorAll( '.bp--table-wrapper[data-bp-tab]' )
						.forEach( initTable );
				}
			);
		}
	}

	if ( window.elementorFrontend ) {
		bindElementorHook();
	} else {
		window.addEventListener( 'elementor/frontend/init', bindElementorHook );
	}
} )();
