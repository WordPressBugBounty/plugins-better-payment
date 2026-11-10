<?php
/**
 * Contract for a class that contributes MCP tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * What an add-on implements to put its own tools on Better Payment's MCP endpoint.
 *
 * Register the class name on `better_payment/mcp/tool_providers`. The registry applies
 * the same guards to every provider — name format, duplicate check, capability,
 * annotations — so an add-on gets the module's rules rather than its own.
 *
 * @since 2.5.0
 */
interface ToolProvider {

	/**
	 * Tool definitions, keyed by tool name.
	 *
	 * Each definition takes `title`, `description`, `inputSchema`, `callback`, and
	 * optionally `capability` and `annotations`. See `Tools\Registry::normalize()`.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions();
}
