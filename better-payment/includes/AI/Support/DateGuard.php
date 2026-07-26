<?php

namespace Better_Payment\Lite\AI\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Campaign date normalisation, and the one rule that must hold everywhere:
 * **an AI-produced campaign end date is always in the future.**
 *
 * A model has no clock. Asked for an end date it answers from its training
 * distribution, and the dates in that distribution are historical — so a campaign
 * generated today acquired a deadline in 2024, opened having already closed,
 * showed zero days remaining and no progress bar, and quietly told every visitor
 * the appeal was over. It is not a cosmetic bug: it is a fundraising page that
 * refuses donations on its first day.
 *
 * This lived only in {@see \Better_Payment\Lite\AI\Services\UserFieldGuard}, and
 * only on one of its two branches — the case where the client had NOT declared
 * the field. Four routes therefore reached storage unchecked:
 *
 *  1. **Conversational edit** (`/ai/chat` → `CampaignEditor`) — never ran the
 *     guard at all, so "give it a deadline" wrote whatever the model said.
 *  2. **Analyze → Apply** — same; the suggestion was validated but never dated.
 *  3. **Generate with a declared value** — the model's op was dropped and the
 *     declared value re-asserted, unchecked.
 *  4. **Regenerate** — pins the campaign's *current* meta as declared fields
 *     (`useConversation.js`), so a campaign that had already acquired a past date
 *     carried it verbatim through every subsequent redesign.
 *
 * Hence a shared helper called from the **validator** (which every model-produced
 * operation passes through, whatever the mode) as well as the field guard. The
 * prompt asks for the same thing in `rules.php`; a prompt is a request, and this
 * is the part that holds.
 *
 * Note this constrains **AI output only**. A human editing the Settings panel by
 * hand may still set any date — closing a campaign retroactively is a legitimate
 * thing for an owner to do, and it is not what this class is about.
 */
class DateGuard {

    /**
     * Coerce a date to `Y-m-d`, or '' when it is not a real date.
     *
     * The wizard always sends `Y-m-d`; a model asked for an end date may answer
     * in prose ("31 December 2026"). Both settle here so a stored end date is
     * always in the one format {@see \Better_Payment\Lite\Campaign\CampaignStats}
     * and the renderer parse.
     *
     * @param string $value
     * @return string
     */
    public static function to_iso( string $value ): string {
        $value = trim( $value );
        if ( '' === $value ) {
            return '';
        }

        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
            return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
        }

        $timestamp = strtotime( $value );

        return false !== $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '';
    }

    /**
     * Whether an ISO date has already elapsed in the site's timezone.
     *
     * Compared against the **end** of that day, so "today" is not past — a
     * campaign may legitimately close today, and treating today as elapsed would
     * reject the one same-day deadline a user might deliberately set.
     *
     * An unparseable value returns false: "not a date" is not "a past date", and
     * the callers drop those separately. Answering true here would conflate the
     * two and make a malformed value look like a deliberate backdate.
     *
     * @param string $iso
     * @return bool
     */
    public static function is_past( string $iso ): bool {
        $iso = self::to_iso( $iso );
        if ( '' === $iso ) {
            return false;
        }

        return strtotime( $iso . ' 23:59:59' ) < (int) current_time( 'timestamp' );
    }

    /**
     * Normalise a model-supplied end date, or return '' if it may not be stored.
     *
     * The single decision point: a value survives only when it parses AND lies in
     * the future. Callers treat '' as "drop the operation", which leaves the field
     * unset — a valid, intentional state that renders as no deadline, rather than
     * a wrong one that renders as an expired campaign.
     *
     * @param mixed $value Raw value from an operation or a declared field.
     * @return string `Y-m-d`, or '' when unusable.
     */
    public static function usable_end_date( $value ): string {
        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $iso = self::to_iso( (string) $value );

        return ( '' === $iso || self::is_past( $iso ) ) ? '' : $iso;
    }
}
