<?php

namespace Better_Payment\Lite\AI\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Campaign brief writer / refiner — the wizard's pre-generation step.
 *
 * Runs {@see AIService} in the text-only 'brief' mode. Given the brief the Smart
 * Prompt Wizard assembled (and, optionally, a plain-language instruction), it
 * returns an improved brief *as text* — nothing is applied to any campaign. The
 * user reviews and edits the result, then hands it to the 'generate' turn.
 *
 * This class exists so the wizard can offer "Refine with AI" without the generate
 * pipeline: no operations, no layout, no guards to run — just prose in, prose out.
 * The one guarantee it adds on top of the raw turn is that the reply is cleaned of
 * any machine wrapping (stray quotes, code fences) before it reaches the editable
 * brief box, since the model is told to emit bare prose but a prompt is a request.
 *
 * @see \Better_Payment\Lite\AI\Prompt\Templates (brief.php) for the hard rules —
 *      chiefly that it never invents or alters a goal amount or an end date, which
 *      are owned by the wizard's structured answers and enforced at generation.
 */
class BriefWriter {

    /**
     * Improve a campaign brief.
     *
     * @param string $brief       The current brief text (the wizard's assembled
     *                            brief, or a previously refined one).
     * @param string $instruction Optional plain-language "make it more…" request.
     *                            '' means a general polish.
     * @return array|\WP_Error { brief: string, usage: array }
     */
    public static function write( string $brief, string $instruction = '' ) {
        $brief = trim( $brief );
        if ( '' === $brief ) {
            return new \WP_Error(
                'ai_brief_missing',
                __( 'There is no brief to refine yet.', 'better-payment' ),
                [ 'status' => 400 ]
            );
        }

        $result = AIService::run( 'brief', self::compose_message( $brief, trim( $instruction ) ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $text = self::clean( (string) ( $result['assistant_message'] ?? '' ) );
        if ( '' === $text ) {
            return new \WP_Error(
                'ai_brief_empty',
                __( 'The assistant did not return an improved brief. Please try again.', 'better-payment' ),
                [ 'status' => 502 ]
            );
        }

        return [
            'brief' => $text,
            'usage' => $result['usage'] ?? [],
        ];
    }

    /**
     * Build the user message: the current brief, plus the improvement instruction
     * when there is one. Labelled plainly so the model knows which part is the
     * brief to rewrite and which is the direction to follow.
     *
     * @param string $brief
     * @param string $instruction
     * @return string
     */
    private static function compose_message( string $brief, string $instruction ): string {
        if ( '' === $instruction ) {
            return "Current brief:\n\n" . $brief
                . "\n\nRewrite this brief so it is more vivid, specific and complete, following the rules above.";
        }

        return "Current brief:\n\n" . $brief
            . "\n\nImprove the brief following this instruction: " . $instruction;
    }

    /**
     * Strip machine wrapping from a reply meant to be bare prose.
     *
     * The model is told to return the brief and nothing else, but occasionally
     * wraps it in a code fence or surrounding quotes. Remove those so they don't
     * land in the user's editable brief box. This unwraps structure; it does not
     * rewrite the writing.
     *
     * @param string $text
     * @return string
     */
    private static function clean( string $text ): string {
        $text = trim( $text );

        // A fully fenced reply → keep only the fence's contents.
        if ( preg_match( '/^```[a-z]*\s*\n(.*?)\n?```$/is', $text, $matches ) ) {
            $text = trim( $matches[1] );
        }

        // Surrounding matched quotes the model sometimes adds around the whole brief.
        if ( strlen( $text ) >= 2 ) {
            $first = $text[0];
            $last  = $text[ strlen( $text ) - 1 ];
            if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
                $text = trim( substr( $text, 1, -1 ) );
            }
        }

        return $text;
    }
}
