/**
 * The MCP Server tab's connection tools: the connection test, the list of OAuth
 * connections (revoke one, or disconnect all), and the data behind the stat cards.
 *
 * OAuth grants have no other home in wp-admin. An Application Password has its own
 * screen under Users; a client approved on the consent screen is listed only here.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { apiMutation } from '../../utils/api';
import {
	connectionDate,
	connectionDateTime,
	connectionIdentity,
	connectionScopeLabel,
} from '../../utils/mcpConnections';

/**
 * The connections list and last-activity time, from one route.
 *
 * Fetched only while `live`: the route exists only once MCP is saved on, and
 * asking before that answers "No route was found".
 *
 * @param {boolean} live Whether the MCP routes are registered.
 * @return {Object} State and actions.
 */
export const useMcpConnections = ( live ) => {
	const [ connections, setConnections ] = useState( null );
	const [ lastActivity, setLastActivity ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	const apply = ( data ) => {
		setConnections( data?.connections ?? [] );
		setLastActivity( data?.last_activity ?? '' );
	};

	const refresh = useCallback( async () => {
		setLoading( true );
		setError( '' );

		try {
			apply( await apiMutation( 'mcp/connections' ) );
		} catch ( e ) {
			setConnections( ( prev ) => prev ?? [] );
			setError( e?.message ?? '' );
		}

		setLoading( false );
	}, [] );

	useEffect( () => {
		if ( live ) {
			refresh();
		}
	}, [ live, refresh ] );

	const revoke = async ( family ) => {
		setError( '' );

		try {
			apply(
				await apiMutation( `mcp/connections/${ family }`, {
					method: 'DELETE',
				} )
			);
		} catch ( e ) {
			setError(
				e?.message ??
					__(
						'The connection could not be revoked.',
						'better-payment'
					)
			);
		}
	};

	const revokeAll = async () => {
		setError( '' );

		try {
			apply(
				await apiMutation( 'mcp/connections', { method: 'DELETE' } )
			);
		} catch ( e ) {
			setError(
				e?.message ??
					__(
						'The connections could not be revoked.',
						'better-payment'
					)
			);
		}
	};

	return {
		connections,
		lastActivity,
		loading,
		error,
		refresh,
		revoke,
		revokeAll,
	};
};

/**
 * A destructive button that asks again in place, instead of window.confirm(),
 * which blocks the page and ignores the screen's styling.
 *
 * @param {Object}   props
 * @param {string}   props.label        Button text.
 * @param {string}   props.confirmText  Short warning shown beside the confirm.
 * @param {string}   props.confirmLabel Confirm button text.
 * @param {string}   props.busyLabel    Confirm button text while it runs.
 * @param {Function} props.onConfirm    Async action.
 * @param {string}   props.variant      `danger` (outline) or `danger-solid`.
 */
export const ConfirmButton = ( {
	label,
	confirmText,
	confirmLabel,
	busyLabel,
	onConfirm,
	variant = 'danger',
} ) => {
	const [ asking, setAsking ] = useState( false );
	const [ busy, setBusy ] = useState( false );

	if ( ! asking ) {
		return (
			<button
				type="button"
				className={ `bp-mcp__button bp-mcp__button--${ variant }` }
				onClick={ () => setAsking( true ) }
			>
				{ label }
			</button>
		);
	}

	return (
		<span className="bp-mcp__confirm" role="group" aria-label={ label }>
			<span className="bp-mcp__confirm-text">{ confirmText }</span>
			<button
				type="button"
				className="bp-mcp__button bp-mcp__button--secondary bp-mcp__button--small"
				disabled={ busy }
				onClick={ () => setAsking( false ) }
			>
				{ __( 'Cancel', 'better-payment' ) }
			</button>
			<button
				type="button"
				className="bp-mcp__button bp-mcp__button--danger-solid bp-mcp__button--small"
				disabled={ busy }
				onClick={ async () => {
					setBusy( true );
					await onConfirm();
					setBusy( false );
					setAsking( false );
				} }
			>
				{ busy ? busyLabel : confirmLabel }
			</button>
		</span>
	);
};

export const McpTestConnection = () => {
	const [ running, setRunning ] = useState( false );
	const [ result, setResult ] = useState( null );
	const [ error, setError ] = useState( '' );

	const run = async () => {
		setRunning( true );
		setError( '' );
		setResult( null );

		try {
			setResult(
				await apiMutation( 'mcp/self-test', { method: 'POST' } )
			);
		} catch ( e ) {
			setError(
				e?.message ?? __( 'The test could not run.', 'better-payment' )
			);
		}

		setRunning( false );
	};

	return (
		<div className="bp-mcp__test">
			<button
				type="button"
				className="bp-mcp__button bp-mcp__button--primary"
				disabled={ running }
				onClick={ run }
			>
				{ running
					? __( 'Testing…', 'better-payment' )
					: __( 'Test connection', 'better-payment' ) }
			</button>

			{ error && (
				<p className="bp-mcp__notice bp-mcp__notice--warning">
					{ error }
				</p>
			) }

			{ result?.checks?.length > 0 && (
				<ul className="bp-mcp__checks">
					{ result.checks.map( ( check ) => (
						<li
							key={ check.id }
							className={ check.ok ? 'is-ok' : 'is-failed' }
						>
							<span
								className="bp-mcp__check-icon"
								aria-hidden="true"
							>
								{ check.ok ? '✓' : '✕' }
							</span>
							<span>
								<strong>{ check.label }</strong>{ ' ' }
								{ check.message }
							</span>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
};

/**
 * The approved applications, one row each with its own Revoke.
 *
 * @param {Object}   props
 * @param {Array}    props.connections Connections, or null while loading.
 * @param {string}   props.error       Last error.
 * @param {Function} props.onRevoke    Receives the connection family.
 */
export const McpConnectionList = ( { connections, error, onRevoke } ) => (
	<div className="bp-mcp__connections-body">
		{ error && (
			<p className="bp-mcp__notice bp-mcp__notice--warning">{ error }</p>
		) }

		{ connections === null && ! error && (
			<div className="bp-mcp__skeleton" aria-hidden="true" />
		) }

		{ connections?.length === 0 && ! error && (
			<p className="bp-mcp__empty">
				{ __(
					'No application has been approved yet.',
					'better-payment'
				) }
			</p>
		) }

		{ connections?.length > 0 && (
			<ul className="bp-mcp__connection-list">
				{ connections.map( ( connection ) => {
					const identity = connectionIdentity( connection );
					const connected = connectionDateTime(
						connection.connected
					);

					return (
						<li key={ connection.family }>
							<span className="bp-mcp__connection-main">
								<strong>
									{ identity.title }
									<span
										className={ `bp-mcp__pill ${
											connection.scope?.includes(
												'write'
											)
												? 'bp-mcp__pill--write'
												: 'bp-mcp__pill--read'
										}` }
									>
										{ connectionScopeLabel(
											connection.scope
										) }
									</span>
									{ identity.warning && (
										<span className="bp-mcp__pill bp-mcp__pill--unverified">
											{ __(
												'Unverified',
												'better-payment'
											) }
										</span>
									) }
								</strong>
								{ ( identity.surface ||
									identity.registeredAs ) && (
									<span className="bp-mcp__connection-surface">
										{ identity.surface }
										{ identity.surface &&
											identity.registeredAs &&
											' · ' }
										{ identity.registeredAs && (
											<span
												title={ __(
													'The name this application registered with',
													'better-payment'
												) }
											>
												{ sprintf(
													/* translators: %s: name the client registered with. */
													__(
														'Registered as “%s”',
														'better-payment'
													),
													identity.registeredAs
												) }
											</span>
										) }
									</span>
								) }
								{ identity.warning && (
									<span className="bp-mcp__connection-warning">
										{ identity.warning }
									</span>
								) }
								<span className="bp-mcp__help">
									{ connection.user }
									{ connected && (
										<>
											{ ' · ' }
											{ __(
												'Connected',
												'better-payment'
											) }{ ' ' }
											{ connected }
										</>
									) }
									{ ' · ' }
									{ __(
										'Last active',
										'better-payment'
									) }{ ' ' }
									{ connectionDate(
										connection.last_refreshed
									) }
								</span>
							</span>
							<ConfirmButton
								label={ __( 'Revoke', 'better-payment' ) }
								confirmText={ __(
									'Stops working at once.',
									'better-payment'
								) }
								confirmLabel={ __(
									'Yes, revoke',
									'better-payment'
								) }
								busyLabel={ __(
									'Revoking…',
									'better-payment'
								) }
								onConfirm={ () =>
									onRevoke( connection.family )
								}
							/>
						</li>
					);
				} ) }
			</ul>
		) }
	</div>
);
