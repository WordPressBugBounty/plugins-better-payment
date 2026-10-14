( function ( $ ) {
	$( document ).on(
		'change',
		'.better-payment .payment-form-layout-3 .payment-method-checkbox input',
		function () {
			if ( this.checked ) {
				$(
					'.better-payment .payment-form-layout-3 .payment-method-checkbox'
				).removeClass( 'active' );
				$( this ).parent().addClass( 'active' );

				$(
					'.better-payment .payment-form-layout-3 .payment-method-checkbox input'
				).removeAttr( 'checked' );
				$( this ).attr( 'checked', true );

				let paypalBtnClassName = 'better-payment-paypal-bt';
				let stripeBtnClassName = 'better-payment-stripe-bt';
				let paystackBtnClassName = 'better-payment-paystack-bt';
				let targetButtonClassName = $( this ).hasClass(
					'layout-payment-method-paypal'
				)
					? paypalBtnClassName
					: stripeBtnClassName;
				targetButtonClassName = $( this ).hasClass(
					'layout-payment-method-paystack'
				)
					? paystackBtnClassName
					: targetButtonClassName;

				$(
					'.better-payment .' +
						paypalBtnClassName +
						', .better-payment .' +
						stripeBtnClassName +
						', .better-payment .' +
						paystackBtnClassName
				).addClass( 'is-hidden' );
				$( '.better-payment .' + targetButtonClassName ).removeClass(
					'is-hidden'
				);
			}
		}
	);

	$( document ).on(
		'change',
		'.better-payment .payment-form-layout-1 .payment-method-checkbox input, .better-payment .payment-form-layout-2 .payment-method-checkbox input, .better-payment .payment-method-item input',
		function ( e ) {
			if ( this.checked ) {
				let paypalBtnClassName = 'better-payment-paypal-bt';
				let stripeBtnClassName = 'better-payment-stripe-bt';
				let paystackBtnClassName = 'better-payment-paystack-bt';
				let targetButtonClassName = $( this ).hasClass(
					'layout-payment-method-paypal'
				)
					? paypalBtnClassName
					: stripeBtnClassName;
				targetButtonClassName = $( this ).hasClass(
					'layout-payment-method-paystack'
				)
					? paystackBtnClassName
					: targetButtonClassName;

				$(
					'.better-payment .' +
						paypalBtnClassName +
						', .better-payment .' +
						stripeBtnClassName +
						', .better-payment .' +
						paystackBtnClassName
				).addClass( 'is-hidden' );
				$( '.better-payment .' + targetButtonClassName ).removeClass(
					'is-hidden'
				);
			}
		}
	);

	$( document ).on(
		'click',
		'.better-payment .user-dashboard-sidebar .bp--sidebar-nav',
		function ( e ) {
			e.preventDefault();
			let $this = $( this );
			let tabCommonSelector =
				'.better-payment .user-dashboard-sidebar .bp--sidebar-nav';
			let tabContentCommonSelector =
				'.better-payment .bp--tab-conetnt-wrapper';
			let tabName = $this.data( 'tab' );

			$( tabCommonSelector ).removeClass( 'active' );
			$( tabContentCommonSelector ).addClass( 'd-none' ).fadeOut();

			$this.addClass( 'active' );
			$( `.better-payment .${ tabName }-tab-wrapper` )
				.fadeIn( 500 )
				.removeClass( 'd-none' );

			// Hide sidebar on mobile
			if ( window.innerWidth < 768 ) {
				if ( $( e.target ).closest( '.bp--sidebar-nav-list' ).length ) {
					hideDashboardSidebar();
				}
			}
		}
	);

	$( '.better-payment .bp-dashboard-hamburger' ).on( 'click', function ( e ) {
		$( '.better-payment-user-dashboard-sidebar' ).fadeIn( 500 );
		$( '.better-payment .bp-overlay' ).fadeIn( 500 );
	} );

	$( '.better-payment .bp-overlay' ).on( 'click', function ( e ) {
		// Overlay click
		if ( e.target !== '.better-payment-user-dashboard-sidebar' ) {
			hideDashboardSidebar();
		}
	} );

	function hideDashboardSidebar() {
		$( '.better-payment-user-dashboard-sidebar' ).fadeOut( 500 );
		$( '.better-payment .bp-overlay' ).fadeOut();
	}

	function copyDataFunction() {
		const $transactionIDElement = $( '.bp-transaction_id' );
		const $copyButton = $( '.bp-transaction_data-copy_btn' );

		if ( $transactionIDElement.length ) {
			const transactionID = $.trim( $transactionIDElement.text() );
			navigator.clipboard.writeText( transactionID );
			$copyButton.css( 'stroke', '#6B59EE' );

			// Dynamically add or update tooltip message using jQuery
			const styleId = 'copy-tooltip-style';
			let $styleTag = $( '#' + styleId );

			if ( ! $styleTag.length ) {
				$styleTag = $( '<style>', { id: styleId } ).appendTo( 'head' );
			}

			// Clear previous rules if needed
			$styleTag.html( `
                .bp-transaction_data-copy_btn::after {
                    content: 'Copied!';
                    right: -23px;
                }
            ` );

			$copyButton.addClass( 'copied-text' );

			setTimeout( () => {
				$copyButton.removeClass( 'copied-text' );
			}, 3000 );
		}
	}

	// jQuery event delegation for dynamically bound elements
	$( document ).on( 'click', '.bp-transaction_data-copy_btn', function () {
		copyDataFunction();
	} );

	function printElementByClass( className ) {
		// Get the first element with the class
		const element = document.querySelector( '.' + className );
		if ( ! element ) return;

		// Create iframeElement
		const iframeElement = document.createElement( 'iframe' );
		iframeElement.style.display = 'none';
		document.body.appendChild( iframeElement );

		// Get styles from parent document
		const styles = Array.from( document.styleSheets )
			.map( ( styleSheet ) => {
				try {
					return Array.from( styleSheet.cssRules )
						.map( ( rule ) => rule.cssText )
						.join( '\n' );
				} catch ( e ) {
					// Cross-origin stylesheet - can't access rules
					return '';
				}
			} )
			.filter( Boolean )
			.join( '\n' );

		// Write to iframeElement document
		const doc =
			iframeElement.contentDocument ||
			iframeElement.contentWindow.document;
		doc.write( `
            <html>
                <head>
                    <title>${ document.title }</title>
                    <style>${ styles }</style>
                </head>
                <body>
                    ${ element.outerHTML }
                </body>
            </html>
        ` );
		doc.close();

		// Print and remove iframeElement
		iframeElement.contentWindow.focus();
		setTimeout( () => {
			iframeElement.contentWindow.print();
			setTimeout(
				() => document.body.removeChild( iframeElement ),
				1000
			);
		}, 300 );
	}

	$( document ).on( 'click', '.bp-print_btn', function () {
		printElementByClass( 'bp-thank_page' );
	} );
} )( jQuery );
