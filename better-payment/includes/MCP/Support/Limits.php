<?php
/**
 * Request bounds for the MCP endpoint.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The bounds the transport applies before any tool runs.
 *
 * JSON-RPC re-opens limits that each tool closed on its own: a tool clamps its own
 * page size, but nothing in a tool stops a client posting a 40 MB body or asking for
 * two hundred tool calls in one request. These are the ceilings on the envelope.
 *
 * @since 2.5.0
 */
class Limits {

	/**
	 * Largest request body the endpoint will parse, in bytes.
	 */
	const MAX_BODY_BYTES = 1048576;

	/**
	 * Most messages accepted in one JSON-RPC batch.
	 *
	 * MCP dropped batching in the 2025-06-18 revision, but older clients still send
	 * arrays, so they are answered rather than rejected — just not unboundedly.
	 */
	const MAX_BATCH = 20;

	/**
	 * Largest body this site accepts.
	 *
	 * @return int
	 */
	public static function max_body_bytes() {
		/**
		 * Filters the largest MCP request body the endpoint will parse.
		 *
		 * @since 2.5.0
		 *
		 * @param int $bytes Maximum body size in bytes.
		 */
		$bytes = (int) apply_filters( 'better_payment/mcp/max_body_bytes', self::MAX_BODY_BYTES );

		return $bytes > 0 ? $bytes : self::MAX_BODY_BYTES;
	}

	/**
	 * Longest JSON-RPC batch this site answers.
	 *
	 * @return int
	 */
	public static function max_batch() {
		/**
		 * Filters the longest JSON-RPC batch the endpoint answers.
		 *
		 * @since 2.5.0
		 *
		 * @param int $count Maximum number of messages.
		 */
		$count = (int) apply_filters( 'better_payment/mcp/max_batch', self::MAX_BATCH );

		return $count > 0 ? $count : self::MAX_BATCH;
	}
}
