<?php

namespace Better_Payment\Lite\AI;

use Better_Payment\Lite\AI\Contracts\AIProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registry of available AI providers.
 *
 * Mirrors {@see \Better_Payment\Lite\Campaign\Elements\ElementRegistry}: a static
 * store PHP populates, third parties extend via filter, and the rest of the AI
 * layer reads. Definitions are lazy — a provider is only instantiated (with its
 * runtime config) when {@see self::make()} is called.
 *
 * Register a custom provider:
 *   add_filter( 'better_payment/ai/providers', function ( $providers ) {
 *       $providers['myllm'] = [
 *           'label'           => 'My LLM',
 *           'class'           => My\Provider::class,   // implements AIProviderInterface
 *           'models'          => [ 'my-model-1' ],
 *           'supports_images' => false,
 *       ];
 *       return $providers;
 *   } );
 */
class ProviderRegistry {

    /**
     * @var array<string, array>
     */
    private static array $providers = [];

    /**
     * Register a provider definition.
     *
     * @param string $id  Stable provider id.
     * @param array  $def { @type string $label; @type string $class; @type array $models; @type bool $supports_images; }
     */
    public static function register( string $id, array $def ): void {
        self::$providers[ $id ] = array_merge( [
            'label'           => $id,
            'class'           => '',
            'models'          => [],
            'image_models'    => [],
            'supports_images' => false,
        ], $def );
    }

    /**
     * All provider definitions, after the extension filter.
     *
     * @return array<string, array>
     */
    public static function get_all(): array {
        /**
         * Filter registered AI providers.
         *
         * @param array<string, array> $providers
         */
        return apply_filters( 'better_payment/ai/providers', self::$providers );
    }

    /**
     * A single provider definition, or null.
     *
     * @return array|null
     */
    public static function get( string $id ) {
        $all = self::get_all();
        return $all[ $id ] ?? null;
    }

    public static function exists( string $id ): bool {
        return null !== self::get( $id );
    }

    /**
     * Instantiate a provider with runtime config.
     *
     * @param string $id
     * @param array  $config Passed to the provider constructor (api_key, model, ...).
     * @return AIProviderInterface|null Null when the id is unknown or the class is invalid.
     */
    public static function make( string $id, array $config = [] ) {
        $def = self::get( $id );
        if ( null === $def || empty( $def['class'] ) || ! class_exists( $def['class'] ) ) {
            return null;
        }
        $instance = new $def['class']( $config );
        return $instance instanceof AIProviderInterface ? $instance : null;
    }

    /**
     * Public-safe descriptors for the UI (no secrets), one per provider.
     *
     * @return array<int, array{id: string, label: string, models: array, supports_images: bool}>
     */
    public static function descriptors(): array {
        $out = [];
        foreach ( self::get_all() as $id => $def ) {
            $out[] = [
                'id'              => $id,
                'label'           => $def['label'],
                'models'          => array_values( $def['models'] ),
                'image_models'    => array_values( $def['image_models'] ?? [] ),
                'supports_images' => (bool) $def['supports_images'],
            ];
        }
        return $out;
    }
}
