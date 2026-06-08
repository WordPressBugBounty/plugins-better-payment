<?php

namespace Better_Payment\Lite\API;

use Better_Payment\Lite\Campaign\CampaignStats;
use Better_Payment\Lite\Campaign\MetaBox;
use Better_Payment\Lite\Campaign\Templates\TemplateManager;
use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Campaign\Services\RendererService;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API routes for bp_campaign posts and campaign stats.
 * Namespace: better-payment/v1
 */
class CampaignAPI extends WP_REST_Controller {

    protected $namespace = 'better-payment/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        // List campaigns
        register_rest_route( $this->namespace, '/campaigns', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_campaigns' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_campaign' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
            ],
        ] );

        // Restore campaign from trash
        register_rest_route( $this->namespace, '/campaigns/(?P<id>[\d]+)/restore', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'restore_campaign' ],
            'permission_callback' => [ $this, 'check_admin_permissions' ],
            'args'                => [ 'id' => [ 'type' => 'integer' ] ],
        ] );

        // Single campaign
        register_rest_route( $this->namespace, '/campaigns/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_campaign' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_campaign' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_campaign' ],
                'permission_callback' => [ $this, 'check_admin_permissions' ],
                'args'                => [ 'id' => [ 'type' => 'integer' ] ],
            ],
        ] );

        // Campaign stats (public — used for frontend display)
        register_rest_route( $this->namespace, '/campaigns/(?P<id>[\d]+)/stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_campaign_stats' ],
            'permission_callback' => '__return_true',
            'args'                => [ 'id' => [ 'type' => 'integer' ] ],
        ] );

        // Available templates — public (builder needs these before user is authenticated)
        register_rest_route( $this->namespace, '/campaigns/templates', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_templates' ],
            'permission_callback' => '__return_true',
        ] );

        // Builder live-preview — renders layout+meta as a full HTML document (no saved post required)
        register_rest_route( $this->namespace, '/campaigns/preview', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'preview_campaign_builder' ],
            'permission_callback' => [ $this, 'check_admin_permissions' ],
        ] );

        // Preview — renders layout JSON as HTML without saving
        register_rest_route( $this->namespace, '/campaigns/(?P<id>[\d]+)/preview', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'preview_campaign' ],
            'permission_callback' => [ $this, 'check_admin_permissions' ],
            'args'                => [ 'id' => [ 'type' => 'integer' ] ],
        ] );

        // Template preview — renders a template definition as HTML for the picker iframe
        register_rest_route( $this->namespace, '/templates/(?P<key>[a-z0-9\-]+)/preview', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'preview_template' ],
            'permission_callback' => [ $this, 'check_admin_permissions' ],
        ] );
    }

    // ------------------------------------------------------------------ handlers

    public function get_campaigns( WP_REST_Request $request ): WP_REST_Response {
        $status   = sanitize_text_field( $request->get_param( 'status' ) ?: 'all' );
        $search   = sanitize_text_field( $request->get_param( 's' ) ?: '' );
        $orderby  = in_array( $request->get_param( 'orderby' ), [ 'title', 'date' ], true ) ? $request->get_param( 'orderby' ) : 'date';
        $order    = strtoupper( $request->get_param( 'order' ) ?: 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        $per_page = max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) );
        $page     = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );

        if ( $status === 'trash' ) {
            $post_statuses = [ 'trash' ];
        } elseif ( in_array( $status, [ 'publish', 'draft' ], true ) ) {
            $post_statuses = [ $status ];
        } else {
            $post_statuses = [ 'publish', 'draft' ];
        }

        $args = [
            'post_type'      => 'bp_campaign',
            'post_status'    => $post_statuses,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => $orderby,
            'order'          => $order,
            's'              => $search,
        ];

        $query = new \WP_Query( $args );
        $data  = array_map( [ $this, 'prepare_campaign_response' ], $query->posts );

        $post_counts = wp_count_posts( 'bp_campaign' );
        $counts      = [
            'all'     => (int) ( $post_counts->publish ?? 0 ) + (int) ( $post_counts->draft ?? 0 ),
            'publish' => (int) ( $post_counts->publish ?? 0 ),
            'draft'   => (int) ( $post_counts->draft ?? 0 ),
            'trash'   => (int) ( $post_counts->trash ?? 0 ),
        ];

        return new WP_REST_Response( [
            'campaigns' => $data,
            'total'     => (int) $query->found_posts,
            'pages'     => (int) $query->max_num_pages,
            'page'      => $page,
            'counts'    => $counts,
        ], 200 );
    }

    public function create_campaign( WP_REST_Request $request ): WP_REST_Response {
        if ( ! $this->valid_nonce( $request ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid nonce' ], 403 );
        }

        $title       = sanitize_text_field( $request->get_param( 'title' ) ?: __( 'New Campaign', 'better-payment' ) );
        $post_status = sanitize_text_field( $request->get_param( 'post_status' ) ?: 'draft' );

        $post_id = wp_insert_post( [
            'post_type'   => 'bp_campaign',
            'post_title'  => $title,
            'post_status' => $post_status,
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new WP_REST_Response( [ 'message' => $post_id->get_error_message() ], 500 );
        }

        MetaBox::save_from_array( $post_id, $request->get_params() );

        return new WP_REST_Response( $this->prepare_campaign_response( get_post( $post_id ) ), 201 );
    }

    public function get_campaign( WP_REST_Request $request ): WP_REST_Response {
        $post = $this->get_validated_post( $request['id'] );
        if ( is_wp_error( $post ) ) {
            return new WP_REST_Response( [ 'message' => $post->get_error_message() ], 404 );
        }

        return new WP_REST_Response( $this->prepare_campaign_response( $post ), 200 );
    }

    public function update_campaign( WP_REST_Request $request ): WP_REST_Response {
        if ( ! $this->valid_nonce( $request ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid nonce' ], 403 );
        }

        $post = $this->get_validated_post( $request['id'] );
        if ( is_wp_error( $post ) ) {
            return new WP_REST_Response( [ 'message' => $post->get_error_message() ], 404 );
        }

        $post_id = $post->ID;

        // Update title / status if provided
        $update = [ 'ID' => $post_id ];

        if ( $request->get_param( 'title' ) ) {
            $update['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
        }

        if ( $request->get_param( 'post_status' ) ) {
            $update['post_status'] = sanitize_text_field( $request->get_param( 'post_status' ) );
        }

        if ( count( $update ) > 1 ) {
            wp_update_post( $update );
        }

        MetaBox::save_from_array( $post_id, $request->get_params() );

        // Bust stats cache in case goal changed.
        CampaignStats::bust_cache( $post_id );

        return new WP_REST_Response( $this->prepare_campaign_response( get_post( $post_id ) ), 200 );
    }

    public function delete_campaign( WP_REST_Request $request ): WP_REST_Response {
        if ( ! $this->valid_nonce( $request ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid nonce' ], 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_REST_Response( [ 'message' => 'Forbidden' ], 403 );
        }

        $post = $this->get_validated_post( $request['id'] );
        if ( is_wp_error( $post ) ) {
            return new WP_REST_Response( [ 'message' => $post->get_error_message() ], 404 );
        }

        $force = filter_var( $request->get_param( 'force' ), FILTER_VALIDATE_BOOLEAN );

        if ( $force ) {
            wp_delete_post( $post->ID, true );
        } else {
            wp_trash_post( $post->ID );
        }

        return new WP_REST_Response( [ 'deleted' => true ], 200 );
    }

    public function restore_campaign( WP_REST_Request $request ): WP_REST_Response {
        if ( ! $this->valid_nonce( $request ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid nonce' ], 403 );
        }

        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'bp_campaign' ) {
            return new WP_REST_Response( [ 'message' => 'Campaign not found' ], 404 );
        }

        wp_untrash_post( $post->ID );

        return new WP_REST_Response( [ 'restored' => true ], 200 );
    }

    public function get_campaign_stats( WP_REST_Request $request ): WP_REST_Response {
        $not_found = new WP_REST_Response( [ 'message' => 'Not found' ], 404 );

        $post = $this->get_validated_post( $request['id'] );
        if ( is_wp_error( $post ) ) {
            return $not_found;
        }

        if ( $post->post_status !== 'publish' && ! current_user_can( 'manage_options' ) ) {
            return $not_found;
        }

        $stats = CampaignStats::get_stats( $post->ID );

        return new WP_REST_Response( $stats, 200 );
    }

    public function get_templates(): WP_REST_Response {
        return new WP_REST_Response( array_values( TemplateManager::get_all() ), 200 );
    }

    public function preview_campaign( WP_REST_Request $request ): WP_REST_Response {
        $post = $this->get_validated_post( $request['id'] );
        if ( is_wp_error( $post ) ) {
            return new WP_REST_Response( [ 'message' => $post->get_error_message() ], 404 );
        }

        $layout = $request->get_param( 'layout' );
        if ( ! is_array( $layout ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid layout' ], 400 );
        }

        $html = RendererService::render_campaign( $post->ID, true, $layout );

        return new WP_REST_Response( [ 'html' => $html ], 200 );
    }

    public function preview_campaign_builder( WP_REST_Request $request ): WP_REST_Response {
        $layout      = $request->get_param( 'layout' );
        $meta        = $request->get_param( 'meta' ) ?: [];
        $campaign_id = (int) ( $request->get_param( 'campaign_id' ) ?: 0 );

        if ( ! is_array( $layout ) ) {
            return new WP_REST_Response( [ 'message' => 'Invalid layout' ], 400 );
        }

        if ( ! is_array( $meta ) ) {
            $meta = [];
        }

        $html = RendererService::build_preview_document( $layout, $meta, $campaign_id );
        return new WP_REST_Response( [ 'html' => $html ], 200 );
    }

    public function preview_template( WP_REST_Request $request ): WP_REST_Response {
        $key  = sanitize_key( $request['key'] );
        $html = RendererService::render_template_preview( $key );
        if ( ! $html ) {
            return new WP_REST_Response( [ 'message' => 'Template not found' ], 404 );
        }
        return new WP_REST_Response( [ 'html' => $html ], 200 );
    }

    // ------------------------------------------------------------------ helpers

    public function check_admin_permissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', 'Unauthorized', [ 'status' => 401 ] );
        }
        return true;
    }

    private function valid_nonce( WP_REST_Request $request ): bool {
        $nonce = $request->get_header( 'x_wp_nonce' );
        return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
    }

    private function get_validated_post( int $id ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'bp_campaign' ) {
            return new WP_Error( 'not_found', __( 'Campaign not found', 'better-payment' ), [ 'status' => 404 ] );
        }
        return $post;
    }

    private function prepare_campaign_response( \WP_Post $post ): array {
        $meta     = MetaBox::get_all( $post->ID );
        $stats    = CampaignStats::get_stats( $post->ID );
        $code     = DB::get_settings( 'better_payment_settings_general_general_currency' );
        $currency = ( is_string( $code ) && $code !== '' ) ? $code : 'USD';

        return [
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'post_status' => $post->post_status,
            'post_date'   => $post->post_date,
            'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '',
            'edit_url'    => admin_url( 'admin.php?page=bp-campaign-builder&campaign_id=' . $post->ID ),
            'view_url'    => $post->post_status === 'publish' ? ( get_permalink( $post->ID ) ?: '' ) : '',
            'currency'    => $currency,
            'meta'        => $meta,
            'stats'       => $stats,
        ];
    }
}
