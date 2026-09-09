<?php
/**
 * GENERATED FILE — DO NOT EDIT BY HAND.
 *
 * Preview-only mirror of Better Payment Pro's offered template layouts, used to
 * render the locked Pro cards in the template picker on a FREE install. Nothing
 * here is ever handed to the browser as a layout: TemplateManager still reports
 * `columns => []` for these keys, so a client that ignores every Pro gate and
 * dispatches APPLY_TEMPLATE still builds an empty campaign. See
 * ProTemplatePreviews and docs/features/campaign-builder/templates.md.
 *
 * Regenerate with:
 *     wp eval-file scripts/export-pro-preview-layouts.php
 *
 * `@bp-assets/` prefixes are resolved against BETTER_PAYMENT_ASSETS at load time;
 * `creator_user_id` is filled in per site. Neither may be baked in here.
 *
 * @package Better_Payment\Lite
 */

return array(
	'pro-community-support-v2' => array(
		'layout' => '1-column',
		'columns' => array(
			0 => array(
				'id' => 'main',
				'label' => 'Campaign',
				'width' => '100%',
				'elements' => array(
					0 => array(
						'id' => 'el_photo',
						'type' => 'photo',
						'settings' => array(
							'src' => '@bp-assets/img/campaign/templates/environmental/Wildlife-Conservation-Campaign.webp',
							'alt' => 'Wildlife thriving in a protected natural habitat',
							'width' => 100,
							'align' => 'center',
						),
					),
					1 => array(
						'id' => 'el_title',
						'type' => 'campaign_title',
						'settings' => array(
							'align' => 'left',
							'font_size' => 42,
							'font_weight' => '700',
							'letter_spacing' => -0.8,
							'line_height' => 1.1,
							'color' => '#1e2a10',
						),
					),
					2 => array(
						'id' => 'el_desc',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => 'Why a habitat needs funding',
							'content' => 'Wildlife does not recover on its own once the habitat has gone. Hedgerows have to be replanted, ponds re-dug, invasive species pulled out year after year, and the whole thing monitored long enough to know whether any of it worked. That last part is what nobody funds, and it is the part that turns a good season into a recovered population.',
							'title_font_size' => 24,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.3,
							'title_color' => '#1e2a10',
							'font_size' => 17,
							'line_height' => 1.8,
							'color' => '#4f5b3c',
						),
					),
					3 => array(
						'id' => 'el_progress',
						'type' => 'progress_bar',
						'settings' => array(
							'headline' => 'Our target',
							'show_donated' => true,
							'show_goal' => true,
							'round_amounts' => true,
							'donate_label' => 'Raised:',
							'goal_label' => 'Target:',
							'width' => 100,
							'align' => 'left',
						),
					),
					4 => array(
						'id' => 'el_summary',
						'type' => 'campaign_summary',
						'settings' => array(
							'headline' => '',
							'show_raised' => true,
							'show_donors' => true,
							'show_percent' => true,
							'show_days' => false,
							'width' => 100,
							'align' => 'left',
						),
					),
					5 => array(
						'id' => 'el_amounts',
						'type' => 'donate_amount',
						'settings' => array(
							'headline' => 'Fund a hectare',
						),
					),
					6 => array(
						'id' => 'el_donate',
						'type' => 'donation_form',
						'settings' => array(
							'button_label' => 'Protect this habitat',
							'button_color' => '#4d7c0f',
							'url' => '',
							'width' => 100,
							'align' => 'center',
						),
					),
					7 => array(
						'id' => 'el_video',
						'type' => 'video',
						'settings' => array(
							'url' => '',
							'aspect_ratio' => '16-9',
							'controls' => true,
							'autoplay' => false,
							'width' => 100,
							'align' => 'center',
						),
					),
					8 => array(
						'id' => 'el_donors',
						'type' => 'donors_wall',
						'settings' => array(
							'headline' => 'Our supporters',
							'layout' => 'grid',
							'columns' => 3,
							'number_to_show' => 12,
							'order_by' => 'amount',
							'order' => 'desc',
							'hide_if_empty' => false,
							'show_summary' => true,
							'show_name' => true,
							'show_amount' => true,
							'show_avatar' => true,
							'show_date' => false,
							'accent_color' => '#4d7c0f',
							'width' => 100,
							'align' => 'left',
						),
					),
					9 => array(
						'id' => 'el_faq',
						'type' => 'faq',
						'settings' => array(
							'heading' => 'Common questions',
							'items' => array(
								0 => array(
									'question' => 'Do you work on one species or the whole habitat?',
									'answer' => 'The habitat. A species doing well in a place that is not will not stay well for long — individual counts are how we tell whether the habitat work is succeeding.',
								),
								1 => array(
									'question' => 'Who owns the land you work on?',
									'answer' => 'Some of it is ours; most of it is not. The majority of this work happens by agreement with farmers, councils and private landowners, which is slower to arrange and far cheaper to sustain.',
								),
								2 => array(
									'question' => 'How do you know it is working?',
									'answer' => 'Counts, surveys and fixed-point photographs on a set schedule, published whether or not they flatter us. Restoration that is only ever described in adjectives is not restoration.',
								),
								3 => array(
									'question' => 'Can I visit or volunteer?',
									'answer' => 'Yes to both, in season. Some sites close during nesting and breeding, so check the calendar before you travel.',
								),
							),
							'style' => 'accordion',
							'icon_style' => 'chevron',
							'single_open' => false,
							'first_open' => false,
							'accent_color' => '#4d7c0f',
							'width' => 100,
							'align' => 'left',
						),
					),
					10 => array(
						'id' => 'el_organizer',
						'type' => 'organizer',
						'settings' => array(
							'creator_user_id' => 0,
							'role_title' => 'Reserve Manager',
							'description' => 'I look after these sites and write the survey updates. Happy to answer any question about where the money goes.',
							'width' => 100,
							'align' => 'left',
						),
					),
					11 => array(
						'id' => 'el_links',
						'type' => 'social_links',
						'settings' => array(
							'headline' => 'Find us',
							'align' => 'left',
							'facebook' => 'https://facebook.com/',
							'instagram' => 'https://instagram.com/',
						),
					),
					12 => array(
						'id' => 'el_share',
						'type' => 'social_sharing',
						'settings' => array(
							'headline' => 'Tell a neighbour',
							'facebook' => true,
							'twitter' => true,
							'linkedin' => true,
							'threads' => true,
							'pinterest' => false,
							'mastodon' => false,
							'bluesky' => false,
							'align' => 'left',
						),
					),
				),
			),
		),
	),
	'pro-emergency-relief-v2' => array(
		'layout' => '2-column',
		'columns' => array(
			0 => array(
				'id' => 'main',
				'label' => 'Appeal',
				'width' => '58%',
				'elements' => array(
					0 => array(
						'id' => 'el_title',
						'type' => 'campaign_title',
						'settings' => array(
							'align' => 'left',
							'font_size' => 44,
							'font_weight' => '800',
							'letter_spacing' => -1.1,
							'line_height' => 1.08,
							'color' => '#3d2f14',
						),
					),
					1 => array(
						'id' => 'el_urgent',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => 'The shelves do not refill themselves',
							'content' => 'What goes out to families this week is what came in last week. There is no reserve behind it and no season when the need pauses — referrals keep arriving through the summer, through the holidays, and through every month in between.',
							'title_font_size' => 24,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.3,
							'title_color' => '#3d2f14',
							'font_size' => 17,
							'line_height' => 1.75,
							'color' => '#6b5b3e',
						),
					),
					2 => array(
						'id' => 'el_hero',
						'type' => 'photo',
						'settings' => array(
							'src' => '@bp-assets/img/campaign/templates/charity/Food-Drive-Campaign.webp',
							'alt' => 'Volunteers sorting and packing food donations for families in need',
							'width' => 100,
							'align' => 'center',
						),
					),
					3 => array(
						'id' => 'el_situation',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => 'How the drive works',
							'content' => 'Donations become food boxes: staples that keep, fresh items bought close to the day they go out, and the things people forget to donate but every household needs — nappies, toiletries, washing powder, pet food. Boxes are packed by volunteers and handed over by referral, so what arrives matches what a household actually eats.',
							'title_font_size' => 24,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.3,
							'title_color' => '#3d2f14',
							'font_size' => 17,
							'line_height' => 1.75,
							'color' => '#6b5b3e',
						),
					),
					4 => array(
						'id' => 'el_faq',
						'type' => 'faq',
						'settings' => array(
							'heading' => 'Before you give',
							'items' => array(
								0 => array(
									'question' => 'Would you rather have food or money?',
									'answer' => 'Money, if you are choosing. It buys at wholesale prices, fills the gaps rather than adding to the surplus, and pays for fresh food close to the day it goes out. Donated food is always welcome — it just cannot be chosen.',
								),
								1 => array(
									'question' => 'Who receives the boxes?',
									'answer' => 'Households referred by schools, health visitors, social workers and support services. Nobody is asked to prove their circumstances twice.',
								),
								2 => array(
									'question' => 'What happens to anything raised beyond the target?',
									'answer' => 'It carries into the following months. Demand does not stop when a total is reached, and a drive that closes on target simply reopens.',
								),
								3 => array(
									'question' => 'Can my workplace or school run a collection?',
									'answer' => 'Yes, and those collections are where a lot of this comes from. Get in touch and we will send the list of what is short this month.',
								),
							),
							'style' => 'accordion',
							'icon_style' => 'plus',
							'single_open' => true,
							'first_open' => true,
							'accent_color' => '#f59e0b',
							'width' => 100,
							'align' => 'left',
						),
					),
				),
			),
			1 => array(
				'id' => 'donate',
				'label' => 'Give Now',
				'width' => '42%',
				'elements' => array(
					0 => array(
						'id' => 'el_progress',
						'type' => 'progress_bar',
						'settings' => array(
							'headline' => 'Drive target',
							'show_donated' => true,
							'show_goal' => true,
							'round_amounts' => true,
							'donate_label' => 'Raised so far:',
							'goal_label' => 'Drive target:',
							'width' => 100,
							'align' => 'left',
						),
					),
					1 => array(
						'id' => 'el_summary',
						'type' => 'campaign_summary',
						'settings' => array(
							'headline' => '',
							'show_raised' => true,
							'show_donors' => true,
							'show_percent' => false,
							'show_days' => true,
							'width' => 100,
							'align' => 'left',
						),
					),
					2 => array(
						'id' => 'el_amounts',
						'type' => 'donate_amount',
						'settings' => array(
							'headline' => 'Fill a food box',
						),
					),
					3 => array(
						'id' => 'el_donate',
						'type' => 'donation_form',
						'settings' => array(
							'button_label' => 'Fund a food box',
							'button_color' => '#f59e0b',
							'url' => '',
							'width' => 100,
							'align' => 'center',
						),
					),
					4 => array(
						'id' => 'el_video',
						'type' => 'video',
						'settings' => array(
							'url' => '',
							'aspect_ratio' => '16-9',
							'controls' => true,
							'autoplay' => false,
							'width' => 100,
							'align' => 'center',
						),
					),
					5 => array(
						'id' => 'el_donors',
						'type' => 'donors_wall',
						'settings' => array(
							'headline' => 'Donations arriving',
							'layout' => 'ticker',
							'columns' => 3,
							'number_to_show' => 20,
							'order_by' => 'date',
							'order' => 'desc',
							'hide_if_empty' => true,
							'show_summary' => true,
							'show_name' => true,
							'show_amount' => true,
							'show_avatar' => false,
							'show_date' => false,
							'accent_color' => '#f59e0b',
							'width' => 100,
							'align' => 'left',
						),
					),
					6 => array(
						'id' => 'el_organizer',
						'type' => 'organizer',
						'settings' => array(
							'creator_user_id' => 0,
							'role_title' => 'Drive Coordinator',
							'description' => 'I run the packing sessions and post what we are short of each week. Ask me anything before you give.',
							'width' => 100,
							'align' => 'left',
						),
					),
					7 => array(
						'id' => 'el_share',
						'type' => 'social_sharing',
						'settings' => array(
							'headline' => 'Spread the word',
							'facebook' => true,
							'twitter' => true,
							'linkedin' => true,
							'threads' => true,
							'pinterest' => false,
							'mastodon' => false,
							'bluesky' => false,
							'align' => 'left',
						),
					),
				),
			),
		),
	),
	'pro-modern-fundraising-v2' => array(
		'layout' => 'split',
		'columns' => array(
			0 => array(
				'id' => 'top',
				'label' => 'Hero (Full Width)',
				'width' => '100%',
				'elements' => array(
					0 => array(
						'id' => 'el_title',
						'type' => 'campaign_title',
						'settings' => array(
							'align' => 'left',
							'font_size' => 46,
							'font_weight' => '800',
							'letter_spacing' => -1.2,
							'line_height' => 1.06,
							'color' => '#0b2540',
						),
					),
					1 => array(
						'id' => 'el_lede',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => '',
							'content' => 'A working water point changes the shape of a day. The hours spent walking come back, the illnesses that come from drinking the wrong thing stop, and the children who were kept home to fetch water go back to school.',
							'title_font_size' => 25,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.4,
							'title_color' => '#0b2540',
							'font_size' => 21,
							'line_height' => 1.75,
							'color' => '#42607d',
						),
					),
					2 => array(
						'id' => 'el_hero',
						'type' => 'photo',
						'settings' => array(
							'src' => '@bp-assets/img/campaign/templates/charity/Clean-Water-Campaign.webp',
							'alt' => 'A community gaining access to clean, safe drinking water',
							'width' => 100,
							'align' => 'center',
						),
					),
					3 => array(
						'id' => 'el_video',
						'type' => 'video',
						'settings' => array(
							'url' => '',
							'aspect_ratio' => '16-9',
							'controls' => true,
							'autoplay' => false,
							'width' => 100,
							'align' => 'center',
						),
					),
				),
			),
			1 => array(
				'id' => 'bottom-left',
				'label' => 'Story',
				'width' => '50%',
				'elements' => array(
					0 => array(
						'id' => 'el_story',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => 'Where your donation goes',
							'content' => 'Boreholes, hand pumps, storage tanks and the pipework between them — sited with the community rather than for it, so what gets built is what gets used. The rest funds the part that decides whether any of it lasts: training local mechanics, stocking the spare parts they need, and going back three years later to check the pump is still turning.',
							'title_font_size' => 25,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.4,
							'title_color' => '#0b2540',
							'font_size' => 17,
							'line_height' => 1.75,
							'color' => '#42607d',
						),
					),
					1 => array(
						'id' => 'el_faq',
						'type' => 'faq',
						'settings' => array(
							'heading' => 'Questions people ask before giving',
							'items' => array(
								0 => array(
									'question' => 'How long does a water point last?',
									'answer' => 'As long as someone can fix it. The hardware is the easy part — the reason so many stand broken is that nobody was trained or funded to maintain them. Maintenance is in the budget from the start, not added later.',
								),
								1 => array(
									'question' => 'Who decides where it gets built?',
									'answer' => 'The community, with our hydrologists advising on what the ground will support. A pump in the wrong place is a pump nobody walks to.',
								),
								2 => array(
									'question' => 'Is my payment secure?',
									'answer' => 'Yes. Payments are handled by the payment provider directly — your card details are never stored on this site.',
								),
								3 => array(
									'question' => 'Can I give monthly instead of once?',
									'answer' => 'Yes, and it helps more than you would think. Regular gifts let us commit to a build schedule months ahead instead of only reacting when donations spike.',
								),
							),
							'style' => 'accordion',
							'icon_style' => 'chevron',
							'single_open' => true,
							'first_open' => true,
							'accent_color' => '#0369a1',
							'width' => 100,
							'align' => 'left',
						),
					),
					2 => array(
						'id' => 'el_organizer',
						'type' => 'organizer',
						'settings' => array(
							'creator_user_id' => 0,
							'role_title' => 'Programme Lead',
							'description' => 'I run this programme and answer every message personally. Get in touch if you would like to talk before giving.',
							'width' => 100,
							'align' => 'left',
						),
					),
				),
			),
			2 => array(
				'id' => 'bottom-right',
				'label' => 'Donate',
				'width' => '50%',
				'elements' => array(
					0 => array(
						'id' => 'el_progress',
						'type' => 'progress_bar',
						'settings' => array(
							'headline' => 'Water point fund',
							'show_donated' => true,
							'show_goal' => true,
							'round_amounts' => true,
							'donate_label' => 'Raised:',
							'goal_label' => 'Build target:',
							'width' => 100,
							'align' => 'left',
						),
					),
					1 => array(
						'id' => 'el_summary',
						'type' => 'campaign_summary',
						'settings' => array(
							'headline' => '',
							'show_raised' => true,
							'show_donors' => true,
							'show_percent' => true,
							'show_days' => true,
							'width' => 100,
							'align' => 'left',
						),
					),
					2 => array(
						'id' => 'el_amounts',
						'type' => 'donate_amount',
						'settings' => array(
							'headline' => 'Fund a water point',
						),
					),
					3 => array(
						'id' => 'el_donate',
						'type' => 'donation_form',
						'settings' => array(
							'button_label' => 'Give Now',
							'button_color' => '#0369a1',
							'url' => '',
							'width' => 100,
							'align' => 'center',
						),
					),
					4 => array(
						'id' => 'el_donors',
						'type' => 'donors_wall',
						'settings' => array(
							'headline' => 'People giving right now',
							'layout' => 'grid',
							'columns' => 2,
							'number_to_show' => 8,
							'order_by' => 'date',
							'order' => 'desc',
							'hide_if_empty' => true,
							'show_summary' => true,
							'show_name' => true,
							'show_amount' => true,
							'show_avatar' => true,
							'show_date' => true,
							'accent_color' => '#0369a1',
							'width' => 100,
							'align' => 'left',
						),
					),
					5 => array(
						'id' => 'el_share',
						'type' => 'social_sharing',
						'settings' => array(
							'headline' => 'Share this campaign',
							'facebook' => true,
							'twitter' => true,
							'linkedin' => true,
							'threads' => true,
							'pinterest' => false,
							'mastodon' => false,
							'bluesky' => false,
							'align' => 'left',
						),
					),
					6 => array(
						'id' => 'el_links',
						'type' => 'social_links',
						'settings' => array(
							'headline' => 'Follow our work',
							'align' => 'left',
							'facebook' => 'https://facebook.com/',
							'twitter' => 'https://twitter.com/',
							'instagram' => 'https://instagram.com/',
						),
					),
				),
			),
		),
	),
	'pro-nonprofit-landing-v2' => array(
		'layout' => '1-column',
		'columns' => array(
			0 => array(
				'id' => 'main',
				'label' => 'Landing Page',
				'width' => '100%',
				'elements' => array(
					0 => array(
						'id' => 'el_title',
						'type' => 'campaign_title',
						'settings' => array(
							'align' => 'center',
							'font_size' => 48,
							'font_weight' => '800',
							'letter_spacing' => -1.5,
							'line_height' => 1.04,
							'color' => '#10182f',
						),
					),
					1 => array(
						'id' => 'el_lede',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => '',
							'content' => 'Climate work is unglamorous and slow: surveys, planting, retrofits, and the long argument with whoever owns the land. It is also the only kind still working a decade later.',
							'title_font_size' => 26,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.5,
							'title_color' => '#10182f',
							'font_size' => 20,
							'line_height' => 1.7,
							'color' => '#47526b',
							'align' => 'center',
						),
					),
					2 => array(
						'id' => 'el_hero',
						'type' => 'photo',
						'settings' => array(
							'src' => '@bp-assets/img/campaign/templates/environmental/Climate-Action-Campaign.webp',
							'alt' => 'A lush green landscape representing a healthier climate',
							'width' => 100,
							'align' => 'center',
						),
					),
					3 => array(
						'id' => 'el_progress',
						'type' => 'progress_bar',
						'settings' => array(
							'headline' => '',
							'show_donated' => true,
							'show_goal' => true,
							'round_amounts' => true,
							'donate_label' => 'Raised:',
							'goal_label' => 'Campaign goal:',
							'width' => 100,
							'align' => 'center',
						),
					),
					4 => array(
						'id' => 'el_summary',
						'type' => 'campaign_summary',
						'settings' => array(
							'headline' => '',
							'show_raised' => true,
							'show_donors' => true,
							'show_percent' => true,
							'show_days' => true,
							'width' => 100,
							'align' => 'center',
						),
					),
					5 => array(
						'id' => 'el_video',
						'type' => 'video',
						'settings' => array(
							'url' => '',
							'aspect_ratio' => '16-9',
							'controls' => true,
							'autoplay' => false,
							'width' => 100,
							'align' => 'center',
						),
					),
					6 => array(
						'id' => 'el_story',
						'type' => 'campaign_description',
						'settings' => array(
							'headline' => 'What your donation funds',
							'content' => 'Tree and hedgerow planting on ground that has been cleared. Insulation and heating upgrades for the households paying the most to stay warm. Soil and water monitoring, so restoration can be measured rather than claimed. And the staff time that keeps all of it going past the first season, which is where most of this work quietly stops.',
							'title_font_size' => 26,
							'title_font_weight' => '700',
							'title_letter_spacing' => -0.5,
							'title_color' => '#10182f',
							'font_size' => 18,
							'line_height' => 1.7,
							'color' => '#47526b',
						),
					),
					7 => array(
						'id' => 'el_amounts',
						'type' => 'donate_amount',
						'settings' => array(
							'headline' => 'Pick an amount',
						),
					),
					8 => array(
						'id' => 'el_donate_top',
						'type' => 'donation_form',
						'settings' => array(
							'button_label' => 'Donate Now',
							'button_color' => '#1d4ed8',
							'url' => '',
							'width' => 60,
							'align' => 'center',
						),
					),
					9 => array(
						'id' => 'el_donors',
						'type' => 'donors_wall',
						'settings' => array(
							'headline' => 'Recent donors',
							'layout' => 'grid',
							'columns' => 4,
							'number_to_show' => 12,
							'order_by' => 'date',
							'order' => 'desc',
							'hide_if_empty' => true,
							'show_summary' => true,
							'show_name' => true,
							'show_amount' => true,
							'show_avatar' => true,
							'show_date' => true,
							'accent_color' => '#1d4ed8',
							'width' => 100,
							'align' => 'center',
						),
					),
					10 => array(
						'id' => 'el_faq',
						'type' => 'faq',
						'settings' => array(
							'heading' => 'Frequently asked questions',
							'items' => array(
								0 => array(
									'question' => 'Is my donation tax-deductible?',
									'answer' => 'In most cases yes, and your emailed receipt is the record you need. Rules vary by country, so check with your accountant if you are claiming a large gift.',
								),
								1 => array(
									'question' => 'What payment methods can I use?',
									'answer' => 'All major cards, plus the wallets your device supports. Everything is processed by the payment provider — we never see or store your card number.',
								),
								2 => array(
									'question' => 'Can I cancel a monthly donation?',
									'answer' => 'Any time, from the link in your receipt or by emailing us. No phone call, no retention script.',
								),
								3 => array(
									'question' => 'How much goes on running costs?',
									'answer' => 'The split between programme and running costs is published in our annual report, and we would rather show you that than quote a flattering number here. Running an organisation costs something; pretending otherwise is how donors stop trusting charities.',
								),
								4 => array(
									'question' => 'Can I give without appearing on the donor wall?',
									'answer' => 'Yes — choose the anonymous option at checkout and your gift is counted but not named.',
								),
							),
							'style' => 'accordion',
							'icon_style' => 'chevron',
							'single_open' => true,
							'first_open' => true,
							'accent_color' => '#1d4ed8',
							'width' => 100,
							'align' => 'left',
						),
					),
					11 => array(
						'id' => 'el_organizer',
						'type' => 'organizer',
						'settings' => array(
							'creator_user_id' => 0,
							'role_title' => 'Executive Director',
							'description' => 'I read every message sent through this page. If something here does not convince you, tell me why — that feedback is worth more than the donation.',
							'width' => 100,
							'align' => 'left',
						),
					),
					12 => array(
						'id' => 'el_donate_bottom',
						'type' => 'donation_form',
						'settings' => array(
							'button_label' => 'Give Today',
							'button_color' => '#1d4ed8',
							'url' => '',
							'width' => 60,
							'align' => 'center',
						),
					),
					13 => array(
						'id' => 'el_share',
						'type' => 'social_sharing',
						'settings' => array(
							'headline' => 'Share this page',
							'facebook' => true,
							'twitter' => true,
							'linkedin' => true,
							'threads' => true,
							'pinterest' => false,
							'mastodon' => false,
							'bluesky' => false,
							'align' => 'center',
						),
					),
					14 => array(
						'id' => 'el_links',
						'type' => 'social_links',
						'settings' => array(
							'headline' => 'Follow us',
							'align' => 'center',
							'facebook' => 'https://facebook.com/',
							'twitter' => 'https://twitter.com/',
							'instagram' => 'https://instagram.com/',
							'linkedin' => 'https://linkedin.com/',
						),
					),
				),
			),
		),
	),
);
