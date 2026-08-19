/**
 * Better Payment (Stripe) — WooCommerce blocks checkout registration.
 *
 * Hand-authored (NOT produced by any webpack build — there is no src/
 * counterpart). It is a plain registration shim for a redirect gateway with
 * no fields: it surfaces the title/description configured on the gateway and
 * lets WooCommerce handle the rest via process_payment().
 *
 * Dependencies (registered handle deps): wc-blocks-registry, wc-settings,
 * wp-element, wp-html-entities.
 */
( function () {
	'use strict';

	const registry = window.wc?.wcBlocksRegistry;
	const settingsApi = window.wc?.wcSettings;
	const element = window.wp?.element;
	const htmlEntities = window.wp?.htmlEntities;

	if ( ! registry || ! settingsApi || ! element ) {
		return;
	}

	const PAYMENT_METHOD_NAME = 'better_payment_stripe';
	const settings = settingsApi.getSetting(
		PAYMENT_METHOD_NAME + '_data',
		{}
	);

	const decode = ( value ) =>
		htmlEntities?.decodeEntities
			? htmlEntities.decodeEntities( value )
			: value;

	const label = decode( settings.title ?? '' ) || 'Better Payment';
	const description = decode( settings.description ?? '' );

	const Label = () => element.createElement( 'span', null, label );
	const Content = () => element.createElement( 'div', null, description );

	registry.registerPaymentMethod( {
		name: PAYMENT_METHOD_NAME,
		label: element.createElement( Label ),
		content: element.createElement( Content ),
		edit: element.createElement( Content ),
		ariaLabel: label,
		canMakePayment: () => true,
		supports: {
			features: settings.supports ?? [ 'products' ],
		},
	} );
} )();
