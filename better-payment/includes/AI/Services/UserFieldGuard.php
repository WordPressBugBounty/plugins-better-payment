<?php

namespace Better_Payment\Lite\AI\Services;

use Better_Payment\Lite\AI\Support\DateGuard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enforces the user's own answers over anything the model produced.
 *
 * The Smart Prompt Wizard's optional questions ("How much do you need to raise?",
 * "When should it end?") map onto campaign meta keys. Leaving one blank means
 * "no preference" — `buildBrief()` omits it from the brief entirely — but a brief
 * that says nothing is not the same as an instruction to say nothing: models
 * routinely fill the gap with a plausible-looking goal figure or end date, and
 * without today's date to anchor on, that date frequently lands in the past.
 *
 * Prompt wording alone cannot guarantee this (see Prompt/Templates/generate.php,
 * which now asks for the same behaviour). This class is the code-level guarantee:
 * the wizard declares which fields the user actually filled in, and every
 * `update_meta` operation for a declared field is replaced with the user's own
 * value — or dropped outright when they left it blank. An empty field therefore
 * stays empty in the generated campaign, and the campaign renders as though the
 * field was never provided (`days_remaining` null, no progress percentage).
 *
 * Scope is deliberately narrow:
 *  - Only the generation path calls this ({@see CampaignGenerator}). Conversational
 *    edits are direct instructions ("end it on 1 March"), so nothing is enforced there.
 *  - A key the client did not declare at all — the free-form prompt path, where
 *    the user's intent lives in prose we cannot parse — keeps the model's value.
 *    Only the past-date check still applies, since a backdated end date on a
 *    brand-new campaign is never what anyone asked for.
 *
 * @see CampaignGenerator::generate()
 */
class UserFieldGuard {

    /**
     * Campaign meta keys that mirror an optional user-facing field.
     *
     * A key listed here is owned by the user: when the client declares it, the
     * model may not set it to anything else.
     *
     * @return array<int, string>
     */
    public static function guarded_keys(): array {
        $keys = [ 'bpc_goal_amount', 'bpc_end_date' ];

        /**
         * Filter the campaign meta keys whose value the user owns outright.
         *
         * @param array<int, string> $keys
         */
        return apply_filters( 'better_payment/ai/user_guarded_meta_keys', $keys );
    }

    /**
     * Normalise the raw `fields` payload from the client.
     *
     * A key present with an empty value is meaningful — it says "the user was
     * asked and chose to leave this blank", which is what makes the difference
     * between enforcing emptiness and staying out of the way. A key that is
     * absent entirely is left absent.
     *
     * Anything unusable (a non-numeric goal, a malformed date) normalises to the
     * empty string rather than being dropped: the user was still asked, so the
     * field is still theirs — it simply has no value.
     *
     * @param mixed $raw
     * @return array<string, string> Declared keys only, values normalised.
     */
    public static function sanitize( $raw ): array {
        if ( ! is_array( $raw ) ) {
            return [];
        }

        $clean = [];
        foreach ( self::guarded_keys() as $key ) {
            if ( ! array_key_exists( $key, $raw ) ) {
                continue;
            }
            $clean[ $key ] = self::normalize( $key, $raw[ $key ] );
        }

        return $clean;
    }

    /**
     * Apply the user's declared fields to a batch of model operations.
     *
     * Runs before the caller derives `meta` from the operations, so the returned
     * operations and that derived meta can never disagree.
     *
     * @param array $operations Validated operations from the model.
     * @param mixed $fields     The client's declared fields (raw or sanitised).
     * @return array
     */
    public static function apply( array $operations, $fields ): array {
        $declared = self::sanitize( $fields );

        $kept = [];
        foreach ( $operations as $operation ) {
            if ( ! is_array( $operation ) || 'update_meta' !== ( $operation['op'] ?? '' ) ) {
                $kept[] = $operation;
                continue;
            }

            $key = (string) ( $operation['key'] ?? '' );

            // The user owns this field. Drop the model's take on it; their own
            // value (if any) is appended below.
            if ( array_key_exists( $key, $declared ) ) {
                continue;
            }

            if ( 'bpc_end_date' === $key ) {
                $date = self::to_iso_date( (string) ( $operation['value'] ?? '' ) );
                // Unparseable or already elapsed — a generated campaign must not
                // open having already closed.
                if ( '' === $date || self::is_past_date( $date ) ) {
                    continue;
                }
                $operation['value'] = $date;
            }

            $kept[] = $operation;
        }

        // Re-assert the user's own values last, so they win outright.
        //
        // These operations are appended AFTER validation, so they never pass
        // through OperationValidator — the past-date rule has to be repeated
        // here or this is a hole straight to storage. It is not hypothetical:
        // Regenerate pins the campaign's *current* meta as declared fields
        // (`useConversation.js`), so once a campaign had acquired a past end date
        // by any means, every subsequent redesign carried it forward verbatim.
        foreach ( $declared as $key => $value ) {
            if ( '' === $value ) {
                continue; // Left blank on purpose — it stays unset.
            }

            if ( 'bpc_end_date' === $key ) {
                $value = DateGuard::usable_end_date( $value );
                if ( '' === $value ) {
                    continue;
                }
            }

            $kept[] = [
                'op'    => 'update_meta',
                'key'   => $key,
                'value' => 'bpc_goal_amount' === $key ? (float) $value : $value,
            ];
        }

        return $kept;
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Normalise one declared field value to its storable string form, or ''.
     *
     * @param mixed $value
     */
    private static function normalize( string $key, $value ): string {
        if ( null === $value || is_array( $value ) || is_object( $value ) || is_bool( $value ) ) {
            return '';
        }

        $value = trim( (string) $value );
        if ( '' === $value ) {
            return '';
        }

        if ( 'bpc_goal_amount' === $key ) {
            // 0 is not a goal — the renderer only shows progress above zero.
            return is_numeric( $value ) && (float) $value > 0 ? (string) ( 0 + $value ) : '';
        }

        if ( 'bpc_end_date' === $key ) {
            return self::to_iso_date( $value );
        }

        return sanitize_text_field( $value );
    }

    /**
     * Coerce a date to `Y-m-d`, or '' when it is not a real date.
     *
     * Retained as the historical entry point; the implementation moved to
     * {@see DateGuard} when the same rule had to hold in the operation validator
     * too. One implementation, so the two can never disagree about what counts
     * as a date.
     */
    public static function to_iso_date( string $value ): string {
        return DateGuard::to_iso( $value );
    }

    /**
     * Whether an ISO date has already elapsed in the site's timezone.
     *
     * @see DateGuard::is_past()
     */
    public static function is_past_date( string $iso ): bool {
        return DateGuard::is_past( $iso );
    }
}
