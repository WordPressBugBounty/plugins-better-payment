<?php

namespace Better_Payment\Lite\Admin;

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pure status + search + start-date filtering for the admin Subscriptions list.
 *
 * The list's filterable fields are NOT in the database. The
 * `{prefix}better_payment_subscription_order` relation table holds only
 * (subscription_id, order_id, order_item_id, type, source) — status, customer
 * name/email and product name all arrive later, when each row is hydrated by
 * its integration (WooCommerce order meta, or the
 * `better_payment/admin/subscription_list_row` filter). So there is no SQL
 * WHERE clause to push these filters into: a filtered request has to hydrate
 * the whole set, filter the hydrated rows, and paginate the result in PHP.
 * This class is that second half, kept pure (no DB, no WP state) so it can be
 * unit tested without a database — see tests/Unit/Admin/SubscriptionListFilterTest.php.
 *
 * The cost is deliberate and bounded to filtered requests only:
 * AdminAPI::get_subscriptions() keeps its SQL-paginated fast path whenever no
 * filter is active, which is every default pageview. Filtering on the
 * hydrated row (rather than a cheaper second status lookup) is also
 * deliberate: it guarantees the filter agrees with what the row actually
 * DISPLAYS. An integration that reports status only through
 * `subscription_list_row` — and not through the lighter
 * `better_payment/admin/subscription_status` filter the stat cards use —
 * would otherwise be filtered out of a list it visibly belongs in.
 *
 * @since 2.4.0
 */
class SubscriptionListFilter {

    /**
     * The dropdown's "no status filter" sentinel. Sent by the UI as an
     * ordinary value so the request always carries the control's state.
     */
    const STATUS_ALL = 'all';

    /**
     * Longest accepted search term. A search is a substring scan over
     * already-hydrated rows, so an unbounded term costs nothing to run — the
     * cap exists to keep a hostile query string out of the comparison, not
     * for performance.
     */
    const MAX_SEARCH_LENGTH = 200;

    /**
     * Row keys a search term is matched against, in the order the list shows
     * them. `subscription_id` and `source_label` are included on purpose: an
     * un-hydrated row (integration deactivated, order deleted) has no
     * customer or product text at all, and must still be findable by the one
     * identifier it does show.
     *
     * @var string[]
     */
    const SEARCHABLE_KEYS = array(
        'subscription_id',
        'customer_name',
        'customer_email',
        'product_name',
        'source_label',
        'source',
    );

    /**
     * The row key the date range filters on: when the subscription STARTED,
     * which is the column the list shows ("Started") and the subscription's
     * own equivalent of a transaction's `payment_date`. `next_payment` is
     * deliberately not filterable — it is a forward-looking schedule value
     * that is blank on every terminal subscription, so a range over it would
     * silently exclude every cancelled row.
     */
    const DATE_KEY = 'start_date';

    /**
     * Pure: normalize the requested status into a comparable slug.
     *
     * Returns '' for the "all" sentinel, an empty value, or anything that
     * does not sanitize to a usable slug — all of which mean "do not filter
     * by status".
     *
     * Any other slug is accepted as-is, deliberately WITHOUT checking it
     * against a known-status list: `status` comes from each integration's own
     * vocabulary (the bundled WooCommerce module writes active/past_due/
     * cancelled/completed, a third-party source may write others), and
     * silently ignoring an unrecognised slug would answer a narrow filter
     * with the entire unfiltered list. An unknown status matches no row, and
     * an empty list is the honest answer to "show me subscriptions in a
     * status none of them are in".
     *
     * @param mixed $status Raw request value.
     * @return string Lowercase status slug, or '' for no status filter.
     */
    public static function sanitize_status( $status ) {
        $status = sanitize_key( (string) $status );

        if ( '' === $status || self::STATUS_ALL === $status ) {
            return '';
        }

        return $status;
    }

    /**
     * Pure: normalize a search term (trimmed, tag/slash-free, length capped).
     * '' means "do not filter by search".
     *
     * @param mixed $search Raw request value.
     * @return string
     */
    public static function sanitize_search( $search ) {
        $search = sanitize_text_field( (string) $search );
        $search = trim( $search );

        if ( '' === $search ) {
            return '';
        }

        return function_exists( 'mb_substr' )
            ? mb_substr( $search, 0, self::MAX_SEARCH_LENGTH )
            : substr( $search, 0, self::MAX_SEARCH_LENGTH );
    }

    /**
     * Pure: normalize one end of the requested start-date range to `Y-m-d`.
     * '' means "do not bound the range at this end", so a range may be open
     * on either side.
     *
     * A DATE, never a datetime: the control is a day picker and the row's
     * `start_date` carries a time, so both ends are compared on the day part
     * alone and both are INCLUSIVE. Comparing a `Y-m-d H:i:s` row value
     * against a bare `Y-m-d` string — what the Transactions tab's SQL
     * `BETWEEN` does — silently excludes everything after midnight on the
     * `to` day, so "16 Aug to 16 Aug" answers with an empty list.
     *
     * Anything that is not a real calendar date reads as no bound rather
     * than as an impossible one. That is the opposite of how an unknown
     * *status* is treated (kept, matches nothing), and deliberately so: a
     * status slug comes from an integration's own open vocabulary, so an
     * unrecognised one is still a meaningful request. A date has one
     * universal format, so a value that is not one is a broken request, and
     * answering it with a permanently empty table looks like data loss.
     *
     * @param mixed $date Raw request value ('YYYY-MM-DD').
     * @return string `Y-m-d`, or '' for no bound.
     */
    public static function sanitize_date( $date ) {
        // A query string can carry `start_date_from[]=…`, which arrives as an
        // array; casting one to string is a PHP notice, not a date.
        if ( ! is_scalar( $date ) ) {
            return '';
        }

        $date = trim( sanitize_text_field( (string) $date ) );

        if ( '' === $date ) {
            return '';
        }

        if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) {
            return '';
        }

        if ( ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
            return '';
        }

        return $date;
    }

    /**
     * Pure: the day a display row started, as `Y-m-d` for comparison.
     *
     * `start_date` is written as `Y-m-d H:i:s` in site time by every bundled
     * integration, so the day is its first ten characters — no timezone maths,
     * which is what keeps this class pure. A third-party integration may hand
     * back some other format through `better_payment/admin/subscription_list_row`,
     * so anything unrecognised falls back to strtotime() and, failing that, to
     * '' — a row with no usable start date satisfies no date filter, the same
     * reading an un-hydrated row's empty status gets.
     *
     * @param array $row Display row.
     * @return string `Y-m-d`, or '' when the row has no usable start date.
     */
    private static function row_date( $row ) {
        if ( ! isset( $row[ self::DATE_KEY ] ) || is_array( $row[ self::DATE_KEY ] ) ) {
            return '';
        }

        $value = trim( (string) $row[ self::DATE_KEY ] );

        if ( '' === $value ) {
            return '';
        }

        if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $value, $match ) ) {
            return $match[0];
        }

        $timestamp = strtotime( $value );

        return false === $timestamp ? '' : gmdate( 'Y-m-d', $timestamp );
    }

    /**
     * Pure: is any filter actually narrowing the list?
     *
     * This is what decides between the SQL-paginated fast path and the
     * hydrate-everything path, so it must be answered from the SANITIZED
     * values — `status=all&search_text=` is a fully unfiltered request even
     * though both params are present, and so is a date the picker sent in a
     * shape sanitize_date() rejects.
     *
     * @param string $status    Sanitized status ('' = none).
     * @param string $search    Sanitized search term ('' = none).
     * @param string $date_from Sanitized start-date lower bound ('' = none).
     * @param string $date_to   Sanitized start-date upper bound ('' = none).
     * @return bool
     */
    public static function is_filtered( $status, $search, $date_from = '', $date_to = '' ) {
        return '' !== (string) $status
            || '' !== (string) $search
            || '' !== (string) $date_from
            || '' !== (string) $date_to;
    }

    /**
     * Pure: case-insensitive substring test, multibyte-aware where the
     * extension is available.
     *
     * @param string $haystack Value being searched.
     * @param string $needle   Search term (already sanitized, non-empty).
     * @return bool
     */
    private static function contains( $haystack, $needle ) {
        if ( '' === $haystack ) {
            return false;
        }

        if ( function_exists( 'mb_stripos' ) ) {
            return false !== mb_stripos( $haystack, $needle );
        }

        return false !== stripos( $haystack, $needle );
    }

    /**
     * Pure: does one hydrated display row satisfy every filter?
     *
     * Status is compared exactly (lowercased on both sides), so a row whose
     * status is '' — an un-hydrated row — never satisfies a status filter.
     * That is the intended reading: "show me the active ones" must not
     * include rows whose status is unknown. A row with no usable `start_date`
     * is treated the same way by the date range, for the same reason.
     *
     * The date range is INCLUSIVE at both ends and compared on the day part
     * alone, so "16 Aug to 16 Aug" returns everything that started that day.
     * Either end may be '' for an open-ended range. A reversed range
     * (`from` later than `to`) is compared literally and matches nothing —
     * the picker cannot produce one, so it means a hand-built request, and
     * quietly swapping the bounds would answer a question nobody asked.
     *
     * Search is an OR across SEARCHABLE_KEYS; the filters AND together.
     *
     * @param mixed  $row       Display row as built by AdminAPI::get_subscriptions().
     *                          Typed loose because it is whatever the
     *                          `better_payment/admin/subscription_list_row` filter last
     *                          returned — a third-party handler can hand back anything.
     * @param string $status    Sanitized status ('' = no status filter).
     * @param string $search    Sanitized search term ('' = no search filter).
     * @param string $date_from Sanitized `Y-m-d` lower bound ('' = no lower bound).
     * @param string $date_to   Sanitized `Y-m-d` upper bound ('' = no upper bound).
     * @return bool
     */
    public static function row_matches( $row, $status, $search, $date_from = '', $date_to = '' ) {
        if ( ! is_array( $row ) ) {
            return false;
        }

        $status    = (string) $status;
        $search    = (string) $search;
        $date_from = (string) $date_from;
        $date_to   = (string) $date_to;

        if ( '' !== $status ) {
            $row_status = isset( $row['status'] ) ? strtolower( trim( (string) $row['status'] ) ) : '';

            if ( $row_status !== $status ) {
                return false;
            }
        }

        if ( '' !== $date_from || '' !== $date_to ) {
            $row_date = self::row_date( $row );

            if ( '' === $row_date ) {
                return false;
            }

            // String comparison is safe (and timezone-free) because both
            // sides are zero-padded `Y-m-d`, where lexical order IS
            // chronological order.
            if ( '' !== $date_from && $row_date < $date_from ) {
                return false;
            }

            if ( '' !== $date_to && $row_date > $date_to ) {
                return false;
            }
        }

        if ( '' === $search ) {
            return true;
        }

        foreach ( self::SEARCHABLE_KEYS as $key ) {
            if ( ! isset( $row[ $key ] ) || is_array( $row[ $key ] ) ) {
                continue;
            }

            if ( self::contains( (string) $row[ $key ], $search ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pure: keep only the rows every filter accepts, reindexed from 0 so the
     * result encodes as a JSON array rather than an object.
     *
     * @param mixed  $rows      Hydrated display rows (loose for the same reason as row_matches()'s $row).
     * @param string $status    Sanitized status ('' = no status filter).
     * @param string $search    Sanitized search term ('' = no search filter).
     * @param string $date_from Sanitized `Y-m-d` lower bound ('' = no lower bound).
     * @param string $date_to   Sanitized `Y-m-d` upper bound ('' = no upper bound).
     * @return array
     */
    public static function apply( $rows, $status, $search, $date_from = '', $date_to = '' ) {
        if ( ! is_array( $rows ) ) {
            return array();
        }

        $matched = array();

        foreach ( $rows as $row ) {
            if ( self::row_matches( $row, $status, $search, $date_from, $date_to ) ) {
                $matched[] = $row;
            }
        }

        return $matched;
    }

    /**
     * Pure: slice an already-filtered set into one page, returning the same
     * envelope SubscriptionRelationModel::get_subscriptions_paginated() does
     * so both paths hand AdminAPI an identical shape.
     *
     * `paged` is clamped to the last page that exists. A filter change resets
     * the UI to page 1, but a reload can still arrive with a stale `paged`
     * from a wider result set, and answering that with an empty table (while
     * reporting a total that says there are rows) reads as a broken list.
     *
     * @param mixed $rows     Filtered rows (loose for the same reason as row_matches()'s $row).
     * @param int   $paged    Requested page (1-based).
     * @param int   $per_page Rows per page.
     * @return array { subscriptions: array, total: int, pages: int, page: int, per_page: int }
     */
    public static function paginate( $rows, $paged, $per_page ) {
        $rows     = is_array( $rows ) ? array_values( $rows ) : array();
        $total    = count( $rows );
        $per_page = max( 1, intval( $per_page ) );
        $pages    = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
        $paged    = min( max( 1, intval( $paged ) ), $pages );
        $offset   = ( $paged - 1 ) * $per_page;

        return array(
            'subscriptions' => array_slice( $rows, $offset, $per_page ),
            'total'         => $total,
            'pages'         => $pages,
            'page'          => $paged,
            'per_page'      => $per_page,
        );
    }
}
