import {
	useState,
	useEffect,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dashboardAPI } from '../../utils/api';
import { isProEnabled } from '../../utils/proEnabled';

/**
 * The sale banner above Better Payment's header: free upsell UI for the running campaign.
 *
 * A site with Pro active never sees it. It used to show to everyone until dismissed, so a paying
 * customer was offered a discount on what they already own.
 */
const SaleInfo = () => {
	const proEnabled = isProEnabled();
	const [ isSaleInfoDismissed, setIsSaleInfoDismissed ] = useState( false );

	useEffect( () => {
		// With Pro there is no banner to show or hide: ask nothing.
		if ( proEnabled ) {
			return;
		}

		dashboardAPI.isSaleInfoDismissed().then( ( data ) => {
			setIsSaleInfoDismissed( data?.isSaleInfoDismissed );
		} );
	}, [ proEnabled ] );

	if ( proEnabled ) {
		return null;
	}

	const handleDismissSaleInfo = () => {
		dashboardAPI.dismissSaleInfo().then( ( data ) => {
			if ( data?.success ) {
				setIsSaleInfoDismissed( true );
			}
		} );
	};
	return (
		<>
			{ ! isSaleInfoDismissed && (
				<div className="bp-flex align-items_center justify-content-center bp-sale_flag">
					<div className="bp-sale_info-wrapper">
						<p className="fx-7 bp-sale_info-text ">
							{ createInterpolateElement(
								/* translators: <highlight> wraps the discount text. */
								__(
									'Unlock premium features with Black Friday Special Sale: <highlight>Grab up to 40% OFF</highlight>',
									'better-payment'
								),
								{
									highlight: (
										<span style={ { color: '#FFC800' } } />
									),
								}
							) }
						</p>
					</div>
					<div className="bp-sale_learnMore-wrapper">
						<a
							href="https://betterpayment.co/notice"
							target="_blank"
							className="bp-font_ibm fx-6 bp-learnMore_btn"
						>
							{ __( 'Learn More', 'better-payment' ) }
						</a>
					</div>
					<div className="bp-flag_close-btn-wrapper">
						<button
							className="bp-flag_close-btn"
							onClick={ handleDismissSaleInfo }
						>
							<svg
								width="16"
								height="17"
								viewBox="0 0 16 17"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="M11.6134 14.0503L13.4509 12.2128L4.07969 2.84154L2.24219 4.67904L11.6134 14.0503ZM4.07969 14.0503L13.4509 4.67904L11.6134 2.84154L2.24219 12.2128L4.07969 14.0503Z"
									fill="#ffffff"
								/>
							</svg>
						</button>
					</div>
				</div>
			) }
		</>
	);
};

export default SaleInfo;
