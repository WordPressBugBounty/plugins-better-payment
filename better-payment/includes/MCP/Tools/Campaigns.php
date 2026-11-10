<?php
/**
 * Campaign tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Tools;

use Better_Payment\Lite\AI\Operations\OperationValidator;
use Better_Payment\Lite\AI\Operations\StateDeriver;
use Better_Payment\Lite\AI\Schema\CampaignSchema;
use Better_Payment\Lite\Campaign\CampaignCurrency;
use Better_Payment\Lite\Campaign\CampaignLifecycle;
use Better_Payment\Lite\Campaign\CampaignStats;
use Better_Payment\Lite\Campaign\Elements\ElementRegistry;
use Better_Payment\Lite\Campaign\MetaBox;
use Better_Payment\Lite\Campaign\Services\RendererService;
use Better_Payment\Lite\Campaign\Templates\CategoryRegistry;
use Better_Payment\Lite\Campaign\Templates\TemplateManager;
use Better_Payment\Lite\MCP\Contracts\ToolProvider;
use Better_Payment\Lite\MCP\Support\DryRun;
use Better_Payment\Lite\MCP\Support\Result;
use Better_Payment\Lite\MCP\Support\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and edit `bp_campaign` fundraising campaigns.
 *
 * The write path is deliberately the SAME path the AI assistant uses:
 *
 *     operations → OperationValidator::validate_batch()
 *                → StateDeriver::derive()
 *                → MetaBox::save_from_array()
 *                    → MetaBox::enforce_pro_entitlement()  (for _bpc_fields_layout)
 *
 * No second sanitiser exists for MCP. A separate one would drift from the AI path,
 * and the drift would only be visible once a campaign saved through one route came
 * out different from the same batch saved through the other.
 *
 * @since 2.5.0
 */
class Campaigns implements ToolProvider {

	/**
	 * Campaign post type.
	 */
	const POST_TYPE = 'bp_campaign';

	/**
	 * The operations campaign_edit applies.
	 *
	 * @var string[]
	 */
	const EDIT_OPERATIONS = array( 'set_layout', 'update_meta', 'set_colors', 'set_donation_amounts' );

	/**
	 * The update_meta keys campaign_edit reads as "change this campaign's currency".
	 *
	 * The currency is not an AI-writable key (the assistant must never re-label a
	 * campaign's money), so these never reach OperationValidator. campaign_edit takes
	 * them itself, the owner's explicit request, under the same lock the builder has
	 * (CampaignCurrency::enforce_lock()).
	 *
	 * @var string[]
	 */
	const CURRENCY_KEYS = array( 'bpc_currency', 'currency' );

	/**
	 * At most this many campaigns are listed per group in a site-currency impact report.
	 */
	const IMPACT_LIST_LIMIT = 50;

	/**
	 * Tool definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		$id = array(
			'type'        => 'integer',
			'description' => 'Campaign post id.',
			'minimum'     => 1,
		);

		return array(
			'campaigns_list'          => array(
				'title'       => __( 'List campaigns', 'better-payment' ),
				'description' => __( 'List Better Payment fundraising campaigns with their status, permalink and template.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array_merge(
						Schema::pagination(),
						array(
							'status' => array(
								'type'        => 'string',
								'description' => 'Post status to filter by.',
								'enum'        => array( 'any', 'publish', 'draft', 'pending', 'private' ),
								'default'     => 'any',
							),
							'search' => array(
								'type'        => 'string',
								'description' => 'Free-text search across campaign titles and content.',
							),
						)
					)
				),
				'callback'    => array( self::class, 'list_campaigns' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_get'            => array(
				'title'       => __( 'Get a campaign', 'better-payment' ),
				'description' => __( 'Fetch one fundraising campaign: its title, status, currency, settings and full element layout. currency.code is what the campaign\'s goal, totals and donations are in; currency.source is "campaign" when it has its own currency (a later change to the site currency does not touch it) or "site" when it follows the site currency; currency.locked is true once it has an approved donation, after which its currency can never change. Element ids in the returned layout must be echoed back unchanged when editing, otherwise each element is treated as newly added.', 'better-payment' ),
				'inputSchema' => Schema::object( array( 'id' => $id ), array( 'id' ) ),
				'callback'    => array( self::class, 'get_campaign' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_stats'          => array(
				'title'       => __( 'Campaign fundraising stats', 'better-payment' ),
				'description' => __( 'Amount raised, donor count, goal progress and days remaining for one fundraising campaign.', 'better-payment' ),
				'inputSchema' => Schema::object( array( 'id' => $id ), array( 'id' ) ),
				'callback'    => array( self::class, 'stats' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_templates'      => array(
				'title'       => __( 'List campaign templates', 'better-payment' ),
				'description' => __( 'List the prebuilt campaign designs offered in the builder, including which require Better Payment Pro.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'templates' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_categories'     => array(
				'title'       => __( 'List campaign categories', 'better-payment' ),
				'description' => __( 'List the fundraising categories a campaign can belong to.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'categories' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_element_schema' => array(
				'title'       => __( 'Describe campaign elements', 'better-payment' ),
				'description' => __( 'Describe every campaign element this site can use: its type key, what it is for, and every setting it accepts with that setting\'s control type. Call this before campaign_edit so element settings use real keys.', 'better-payment' ),
				'inputSchema' => Schema::object( array() ),
				'callback'    => array( self::class, 'element_schema' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_preview'        => array(
				'title'       => __( 'Render a campaign preview', 'better-payment' ),
				'description' => __( 'Render one fundraising campaign to a standalone HTML document, the same preview the campaign builder shows. Useful for checking a layout after editing it. It is not the public page: drafts and password-protected campaigns render too, links and buttons are inert, the popup payment form is not included, an empty element shows as a placeholder, and on a free install a Pro element shows a sample-data mock.', 'better-payment' ),
				'inputSchema' => Schema::object( array( 'id' => $id ), array( 'id' ) ),
				'callback'    => array( self::class, 'preview' ),
				'annotations' => array(
					'readOnlyHint'   => true,
					'idempotentHint' => true,
				),
			),

			'campaign_create'         => array(
				'title'       => __( 'Create a campaign', 'better-payment' ),
				'description' => __( 'Create a new fundraising campaign as a draft. Pass template_key (from campaign_templates) to start from that template\'s layout and design, exactly as picking it in the builder does; without one the campaign has no layout until campaign_edit sets one. It is not visible to visitors until campaign_publish runs.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'title'        => array(
							'type'        => 'string',
							'description' => 'Campaign title.',
							'minLength'   => 1,
						),
						'description'  => array(
							'type'        => 'string',
							'description' => 'Campaign description, stored as the post content.',
						),
						'goal_amount'  => array(
							'type'        => 'number',
							'description' => 'Fundraising goal. Omit to create a campaign with no goal.',
							'minimum'     => 0,
						),
						'end_date'     => Schema::date( 'Campaign end date (YYYY-MM-DD). Must be in the future. Omit for no deadline.' ),
						'template_key' => array(
							'type'        => 'string',
							'description' => 'Template to start from: a key from campaign_templates with has_layout true. Omit to create the campaign with no layout.',
						),
						'dry_run'      => DryRun::property(),
					),
					array( 'title' )
				),
				'callback'    => array( self::class, 'create_campaign' ),
				'annotations' => array(),
			),

			'campaign_edit'           => array(
				'title'       => __( 'Edit a campaign', 'better-payment' ),
				'description' => __( 'Apply campaign edit operations. Supported operations are set_layout (replace the whole element layout), update_meta (change one campaign setting), set_colors and set_donation_amounts. To change the campaign\'s currency send update_meta with key bpc_currency and a three-letter code: it is refused once the campaign has an approved donation (currency.locked in campaign_get), because its goal and the money already raised are in that currency; the result reports the outcome under currency. Changing the site currency in settings_update never changes a campaign that has its own currency. any other operation (update_block, insert_block, delete_block, move_block, replace_image, generate_image) is not supported here and is reported back under unsupported, never applied. To change one element, send set_layout with the whole layout. To start from a prebuilt design use campaign_apply_template. Fetch the current layout with campaign_get first and echo each element id back unchanged; a re-minted id is treated as a new element. Invalid operations are dropped, not applied.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'id'         => $id,
						'operations' => array(
							'type'        => 'array',
							'description' => 'Operations to apply, in order.',
							'items'       => array(
								'type'                 => 'object',
								'additionalProperties' => true,
							),
						),
						'dry_run'    => DryRun::property(),
					),
					array( 'id', 'operations' )
				),
				'callback'    => array( self::class, 'apply_operations' ),
				'annotations' => array(
					'destructiveHint' => true,
				),
			),

			'campaign_apply_template' => array(
				'title'       => __( 'Apply a template to a campaign', 'better-payment' ),
				'description' => __( 'Replace a campaign\'s page with a prebuilt template from campaign_templates, exactly as choosing it on the builder\'s Templates tab does: the template\'s layout, design and accent colour replace the current ones; the title, goal, end date, donation amounts and every other setting are kept. Use this, not campaign_edit, when the user names a template for an existing campaign.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'id'           => $id,
						'template_key' => array(
							'type'        => 'string',
							'description' => 'A key from campaign_templates with has_layout true.',
							'minLength'   => 1,
						),
						'dry_run'      => DryRun::property(),
					),
					array( 'id', 'template_key' )
				),
				'callback'    => array( self::class, 'apply_template' ),
				'annotations' => array(
					'destructiveHint' => true,
				),
			),

			'campaign_publish'        => array(
				'title'       => __( 'Publish or unpublish a campaign', 'better-payment' ),
				'description' => __( 'Publish a fundraising campaign so visitors can donate, or return it to draft.', 'better-payment' ),
				'inputSchema' => Schema::object(
					array(
						'id'      => $id,
						'status'  => array(
							'type'        => 'string',
							'description' => 'Target post status.',
							'enum'        => array( 'publish', 'draft' ),
							'default'     => 'publish',
						),
						'dry_run' => DryRun::property(),
					),
					array( 'id' )
				),
				'callback'    => array( self::class, 'publish' ),
				'annotations' => array(
					'idempotentHint' => true,
				),
			),
		);
	}

	/**
	 * Resolve and validate a campaign id.
	 *
	 * @param mixed $input Tool arguments.
	 * @return \WP_Post|\WP_Error
	 */
	private static function resolve( $input ) {
		$input = is_array( $input ) ? $input : array();
		$id    = isset( $input['id'] ) ? (int) $input['id'] : 0;

		if ( $id < 1 ) {
			return Result::invalid( __( 'A campaign id is required.', 'better-payment' ) );
		}

		$post = get_post( $id );

		// The post type check is what stops this family being a generic "read or
		// rewrite any post" tool.
		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return Result::not_found( __( 'Campaign not found.', 'better-payment' ) );
		}

		return $post;
	}

	/**
	 * Compact campaign descriptor.
	 *
	 * @param \WP_Post $post Campaign post.
	 * @return array
	 */
	private static function summary( $post ) {
		return array(
			'id'           => (int) $post->ID,
			'title'        => (string) $post->post_title,
			'status'       => (string) $post->post_status,
			'lifecycle'    => CampaignLifecycle::state( (int) $post->ID ),
			'permalink'    => 'publish' === $post->post_status ? (string) get_permalink( $post ) : '',
			'template_key' => (string) get_post_meta( $post->ID, '_bpc_template_key', true ),
			'currency'     => self::currency_info( (int) $post->ID ),
			'created'      => (string) $post->post_date_gmt,
			'modified'     => (string) $post->post_modified_gmt,
		);
	}

	/**
	 * List campaigns.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function list_campaigns( $input = array() ) {
		$input    = is_array( $input ) ? $input : array();
		$page     = Schema::clamp_page( isset( $input['page'] ) ? $input['page'] : 1 );
		$per_page = Schema::clamp_per_page( isset( $input['per_page'] ) ? $input['per_page'] : null );

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => ! empty( $input['status'] ) ? sanitize_text_field( (string) $input['status'] ) : 'any',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $input['search'] ) ) {
			$args['s'] = sanitize_text_field( (string) $input['search'] );
		}

		$query     = new \WP_Query( $args );
		$campaigns = array();

		foreach ( $query->posts as $post ) {
			$campaigns[] = self::summary( $post );
		}

		return array(
			'campaigns' => $campaigns,
			'total'     => (int) $query->found_posts,
			'page'      => $page,
			'per_page'  => $per_page,
			'pages'     => (int) $query->max_num_pages,
		);
	}

	/**
	 * Fetch one campaign with its layout.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function get_campaign( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$campaign            = self::summary( $post );
		$campaign['content'] = (string) $post->post_content;
		$campaign['meta']    = Result::plain( MetaBox::get_all( (int) $post->ID ) );

		return array( 'campaign' => $campaign );
	}

	/**
	 * Fundraising stats for one campaign.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function stats( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		return array(
			'id'    => (int) $post->ID,
			'stats' => Result::plain( CampaignStats::get_stats( (int) $post->ID ) ),
		);
	}

	/**
	 * Templates offered in the picker.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function templates( $input = array() ) {
		unset( $input );

		// get_for_picker(), never get_all(): get_all() also returns the frozen
		// `hidden` designs that exist only so campaigns already built on them keep
		// rendering. Offering one as a starting point would be wrong.
		$out = array();

		foreach ( TemplateManager::get_for_picker() as $key => $template ) {
			$out[] = array(
				'key'         => (string) $key,
				'label'       => isset( $template['label'] ) ? (string) $template['label'] : '',
				'description' => isset( $template['description'] ) ? (string) $template['description'] : '',
				'tags'        => isset( $template['tags'] ) ? Result::plain( $template['tags'] ) : array(),
				'pro'         => ! empty( $template['pro'] ),
				'has_layout'  => ! empty( $template['columns'] ),
			);
		}

		return array( 'templates' => $out );
	}

	/**
	 * Registered campaign categories.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function categories( $input = array() ) {
		unset( $input );

		return array( 'categories' => Result::plain( CategoryRegistry::get_all() ) );
	}

	/**
	 * Element and meta schema.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array
	 */
	public static function element_schema( $input = array() ) {
		unset( $input );

		return array( 'schema' => Result::plain( CampaignSchema::for_prompt() ) );
	}

	/**
	 * Render a campaign preview document.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function preview( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$meta   = MetaBox::get_all( (int) $post->ID );
		$layout = isset( $meta['bpc_fields_layout'] ) && is_array( $meta['bpc_fields_layout'] )
			? $meta['bpc_fields_layout']
			: array();

		return array(
			'id'   => (int) $post->ID,
			'html' => (string) RendererService::build_preview_document( $layout, $meta, (int) $post->ID ),
		);
	}

	/**
	 * Create a draft campaign.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function create_campaign( $input = array() ) {
		$input = is_array( $input ) ? $input : array();
		$title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';

		if ( '' === $title ) {
			return Result::invalid( __( 'A campaign title is required.', 'better-payment' ) );
		}

		$meta = array();

		if ( isset( $input['goal_amount'] ) && is_numeric( $input['goal_amount'] ) ) {
			$meta['bpc_goal_amount'] = (float) $input['goal_amount'];
		}

		if ( ! empty( $input['end_date'] ) ) {
			$end_date = sanitize_text_field( (string) $input['end_date'] );

			// A campaign that opens already closed shows zero days remaining, no
			// progress bar, and tells every visitor the appeal is over. A model has
			// no clock, so a date it produces is checked rather than trusted.
			//
			// Checked BEFORE the dry-run branch below, not after: a preview that
			// accepted a date the real call refuses would hide this until the write.
			if ( ! self::is_future_date( $end_date ) ) {
				return Result::invalid( __( 'The campaign end date must be in the future.', 'better-payment' ) );
			}

			$meta['bpc_end_date'] = $end_date;
		}

		$template = null;

		if ( isset( $input['template_key'] ) && '' !== trim( (string) $input['template_key'] ) ) {
			// Checked before the dry-run branch, like the end date: a preview must
			// refuse whatever the real call refuses.
			$template = self::offered_template( sanitize_key( (string) $input['template_key'] ) );

			if ( is_wp_error( $template ) ) {
				return $template;
			}
		}

		// What the builder gives every new campaign, then the template, then what the
		// caller asked for, then add-ons (Pro starts new campaigns on its popup form).
		$meta = array_merge(
			self::new_campaign_meta(),
			null !== $template ? self::template_meta( $template, $title ) : array(),
			$meta
		);

		/** This filter is documented in includes/API/CampaignAPI.php */
		$filtered = apply_filters( 'better_payment/campaign/create_params', $meta, null );
		$meta     = is_array( $filtered ) ? $filtered : $meta;

		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				sprintf(
					/* translators: %s: campaign title. */
					__( 'A draft campaign titled "%s" would be created.', 'better-payment' ),
					$title
				),
				array(
					'create_campaign' => array(
						'title'        => $title,
						'status'       => 'draft',
						'meta'         => array_keys( $meta ),
						'template_key' => null !== $template ? (string) $template['key'] : null,
					),
				)
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_title'   => $title,
				'post_content' => isset( $input['description'] ) ? wp_kses_post( (string) $input['description'] ) : '',
				// Draft, like every campaign the builder creates. Publishing is a
				// separate, deliberate act.
				'post_status'  => 'draft',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		MetaBox::save_from_array( (int) $post_id, $meta );

		return array( 'campaign' => self::summary( get_post( $post_id ) ) );
	}

	/**
	 * The settings the builder gives every new campaign.
	 *
	 * PHP twin of the non-empty values in the builder's `BASE_META`
	 * (`src/campaign-builder/store/campaignStore.js`), which a campaign made by hand
	 * saves on its first save. Without them a campaign created here had no suggested
	 * amounts (the renderer fell back to 10 / 25 / 50 / 100), and the custom amount
	 * switched off, since an absent `_bpc_allow_custom_amount` reads as 0.
	 *
	 * @return array Meta for MetaBox::save_from_array().
	 */
	public static function new_campaign_meta() {
		return array(
			'bpc_status'              => 'active',
			'bpc_allow_custom_amount' => 1,
			'bpc_color_primary'       => '#6b63f6',
			'bpc_suggested_amounts'   => array(
				array(
					'id'          => 'sa_1',
					'amount'      => '5',
					'description' => '',
					'is_default'  => false,
				),
				array(
					'id'          => 'sa_2',
					'amount'      => '10',
					'description' => '',
					'is_default'  => false,
				),
				array(
					'id'          => 'sa_3',
					'amount'      => '15',
					'description' => '',
					'is_default'  => true,
				),
				array(
					'id'          => 'sa_4',
					'amount'      => '20',
					'description' => '',
					'is_default'  => false,
				),
			),
		);
	}

	/**
	 * Apply a template to an existing campaign.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function apply_template( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$input    = is_array( $input ) ? $input : array();
		$template = self::offered_template( sanitize_key( isset( $input['template_key'] ) ? (string) $input['template_key'] : '' ) );

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		$meta = self::template_meta( $template, (string) $post->post_title );

		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				sprintf(
					/* translators: 1: template key, 2: campaign id. */
					__( 'Template "%1$s" would replace the page of campaign %2$d.', 'better-payment' ),
					(string) $template['key'],
					(int) $post->ID
				),
				array(
					'apply_template' => array(
						'id'           => (int) $post->ID,
						'template_key' => (string) $template['key'],
						'meta'         => array_keys( $meta ),
					),
				)
			);
		}

		// save_from_array() runs enforce_pro_entitlement() on the layout, as for
		// campaign_edit.
		MetaBox::save_from_array( (int) $post->ID, $meta );
		CampaignStats::bust_cache( (int) $post->ID );

		return array( 'campaign' => self::summary( get_post( $post->ID ) ) );
	}

	/**
	 * A template the picker offers and this install can apply.
	 *
	 * Only `get_for_picker()` is consulted, so a retired (`hidden`) design is never
	 * started from. A template with no layout is a locked Pro card on a free install:
	 * applying it would build an empty campaign, so it is refused instead.
	 *
	 * @param string $key Template key.
	 * @return array|\WP_Error
	 */
	private static function offered_template( $key ) {
		$templates = TemplateManager::get_for_picker();

		if ( '' === $key || ! isset( $templates[ $key ] ) ) {
			return Result::invalid( __( 'Unknown template. Call campaign_templates for the available keys.', 'better-payment' ) );
		}

		$template = $templates[ $key ];

		if ( empty( $template['columns'] ) || ! is_array( $template['columns'] ) ) {
			return Result::invalid( __( 'This template needs Better Payment Pro. Pick a template with has_layout true.', 'better-payment' ) );
		}

		$template['key'] = $key;

		return $template;
	}

	/**
	 * Merge each element's registry defaults under its settings.
	 *
	 * The builder does this for every layout it loads from an AI `set_layout`
	 * (`stableIds()` in campaignStore.js): a key the caller omitted takes the
	 * element's default, and a key it sent, even empty, is kept.
	 *
	 * @param array $layout Layout with `columns`.
	 * @return array
	 */
	private static function with_element_defaults( array $layout ) {
		if ( empty( $layout['columns'] ) || ! is_array( $layout['columns'] ) ) {
			return $layout;
		}

		foreach ( $layout['columns'] as $c => $column ) {
			if ( ! is_array( $column ) || empty( $column['elements'] ) || ! is_array( $column['elements'] ) ) {
				continue;
			}

			foreach ( $column['elements'] as $e => $element ) {
				if ( ! is_array( $element ) || empty( $element['type'] ) ) {
					continue;
				}

				$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

				$layout['columns'][ $c ]['elements'][ $e ]['settings'] = array_merge(
					ElementRegistry::get_defaults( (string) $element['type'] ),
					$settings
				);
			}
		}

		return $layout;
	}

	/**
	 * The campaign meta that applying a template writes.
	 *
	 * Server-side twin of the builder's `APPLY_TEMPLATE` reducer case: the layout is
	 * copied with fresh column and element ids and the registry defaults merged under
	 * each element's settings, the key is stored (the renderer resolves the theme from
	 * it on every view, and the builder opens such a campaign in the Editor), the
	 * template's accent becomes the campaign colour, and an untitled Title element
	 * takes the campaign title.
	 *
	 * @param array  $template Template definition from the registry.
	 * @param string $title    Campaign title.
	 * @return array Meta for MetaBox::save_from_array().
	 */
	private static function template_meta( array $template, $title ) {
		$columns = array();

		foreach ( $template['columns'] as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}

			$elements = array();

			foreach ( isset( $column['elements'] ) && is_array( $column['elements'] ) ? $column['elements'] : array() as $element ) {
				if ( ! is_array( $element ) || empty( $element['type'] ) ) {
					continue;
				}

				$type     = (string) $element['type'];
				$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
				$settings = array_merge( ElementRegistry::get_defaults( $type ), $settings );

				if ( 'campaign_title' === $type && empty( $settings['title'] ) && '' !== $title ) {
					$settings['title'] = $title;
				}

				$element['id']       = 'el_' . substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 12 );
				$element['settings'] = $settings;
				$elements[]          = $element;
			}

			$column['id']       = 'col_' . substr( wp_generate_uuid4(), 0, 8 );
			$column['elements'] = $elements;
			$columns[]          = $column;
		}

		$meta = array(
			'bpc_template_key'  => (string) $template['key'],
			'bpc_fields_layout' => wp_json_encode(
				array(
					'layout'  => isset( $template['layout'] ) ? $template['layout'] : '',
					'columns' => $columns,
				)
			),
		);

		if ( ! empty( $template['preview_color'] ) ) {
			$meta['bpc_color_primary'] = (string) $template['preview_color'];
		}

		return $meta;
	}

	/**
	 * Apply an operation batch.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function apply_operations( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$input      = is_array( $input ) ? $input : array();
		$operations = isset( $input['operations'] ) && is_array( $input['operations'] ) ? $input['operations'] : array();

		if ( empty( $operations ) ) {
			return Result::invalid( __( 'No operations supplied.', 'better-payment' ) );
		}

		// A currency change is taken out before validation: the AI validator drops
		// it (the assistant may never write the currency), and it has its own lock.
		$currency_request = null;

		foreach ( $operations as $index => $operation ) {
			if ( is_array( $operation )
				&& isset( $operation['op'], $operation['key'] )
				&& 'update_meta' === $operation['op']
				&& in_array( (string) $operation['key'], self::CURRENCY_KEYS, true )
			) {
				$currency_request = isset( $operation['value'] ) && is_scalar( $operation['value'] ) ? (string) $operation['value'] : '';
				unset( $operations[ $index ] );
			}
		}

		$operations = array_values( $operations );
		$currency   = null === $currency_request ? null : self::plan_currency_change( (int) $post->ID, $currency_request );

		if ( empty( $operations ) && null !== $currency ) {
			return self::apply_currency_only( $post, $currency, DryRun::requested( $input ) );
		}

		$meta    = MetaBox::get_all( (int) $post->ID );
		$current = isset( $meta['bpc_fields_layout'] ) && is_array( $meta['bpc_fields_layout'] )
			? $meta['bpc_fields_layout']
			: array();

		// The same validator the AI assistant runs. It drops malformed operations,
		// strips values the model must not set, and refuses unknown element types.
		$valid = OperationValidator::validate_batch(
			$operations,
			array(
				'campaign_id' => (int) $post->ID,
				'layout'      => $current,
				'meta'        => $meta,
			)
		);

		// The validator also accepts the builder-only operations (update_block,
		// insert_block, …), which the AI panel applies in the browser. Nothing on
		// this route applies them, so they used to be counted as applied while the
		// campaign did not change. They are reported instead.
		$unsupported = array();

		foreach ( $valid as $index => $operation ) {
			$op = isset( $operation['op'] ) ? (string) $operation['op'] : '';

			if ( ! in_array( $op, self::EDIT_OPERATIONS, true ) ) {
				$unsupported[] = $op;
				unset( $valid[ $index ] );
			}
		}

		$valid       = array_values( $valid );
		$unsupported = array_values( array_unique( $unsupported ) );
		$dropped     = count( $operations ) - count( $valid ) - count( $unsupported );

		if ( empty( $valid ) ) {
			// The other operations were all dropped; the currency change still stands
			// on its own, reported like a currency-only call.
			if ( null !== $currency && empty( $unsupported ) ) {
				return self::apply_currency_only( $post, $currency, DryRun::requested( $input ) );
			}

			if ( ! empty( $unsupported ) ) {
				return Result::invalid(
					sprintf(
						/* translators: 1: operation names, 2: supported operation names. */
						__( 'Not supported by campaign_edit: %1$s. Use %2$s; to change one element send set_layout with the whole layout, and to use a prebuilt design call campaign_apply_template.', 'better-payment' ),
						implode( ', ', $unsupported ),
						implode( ', ', self::EDIT_OPERATIONS )
					)
				);
			}

			return Result::invalid( __( 'No valid operations. Call campaign_element_schema for the accepted element types and setting keys.', 'better-payment' ) );
		}

		$derived = StateDeriver::derive( $valid );

		if ( null !== $derived['layout'] ) {
			$derived['layout'] = self::with_element_defaults( $derived['layout'] );
		}

		// After validation and derivation, before anything is written: a preview that
		// succeeded where the real call would fail would be a promise the plugin does
		// not keep.
		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				sprintf(
					/* translators: %d: number of operations. */
					_n( '%d operation would be applied.', '%d operations would be applied.', count( $valid ), 'better-payment' ),
					count( $valid )
				),
				array_merge(
					array(
						'operations_applied' => count( $valid ),
						'operations_dropped' => max( 0, $dropped ),
						'unsupported'        => $unsupported,
						'meta_keys'          => array_map( 'strval', array_keys( $derived['meta'] ) ),
						'layout_replaced'    => null !== $derived['layout'],
					),
					null !== $currency ? array( 'currency' => $currency ) : array()
				)
			);
		}

		$data = array();

		if ( null !== $derived['layout'] ) {
			// save_from_array() runs enforce_pro_entitlement() on this key, so a
			// site without Pro cannot gain Pro element configuration through MCP,
			// and a lapsed subscriber's stored settings are restored rather than
			// destroyed. Entitlement is read live from better_payment/pro_enabled.
			$data['bpc_fields_layout'] = wp_json_encode( $derived['layout'] );
		}

		$title_changed = false;

		foreach ( $derived['meta'] as $key => $value ) {
			if ( 'title' === $key ) {
				wp_update_post(
					array(
						'ID'         => (int) $post->ID,
						'post_title' => sanitize_text_field( (string) $value ),
					)
				);
				$title_changed = true;
				continue;
			}

			if ( 'bpc_end_date' === $key && ! self::is_future_date( (string) $value ) ) {
				// Dropped rather than corrected: no end date is a valid state, while
				// a silently "fixed" date is a deadline nobody chose.
				continue;
			}

			$data[ $key ] = $value;
		}

		if ( ! empty( $data ) ) {
			MetaBox::save_from_array( (int) $post->ID, $data );
		}

		if ( null !== $currency ) {
			$currency = self::write_currency( (int) $post->ID, $currency );
		}

		CampaignStats::bust_cache( (int) $post->ID );

		$result = array(
			'applied'       => count( $valid ),
			'dropped'       => max( 0, $dropped ),
			'unsupported'   => $unsupported,
			'title_changed' => $title_changed,
			'meta'          => Result::plain( MetaBox::get_all( (int) $post->ID ) ),
		);

		if ( null !== $currency ) {
			$result['currency'] = $currency;
		}

		return $result;
	}

	/**
	 * A campaign's currency, as the tools report it.
	 *
	 * @param int $campaign_id Campaign id.
	 * @return array{code: string, source: string, locked: bool, unsupported_gateways: string[]}
	 */
	public static function currency_info( $campaign_id ) {
		$campaign_id = (int) $campaign_id;
		$code        = CampaignCurrency::of( $campaign_id );

		return array(
			'code'                 => $code,
			// "campaign": stamped, a site currency change leaves it alone.
			// "site": created before per-campaign currencies, follows the setting.
			'source'               => '' !== CampaignCurrency::stored( $campaign_id ) ? 'campaign' : 'site',
			'locked'               => CampaignCurrency::is_locked( $campaign_id ),
			'unsupported_gateways' => CampaignCurrency::unsupported_gateways( $code ),
		);
	}

	/**
	 * Decide what a requested currency change would do, writing nothing.
	 *
	 * @param int    $campaign_id Campaign id.
	 * @param string $requested   Requested code.
	 * @return array<string, mixed> `status` is `change`, `unchanged` or `refused`.
	 */
	private static function plan_currency_change( $campaign_id, $requested ) {
		$current = CampaignCurrency::of( $campaign_id );
		$stored  = CampaignCurrency::stored( $campaign_id );
		$code    = CampaignCurrency::sanitize( $requested );

		$plan = array(
			'status' => 'refused',
			'from'   => $current,
			'to'     => $current,
		);

		if ( '' === $code ) {
			$plan['reason']  = 'unknown_currency';
			$plan['message'] = sprintf(
				/* translators: %s: requested currency code. */
				__( '"%s" is not a currency Better Payment offers. Use a three-letter code from the site currency list (for example USD, EUR, ZAR).', 'better-payment' ),
				$requested
			);
			return $plan;
		}

		if ( $code === $stored ) {
			$plan['status']  = 'unchanged';
			$plan['message'] = sprintf(
				/* translators: %s: currency code. */
				__( 'The campaign already takes donations in %s.', 'better-payment' ),
				$code
			);
			return $plan;
		}

		if ( CampaignCurrency::is_locked( $campaign_id ) ) {
			$plan['reason']  = 'currency_locked';
			$plan['message'] = sprintf(
				/* translators: 1: campaign currency, 2: requested currency. */
				__( 'This campaign has already received donations, so its currency stays %1$s: its goal and the money already raised are in %1$s, and switching would split them. To raise money in %2$s, create a new campaign while the site currency is %2$s (or set its currency before the first donation).', 'better-payment' ),
				$current,
				$code
			);
			return $plan;
		}

		$plan['status']               = 'change';
		$plan['to']                   = $code;
		$plan['unsupported_gateways'] = CampaignCurrency::unsupported_gateways( $code );
		$plan['message']              = sprintf(
			/* translators: 1: old currency, 2: new currency. */
			__( 'The campaign\'s currency changes from %1$s to %2$s. Its goal and donation amounts keep their numbers; they now read as %2$s.', 'better-payment' ),
			$current,
			$code
		);

		return $plan;
	}

	/**
	 * Store a planned currency change.
	 *
	 * @param int                  $campaign_id Campaign id.
	 * @param array<string, mixed> $plan        From plan_currency_change().
	 * @return array<string, mixed> The plan, `status` now `changed` when it was written.
	 */
	private static function write_currency( $campaign_id, array $plan ) {
		if ( 'change' !== $plan['status'] ) {
			return $plan;
		}

		// save_from_array() runs CampaignCurrency::enforce_lock() again, so a donation
		// that landed after the plan was made still keeps the currency.
		MetaBox::save_from_array( $campaign_id, array( 'bpc_currency' => $plan['to'] ) );
		CampaignStats::bust_cache( $campaign_id );

		$now = CampaignCurrency::of( $campaign_id );

		if ( $now !== $plan['to'] ) {
			$plan['status']  = 'refused';
			$plan['reason']  = 'currency_locked';
			$plan['to']      = $now;
			$plan['message'] = __( 'A donation arrived before the change was saved, so the campaign keeps its currency.', 'better-payment' );

			return $plan;
		}

		$plan['status'] = 'changed';

		return $plan;
	}

	/**
	 * campaign_edit with nothing but a currency change.
	 *
	 * @param \WP_Post             $post    Campaign.
	 * @param array<string, mixed> $plan    From plan_currency_change().
	 * @param bool                 $dry_run Whether to write nothing.
	 * @return array|\WP_Error
	 */
	private static function apply_currency_only( $post, array $plan, $dry_run ) {
		// A refusal is an error result, so the model reports it instead of "done".
		if ( 'refused' === $plan['status'] ) {
			return Result::refused( (string) $plan['message'] );
		}

		if ( $dry_run ) {
			return DryRun::preview( (string) $plan['message'], array( 'currency' => $plan ) );
		}

		$plan = self::write_currency( (int) $post->ID, $plan );

		if ( 'refused' === $plan['status'] ) {
			return Result::refused( (string) $plan['message'] );
		}

		return array(
			'applied'  => 'changed' === $plan['status'] ? 1 : 0,
			'currency' => $plan,
			'campaign' => self::summary( get_post( (int) $post->ID ) ),
		);
	}

	/**
	 * What a change of the site currency does to the existing campaigns.
	 *
	 * The site currency is only the starting currency of a NEW campaign. A campaign
	 * stamped with its own currency keeps it; only campaigns created before
	 * per-campaign currencies (no stamp) follow the setting. An assistant that
	 * changed the setting must be able to say which is which, rather than tell the
	 * owner the campaigns now take the new currency.
	 *
	 * @param string $new_code The new site currency.
	 * @return array<string, mixed>
	 */
	public static function site_currency_impact( $new_code ) {
		$new_code = strtoupper( (string) $new_code );

		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$keep   = array();
		$follow = array();
		$counts = array(
			'keep_own_currency'    => 0,
			'can_change'           => 0,
			'follow_site_currency' => 0,
			'already_in_currency'  => 0,
		);

		foreach ( (array) $ids as $id ) {
			$id     = (int) $id;
			$stored = CampaignCurrency::stored( $id );
			$row    = array(
				'id'     => $id,
				'title'  => (string) get_the_title( $id ),
				'status' => (string) get_post_status( $id ),
			);

			if ( '' === $stored ) {
				++$counts['follow_site_currency'];
				if ( count( $follow ) < self::IMPACT_LIST_LIMIT ) {
					// A locked one keeps its earlier donations in the old currency,
					// shown beside the total as "+ other currency".
					$row['has_donations'] = CampaignCurrency::is_locked( $id );
					$follow[]             = $row;
				}
				continue;
			}

			if ( $stored === $new_code ) {
				++$counts['already_in_currency'];
				continue;
			}

			$locked = CampaignCurrency::is_locked( $id );

			++$counts['keep_own_currency'];
			if ( ! $locked ) {
				++$counts['can_change'];
			}

			if ( count( $keep ) < self::IMPACT_LIST_LIMIT ) {
				$row['currency']   = $stored;
				$row['can_change'] = ! $locked;
				$keep[]            = $row;
			}
		}

		return array(
			'note'                 => sprintf(
				/* translators: %s: new site currency. */
				__( 'The site currency is the currency NEW campaigns start in; it does not convert or re-label existing campaigns. Every campaign created since per-campaign currencies has its own currency and keeps it: those are listed under keep_own_currency and still take donations in that currency. One with can_change true has no donation yet and can be switched with campaign_edit (update_meta bpc_currency = %1$s); one with can_change false has received donations and its currency can never change. Campaigns under follow_site_currency were created earlier, have no currency of their own and now use %1$s.', 'better-payment' ),
				$new_code
			),
			'counts'               => $counts,
			'keep_own_currency'    => $keep,
			'follow_site_currency' => $follow,
			'unsupported_gateways' => CampaignCurrency::unsupported_gateways( $new_code ),
		);
	}

	/**
	 * Publish or unpublish.
	 *
	 * @param mixed $input Tool arguments.
	 * @return array|\WP_Error
	 */
	public static function publish( $input = array() ) {
		$post = self::resolve( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$input  = is_array( $input ) ? $input : array();
		$status = isset( $input['status'] ) ? (string) $input['status'] : 'publish';
		$status = in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'publish';

		if ( DryRun::requested( $input ) ) {
			return DryRun::preview(
				sprintf(
					/* translators: 1: campaign id, 2: target status. */
					__( 'Campaign %1$d would be set to "%2$s".', 'better-payment' ),
					(int) $post->ID,
					$status
				),
				array(
					'id'     => (int) $post->ID,
					'status' => array(
						'from' => (string) $post->post_status,
						'to'   => $status,
					),
				)
			);
		}

		$updated = wp_update_post(
			array(
				'ID'          => (int) $post->ID,
				'post_status' => $status,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		return array( 'campaign' => self::summary( get_post( (int) $post->ID ) ) );
	}

	/**
	 * Whether a date string is today or later in the site's timezone.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	private static function is_future_date( $date ) {
		$date = trim( (string) $date );

		if ( '' === $date ) {
			return false;
		}

		$timestamp = strtotime( $date . ' 23:59:59' );

		if ( false === $timestamp ) {
			// Unparseable is not "past": conflating them makes a typo look like a
			// deliberate backdate. The caller treats false as "do not store".
			return false;
		}

		return $timestamp >= current_time( 'timestamp' );
	}
}
