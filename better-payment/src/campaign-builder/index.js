import { render } from '@wordpress/element';
import App from './App';
import './style.scss';

const root = document.getElementById( 'bp-campaign-builder' );
if ( root ) {
    const campaignId   = parseInt( root.dataset.campaignId || '0', 10 );
    const restUrl      = root.dataset.restUrl || '';
    const nonce        = root.dataset.nonce || '';
    const adminUrl     = root.dataset.adminUrl || '';
    const campaignsUrl = root.dataset.campaignsUrl || `${ adminUrl }admin.php?page=better-payment-admin&tab=campaigns`;

    render(
        <App
            campaignId={ campaignId }
            restUrl={ restUrl }
            nonce={ nonce }
            adminUrl={ adminUrl }
            campaignsUrl={ campaignsUrl }
        />,
        root
    );
}
