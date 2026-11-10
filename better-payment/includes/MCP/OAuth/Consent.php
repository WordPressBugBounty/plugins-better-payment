<?php
/**
 * The consent screen shown during an MCP OAuth authorization.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\OAuth;

use Better_Payment\Lite\MCP\Auth\Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the page where a site owner decides what a client may do.
 *
 * Three rules, each of which is the difference between consent and a formality:
 *
 * - **Nothing is pre-ticked.** A screen that arrives with "write" already selected is
 *   asking the owner to notice a default, not to make a choice.
 * - **It says what the client will be able to read.** "Access your site" is not a
 *   disclosure; transaction records carry payer names and e-mail addresses, and the
 *   screen names that outright.
 * - **Deny is as reachable as Allow.** Not a small link under the button.
 * - **It says where the code goes.** The client's name is whatever an anonymous
 *   registration sent, so anyone could register "Claude" with their own redirect and
 *   send an owner a link to this page. The redirect host is shown beside the name,
 *   every client is labelled self-registered, and a well-known name arriving from a
 *   host that is not that vendor's gets a warning (describe()).
 * - **It cannot be framed.** `X-Frame-Options: DENY` and `frame-ancestors 'none'`, so
 *   Allow cannot be clicked through an invisible overlay.
 *
 * @since 2.5.0
 */
class Consent {

	/**
	 * Nonce action for the consent form.
	 */
	const NONCE_ACTION = 'better_payment_mcp_consent';

	/**
	 * Render the consent page and stop.
	 *
	 * @param array $client  Registered client record.
	 * @param array $request Validated authorization request.
	 * @return void
	 */
	public static function render( array $client, array $request ) {
		$about = self::describe( $client, isset( $request['hidden']['redirect_uri'] ) ? (string) $request['hidden']['redirect_uri'] : '' );
		$name  = $about['name'];

		$user = wp_get_current_user();

		self::send_headers();

		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html__( 'Connect to Better Payment', 'better-payment' ); ?></title>
	<style>
		:root { color-scheme: light dark; }
		body { margin: 0; padding: 40px 16px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; color: #1d2327; }
		.bp-consent { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 28px; box-sizing: border-box; }
		.bp-consent h1 { font-size: 20px; margin: 0 0 8px; }
		.bp-consent p { margin: 0 0 16px; line-height: 1.6; }
		.bp-consent__site { font-size: 13px; color: #646970; margin-bottom: 24px; }
		/* Checkbox in its own column, centred on the title's line; text in the next column. */
		.bp-consent__scope { display: grid; grid-template-columns: 18px minmax(0, 1fr); column-gap: 12px; align-items: start; border: 1px solid #dcdcde; border-radius: 6px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; transition: border-color .15s, background-color .15s; }
		.bp-consent__scope:hover { border-color: #8c8f94; }
		.bp-consent__scope:has(input:checked) { border-color: #2271b1; background: #f0f6fc; }
		.bp-consent__scope:has(input:focus-visible) { outline: 2px solid #2271b1; outline-offset: 2px; }
		.bp-consent__scope input { width: 18px; height: 18px; margin: 3px 0 0; accent-color: #2271b1; cursor: pointer; }
		.bp-consent__scope-text { display: grid; gap: 4px; min-width: 0; }
		.bp-consent__scope-title { font-size: 15px; line-height: 24px; font-weight: 600; }
		.bp-consent__scope-desc { font-size: 13px; line-height: 1.5; color: #50575e; }
		.bp-consent__actions { display: flex; gap: 10px; margin-top: 24px; }
		.bp-consent__actions button { flex: 1; padding: 11px 16px; border-radius: 6px; font-size: 14px; cursor: pointer; border: 1px solid #2271b1; }
		.bp-consent__allow { background: #2271b1; color: #fff; }
		.bp-consent__deny { background: #fff; color: #2271b1; }
		.bp-consent__warning { border-left: 4px solid #d63638; background: #fcf0f1; padding: 12px 14px; border-radius: 4px; }
		.bp-consent__client { margin: 0 0 20px; font-size: 13px; display: grid; grid-template-columns: max-content 1fr; gap: 6px 12px; }
		.bp-consent__client dt { color: #646970; }
		.bp-consent__client dd { margin: 0; overflow-wrap: anywhere; }
		.bp-consent__client dd span { color: #646970; }
		@media (prefers-color-scheme: dark) {
			body { background: #1d2327; color: #f0f0f1; }
			.bp-consent { background: #2c3338; border-color: #3c434a; }
			.bp-consent__scope { border-color: #50575e; }
			.bp-consent__scope:hover { border-color: #8c8f94; }
			.bp-consent__scope:has(input:checked) { border-color: #72aee6; background: #1f2c38; }
			.bp-consent__scope:has(input:focus-visible) { outline-color: #72aee6; }
			.bp-consent__scope input { accent-color: #72aee6; }
			.bp-consent__scope-desc { color: #c3c4c7; }
			.bp-consent__deny { background: #2c3338; color: #72aee6; }
			.bp-consent__warning { background: #3c2a2b; }
			.bp-consent__client dt, .bp-consent__client dd span { color: #a7aaad; }
		}
	</style>
</head>
<body>
	<main class="bp-consent">
		<h1><?php echo esc_html( sprintf( /* translators: %s: client application name. */ __( 'Connect %s to Better Payment?', 'better-payment' ), $name ) ); ?></h1>
		<p class="bp-consent__site">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: site name, 2: WordPress username. */
					__( '%1$s · signed in as %2$s', 'better-payment' ),
					get_bloginfo( 'name' ),
					$user->user_login
				)
			);
			?>
		</p>

		<?php if ( '' !== $about['impersonates'] ) : ?>
			<p class="bp-consent__warning" role="alert">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: application name the client registered, 2: host the code would be sent to. */
						__( 'Warning: this application calls itself %1$s, but it would send your authorization to %2$s, which is not %1$s. Unless you set this connection up yourself, choose Cancel.', 'better-payment' ),
						$about['impersonates'],
						$about['host']
					)
				);
				?>
			</p>
		<?php endif; ?>

		<dl class="bp-consent__client">
			<dt><?php echo esc_html__( 'Sends you back to', 'better-payment' ); ?></dt>
			<dd>
				<strong><?php echo esc_html( $about['host'] ); ?></strong>
				<?php if ( '' !== $about['app_scheme'] ) : ?>
					<span><?php echo esc_html__( '(opens an application on your own computer)', 'better-payment' ); ?></span>
				<?php elseif ( $about['loopback'] ) : ?>
					<span><?php echo esc_html__( '(an application on your own computer)', 'better-payment' ); ?></span>
				<?php endif; ?>
			</dd>
			<?php if ( '' !== $about['client_uri'] ) : ?>
				<dt><?php echo esc_html__( 'Website', 'better-payment' ); ?></dt>
				<dd><?php echo esc_html( $about['client_uri'] ); ?></dd>
			<?php endif; ?>
			<dt><?php echo esc_html__( 'Registered by', 'better-payment' ); ?></dt>
			<dd><?php echo esc_html__( 'the application itself — unverified. Its name is whatever it chose to send.', 'better-payment' ); ?></dd>
		</dl>

		<p><?php echo esc_html__( 'Choose what this application may do with your payment data. Transaction records include payer names and e-mail addresses.', 'better-payment' ); ?></p>

		<form method="post" action="<?php echo esc_url( $request['self_url'] ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION ); ?>
			<?php foreach ( $request['hidden'] as $key => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php endforeach; ?>

			<?php
			// Every value is escaped inside scope_option().
			echo self::scope_option( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				Context::SCOPE_READ,
				__( 'Read', 'better-payment' ),
				__( 'Transactions, customers, campaigns, settings and gateway status. Gateway credentials are never readable.', 'better-payment' )
			);
			echo self::scope_option( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				Context::SCOPE_WRITE,
				__( 'Write', 'better-payment' ),
				__( 'Edit and publish campaigns, change allowlisted settings, mark transactions complete or delete them. No tool can take a payment or issue a refund.', 'better-payment' )
			);
			?>

			<div class="bp-consent__actions">
				<button type="submit" name="consent" value="deny" class="bp-consent__deny"><?php echo esc_html__( 'Cancel', 'better-payment' ); ?></button>
				<button type="submit" name="consent" value="allow" class="bp-consent__allow"><?php echo esc_html__( 'Allow', 'better-payment' ); ?></button>
			</div>
		</form>
	</main>
</body>
</html>
		<?php

		exit;
	}

	/**
	 * One permission card on the consent screen.
	 *
	 * The checkbox is the label's first child and sits in its own grid column,
	 * centred on the title's line; the title and the description share the second
	 * column. The whole card is the label, so clicking the text toggles the box.
	 * Nothing is ever pre-ticked: the owner chooses each permission.
	 *
	 * @param string $scope       Scope value posted in `granted_scope[]`.
	 * @param string $title       Permission name.
	 * @param string $description What the permission allows.
	 * @return string Escaped markup.
	 */
	public static function scope_option( $scope, $title, $description ) {
		$id = 'bp-consent-scope-' . sanitize_key( (string) $scope );

		return sprintf(
			'<label class="bp-consent__scope" for="%1$s">'
				. '<input type="checkbox" id="%1$s" name="granted_scope[]" value="%2$s" aria-describedby="%1$s-desc">'
				. '<span class="bp-consent__scope-text">'
				. '<strong class="bp-consent__scope-title">%3$s</strong>'
				. '<span class="bp-consent__scope-desc" id="%1$s-desc">%4$s</span>'
				. '</span>'
				. '</label>',
			esc_attr( $id ),
			esc_attr( (string) $scope ),
			esc_html( (string) $title ),
			esc_html( (string) $description )
		);
	}

	/**
	 * What the consent page says about a client.
	 *
	 * `impersonates` is the vendor name when the client calls itself after a well-known
	 * MCP client (Claude, ChatGPT, Cursor, …) but its redirect goes to a host that is
	 * not that vendor's and not this computer. A loopback or desktop-application
	 * redirect (`cursor://…`, Store::app_redirect_schemes()) never warns: the code stays
	 * on the owner's own machine, which is how desktop clients (Claude Code, Cursor,
	 * VS Code) receive it.
	 *
	 * @param array  $client       Registered client record.
	 * @param string $redirect_uri The redirect the code would be sent to.
	 * @return array{name: string, host: string, loopback: bool, app_scheme: string, client_uri: string, impersonates: string}
	 */
	public static function describe( array $client, $redirect_uri ) {
		$name = isset( $client['client_name'] ) ? trim( (string) $client['client_name'] ) : '';
		$host = strtolower( (string) wp_parse_url( (string) $redirect_uri, PHP_URL_HOST ) );
		$host = trim( $host, '[]' );

		// A desktop-application scheme (`cursor://…`) hands the code to an
		// application on the owner's own computer, exactly as a loopback address
		// does, so it is described and trusted the same way.
		$app_scheme = Store::is_app_redirect( $redirect_uri )
			? strtolower( (string) wp_parse_url( (string) $redirect_uri, PHP_URL_SCHEME ) )
			: '';
		$loopback   = '' !== $app_scheme || in_array( $host, array( '127.0.0.1', 'localhost', '::1' ), true );

		if ( '' !== $app_scheme ) {
			$host = $app_scheme . '://' . $host;
		}

		$about = array(
			'name'         => '' !== $name ? $name : __( 'An application', 'better-payment' ),
			'host'         => '' !== $host ? $host : __( 'an unknown address', 'better-payment' ),
			'loopback'     => $loopback,
			'app_scheme'   => $app_scheme,
			'client_uri'   => isset( $client['client_uri'] ) ? (string) $client['client_uri'] : '',
			'impersonates' => '',
		);

		if ( '' === $name || '' === $host || $loopback ) {
			return $about;
		}

		$haystack = strtolower( $name );

		foreach ( self::known_clients() as $vendor => $hosts ) {
			if ( false === strpos( $haystack, strtolower( (string) $vendor ) ) ) {
				continue;
			}

			foreach ( (array) $hosts as $allowed ) {
				$allowed = strtolower( (string) $allowed );

				if ( $host === $allowed || substr( $host, -strlen( '.' . $allowed ) ) === '.' . $allowed ) {
					return $about;
				}
			}

			$about['impersonates'] = (string) $vendor;

			return $about;
		}

		return $about;
	}

	/**
	 * Well-known MCP client names and the hosts their redirects come from.
	 *
	 * @return array<string, string[]>
	 */
	public static function known_clients() {
		/**
		 * Filters the well-known MCP client names the consent page checks a client's
		 * redirect host against. Keys are matched case-insensitively inside the
		 * registered name; a host matches itself and its subdomains.
		 *
		 * @since 2.5.0
		 *
		 * @param array<string, string[]> $clients Name => hosts.
		 */
		return (array) apply_filters(
			'better_payment/mcp/oauth_known_clients',
			array(
				'Claude'        => array( 'claude.ai', 'claude.com', 'anthropic.com' ),
				'Anthropic'     => array( 'claude.ai', 'claude.com', 'anthropic.com' ),
				'ChatGPT'       => array( 'chatgpt.com', 'openai.com' ),
				'OpenAI'        => array( 'chatgpt.com', 'openai.com' ),
				'Cursor'        => array( 'cursor.com', 'cursor.sh' ),
				'VS Code'       => array( 'vscode.dev', 'visualstudio.com', 'github.com' ),
				'Visual Studio' => array( 'vscode.dev', 'visualstudio.com', 'github.com' ),
				'Copilot'       => array( 'github.com', 'githubcopilot.com' ),
				'Windsurf'      => array( 'windsurf.com', 'codeium.com' ),
				'Codeium'       => array( 'windsurf.com', 'codeium.com' ),
				'Gemini'        => array( 'google.com' ),
			)
		);
	}

	/**
	 * Render a terminal error page and stop.
	 *
	 * Used only for failures that happen BEFORE the redirect URI is trusted — an
	 * unknown client, a redirect that is not registered. Everything after that is
	 * reported to the client by redirect, as OAuth requires.
	 *
	 * @param string $title   Heading.
	 * @param string $message Explanation.
	 * @return void
	 */
	public static function error( $title, $message ) {
		self::send_headers( 400 );

		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		body { margin: 0; padding: 40px 16px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f0f1; color: #1d2327; }
		main { max-width: 520px; margin: 0 auto; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 28px; }
		h1 { font-size: 20px; margin: 0 0 8px; }
		p { margin: 0; line-height: 1.6; }
	</style>
</head>
<body>
	<main>
		<h1><?php echo esc_html( $title ); ?></h1>
		<p><?php echo esc_html( $message ); ?></p>
	</main>
</body>
</html>
		<?php

		exit;
	}

	/**
	 * Headers shared by both pages.
	 *
	 * @param int $status HTTP status.
	 * @return void
	 */
	private static function send_headers( $status = 200 ) {
		if ( headers_sent() ) {
			return;
		}

		status_header( $status );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		// A consent decision must never be served from a cache, and the page carries
		// the client id and state in its form.
		header( 'Cache-Control: no-store' );
		// Allow must not be clickable through an invisible frame on another site.
		header( 'X-Frame-Options: DENY' );
		header( "Content-Security-Policy: frame-ancestors 'none'" );
		nocache_headers();
	}
}
