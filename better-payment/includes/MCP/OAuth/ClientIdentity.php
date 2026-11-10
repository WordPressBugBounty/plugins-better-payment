<?php
/**
 * Which application an OAuth client is, for the Connected applications list.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\OAuth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Names a registered OAuth client the way its owner knows it.
 *
 * Dynamic client registration is anonymous: `client_name` is whatever the caller
 * sent, so a name alone proves nothing. A client is shown as a known application
 * only when its redirect agrees with the name: a hosted client (Claude, ChatGPT)
 * must send codes to that vendor's own host, and a desktop or command-line client
 * (Claude Code, VS Code, Cursor, …) to a loopback address or its own application
 * scheme (`cursor://…`) on the owner's computer.
 * A name that claims a vendor its redirect does not back is listed under the name
 * it registered, marked unverified, with the host its codes go to.
 *
 * A redirect to a vendor's own host identifies that vendor even under another name:
 * only that vendor can receive a code there.
 *
 * What cannot be told apart is not guessed. Claude's web, desktop and mobile apps
 * share one connector: it is added to the Claude account, the code is redeemed by
 * Anthropic's servers at `claude.ai`, and every app of that account uses the same
 * grant. Nothing in the request says which app the owner clicked in, so the row
 * says "web & desktop" — the same name the Connect a client tabs use. ChatGPT works
 * the same way.
 *
 * @since 2.5.0
 */
class ClientIdentity {

	/**
	 * Hosts that are the owner's own computer.
	 */
	const LOOPBACK_HOSTS = array( '127.0.0.1', 'localhost', '::1' );

	/**
	 * Known applications, most specific first.
	 *
	 * `names`: matched case-insensitively inside `client_name`.
	 * `hosts`: redirect hosts (and their subdomains) that belong to the application.
	 * `local`: whether a loopback redirect backs the name (desktop and CLI clients).
	 *
	 * Entries come through a filter, so callers check each key before using it.
	 *
	 * @return array<int, mixed>
	 */
	public static function known() {
		$identities = array(
			array(
				'id'      => 'claude-code',
				'label'   => 'Claude Code',
				'surface' => __( 'Command line, on your computer', 'better-payment' ),
				'names'   => array( 'Claude Code' ),
				'hosts'   => array(),
				'local'   => true,
			),
			array(
				'id'      => 'claude',
				'label'   => __( 'Claude (web & desktop)', 'better-payment' ),
				'surface' => __( 'claude.ai and the Claude desktop and mobile apps', 'better-payment' ),
				'names'   => array( 'Claude', 'Anthropic' ),
				'hosts'   => array( 'claude.ai', 'claude.com', 'anthropic.com' ),
				'local'   => false,
			),
			array(
				'id'      => 'codex',
				'label'   => 'Codex CLI',
				'surface' => __( 'Command line, on your computer', 'better-payment' ),
				'names'   => array( 'Codex' ),
				'hosts'   => array(),
				'local'   => true,
			),
			array(
				'id'      => 'chatgpt',
				'label'   => 'ChatGPT',
				'surface' => __( 'chatgpt.com and the ChatGPT desktop and mobile apps', 'better-payment' ),
				'names'   => array( 'ChatGPT', 'OpenAI' ),
				'hosts'   => array( 'chatgpt.com', 'openai.com' ),
				'local'   => false,
			),
			array(
				'id'      => 'vscode-insiders',
				'label'   => 'VS Code Insiders',
				'surface' => __( 'Editor, on your computer', 'better-payment' ),
				'names'   => array( 'Code - Insiders', 'Code Insiders' ),
				'hosts'   => array( 'insiders.vscode.dev' ),
				'local'   => true,
			),
			array(
				'id'      => 'vscode',
				'label'   => 'VS Code',
				'surface' => __( 'Editor, on your computer', 'better-payment' ),
				'names'   => array( 'Visual Studio Code', 'VS Code', 'VSCode' ),
				'hosts'   => array( 'vscode.dev' ),
				'local'   => true,
			),
			array(
				'id'      => 'copilot',
				'label'   => 'GitHub Copilot',
				'surface' => __( 'Editor, on your computer', 'better-payment' ),
				'names'   => array( 'Copilot' ),
				'hosts'   => array( 'github.com', 'githubcopilot.com' ),
				'local'   => true,
			),
			array(
				'id'      => 'cursor',
				'label'   => 'Cursor',
				'surface' => __( 'Editor, on your computer', 'better-payment' ),
				'names'   => array( 'Cursor' ),
				'hosts'   => array( 'cursor.com', 'cursor.sh' ),
				'local'   => true,
			),
			array(
				'id'      => 'windsurf',
				'label'   => 'Windsurf',
				'surface' => __( 'Editor, on your computer', 'better-payment' ),
				'names'   => array( 'Windsurf', 'Codeium' ),
				'hosts'   => array( 'windsurf.com', 'codeium.com' ),
				'local'   => true,
			),
			array(
				'id'      => 'gemini-cli',
				'label'   => 'Gemini CLI',
				'surface' => __( 'Command line, on your computer', 'better-payment' ),
				'names'   => array( 'Gemini' ),
				'hosts'   => array(),
				'local'   => true,
			),
			array(
				'id'      => 'mcp-inspector',
				'label'   => 'MCP Inspector',
				'surface' => __( 'Developer tool, on your computer', 'better-payment' ),
				'names'   => array( 'MCP Inspector' ),
				'hosts'   => array(),
				'local'   => true,
			),
		);

		/**
		 * Filters the applications the Connected applications list recognises.
		 *
		 * Each entry: `id`, `label`, `surface`, `names` (matched case-insensitively
		 * inside the registered client name), `hosts` (redirect hosts that belong to
		 * the application; subdomains match) and `local` (whether a loopback redirect
		 * backs the name). Earlier entries win, so put a specific name before a
		 * shorter one it contains ("Claude Code" before "Claude").
		 *
		 * @since 2.5.0
		 *
		 * @param array $identities Known applications.
		 */
		/** @var mixed $filtered */
		$filtered = apply_filters( 'better_payment/mcp/client_identities', $identities );

		return is_array( $filtered ) ? $filtered : $identities;
	}

	/**
	 * Identify a registered client.
	 *
	 * @param array|null $client Client record from Store::get_client(), or null.
	 * @return array{app: string, label: string, surface: string, verified: bool, claims: string, registered_name: string, redirect_host: string}
	 */
	public static function identify( $client ) {
		$name      = is_array( $client ) && isset( $client['client_name'] ) ? trim( (string) $client['client_name'] ) : '';
		$redirects = is_array( $client ) && isset( $client['redirect_uris'] ) ? (array) $client['redirect_uris'] : array();
		$hosts     = self::hosts( $redirects );
		$local     = ! empty( $hosts ) && array() === array_diff( $hosts, self::LOOPBACK_HOSTS );
		$remote    = array_values( array_diff( $hosts, self::LOOPBACK_HOSTS ) );

		$result = array(
			'app'             => '',
			'label'           => '' !== $name ? $name : __( 'Unnamed client', 'better-payment' ),
			'surface'         => $local
				? __( 'An application on your computer', 'better-payment' )
				: ( isset( $remote[0] ) ? $remote[0] : '' ),
			'verified'        => false,
			'claims'          => '',
			'registered_name' => $name,
			'redirect_host'   => $local ? 'localhost' : ( isset( $remote[0] ) ? $remote[0] : '' ),
		);

		$known = self::known();

		// The name first: it is what tells Claude Code from another loopback client.
		foreach ( $known as $identity ) {
			if ( ! is_array( $identity ) || ! self::name_matches( $name, $identity ) ) {
				continue;
			}

			if ( self::backed_by_redirect( $identity, $hosts, $local ) ) {
				return self::as_identity( $result, $identity );
			}

			// Calls itself something its redirect does not back. Keep the registered
			// name, and say what it claims, so the owner can judge.
			$result['claims'] = isset( $identity['label'] ) ? (string) $identity['label'] : '';

			return $result;
		}

		// No known name, but a code that can only reach one vendor is that vendor.
		if ( ! $local && ! empty( $remote ) ) {
			foreach ( $known as $identity ) {
				if ( is_array( $identity ) && ! empty( $identity['hosts'] ) && self::all_within( $remote, (array) $identity['hosts'] ) ) {
					return self::as_identity( $result, $identity );
				}
			}
		}

		return $result;
	}

	/**
	 * The result for a recognised application.
	 *
	 * @param array $result   Base result.
	 * @param array $identity Known application.
	 * @return array
	 */
	private static function as_identity( array $result, array $identity ) {
		$result['app']      = isset( $identity['id'] ) ? (string) $identity['id'] : '';
		$result['label']    = isset( $identity['label'] ) ? (string) $identity['label'] : $result['label'];
		$result['surface']  = isset( $identity['surface'] ) ? (string) $identity['surface'] : $result['surface'];
		$result['verified'] = true;

		return $result;
	}

	/**
	 * Whether the registered name names this application.
	 *
	 * @param string $name     Registered client name.
	 * @param array  $identity Known application.
	 * @return bool
	 */
	private static function name_matches( $name, array $identity ) {
		if ( '' === $name ) {
			return false;
		}

		foreach ( isset( $identity['names'] ) ? (array) $identity['names'] : array() as $needle ) {
			if ( '' !== (string) $needle && false !== stripos( $name, (string) $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether every redirect goes where this application receives its codes.
	 *
	 * @param array    $identity Known application.
	 * @param string[] $hosts    Redirect hosts.
	 * @param bool     $local    Whether every redirect is loopback.
	 * @return bool
	 */
	private static function backed_by_redirect( array $identity, array $hosts, $local ) {
		if ( empty( $hosts ) ) {
			return false;
		}

		if ( $local ) {
			return ! empty( $identity['local'] );
		}

		$allowed = isset( $identity['hosts'] ) ? (array) $identity['hosts'] : array();

		if ( ! empty( $identity['local'] ) ) {
			// A desktop client may list a loopback redirect beside its own web one.
			$allowed = array_merge( $allowed, self::LOOPBACK_HOSTS );
		}

		return ! empty( $allowed ) && self::all_within( $hosts, $allowed );
	}

	/**
	 * Whether every host is one of, or a subdomain of, the allowed hosts.
	 *
	 * @param string[] $hosts   Hosts.
	 * @param string[] $allowed Allowed hosts.
	 * @return bool
	 */
	private static function all_within( array $hosts, array $allowed ) {
		foreach ( $hosts as $host ) {
			$ok = false;

			foreach ( $allowed as $base ) {
				$base = strtolower( (string) $base );

				if ( $host === $base || substr( $host, -strlen( '.' . $base ) ) === '.' . $base ) {
					$ok = true;
					break;
				}
			}

			if ( ! $ok ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The distinct, lower-cased hosts of a client's redirects.
	 *
	 * @param array $redirects Redirect URIs.
	 * @return string[]
	 */
	private static function hosts( array $redirects ) {
		$hosts = array();

		foreach ( $redirects as $uri ) {
			// A desktop-application scheme (`cursor://…`) delivers the code on the
			// owner's computer, as loopback does.
			if ( Store::is_app_redirect( (string) $uri ) ) {
				$hosts[] = 'localhost';
				continue;
			}

			$host = strtolower( trim( (string) wp_parse_url( (string) $uri, PHP_URL_HOST ), '[]' ) );

			if ( '' !== $host ) {
				$hosts[] = $host;
			}
		}

		return array_values( array_unique( $hosts ) );
	}
}
