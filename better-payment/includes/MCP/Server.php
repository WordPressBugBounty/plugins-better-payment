<?php
/**
 * Better Payment's MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP;

use Better_Payment\Lite\MCP\Admin\SelfTest;
use Better_Payment\Lite\MCP\Auth\Authenticator;
use Better_Payment\Lite\MCP\Auth\Context;
use Better_Payment\Lite\MCP\Support\ActivityLog;
use Better_Payment\Lite\MCP\Support\Limits;
use Better_Payment\Lite\MCP\Support\Origin;
use Better_Payment\Lite\MCP\Support\RateLimiter;
use Better_Payment\Lite\MCP\Tools\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Streamable HTTP transport: one URL, JSON-RPC 2.0, nothing bespoke.
 *
 * This is the whole point of the module's shape. Claude Code, Claude Desktop, ChatGPT,
 * Cursor, VS Code and Windsurf all speak the same protocol to the same address, so
 * "connect Better Payment" is a URL the site owner pastes, not an integration each
 * client has to have written for it.
 *
 * What this endpoint deliberately does NOT do:
 *
 * - **No server-initiated stream.** A GET is answered 405. Every tool here returns in
 *   one round trip; opening an SSE channel to send nothing would be a connection held
 *   open on a payment site for decoration.
 * - **No session state.** No `Mcp-Session-Id` is issued, so a client may be load
 *   balanced across PHP workers and nothing breaks. The spec allows it and WordPress
 *   has nowhere good to keep per-connection state.
 * - **No cookie authentication**, ever. See `Auth\Authenticator`.
 *
 * @since 2.5.0
 */
class Server {

	/**
	 * Protocol revision this server implements.
	 */
	const LATEST_PROTOCOL = '2025-06-18';

	/**
	 * Revisions this server will negotiate down to.
	 *
	 * @return string[]
	 */
	public static function supported_protocols() {
		return array( '2025-06-18', '2025-03-26', '2024-11-05' );
	}

	/**
	 * Handle one request to the endpoint.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function handle( $request ) {
		$method = strtoupper( (string) $request->get_method() );

		if ( 'POST' !== $method ) {
			// The spec lets a server that offers no server-initiated stream refuse
			// GET, and DELETE only means anything where sessions exist.
			$response = new \WP_REST_Response(
				array(
					'error' => array(
						'code'    => -32600,
						'message' => 'This MCP endpoint accepts POST only.',
					),
				),
				405
			);

			$response->header( 'Allow', 'POST' );

			return $response;
		}

		// Checked before anything else about the request: this is what stops a page on
		// another site reaching this endpoint through DNS rebinding, and the spec
		// requires it whether or not the request also carries a credential.
		if ( ! Origin::allows( (string) $request->get_header( 'origin' ) ) ) {
			return self::respond( self::error_message( null, -32600, 'Origin not allowed.' ), 403 );
		}

		$body = (string) $request->get_body();

		if ( strlen( $body ) > Limits::max_body_bytes() ) {
			return self::respond( self::error_message( null, -32600, 'Request body is too large.' ), 413 );
		}

		// Authentication before parsing: an unauthenticated caller learns nothing
		// about what this endpoint understands.
		Context::reset();

		$self_test = SelfTest::consume_marker( (string) $request->get_header( SelfTest::MARKER_HEADER ) );

		if ( ! $self_test && RateLimiter::is_locked() ) {
			$response = self::respond( self::error_message( null, -32001, 'Too many failed attempts. Try again later.' ), 429 );

			$response->header( 'Retry-After', (string) RateLimiter::retry_after() );

			return $response;
		}

		$user = Authenticator::authenticate( $request );

		if ( is_wp_error( $user ) ) {
			// Only a credential that failed counts. The bare first request of every
			// OAuth flow is a 401 by design, and locking out on those would stop a
			// client from ever reaching the discovery pointer.
			if ( ! $self_test && '' !== Authenticator::authorization_header( $request ) ) {
				RateLimiter::record_failure();
			}

			return self::unauthorized( $user );
		}

		// A success does not clear the failure count: see RateLimiter.

		$decoded = json_decode( $body, true );

		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			return self::respond( self::error_message( null, -32700, 'Parse error.' ), 400 );
		}

		// A JSON-RPC batch. MCP dropped batching in 2025-06-18, but older clients
		// still send arrays and answering them costs nothing.
		if ( self::is_batch( $decoded ) ) {
			if ( count( $decoded ) > Limits::max_batch() ) {
				return self::respond( self::error_message( null, -32600, 'Too many messages in one request.' ), 413 );
			}

			$replies = array();

			foreach ( $decoded as $message ) {
				$reply = self::dispatch( $message );

				if ( null !== $reply ) {
					$replies[] = $reply;
				}
			}

			if ( empty( $replies ) ) {
				return self::accepted();
			}

			return self::respond( $replies );
		}

		if ( ! is_array( $decoded ) ) {
			return self::respond( self::error_message( null, -32600, 'Invalid request.' ), 400 );
		}

		$reply = self::dispatch( $decoded );

		if ( null === $reply ) {
			// A notification. Nothing to say back, and the spec wants 202.
			return self::accepted();
		}

		return self::respond( $reply );
	}

	/**
	 * Whether a decoded body is a JSON-RPC batch.
	 *
	 * @param mixed $decoded Decoded body.
	 * @return bool
	 */
	private static function is_batch( $decoded ) {
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return false;
		}

		// A list, not an object: array_keys() of a JSON object never starts at 0.
		return array_keys( $decoded ) === range( 0, count( $decoded ) - 1 );
	}

	/**
	 * Route one JSON-RPC message.
	 *
	 * @param mixed $message Decoded message.
	 * @return array|null The reply, or null for a notification.
	 */
	private static function dispatch( $message ) {
		if ( ! is_array( $message ) || ! isset( $message['method'] ) ) {
			return self::error_message( null, -32600, 'Invalid request.' );
		}

		$method = (string) $message['method'];
		$id     = isset( $message['id'] ) ? $message['id'] : null;
		$params = isset( $message['params'] ) && is_array( $message['params'] ) ? $message['params'] : array();

		// A message with no id is a notification: acknowledged, never answered.
		// `notifications/initialized` is the one every client sends.
		if ( null === $id ) {
			return null;
		}

		switch ( $method ) {
			case 'initialize':
				return self::result( $id, self::initialize( $params ) );

			case 'ping':
				return self::result( $id, (object) array() );

			case 'tools/list':
				return self::result( $id, array( 'tools' => Registry::describe() ) );

			case 'tools/call':
				return self::call_tool( $id, $params );

			// Declared in no capability, so a compliant client never asks. Answered
			// as empty rather than as an error, because some clients probe anyway
			// and an error in the log looks like a fault.
			case 'resources/list':
				return self::result( $id, array( 'resources' => array() ) );

			case 'prompts/list':
				return self::result( $id, array( 'prompts' => array() ) );

			default:
				return self::error_message( $id, -32601, sprintf( 'Method not found: %s', $method ) );
		}
	}

	/**
	 * The `initialize` result.
	 *
	 * @param array $params Client parameters.
	 * @return array
	 */
	private static function initialize( array $params ) {
		$requested = isset( $params['protocolVersion'] ) ? (string) $params['protocolVersion'] : '';

		// Echo the client's revision when this server implements it, otherwise
		// answer with the newest one it does. A client that cannot live with that
		// disconnects, which is the negotiation the spec describes.
		$protocol = in_array( $requested, self::supported_protocols(), true ) ? $requested : self::LATEST_PROTOCOL;

		return array(
			'protocolVersion' => $protocol,
			'capabilities'    => array(
				// listChanged is false and must stay false while there is no session
				// to send a notification down.
				'tools' => array( 'listChanged' => false ),
			),
			'serverInfo'      => array(
				'name'    => 'better-payment',
				'title'   => 'Better Payment',
				'version' => defined( 'BETTER_PAYMENT_VERSION' ) ? BETTER_PAYMENT_VERSION : '0.0.0',
			),
			'instructions'    => self::instructions(),
		);
	}

	/**
	 * What the client is told about using this server.
	 *
	 * @return string
	 */
	private static function instructions() {
		$instructions = __( 'Better Payment manages payments, fundraising campaigns and subscriptions on this WordPress site. Call site_info first: it reports the plugin version, the active gateways, the site currency and whether Better Payment Pro is installed, which decides what the rest of the tools can answer. Each fundraising campaign has its own currency (currency in campaign_get): it is set from the site currency when the campaign is created, changing the site currency later does not change it, and it can no longer change once the campaign has a donation. Campaign amounts are in the campaign\'s currency; a transaction\'s amount is in the currency stored on it. Payer names and e-mail addresses are withheld from list results unless include_customer_data is set. Every tool that changes something accepts dry_run, which reports what would change and writes nothing. No tool can take a payment or issue a refund.', 'better-payment' );

		/**
		 * Filters the instructions sent to an MCP client on initialize.
		 *
		 * @since 2.5.0
		 *
		 * @param string $instructions Server instructions.
		 */
		return (string) apply_filters( 'better_payment/mcp/instructions', $instructions );
	}

	/**
	 * Run one tool call.
	 *
	 * @param mixed $id     JSON-RPC id.
	 * @param array $params Call parameters.
	 * @return array
	 */
	private static function call_tool( $id, array $params ) {
		$name = isset( $params['name'] ) ? (string) $params['name'] : '';

		if ( '' === $name ) {
			return self::error_message( $id, -32602, 'A tool name is required.' );
		}

		if ( null === Registry::get( $name ) ) {
			// A protocol-level error, not a tool result: the client asked for
			// something that does not exist, which is its bug to fix, not a fact
			// about this site to hand to the model.
			return self::error_message( $id, -32602, sprintf( 'Unknown tool: %s', $name ) );
		}

		$arguments = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();

		$result = Registry::execute( $name, $arguments );

		if ( is_wp_error( $result ) ) {
			ActivityLog::record( $name, 'better_payment_mcp_refused' === $result->get_error_code() ? 'refused' : 'error', get_current_user_id() );

			// A failed tool is reported INSIDE the result with isError, not as a
			// JSON-RPC error. The model has to be able to see what went wrong and
			// try something else; a transport error is invisible to it.
			return self::result(
				$id,
				array(
					'content' => array(
						array(
							'type' => 'text',
							'text' => $result->get_error_message(),
						),
					),
					'isError' => true,
				)
			);
		}

		ActivityLog::record( $name, 'ok', get_current_user_id() );

		$encoded = wp_json_encode( $result );

		return self::result(
			$id,
			array(
				// Both shapes, deliberately: `structuredContent` is what a modern
				// client reads, and the text block is what an older one shows. A
				// server that sends only one leaves half its clients with an empty
				// result.
				'content'           => array(
					array(
						'type' => 'text',
						'text' => false === $encoded ? '{}' : $encoded,
					),
				),
				'structuredContent' => $result,
				'isError'           => false,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Envelopes
	 * ------------------------------------------------------------------ */

	/**
	 * A JSON-RPC success envelope.
	 *
	 * @param mixed $id     Request id.
	 * @param mixed $result Result payload.
	 * @return array
	 */
	private static function result( $id, $result ) {
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		);
	}

	/**
	 * A JSON-RPC error envelope.
	 *
	 * @param mixed  $id      Request id, or null.
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Human message.
	 * @return array
	 */
	private static function error_message( $id, $code, $message ) {
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => array(
				'code'    => (int) $code,
				'message' => (string) $message,
			),
		);
	}

	/**
	 * Send a JSON-RPC payload.
	 *
	 * @param array $payload Envelope or list of envelopes.
	 * @param int   $status  HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function respond( array $payload, $status = 200 ) {
		$response = new \WP_REST_Response( $payload, $status );

		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'MCP-Protocol-Version', self::LATEST_PROTOCOL );

		return $response;
	}

	/**
	 * The empty 202 a notification gets.
	 *
	 * @return \WP_REST_Response
	 */
	private static function accepted() {
		$response = new \WP_REST_Response( null, 202 );

		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * A 401 carrying the discovery pointer.
	 *
	 * @param \WP_Error $error Authentication failure.
	 * @return \WP_REST_Response
	 */
	private static function unauthorized( \WP_Error $error ) {
		$data = $error->get_error_data();

		$response = self::respond(
			self::error_message( null, -32001, $error->get_error_message() ),
			401
		);

		if ( is_array( $data ) && ! empty( $data['www_authenticate'] ) ) {
			// Without this header a client can only report "401" and stop; with it,
			// it finds the authorization server and offers the owner a Connect
			// button.
			$response->header( 'WWW-Authenticate', (string) $data['www_authenticate'] );
		}

		return $response;
	}
}
