<?php

namespace Better_Payment\Lite\Campaign\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Amount formatting for everything a campaign puts on screen.
 *
 * One rule, applied everywhere: **decimals appear only when there is a fraction
 * to show.** A goal of 10000 reads "10,000", a raised total of 3750 reads
 * "3,750", and 3750.50 still reads "3,750.50". A column of ".00" says nothing
 * except that the site is running software that cannot tell the difference.
 *
 * It lives here rather than at each call site because there were six of them
 * across two plugins — the progress goal, the summary total, the minimum-donation
 * notice, the amount chips, the campaign-list column and Pro's donors wall — and
 * they already disagreed: some used `number_format`, Pro used `number_format_i18n`,
 * and the amount chips used bare float interpolation, which printed "€10.5" for
 * ten pounds fifty and "€1000" with no separator at all.
 */
class Money {

    /**
     * Format an amount for display, dropping decimals that are all zero.
     *
     * @param mixed $amount   Numeric amount.
     * @param int   $decimals Decimals to show when a fraction is present.
     * @return string
     */
    public static function format( $amount, int $decimals = 2 ): string {
        $amount = (float) $amount;

        if ( $decimals < 0 ) {
            $decimals = 0;
        }

        // Decide on the ROUNDED value, not the raw one: 3750.004 displays as
        // "3,750.00" at two decimals, which is exactly the string this exists to
        // avoid, so it must count as having no fraction.
        $rounded = round( $amount, $decimals );

        if ( abs( $rounded - floor( $rounded ) ) < 0.0000001 ) {
            $decimals = 0;
        }

        // number_format_i18n applies the site's separators; the plain fallback
        // keeps this callable from a unit test with no WordPress loaded.
        return function_exists( 'number_format_i18n' )
            ? number_format_i18n( $amount, $decimals )
            : number_format( $amount, $decimals );
    }

    /**
     * Format an amount with its currency symbol in front.
     *
     * @param string $symbol   Currency symbol, already resolved.
     * @param mixed  $amount   Numeric amount.
     * @param int    $decimals Decimals to show when a fraction is present.
     * @return string
     */
    public static function with_symbol( string $symbol, $amount, int $decimals = 2 ): string {
        return $symbol . self::format( $amount, $decimals );
    }

    /**
     * The symbol campaign surfaces print for a currency code; the code itself when unknown.
     *
     * @param string $code ISO currency code.
     * @return string
     */
    public static function symbol( string $code ): string {
        $map = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥',
            'CAD' => 'CA$', 'AUD' => 'A$', 'INR' => '₹', 'BRL' => 'R$',
            'MXN' => 'MX$', 'SGD' => 'S$', 'CHF' => 'CHF', 'SEK' => 'kr',
            'NOK' => 'kr', 'DKK' => 'kr', 'NZD' => 'NZ$', 'ZAR' => 'R',
            'BDT' => '৳', 'PKR' => '₨', 'NGN' => '₦', 'KES' => 'KSh',
        ];

        return $map[ strtoupper( $code ) ] ?? $code;
    }

    /**
     * Each currency's amount with its own symbol, joined for one line of text.
     *
     * Used where a campaign raised money in more than one currency: the amounts are listed
     * side by side, never added. A symbol that is only the code itself gets a space after it
     * ("CHF 20"), so the code and the digits do not run together.
     *
     * @param array<string, float> $by_currency Code => amount.
     * @param string               $separator   Text between two amounts.
     * @return string
     */
    public static function list_by_currency( array $by_currency, string $separator = ' + ' ): string {
        $parts = [];
        foreach ( $by_currency as $code => $amount ) {
            $symbol  = self::symbol( (string) $code );
            $symbol .= strtoupper( $symbol ) === strtoupper( (string) $code ) ? ' ' : '';
            $parts[] = self::with_symbol( $symbol, $amount );
        }

        return implode( $separator, $parts );
    }
}
