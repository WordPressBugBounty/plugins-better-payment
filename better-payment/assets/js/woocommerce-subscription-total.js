/**
 * Better Payment — "Subscription Total" for the WooCommerce Cart and
 * Checkout BLOCKS.
 *
 * Hand-authored (NOT produced by any webpack build — there is no src/
 * counterpart), matching assets/js/woocommerce-blocks.js.
 *
 * The classic cart/checkout render this row in PHP. The blocks render their
 * totals in React, so there is no PHP hook to render into: the summary
 * travels as Store API cart data (CartTotals::store_api_payload()) and this
 * script paints it into the ExperimentalOrderMeta slot, which both the Cart
 * block and the Checkout block expose below their totals.
 *
 * **Every string arrives pre-formatted and pre-translated from PHP.** Nothing
 * here formats currency, dates or copy — doing so would drift from the
 * classic row on the same shop, and would have to re-implement a dozen
 * WooCommerce currency settings plus the site's date format and locale. It is
 * also why no `value` prop is passed to TotalsItem: that prop takes a number
 * in minor units and formats it itself.
 *
 * Dependencies (registered handle deps): wc-blocks-checkout, wp-element,
 * wp-plugins.
 *
 * > Do not go looking for these exports in `blocks-checkout.js` — that file is
 * > a ~3KB webpack stub which resolves the real module out of a shared chunk
 * > (`wc-cart-checkout-base`) and assigns it to `window.wc.blocksCheckout`.
 * > Grepping the stub for `ExperimentalOrderMeta` finds nothing and looks like
 * > proof the API is gone. It is not.
 */
( function () {
	'use strict';

	const blocksCheckout = window.wc?.blocksCheckout;
	const element = window.wp?.element;
	const plugins = window.wp?.plugins;

	const OrderMetaSlot = blocksCheckout?.ExperimentalOrderMeta;
	const TotalsItem = blocksCheckout?.TotalsItem;
	const TotalsWrapper = blocksCheckout?.TotalsWrapper;

	// ExperimentalOrderMeta is, as the name says, experimental: a WooCommerce
	// release can rename or drop it. Bail rather than throw — the shop keeps a
	// working cart, just without the row. TotalsItem/TotalsWrapper are
	// degraded to plain markup instead, since they are only presentational.
	if ( ! OrderMetaSlot || ! element || ! plugins?.registerPlugin ) {
		return;
	}

	const NAMESPACE = 'better-payment';
	const { createElement } = element;

	/**
	 * One small line under the amount.
	 *
	 * @param {string} text     Pre-formatted text.
	 * @param {string} key      React key.
	 * @param {string} extraCls Optional extra class name.
	 * @return {Object|null} Element, or null when there is nothing to say.
	 */
	const metaLine = ( text, key, extraCls ) =>
		text
			? createElement(
					'span',
					{
						key,
						className:
							'bp-subscription-total__meta' +
							( extraCls ? ' ' + extraCls : '' ),
					},
					text
			  )
			: null;

	const SubscriptionTotal = ( { extensions } ) => {
		const data = extensions?.[ NAMESPACE ];

		// No subscription in the cart — or an older WooCommerce that did not
		// deliver our extension data at all.
		if ( ! data?.has_subscription ) {
			return null;
		}

		// The small lines that sit under the label/amount pair.
		const description = createElement(
			'span',
			{ className: 'bp-subscription-total__lines' },
			metaLine( data.next_billing, 'next' ),
			metaLine(
				data.cancel_note,
				'cancel',
				'bp-subscription-total__cancel'
			)
		);

		// TotalsItem only renders `value` when it is a React ELEMENT or a
		// finite number — a bare string renders as nothing at all. The amount
		// is already formatted server-side, so it has to be wrapped.
		const amount = createElement(
			'span',
			{ className: 'bp-subscription-total__amount' },
			data.amount
		);

		// Rendering through WooCommerce's own TotalsItem/TotalsWrapper is what
		// puts this row on the same grid as Subtotal and Total: the label sits
		// left, the amount right, and the wrapper supplies the separator and
		// the padding the surrounding rows use. Hand-rolled markup lands
		// outside that padding and reads as a detached block glued to the
		// bottom of the summary.
		if ( ! TotalsItem ) {
			return createElement(
				'div',
				{
					className:
						'bp-subscription-total bp-subscription-total--fallback',
				},
				createElement(
					'span',
					{ className: 'bp-subscription-total__label' },
					data.label
				),
				amount,
				description
			);
		}

		const row = createElement( TotalsItem, {
			className: 'bp-subscription-total',
			label: data.label,
			value: amount,
			description,
		} );

		return TotalsWrapper ? createElement( TotalsWrapper, null, row ) : row;
	};

	plugins.registerPlugin( 'better-payment-subscription-total', {
		// One fill serves the Cart block AND the Checkout block — the slot is
		// rendered by both, so the two surfaces cannot drift apart.
		render: () =>
			createElement(
				OrderMetaSlot,
				null,
				createElement( SubscriptionTotal )
			),
		scope: 'woocommerce-checkout',
	} );
} )();
