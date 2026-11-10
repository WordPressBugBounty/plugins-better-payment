<?php
/**
 * Storage for the MCP OAuth 2.1 authorization server.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\OAuth;

use Better_Payment\Lite\MCP\Auth\Context;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clients, authorization codes and tokens, kept in three non-autoloaded options.
 *
 * Rules that matter more than the storage medium:
 *
 * - **Nothing is stored in a form that can be presented.** Codes and tokens are kept
 *   as SHA-256 hashes, so a database dump does not hand an attacker working
 *   credentials. The plaintext exists once, in the response that issues it.
 * - **Comparison is constant-time.** A hash lookup by array key would leak nothing,
 *   but the code path still ends in `hash_equals()` rather than `===`.
 * - **Expiry is enforced on read, not by a cron.** A site whose cron never runs must
 *   not end up honouring tokens from a year ago.
 * - **Every write prunes.** These options are the only thing that grows, and a
 *   fundraising site is not a place to leave an unbounded option.
 *
 * @since 2.5.0
 */
class Store {

	/**
	 * Registered clients.
	 */
	const CLIENTS_OPTION = 'better_payment_mcp_oauth_clients';

	/**
	 * Outstanding authorization codes.
	 */
	const CODES_OPTION = 'better_payment_mcp_oauth_codes';

	/**
	 * Issued tokens.
	 */
	const TOKENS_OPTION = 'better_payment_mcp_oauth_tokens';

	/**
	 * How long an authorization code is usable, in seconds.
	 *
	 * OAuth 2.1 says a code should live about a minute. It is also single use: the
	 * first redemption deletes it.
	 */
	const CODE_TTL = 60;

	/**
	 * Access token lifetime, in seconds.
	 */
	const ACCESS_TTL = 3600;

	/**
	 * Refresh token lifetime, in seconds.
	 */
	const REFRESH_TTL = 2592000;

	/**
	 * Most clients kept registered.
	 */
	const MAX_CLIENTS = 50;

	/**
	 * Most tokens kept.
	 */
	const MAX_TOKENS = 200;

	/**
	 * Dynamic Client Registration limits. The endpoint is unauthenticated, and every
	 * client record lives in one option that /authorize and /token load whole, so a
	 * registration may not carry an unbounded payload.
	 */
	const MAX_REDIRECT_URIS = 10;

	const MAX_REDIRECT_URI_LENGTH = 2000;

	const MAX_CLIENT_NAME_LENGTH = 100;

	const MAX_CLIENT_URI_LENGTH = 500;

	/**
	 * Seconds to wait for the store lock before giving up.
	 */
	const LOCK_TIMEOUT = 10;

	/**
	 * How many nested calls hold the store lock in this request.
	 *
	 * @var int
	 */
	private static $lock_depth = 0;

	/**
	 * Hash a presented secret for storage and lookup.
	 *
	 * @param string $value Plaintext code or token.
	 * @return string
	 */
	private static function fingerprint( $value ) {
		return hash( 'sha256', (string) $value );
	}

	/**
	 * Read one of the options.
	 *
	 * @param string $option Option name.
	 * @return array
	 */
	private static function read( $option ) {
		// Under the lock the stored value is the only one that counts: another request
		// may have written it since this one first read it, so the copy this request
		// cached must not stand in for it.
		if ( self::$lock_depth > 0 ) {
			wp_cache_delete( $option, 'options' );
		}

		$stored = get_option( $option, array() );

		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Write one of the options, never autoloaded.
	 *
	 * @param string $option Option name.
	 * @param array  $value  New value.
	 * @return void
	 */
	private static function write( $option, array $value ) {
		update_option( $option, $value, false );
	}

	/**
	 * A cryptographically random opaque string.
	 *
	 * @return string
	 */
	private static function random() {
		return bin2hex( random_bytes( 32 ) );
	}

	/**
	 * Run a read-modify-write of the store under one MySQL named lock.
	 *
	 * Codes and tokens are arrays in single options, so two requests that each read,
	 * change and write them lose one of the writes. Without a lock a refresh that read
	 * the tokens before the owner clicked Revoke wrote the revoked family back after
	 * it, one code could be redeemed twice, and a replayed refresh token could slip
	 * past family revocation. Nested calls (a rotation revokes or issues inside its own
	 * lock) reuse the lock this request already holds.
	 *
	 * @param callable $callback  The read-modify-write.
	 * @param mixed    $when_busy Returned when the lock cannot be had in time. Passing
	 *                            `null` runs the callback unlocked instead: a revocation
	 *                            must happen even then, because refusing it is worse.
	 * @return mixed The callback's result, or `$when_busy`.
	 */
	private static function locked( callable $callback, $when_busy = null ) {
		if ( self::$lock_depth > 0 ) {
			return $callback();
		}

		if ( ! self::acquire_lock() ) {
			return null === $when_busy ? $callback() : $when_busy;
		}

		++self::$lock_depth;

		try {
			return $callback();
		} finally {
			--self::$lock_depth;
			self::release_lock();
		}
	}

	/**
	 * The store lock's name: one per site (GET_LOCK is server-wide; names cap at 64).
	 *
	 * @return string
	 */
	public static function lock_name() {
		global $wpdb;

		return 'bp_mcp_oauth_' . md5( $wpdb->prefix );
	}

	/**
	 * Take the store lock.
	 *
	 * @return bool
	 */
	private static function acquire_lock() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A lock, not data.
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::lock_name(), self::LOCK_TIMEOUT ) );
	}

	/**
	 * Release the store lock.
	 *
	 * @return void
	 */
	private static function release_lock() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- A lock, not data.
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lock_name() ) );
	}

	/**
	 * The error returned when the store is busy past LOCK_TIMEOUT.
	 *
	 * @return \WP_Error
	 */
	private static function busy_error() {
		return new \WP_Error(
			'temporarily_unavailable',
			__( 'The authorization server is busy. Try again.', 'better-payment' ),
			array( 'status' => 503 )
		);
	}

	/* ---------------------------------------------------------------------
	 * Clients
	 * ------------------------------------------------------------------ */

	/**
	 * Register a client through Dynamic Client Registration.
	 *
	 * Public clients only: no secret is issued, because the clients that use this
	 * endpoint are desktop apps and browser front-ends that cannot keep one. PKCE is
	 * what authenticates the token request instead, and it is mandatory below.
	 *
	 * @param array $metadata Client metadata from the request.
	 * @return array|\WP_Error Registration response, or an error.
	 */
	public static function register_client( array $metadata ) {
		return self::locked(
			static function () use ( $metadata ) {
				return self::register_client_locked( $metadata );
			},
			self::busy_error()
		);
	}

	/**
	 * The unlocked half of register_client().
	 *
	 * @see register_client()
	 */
	private static function register_client_locked( array $metadata ) {
		$redirect_uris = isset( $metadata['redirect_uris'] ) && is_array( $metadata['redirect_uris'] )
			? $metadata['redirect_uris']
			: array();

		$too_large = self::oversized_metadata( $metadata, $redirect_uris );

		if ( null !== $too_large ) {
			return $too_large;
		}

		$clean = array();

		foreach ( $redirect_uris as $uri ) {
			$uri = self::clean_redirect_uri( (string) $uri );

			if ( '' === $uri ) {
				continue;
			}

			// Loopback redirects are how a desktop client receives the code; every
			// other redirect must be HTTPS, or the code travels in clear text.
			if ( ! self::is_acceptable_redirect( $uri ) ) {
				continue;
			}

			$clean[] = $uri;
		}

		if ( empty( $clean ) ) {
			return new \WP_Error(
				'invalid_redirect_uri',
				__( 'At least one HTTPS, loopback or desktop-application redirect URI is required.', 'better-payment' ),
				array( 'status' => 400 )
			);
		}

		$clients = self::read( self::CLIENTS_OPTION );

		if ( count( $clients ) >= self::MAX_CLIENTS ) {
			$clients = self::evict_unused_clients( $clients );
		}

		if ( count( $clients ) >= self::MAX_CLIENTS ) {
			return new \WP_Error(
				'too_many_clients',
				__( 'This site has reached its limit of connected applications. Revoke one under Settings, then connect again.', 'better-payment' ),
				array( 'status' => 400 )
			);
		}

		$client_id = self::random();

		$clients[ $client_id ] = array(
			'client_name'   => isset( $metadata['client_name'] ) ? sanitize_text_field( (string) $metadata['client_name'] ) : '',
			'client_uri'    => isset( $metadata['client_uri'] ) ? esc_url_raw( (string) $metadata['client_uri'] ) : '',
			'redirect_uris' => $clean,
			'created'       => time(),
		);

		self::write( self::CLIENTS_OPTION, $clients );

		return array(
			'client_id'                  => $client_id,
			'client_id_issued_at'        => (int) $clients[ $client_id ]['created'],
			'client_name'                => $clients[ $client_id ]['client_name'],
			'redirect_uris'              => $clean,
			'grant_types'                => array( 'authorization_code', 'refresh_token' ),
			'response_types'             => array( 'code' ),
			'token_endpoint_auth_method' => 'none',
		);
	}

	/**
	 * Refuse a registration whose metadata is larger than any real client sends.
	 *
	 * Refused rather than truncated: a client whose own name or redirect came back
	 * cut short would be told it registered something it did not.
	 *
	 * @param array $metadata      Client metadata.
	 * @param array $redirect_uris Its `redirect_uris`.
	 * @return \WP_Error|null The refusal, or null when it fits.
	 */
	private static function oversized_metadata( array $metadata, array $redirect_uris ) {
		$name = isset( $metadata['client_name'] ) && is_scalar( $metadata['client_name'] ) ? (string) $metadata['client_name'] : '';
		$uri  = isset( $metadata['client_uri'] ) && is_scalar( $metadata['client_uri'] ) ? (string) $metadata['client_uri'] : '';

		$problem = '';

		if ( count( $redirect_uris ) > self::MAX_REDIRECT_URIS ) {
			/* translators: %d: maximum number of redirect URIs. */
			$problem = sprintf( __( 'At most %d redirect URIs may be registered.', 'better-payment' ), self::MAX_REDIRECT_URIS );
		} elseif ( self::mb_length( $name ) > self::MAX_CLIENT_NAME_LENGTH ) {
			/* translators: %d: maximum length. */
			$problem = sprintf( __( 'client_name may be at most %d characters.', 'better-payment' ), self::MAX_CLIENT_NAME_LENGTH );
		} elseif ( strlen( $uri ) > self::MAX_CLIENT_URI_LENGTH ) {
			/* translators: %d: maximum length. */
			$problem = sprintf( __( 'client_uri may be at most %d bytes.', 'better-payment' ), self::MAX_CLIENT_URI_LENGTH );
		} else {
			foreach ( $redirect_uris as $redirect ) {
				if ( ! is_scalar( $redirect ) || strlen( (string) $redirect ) > self::MAX_REDIRECT_URI_LENGTH ) {
					/* translators: %d: maximum length. */
					$problem = sprintf( __( 'Each redirect URI may be at most %d bytes.', 'better-payment' ), self::MAX_REDIRECT_URI_LENGTH );
					break;
				}
			}
		}

		return '' === $problem ? null : new \WP_Error( 'invalid_client_metadata', $problem, array( 'status' => 400 ) );
	}

	/**
	 * Character length, without requiring mbstring.
	 *
	 * @param string $value Text.
	 * @return int
	 */
	private static function mb_length( $value ) {
		return function_exists( 'mb_strlen' ) ? (int) mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}

	/**
	 * Make room in the registry without touching a connected client.
	 *
	 * Registration is public, so whatever it evicts, a stranger can evict. Oldest-first
	 * over every client let fifty anonymous POSTs remove all of them, and a client that
	 * is gone fails its next refresh with `invalid_client` — a working connection,
	 * disconnected by someone holding no credential. Only a client with no live token
	 * and no pending code may go, oldest first, and when none qualifies the caller
	 * refuses the new registration instead.
	 *
	 * @param array $clients Stored clients, oldest first.
	 * @return array
	 */
	private static function evict_unused_clients( array $clients ) {
		$in_use = array();

		foreach ( self::prune_tokens( self::read( self::TOKENS_OPTION ) ) as $grant ) {
			if ( 'rotated' !== $grant['type'] ) {
				$in_use[ $grant['client_id'] ] = true;
			}
		}

		foreach ( self::prune_codes( self::read( self::CODES_OPTION ) ) as $grant ) {
			$in_use[ $grant['client_id'] ] = true;
		}

		foreach ( array_keys( $clients ) as $client_id ) {
			if ( count( $clients ) < self::MAX_CLIENTS ) {
				break;
			}

			if ( ! isset( $in_use[ $client_id ] ) ) {
				unset( $clients[ $client_id ] );
			}
		}

		return $clients;
	}

	/**
	 * Schemes that may never be a native application's redirect, whatever a filter
	 * says: web, script and document schemes a browser handles itself.
	 */
	const FORBIDDEN_APP_SCHEMES = array( 'http', 'https', 'javascript', 'data', 'file', 'vbscript', 'blob', 'about', 'ftp', 'ws', 'wss', 'mailto', 'tel', 'sms', 'intent', 'chrome', 'view-source', 'filesystem' );

	/**
	 * Private-use URI schemes of desktop MCP clients (RFC 8252 §7.1).
	 *
	 * A desktop editor receives its authorization code through its own scheme:
	 * Cursor signs in with `cursor://anysphere.cursor-mcp/oauth/callback` and uses
	 * its loopback address only when Cursor turns that on remotely. Without its
	 * scheme here Cursor registered, then every sign-in stopped at "Redirect
	 * address not recognised".
	 *
	 * An allowlist, not every scheme: any application on the computer can claim a
	 * scheme, so a scheme only says "an application on this computer". PKCE S256 is
	 * what keeps an intercepted code useless, and it is mandatory here.
	 *
	 * @return string[] Lower-case schemes.
	 */
	public static function app_redirect_schemes() {
		/**
		 * Filters the private-use URI schemes a desktop MCP client may register as
		 * its OAuth redirect (RFC 8252). Web, script and document schemes are
		 * refused whatever the filter returns.
		 *
		 * @since 2.5.0
		 *
		 * @param string[] $schemes Schemes, without `://`.
		 */
		/** @var mixed $schemes */
		$schemes = apply_filters( 'better_payment/mcp/oauth_redirect_schemes', array( 'cursor', 'vscode', 'vscode-insiders', 'windsurf' ) );
		$clean   = array();

		foreach ( is_array( $schemes ) ? $schemes : array() as $scheme ) {
			$scheme = strtolower( trim( (string) $scheme ) );

			if ( 1 === preg_match( '/^[a-z][a-z0-9+.\-]*$/', $scheme ) && ! in_array( $scheme, self::FORBIDDEN_APP_SCHEMES, true ) ) {
				$clean[] = $scheme;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Whether a URI uses an allowed desktop-application scheme.
	 *
	 * @param string $uri URI.
	 * @return bool
	 */
	public static function is_app_redirect( $uri ) {
		$scheme = strtolower( (string) wp_parse_url( (string) $uri, PHP_URL_SCHEME ) );

		return '' !== $scheme && in_array( $scheme, self::app_redirect_schemes(), true );
	}

	/**
	 * A redirect URI from a request, cleaned the one way every endpoint compares it.
	 *
	 * `esc_url_raw()` with its default protocols turns `cursor://…` into '', so the
	 * registration, the authorization request and the token request must all clean
	 * it with the same protocol list or an exact match can never succeed.
	 *
	 * @param string $uri Raw URI.
	 * @return string Cleaned URI, or '' when its scheme is not allowed.
	 */
	public static function clean_redirect_uri( $uri ) {
		return esc_url_raw( (string) $uri, array_merge( array( 'https', 'http' ), self::app_redirect_schemes() ) );
	}

	/**
	 * Whether a redirect URI may be registered.
	 *
	 * HTTPS anywhere, plain HTTP only to loopback, or an allowed desktop-application
	 * scheme (see app_redirect_schemes()).
	 *
	 * @param string $uri Candidate URI.
	 * @return bool
	 */
	public static function is_acceptable_redirect( $uri ) {
		$parts = wp_parse_url( (string) $uri );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		$scheme = strtolower( (string) $parts['scheme'] );

		// Codes travel in the query; a fragment or user info has no place here.
		if ( isset( $parts['fragment'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return false;
		}

		if ( 'https' === $scheme ) {
			return true;
		}

		if ( in_array( $scheme, self::app_redirect_schemes(), true ) ) {
			return true;
		}

		if ( 'http' !== $scheme ) {
			return false;
		}

		return in_array( strtolower( $parts['host'] ), array( '127.0.0.1', '::1', 'localhost' ), true );
	}

	/**
	 * Fetch one registered client.
	 *
	 * @param string $client_id Client id.
	 * @return array|null
	 */
	public static function get_client( $client_id ) {
		$clients   = self::read( self::CLIENTS_OPTION );
		$client_id = (string) $client_id;

		return isset( $clients[ $client_id ] ) ? $clients[ $client_id ] : null;
	}

	/* ---------------------------------------------------------------------
	 * Authorization codes
	 * ------------------------------------------------------------------ */

	/**
	 * Issue an authorization code.
	 *
	 * @param string $client_id    Client id.
	 * @param int    $user_id      Consenting user.
	 * @param string $redirect_uri Redirect the code will be sent to.
	 * @param string $challenge    PKCE S256 challenge.
	 * @param array  $scope        Granted scopes.
	 * @return string The plaintext code.
	 */
	public static function issue_code( $client_id, $user_id, $redirect_uri, $challenge, array $scope ) {
		return self::locked(
			static function () use ( $client_id, $user_id, $redirect_uri, $challenge, $scope ) {
				return self::issue_code_locked( $client_id, $user_id, $redirect_uri, $challenge, $scope );
			},
			null
		);
	}

	/**
	 * The unlocked half of issue_code().
	 *
	 * @see issue_code()
	 */
	private static function issue_code_locked( $client_id, $user_id, $redirect_uri, $challenge, array $scope ) {
		$code  = self::random();
		$codes = self::prune_codes( self::read( self::CODES_OPTION ) );

		$codes[ self::fingerprint( $code ) ] = array(
			'client_id'    => (string) $client_id,
			'user_id'      => (int) $user_id,
			'redirect_uri' => (string) $redirect_uri,
			'challenge'    => (string) $challenge,
			'scope'        => array_values( $scope ),
			'expires'      => time() + self::CODE_TTL,
		);

		self::write( self::CODES_OPTION, $codes );

		return $code;
	}

	/**
	 * Redeem an authorization code.
	 *
	 * @param string $code         Presented code.
	 * @param string $client_id    Client presenting it.
	 * @param string $redirect_uri Redirect URI from the token request.
	 * @param string $verifier     PKCE verifier.
	 * @return array|\WP_Error The grant, or an error.
	 */
	public static function consume_code( $code, $client_id, $redirect_uri, $verifier ) {
		return self::locked(
			static function () use ( $code, $client_id, $redirect_uri, $verifier ) {
				return self::consume_code_locked( $code, $client_id, $redirect_uri, $verifier );
			},
			self::busy_error()
		);
	}

	/**
	 * The unlocked half of consume_code().
	 *
	 * @see consume_code()
	 */
	private static function consume_code_locked( $code, $client_id, $redirect_uri, $verifier ) {
		$codes       = self::prune_codes( self::read( self::CODES_OPTION ) );
		$fingerprint = self::fingerprint( $code );

		if ( ! isset( $codes[ $fingerprint ] ) ) {
			return self::grant_error( __( 'The authorization code is invalid or has already been used.', 'better-payment' ) );
		}

		$grant = $codes[ $fingerprint ];

		// Single use, whatever happens next: a code that failed a check must not be
		// available for a second attempt.
		unset( $codes[ $fingerprint ] );
		self::write( self::CODES_OPTION, $codes );

		if ( ! hash_equals( (string) $grant['client_id'], (string) $client_id ) ) {
			return self::grant_error( __( 'This authorization code was issued to another client.', 'better-payment' ) );
		}

		// Exact match, not a prefix: a prefix match lets a registered
		// `https://example.com/cb` accept `https://example.com/cb.attacker`.
		if ( (string) $grant['redirect_uri'] !== (string) $redirect_uri ) {
			return self::grant_error( __( 'The redirect URI does not match the one the code was issued for.', 'better-payment' ) );
		}

		$computed = rtrim( strtr( base64_encode( hash( 'sha256', (string) $verifier, true ) ), '+/', '-_' ), '=' );

		if ( '' === (string) $verifier || ! hash_equals( (string) $grant['challenge'], $computed ) ) {
			return self::grant_error( __( 'The PKCE verifier does not match.', 'better-payment' ) );
		}

		return $grant;
	}

	/**
	 * Drop expired codes.
	 *
	 * @param array $codes Stored codes.
	 * @return array
	 */
	private static function prune_codes( array $codes ) {
		$now = time();

		foreach ( $codes as $fingerprint => $grant ) {
			if ( ! isset( $grant['expires'] ) || $grant['expires'] < $now ) {
				unset( $codes[ $fingerprint ] );
			}
		}

		return $codes;
	}

	/* ---------------------------------------------------------------------
	 * Tokens
	 * ------------------------------------------------------------------ */

	/**
	 * Issue an access token and a refresh token.
	 *
	 * @param string $client_id Client id.
	 * @param int    $user_id   Resource owner.
	 * @param array  $scope     Granted scopes.
	 * @param string $family    Rotation family, generated when omitted.
	 * @return array The token response body.
	 */
	public static function issue_tokens( $client_id, $user_id, array $scope, $family = '' ) {
		return self::locked(
			static function () use ( $client_id, $user_id, $scope, $family ) {
				return self::issue_tokens_locked( $client_id, $user_id, $scope, $family );
			},
			null
		);
	}

	/**
	 * The unlocked half of issue_tokens().
	 *
	 * @see issue_tokens()
	 */
	private static function issue_tokens_locked( $client_id, $user_id, array $scope, $family = '' ) {
		$tokens = self::prune_tokens( self::read( self::TOKENS_OPTION ) );

		$access  = self::random();
		$refresh = self::random();
		$family  = '' !== (string) $family ? (string) $family : self::random();
		$now     = time();

		// When the owner approved this connection. A rotation carries it over from
		// the grant it replaces (still stored as a `rotated` tombstone), so it stays
		// the consent time for the life of the family.
		$connected = $now;

		foreach ( $tokens as $grant ) {
			if ( isset( $grant['family'], $grant['connected'] ) && (string) $grant['family'] === $family ) {
				$connected = (int) $grant['connected'];
				break;
			}
		}

		$tokens[ self::fingerprint( $access ) ] = array(
			'type'      => 'access',
			'client_id' => (string) $client_id,
			'user_id'   => (int) $user_id,
			'scope'     => array_values( $scope ),
			'family'    => $family,
			'connected' => $connected,
			'expires'   => $now + self::ACCESS_TTL,
		);

		$tokens[ self::fingerprint( $refresh ) ] = array(
			'type'      => 'refresh',
			'client_id' => (string) $client_id,
			'user_id'   => (int) $user_id,
			'scope'     => array_values( $scope ),
			'family'    => $family,
			'connected' => $connected,
			'expires'   => $now + self::REFRESH_TTL,
		);

		self::write( self::TOKENS_OPTION, self::cap_tokens( $tokens ) );

		return array(
			'access_token'  => $access,
			'token_type'    => 'Bearer',
			'expires_in'    => self::ACCESS_TTL,
			'refresh_token' => $refresh,
			'scope'         => implode( ' ', $scope ),
		);
	}

	/**
	 * Resolve an access token.
	 *
	 * @param string $token Presented token.
	 * @return array|null The grant, or null when it is unusable.
	 */
	public static function resolve_access_token( $token ) {
		$tokens      = self::read( self::TOKENS_OPTION );
		$fingerprint = self::fingerprint( $token );

		if ( ! isset( $tokens[ $fingerprint ] ) ) {
			return null;
		}

		$grant = $tokens[ $fingerprint ];

		if ( 'access' !== $grant['type'] ) {
			// A refresh token presented as a Bearer credential. Refused rather than
			// treated as an access token, which is the whole point of the split.
			return null;
		}

		if ( $grant['expires'] < time() ) {
			return null;
		}

		return $grant;
	}

	/**
	 * Exchange a refresh token for a new pair.
	 *
	 * Rotation with family revocation: the presented refresh token is destroyed and
	 * a new pair is issued. If a token that has already been rotated away is
	 * presented again — the signature of a stolen refresh token being replayed — the
	 * entire family is revoked, which logs out both the thief and the legitimate
	 * client. That is the intended outcome: the owner reconnects and the attacker
	 * cannot.
	 *
	 * @param string $refresh   Presented refresh token.
	 * @param string $client_id Client presenting it.
	 * @return array|\WP_Error
	 */
	public static function rotate_refresh_token( $refresh, $client_id ) {
		return self::locked(
			static function () use ( $refresh, $client_id ) {
				return self::rotate_refresh_token_locked( $refresh, $client_id );
			},
			self::busy_error()
		);
	}

	/**
	 * The unlocked half of rotate_refresh_token().
	 *
	 * @see rotate_refresh_token()
	 */
	private static function rotate_refresh_token_locked( $refresh, $client_id ) {
		$tokens      = self::prune_tokens( self::read( self::TOKENS_OPTION ) );
		$fingerprint = self::fingerprint( $refresh );

		if ( ! isset( $tokens[ $fingerprint ] ) || 'refresh' !== $tokens[ $fingerprint ]['type'] ) {
			// A token this server rotated away leaves a tombstone behind precisely
			// so this branch can tell a replay from a typo. Deleting the record
			// outright would make the two indistinguishable, and the family would
			// survive the theft it is there to contain.
			if ( isset( $tokens[ $fingerprint ]['family'] ) ) {
				self::revoke_family( $tokens[ $fingerprint ]['family'] );
			}

			return self::grant_error( __( 'The refresh token is invalid or has been used already.', 'better-payment' ) );
		}

		$grant = $tokens[ $fingerprint ];

		if ( ! hash_equals( (string) $grant['client_id'], (string) $client_id ) ) {
			return self::grant_error( __( 'This refresh token was issued to another client.', 'better-payment' ) );
		}

		if ( $grant['expires'] < time() ) {
			return self::grant_error( __( 'The refresh token has expired.', 'better-payment' ) );
		}

		// Kept, not deleted: see the replay branch above. It expires with the
		// original refresh token, so the tombstone is not a growing record.
		$tokens[ $fingerprint ]['type'] = 'rotated';
		self::write( self::TOKENS_OPTION, $tokens );

		return self::issue_tokens(
			$grant['client_id'],
			$grant['user_id'],
			Context::sanitize( $grant['scope'] ),
			$grant['family']
		);
	}

	/**
	 * Revoke one access token.
	 *
	 * @param string $token Plaintext token.
	 * @return void
	 */
	public static function revoke_access_token( $token ) {
		self::locked(
			static function () use ( $token ) {
				self::revoke_access_token_locked( $token );
			},
			null
		);
	}

	/**
	 * The unlocked half of revoke_access_token().
	 *
	 * @see revoke_access_token()
	 */
	private static function revoke_access_token_locked( $token ) {
		$tokens      = self::read( self::TOKENS_OPTION );
		$fingerprint = self::fingerprint( $token );

		if ( isset( $tokens[ $fingerprint ] ) ) {
			unset( $tokens[ $fingerprint ] );
			self::write( self::TOKENS_OPTION, $tokens );
		}
	}

	/**
	 * Hold the token store at its cap, spending the least valuable records first.
	 *
	 * A rotated token stays behind as a tombstone for thirty days, so one client
	 * refreshing hourly writes about 700 records a month. Cutting the oldest records
	 * regardless of type therefore removed an idle client's live refresh token, and
	 * that client was disconnected by another client's traffic. Losing an old tombstone
	 * costs only replay detection for that one token.
	 *
	 * @param array $tokens Stored tokens, oldest first.
	 * @return array
	 */
	private static function cap_tokens( array $tokens ) {
		$overflow = count( $tokens ) - self::MAX_TOKENS;

		foreach ( array( 'rotated', 'access', 'refresh' ) as $type ) {
			foreach ( $tokens as $fingerprint => $grant ) {
				if ( $overflow < 1 ) {
					return $tokens;
				}

				if ( $type === $grant['type'] ) {
					unset( $tokens[ $fingerprint ] );
					--$overflow;
				}
			}
		}

		return $tokens;
	}

	/**
	 * Revoke every token in one rotation family.
	 *
	 * @param string $family Family id.
	 * @return bool Whether anything was revoked.
	 */
	public static function revoke_family( $family ) {
		return self::locked(
			static function () use ( $family ) {
				return self::revoke_family_locked( $family );
			},
			null
		);
	}

	/**
	 * The unlocked half of revoke_family().
	 *
	 * @see revoke_family()
	 */
	private static function revoke_family_locked( $family ) {
		$tokens  = self::read( self::TOKENS_OPTION );
		$family  = (string) $family;
		$changed = false;

		foreach ( $tokens as $fingerprint => $grant ) {
			if ( isset( $grant['family'] ) && (string) $grant['family'] === $family ) {
				unset( $tokens[ $fingerprint ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			self::write( self::TOKENS_OPTION, $tokens );
		}

		return $changed;
	}

	/**
	 * Revoke every connection at once: all access and refresh tokens, and any
	 * authorization code not yet exchanged. Registered clients stay, so an
	 * application reconnects through the consent screen like a new one.
	 *
	 * Rotated tombstones go too. They exist to tell a replay from a typo, and
	 * with every family gone a replayed token is refused either way.
	 *
	 * @return int How many connections were live before the call.
	 */
	public static function revoke_all() {
		return self::locked(
			static function () {
				return self::revoke_all_locked();
			},
			null
		);
	}

	/**
	 * The unlocked half of revoke_all().
	 *
	 * @see revoke_all()
	 */
	private static function revoke_all_locked() {
		$count = count( self::connections() );

		self::write( self::TOKENS_OPTION, array() );
		self::write( self::CODES_OPTION, array() );

		return $count;
	}

	/**
	 * Every live connection on this site, for the list in wp-admin, newest first.
	 *
	 * Each rotation family holds one live refresh token, so that token is the
	 * connection. Its expiry gives the last refresh: a client that has gone quiet shows
	 * an old date, which is what an owner deciding what to revoke needs to see.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function connections() {
		$out = array();

		foreach ( self::prune_tokens( self::read( self::TOKENS_OPTION ) ) as $grant ) {
			if ( 'refresh' !== $grant['type'] ) {
				continue;
			}

			$client   = self::get_client( $grant['client_id'] );
			$user     = get_userdata( (int) $grant['user_id'] );
			$identity = ClientIdentity::identify( $client );

			$out[] = array(
				'family'          => (string) $grant['family'],
				// The application as the owner knows it ("Claude Code", "Claude (web &
				// desktop)"), never a bare self-registered name it cannot back.
				'client'          => $identity['label'],
				'app'             => $identity['app'],
				'surface'         => $identity['surface'],
				'verified'        => $identity['verified'],
				'claims'          => $identity['claims'],
				'registered_name' => $identity['registered_name'],
				'redirect_host'   => $identity['redirect_host'],
				'user'            => $user ? $user->display_name : __( 'Deleted user', 'better-payment' ),
				'scope'           => array_values( $grant['scope'] ),
				// 0 for a grant issued before the time was recorded.
				'connected'       => isset( $grant['connected'] ) ? (int) $grant['connected'] : 0,
				'last_refreshed'  => (int) $grant['expires'] - self::REFRESH_TTL,
			);
		}

		return array_reverse( $out );
	}

	/**
	 * Drop expired tokens.
	 *
	 * @param array $tokens Stored tokens.
	 * @return array
	 */
	private static function prune_tokens( array $tokens ) {
		$now = time();

		foreach ( $tokens as $fingerprint => $grant ) {
			if ( ! isset( $grant['expires'] ) || $grant['expires'] < $now ) {
				unset( $tokens[ $fingerprint ] );
			}
		}

		return $tokens;
	}

	/**
	 * The one error shape the token endpoint returns.
	 *
	 * @param string $message Human message.
	 * @return \WP_Error
	 */
	private static function grant_error( $message ) {
		return new \WP_Error( 'invalid_grant', (string) $message, array( 'status' => 400 ) );
	}

	/**
	 * Drop everything. Used by the uninstaller and by tests.
	 *
	 * @return void
	 */
	public static function reset() {
		delete_option( self::CLIENTS_OPTION );
		delete_option( self::CODES_OPTION );
		delete_option( self::TOKENS_OPTION );
	}
}
