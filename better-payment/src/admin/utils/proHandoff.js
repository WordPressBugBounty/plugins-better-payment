/**
 * Slots Better Payment Pro fills in the React admin and the quick-setup wizard.
 *
 * Pro-feature UI moved out of Lite (docs/contributing/extending.md → "Moving Pro
 * code out of Lite"). Lite applies a JS filter where the feature belongs, starting
 * from `null`; Pro 1.4.4+ registers its component on it. A free install renders
 * nothing there. Lite's transitional copies were deleted when
 * `ProCompatibility::MIN_PRO_VERSION` reached 1.4.4.
 */

/**
 * PayPal refund credentials (live/test client ID + secret) on the Settings page.
 * Only Pro's refunds read those keys. Callback: `( node, { mode, settings, onChange } )`.
 */
export const PAYPAL_REFUND_CREDENTIALS_SETTINGS_FILTER =
	'better_payment_settings_paypal_refund_credentials';

/**
 * The same fields in the quick-setup wizard's PayPal step.
 * Callback: `( node, { mode, data, onDataChange } )`.
 */
export const PAYPAL_REFUND_CREDENTIALS_QUICK_SETUP_FILTER =
	'better_payment_quick_setup_paypal_refund_credentials';

/**
 * Extra Settings tabs an add-on registers (Pro's Currencies tab, 2.5.1).
 * Callback: `( tabs ) => tabs`, starting from `[]`. Each tab is
 * `{ id, label, group, icon?, render }`: `id` a slug (`a-z`, `0-9`, `_`, `-`), `label` and `group`
 * (the nav heading it sits under) strings, `icon` an optional node, `render( { registerSave } )` a
 * function returning the tab's content. The tab owns its data: it calls `registerSave( fn )` with a
 * function that saves it (may return a promise; it shows its own notices), and the page header's
 * Save Changes runs that while the tab is open. A free install registers none, so the Settings
 * page is unchanged.
 */
export const SETTINGS_TABS_FILTER = 'better_payment_settings_tabs';

/**
 * The add-on tabs worth rendering: well-formed entries only, first one per id, never one that
 * would take over a built-in tab.
 *
 * @param {any}      tabs     What the filter returned.
 * @param {string[]} reserved Built-in tab ids.
 * @return {{id: string, label: string, group: string, icon: any, render: Function}[]} Tabs.
 */
export const settingsTabsFrom = ( tabs, reserved = [] ) => {
	if ( ! Array.isArray( tabs ) ) {
		return [];
	}

	const seen = new Set( reserved );

	return tabs
		.filter( ( tab ) => {
			const valid =
				typeof tab?.id === 'string' &&
				/^[a-z0-9_-]+$/.test( tab.id ) &&
				typeof tab?.label === 'string' &&
				tab.label !== '' &&
				typeof tab?.render === 'function' &&
				! seen.has( tab.id );

			if ( valid ) {
				seen.add( tab.id );
			}

			return valid;
		} )
		.map( ( tab ) => ( {
			id: tab.id,
			label: tab.label,
			group:
				typeof tab.group === 'string' && tab.group !== ''
					? tab.group
					: tab.label,
			icon: tab.icon ?? null,
			render: tab.render,
		} ) );
};

/**
 * Add-on tabs grouped under their nav heading, in first-seen order.
 *
 * @param {{group: string}[]} tabs Tabs from settingsTabsFrom().
 * @return {{group: string, tabs: Object[]}[]} Groups.
 */
export const groupSettingsTabs = ( tabs ) => {
	const groups = [];

	tabs.forEach( ( tab ) => {
		const existing = groups.find( ( entry ) => entry.group === tab.group );
		if ( existing ) {
			existing.tabs.push( tab );
		} else {
			groups.push( { group: tab.group, tabs: [ tab ] } );
		}
	} );

	return groups;
};

/**
 * The PayPal mode the credential fields belong to. The quick-setup step shows
 * the test fields only when live mode is explicitly `'no'`.
 *
 * @param {Object} settings Settings or wizard data.
 * @return {'live'|'test'} Mode.
 */
export const paypalModeOf = ( settings ) =>
	settings?.better_payment_settings_payment_paypal_live_mode === 'no'
		? 'test'
		: 'live';
