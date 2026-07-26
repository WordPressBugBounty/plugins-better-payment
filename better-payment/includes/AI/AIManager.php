<?php

namespace Better_Payment\Lite\AI;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\AI\Providers\ClaudeProvider;
use Better_Payment\Lite\AI\Providers\GeminiProvider;
use Better_Payment\Lite\AI\Providers\OpenAIProvider;
use Better_Payment\Lite\AI\Providers\OpenRouterProvider;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Entry point for the AI module.
 *
 * Registers the built-in providers and resolves the currently-active provider
 * (with its runtime config) from the plugin settings option. Wired from
 * Better_Payment::init_ai_module().
 *
 * Settings live in the single `better_payment_settings` option (see
 * Admin/DB.php defaults) so they round-trip through the existing settings REST.
 *
 * @see ProviderRegistry
 * @see \Better_Payment\Lite\API\AIAPI
 */
class AIManager {

    /**
     * Boot the module: register providers.
     */
    public function init(): void {
        self::register_default_providers();
    }

    /**
     * Register the four built-in providers.
     */
    public static function register_default_providers(): void {
        ProviderRegistry::register( 'openai', [
            'label'           => 'OpenAI',
            'class'           => OpenAIProvider::class,
            'models'          => [ 'gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini' ],
            'image_models'    => [ 'gpt-image-1' ],
            'supports_images' => true,
        ] );
        ProviderRegistry::register( 'claude', [
            'label'           => 'Claude (Anthropic)',
            'class'           => ClaudeProvider::class,
            'models'          => [ 'claude-sonnet-5', 'claude-opus-4-8', 'claude-haiku-4-5-20251001' ],
            'image_models'    => [],
            'supports_images' => false,
        ] );
        ProviderRegistry::register( 'gemini', [
            'label'           => 'Google Gemini',
            'class'           => GeminiProvider::class,
            'models'          => [ 'gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash' ],
            'image_models'    => [ 'imagen-3.0-generate-002' ],
            'supports_images' => true,
        ] );
        ProviderRegistry::register( 'openrouter', [
            'label'           => 'OpenRouter',
            'class'           => OpenRouterProvider::class,
            'models'          => [ 'openai/gpt-4o', 'anthropic/claude-sonnet-5', 'google/gemini-2.5-pro' ],
            'image_models'    => [],
            'supports_images' => false,
        ] );
    }

    /**
     * Whether the AI Assistant is enabled in settings.
     */
    public static function is_enabled(): bool {
        return 'yes' === DB::get_settings( 'better_payment_settings_ai_enabled' )
            || '1' === (string) DB::get_settings( 'better_payment_settings_ai_enabled' );
    }

    /**
     * The active provider id from settings (default openai).
     */
    public static function active_provider_id(): string {
        $id = (string) DB::get_settings( 'better_payment_settings_ai_provider' );
        return '' !== $id ? $id : 'openai';
    }

    /**
     * Build the runtime config for a provider id from settings.
     *
     * @return array
     */
    public static function config_for( string $provider_id ): array {
        $temperature = DB::get_settings( 'better_payment_settings_ai_temperature' );
        $max_tokens  = DB::get_settings( 'better_payment_settings_ai_max_tokens' );

        return [
            'api_key'     => (string) DB::get_settings( 'better_payment_settings_ai_api_key_' . $provider_id ),
            'model'       => (string) DB::get_settings( 'better_payment_settings_ai_model' ),
            'image_model' => (string) DB::get_settings( 'better_payment_settings_ai_image_model' ),
            'temperature' => is_numeric( $temperature ) ? (float) $temperature : 0.7,
            'max_tokens'  => is_numeric( $max_tokens ) ? (int) $max_tokens : 4096,
        ];
    }

    /**
     * Instantiate the active provider, or null when unknown/unconfigured.
     *
     * @return \Better_Payment\Lite\AI\Contracts\AIProviderInterface|null
     */
    public static function active_provider() {
        $id = self::active_provider_id();
        return ProviderRegistry::make( $id, self::config_for( $id ) );
    }

    /**
     * The configured system prompt, or a sensible default.
     */
    public static function system_prompt(): string {
        $custom = trim( (string) DB::get_settings( 'better_payment_settings_ai_system_prompt' ) );
        return '' !== $custom ? $custom : '';
    }
}
