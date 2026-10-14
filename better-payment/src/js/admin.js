/**
 * Front-end helpers shared by the user-dashboard templates.
 *
 * This file used to drive the PHP admin: settings tabs and save, the
 * transactions list (filters, dropdowns, import/export, delete,
 * mark-as-completed), the onboarding progress bar and the quick-start video.
 * That admin is React now and none of its markup renders anywhere, so only what
 * live templates still use remains:
 *  - copy-to-clipboard on transaction ids and emails (the user-dashboard
 *    templates, the rows bp-dashboard-pagination.js builds, Pro's subscriptions tab);
 *  - the global toastr options (front-end payment toasts go through them).
 *
 * The jQuery UI datepicker on `.bp-datepicker` belonged to Pro's analytics-reports
 * tab and moved to Pro (src/js/user-dashboard.js).
 *
 * Enqueued on every front-end request as `bp-admin-settings` (Admin\Menu).
 */
( function ( $ ) {
	$( document ).ready( function () {
		'use strict';

		// Copy Transaction Id
		$( document ).on( 'click', '.bp-copy-clipboard', bpTransactionIdCopy );

		function bpTransactionIdCopy() {
			let bpTxnCounter = $( this ).attr( 'data-bp_txn_counter' );
			let node = 'bp_copy_clipboard_input_' + bpTxnCounter;
			let nodeInfo = 'bp_copy_clipboard_info_' + bpTxnCounter;

			node = document.getElementById( node );
			if ( document.body.createTextRange ) {
				const range = document.body.createTextRange();
				range.moveToElementText( node );
				range.select();

				document.execCommand( 'copy' );
			} else if ( window.getSelection ) {
				const selection = window.getSelection();
				const range = document.createRange();
				range.selectNodeContents( node );
				selection.removeAllRanges();
				selection.addRange( range );

				document.execCommand( 'copy' );
				selection.removeAllRanges();
			} else {
				console.warn(
					'Could not select text in node: Unsupported browser.'
				);
			}
			$( '.bp-copy-clipboard' )
				.attr( 'title', 'Copy' )
				.css( 'color', '#2a3256' );
			$( this ).attr( 'title', 'Copied!' ).css( 'color', '#6e58f7' );
			$( '#' + nodeInfo )
				.removeClass( 'is-hidden' )
				.fadeIn()
				.fadeOut( 3000 );
		}

		// Copy Email
		$( document ).on( 'click', '.bp-email-copy-clipboard', bpEmailCopy );

		function bpEmailCopy() {
			let bpTxnCounter = $( this ).attr( 'data-bp_txn_counter' );
			let node = 'bp_email_copy_clipboard_input_' + bpTxnCounter;
			let nodeInfo = 'bp_email_copy_clipboard_info_' + bpTxnCounter;

			node = document.getElementById( node );

			if ( document.body.createTextRange ) {
				const range = document.body.createTextRange();
				range.moveToElementText( node );
				range.select();

				document.execCommand( 'copy' );
			} else if ( window.getSelection ) {
				const selection = window.getSelection();
				const range = document.createRange();
				range.selectNodeContents( node );
				selection.removeAllRanges();
				selection.addRange( range );

				document.execCommand( 'copy' );
				selection.removeAllRanges();
			} else {
				console.warn(
					'Could not select text in node: Unsupported browser.'
				);
			}
			$( '.bp-email-copy-clipboard' )
				.attr( 'title', 'Copy' )
				.css( 'color', '#2a3256' );
			$( this ).attr( 'title', 'Copied!' ).css( 'color', '#6e58f7' );
			$( '#' + nodeInfo )
				.removeClass( 'is-hidden' )
				.fadeIn()
				.fadeOut( 3000 );
		}

		//Helper Functions Starts
		function toasterOptions() {
			toastr.options = {
				timeOut: '2000',
				toastClass: 'font-size-md',
				positionClass: 'toast-top-center',
				showMethod: 'slideDown',
				hideMethod: 'slideUp',
			};
		}
		toasterOptions();
		//Helper Functions Ends
	} );
} )( jQuery );
