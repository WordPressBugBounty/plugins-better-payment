<?php

namespace Better_Payment\Lite\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OpenRouter provider — OpenAI-compatible gateway to many models with one key.
 *
 * Reuses {@see OpenAIProvider}'s request/response mapping; only the endpoint,
 * auth headers and model list differ. OpenRouter does not offer a unified image
 * endpoint here, so image generation is disabled.
 */
class OpenRouterProvider extends OpenAIProvider {

    public function get_id(): string {
        return 'openrouter';
    }

    public function get_label(): string {
        return 'OpenRouter';
    }

    public function get_models(): array {
        return [
            'openai/gpt-4o',
            'anthropic/claude-sonnet-5',
            'google/gemini-2.5-pro',
            'meta-llama/llama-3.3-70b-instruct',
        ];
    }

    public function supports_images(): bool {
        return false;
    }

    public function generate_image( string $prompt, array $options = [] ): array {
        return [ 'error' => __( 'Image generation is not available via OpenRouter in this plugin.', 'better-payment' ) ];
    }

    protected function chat_endpoint(): string {
        return 'https://openrouter.ai/api/v1/chat/completions';
    }

    /**
     * @return array<string, string>
     */
    protected function auth_headers(): array {
        return [
            'Authorization' => 'Bearer ' . sanitize_text_field( (string) $this->config['api_key'] ),
            // Optional attribution headers recommended by OpenRouter.
            'HTTP-Referer'  => home_url(),
            'X-Title'       => 'Better Payment',
        ];
    }
}
