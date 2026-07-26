<?php

namespace Better_Payment\Lite\AI\Providers;

use Better_Payment\Lite\AI\Contracts\AIProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shared plumbing for HTTP-based AI providers.
 *
 * Follows the plugin's existing outbound-request idiom (see
 * Blocks/BlockActions.php): wp_remote_post + is_wp_error + wp_remote_retrieve_body
 * + json_decode. Concrete providers implement only the request/response mapping
 * for their API.
 *
 * @see AIProviderInterface
 */
abstract class AbstractProvider implements AIProviderInterface {

    /**
     * Provider configuration.
     *
     * @var array {
     *     @type string $api_key
     *     @type string $model
     *     @type string $image_model
     *     @type float  $temperature
     *     @type int    $max_tokens
     * }
     */
    protected array $config;

    /**
     * @param array $config
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, [
            'api_key'     => '',
            'model'       => '',
            'image_model' => '',
            'temperature' => 0.7,
            'max_tokens'  => 4096,
        ] );
    }

    public function is_configured(): bool {
        return '' !== trim( (string) $this->config['api_key'] );
    }

    public function supports_images(): bool {
        return false;
    }

    public function generate_image( string $prompt, array $options = [] ): array {
        return [ 'error' => __( 'This provider does not support image generation.', 'better-payment' ) ];
    }

    /**
     * Resolve the model id for a chat request (option override → config → first known).
     */
    protected function resolve_model( array $options ): string {
        if ( ! empty( $options['model'] ) ) {
            return (string) $options['model'];
        }
        if ( ! empty( $this->config['model'] ) ) {
            return (string) $this->config['model'];
        }
        $models = $this->get_models();
        return $models[0] ?? '';
    }

    protected function temperature( array $options ): float {
        if ( isset( $options['temperature'] ) && is_numeric( $options['temperature'] ) ) {
            return (float) $options['temperature'];
        }
        return (float) $this->config['temperature'];
    }

    protected function max_tokens( array $options ): int {
        if ( isset( $options['max_tokens'] ) && is_numeric( $options['max_tokens'] ) ) {
            return (int) $options['max_tokens'];
        }
        return (int) $this->config['max_tokens'];
    }

    /**
     * Perform a JSON POST and return a normalised result.
     *
     * @param string $url
     * @param array  $headers
     * @param array  $body     Encoded with wp_json_encode.
     * @param int    $timeout
     * @return array {
     *     @type bool        $ok
     *     @type int         $status
     *     @type array|mixed $data   Decoded JSON body (assoc array) on success.
     *     @type string|null $error
     * }
     */
    protected function post_json( string $url, array $headers, array $body, int $timeout = 60 ): array {
        $headers = wp_parse_args( $headers, [ 'Content-Type' => 'application/json' ] );

        $response = wp_remote_post( $url, [
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
            'timeout' => $timeout,
        ] );

        if ( is_wp_error( $response ) ) {
            return [ 'ok' => false, 'status' => 0, 'data' => null, 'error' => $response->get_error_message() ];
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        $raw    = wp_remote_retrieve_body( $response );
        $data   = json_decode( $raw, true );

        if ( $status < 200 || $status >= 300 ) {
            return [
                'ok'     => false,
                'status' => $status,
                'data'   => $data,
                'error'  => self::extract_error_message( $data, $status ),
            ];
        }

        return [ 'ok' => true, 'status' => $status, 'data' => $data, 'error' => null ];
    }

    /**
     * Best-effort extraction of an API error message from a decoded body.
     *
     * @param mixed $data
     */
    protected static function extract_error_message( $data, int $status ): string {
        if ( is_array( $data ) ) {
            if ( isset( $data['error']['message'] ) ) {
                return (string) $data['error']['message'];
            }
            if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
                return $data['error'];
            }
            if ( isset( $data['message'] ) && is_string( $data['message'] ) ) {
                return $data['message'];
            }
        }
        /* translators: %d: HTTP status code. */
        return sprintf( __( 'AI provider request failed (HTTP %d).', 'better-payment' ), $status );
    }

    /**
     * Convert a provider-agnostic tool param map (OperationRegistry shape) into a
     * JSON-Schema object, shared by all function/tool-calling providers.
     *
     * @param array $params [ key => [ 'type' => ..., 'required' => bool, 'summary' => ... ] ]
     * @return array{type: string, properties: array, required: array}
     */
    protected static function params_to_json_schema( array $params ): array {
        $properties = [];
        $required   = [];

        foreach ( $params as $key => $spec ) {
            $type = $spec['type'] ?? 'string';
            $prop = [ 'description' => $spec['summary'] ?? '' ];

            switch ( $type ) {
                case 'array':
                    $prop['type']  = 'array';
                    $prop['items'] = [ 'type' => 'object' ];
                    break;
                case 'object':
                    $prop['type'] = 'object';
                    break;
                case 'integer':
                    $prop['type'] = 'integer';
                    break;
                case 'number':
                    $prop['type'] = 'number';
                    break;
                case 'mixed':
                    // Leave type open — accept any JSON value.
                    break;
                default:
                    $prop['type'] = 'string';
            }

            $properties[ $key ] = $prop;
            if ( ! empty( $spec['required'] ) ) {
                $required[] = $key;
            }
        }

        return [
            'type'       => 'object',
            'properties' => empty( $properties ) ? (object) [] : $properties,
            'required'   => $required,
        ];
    }

    /**
     * Decode a tool-call arguments payload that may arrive as a JSON string or array.
     *
     * @param mixed $arguments
     * @return array
     */
    protected static function decode_arguments( $arguments ): array {
        if ( is_array( $arguments ) ) {
            return $arguments;
        }
        if ( is_string( $arguments ) && '' !== $arguments ) {
            $decoded = json_decode( $arguments, true );
            return is_array( $decoded ) ? $decoded : [];
        }
        return [];
    }

    /**
     * Split normalised messages into a system prompt + non-system turns.
     * Providers that carry the system prompt separately use this.
     *
     * @param array  $messages
     * @param string $system_option Explicit system option (takes precedence).
     * @return array{0: string, 1: array} [ system, turns ]
     */
    protected static function split_system( array $messages, string $system_option = '' ): array {
        $system = $system_option;
        $turns  = [];
        foreach ( $messages as $message ) {
            if ( ! is_array( $message ) ) {
                continue;
            }
            $role = $message['role'] ?? 'user';
            if ( 'system' === $role ) {
                $system = trim( $system . "\n" . (string) ( $message['content'] ?? '' ) );
                continue;
            }
            $turns[] = [
                'role'    => 'assistant' === $role ? 'assistant' : 'user',
                'content' => (string) ( $message['content'] ?? '' ),
            ];
        }
        return [ trim( $system ), $turns ];
    }
}
