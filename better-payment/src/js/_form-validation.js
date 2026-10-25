/**
 * Field checks the Payment Form runs before it sends anything to a gateway.
 *
 * Imported by better-payment.js (the widget, block and Pro's donate popup all
 * submit through its gateway click handlers). The Stripe and Paystack handlers
 * call `preventDefault()`, and the popup form is `novalidate`, so the browser's
 * own constraint validation never runs: these checks are the only ones before the
 * request. The server holds the same email rule (Classes\PayerEmail).
 */

/**
 * The same shape the server accepts: something@something.tld, no spaces.
 */
export const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Whether a value is an email address.
 *
 * @param {*} value Field value.
 * @return {boolean} True for an address.
 */
export function isValidEmail( value ) {
	return EMAIL_PATTERN.test( String( value ?? '' ).trim() );
}

/**
 * Whether a field is required.
 *
 * HTML semantics: the attribute's presence makes a field required, whatever its
 * value. `required=""` (a bare `required`, or jQuery's `.prop( 'required', true )`)
 * used to count as optional, so a bare attribute was never enforced.
 *
 * @param {Element|RadioNodeList|undefined} field A form control, or a node list.
 * @return {boolean} True when the field must have a value.
 */
export function isFieldRequired( field ) {
	if ( ! field ) {
		return false;
	}

	// Several controls share the name (a radio group): handled as before, as one.
	if ( typeof field.getAttribute !== 'function' ) {
		return field.length > 1;
	}

	return field.hasAttribute( 'required' );
}

/**
 * The first email field holding something that is not an address.
 *
 * An empty field is left to the required check: an optional email may stay empty.
 * Disabled fields are not submitted, so they are skipped.
 *
 * @param {HTMLFormElement|null|undefined} form The form.
 * @return {HTMLInputElement|null} The offending field, or null.
 */
export function findInvalidEmailField( form ) {
	if ( ! form?.querySelectorAll ) {
		return null;
	}

	const fields = form.querySelectorAll( 'input[type="email"]' );

	for ( const field of fields ) {
		if ( field.disabled ) {
			continue;
		}

		const value = String( field.value ?? '' ).trim();

		if ( '' !== value && ! isValidEmail( value ) ) {
			return field;
		}
	}

	return null;
}

/**
 * A control's own bound, or null when it has none.
 *
 * @param {Element} field     The control.
 * @param {string}  attribute `min` or `max`.
 * @return {number|null} The bound.
 */
function numericAttribute( field, attribute ) {
	const raw = field?.getAttribute?.( attribute );

	if ( null === raw || undefined === raw || '' === String( raw ).trim() ) {
		return null;
	}

	const value = parseFloat( raw );

	return Number.isFinite( value ) ? value : null;
}

/**
 * The first amount outside its field's own `min` / `max`.
 *
 * Every amount input prints the bounds the server enforces (the Payment Form's
 * min/max, a campaign's minimum in Pro's popup), but the gateway handlers call
 * `preventDefault()` and the popup form is `novalidate`, so the browser never
 * checked them. A donor who typed less than a campaign minimum got a 2-second
 * toast from the server, and on PayPal (a native post) a "Payment not accepted"
 * error page that took them off the site.
 *
 * An empty field is left to the required check. Disabled fields are skipped.
 *
 * @param {HTMLFormElement|null|undefined} form The form.
 * @return {{field: HTMLInputElement, bound: string, limit: number}|null} The offending field.
 */
export function findAmountOutOfBounds( form ) {
	if ( ! form?.querySelectorAll ) {
		return null;
	}

	const fields = form.querySelectorAll(
		'input[name="primary_payment_amount"]'
	);

	for ( const field of fields ) {
		if ( field.disabled || 'hidden' === field.type ) {
			continue;
		}

		const raw = String( field.value ?? '' ).trim();
		const value = parseFloat( raw );

		if ( '' === raw || ! Number.isFinite( value ) ) {
			continue;
		}

		// Same tolerance as the server (PaymentRequestGuard::TOLERANCE).
		const min = numericAttribute( field, 'min' );
		if ( null !== min && value + 0.005 < min ) {
			return { field, bound: 'min', limit: min };
		}

		const max = numericAttribute( field, 'max' );
		if ( null !== max && max > 0 && value - 0.005 > max ) {
			return { field, bound: 'max', limit: max };
		}
	}

	return null;
}

/**
 * What a required-field message calls the field: its placeholder, else its
 * label, else its name (`primary_first_name` → `First Name`).
 *
 * The name fallback used to replace only the first underscore, so a field whose
 * owner cleared the placeholder was reported as "First_name field is required!".
 *
 * @param {Element|null|undefined} field The control.
 * @return {string} A readable name.
 */
export function fieldDisplayName( field ) {
	const placeholder = String( field?.placeholder ?? '' )
		.replace( ' *', '' )
		.trim();

	if ( placeholder ) {
		return placeholder;
	}

	const id = field?.id;
	const label =
		id && field?.ownerDocument
			? field.ownerDocument.querySelector(
					`label[for="${ String( id ).replace( /"/g, '\\"' ) }"]`
			  )
			: null;
	const labelText = String( label?.textContent ?? '' )
		.replace( /\*/g, '' )
		.replace( /\s+/g, ' ' )
		.trim();

	if ( labelText ) {
		return labelText;
	}

	return String( field?.name ?? '' )
		.replace( /^primary_?/, '' )
		.replace( /_/g, ' ' )
		.trim()
		.replace( /\b\w/g, ( char ) => char.toUpperCase() );
}

/**
 * Point the donor at a field the form refused: focus it and mark it
 * `aria-invalid`, so assistive tech announces which field it is. The mark is
 * cleared by `clearInvalidMark()` once the field is edited.
 *
 * @param {Element|null|undefined} field The control.
 */
export function markInvalidField( field ) {
	if ( ! field?.setAttribute ) {
		return;
	}

	field.setAttribute( 'aria-invalid', 'true' );
	field.focus?.();
}

/**
 * Input handler: an edited field is no longer known to be invalid.
 *
 * @param {Event} event The input or change event.
 */
export function clearInvalidMark( event ) {
	const field = event?.target;

	if ( 'true' === field?.getAttribute?.( 'aria-invalid' ) ) {
		field.removeAttribute( 'aria-invalid' );
	}
}
