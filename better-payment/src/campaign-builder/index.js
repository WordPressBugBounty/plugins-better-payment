import { render } from '@wordpress/element';
import App from './App';
import {
	BoxShadowControl,
	ColorControl,
	ControlHeader,
	DimensionsControl,
} from './components/StyleControls';
import Popover from './components/Popover';
import './style.scss';

// The builder's control widgets, for an add-on's own controls (drawn through
// `better_payment.campaign_builder.settings_control`) so they look and behave
// like Lite's — Pro's gradient uses the colour control and its popover. Read at
// render time: an add-on's script loads before this bundle.
window.betterPaymentBuilderUI = Object.freeze( {
	BoxShadowControl,
	ColorControl,
	ControlHeader,
	DimensionsControl,
	Popover,
} );

const root = document.getElementById( 'bp-campaign-builder' );
if ( root ) {
	const campaignId = parseInt( root.dataset.campaignId || '0', 10 );
	const restUrl = root.dataset.restUrl || '';
	const nonce = root.dataset.nonce || '';
	const adminUrl = root.dataset.adminUrl || '';
	const start = root.dataset.start || '';
	const campaignsUrl =
		root.dataset.campaignsUrl ||
		`${ adminUrl }admin.php?page=better-payment-admin&tab=campaigns`;

	render(
		<App
			campaignId={ campaignId }
			restUrl={ restUrl }
			nonce={ nonce }
			adminUrl={ adminUrl }
			campaignsUrl={ campaignsUrl }
			start={ start }
		/>,
		root
	);
}
