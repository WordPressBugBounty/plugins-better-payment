<?php
/**
 * Per-client connection instructions for the MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Admin;

use Better_Payment\Lite\MCP\MCPManager;
use Better_Payment\Lite\MCP\OAuth\Discovery;
use Better_Payment\Lite\MCP\Tools\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the copy-and-paste block each MCP client needs.
 *
 * The protocol is one URL, but no two clients are configured the same way: Claude
 * Code takes a CLI command, Cursor and VS Code take a JSON file, ChatGPT and the
 * Claude web app take the URL in a form. A site owner should not have to learn which,
 * so the settings screen shows the finished snippet for each, filled in with this
 * site's own address.
 *
 * The snippets are generated, not stored, so an endpoint that changes because the
 * site moved or its permalinks changed cannot leave stale instructions behind.
 *
 * @since 2.5.0
 */
class ConnectGuide {

	/**
	 * Everything the settings screen needs to describe MCP on this site.
	 *
	 * @return array<string, mixed>
	 */
	public static function payload() {
		$endpoint = MCPManager::endpoint_url();

		return array(
			'enabled'         => MCPManager::is_enabled(),
			'oauthEnabled'    => MCPManager::oauth_enabled(),
			'endpoint'        => $endpoint,
			'protocol'        => \Better_Payment\Lite\MCP\Server::LATEST_PROTOCOL,
			'toolCount'       => MCPManager::is_enabled() ? count( Registry::all() ) : 0,
			'appPasswordsUrl' => admin_url( 'profile.php#application-passwords-section' ),
			// Both are real requirements rather than advice: WordPress refuses
			// Application Passwords over plain HTTP, and discovery has nowhere to
			// live without a path-based permalink.
			'isSsl'           => is_ssl(),
			'discoveryReady'  => Discovery::is_available(),
			'metadataUrl'     => Discovery::protected_resource_url(),
			'clients'         => self::clients( $endpoint ),
			'docsUrl'         => self::docs_url(),
		);
	}

	/**
	 * The public setup guide the hero's "Learn how it works" link opens.
	 */
	const DOCS_URL = 'https://betterpayment.co/docs/better-payment-mcp-server-setup/';

	/**
	 * The setup guide's address, or '' to hide the link.
	 *
	 * One place for it: the settings screen reads it from this payload and never
	 * spells the URL itself.
	 *
	 * @return string HTTPS URL, or ''.
	 */
	public static function docs_url() {
		/**
		 * Filters the MCP setup guide linked from the MCP Server tab. Return '' to
		 * hide the link. Anything that is not an https URL hides it too.
		 *
		 * @since 2.5.0
		 *
		 * @param string $url Guide URL.
		 */
		/** @var mixed $url */
		$url = apply_filters( 'better_payment/mcp/docs_url', self::DOCS_URL );
		$url = is_string( $url ) ? esc_url_raw( trim( $url ), array( 'https' ) ) : '';

		return 0 === strpos( $url, 'https://' ) ? $url : '';
	}

	/**
	 * One entry per client, in the order the screen shows them.
	 *
	 * @param string $endpoint Endpoint URL.
	 * @return array<int, array<string, mixed>>
	 */
	private static function clients( $endpoint ) {
		$clients = array(
			array(
				'id'      => 'claude-code',
				'label'   => 'Claude Code',
				'kind'    => 'command',
				'snippet' => sprintf( 'claude mcp add --transport http better-payment %s', $endpoint ),
				'note'    => __( 'Run this in your terminal. Claude Code opens a browser to authorize the connection.', 'better-payment' ),
			),
			array(
				'id'      => 'claude-desktop',
				'label'   => __( 'Claude (web & desktop)', 'better-payment' ),
				'kind'    => 'url',
				'snippet' => $endpoint,
				'note'    => __( 'Settings → Connectors → Add custom connector, then paste this URL and approve the access you want to grant.', 'better-payment' ),
			),
			array(
				'id'      => 'chatgpt',
				'label'   => 'ChatGPT',
				'kind'    => 'url',
				'snippet' => $endpoint,
				'note'    => __( 'Settings → Connectors → Create, then paste this URL. ChatGPT signs in with OAuth, so keep OAuth 2.1 sign-in switched on under Connection.', 'better-payment' ),
			),
			array(
				'id'      => 'cursor',
				'label'   => 'Cursor',
				'kind'    => 'json',
				'file'    => '~/.cursor/mcp.json',
				'snippet' => self::json_snippet( $endpoint ),
				'note'    => __( 'Add this to Cursor\'s MCP configuration file, then enable the server in Cursor\'s Settings → MCP. Cursor opens your browser to approve the connection, so keep OAuth 2.1 sign-in switched on under Connection.', 'better-payment' ),
			),
			array(
				'id'      => 'vscode',
				'label'   => 'VS Code',
				'kind'    => 'json',
				'file'    => '.vscode/mcp.json',
				'snippet' => self::vscode_snippet( $endpoint ),
				'note'    => __( 'Add this to your workspace, then start the server from the Copilot Chat MCP panel.', 'better-payment' ),
			),
			array(
				'id'      => 'windsurf',
				'label'   => 'Windsurf',
				'kind'    => 'json',
				'file'    => '~/.codeium/windsurf/mcp_config.json',
				'snippet' => self::json_snippet( $endpoint ),
				'note'    => __( 'Add this to Windsurf\'s MCP configuration, then refresh the server list.', 'better-payment' ),
			),
			array(
				'id'      => 'other',
				'label'   => __( 'Any other MCP client', 'better-payment' ),
				'kind'    => 'url',
				'snippet' => $endpoint,
				'note'    => __( 'Streamable HTTP transport. Authenticate with OAuth 2.1, or with an Application Password over HTTP Basic.', 'better-payment' ),
			),
		);

		/**
		 * Filters the connection instructions shown on the settings screen.
		 *
		 * @since 2.5.0
		 *
		 * @param array<int, array<string, mixed>> $clients  Client entries.
		 * @param string                           $endpoint Endpoint URL.
		 */
		return (array) apply_filters( 'better_payment/mcp/connect_guide', $clients, $endpoint );
	}

	/**
	 * The `mcpServers` shape Cursor and Windsurf read.
	 *
	 * @param string $endpoint Endpoint URL.
	 * @return string
	 */
	private static function json_snippet( $endpoint ) {
		return self::encode(
			array(
				'mcpServers' => array(
					'better-payment' => array(
						'url' => $endpoint,
					),
				),
			)
		);
	}

	/**
	 * VS Code uses `servers` and wants the transport named.
	 *
	 * @param string $endpoint Endpoint URL.
	 * @return string
	 */
	private static function vscode_snippet( $endpoint ) {
		return self::encode(
			array(
				'servers' => array(
					'better-payment' => array(
						'type' => 'http',
						'url'  => $endpoint,
					),
				),
			)
		);
	}

	/**
	 * Pretty-print without escaping the slashes in the URL.
	 *
	 * A snippet is pasted into a config file by a person, so `https:\/\/` — which is
	 * what wp_json_encode() produces by default — would be the first thing they had
	 * to fix.
	 *
	 * @param array $value Snippet body.
	 * @return string
	 */
	private static function encode( array $value ) {
		$encoded = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		return false === $encoded ? '' : $encoded;
	}
}
