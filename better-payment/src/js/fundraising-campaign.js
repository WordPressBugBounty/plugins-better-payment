( function ( $ ) {
	// Function to initialize the slider
	function bpFundraisingCampaignSlider() {
		// Check if the necessary elements exist
		if (
			$( '.better-payment .img-wrapper img' ).length &&
			$( '#bp-bgimage' ).length
		) {
			const galleryImages = $( '.better-payment .img-wrapper img' );
			const bgImage = $( '#bp-bgimage' );
			const imageWrappers = $( '.better-payment .img-wrapper' );
			const overlays = $( '.better-payment .image_overlay' );
			const imageUrls = galleryImages
				.map( function () {
					return $( this ).data( 'bg' );
				} )
				.get();

			// Only proceed if we have image URLs
			if ( imageUrls.length > 0 ) {
				let currentIndex = 0;
				let sliderInterval;

				const updateBackground = ( index ) => {
					if ( ! imageUrls[ index ] ) return;

					// Set background image
					bgImage.css(
						'background-image',
						`url('${ imageUrls[ index ] }')`
					);

					// Update overlays and borders
					imageWrappers.each( function ( i ) {
						const $wrapper = $( this );
						const $overlay = $wrapper.find( '.image_overlay' );

						if ( i === index ) {
							$overlay.hide();
							$wrapper.css( 'border', '2px solid #FBBF24' );
						} else {
							$overlay.show();
							$wrapper.css( 'border', 'none' );
						}
					} );

					// Toggle active class
					galleryImages
						.removeClass( 'active' )
						.eq( index )
						.addClass( 'active' );
				};

				const startSlider = () => {
					clearInterval( sliderInterval ); // Clear any existing interval
					sliderInterval = setInterval( () => {
						currentIndex = ( currentIndex + 1 ) % imageUrls.length;
						updateBackground( currentIndex );
					}, 5000 );
				};

				// Manual click handler
				overlays.each( function ( index ) {
					$( this )
						.off( 'click' )
						.on( 'click', function () {
							clearInterval( sliderInterval );
							currentIndex = index;
							updateBackground( index );
							startSlider();
						} );
				} );

				updateBackground( currentIndex );
				startSlider();

				return true;
			}
		}
		return false;
	}

	// Initialize on document ready (for frontend)
	$( document ).ready( function () {
		$( document ).on(
			'click',
			'.better-payment .bp-tab_button',
			function () {
				// Remove active class from all buttons
				$( '.bp-tab_button' ).removeClass( 'bp-active' );

				// Add active class to the clicked button
				$( this ).addClass( 'bp-active' );

				// Get the tab to show
				var tabToShow = $( this ).data( 'tab' );

				// Hide all tab panes
				$( '.bp-tab_pane' ).hide();

				// Show the selected tab pane
				$( '#' + tabToShow ).show();
			}
		);

		$( document ).on(
			'click',
			'.better-payment .bp-payment_item .bp-option-amount',
			function () {
				resetDonateButtonHref();
				var selectedValue = $( this ).val();

				// Set the value in the input field
				$( '.other_amount' ).val( selectedValue );

				// Manage active_border class
				$( 'label[for^="amount_"]' ).removeClass( 'active_border' ); // remove from all
				$( 'label[for="' + $( this ).attr( 'id' ) + '"]' ).addClass(
					'active_border'
				); // add to selected
			}
		);

		$( document ).on(
			'focus',
			'.better-payment .bp-payment_form .other_amount',
			function () {
				resetDonateButtonHref();
				$( '.bp-option-amount' ).prop( 'checked', false );
				$( 'label[for^="amount_"]' ).removeClass( 'active_border' );
			}
		);

		// Reset donate button href when user selects radio or types custom amount
		function resetDonateButtonHref() {
			var $button = $( '.bp-donate_btn' );
			var baseHref = $button.attr( 'href' )?.split( '?' )[ 0 ];
			$button.attr( 'href', baseHref );
			$button.data( 'clicked', false ); // allow query string to be added again on next clickensure base is saved for future
		}

		$( document ).on(
			'change',
			'.better-payment .bp-donate_amounts .bp-option-amount',
			function () {
				resetDonateButtonHref();

				// Remove active class from all labels
				$( '.bp-donate_amounts .bp-radio_label' ).removeClass(
					'bp-radio_active'
				);

				// Add active class to the label associated with the selected radio
				$( this )
					.closest( '.bp-radio_label' )
					.addClass( 'bp-radio_active' );

				// Set the value in the number input field
				var amount = $( this ).data( 'value' );
				$( '.campaign-custom-amount' ).val( amount );
			}
		);

		$( document ).on(
			'focus',
			'.better-payment .campaign-custom-amount',
			function () {
				resetDonateButtonHref();

				// Uncheck all radio buttons
				$( '.bp-option-amount' ).prop( 'checked', false );

				// Remove active class from all labels
				$( '.bp-radio_label' ).removeClass( 'bp-radio_active' );
			}
		);

		// Campaign builder: sync preset amount selection into custom input field
		$( document ).on(
			'change',
			'.bp-campaign .bp-donate_amounts .bp-option-amount',
			function () {
				var amount = $( this ).val();
				$( this )
					.closest( '.bp-campaign-donate' )
					.find( '.campaign-custom-amount' )
					.val( amount );
			}
		);

		// Campaign builder: clear radio selection when user types a custom amount
		$( document ).on(
			'focus',
			'.bp-campaign .campaign-custom-amount',
			function () {
				$( this )
					.closest( '.bp-campaign-donate' )
					.find( '.bp-option-amount' )
					.prop( 'checked', false );
			}
		);

		// Campaign builder: validate minimum donation amount before navigating
		$( document ).on(
			'click',
			'.bp-campaign .bp-donate_btn',
			function ( e ) {
				var $campaign = $( this ).closest( '.bp-campaign' );
				var $notice = $campaign.find( '.bp-min-donation-notice' );

				if ( ! $notice.length ) return;

				var minAmount = parseFloat( $notice.data( 'min' ) ) || 0;
				if ( minAmount <= 0 ) return;

				var customVal = parseFloat(
					$campaign.find( '.campaign-custom-amount' ).val()
				);
				var radioVal = parseFloat(
					$campaign.find( '.bp-option-amount:checked' ).val()
				);
				var enteredAmount =
					! isNaN( customVal ) && customVal > 0
						? customVal
						: ! isNaN( radioVal ) && radioVal > 0
						? radioVal
						: 0;

				if ( enteredAmount < minAmount ) {
					e.preventDefault();
					$notice.addClass( 'is-error' );
				} else {
					$notice.removeClass( 'is-error' );
				}
			}
		);

		// Clear error state when user changes amount selection
		$( document ).on(
			'change',
			'.bp-campaign .bp-option-amount, input.bp-campaign .campaign-custom-amount',
			function () {
				$( this )
					.closest( '.bp-campaign' )
					.find( '.bp-min-donation-notice' )
					.removeClass( 'is-error' );
			}
		);
		$( document ).on(
			'input',
			'.bp-campaign .campaign-custom-amount',
			function () {
				$( this )
					.closest( '.bp-campaign' )
					.find( '.bp-min-donation-notice' )
					.removeClass( 'is-error' );
			}
		);

		$( document ).on(
			'click',
			'.better-payment .bp-donate_btn',
			function ( e ) {
				var $button = $( this );
				var selectedRadio = $(
					'.better-payment .bp-option-amount:checked'
				).val();
				var customAmount = $(
					'.better-payment .campaign-custom-amount'
				).val();
				var amount = '';
				var bp_campaign_id =
					$( '.better_payment_campaign_id' ).val() || '';
				var bp_campaign_currency =
					$( '.better_payment_campaign_currency' ).val() || '';

				// Use custom input if provided, else fallback to radio
				if ( customAmount ) {
					amount = customAmount;
				} else if ( selectedRadio ) {
					amount = selectedRadio;
				}

				// Append or update query parameter
				if ( amount || bp_campaign_id || bp_campaign_currency ) {
					var originalUrl = new URL(
						$button.attr( 'href' ),
						window.location.origin
					);

					if ( bp_campaign_currency ) {
						originalUrl.searchParams.set(
							'campaign_currency',
							bp_campaign_currency
						);
					}
					if ( amount ) {
						originalUrl.searchParams.set(
							'payment_amount',
							amount
						);
					}
					if ( bp_campaign_id ) {
						originalUrl.searchParams.set(
							'campaign_id',
							bp_campaign_id
						);
					}
					$button.attr( 'href', originalUrl.toString() );
				}
			}
		);

		// Try to initialize slider
		if ( ! bpFundraisingCampaignSlider() ) {
			bpFundraisingCampaignSlider();
		}
	} );

	// Initialize for Elementor editor
	$( window ).on( 'elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/fundraising-campaign.default',
			function ( $scope ) {
				// Try to initialize slider with a delay to ensure elements are rendered
				bpFundraisingCampaignSlider();
			}
		);
	} );
} )( jQuery );
