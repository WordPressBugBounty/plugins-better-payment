<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Campaign\CampaignCurrency;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Money totals that never add one currency to another.
 *
 * Every transaction row stores its own `currency`. A total over many rows is therefore a set of
 * totals, one per currency: 20 USD and 15 ZAR are "$20.00 + R15.00", never "35". This class holds
 * the one rule every report follows:
 *
 * - Sums are kept per currency code (upper case, trimmed). A blank code is a row stored before the
 *   column was filled, and reads as the fallback currency the caller passes (the site currency).
 * - One currency is the **primary** one. It is printed as the headline figure and is what the
 *   older single-number keys (`total_transactions_amount`, …) report.
 * - Every other currency is listed beside it, largest first, only when it holds a non-zero sum.
 *
 * Campaign\CampaignStats groups a campaign's donations the same way (`raised_by_currency`), with
 * the campaign's own currency always primary.
 *
 * Every method that takes a `code => number` array reads its keys through by_code(): a code is
 * matched in any letter case and with spaces around it, and two spellings of one code ('usd',
 * 'USD') are one currency. A caller never has to normalise its keys first, and a figure can never
 * read 0 only because its key was lower case.
 *
 * @since 2.5.1
 */
class CurrencyTotals {

	/**
	 * The site's general currency setting, upper case; USD when unset.
	 *
	 * @return string
	 */
	public static function site(): string {
		return CampaignCurrency::site();
	}

	/**
	 * A row's currency code, normalised; the fallback when the row has none.
	 *
	 * @param mixed  $code     Stored code.
	 * @param string $fallback Code a blank one reads as.
	 * @return string
	 */
	public static function code( $code, string $fallback ): string {
		$code = strtoupper( trim( is_scalar( $code ) ? (string) $code : '' ) );

		return '' === $code ? strtoupper( trim( $fallback ) ) : $code;
	}

	/**
	 * A `code => number` array with every key normalised (upper case, trimmed).
	 *
	 * Two spellings of one code are added together, in the place of the first one. A blank code
	 * names no currency and is dropped: unlike code(), there is no fallback to read it as here.
	 *
	 * @param array $values Code => number.
	 * @return array<string, float>
	 */
	public static function by_code( array $values ): array {
		$by_code = [];

		foreach ( $values as $code => $value ) {
			$code = strtoupper( trim( (string) $code ) );
			if ( '' === $code ) {
				continue;
			}

			$by_code[ $code ] = ( $by_code[ $code ] ?? 0.0 ) + ( is_scalar( $value ) ? (float) $value : 0.0 );
		}

		return $by_code;
	}

	/**
	 * The currency a set of rows is reported in.
	 *
	 * The preferred currency (normally the site's) when at least one row is in it. Otherwise the
	 * currency holding the largest sum, so a payer who only ever paid in euros on a dollar site
	 * sees "€50.00", not "$0.00 + €50.00". With no rows at all it is the preferred currency.
	 *
	 * @param array<string, int>   $counts    Code => number of rows.
	 * @param array<string, float> $sums      Code => sum.
	 * @param string               $preferred Preferred code.
	 * @return string
	 */
	public static function choose_primary( array $counts, array $sums, string $preferred ): string {
		$preferred = strtoupper( trim( $preferred ) );
		$counts    = self::by_code( $counts );
		$sums      = self::by_code( $sums );

		if ( ! empty( $counts[ $preferred ] ) || empty( $counts ) ) {
			return $preferred;
		}

		$best     = '';
		$best_sum = null;
		foreach ( $counts as $code => $count ) {
			if ( $count <= 0 ) {
				continue;
			}
			$sum = $sums[ $code ] ?? 0.0;
			// Ties go to the alphabetically first code, so the choice never depends on row order.
			if ( null === $best_sum || $sum > $best_sum || ( $sum === $best_sum && strcmp( $code, $best ) < 0 ) ) {
				$best     = $code;
				$best_sum = $sum;
			}
		}

		return '' === $best ? $preferred : $best;
	}

	/**
	 * Per-currency sums in report order: the primary currency first (even at 0), then every other
	 * currency with a non-zero sum, largest first. Each sum is rounded to cents.
	 *
	 * @param array<string, float> $sums    Code => sum.
	 * @param string               $primary Primary code.
	 * @return array<string, float>
	 */
	public static function order( array $sums, string $primary ): array {
		$primary = strtoupper( trim( $primary ) );
		$sums    = self::by_code( $sums );
		$others  = [];

		// The primary sum is read from the normalised keys too. It used to be read from the
		// caller's own array, so sums keyed 'usd' reported a primary 'USD' of 0.
		foreach ( $sums as $code => $sum ) {
			$sum = round( $sum, 2 );
			if ( $code !== $primary && 0.0 !== $sum ) {
				$others[ $code ] = $sum;
			}
		}

		// Largest first; equal sums keep a stable, alphabetical order.
		uksort(
			$others,
			static function ( $a, $b ) use ( $others ) {
				return $others[ $b ] <=> $others[ $a ] ?: strcmp( (string) $a, (string) $b );
			}
		);

		return [ $primary => round( $sums[ $primary ] ?? 0.0, 2 ) ] + $others;
	}

	/**
	 * The currencies in a report other than its primary one, under their normalised codes.
	 *
	 * @param array<string, float> $by_currency Code => sum, as order() returns it.
	 * @param string               $primary     Primary code.
	 * @return array<string, float>
	 */
	public static function others( array $by_currency, string $primary ): array {
		$primary = strtoupper( trim( $primary ) );
		$others  = [];

		foreach ( self::by_code( $by_currency ) as $code => $sum ) {
			if ( $code !== $primary && 0.0 !== round( $sum, 2 ) ) {
				$others[ $code ] = $sum;
			}
		}

		return $others;
	}

	/**
	 * The symbol Better Payment prints for a currency; the code itself when it has none.
	 *
	 * @param string $code ISO code.
	 * @return string
	 */
	public static function symbol( string $code ): string {
		$code = strtoupper( trim( $code ) );

		return ( new Helper() )->get_currency_symbol( $code );
	}

	/**
	 * One amount with its currency's symbol, always with two decimals (the analytics screens'
	 * ledger style). A symbol that is only the code itself gets a space ("CHF 20.00").
	 *
	 * @param string $code   ISO code.
	 * @param mixed  $amount Amount.
	 * @return string
	 */
	public static function format( string $code, $amount ): string {
		$symbol = self::symbol( $code );
		$space  = strtoupper( $symbol ) === strtoupper( trim( $code ) ) ? ' ' : '';

		return $symbol . $space . number_format( (float) $amount, 2 );
	}

	/**
	 * Several amounts on one line, each with its own symbol: "€2.50 + R15.00".
	 *
	 * @param array<string, float> $by_currency Code => amount.
	 * @param string               $separator   Text between two amounts.
	 * @return string
	 */
	public static function format_list( array $by_currency, string $separator = ' + ' ): string {
		$parts = [];
		foreach ( $by_currency as $code => $amount ) {
			$parts[] = self::format( (string) $code, $amount );
		}

		return implode( $separator, $parts );
	}

	/**
	 * The currencies beside a primary one, on one line: "R15.00 + €2.50".
	 *
	 * A symbol shared with the primary currency, or with another listed currency, cannot say
	 * which currency it is: CAD prints "$" exactly like USD, so "$380.00 + $196.00" reads as two
	 * dollar amounts. Such a currency prints its code instead ("CAD 196.00").
	 *
	 * @param array<string, float> $by_currency Code => amount, the primary included or not.
	 * @param string               $primary     Primary code.
	 * @param string               $separator   Text between two amounts.
	 * @return string
	 */
	public static function format_others( array $by_currency, string $primary, string $separator = ' + ' ): string {
		$primary = strtoupper( trim( $primary ) );
		$others  = self::others( $by_currency, $primary );

		$uses = [ self::symbol( $primary ) => 1 ];
		foreach ( array_keys( $others ) as $code ) {
			$symbol          = self::symbol( (string) $code );
			$uses[ $symbol ] = ( $uses[ $symbol ] ?? 0 ) + 1;
		}

		$parts = [];
		foreach ( $others as $code => $amount ) {
			$code    = (string) $code;
			$parts[] = $uses[ self::symbol( $code ) ] > 1
				? $code . ' ' . number_format( (float) $amount, 2 )
				: self::format( $code, $amount );
		}

		return implode( $separator, $parts );
	}

	/**
	 * Display strings for an analytics array (Admin\DB::get_user_analytics_by_email() shape).
	 *
	 * For each bucket (total, completed, incomplete, refunded): `primary` is the primary
	 * currency's amount with its symbol, `others` the other currencies on one line ('' when none).
	 * The User Dashboard widget template and the block's editor preview both print these, so the
	 * two cannot disagree. An array without `*_by_currency` keys (an older caller) is read as one
	 * currency.
	 *
	 * @param array $analytics Analytics array.
	 * @return array<string, array{primary: string, others: string}>
	 */
	public static function format_analytics( array $analytics ): array {
		$primary = isset( $analytics['currency'] ) && is_scalar( $analytics['currency'] ) && '' !== trim( (string) $analytics['currency'] )
			? strtoupper( trim( (string) $analytics['currency'] ) )
			: self::site();
		$result  = [];

		foreach ( [ 'total', 'completed', 'incomplete', 'refunded' ] as $bucket ) {
			$key         = $bucket . '_transactions_amount_by_currency';
			$by_currency = isset( $analytics[ $key ] ) && is_array( $analytics[ $key ] )
				? self::by_code( $analytics[ $key ] )
				: [ $primary => (float) ( $analytics[ $bucket . '_transactions_amount' ] ?? 0 ) ];

			$result[ $bucket ] = [
				'primary' => self::format( $primary, $by_currency[ $primary ] ?? 0 ),
				'others'  => self::format_others( $by_currency, $primary ),
			];
		}

		return $result;
	}

	/**
	 * Group loaded rows' amounts per currency.
	 *
	 * @param iterable $rows     Rows (objects or arrays) with `currency` and an amount.
	 * @param string   $fallback Code a blank currency reads as.
	 * @param callable $amount   Row => amount it contributes.
	 * @return array{counts: array<string, int>, sums: array<string, float>}
	 */
	public static function group_rows( $rows, string $fallback, callable $amount ): array {
		$counts = [];
		$sums   = [];

		foreach ( $rows as $row ) {
			$currency = is_array( $row ) ? ( $row['currency'] ?? '' ) : ( $row->currency ?? '' );
			$code     = self::code( $currency, $fallback );

			$counts[ $code ] = ( $counts[ $code ] ?? 0 ) + 1;
			$sums[ $code ]   = ( $sums[ $code ] ?? 0.0 ) + (float) $amount( $row );
		}

		return [
			'counts' => $counts,
			'sums'   => $sums,
		];
	}
}
