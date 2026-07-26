<?php

namespace Better_Payment\Lite\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Google Gemini provider (Generative Language API).
 *
 * Auth is a query-string API key (not a bearer header). Chat uses
 * functionDeclarations for tool calling; images use an Imagen `:predict` call.
 */
class GeminiProvider extends AbstractProvider {

    const BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function get_id(): string {
        return 'gemini';
    }

    public function get_label(): string {
        return 'Google Gemini';
    }

    public function get_models(): array {
        return [ 'gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-2.0-flash' ];
    }

    public function supports_images(): bool {
        return true;
    }

    public function chat( array $messages, array $options = [] ): array {
        list( $system, $turns ) = self::split_system( $messages, (string) ( $options['system'] ?? '' ) );

        $contents = [];
        foreach ( $turns as $turn ) {
            $contents[] = [
                'role'  => 'assistant' === $turn['role'] ? 'model' : 'user',
                'parts' => [ [ 'text' => $turn['content'] ] ],
            ];
        }

        $body = [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => $this->temperature( $options ),
                'maxOutputTokens' => $this->max_tokens( $options ),
            ],
        ];
        if ( '' !== $system ) {
            $body['systemInstruction'] = [ 'parts' => [ [ 'text' => $system ] ] ];
        }
        if ( ! empty( $options['tools'] ) ) {
            $body['tools'] = [ [ 'functionDeclarations' => $this->format_tools( $options['tools'] ) ] ];
            if ( 'required' === ( $options['tool_choice'] ?? '' ) ) {
                $body['toolConfig'] = [ 'functionCallingConfig' => [ 'mode' => 'ANY' ] ];
            }
        }

        $url    = self::BASE . rawurlencode( $this->resolve_model( $options ) ) . ':generateContent?key=' . rawurlencode( sanitize_text_field( (string) $this->config['api_key'] ) );
        $result = $this->post_json( $url, [], $body );
        if ( ! $result['ok'] ) {
            return [ 'text' => '', 'tool_calls' => [], 'usage' => [], 'error' => $result['error'], 'raw' => $result['data'] ];
        }

        return $this->normalize_chat( $result['data'] );
    }

    /**
     * @param array $tools
     * @return array
     */
    protected function format_tools( array $tools ): array {
        $out = [];
        foreach ( $tools as $tool ) {
            $out[] = [
                'name'        => $tool['name'],
                'description' => $tool['description'] ?? '',
                'parameters'  => self::params_to_json_schema( $tool['params'] ?? [] ),
            ];
        }
        return $out;
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected function normalize_chat( $data ): array {
        $text       = '';
        $tool_calls = [];

        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        foreach ( (array) $parts as $part ) {
            if ( isset( $part['text'] ) ) {
                $text .= (string) $part['text'];
            }
            if ( isset( $part['functionCall']['name'] ) ) {
                $tool_calls[] = [
                    'name'      => (string) $part['functionCall']['name'],
                    'arguments' => self::decode_arguments( $part['functionCall']['args'] ?? [] ),
                ];
            }
        }

        return [
            'text'       => $text,
            'tool_calls' => $tool_calls,
            'usage'      => $data['usageMetadata'] ?? [],
            'error'      => null,
            'raw'        => $data,
        ];
    }

    public function generate_image( string $prompt, array $options = [] ): array {
        $model = ! empty( $options['model'] ) ? (string) $options['model']
            : ( ! empty( $this->config['image_model'] ) ? (string) $this->config['image_model'] : 'imagen-3.0-generate-002' );

        $url  = self::BASE . rawurlencode( $model ) . ':predict?key=' . rawurlencode( sanitize_text_field( (string) $this->config['api_key'] ) );
        $body = [
            'instances'  => [ [ 'prompt' => $prompt ] ],
            'parameters' => [ 'sampleCount' => 1 ],
        ];

        $result = $this->post_json( $url, [], $body, 120 );
        if ( ! $result['ok'] ) {
            return [ 'error' => $result['error'] ];
        }

        $b64 = $result['data']['predictions'][0]['bytesBase64Encoded'] ?? '';
        if ( '' !== $b64 ) {
            return [ 'b64' => (string) $b64, 'mime' => 'image/png', 'error' => null ];
        }
        return [ 'error' => __( 'Image provider returned no image.', 'better-payment' ) ];
    }
}
