<?php

namespace Better_Payment\Lite\AI\Schema;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Per-widget content direction handed to the model alongside the element schema.
 *
 * The schema tells the model which *keys* an element accepts. It says nothing
 * about what belongs *in* them, and a bare key list is not self-describing: a
 * `headline` on `progress_bar` is a one-line status ("Nearly there"), a
 * `headline` on `campaign_description` is a section heading, and a `headline` on
 * `social_sharing` is a call to share. Given only the word "headline" three
 * times, a model writes the same sentence three times — which is exactly how
 * generated campaigns ended up reading as one undifferentiated blob of copy
 * with the campaign title restated in four places.
 *
 * So each element type gets a short directive describing what its content is
 * *for*. This is prompt text, not validation — {@see OperationValidator} remains
 * the boundary that decides what is actually allowed through.
 *
 * Two of these guides are load-bearing rather than stylistic, and must not be
 * softened into generic writing advice:
 *
 *  - **`social_links` must never invent a URL.** A plausible-looking
 *    `facebook.com/<something>` is not a harmless placeholder; it is a link from
 *    a fundraising page to an account the campaign does not control, and it may
 *    well belong to a real unrelated person.
 *  - **`donors_wall` must never contain donor names.** It renders live
 *    transaction data. A model writing example donors into it would be
 *    fabricating a record of who gave money — the same reason Lite's
 *    {@see \Better_Payment\Lite\Campaign\Elements\ProElementPreview} keeps its
 *    sample donors obviously fictional and builder-only.
 *
 * Keyed by element type, so an element with no entry simply gets no guidance
 * line (third-party elements registered via `better_payment/campaign_elements`
 * are described by their schema alone).
 *
 * @see PromptBuilder::describe_schema() Renders these under each element.
 * @see CampaignSchema::for_prompt()     Attaches them to the schema payload.
 */
class ElementContentGuide {

    /**
     * Element type => one-paragraph content directive.
     *
     * Kept to a couple of sentences each: this ships on every request, and a
     * guide long enough to crowd out the user's own brief defeats the purpose.
     *
     * @return array<string, string>
     */
    private static function guides(): array {
        $guides = [
            'campaign_title' => __( 'A 4–10 word headline naming who or what the money helps and why it is urgent. Specific, not a category ("Get Amara Back on Her Feet", never "Medical Fundraiser"). No trailing period, no organisation name on its own, and never the same wording you use anywhere else on the page.', 'better-payment' ),

            'campaign_description' => __( 'The story, and the only long-form copy on the page: 2–4 short paragraphs in `content`. Open on the person or situation, give concrete verifiable detail (what happened, what the money buys, what changes if it is funded), and close on what a donation does. Use `headline` only as a section heading for the story ("Amara\'s Story") — never a restatement of the campaign title.', 'better-payment' ),

            'photo' => __( 'Write `alt` only, and write it as a real description of the scene the image shows for a reader who cannot see it. Never set `src` — the system supplies the actual image and any URL you invent is a broken link.', 'better-payment' ),

            'progress_bar' => __( 'A live figure widget: it renders the real raised/goal numbers itself. `headline` is an optional one-line status in words, not numbers ("Nearly there" / "Every donation moves this bar"). `goal_label` and `donate_label` are short prefixes the renderer appends the amount to — write "Our goal:" and stop.', 'better-payment' ),

            'campaign_summary' => __( 'A compact live stats block (raised, donors, percent, days left) — the numbers come from the campaign, never from you. `headline` names the block in 2–4 words ("Where we stand"). Turn off any stat the campaign cannot support: `show_days` on a campaign with no end date shows nothing useful.', 'better-payment' ),

            'donation_form' => __( 'The primary call to action. `button_label` is an imperative of 2–4 words, specific to the cause where it can be ("Fund Amara\'s Surgery", "Give Shelter Tonight") rather than a bare "Submit". An amount inside the button text is fine here — unlike a `*_label` key, this one is echoed on its own.', 'better-payment' ),

            'organizer' => __( 'Who is behind the campaign, for trust. `role_title` is the role or relationship ("Amara\'s sister", "Shelter Director"), never a personal name — the name comes from the linked WordPress user. `description` is 1–2 sentences of credibility in the organiser\'s own first-person voice.', 'better-payment' ),

            'donate_amount' => __( 'The giving tiers. `headline` invites a choice in a few words ("Choose your impact"). The tiers themselves go in the separate `set_donation_amounts` operation, where each amount\'s description states what that specific sum concretely does ("Feeds one family for a week") — an impact, never a restatement of the number.', 'better-payment' ),

            'social_sharing' => __( 'Share buttons for this page. `headline` is a short ask ("Share Amara\'s story"). The network toggles only decide which buttons appear — the links are generated from the campaign URL, so there is nothing for you to write into them.', 'better-payment' ),

            'social_links' => __( 'Links to the organiser\'s OWN existing profiles. Set a network\'s URL ONLY if the brief gives you that exact address; otherwise leave every URL unset and let the element stay empty. Never construct a plausible handle — an invented profile link points a fundraising page at an account nobody involved controls.', 'better-payment' ),

            // --- Pro elements. Described only when Pro is active; see CampaignSchema::offerable_elements().
            'donors_wall' => __( 'A live list of real donors, rendered from actual transactions — you write no donor names, amounts or dates, and there is no setting to put them in. Your job is `headline` ("Thank you to our supporters") and the display options: `layout`, how many to show, and which columns are visible.', 'better-payment' ),

            'faq' => __( 'Answers to what a hesitant donor of THIS campaign would actually ask before giving. Write 3–5 items in `items`, each a real question in the donor\'s voice with a direct 1–3 sentence answer — how the money is used, what happens if the goal is not met, whether the payment is secure, when the help arrives. Never generic filler, and never a question the page already answers.', 'better-payment' ),

            'video' => __( 'An embedded campaign video. Set `url` ONLY if the brief contains the actual video address; a video ID you invent resolves to an unrelated stranger\'s video on the fundraising page. With no URL in the brief, leave `url` out entirely and set only the display options.', 'better-payment' ),
        ];

        /**
         * Filter the per-element content guidance sent to the model.
         *
         * @param array<string, string> $guides Element type => directive.
         */
        return apply_filters( 'better_payment/ai/element_content_guides', $guides );
    }

    /**
     * The directive for one element type, or '' when it has none.
     */
    public static function for_type( string $type ): string {
        $guides = self::guides();

        return isset( $guides[ $type ] ) ? (string) $guides[ $type ] : '';
    }

    /**
     * Every element type that carries a directive.
     *
     * @return array<int, string>
     */
    public static function types(): array {
        return array_keys( self::guides() );
    }
}
