<?php
/**
 * What the current MCP request is allowed to do.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carries the authenticated caller's grant through the request.
 *
 * Two credentials reach this endpoint and they are not equivalent:
 *
 * - An **Application Password** is the site owner's own credential, created in
 *   wp-admin and revocable there. It carries whatever its owner can do, so it is
 *   recorded here as an unscoped grant.
 * - An **OAuth access token** was granted to a third-party client — Claude, ChatGPT,
 *   an IDE — on a consent screen where the owner ticked what it may do. That choice
 *   only means something if something enforces it, which is what `allows()` is for.
 *
 * The capability check still runs on top of this for every tool. A scope narrows what
 * a credential may do; it never widens what its user may do.
 *
 * @since 2.5.0
 */
class Context {

	/**
	 * Read access: every tool annotated `readOnlyHint`.
	 */
	const SCOPE_READ = 'read';

	/**
	 * Write access: everything that changes state.
	 */
	const SCOPE_WRITE = 'write';

	/**
	 * Granted scopes, or null for an unscoped credential.
	 *
	 * @var string[]|null
	 */
	private static $scopes = null;

	/**
	 * Whether a credential has been resolved for this request.
	 *
	 * @var bool
	 */
	private static $resolved = false;

	/**
	 * Every scope this server issues.
	 *
	 * @return string[]
	 */
	public static function supported() {
		return array( self::SCOPE_READ, self::SCOPE_WRITE );
	}

	/**
	 * Keep only the scopes this server knows.
	 *
	 * An unknown scope is dropped rather than refused: RFC 6749 lets a server issue
	 * a narrower grant than was asked for, and refusing outright would break clients
	 * that request a boilerplate scope list.
	 *
	 * @param mixed $requested Space-separated string or array.
	 * @return string[]
	 */
	public static function sanitize( $requested ) {
		if ( is_string( $requested ) ) {
			$requested = preg_split( '/\s+/', trim( $requested ) );
		}

		if ( ! is_array( $requested ) ) {
			$requested = array();
		}

		$clean = array();

		foreach ( $requested as $scope ) {
			$scope = sanitize_key( (string) $scope );

			if ( in_array( $scope, self::supported(), true ) && ! in_array( $scope, $clean, true ) ) {
				$clean[] = $scope;
			}
		}

		// A grant with no recognisable scope is a read grant, not a grant that can
		// do nothing: a client that asked for nothing usable still wants to read.
		if ( empty( $clean ) ) {
			$clean[] = self::SCOPE_READ;
		}

		return $clean;
	}

	/**
	 * Record an unscoped credential (an Application Password).
	 *
	 * @return void
	 */
	public static function set_unscoped() {
		self::$scopes   = null;
		self::$resolved = true;
	}

	/**
	 * Record the scopes an access token was issued with.
	 *
	 * @param mixed $scopes Granted scopes.
	 * @return void
	 */
	public static function set_scopes( $scopes ) {
		self::$scopes   = self::sanitize( $scopes );
		self::$resolved = true;
	}

	/**
	 * Granted scopes, or null when the credential is unscoped.
	 *
	 * @return string[]|null
	 */
	public static function scopes() {
		return self::$scopes;
	}

	/**
	 * Whether this request may do something needing the given scope.
	 *
	 * @param string $scope Scope slug.
	 * @return bool
	 */
	public static function allows( $scope ) {
		if ( ! self::$resolved ) {
			// Nothing authenticated, so nothing is allowed. Reached only if a caller
			// skips Authenticator, which the transport never does.
			return false;
		}

		if ( null === self::$scopes ) {
			return true;
		}

		return in_array( (string) $scope, self::$scopes, true );
	}

	/**
	 * Forget the current grant. Test helper, and used between batched requests.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$scopes   = null;
		self::$resolved = false;
	}
}
