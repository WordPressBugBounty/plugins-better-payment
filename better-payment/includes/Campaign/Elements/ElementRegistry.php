<?php

namespace Better_Payment\Lite\Campaign\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registry for campaign builder element types.
 *
 * Mirrors Fluent Forms' Components.php pattern: a central registry that PHP
 * populates, third-party code can extend via filter, and the JS builder
 * reads via localized data.
 *
 * Usage:
 *   ElementRegistry::get_all()  — returns all registered elements
 *   ElementRegistry::get($type) — returns a single element schema
 *
 * To add custom elements from another plugin:
 *   add_filter( 'better_payment/campaign_elements', function( $elements ) {
 *       $elements['my_element'] = [ 'type' => 'my_element', ... ];
 *       return $elements;
 *   } );
 */
class ElementRegistry {

    /** @var array<string, array> */
    private static array $elements = [];

    /**
     * Register an element type.
     *
     * @param string $type   Unique element type key.
     * @param array  $schema Element schema (see CampaignElements.php for shape).
     */
    public static function register( string $type, array $schema ): void {
        self::$elements[ $type ] = array_merge( $schema, [ 'type' => $type ] );
    }

    /**
     * Get all registered element schemas, after applying the extension filter.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        return apply_filters( 'better_payment/campaign_elements', self::$elements );
    }

    /**
     * Get a single element schema by type.
     *
     * @param string $type
     * @return array|null
     */
    public static function get( string $type ): ?array {
        $all = self::get_all();
        return $all[ $type ] ?? null;
    }

    /**
     * Get default settings for an element type.
     *
     * @param string $type
     * @return array
     */
    public static function get_defaults( string $type ): array {
        $schema = self::get( $type );
        return $schema['defaultSettings'] ?? [];
    }
}
