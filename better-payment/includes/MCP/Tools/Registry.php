<?php
/**
 * The MCP tool registry.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\MCP\Auth\Context;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects every tool this installation offers and runs them.
 *
 * One registry, one set of guards. Providers declare definitions; the registry decides
 * the name format, rejects duplicates, attaches the capability each tool needs and
 * normalises the annotations, so a Pro tool and a Lite tool are held to the same rules
 * and nothing can register by going around it.
 *
 * Deliberately NOT built on WordPress's Abilities API. That API is core only from 6.9,
 * this plugin supports 6.0, and the module would then offer a different tool list on
 * two halves of its install base — for a registry this small, with no second consumer.
 *
 * @since 2.5.0
 */
class Registry {

	/**
	 * Capability a tool gets when it declares none.
	 */
	const DEFAULT_CAPABILITY = 'manage_options';

	/**
	 * Per-request memo of the collected tools.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private static $tools = null;

	/**
	 * Provider classes shipped by Lite.
	 *
	 * @return string[]
	 */
	private static function built_in_providers() {
		return array(
			Site::class,
			Transactions::class,
			Customers::class,
			Subscriptions::class,
			Settings::class,
			Campaigns::class,
		);
	}

	/**
	 * Every tool this site offers, keyed by name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		if ( null !== self::$tools ) {
			return self::$tools;
		}

		/**
		 * Filters the classes that contribute MCP tools.
		 *
		 * This is how Better Payment Pro and third-party add-ons publish their own
		 * tools. Each class must implement `Contracts\ToolProvider`; one that does
		 * not is skipped rather than fatalling, so an add-on written against a newer
		 * Lite cannot take the endpoint down.
		 *
		 * @since 2.5.0
		 *
		 * @param mixed $providers Provider class names. Filtered, so a site can
		 *                        put anything in here.
		 */
		$providers = (array) apply_filters( 'better_payment/mcp/tool_providers', self::built_in_providers() );

		$tools = array();

		foreach ( $providers as $provider ) {
			if ( ! is_string( $provider ) || ! class_exists( $provider ) ) {
				continue;
			}

			if ( ! in_array( ToolProvider::class, class_implements( $provider ), true ) ) {
				continue;
			}

			$definitions = call_user_func( array( $provider, 'definitions' ) );

			if ( ! is_array( $definitions ) ) {
				continue;
			}

			foreach ( $definitions as $name => $definition ) {
				$normalized = self::normalize( $name, $definition );

				if ( null === $normalized ) {
					continue;
				}

				// First registration wins. A later provider silently replacing an
				// earlier tool would let an add-on shadow `settings_update` with its
				// own implementation.
				if ( isset( $tools[ $normalized['name'] ] ) ) {
					continue;
				}

				$tools[ $normalized['name'] ] = $normalized;
			}
		}

		/**
		 * Filters the assembled MCP tool list.
		 *
		 * Runs after normalisation, so entries here are already in the registry's
		 * shape. Use it to remove a tool; use `tool_providers` to add one.
		 *
		 * @since 2.5.0
		 *
		 * @param mixed $tools Tools keyed by name. Filtered, so a site can put
		 *                     anything in here.
		 */
		$tools = (array) apply_filters( 'better_payment/mcp/tools', $tools );

		self::$tools = $tools;

		return self::$tools;
	}

	/**
	 * Validate and complete one definition.
	 *
	 * @param string $name       Tool name.
	 * @param mixed  $definition Raw definition.
	 * @return array<string, mixed>|null Null when the definition cannot be used.
	 */
	public static function normalize( $name, $definition ) {
		$name = (string) $name;

		// Lowercase, underscore-separated, no namespace. MCP clients surface the raw
		// name to the user, and every client agrees on this subset.
		if ( ! preg_match( '/^[a-z][a-z0-9_]{0,63}$/', $name ) ) {
			return null;
		}

		if ( ! is_array( $definition ) ) {
			return null;
		}

		if ( ! isset( $definition['callback'] ) || ! is_callable( $definition['callback'] ) ) {
			return null;
		}

		$annotations = isset( $definition['annotations'] ) && is_array( $definition['annotations'] )
			? $definition['annotations']
			: array();

		return array(
			'name'        => $name,
			'title'       => isset( $definition['title'] ) ? (string) $definition['title'] : $name,
			'description' => isset( $definition['description'] ) ? (string) $definition['description'] : '',
			'inputSchema' => isset( $definition['inputSchema'] ) && is_array( $definition['inputSchema'] )
				? $definition['inputSchema']
				: array(
					'type'       => 'object',
					'properties' => (object) array(),
				),
			'annotations' => array(
				'title'           => isset( $definition['title'] ) ? (string) $definition['title'] : $name,
				'readOnlyHint'    => ! empty( $annotations['readOnlyHint'] ),
				'destructiveHint' => ! empty( $annotations['destructiveHint'] ),
				'idempotentHint'  => ! empty( $annotations['idempotentHint'] ),
				// Nothing here reaches beyond this site's own database and gateways.
				'openWorldHint'   => false,
			),
			'capability'  => self::capability_for( $name, $definition ),
			'callback'    => $definition['callback'],
		);
	}

	/**
	 * The capability one tool requires.
	 *
	 * `manage_options` is not right for everything: a support user looking up one
	 * customer's payment history should not also be able to rewrite the site's
	 * options. A site retunes the whole map through one filter rather than having to
	 * re-register tools.
	 *
	 * @param string $name       Tool name.
	 * @param array  $definition Raw definition.
	 * @return string
	 */
	private static function capability_for( $name, array $definition ) {
		$declared = isset( $definition['capability'] ) && is_string( $definition['capability'] ) && '' !== $definition['capability']
			? $definition['capability']
			: self::DEFAULT_CAPABILITY;

		/**
		 * Filters the capability required by each MCP tool.
		 *
		 * @since 2.5.0
		 *
		 * @param mixed $map Tool name => capability. Filtered, so a site can put
		 *                   anything in here.
		 */
		$map = (array) apply_filters( 'better_payment/mcp/capability_map', array() );

		if ( isset( $map[ $name ] ) && is_string( $map[ $name ] ) && '' !== $map[ $name ] ) {
			return $map[ $name ];
		}

		return $declared;
	}

	/**
	 * One tool, or null.
	 *
	 * @param string $name Tool name.
	 * @return array<string, mixed>|null
	 */
	public static function get( $name ) {
		$tools = self::all();
		$name  = (string) $name;

		return isset( $tools[ $name ] ) ? $tools[ $name ] : null;
	}

	/**
	 * The list an MCP client sees.
	 *
	 * The capability and the callback are internal bookkeeping and are dropped here:
	 * a capability name tells a client nothing it can act on, and publishing the
	 * callback would describe the plugin's internals to every connected model.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function describe() {
		$out = array();

		foreach ( self::all() as $tool ) {
			$out[] = array(
				'name'        => $tool['name'],
				'title'       => $tool['title'],
				'description' => $tool['description'],
				'inputSchema' => $tool['inputSchema'],
				'annotations' => $tool['annotations'],
			);
		}

		return $out;
	}

	/**
	 * Run one tool.
	 *
	 * The capability is checked HERE rather than inside each tool, so a tool cannot
	 * be added without one and the check cannot drift per tool.
	 *
	 * @param string $name      Tool name.
	 * @param array  $arguments Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function execute( $name, array $arguments = array() ) {
		$tool = self::get( $name );

		if ( null === $tool ) {
			/* translators: %s: tool name. */
			return Result::not_found( sprintf( __( 'Unknown tool: %s', 'better-payment' ), (string) $name ) );
		}

		if ( ! current_user_can( $tool['capability'] ) ) {
			return Result::refused( __( 'This account is not allowed to use that tool.', 'better-payment' ) );
		}

		// A read-only grant may run read-only tools and nothing else. The annotation
		// is the single source of that decision, so a tool cannot be written that
		// changes state while claiming to be a read.
		if ( empty( $tool['annotations']['readOnlyHint'] ) && ! Context::allows( Context::SCOPE_WRITE ) ) {
			return Result::refused( __( 'This connection was granted read-only access to Better Payment.', 'better-payment' ) );
		}

		// Here for the same reason as the capability check: a tool cannot forget it.
		$arguments = Schema::validate_arguments( $tool['inputSchema'], $arguments );

		if ( is_wp_error( $arguments ) ) {
			return $arguments;
		}

		$result = call_user_func( $tool['callback'], $arguments );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return is_array( $result ) ? $result : array( 'result' => Result::plain( $result ) );
	}

	/**
	 * Drop the memo. Test helper — the registry is built once per request.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$tools = null;
	}
}
