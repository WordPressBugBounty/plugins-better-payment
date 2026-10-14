import { applyFilters } from '@wordpress/hooks';
import {
	PAYPAL_REFUND_CREDENTIALS_QUICK_SETUP_FILTER,
	paypalModeOf,
} from '../../../../utils/proHandoff';

const PayPal = ( { data, onDataChange } ) => {
	const mode = paypalModeOf( data );

	// The refund credentials (client ID + secret) are Pro's: only Pro's refunds use
	// them. Pro fills the slot, and a free install renders nothing here.
	const refundCredentials = applyFilters(
		PAYPAL_REFUND_CREDENTIALS_QUICK_SETUP_FILTER,
		null,
		{ mode, data, onDataChange }
	);

	return (
		<div className="bp-payment__content">
			<div className="bp-payment__field live__mode">
				<div className="bp-payment__field-details">
					<h3 className="bp-payment__field-title">Live Mode</h3>
					<p className="bp-payment__field-description">
						Live mode allows you to process real transactions. You
						will need a PayPal business email to accept real
						payments.
					</p>
				</div>
				<label className="bp-toggle">
					<input
						type="checkbox"
						checked={
							data?.better_payment_settings_payment_paypal_live_mode ===
							'yes'
						}
						onChange={ ( e ) =>
							onDataChange( {
								...data,
								better_payment_settings_payment_paypal_live_mode:
									e.target.checked ? 'yes' : 'no',
							} )
						}
					/>
					<span className="bp-toggle__slider"></span>
				</label>
			</div>

			<div className="bp-payment__field email__field">
				<div className="bp-payment__field-details">
					<label className="bp-label">Business Email</label>
					<p className="bp-payment__field-description">
						Your PayPal account email address to accept payments via
						PayPal.
					</p>
				</div>
				<div className="bp-input-wrapper">
					<svg
						width="18"
						height="17"
						viewBox="0 0 18 17"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M13.3274 5.88867C13.3274 5.88867 10.6523 9.0992 8.69579 9.0992C6.74007 9.0992 4.03497 5.88867 4.03497 5.88867"
							stroke="#899AB2"
							strokeWidth="1.5"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
						<path
							fill-rule="evenodd"
							clip-rule="evenodd"
							d="M0.75 8.34733C0.75 2.64891 2.73413 0.75 8.68651 0.75C14.6389 0.75 16.623 2.64891 16.623 8.34733C16.623 14.0449 14.6389 15.9447 8.68651 15.9447C2.73413 15.9447 0.75 14.0449 0.75 8.34733Z"
							stroke="#899AB2"
							strokeWidth="1.5"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
					<input
						type="email"
						className="bp-input"
						placeholder="Email Address"
						value={
							data?.better_payment_settings_payment_paypal_email
						}
						onChange={ ( e ) =>
							onDataChange( {
								...data,
								better_payment_settings_payment_paypal_email:
									e.target.value,
							} )
						}
					/>
				</div>
			</div>
			{ refundCredentials }
		</div>
	);
};

export default PayPal;
