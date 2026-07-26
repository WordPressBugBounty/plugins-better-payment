<?php

namespace Better_Payment\Lite\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Anthropic Claude provider (Messages API).
 *
 * Uses native tool-use so the model returns Builder Operations as structured
 * `tool_use` blocks rather than free text. Anthropic does not generate images,
 * so image support is off.
 */
class ClaudeProvider extends AbstractProvider {

    const ENDPOINT   = 'https://api.anthropic.com/v1/messages';
    const API_VERSION = '2023-06-01';

    public function get_id(): string {
        return 'claude';
    }

    public function get_label(): string {
        return 'Claude (Anthropic)';
    }

    public function get_models(): array {
        return [ 'claude-sonnet-5', 'claude-opus-4-8', 'claude-haiku-4-5-20251001' ];
    }

    public function chat( array $messages, array $options = [] ): array {
        list( $system, $turns ) = self::split_system( $messages, (string) ( $options['system'] ?? '' ) );

        $payload_messages = [];
        foreach ( $turns as $turn ) {
            $payload_messages[] = [
                'role'    => $turn['role'],
                'content' => $turn['content'],
            ];
        }

        $body = [
            'model'       => $this->resolve_model( $options ),
            'max_tokens'  => $this->max_tokens( $options ),
            'temperature' => $this->temperature( $options ),
            'messages'    => $payload_messages,
        ];
        if ( '' !== $system ) {
            $body['system'] = $system;
        }
        if ( ! empty( $options['tools'] ) ) {
            $body['tools'] = $this->format_tools( $options['tools'] );
            if ( 'required' === ( $options['tool_choice'] ?? '' ) ) {
                $body['tool_choice'] = [ 'type' => 'any' ];
            }
        }

        $headers = [
            'x-api-key'         => sanitize_text_field( (string) $this->config['api_key'] ),
            'anthropic-version' => self::API_VERSION,
        ];

        $result = $this->post_json( self::ENDPOINT, $headers, $body );
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
                'name'         => $tool['name'],
                'description'  => $tool['description'] ?? '',
                'input_schema' => self::params_to_json_schema( $tool['params'] ?? [] ),
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

        foreach ( (array) ( $data['content'] ?? [] ) as $block ) {
            $btype = $block['type'] ?? '';
            if ( 'text' === $btype ) {
                $text .= (string) ( $block['text'] ?? '' );
            } elseif ( 'tool_use' === $btype ) {
                $tool_calls[] = [
                    'name'      => (string) ( $block['name'] ?? '' ),
                    'arguments' => self::decode_arguments( $block['input'] ?? [] ),
                ];
            }
        }

        return [
            'text'       => $text,
            'tool_calls' => $tool_calls,
            'usage'      => $data['usage'] ?? [],
            'error'      => null,
            'raw'        => $data,
        ];
    }
}
