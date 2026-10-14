/**
 * Keeps a payment button locked from the moment a checkout is requested until the
 * browser has left the page.
 *
 * Every click on a Stripe or Paystack button asks the server for a new checkout,
 * and the server records an `unpaid` order for each one. The button used to be
 * re-enabled (and its "Redirecting" label overwritten) in the same tick that
 * started the redirect, while `stripe.redirectToCheckout()` was still loading
 * Stripe. A visitor who clicked again in that second got a second checkout and a
 * second order: they paid one, and the other stayed `Incomplete` in the admin
 * list forever beside the completed one.
 *
 * So the button is released only when the visitor is still here and needs it:
 * the request failed, the server refused, the redirect reported an error, or the
 * page came back from the back/forward cache after the redirect had happened.
 *
 * Plain DOM (no jQuery) so it can be unit-tested.
 */

/** Marks a button that has a checkout in flight. */
export const LOCK_ATTRIBUTE = 'data-bp-checkout-lock';

/** The label each locked button had before it was locked. */
const originalLabels = new WeakMap();

/**
 * Whether a checkout is already in flight for this button.
 *
 * @param {HTMLElement|null|undefined} button The payment button.
 * @return {boolean} True while locked.
 */
export function isCheckoutLocked( button ) {
	return !! button?.hasAttribute?.( LOCK_ATTRIBUTE );
}

/**
 * Lock a button before its checkout request is sent.
 *
 * @param {HTMLElement|null|undefined} button The payment button.
 * @return {boolean} False when there is no button or it is already locked —
 *                   the caller must then send nothing.
 */
export function lockCheckoutButton( button ) {
	if ( ! button || isCheckoutLocked( button ) ) {
		return false;
	}

	originalLabels.set( button, button.innerHTML );
	button.setAttribute( LOCK_ATTRIBUTE, '' );
	button.disabled = true;

	return true;
}

/**
 * Show the "Redirecting" label on a locked button. The button stays locked.
 *
 * @param {HTMLElement|null|undefined} button The payment button.
 * @param {string}                     html   Label markup.
 */
export function showRedirecting( button, html ) {
	if ( isCheckoutLocked( button ) ) {
		button.innerHTML = html;
	}
}

/**
 * Unlock a button and give it back the label it had before it was locked.
 *
 * @param {HTMLElement|null|undefined} button The payment button.
 */
export function releaseCheckoutButton( button ) {
	if ( ! isCheckoutLocked( button ) ) {
		return;
	}

	if ( originalLabels.has( button ) ) {
		button.innerHTML = originalLabels.get( button );
		originalLabels.delete( button );
	}

	button.removeAttribute( LOCK_ATTRIBUTE );
	button.disabled = false;
}

/**
 * Unlock every locked button under `root`.
 *
 * @param {ParentNode} root Where to look.
 */
export function releaseAllCheckoutButtons( root ) {
	root?.querySelectorAll?.( `[${ LOCK_ATTRIBUTE }]` ).forEach(
		releaseCheckoutButton
	);
}

/**
 * `pageshow` handler: a page restored from the back/forward cache comes back
 * exactly as it was left — mid-redirect, buttons locked. Unlock them so a
 * visitor who pressed Back from the gateway can pay. A normal load needs
 * nothing: its buttons were never locked.
 *
 * @param {PageTransitionEvent} event The pageshow event.
 * @param {ParentNode}          root  Where to look.
 */
export function releaseOnPageRestore( event, root ) {
	if ( event?.persisted ) {
		releaseAllCheckoutButtons( root );
	}
}

/**
 * Start a Stripe Checkout redirect, always settling with `{ error }` or `{}`.
 *
 * Stripe.js missing, blocked or throwing is reported as an error, the same as
 * an error `redirectToCheckout()` returns, so the caller has one place to
 * release the button. On success the page navigates away and the promise is
 * usually never observed.
 *
 * @param {Function} StripeFactory The global `Stripe` (may be undefined).
 * @param {string}   publicKey     Publishable key.
 * @param {string}   sessionId     Checkout Session id.
 * @return {Promise<{error?: {message?: string}}>} Settles with the outcome.
 */
export function redirectToStripeCheckout(
	StripeFactory,
	publicKey,
	sessionId
) {
	try {
		return Promise.resolve(
			StripeFactory( publicKey ).redirectToCheckout( { sessionId } )
		).then(
			( result ) => result ?? {},
			( error ) => ( { error: { message: error?.message } } )
		);
	} catch ( error ) {
		return Promise.resolve( { error: { message: error?.message } } );
	}
}
