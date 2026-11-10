import { __, sprintf } from '@wordpress/i18n';

/**
 * What one OAuth connection may do, in the owner's words.
 *
 * @param {string[]} scope Granted scopes.
 * @return {string} Label.
 */
export const connectionScopeLabel = ( scope ) =>
	scope?.includes( 'write' )
		? __( 'Read & write', 'better-payment' )
		: __( 'Read only', 'better-payment' );

/**
 * A stored timestamp as a local date.
 *
 * @param {number} timestamp Unix timestamp in seconds.
 * @return {string} Formatted date, or '' when there is none.
 */
export const connectionDate = ( timestamp ) =>
	timestamp ? new Date( timestamp * 1000 ).toLocaleDateString() : '';

/**
 * A stored timestamp as a local date and time ("09/11/2026, 14:05").
 *
 * The time matters here: one account can approve the same application twice
 * (two Claude connectors), and the moment each was approved is what tells the
 * two rows apart.
 *
 * @param {number} timestamp Unix timestamp in seconds.
 * @return {string} Formatted date and time, or '' when there is none.
 */
export const connectionDateTime = ( timestamp ) =>
	timestamp
		? new Date( timestamp * 1000 ).toLocaleString( undefined, {
				dateStyle: 'medium',
				timeStyle: 'short',
		  } )
		: '';

/**
 * What one row of Connected applications says, from the server's identity fields.
 *
 * `title` is the application as the owner knows it. `registeredAs` is the name
 * the client registered, shown only when it differs from the title and adds
 * something (Claude Code registers "Claude Code (better-payment)"). `warning` is
 * set for a client whose name claims an application its sign-in redirect does
 * not back: the server keeps the registered name as the title then.
 *
 * @param {Object} connection One entry of the connections route.
 * @return {{title: string, surface: string, registeredAs: string, warning: string, verified: boolean}} Row text.
 */
export const connectionIdentity = ( connection = {} ) => {
	const title =
		connection?.client || __( 'Unnamed client', 'better-payment' );
	const registered = ( connection?.registered_name ?? '' ).trim();
	const claims = connection?.claims ?? '';
	const host = connection?.redirect_host ?? '';

	let warning = '';

	if ( claims ) {
		warning = host
			? sprintf(
					/* translators: 1: application name, e.g. Claude. 2: host name. */
					__(
						'Calls itself %1$s, but signs in through %2$s. Revoke it unless you connected it yourself.',
						'better-payment'
					),
					claims,
					host
			  )
			: sprintf(
					/* translators: %s: application name, e.g. Claude. */
					__(
						'Calls itself %s, but its sign-in address does not belong to it. Revoke it unless you connected it yourself.',
						'better-payment'
					),
					claims
			  );
	}

	return {
		title,
		surface: connection?.surface ?? '',
		registeredAs:
			registered && registered.toLowerCase() !== title.toLowerCase()
				? registered
				: '',
		warning,
		verified: !! connection?.verified,
	};
};

/**
 * Which parts of the MCP panel can work right now.
 *
 * The server's REST routes exist only once the setting is SAVED as on
 * (`MCPManager::register_routes()` re-reads the stored option), so a panel that
 * asks for its connections straight after the toggle is flipped gets WordPress's
 * "No route was found" error. `live` says the saved value is on; the panel shows
 * the address and snippets from `enabled` alone, and the route-backed tools
 * (connections, connection test) only while `live`.
 *
 * @param {Object} values                 Setting values.
 * @param {string} values.value           MCP toggle, as edited.
 * @param {string} values.savedValue      MCP toggle, as saved.
 * @param {string} values.oauthValue      OAuth toggle, as edited.
 * @param {string} values.savedOauthValue OAuth toggle, as saved.
 * @return {{enabled: boolean, live: boolean, oauthEnabled: boolean, oauthLive: boolean, unsaved: boolean}} State.
 */
export const mcpPanelState = ( {
	value,
	savedValue,
	oauthValue,
	savedOauthValue,
} = {} ) => {
	const enabled = value === 'yes';
	const live = enabled && savedValue === 'yes';
	const oauthEnabled = oauthValue === 'yes';
	const oauthLive = live && oauthEnabled && savedOauthValue === 'yes';

	return {
		enabled,
		live,
		oauthEnabled,
		oauthLive,
		unsaved:
			enabled &&
			( savedValue !== 'yes' ||
				oauthEnabled !== ( savedOauthValue === 'yes' ) ),
	};
};

/**
 * A past time as "5 minutes ago", in the browser's language.
 *
 * @param {string} iso ISO 8601 time.
 * @param {number} now Current time in milliseconds (for tests).
 * @return {string} Relative time, or '' for no or an unreadable time.
 */
export const relativeTime = ( iso, now = Date.now() ) => {
	const then = iso ? Date.parse( iso ) : NaN;

	if ( Number.isNaN( then ) ) {
		return '';
	}

	const seconds = Math.round( ( then - now ) / 1000 );
	const units = [
		[ 'year', 31536000 ],
		[ 'month', 2592000 ],
		[ 'week', 604800 ],
		[ 'day', 86400 ],
		[ 'hour', 3600 ],
		[ 'minute', 60 ],
	];
	const format = new Intl.RelativeTimeFormat( undefined, {
		numeric: 'auto',
	} );

	for ( const [ unit, size ] of units ) {
		if ( Math.abs( seconds ) >= size ) {
			return format.format( Math.round( seconds / size ), unit );
		}
	}

	return format.format( 0, 'second' );
};

/**
 * The note printed after the endpoint in Connection health: the MCP revision the
 * server speaks, for support ("which protocol does your server use?").
 *
 * @param {Object} info `betterPaymentAdmin.mcp`.
 * @return {string} "MCP 2025-06-18", or '' when unknown.
 */
export const mcpEndpointMeta = ( info = {} ) =>
	info?.protocol ? `MCP ${ info.protocol }` : '';

/**
 * The Connection health list: what this site does and does not give a client.
 *
 * `ok: false` is something to fix; `ok: null` is information (no right answer).
 * The protocol revision is not a check: the owner can do nothing with it, so it
 * sits beside the endpoint line instead (`mcpEndpointMeta()`).
 *
 * @param {Object}  info         `betterPaymentAdmin.mcp`.
 * @param {boolean} oauthEnabled OAuth switch.
 * @return {Array<{id: string, ok: (boolean|null), label: string}>} Checks.
 */
export const mcpHealthChecks = ( info = {}, oauthEnabled = false ) => {
	const checks = [
		{
			id: 'https',
			ok: !! info?.isSsl,
			label: info?.isSsl
				? __( 'Secure connection (HTTPS) is on.', 'better-payment' )
				: __(
						'Not served over HTTPS: Application Passwords are refused and hosted clients cannot connect.',
						'better-payment'
				  ),
		},
	];

	if ( oauthEnabled ) {
		checks.push( {
			id: 'discovery',
			ok: !! info?.discoveryReady,
			label: info?.discoveryReady
				? __(
						'OAuth discovery documents are published.',
						'better-payment'
				  )
				: __(
						'Plain permalinks: OAuth discovery has no address. Switch permalinks under Settings → Permalinks.',
						'better-payment'
				  ),
		} );
	} else {
		checks.push( {
			id: 'oauth',
			ok: null,
			label: __(
				'OAuth sign-in is off: only Application Passwords can connect.',
				'better-payment'
			),
		} );
	}

	return checks;
};

/**
 * The setup guide the MCP tab's hero links to, from `betterPaymentAdmin.mcp`.
 *
 * The server owns the address (`ConnectGuide::docs_url()`, filterable); this only
 * refuses to render anything that is not an https URL, so a bad filter value can
 * never become a `javascript:` link.
 *
 * @param {Object} info `betterPaymentAdmin.mcp`.
 * @return {string} URL, or '' to show no link.
 */
export const mcpDocsUrl = ( info = {} ) => {
	const url = typeof info?.docsUrl === 'string' ? info.docsUrl.trim() : '';

	return /^https:\/\/[^\s]+$/i.test( url ) ? url : '';
};
