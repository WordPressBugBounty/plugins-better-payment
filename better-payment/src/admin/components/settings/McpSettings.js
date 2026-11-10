/**
 * Settings → MCP Server tab.
 *
 * Better Payment is an MCP server: one URL that any MCP client connects to. The job
 * of this panel is to make that one URL usable without the site owner having to know
 * how their particular client is configured — so it shows the finished snippet for
 * each client rather than documenting the protocol.
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	ConfirmButton,
	McpConnectionList,
	McpTestConnection,
	useMcpConnections,
} from './McpConnections';
import {
	mcpDocsUrl,
	mcpEndpointMeta,
	mcpHealthChecks,
	mcpPanelState,
	relativeTime,
} from '../../utils/mcpConnections';
import Collapse from './Collapse';

const mcp = () => window.betterPaymentAdmin?.mcp ?? {};

/**
 * Copy a string, falling back to a hidden textarea where the async clipboard API is
 * unavailable — wp-admin is served over plain HTTP on plenty of local installs, and
 * navigator.clipboard does not exist in an insecure context.
 *
 * @param {string} text Text to copy.
 * @return {Promise<boolean>} Whether the copy succeeded.
 */
const copyText = async ( text ) => {
	try {
		if ( window.navigator?.clipboard?.writeText ) {
			await window.navigator.clipboard.writeText( text );
			return true;
		}
	} catch ( e ) {
		// Fall through to the textarea.
	}

	try {
		const field = document.createElement( 'textarea' );
		field.value = text;
		field.setAttribute( 'readonly', '' );
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild( field );
		field.select();
		const ok = document.execCommand( 'copy' );
		document.body.removeChild( field );
		return ok;
	} catch ( e ) {
		return false;
	}
};

const CopyButton = ( { value, label, className = '' } ) => {
	const [ copied, setCopied ] = useState( false );

	return (
		<button
			type="button"
			className={ `bp-mcp__button bp-mcp__button--secondary ${ className }` }
			onClick={ async () => {
				const ok = await copyText( value );
				setCopied( ok );
				window.setTimeout( () => setCopied( false ), 2000 );
			} }
		>
			{ copied
				? __( 'Copied', 'better-payment' )
				: label ?? __( 'Copy', 'better-payment' ) }
		</button>
	);
};

/**
 * The Enabled/Disabled switch box every settings feature row uses, so the MCP
 * and OAuth switches read exactly like their neighbours.
 *
 * @param {Object}   props
 * @param {boolean}  props.checked  Whether the switch is on.
 * @param {Function} props.onChange Receives 'yes' or 'no'.
 * @param {string}   props.label    Accessible name of the checkbox.
 * @param {string}   props.id       Checkbox id, unique on the page. A fixed
 *                                  string rather than `useId()`: WordPress 6.0
 *                                  ships React 17, which has no `useId`.
 */
export const SettingSwitch = ( { checked, onChange, label, id } ) => (
	<div className="bp-content_wrapper">
		<form
			action="#"
			onSubmit={ ( e ) => e.preventDefault() }
			className={ `bp-flex align-items_center justify-content-space_between bp-content_setting ${
				checked ? 'enabled_switch' : ''
			}` }
		>
			<span className="bp-font_ibm fx-4 bp-content_status">
				{ checked
					? __( 'Enabled', 'better-payment' )
					: __( 'Disabled', 'better-payment' ) }
			</span>
			<label className="bp-toggle_switch" htmlFor={ id }>
				<input
					id={ id }
					type="checkbox"
					className="bp-toggle_switch-checkbox"
					checked={ checked }
					onChange={ ( e ) =>
						onChange( e.target.checked ? 'yes' : 'no' )
					}
				/>
				<span className="bp-slider bp-round"></span>
				<span className="screen-reader-text">{ label }</span>
			</label>
		</form>
	</div>
);

const Icon = ( { name } ) => {
	const paths = {
		server: (
			<>
				<rect x="3" y="4" width="18" height="7" rx="2" />
				<rect x="3" y="13" width="18" height="7" rx="2" />
				<path d="M7 7.5h.01M7 16.5h.01" />
			</>
		),
		power: (
			<>
				<path d="M12 3v8" />
				<path d="M6.4 6.4a8 8 0 1 0 11.2 0" />
			</>
		),
		copy: (
			<>
				<rect x="9" y="9" width="11" height="11" rx="2" />
				<path d="M5 15V6a2 2 0 0 1 2-2h9" />
			</>
		),
		check: (
			<>
				<path d="M12 3 4 6v6c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V6l-8-3Z" />
				<path d="m9 12 2 2 4-4" />
			</>
		),
		tools: (
			<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z" />
		),
		apps: (
			<>
				<rect x="3" y="3" width="7" height="7" rx="1.5" />
				<rect x="14" y="3" width="7" height="7" rx="1.5" />
				<rect x="3" y="14" width="7" height="7" rx="1.5" />
				<rect x="14" y="14" width="7" height="7" rx="1.5" />
			</>
		),
		clock: (
			<>
				<circle cx="12" cy="12" r="9" />
				<path d="M12 7v5l3 2" />
			</>
		),
	};

	return (
		<svg
			className="bp-mcp__icon"
			width="18"
			height="18"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.8"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			focusable="false"
		>
			{ paths[ name ] }
		</svg>
	);
};

const Card = ( { title, help, action, children, className = '' } ) => (
	<section className={ `bp-mcp__card ${ className }` }>
		<header className="bp-mcp__card-head">
			<div>
				<h4 className="bp-font_ibm bp-mcp__card-title">{ title }</h4>
				{ help && <p className="bp-mcp__help">{ help }</p> }
			</div>
			{ action && <div className="bp-mcp__card-action">{ action }</div> }
		</header>
		<div className="bp-mcp__card-body">{ children }</div>
	</section>
);

const Stat = ( { icon, label, value, sub } ) => (
	<div className="bp-mcp__stat">
		<span className="bp-mcp__stat-head">
			<span className="bp-mcp__stat-label">{ label }</span>
			<Icon name={ icon } />
		</span>
		<span className="bp-mcp__stat-value">{ value }</span>
		{ sub && <span className="bp-mcp__stat-sub">{ sub }</span> }
	</div>
);

/**
 * The hero's status pill.
 *
 * @param {boolean} enabled Switch as edited.
 * @param {boolean} live    Switch saved on.
 * @return {{key: string, label: string}} Pill.
 */
const serverStatus = ( enabled, live ) => {
	if ( live ) {
		return { key: 'active', label: __( 'Active', 'better-payment' ) };
	}

	if ( enabled ) {
		return { key: 'pending', label: __( 'Not saved', 'better-payment' ) };
	}

	return { key: 'off', label: __( 'Off', 'better-payment' ) };
};

/**
 * A health row's tone: `null` is information, not a pass or a fail.
 *
 * @param {boolean|null} ok Check result.
 * @return {string} Tone.
 */
const checkTone = ( ok ) => {
	if ( ok === null ) {
		return 'info';
	}

	return ok ? 'ok' : 'warn';
};

const STEPS = [
	{
		icon: 'power',
		title: __( 'Turn MCP on', 'better-payment' ),
		text: __( 'Flip the switch below and save.', 'better-payment' ),
	},
	{
		icon: 'copy',
		title: __( 'Copy the server URL', 'better-payment' ),
		text: __( 'One URL for every client.', 'better-payment' ),
	},
	{
		icon: 'check',
		title: __( 'Approve the client', 'better-payment' ),
		text: __(
			'Add it in Claude, ChatGPT or Cursor and confirm.',
			'better-payment'
		),
	},
];

const McpSettings = ( {
	value,
	savedValue,
	onChange,
	oauthValue,
	savedOauthValue,
	onOauthChange,
} ) => {
	const info = mcp();
	const clients = info?.clients ?? [];
	const [ activeClient, setActiveClient ] = useState(
		clients[ 0 ]?.id ?? ''
	);

	const { enabled, live, oauthEnabled, oauthLive, unsaved } = mcpPanelState( {
		value,
		savedValue,
		oauthValue,
		savedOauthValue,
	} );
	const {
		connections,
		lastActivity,
		loading,
		error,
		refresh,
		revoke,
		revokeAll,
	} = useMcpConnections( live );

	const current =
		clients.find( ( client ) => client.id === activeClient ) ??
		clients[ 0 ];
	const status = serverStatus( enabled, live );
	const docsUrl = mcpDocsUrl( info );
	const appCount = connections?.length ?? 0;

	return (
		<div className="bp-mcp">
			<div className="bp-mcp__hero">
				<div className="bp-mcp__hero-main">
					<span className="bp-mcp__hero-icon">
						<Icon name="server" />
					</span>
					<div>
						<h3 className="bp-font_ibm bp-mcp__hero-title">
							{ __(
								'Run Better Payment from your AI assistant',
								'better-payment'
							) }
							<span
								className={ `bp-mcp__status bp-mcp__status--${ status.key }` }
							>
								{ status.label }
							</span>
						</h3>
						<p className="bp-mcp__help">
							{ __(
								'A built-in MCP server lets Claude, ChatGPT, Cursor and other assistants read your payments and manage your campaigns in plain language, without leaving the chat. It stays off until you switch it on, and only administrators can connect.',
								'better-payment'
							) }
						</p>
						{ docsUrl && (
							<a
								className="bp-mcp__learn"
								href={ docsUrl }
								target="_blank"
								rel="noopener noreferrer"
							>
								<span className="bp-mcp__learn-text">
									{ __(
										'Learn how it works',
										'better-payment'
									) }
								</span>
								<span
									className="bp-mcp__learn-arrow"
									aria-hidden="true"
								>
									→
								</span>
								<span className="screen-reader-text">
									{ __(
										'(opens in a new tab)',
										'better-payment'
									) }
								</span>
							</a>
						) }
					</div>
				</div>
				<ol className="bp-mcp__steps">
					{ STEPS.map( ( step ) => (
						<li key={ step.icon }>
							<span className="bp-mcp__step-icon">
								<Icon name={ step.icon } />
							</span>
							<span>
								<strong>{ step.title }</strong>
								<span className="bp-mcp__help">
									{ step.text }
								</span>
							</span>
						</li>
					) ) }
				</ol>
			</div>

			<div className="bp-flex justify-content-space_between align-items_center bp-general_features bp-mcp__enable">
				<div className="bp-text_wrapper">
					<h4 className="bp-font_ibm bp-content_header">
						{ __( 'Enable MCP access', 'better-payment' ) }
					</h4>
					<p className="bp-font_ibm fx-5 bp-content_sub-header">
						{ __(
							'When enabled, approved AI assistants can connect to this site to read payments and manage campaigns. Off by default.',
							'better-payment'
						) }
					</p>
				</div>
				<SettingSwitch
					id="bp-mcp-enabled"
					checked={ enabled }
					onChange={ onChange }
					label={ __( 'Enable MCP access', 'better-payment' ) }
				/>
			</div>

			<Collapse open={ enabled }>
				<div className="bp-mcp__body">
					<Collapse open={ unsaved }>
						<p className="bp-mcp__notice bp-mcp__notice--info">
							{ __(
								'Save changes to apply. The stats, connected applications and the connection test appear once the settings are saved.',
								'better-payment'
							) }
						</p>
					</Collapse>

					<Collapse open={ live }>
						<div className="bp-mcp__stats">
							<Stat
								icon="server"
								label={ __(
									'Server status',
									'better-payment'
								) }
								value={
									<>
										<span className="bp-mcp__dot" />
										{ __( 'Active', 'better-payment' ) }
									</>
								}
								sub={ __(
									'Answering at the URL below',
									'better-payment'
								) }
							/>
							<Stat
								icon="tools"
								label={ __(
									'Tools exposed',
									'better-payment'
								) }
								value={ info?.toolCount ?? 0 }
								sub={ __(
									'Payments, campaigns, settings',
									'better-payment'
								) }
							/>
							<Stat
								icon="apps"
								label={ __(
									'Connected apps',
									'better-payment'
								) }
								value={ oauthLive ? appCount : '—' }
								sub={
									oauthLive
										? __(
												'Approved over OAuth',
												'better-payment'
										  )
										: __( 'OAuth is off', 'better-payment' )
								}
							/>
							<Stat
								icon="clock"
								label={ __(
									'Last activity',
									'better-payment'
								) }
								value={
									relativeTime( lastActivity ) ||
									__( 'No calls yet', 'better-payment' )
								}
								sub={ __(
									'Most recent tool call',
									'better-payment'
								) }
							/>
						</div>
					</Collapse>

					<Card
						className="bp-mcp__connection"
						title={ __( 'Connection', 'better-payment' ) }
						help={ __(
							'Give the server URL to your MCP client, and choose how clients sign in.',
							'better-payment'
						) }
					>
						<div className="bp-mcp__connection-grid">
							<div className="bp-mcp__tile">
								<span className="bp-mcp__label">
									{ __( 'Server URL', 'better-payment' ) }
								</span>
								<div className="bp-mcp__field">
									<input
										type="text"
										className="bp-mcp__endpoint-input"
										readOnly
										value={ info?.endpoint ?? '' }
										onFocus={ ( e ) => e.target.select() }
									/>
									<CopyButton
										value={ info?.endpoint ?? '' }
									/>
								</div>
								<p className="bp-mcp__help">
									{ __(
										'Add this URL as a custom connector in your AI client.',
										'better-payment'
									) }
								</p>
							</div>

							<div className="bp-mcp__tile">
								<span className="bp-mcp__label">
									{ __(
										'How clients sign in',
										'better-payment'
									) }
								</span>
								<div className="bp-mcp__oauth">
									<span>
										<strong>
											{ __(
												'OAuth 2.1 sign-in',
												'better-payment'
											) }
										</strong>
										<span className="bp-mcp__help">
											{ __(
												'Needed by Claude and ChatGPT. You approve each connection in your browser.',
												'better-payment'
											) }
										</span>
									</span>
									<SettingSwitch
										id="bp-mcp-oauth"
										checked={ oauthEnabled }
										onChange={ onOauthChange }
										label={ __(
											'OAuth 2.1 sign-in',
											'better-payment'
										) }
									/>
								</div>
								<p className="bp-mcp__help">
									{ __(
										'Command-line clients can use an Application Password instead.',
										'better-payment'
									) }{ ' ' }
									<a
										className="bp-mcp__link"
										href={ info?.appPasswordsUrl ?? '#' }
										target="_blank"
										rel="noreferrer"
									>
										{ __(
											'Create an Application Password',
											'better-payment'
										) }
									</a>
								</p>
							</div>
						</div>

						<Collapse open={ live }>
							<McpTestConnection />
						</Collapse>
					</Card>

					{ clients.length > 0 && (
						<Card
							className="bp-mcp__clients"
							title={ __( 'Connect a client', 'better-payment' ) }
							help={ __(
								'Pick your client and copy the ready-made setup.',
								'better-payment'
							) }
						>
							<div
								className="bp-mcp__tabs"
								role="tablist"
								aria-label={ __(
									'MCP clients',
									'better-payment'
								) }
							>
								{ clients.map( ( client ) => (
									<button
										type="button"
										key={ client.id }
										role="tab"
										aria-selected={
											client.id === current?.id
										}
										className={ `bp-mcp__tab${
											client.id === current?.id
												? ' is-active'
												: ''
										}` }
										onClick={ () =>
											setActiveClient( client.id )
										}
									>
										{ client.label }
									</button>
								) ) }
							</div>

							{ current && (
								<div
									className="bp-mcp__snippet"
									role="tabpanel"
								>
									<div className="bp-mcp__snippet-bar">
										<span className="bp-mcp__snippet-file">
											{ current.file ??
												( current.kind === 'command'
													? __(
															'Terminal',
															'better-payment'
													  )
													: __(
															'Connector URL',
															'better-payment'
													  ) ) }
										</span>
										<CopyButton
											value={ current.snippet }
											className="bp-mcp__button--small"
										/>
									</div>
									<pre className="bp-mcp__snippet-body">
										{ current.snippet }
									</pre>
								</div>
							) }
							{ current?.note && (
								<p className="bp-mcp__help bp-mcp__snippet-note">
									{ current.note }
								</p>
							) }
						</Card>
					) }

					<Collapse open={ oauthLive }>
						<Card
							className="bp-mcp__connections"
							title={ __(
								'Connected applications',
								'better-payment'
							) }
							help={ __(
								'Applications you approved on the sign-in screen. Refresh to pick up a new one.',
								'better-payment'
							) }
							action={
								<button
									type="button"
									className="bp-mcp__button bp-mcp__button--secondary bp-mcp__button--small"
									disabled={ loading }
									onClick={ refresh }
								>
									{ loading
										? __( 'Refreshing…', 'better-payment' )
										: __( 'Refresh', 'better-payment' ) }
								</button>
							}
						>
							<McpConnectionList
								connections={ connections }
								error={ error }
								onRevoke={ revoke }
							/>
						</Card>
					</Collapse>

					<Card
						className="bp-mcp__health"
						title={ __( 'Connection health', 'better-payment' ) }
					>
						<ul className="bp-mcp__health-list">
							{ mcpHealthChecks( info, oauthEnabled ).map(
								( check ) => (
									<li
										key={ check.id }
										className={ `is-${ checkTone(
											check.ok
										) }` }
									>
										{ check.label }
									</li>
								)
							) }
							<li className="is-info">
								{ __( 'Endpoint:', 'better-payment' ) }{ ' ' }
								<code>{ info?.endpoint }</code>
								{ mcpEndpointMeta( info ) && (
									<span className="bp-mcp__endpoint-meta">
										{ ' · ' }
										{ mcpEndpointMeta( info ) }
									</span>
								) }
							</li>
						</ul>

						<Collapse open={ oauthLive && appCount > 0 }>
							<div className="bp-mcp__danger">
								<span>
									<strong>
										{ __(
											'Disconnect all',
											'better-payment'
										) }
									</strong>
									<span className="bp-mcp__help">
										{ __(
											'Revoke every OAuth connection at once. Each application has to be approved again.',
											'better-payment'
										) }
									</span>
								</span>
								<ConfirmButton
									variant="danger-solid"
									label={ __(
										'Disconnect all clients',
										'better-payment'
									) }
									confirmText={ __(
										'Every client stops at once.',
										'better-payment'
									) }
									confirmLabel={ __(
										'Yes, disconnect all',
										'better-payment'
									) }
									busyLabel={ __(
										'Disconnecting…',
										'better-payment'
									) }
									onConfirm={ revokeAll }
								/>
							</div>
						</Collapse>
					</Card>

					<p className="bp-mcp__footnote">
						{ __(
							'No tool can take a payment or issue a refund. Payer names and e-mail addresses are withheld unless a tool is explicitly asked for them, and gateway credentials are never readable.',
							'better-payment'
						) }
					</p>
				</div>
			</Collapse>
		</div>
	);
};

export default McpSettings;
