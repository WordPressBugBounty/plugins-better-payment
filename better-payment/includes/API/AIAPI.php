<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\AI\AIManager;
use Better_Payment\Lite\AI\ProviderRegistry;
use Better_Payment\Lite\AI\Operations\OperationRegistry;
use Better_Payment\Lite\AI\Services\BriefWriter;
use Better_Payment\Lite\AI\Services\CampaignAnalyzer;
use Better_Payment\Lite\AI\Services\CampaignEditor;
use Better_Payment\Lite\AI\Services\CampaignGenerator;
use Better_Payment\Lite\AI\Services\ConversationManager;
use Better_Payment\Lite\AI\Services\ImageGenerator;
use Better_Payment\Lite\AI\Services\UserFieldGuard;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API routes for the AI Campaign Assistant.
 *
 * Namespace: better-payment/v1. Mirrors CampaignAPI: admin-only
 * (`manage_options`) with X-WP-Nonce verification on writes. The builder already
 * configures apiFetch with the wp_rest nonce, so no new client plumbing.
 *
 * @see \Better_Payment\Lite\AI\Services\AIService  Turn orchestration.
 */
class AIAPI extends WP_REST_Controller {

    protected $namespace = 'better-payment/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, '/ai/chat', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'chat' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/generate', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generate' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/analyze', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'analyze' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/brief', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'refine_brief' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/image', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'image' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/conversations/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_conversation' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_conversation' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'clear_conversation' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
        ] );

        register_rest_route( $this->namespace, '/ai/config', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_config' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );
    }

    // ------------------------------------------------------------------ handlers

    public function chat( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $disabled = $this->enabled_guard();
        if ( null !== $disabled ) {
            return $disabled;
        }

        $campaign_id = (int) $request->get_param( 'campaign_id' );
        $message     = trim( (string) $request->get_param( 'message' ) );
        if ( '' === $message ) {
            return new WP_REST_Response( [ 'message' => __( 'Message is required.', 'better-payment' ) ], 400 );
        }

        $context = $this->read_context( $request );
        $history = ConversationManager::sanitize( (array) $request->get_param( 'history' ) );

        // Sent by the builder's per-element "AI quick edit" buttons. Only the id
        // travels — CampaignEditor reads the type and current settings back out of
        // the trusted context layout.
        $target_element_id = sanitize_text_field( (string) $request->get_param( 'target_element_id' ) );

        $result = CampaignEditor::edit( $message, $context, $history, $target_element_id );
        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        // Persist the turn when we have a saved campaign to attach it to.
        if ( $campaign_id > 0 ) {
            ConversationManager::append( $campaign_id, $message, $result['assistant_message'] );
        }

        return new WP_REST_Response( $result, 200 );
    }

    public function generate( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $disabled = $this->enabled_guard();
        if ( null !== $disabled ) {
            return $disabled;
        }

        $brief = trim( (string) $request->get_param( 'message' ) );
        if ( '' === $brief ) {
            return new WP_REST_Response( [ 'message' => __( 'A campaign brief is required.', 'better-payment' ) ], 400 );
        }

        // The category the user picked in the Smart Prompt Wizard, if any. Not
        // validated against the registry here — CampaignGenerator falls back to
        // inferring from the brief for anything it doesn't recognise, so an
        // unknown slug degrades to the same path as no slug at all.
        $category = sanitize_key( (string) $request->get_param( 'category' ) );

        // The campaign fields the user filled in themselves. A key present but
        // empty means they were asked and left it blank — the model may not fill
        // that gap in for them. Absent entirely (free-form prompt) enforces
        // nothing. See UserFieldGuard.
        $fields = UserFieldGuard::sanitize( $request->get_param( 'fields' ) );

        $result = CampaignGenerator::generate( $brief, $this->read_context( $request ), $category, $fields );
        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }
        return new WP_REST_Response( $result, 200 );
    }

    public function analyze( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $disabled = $this->enabled_guard();
        if ( null !== $disabled ) {
            return $disabled;
        }

        $result = CampaignAnalyzer::analyze( $this->read_context( $request ) );
        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Refine the Smart Prompt Wizard's brief before generation (text in, text
     * out). Applies nothing — the improved brief is returned for the user to
     * review and edit, then hand to /ai/generate. Same guards as the other AI
     * routes: valid nonce, feature enabled, manage_options.
     */
    public function refine_brief( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $disabled = $this->enabled_guard();
        if ( null !== $disabled ) {
            return $disabled;
        }

        $brief = trim( (string) $request->get_param( 'brief' ) );
        if ( '' === $brief ) {
            return new WP_REST_Response( [ 'message' => __( 'A brief is required.', 'better-payment' ) ], 400 );
        }

        $instruction = trim( (string) $request->get_param( 'instruction' ) );

        $result = BriefWriter::write( $brief, $instruction );
        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }
        return new WP_REST_Response( $result, 200 );
    }

    public function image( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $disabled = $this->enabled_guard();
        if ( null !== $disabled ) {
            return $disabled;
        }

        $prompt = trim( (string) $request->get_param( 'prompt' ) );
        if ( '' === $prompt ) {
            return new WP_REST_Response( [ 'message' => __( 'An image prompt is required.', 'better-payment' ) ], 400 );
        }

        $result = ImageGenerator::generate( $prompt, (int) $request->get_param( 'campaign_id' ) );
        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }
        return new WP_REST_Response( $result, 200 );
    }

    public function get_conversation( WP_REST_Request $request ): WP_REST_Response {
        $id = (int) $request->get_param( 'id' );
        return new WP_REST_Response( [ 'messages' => ConversationManager::get( $id ) ], 200 );
    }

    /**
     * Persist the full conversation for a campaign (the client is the source of
     * truth for the message list). This is how the very first "create campaign"
     * turn — which happens while the campaign is still unsaved (id 0), so it
     * cannot be appended server-side during /ai/generate — gets written once the
     * campaign has been saved and has an id. Replaces rather than appends, so a
     * re-sync is idempotent. Nonce-guarded (write route) but NOT enabled-guarded:
     * saving history the user already produced must keep working even if an admin
     * later turns the AI Assistant off.
     */
    public function save_conversation( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        $id       = (int) $request->get_param( 'id' );
        $messages = ConversationManager::replace( $id, (array) $request->get_param( 'messages' ) );
        return new WP_REST_Response( [ 'messages' => $messages ], 200 );
    }

    public function clear_conversation( WP_REST_Request $request ): WP_REST_Response {
        $nonce = $this->nonce_guard( $request );
        if ( null !== $nonce ) {
            return $nonce;
        }
        ConversationManager::clear( (int) $request->get_param( 'id' ) );
        return new WP_REST_Response( [ 'ok' => true ], 200 );
    }

    public function get_config( WP_REST_Request $request ): WP_REST_Response {
        $provider = AIManager::active_provider();
        return new WP_REST_Response( [
            'enabled'    => AIManager::is_enabled(),
            'active'     => AIManager::active_provider_id(),
            'configured' => null !== $provider && $provider->is_configured(),
            'providers'  => ProviderRegistry::descriptors(),
            'operations' => OperationRegistry::names(),
        ], 200 );
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Read the trusted { layout, meta } context from the request body.
     *
     * @return array{ layout: array, meta: array }
     */
    private function read_context( WP_REST_Request $request ): array {
        $context = $request->get_param( 'context' );
        $context = is_array( $context ) ? $context : [];
        return [
            'layout' => is_array( $context['layout'] ?? null ) ? $context['layout'] : [],
            'meta'   => is_array( $context['meta'] ?? null ) ? $context['meta'] : [],
        ];
    }

    /**
     * Nonce guard for write routes. Returns a 403 response when invalid, else null.
     *
     * @return WP_REST_Response|null
     */
    private function nonce_guard( WP_REST_Request $request ) {
        if ( ! $this->valid_nonce( $request ) ) {
            return new WP_REST_Response( [ 'message' => __( 'Invalid nonce', 'better-payment' ) ], 403 );
        }
        return null;
    }

    private function valid_nonce( WP_REST_Request $request ): bool {
        $nonce = $request->get_header( 'x_wp_nonce' );
        return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
    }

    /**
     * Guard for routes that call out to an AI provider. Returns a 403 response
     * when the AI Assistant is disabled in settings, else null.
     *
     * @return WP_REST_Response|null
     */
    private function enabled_guard() {
        if ( ! AIManager::is_enabled() ) {
            return new WP_REST_Response( [
                'code'    => 'ai_disabled',
                'message' => __( 'The AI Assistant is disabled. Enable it under Settings → AI Assistant.', 'better-payment' ),
            ], 403 );
        }
        return null;
    }

    /**
     * Convert a WP_Error to a REST response using its status.
     */
    private function error_response( $error ): WP_REST_Response {
        $status = 500;
        $data   = $error->get_error_data();
        if ( is_array( $data ) && isset( $data['status'] ) ) {
            $status = (int) $data['status'];
        }
        return new WP_REST_Response( [
            'code'    => $error->get_error_code(),
            'message' => $error->get_error_message(),
        ], $status );
    }

    public function check_admin_permissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
        }
        return true;
    }
}
