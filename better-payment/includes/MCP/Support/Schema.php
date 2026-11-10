<?php
/**
 * Shared JSON Schema fragments for Better Payment's MCP tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The schema fragments every tool shares, plus the clamps that back them.
 *
 * Two rules carry across the whole module:
 *
 * 1. **No union types.** `[ 'integer', 'null' ]` is legal JSON Schema and several
 *    MCP clients reject it outright. A nullable value is expressed as an OPTIONAL
 *    property instead.
 * 2. **Every list is bounded.** An unbounded SELECT over a large transactions table
 *    exhausts memory inside one HTTP request, and the client only sees a timeout.
 *
 * A schema declares bounds; it does not enforce them. Nothing in this module runs a
 * JSON Schema validator, so the clamps below are applied in PHP by every tool that
 * paginates.
 *
 * @since 2.5.0
 */
class Schema {

	/**
	 * Hard ceiling on any paginated tool.
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * Default page size.
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Pagination properties, for merging into an object schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function pagination() {
		return array(
			'page'     => array(
				'type'        => 'integer',
				'description' => 'Page number, starting at 1.',
				'minimum'     => 1,
				'default'     => 1,
			),
			'per_page' => array(
				'type'        => 'integer',
				'description' => 'Results per page.',
				'minimum'     => 1,
				'maximum'     => self::MAX_PER_PAGE,
				'default'     => self::DEFAULT_PER_PAGE,
			),
		);
	}

	/**
	 * A `YYYY-MM-DD` date property.
	 *
	 * @param string $description Human description.
	 * @return array<string, mixed>
	 */
	public static function date( $description ) {
		return array(
			'type'        => 'string',
			'description' => (string) $description,
			'format'      => 'date',
		);
	}

	/**
	 * Transaction status buckets.
	 *
	 * These are BUCKETS, not raw column values. `Classes\Helper::transaction_statuses_with_type_v2()`
	 * folds the raw statuses (`paid`, `Completed`, `success`, `no_payment_required`,
	 * `pending`, `unpaid`, `refunded`, NULL) into these four. Declaring raw statuses
	 * here would produce filters that silently match nothing.
	 *
	 * @return array<string, mixed>
	 */
	public static function transaction_status() {
		return array(
			'type'        => 'string',
			'description' => 'Status bucket to filter by. Buckets group the raw gateway statuses; "all" applies no status filter.',
			'enum'        => array( 'all', 'completed', 'incomplete', 'refunded' ),
			'default'     => 'all',
		);
	}

	/**
	 * Payment gateway source.
	 *
	 * @return array<string, mixed>
	 */
	public static function gateway() {
		return array(
			'type'        => 'string',
			'description' => 'Payment gateway that produced the transaction.',
			'enum'        => array( 'stripe', 'paypal', 'paystack' ),
		);
	}

	/**
	 * The `include_customer_data` opt-in every payer-bearing list declares.
	 *
	 * @return array<string, mixed>
	 */
	public static function customer_opt_in() {
		return array(
			'type'        => 'boolean',
			'description' => 'Include the payer name, email and submitted form fields. Off by default because this data leaves the site with the result.',
			'default'     => false,
		);
	}

	/**
	 * Wrap properties into an object schema.
	 *
	 * `additionalProperties` stays `false`: an unrecognised argument is a client bug,
	 * and the one surface where the caller cannot read our source is the worst place
	 * to hide one.
	 *
	 * @param array    $properties Property map.
	 * @param string[] $required   Required property names.
	 * @return array<string, mixed>
	 */
	public static function object( array $properties, array $required = array() ) {
		$schema = array(
			'type'                 => 'object',
			'properties'           => (object) $properties,
			'additionalProperties' => false,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = array_values( $required );
		}

		return $schema;
	}

	/**
	 * Hold a tool call's arguments to the tool's declared top-level schema.
	 *
	 * Runs before the tool, so `required`, `type`, `enum` and `additionalProperties`
	 * are enforced rather than advertised. A value of the wrong type is refused and
	 * never coerced to a default: `"abc"` as an order id must not become 0 on its way
	 * into `transaction_delete`. Numeric strings and `"true"` / `"false"` are accepted,
	 * because clients send them for arguments typed by a person. Bounds stay with the
	 * clamps below, and nested values stay with the tool that understands them.
	 *
	 * @param array $schema    Tool input schema.
	 * @param array $arguments Arguments from the client.
	 * @return array|\WP_Error Arguments with scalars normalised, or the refusal.
	 */
	public static function validate_arguments( array $schema, array $arguments ) {
		$properties = isset( $schema['properties'] ) ? (array) $schema['properties'] : array();
		$required   = isset( $schema['required'] ) ? (array) $schema['required'] : array();
		$closed     = isset( $schema['additionalProperties'] ) && false === $schema['additionalProperties'];

		foreach ( $required as $key ) {
			if ( ! isset( $arguments[ $key ] ) || '' === $arguments[ $key ] ) {
				/* translators: %s: argument name. */
				return Result::invalid( sprintf( __( 'Missing required argument: %s', 'better-payment' ), $key ) );
			}
		}

		foreach ( $arguments as $key => $value ) {
			if ( ! isset( $properties[ $key ] ) ) {
				if ( $closed ) {
					/* translators: %s: argument name. */
					return Result::invalid( sprintf( __( 'Unknown argument: %s', 'better-payment' ), $key ) );
				}

				continue;
			}

			$definition = (array) $properties[ $key ];
			$type       = isset( $definition['type'] ) ? (string) $definition['type'] : '';
			$normalised = self::normalise_value( $value, $type );

			if ( null === $normalised ) {
				/* translators: 1: argument name, 2: JSON type. */
				return Result::invalid( sprintf( __( 'Argument %1$s must be of type %2$s.', 'better-payment' ), $key, $type ) );
			}

			if ( ! empty( $definition['enum'] ) && ! in_array( $normalised, (array) $definition['enum'], true ) ) {
				/* translators: %s: argument name. */
				return Result::invalid( sprintf( __( 'Invalid value for argument: %s', 'better-payment' ), $key ) );
			}

			$arguments[ $key ] = $normalised;
		}

		return $arguments;
	}

	/**
	 * One value in its declared JSON type.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $type  JSON Schema type, or '' when none is declared.
	 * @return mixed The value, or null when it does not fit the type.
	 */
	private static function normalise_value( $value, $type ) {
		switch ( $type ) {
			case 'integer':
				if ( is_int( $value ) ) {
					return $value;
				}

				if ( is_float( $value ) && floor( $value ) === $value ) {
					return (int) $value;
				}

				return is_string( $value ) && preg_match( '/^-?\d+$/', $value ) ? (int) $value : null;

			case 'number':
				return is_numeric( $value ) ? 0 + $value : null;

			case 'boolean':
				if ( is_bool( $value ) ) {
					return $value;
				}

				if ( in_array( $value, array( 'true', 1, '1' ), true ) ) {
					return true;
				}

				return in_array( $value, array( 'false', 0, '0' ), true ) ? false : null;

			case 'string':
				if ( is_string( $value ) ) {
					return $value;
				}

				return is_int( $value ) || is_float( $value ) ? (string) $value : null;

			case 'array':
			case 'object':
				return is_array( $value ) ? $value : null;

			default:
				return $value;
		}
	}

	/**
	 * Clamp a page size to the documented bounds.
	 *
	 * @param mixed $value Raw input value.
	 * @return int
	 */
	public static function clamp_per_page( $value ) {
		$per_page = is_numeric( $value ) ? (int) $value : self::DEFAULT_PER_PAGE;

		if ( $per_page < 1 ) {
			$per_page = self::DEFAULT_PER_PAGE;
		}

		if ( $per_page > self::MAX_PER_PAGE ) {
			$per_page = self::MAX_PER_PAGE;
		}

		return $per_page;
	}

	/**
	 * Clamp a page number.
	 *
	 * @param mixed $value Raw input value.
	 * @return int
	 */
	public static function clamp_page( $value ) {
		$page = is_numeric( $value ) ? (int) $value : 1;

		return $page < 1 ? 1 : $page;
	}
}
