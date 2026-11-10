<?php
/**
 * Activity record for MCP tool calls.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records which tools were called, by whom, and how each call ended.
 *
 * **Never the arguments.** A tool's input carries customer e-mail addresses, settings
 * payloads and campaign copy; a log that keeps them turns a diagnostic aid into a
 * second copy of the data the module is careful about everywhere else. So a record is
 * a tool name, an actor, an outcome and a timestamp, and nothing else.
 *
 * Buffered in memory and written once on `shutdown`: a JSON-RPC request may carry
 * several tool calls, and one option write per call would make the log the most
 * expensive thing in the request.
 *
 * @since 2.5.0
 */
class ActivityLog {

	/**
	 * Option holding the ring buffer. Not autoloaded.
	 */
	const OPTION = 'better_payment_mcp_activity';

	/**
	 * How many records are kept.
	 */
	const MAX_RECORDS = 200;

	/**
	 * Largest `limit` the reporting tool accepts.
	 */
	const MAX_LIMIT = 200;

	/**
	 * Records buffered for this request.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $buffer = array();

	/**
	 * Whether the shutdown flush is already attached.
	 *
	 * @var bool
	 */
	private static $hooked = false;

	/**
	 * Buffer one call.
	 *
	 * @param string $tool    Tool name.
	 * @param string $outcome One of `ok`, `error`, `refused`.
	 * @param int    $user_id Acting user.
	 * @return void
	 */
	public static function record( $tool, $outcome, $user_id = 0 ) {
		$tool = (string) $tool;

		if ( '' === $tool ) {
			return;
		}

		self::$buffer[] = array(
			'tool'    => $tool,
			'outcome' => in_array( $outcome, array( 'ok', 'error', 'refused' ), true ) ? $outcome : 'ok',
			'user'    => (int) $user_id,
			'time'    => gmdate( 'c' ),
		);

		if ( ! self::$hooked ) {
			self::$hooked = true;
			add_action( 'shutdown', array( __CLASS__, 'flush' ), 99 );
		}
	}

	/**
	 * Write the buffer out.
	 *
	 * @return void
	 */
	public static function flush() {
		if ( empty( self::$buffer ) ) {
			return;
		}

		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		$stored = array_merge( $stored, self::$buffer );

		if ( count( $stored ) > self::MAX_RECORDS ) {
			$stored = array_slice( $stored, -self::MAX_RECORDS );
		}

		self::$buffer = array();

		// Explicitly not autoloaded: this option is read by one tool and never on a
		// page load.
		update_option( self::OPTION, $stored, false );
	}

	/**
	 * Most recent calls, newest first.
	 *
	 * @param mixed $limit How many to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( $limit = 50 ) {
		$limit = is_numeric( $limit ) ? (int) $limit : 50;
		$limit = $limit < 1 ? 1 : $limit;
		$limit = $limit > self::MAX_LIMIT ? self::MAX_LIMIT : $limit;

		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();

		// The buffer holds calls made in THIS request, which are exactly the ones a
		// client asking "what just happened" means.
		$stored = array_merge( $stored, self::$buffer );

		return array_slice( array_reverse( $stored ), 0, $limit );
	}

	/**
	 * When the last tool call was made, for the settings screen's
	 * "Last activity" figure. Reads the time only, never the tool or the user.
	 *
	 * @return string ISO 8601 time in UTC, or '' when nothing was ever called.
	 */
	public static function last_time() {
		$latest = self::recent( 1 );

		return isset( $latest[0]['time'] ) ? (string) $latest[0]['time'] : '';
	}

	/**
	 * Per-tool call counts.
	 *
	 * @return array<string, int>
	 */
	public static function summary() {
		$stored = get_option( self::OPTION, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$stored = array_merge( $stored, self::$buffer );

		$summary = array();

		foreach ( $stored as $record ) {
			if ( ! isset( $record['tool'] ) ) {
				continue;
			}

			$tool = (string) $record['tool'];

			$summary[ $tool ] = isset( $summary[ $tool ] ) ? $summary[ $tool ] + 1 : 1;
		}

		arsort( $summary );

		return $summary;
	}

	/**
	 * Drop everything. Used by the uninstaller and by tests.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$buffer = array();
		delete_option( self::OPTION );
	}
}
