<?php

namespace Better_Payment\Lite\AI\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Contract every AI provider must implement.
 *
 * The rest of the AI layer talks only to this interface — never to a concrete
 * provider — so switching providers (or adding a new one) requires no change to
 * the builder, services, or REST layer. Providers are provider-agnostic in and
 * out: they receive normalised messages + tool descriptors and return a
 * normalised result.
 *
 * @see \Better_Payment\Lite\AI\Providers\AbstractProvider Shared HTTP plumbing.
 * @see \Better_Payment\Lite\AI\ProviderRegistry           Discovery / instantiation.
 */
interface AIProviderInterface {

    /**
     * Send a chat/completion request.
     *
     * @param array $messages Normalised turns: [ [ 'role' => 'user'|'assistant'|'system', 'content' => string ], ... ].
     * @param array $options  {
     *     @type string $system        System prompt.
     *     @type string $model         Model id override.
     *     @type float  $temperature   Sampling temperature.
     *     @type int    $max_tokens    Output token cap.
     *     @type array  $tools         Provider-agnostic tool descriptors (see OperationRegistry::as_tools()).
     * }
     * @return array {
     *     @type string      $text       Assistant natural-language message.
     *     @type array       $tool_calls [ [ 'name' => string, 'arguments' => array ], ... ].
     *     @type array       $usage      Token usage, when reported.
     *     @type string|null $error      Human-readable error, or null on success.
     *     @type mixed       $raw        Raw decoded provider response (for debugging).
     * }
     */
    public function chat( array $messages, array $options = [] ): array;

    /**
     * Generate an image from a prompt.
     *
     * @param string $prompt
     * @param array  $options { @type string $model; @type string $size; }
     * @return array {
     *     @type string      $url    Remote image URL, when the provider returns one.
     *     @type string      $b64    Base64 image data, when the provider returns bytes.
     *     @type string      $mime   Image mime type.
     *     @type string|null $error  Human-readable error, or null on success.
     * }
     */
    public function generate_image( string $prompt, array $options = [] ): array;

    /** Stable provider id (e.g. "openai"). */
    public function get_id(): string;

    /** Human label (e.g. "OpenAI"). */
    public function get_label(): string;

    /**
     * Available model ids for this provider.
     *
     * @return array<int, string>
     */
    public function get_models(): array;

    /** Whether this provider can generate images. */
    public function supports_images(): bool;

    /** Whether the provider has the configuration (API key) it needs to run. */
    public function is_configured(): bool;
}
