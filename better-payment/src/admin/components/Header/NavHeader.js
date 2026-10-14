import { useState, useEffect, useRef } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import betterPaymentLogo from './../../images/logo.png';
import Hamburger from '../../svg/Hamburger';
import Dashboard from '../../svg/Dashboard';
import Campaign from '../../svg/Campaign';
import Transactions from '../../svg/Transactions';
import Subscriptions from '../../svg/Subscriptions';
import Analytics from '../../svg/Analytics';
import Settings from '../../svg/Settings';

const NavHeader = ( {
	currentPage,
	onPageChange,
	isBPlightMode,
	setIsBPlightMode,
} ) => {
	const [ isMenuOpen, setIsMenuOpen ] = useState( false );
	const menuRef = useRef( null );

	// Initialize body class on mount and when mode changes
	useEffect( () => {
		if ( isBPlightMode ) {
			document.body.classList.remove( 'better-body-dark' );
		} else {
			document.body.classList.add( 'better-body-dark' );
		}
	}, [ isBPlightMode ] );

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				menuRef.current &&
				! menuRef.current.contains( event.target ) &&
				isMenuOpen
			) {
				setIsMenuOpen( false );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ isMenuOpen ] );

	const navigationItems = applyFilters( 'better_payment_navigation_items', [
		{
			id: 'dashboard',
			label: __( 'Dashboard', 'better-payment' ),
			dataTab: 'dashboard',
			icon: <Dashboard />,
		},
		{
			id: 'campaigns',
			label: __( 'Campaigns', 'better-payment' ),
			dataTab: 'campaigns',
			icon: <Campaign />,
		},
		{
			id: 'transactions',
			label: __( 'Transactions', 'better-payment' ),
			dataTab: 'transactions',
			icon: <Transactions />,
		},
		{
			id: 'subscriptions',
			label: __( 'Subscriptions', 'better-payment' ),
			dataTab: 'subscriptions',
			icon: <Subscriptions />,
		},
		{
			id: 'analytics',
			label: __( 'Analytics', 'better-payment' ),
			dataTab: 'analytics',
			icon: <Analytics />,
		},
		{
			id: 'settings',
			label: __( 'Settings', 'better-payment' ),
			dataTab: 'settings',
			icon: <Settings />,
		},
	] );

	const handleTabClick = ( tabId ) => {
		onPageChange( tabId );
		setIsMenuOpen( false );
	};

	const handleModeChange = () => {
		const newMode = ! isBPlightMode;
		setIsBPlightMode( newMode );
		localStorage.setItem( 'isBPlightMode', newMode );

		// Add or remove dark mode class from body
		if ( newMode ) {
			document.body.classList.remove( 'better-body-dark' );
		} else {
			document.body.classList.add( 'better-body-dark' );
		}
	};

	return (
		<div className="section header-section">
			<div className="bp-container">
				<div className="margin-bottom_20 border-radius_12 bp-header_wrapper">
					<div className="bp-row align-items_center justify-content-space_between-active_1180">
						<div className="bp-col bp-col_3 bp-col_50-active_992">
							<div className="bp-flex align-items_center bp-logo">
								<img
									src={ betterPaymentLogo }
									alt="logo"
									className="bp-logo_image"
								/>
								<h2 className="bp-logo_name">Better Payment</h2>
							</div>
						</div>
						<div className="bp-col bp-col_8  bp-menu_position">
							<div
								className={ `bp-menu ${
									isMenuOpen ? '' : 'bp-hidden-xs'
								}` }
								ref={ menuRef }
							>
								<div className="bp-flex align-items_center justify-content-space_between bp-menu_wrapper">
									<ul className="bp-menu_list">
										{ navigationItems.map( ( item ) => (
											<li
												key={ item.dataTab }
												className={ classNames(
													'tab-item bp-flex align-items_center border-radius_12 bp-menu_list-item',
													{
														'bp-tab_active':
															currentPage ===
															item.dataTab,
													}
												) }
												data-tab={ item.dataTab }
												onClick={ () =>
													handleTabClick(
														item.dataTab
													)
												}
												style={ { cursor: 'pointer' } }
											>
												<span className="line-height_0 bp-menu_list-item_icon">
													{ item.icon }
												</span>
												<span className="fx-5 bp-font_inter bp-menu_list-item_text">
													{ item.label }
												</span>
											</li>
										) ) }
									</ul>
								</div>
							</div>
						</div>
						<div className="bp-col bp-col_1 bp-col_50-active_1180">
							<div className="bp-flex mood-wrapper">
								<button
									className="bp-btn bp-sun_button"
									onClick={ handleModeChange }
								>
									<svg
										width="24"
										height="24"
										viewBox="0 0 24 24"
										fill="none"
										xmlns="http://www.w3.org/2000/svg"
									>
										<path
											d="M12 1V3M12 21V23M4.22 4.22L5.64 5.64M18.36 18.36L19.78 19.78M1 12H3M21 12H23M4.22 19.78L5.64 18.36M18.36 5.64L19.78 4.22M17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12Z"
											stroke="#8F9AB0"
											strokeWidth="2"
											strokeLinecap="round"
											strokeLinejoin="round"
										/>
									</svg>
								</button>
								<div className="bp-hamberger-wrapper">
									<button
										className="bp-btn bp-menu_icon bp-visible-xs"
										onClick={ () =>
											setIsMenuOpen( ! isMenuOpen )
										}
									>
										<Hamburger />
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default NavHeader;
