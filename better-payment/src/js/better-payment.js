import {
	isCheckoutLocked,
	lockCheckoutButton,
	releaseCheckoutButton,
	releaseOnPageRestore,
	redirectToStripeCheckout,
	showRedirecting,
} from './_checkout-lock';

( function ( $ ) {
	'use strict';

	const formsFieldTypeBPName = 'payment_amount';
	const { custom_texts } = betterPayment || {};
	const redirectingText = custom_texts?.redirecting || 'Redirecting';
	const redirectingHtml = `${ redirectingText } <span class="elementor-control-spinner">&nbsp;<i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>`;

	// Back from the gateway restores this page from the back/forward cache with
	// its payment button still locked mid-redirect; unlock it so the visitor can pay.
	window.addEventListener( 'pageshow', function ( event ) {
		releaseOnPageRestore( event, document );
	} );
	// let fieldText = (custom_texts?.field || 'field').toLowerCase();
	// let requiredText = (custom_texts?.required || 'required').toLowerCase();
	let fieldIsRequiredText =
		custom_texts?.field_is_required || 'field is required';
	let businessEmailIsRequiredText =
		custom_texts?.business_email_is_required ||
		'Business Email is required';
	let paymentAmountFieldIsRequiredText =
		custom_texts?.payment_amount_field_is_required ||
		'Payment Amount field is required';
	let minimumAmountIsOneText =
		custom_texts?.minimum_amount_is_one || 'Minimum amount is 1';
	let somethingWentWrongText =
		custom_texts?.something_went_wrong || 'Something went wrong';

	$( document ).on( 'click', '.better-payment-paypal-bt', function ( e ) {
		const id = $( '#' + this.form.id ),
			$form = $( id ),
			paymentInfo = $( this ).data( 'paypal-info' ),
			fields = $form.serializeObject();

		let data = validateBetterPaymentForm( paymentInfo, fields, this.form );

		// $(this).html('Submitting <span class="elementor-control-spinner">&nbsp;<i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>');
		$( this ).attr( 'disabled', true );

		if ( typeof data.status !== 'undefined' && ! data.status ) {
			e.preventDefault();
			toastr.error(
				data.errors.length
					? data.errors[ 0 ]
					: `${ somethingWentWrongText }!`
			);
			this.disabled = false;

			return false;
		}

		// $(this).html('Proceed to Payment');
		$( this ).attr( 'disabled', false );

		$( this ).html(
			`${ redirectingText } <span class="elementor-control-spinner">&nbsp;<i class="eicon-spinner eicon-animation-spin"></i>&nbsp;</span>`
		);
	} );

	$( document ).on( 'click', '.better-payment-stripe-bt', function ( e ) {
		e.preventDefault();

		// A checkout is already in flight for this button.
		if ( isCheckoutLocked( this ) ) {
			return false;
		}

		const id = $( '#' + this.form.id ),
			$form = $( id ),
			setting_data = $form.data( 'better-payment' ),
			fields = $form.serializeObject();

		let data = validateBetterPaymentForm( '', fields, this.form, false );

		if ( typeof data.status !== 'undefined' && ! data.status ) {
			e.preventDefault();
			toastr.error(
				data.errors.length
					? data.errors[ 0 ]
					: `${ somethingWentWrongText }!`
			);
			this.disabled = false;

			return false;
		}

		// One checkout per click: a second request would record a second order.
		if ( ! lockCheckoutButton( this ) ) {
			return false;
		}

		const button = this;

		$.ajax( {
			url: betterPayment.ajax_url,
			type: 'post',
			data: {
				action: 'better_payment_stripe_get_token',
				security: betterPayment.nonce,
				fields: fields,
				setting_data: setting_data,
			},

			success: function ( response ) {
				if ( typeof response?.data?.stripe_data === 'undefined' ) {
					toastr.error(
						response?.data || `${ somethingWentWrongText }!`
					);
					releaseCheckoutButton( button );
					return;
				}

				// Stays locked: the page is about to leave for Stripe.
				showRedirecting( button, redirectingHtml );

				redirectToStripeCheckout(
					window.Stripe,
					response.data.stripe_public_key,
					response.data.stripe_data
				).then( function ( result ) {
					if ( result?.error ) {
						toastr.error(
							result.error.message ||
								`${ somethingWentWrongText }!`
						);
						releaseCheckoutButton( button );
					}
				} );
			},
			error: function () {
				console.log( 'Something went wrong!' );
				releaseCheckoutButton( button );
			},
		} );
	} );

	$( document ).on( 'click', '.better-payment-paystack-bt', function ( e ) {
		e.preventDefault();

		// A checkout is already in flight for this button.
		if ( isCheckoutLocked( this ) ) {
			return false;
		}

		const id = $( '#' + this.form.id ),
			$form = $( id ),
			setting_data = $form.data( 'better-payment' ),
			fields = $form.serializeObject();

		let data = validateBetterPaymentForm( '', fields, this.form, false );

		if ( typeof data.status !== 'undefined' && ! data.status ) {
			e.preventDefault();
			toastr.error(
				data.errors.length
					? data.errors[ 0 ]
					: `${ somethingWentWrongText }!`
			);
			this.disabled = false;

			return false;
		}

		// One checkout per click: a second request would record a second order.
		if ( ! lockCheckoutButton( this ) ) {
			return false;
		}

		const button = this;

		$.ajax( {
			url: betterPayment.ajax_url,
			type: 'post',
			data: {
				action: 'better_payment_paystack_get_token',
				security: betterPayment.nonce,
				fields: fields,
				setting_data: setting_data,
			},

			success: function ( response ) {
				if (
					typeof response?.data?.authorization_url === 'undefined'
				) {
					toastr.error(
						response?.data || `${ somethingWentWrongText }!`
					);
					releaseCheckoutButton( button );
					return;
				}

				// Stays locked: the page is about to leave for Paystack.
				showRedirecting( button, redirectingHtml );
				window.location.href = response.data.authorization_url;
			},
			error: function () {
				console.log( 'Something went wrong!' );
				releaseCheckoutButton( button );
			},
		} );
	} );

	function validateBetterPaymentForm(
		paymentInfo,
		fields,
		thisForm,
		isPaypal = true
	) {
		let fieldsToExclude = getExcludedFields(),
			data = {};

		data.status = true;
		data.errors = [];

		let validatedData = validateForm( thisForm, fields, fieldsToExclude );

		data.status =
			typeof validatedData.status !== 'undefined'
				? validatedData.status
				: data.status;
		data.errors =
			typeof validatedData.errors !== 'undefined' &&
			validatedData.errors.constructor === Array
				? data.errors.concat( validatedData.errors )
				: data.errors;

		if ( isPaypal && ! paymentInfo.business_email ) {
			data.errors.push( `${ businessEmailIsRequiredText }!` );
			data.status = false;
		}

		if (
			typeof fields.primary_payment_amount === 'undefined' ||
			fields.primary_payment_amount === ''
		) {
			if (
				typeof fields.primary_payment_amount_radio === 'undefined' ||
				fields.primary_payment_amount_radio === ''
			) {
				data.errors.push( `${ paymentAmountFieldIsRequiredText }!` );
				data.status = false;
			}
		}

		if (
			typeof fields.primary_payment_amount !== 'undefined' &&
			parseFloat( fields.primary_payment_amount ) < 1
		) {
			data.errors.push( `${ minimumAmountIsOneText }!` );
			data.status = false;
		}

		return data;
	}

	$( document ).on( 'change', '.bp-custom-payment-amount', function ( e ) {
		let formWrapID = $( this ).closest( '.elementor-form' ).data( 'id' );
		let formIDSelector = this.form && this.form.id ? this.form.id : '';
		formIDSelector =
			formIDSelector == '' && formWrapID != ''
				? '.elementor-element-' + formWrapID
				: '#' + formIDSelector;

		const radioInput = document.querySelectorAll(
			formIDSelector + ' .bp-payment-amount-wrap input[type="radio"]'
		);
		radioInput.forEach( ( radio ) => {
			radio.checked = false;
		} );

		let amountValue = parseFloat( this.value );

		$( '.bp-transaction-details-amount-text' ).text( amountValue );

		// Hook: a layout can adjust the total it shows (Pro's layouts 4 and 5 multiply it by their quantity field).
		$( document ).trigger( 'better_payment_amount_changed', [
			amountValue,
			'input',
		] );
	} );

	$( document ).on( 'click', '.bp-form_pay-radio', function ( e ) {
		const $this = $( this );
		let amount = parseFloat( $this.val() );

		if ( amount == '' ) {
			return false;
		}

		$this
			.closest( 'form' )
			.find( '.bp-custom-payment-amount' )
			.val( amount );

		$( '.bp-transaction-details-amount-text' ).text( amount );

		// Hook: a layout can adjust the total it shows (Pro's layouts 4 and 5 multiply it by their quantity field).
		$( document ).trigger( 'better_payment_amount_changed', [
			amount,
			'radio',
		] );
	} );

	$( window ).on( 'elementor/frontend/init', () => {
		const Better_Payment_Stripe =
			elementorModules.frontend.handlers.Base.extend( {
				getDefaultSettings: function getDefaultSettings() {
					return {
						selectors: {
							form: '.elementor-form',
						},
					};
				},

				getDefaultElements: function getDefaultElements() {
					let selectors = this.getSettings( 'selectors' ),
						elements = {};
					elements.$form = this.$element.find( selectors.form );
					return elements;
				},

				bindEvents: function bindEvents() {
					this.elements.$form.on(
						'submit_success',
						this.handleFormAction
					);
				},

				handleFormAction: function handleFormAction( event, res ) {
					if ( typeof res.data.better_stripe_data != 'undefined' ) {
						let stripe = Stripe(
							res.data.better_stripe_data.stripe_public_key
						);
						stripe
							.redirectToCheckout( {
								sessionId:
									res.data.better_stripe_data.stripe_data,
							} )
							.then( function ( t ) {} );
					}

					if ( typeof res.data.better_paystack_data != 'undefined' ) {
						if (
							typeof res.data.better_paystack_data
								.authorization_url != 'undefined'
						) {
							window.location.href =
								res.data.better_paystack_data.authorization_url;
						} else {
							toastr.error( response.data );
						}
					}
				},
			} );

		const Better_Payment_Handler = ( $element ) => {
			elementorFrontend.elementsHandler.addHandler(
				Better_Payment_Stripe,
				{
					$element,
				}
			);
		};
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/form.default',
			Better_Payment_Handler
		);

		if ( typeof elementor != 'undefined' ) {
			elementor.hooks.addAction(
				'panel/open_editor/widget/form',
				function ( panel, model, view ) {
					elementor.hooks.addFilter(
						'elementor_pro/forms/content_template/item',
						function ( item, i, settings ) {
							if (
								typeof item.field_type !== 'undefined' &&
								item.field_type === formsFieldTypeBPName
							) {
								item.field_type = 'html';
								item.field_html = getFormsFieldsMarkup(
									settings,
									item
								);
							}

							return item;
						},
						10,
						3
					);
				}
			);
		}
	} );

	$.fn.serializeObject = function () {
		var objInit = {};
		var a = this.serializeArray();
		$.each( a, function () {
			if ( objInit[ this.name ] ) {
				if ( ! objInit[ this.name ].push ) {
					objInit[ this.name ] = [ objInit[ this.name ] ];
				}
				objInit[ this.name ].push( this.value || '' );
			} else {
				objInit[ this.name ] = this.value || '';
			}
		} );
		return objInit;
	};

	//Better Payment Functions Starts
	function isFormsFieldsEnable( settings ) {
		return (
			typeof settings.better_payment_payment_amount_enable !=
				'undefined' &&
			'yes' === settings.better_payment_payment_amount_enable
		);
	}

	function isFormsInputFieldEnable( settings, item ) {
		if ( ! isFormsFieldsEnable( settings ) ) {
			return false;
		}

		return (
			typeof item.bp_field_type != 'undefined' &&
			( 'input-field' === item.bp_field_type ||
				'both' === item.bp_field_type )
		);
	}

	function isFormsAmountListEnable( settings, item ) {
		if ( ! isFormsFieldsEnable( settings ) ) {
			return false;
		}

		let showAmountListEnable =
			typeof settings.better_payment_show_amount_list_enable !=
				'undefined' &&
			'yes' === settings.better_payment_show_amount_list_enable;

		return (
			showAmountListEnable &&
			typeof item.bp_field_type !== 'undefined' &&
			( 'amount-list' === item.bp_field_type ||
				'both' === item.bp_field_type )
		);
	}

	function getFormsCurrency( settings ) {
		let currency = 'USD';

		if ( settings.submit_actions.includes( 'paypal' ) ) {
			currency =
				typeof settings.better_payment_form_paypal_currency !==
				'undefined'
					? settings.better_payment_form_paypal_currency
					: currency;
		} else if ( settings.submit_actions.includes( 'stripe' ) ) {
			currency =
				typeof settings.better_payment_form_stripe_currency !==
				'undefined'
					? settings.better_payment_form_stripe_currency
					: currency;
		} else if ( settings.submit_actions.includes( 'paystack' ) ) {
			currency =
				typeof settings.better_payment_form_paystack_currency !==
				'undefined'
					? settings.better_payment_form_paystack_currency
					: currency;
		}

		return currency;
	}

	function getFormsCurrencyAlignment( settings ) {
		let currencyAlignment = 'left';

		if ( settings.submit_actions.includes( 'paypal' ) ) {
			currencyAlignment =
				typeof settings.better_payment_form_currency_alignment_paypal !==
				'undefined'
					? settings.better_payment_form_currency_alignment_paypal
					: currencyAlignment;
		} else if ( settings.submit_actions.includes( 'stripe' ) ) {
			currencyAlignment =
				typeof settings.better_payment_form_currency_alignment_stripe !==
				'undefined'
					? settings.better_payment_form_currency_alignment_stripe
					: currencyAlignment;
		} else if ( settings.submit_actions.includes( 'paystack' ) ) {
			currencyAlignment =
				typeof settings.better_payment_form_currency_alignment_paystack !==
				'undefined'
					? settings.better_payment_form_currency_alignment_paystack
					: currencyAlignment;
		}

		currencyAlignment =
			'left' === currencyAlignment || 'right' === currencyAlignment
				? currencyAlignment
				: 'left';
		return currencyAlignment;
	}

	function getFormsAmountListMarkup( settings, item ) {
		if (
			typeof settings.better_payment_show_amount_list_items ===
				'undefined' &&
			settings.better_payment_show_amount_list_items === ''
		) {
			return;
		}

		let currency = getFormsCurrency( settings );
		let currencySymbol = getCurrencySymbols( currency );
		let currencyAlignment = getFormsCurrencyAlignment( settings );
		let currencySymbolLeft =
			'left' === currencyAlignment ? currencySymbol : '';
		let currencySymbolRight =
			'right' === currencyAlignment ? currencySymbol : '';
		let bpFormsAmountListMarkup = '';
		let bpFormsAmountListItemMarkup = '';

		if ( Array.isArray( settings.better_payment_show_amount_list_items ) ) {
			let amountList = settings.better_payment_show_amount_list_items;
			let uniqueId,
				itemValue = '';

			amountList.forEach( ( amountListItem ) => {
				uniqueId = uniqid();
				itemValue =
					typeof amountListItem.better_payment_amount_val !==
					'undefined'
						? parseFloat( amountListItem.better_payment_amount_val )
						: '';

				bpFormsAmountListItemMarkup += `
                        <div class="bp-form__group pb-3">
                            <input type="radio" value="${ itemValue }"
                                id="bp_payment_amount-${ uniqueId }" class="bp-form__control bp-form_pay-radio "
                                name="primary_payment_amount_radio">
                            <label for="bp_payment_amount-${ uniqueId }">${ currencySymbolLeft }${ itemValue }${ currencySymbolRight }</label>
                        </div>
                    `;
			} );

			bpFormsAmountListMarkup += `
                    <div class="payment-form-layout">
                        <div class="bp-payment-amount-wrap">
                            ${ bpFormsAmountListItemMarkup }
                        </div>
                    </div>
                `;
		}

		return bpFormsAmountListMarkup;
	}

	function getFormsInputFieldMarkup( settings, item ) {
		let currency = getFormsCurrency( settings );
		let currencySymbol = getCurrencySymbols( currency );

		let bpPlaceholder =
			typeof item.bp_placeholder !== 'undefined'
				? item.bp_placeholder
				: '';

		let bpPaymentAmountInput = `<input id="form-field-${ formsFieldTypeBPName }" class="input is-medium required bp-custom-payment-amount bp-custom-payment-amount-el-integration" type="number" placeholder="${ bpPlaceholder }" name="form_fields[${ formsFieldTypeBPName }]" required="required" min="${ item.bp_field_min }" max="${ item.bp_field_max }" value="">`;

		let paymentAmountInputGroup = `  <div class="better-payment-field-advanced-layout field-primary_payment_amount elementor-repeater-item-form-field-payment_amount">
                                            <div class="control has-icons-left">
                                                ${ bpPaymentAmountInput }
                                                <span class="icon is-medium is-left">
                                                    <span class="bp-currency-symbol">${ currencySymbol }</span>
                                                </span>
                                            </div>
                                        </div>
                                    `;
		return paymentAmountInputGroup;
	}

	function getFormsFieldsMarkup( settings, item ) {
		let showAmountList = isFormsAmountListEnable( settings, item );
		let showInputField = isFormsInputFieldEnable( settings, item );

		let bpFormsFieldsMarkup = '';

		if ( showAmountList || showInputField ) {
			let bpPaymentAmountLabel = `<label for="form-field-${ formsFieldTypeBPName }" class="elementor-field-label">${ item.field_label }</label>`;
			bpFormsFieldsMarkup += bpPaymentAmountLabel;
			bpFormsFieldsMarkup += `<div class ="better-payment is-full-width">`;
		}

		if ( showAmountList ) {
			bpFormsFieldsMarkup += getFormsAmountListMarkup( settings, item );
		}

		if ( showInputField ) {
			bpFormsFieldsMarkup += getFormsInputFieldMarkup( settings, item );
		}

		if ( showAmountList || showInputField ) {
			bpFormsFieldsMarkup += `</div>`;
		}

		return bpFormsFieldsMarkup;
	}

	function uniqid( prefix = '', random = false ) {
		const sec = Date.now() * 1000 + Math.random() * 1000;
		const id = sec.toString( 16 ).replace( /\./g, '' ).padEnd( 14, '0' );
		return `${ prefix }${ id }${
			random ? `.${ Math.trunc( Math.random() * 100000000 ) }` : ''
		}`;
	}

	function getExcludedFields() {
		return [
			'better_payment_page_id',
			'better_payment_widget_id',
			'return',
			'action',
			'security',
			'cancel_return',
			'payment_amount',
			'primary_payment_amount',
		];
	}

	function getCurrencySymbols( currency = 'USD' ) {
		let currencySymbol = '$';
		let currencyList = betterPayment.currency_symbols;

		if ( currency in currencyList ) {
			currencySymbol = currencyList[ currency ];
		}

		return currencySymbol;
	}
	//Better Payment Functions Ends

	//Helper Functions Starts
	function validateEmail( email ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
	}

	function validateForm( formSelector, fields, fieldsToExclude ) {
		let data = {};

		data.status = true;
		data.errors = [];

		for ( var key in fields ) {
			if ( fields.hasOwnProperty( key ) ) {
				if ( fieldsToExclude.indexOf( key ) === -1 ) {
					let validatedData = validateField(
						formSelector[ key ],
						fields[ key ]
					);
					if ( ! validatedData.status ) {
						data.errors.push(
							typeof validatedData.error !== 'undefined'
								? validatedData.error
								: `${ key } ${ fieldIsRequiredText }!`
						);
						data.status = false;
						return data;
					}
				}
			}
		}

		return data;
	}

	function validateField( fieldSelector, fieldValue, errorMessage = '' ) {
		let fieldRequired = isFieldRequired( fieldSelector );
		let data = {};

		data.error = '';
		data.status = true;

		if ( fieldSelector.length > 1 ) {
			// Multiple Fields/Nodes
			return data;
		}

		if ( fieldRequired ) {
			let itemPlaceholder = fieldSelector.placeholder;
			itemPlaceholder = itemPlaceholder.replace( ' *', '' );

			if ( ! itemPlaceholder ) {
				itemPlaceholder = fieldSelector.name;
				itemPlaceholder = itemPlaceholder
					.replace( 'primary', '' )
					.replace( '_', ' ' )
					.replace( /\b\w/g, ( char ) => char.toUpperCase() ); // primary_first_name => First Name
			}

			let error = errorMessage
				? errorMessage
				: `${ itemPlaceholder } ${ fieldIsRequiredText }!`;
			if ( typeof fieldValue === 'undefined' || fieldValue === '' ) {
				data.error = error;
				data.status = false;
			}
		}

		return data;
	}

	function isFieldRequired( fieldSelector ) {
		let fieldRequired = false;

		if ( fieldSelector.length > 1 ) {
			//Multiple Fields/Nodes
			return true;
		}

		//if required attribute has value rather than null or ''
		fieldRequired = ! (
			fieldSelector.getAttribute( 'required' ) === null ||
			fieldSelector.getAttribute( 'required' ) === ''
		);
		return fieldRequired;
	}

	function toasterOptions() {
		toastr.options = {
			timeOut: '2000',
			toastClass: 'font-size-md',
			maxOpened: 1,
		};
	}
	toasterOptions();
	//Helper Functions Ends

	// PayPal IPN polling — fires when the return URL lands before IPN is received.
	( function initPaypalPendingPoller() {
		const $pending = $( '.bp-paypal-pending' );
		if ( ! $pending.length ) return;

		const orderId = $pending.data( 'order-id' );
		const returnUrl = $pending.data( 'return-url' );
		const maxAttempts = 20;
		const intervalMs = 3000;
		let attempts = 0;
		const timeoutText =
			betterPayment.custom_texts &&
			betterPayment.custom_texts.paypal_payment_timeout
				? betterPayment.custom_texts.paypal_payment_timeout
				: 'Payment verification timed out. Please contact support if your payment was charged.';

		function showTimeout() {
			$pending.addClass( 'bp-paypal-pending--timeout' );
			$pending
				.find( '.bp-paypal-pending__heading' )
				.text(
					betterPayment.custom_texts &&
						betterPayment.custom_texts.paypal_timeout_heading
						? betterPayment.custom_texts.paypal_timeout_heading
						: 'Verification timed out'
				);
			$pending.find( '.bp-paypal-pending__sub' ).text( timeoutText );
		}

		const poller = setInterval( function () {
			attempts++;

			$.post(
				betterPayment.ajax_url,
				{
					action: 'better_payment_check_paypal_status',
					security: betterPayment.nonce,
					order_id: orderId,
				},
				function ( response ) {
					if (
						response.success &&
						response.data.status === 'completed'
					) {
						clearInterval( poller );
						// Navigate to the original return URL captured before remove_arg()
						// strips the PayPal params via history.replaceState.
						window.location.href = returnUrl;
					} else if ( attempts >= maxAttempts ) {
						clearInterval( poller );
						showTimeout();
					}
				}
			).fail( function () {
				if ( attempts >= maxAttempts ) {
					clearInterval( poller );
					showTimeout();
				}
			} );
		}, intervalMs );
	} )();
} )( jQuery );
