import { __ } from '@wordpress/i18n';

/**
 * Where the MCP highlight sends the owner: the MCP Server settings tab. Relative,
 * because the dashboard is itself `wp-admin/admin.php`, and the settings screen
 * opens the tab named by `section` (Settings.js deep-link support).
 */
export const MCP_SETTINGS_URL =
	'admin.php?page=better-payment-admin&tab=settings&section=mcp';

/**
 * The dashboard's "What's New" card, newest first.
 *
 * `highlight` is the one release headline shown in the tinted box at the top;
 * `items` are the rest. Each entry's `text` holds `<link>…</link>` around the
 * words that link, so translators keep the link with the right words
 * (rendered through createInterpolateElement()).
 *
 * @return {{highlight: Object, items: Object[]}} Card content.
 */
export const changeLogEntries = () => ( {
	highlight: {
		id: 'mcp',
		label: __( 'New:', 'better-payment' ),
		text: __(
			'<link>MCP Server</link> - run Better Payment from Claude, ChatGPT, Cursor and other AI assistants: read payments and manage campaigns in plain language.',
			'better-payment'
		),
		href: MCP_SETTINGS_URL,
		external: false,
	},
	items: [
		{
			id: 'campaign-builder',
			label: __( 'New:', 'better-payment' ),
			text: __(
				'<link>Campaign Builder</link> - create fundraising campaigns with prebuilt templates & full customization flexibility.',
				'better-payment'
			),
			href: 'https://betterpayment.co/docs/how-to-create-a-new-campaign',
			external: true,
		},
		{
			id: 'payment-form-block',
			label: __( 'New:', 'better-payment' ),
			text: __(
				'Payment Form Gutenberg block - Add payment forms anywhere in your content using the block editor',
				'better-payment'
			),
		},
		{
			id: 'user-dashboard-block',
			label: __( 'New:', 'better-payment' ),
			text: __(
				'User Dashboard Gutenberg block - Give users a centralized view of payments, transactions, subscriptions and analytics.',
				'better-payment'
			),
		},
	],
} );
