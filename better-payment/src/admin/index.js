import { createRoot } from '@wordpress/element';
import App from './App';
import { watchAdminMenuPin } from './utils/pinMenu';
import './styles/admin.scss';

// Wait for DOM to be ready
document.addEventListener( 'DOMContentLoaded', function () {
	const container = document.getElementById( 'better-payment-admin-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <App /> );
		watchAdminMenuPin();
	}
} );
