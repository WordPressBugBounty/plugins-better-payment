<?php

namespace Better_Payment\Lite\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OpenAI provider (Chat Completions + Images).
 *
 * Also the base for any OpenAI-compatible API (see {@see OpenRouterProvider}),
 * so the endpoint and auth headers are overridable.
 */
class OpenAIProvider extends AbstractProvider {

    public function get_id(): string {
        return 'openai';
    }

    public function get_label(): string {
        return 'OpenAI';
    }

    public function get_models(): array {
        return [ 'gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini' ];
    }

    public function supports_images(): bool {
        return true;
    }

    protected function chat_endpoint(): string {
        return 'https://api.openai.com/v1/chat/completions';
    }

    protected function images_endpoint(): string {
        return 'https://api.openai.com/v1/images/generations';
    }

    /**
     * @return array<string, string>
     */
    protected function auth_headers(): array {
        return [ 'Authorization' => 'Bearer ' . sanitize_text_field( (string) $this->config['api_key'] ) ];
    }

    public function chat( array $messages, array $options = [] ): array {
        list( $system, $turns ) = self::split_system( $messages, (string) ( $options['system'] ?? '' ) );

        $payload_messages = [];
        if ( '' !== $system ) {
            $payload_messages[] = [ 'role' => 'system', 'content' => $system ];
        }
        foreach ( $turns as $turn ) {
            $payload_messages[] = $turn;
        }

        $body = [
            'model'       => $this->resolve_model( $options ),
            'messages'    => $payload_messages,
            'temperature' => $this->temperature( $options ),
            'max_tokens'  => $this->max_tokens( $options ),
        ];

        if ( ! empty( $options['tools'] ) ) {
            $body['tools']       = $this->format_tools( $options['tools'] );
            $body['tool_choice'] = 'required' === ( $options['tool_choice'] ?? '' ) ? 'required' : 'auto';
        }

        $result = $this->post_json( $this->chat_endpoint(), $this->auth_headers(), $body );
        if ( ! $result['ok'] ) {
            return [ 'text' => '', 'tool_calls' => [], 'usage' => [], 'error' => $result['error'], 'raw' => $result['data'] ];
        }

        return $this->normalize_chat( $result['data'] );
    }

    /**
     * Map provider-agnostic tool descriptors to OpenAI function tools.
     *
     * @param array $tools
     * @return array
     */
    protected function format_tools( array $tools ): array {
        $out = [];
        foreach ( $tools as $tool ) {
            $out[] = [
                'type'     => 'function',
                'function' => [
                    'name'        => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters'  => self::params_to_json_schema( $tool['params'] ?? [] ),
                ],
            ];
        }
        return $out;
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected function normalize_chat( $data ): array {
        $message    = $data['choices'][0]['message'] ?? [];
        $text       = (string) ( $message['content'] ?? '' );
        $tool_calls = [];

        foreach ( (array) ( $message['tool_calls'] ?? [] ) as $call ) {
            if ( ! isset( $call['function']['name'] ) ) {
                continue;
            }
            $tool_calls[] = [
                'name'      => (string) $call['function']['name'],
                'arguments' => self::decode_arguments( $call['function']['arguments'] ?? [] ),
            ];
        }

        return [
            'text'       => $text,
            'tool_calls' => $tool_calls,
            'usage'      => $data['usage'] ?? [],
            'error'      => null,
            'raw'        => $data,
        ];
    }

    public function generate_image( string $prompt, array $options = [] ): array {
        $model = ! empty( $options['model'] ) ? (string) $options['model']
            : ( ! empty( $this->config['image_model'] ) ? (string) $this->config['image_model'] : 'gpt-image-1' );

        $body = [
            'model'  => $model,
            'prompt' => $prompt,
            'size'   => (string) ( $options['size'] ?? '1024x1024' ),
            'n'      => 1,
        ];

        $result = $this->post_json( $this->images_endpoint(), $this->auth_headers(), $body, 120 );
        if ( ! $result['ok'] ) {
            return [ 'error' => $result['error'] ];
        }

        $item = $result['data']['data'][0] ?? [];
        if ( ! empty( $item['b64_json'] ) ) {
            return [ 'b64' => (string) $item['b64_json'], 'mime' => 'image/png', 'error' => null ];
        }
        if ( ! empty( $item['url'] ) ) {
            return [ 'url' => (string) $item['url'], 'error' => null ];
        }
        return [ 'error' => __( 'Image provider returned no image.', 'better-payment' ) ];
    }
}
