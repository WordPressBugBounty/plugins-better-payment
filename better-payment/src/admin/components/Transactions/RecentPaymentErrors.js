import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { transactionsAPI } from '../../utils/api';
import { formatDateTime } from '../../utils/helper';

const GATEWAYS = { stripe: 'Stripe', paypal: 'PayPal', paystack: 'Paystack' };

/**
 * The fix for a kind of failure, in the admin's words.
 *
 * @param {string} kind Entry kind.
 * @return {string} Hint.
 */
export const paymentErrorHint = ( kind ) => {
	switch ( kind ) {
		case 'config':
			return __(
				'Check the gateway keys in Settings.',
				'better-payment'
			);
		case 'amount':
			return __( 'The gateway refused this amount.', 'better-payment' );
		case 'currency':
			return __(
				'The gateway account does not accept this currency.',
				'better-payment'
			);
		case 'busy':
			return __( 'The gateway could not be reached.', 'better-payment' );
		default:
			return __( 'The gateway refused the request.', 'better-payment' );
	}
};

/**
 * Admin-only list of payments that failed to start. These have no transaction row, so
 * this is the one place their real reason appears. Renders nothing while the log is empty.
 *
 * @return {JSX.Element|null} The panel.
 */
const RecentPaymentErrors = () => {
	const [ errors, setErrors ] = useState( [] );

	useEffect( () => {
		transactionsAPI.getPaymentErrors().then( ( data ) => {
			setErrors( Array.isArray( data?.errors ) ? data.errors : [] );
		} );
	}, [] );

	if ( errors.length === 0 ) {
		return null;
	}

	const clear = async () => {
		try {
			await transactionsAPI.clearPaymentErrors();
			setErrors( [] );
		} catch ( error ) {
			toast.error( error?.message ?? __( 'Failed', 'better-payment' ) );
		}
	};

	return (
		<details className="bp-payment-errors">
			<summary>
				{ sprintf(
					/* translators: %d: number of entries. */
					__( 'Recent payment errors (%d)', 'better-payment' ),
					errors.length
				) }
			</summary>
			<ul>
				{ errors.map( ( entry ) => (
					<li
						key={ `${ entry.time }-${ entry.gateway }-${ entry.code }` }
					>
						<strong>
							{ GATEWAYS[ entry.gateway ] ?? entry.gateway }
						</strong>
						{ entry.page_url ? (
							<a href={ entry.page_url }>
								{ entry.page_title ||
									__( '(no title)', 'better-payment' ) }
							</a>
						) : (
							<span>{ entry.page_title }</span>
						) }
						<span>{ formatDateTime( entry.time ) }</span>
						{ entry.count > 1 && (
							<span>
								{ sprintf(
									/* translators: %d: how many times this failure happened. */
									__( '%d times', 'better-payment' ),
									entry.count
								) }
							</span>
						) }
						<p>
							{ paymentErrorHint( entry.kind ) }
							{ entry.message ? ` ${ entry.message }` : '' }
						</p>
					</li>
				) ) }
			</ul>
			<button type="button" onClick={ clear }>
				{ __( 'Clear', 'better-payment' ) }
			</button>
		</details>
	);
};

export default RecentPaymentErrors;
