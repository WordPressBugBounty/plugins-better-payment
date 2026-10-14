import { useState, useEffect, useRef } from '@wordpress/element';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import Dashboard from './pages/Dashboard';
import Transactions from './pages/Transactions';
import Subscriptions from './pages/Subscriptions';
import Analytics from './pages/Analytics';
import Settings from './pages/Settings';
import Campaigns from './pages/Campaigns';
import NavHeader from './components/Header/NavHeader';
import SaleInfo from './components/Header/SaleInfo';
import { applyFilters } from '@wordpress/hooks';
import { scrollAdminToTop } from './utils/scroll';

function App() {
	// Read the tab from the URL up front, so the first render is the right
	// page and a page load is never mistaken for a tab switch below.
	const [ currentPage, setCurrentPage ] = useState(
		() =>
			new URLSearchParams( window.location.search ).get( 'tab' ) ||
			window.location.hash.replace( '#', '' ) ||
			'dashboard'
	);
	const previousPage = useRef( currentPage );
	const [ isBPlightMode, setIsBPlightMode ] = useState(
		localStorage.getItem( 'isBPlightMode' ) !== 'false'
	);

	// Expose toast instance globally for Better Payment Pro plugin to use
	useEffect( () => {
		window.__BETTER_PAYMENT_TOAST__ = toast;
	}, [] );

	const mainClass = isBPlightMode
		? 'better-payment'
		: 'better-payment better-payment-dark';

	// Listen for WP submenu SPA navigation and browser back/forward
	useEffect( () => {
		function onNavigate( e ) {
			const tab = e && e.detail ? e.detail.tab : null;
			if ( tab ) setCurrentPage( tab );
		}
		function syncFromUrl() {
			const params = new URLSearchParams( window.location.search );
			const tab = params.get( 'tab' ) || 'dashboard';
			setCurrentPage( tab );
		}
		window.addEventListener( 'bp:navigate', onNavigate );
		window.addEventListener( 'popstate', syncFromUrl );
		return () => {
			window.removeEventListener( 'bp:navigate', onNavigate );
			window.removeEventListener( 'popstate', syncFromUrl );
		};
	}, [] );

	// Update URL and notify WP menu when page changes
	useEffect( () => {
		// A tab switch loads no new page, so the browser keeps the old scroll
		// position — start the new tab at the top, as a real page load would.
		if ( previousPage.current !== currentPage ) {
			previousPage.current = currentPage;
			scrollAdminToTop();
		}

		const url = new URL( window.location );
		const currentTab = url.searchParams.get( 'tab' ) || 'dashboard';
		if ( currentTab !== currentPage ) {
			url.searchParams.set( 'tab', currentPage );

			// Clear details-view parameters on any real tab switch — both the
			// Transactions and Subscriptions tabs write action/id(/source)
			// when a details page opens, and neither reads them back on
			// mount, so stale ones must not ride along to the next tab.
			url.searchParams.delete( 'action' );
			url.searchParams.delete( 'id' );
			url.searchParams.delete( 'source' );

			window.history.pushState( {}, '', url );
		}
		// Let PHP-inserted script update WP submenu active state
		window.dispatchEvent(
			new CustomEvent( 'bp:page-changed', {
				detail: { tab: currentPage },
			} )
		);
	}, [ currentPage ] );

	const renderCurrentPage = () => {
		switch ( currentPage ) {
			case 'transactions':
				return <Transactions />;
			case 'subscriptions':
				return <Subscriptions />;
			case 'analytics':
				return <Analytics />;
			case 'settings':
				return <Settings />;
			case 'campaigns':
				return <Campaigns />;
			case 'license':
				return applyFilters( 'better_payment_license_page', null );
			case 'dashboard':
			default:
				return <Dashboard onPageChange={ setCurrentPage } />;
		}
	};

	return (
		<div className={ mainClass }>
			<ToastContainer
				position="bottom-right"
				autoClose={ 2000 }
				hideProgressBar={ false }
				newestOnTop={ false }
				closeOnClick
				rtl={ false }
				pauseOnFocusLoss
				draggable
				pauseOnHover
			/>
			<SaleInfo />
			<NavHeader
				currentPage={ currentPage }
				onPageChange={ setCurrentPage }
				isBPlightMode={ isBPlightMode }
				setIsBPlightMode={ setIsBPlightMode }
			/>
			{ renderCurrentPage() }
		</div>
	);
}

export default App;
