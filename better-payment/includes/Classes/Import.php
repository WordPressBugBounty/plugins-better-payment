<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Models\TransactionModel;
use Better_Payment\Lite\Traits\Helper;
use Exception;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export handler class
 * 
 * @since 0.0.4
 */
class Import extends Controller{

    use Helper;
    /**
     * Class constructor
     * 
     * @since 0.0.4
     */
    public function __construct() {
        
    }

    /**
     * A CSV `payment_date` cell as the UTC `Y-m-d H:i:s` the table stores.
     *
     * The export writes the date in site time, in the site's date and time
     * format (`Export`: `wp_date()`), so a cell without a timezone is read as
     * site time and converted. It used to be stored as if it were UTC, so an
     * export → import round trip moved every row by the site's UTC offset. A
     * cell that names its own timezone (`Z`, `+06:00`, `UTC`, `GMT`) is honoured
     * as given. An unparseable cell keeps the old result (the epoch).
     *
     * @since 2.4.1
     *
     * @param string $value Cell value.
     * @return string
     */
    public static function payment_date_from_csv( $value ) {
        $value     = trim( (string) $value );
        $timestamp = strtotime( $value );

        if ( false === $timestamp ) {
            return gmdate( 'Y-m-d H:i:s', 0 );
        }

        if ( preg_match( '/(?:Z|[+-]\d{2}:?\d{2}|\bUTC|\bGMT)$/i', $value ) ) {
            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }

        // strtotime() read the wall-clock time as UTC (WordPress runs PHP in UTC);
        // it is site time, so convert it.
        return get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' );
    }

    /**
     * Decode a CSV `form_fields_info` cell into an array.
     *
     * The cell comes from an uploaded file, so it is never handed to maybe_unserialize():
     * that instantiates any class a serialized payload names, which turns an import into an
     * object-injection sink for whatever gadget chain the site's plugins happen to provide.
     * Objects are refused outright; only a plain array survives.
     *
     * @since 2.3.4
     *
     * @param mixed $value Cell value.
     * @return array
     */
    public static function decode_form_fields_info( $value ) {
        if ( ! is_string( $value ) || ! is_serialized( $value ) ) {
            return [];
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- classes are disallowed; a malformed cell is expected input.
        $decoded = @unserialize( trim( $value ), [ 'allowed_classes' => false ] );

        return is_array( $decoded ) ? $decoded : [];
    }

    /**
     * The page the import form was posted from, for the redirect back.
     *
     * @return string Empty when the browser sent no referer.
     */
    private static function referer() {
        return isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
    }

    public function import_transactions() {
        $message = __('Invalid File!', 'better-payment');
        
        // phpcs:ignore PluginCheck.Security.VerifyNonce.UnsafeVerifyNonceStatement -- The result is checked: $invalid_nonce aborts the import below.
        $invalid_nonce  = ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'better_payment_transaction_import_nonce' );
        $invalid_access = ! current_user_can('manage_options') || ! isset( $_FILES['better-payment-transaction-import-input'] ); 

		if ( $invalid_nonce || $invalid_access ) {
            set_transient('better_payment_import_transactions_error', esc_html( $message ), 30);
            wp_safe_redirect( self::referer() );
            exit;
		}

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload array; tmp_name is set by PHP and the name is checked by validate_csv_file(). Nonce verified above.
        $file = $_FILES['better-payment-transaction-import-input'];
        
        try {
            $this->validate_csv_file($file);
            $csv = Reader::createFromPath($file['tmp_name'], 'r');
            $csv->setHeaderOffset(0);
            
            // Header validation
            $csvHeader = $csv->getHeader();
            if ( isset( $csvHeader[0] ) && false !== strpos( $csvHeader[0], ';' ) ) {
                $csv->setDelimiter(';');
                $csvHeader = $csv->getHeader();
            }

            if ( ! is_array( $csvHeader ) || ! count( $csvHeader) ){
                throw new Exception( $message );
            }

            $csvHeader = array_map('sanitize_text_field', $csvHeader);
            
            $exportObj = new Export();
            $allowedHeaders = $exportObj->export_transactions_heading();

            if ( count( $allowedHeaders ) !== count( $csvHeader ) ) {
                throw new Exception( $message );
            }

            // Fetch rows
            $stmt = Statement::create()->offset(0)->limit(3000);
            $records = $stmt->process($csv);

            $data = array();

            foreach ( $records as $record ) {
                $better_form_fields = $this->build_form_fields( $record );

                $amount_currency_array = explode(' ', $record['amount']);
                $currency = is_array($amount_currency_array) && count($amount_currency_array) > 1 ? $amount_currency_array[0] : esc_html('USD');
                $amount = is_array($amount_currency_array) && count($amount_currency_array) > 1 ? $amount_currency_array[1] : $record['amount'];

                $data[] = array(
                    'email'             => sanitize_email( $record['email'] ),
                    'currency'          => sanitize_text_field( $currency ),
                    'amount'            => floatval( $amount ),
                    // 'payment_type'      => sanitize_text_field( $record['payment_type'] ),
                    'transaction_id'    => sanitize_text_field( $record['transaction_id'] ),
                    'source'            => sanitize_text_field( $record['source'] ),
                    'status'            => sanitize_text_field( $record['status'] ),
                    'payment_date'      => self::payment_date_from_csv( sanitize_text_field( $record['payment_date'] ) ),
                    'form_fields_info'  => maybe_serialize( $better_form_fields ),
                );
            }

            if ( empty( $data ) ) {
                throw new Exception( __('No valid records found in the CSV file!', 'better-payment') );
            }

            $table = DB::get_table_name();
            $rowsInserted = $this->bulk_insert_transactions( $table, $data );
            if ( $rowsInserted !== false && $rowsInserted > 0 ) {
                /* translators: %d: number of imported transaction rows. */
                set_transient('better_payment_import_transactions_success', sprintf( esc_html__( '%d rows inserted successfully!', 'better-payment' ), $rowsInserted ), 30);
            } else {
                throw new Exception( __('Failed to insert records into database!', 'better-payment') );
            }
        } catch (\Exception $exception) {
            set_transient('better_payment_import_transactions_error', esc_html( $exception->getMessage() ), 30);
            wp_safe_redirect( self::referer() );
            exit;
        }

        wp_safe_redirect( self::referer() );
        exit;
    }

    /**
     * The $wpdb->prepare() placeholder for one imported value.
     *
     * @since 2.4.2
     *
     * @param mixed $value Value to bind.
     * @return string '%d' for an int, '%f' for a float, '%s' for anything else.
     */
    public static function placeholder_for( $value ) {
        if ( is_int( $value ) ) {
            return '%d';
        }

        if ( is_float( $value ) ) {
            return '%f';
        }

        return '%s';
    }

    /**
     * The `form_fields_info` an imported CSV row is stored with.
     *
     * Lite keeps the name, email and source and marks the row imported.
     * `better_payment/import/form_fields_info` receives that, the CSV row and the
     * row's decoded `form_fields_info` cell: Pro adds the subscription keys of a
     * "Subscription" row there.
     *
     * @since 2.4.0
     *
     * @param mixed $record CSV row keyed by header; anything else is treated as empty.
     * @return array
     */
    public function build_form_fields( $record ) {
        $record = is_array( $record ) ? $record : [];

        $better_form_fields = [
            'primary_first_name'    => sanitize_text_field( $record['name'] ?? '' ),
            'email'                 => sanitize_email( $record['email'] ?? '' ),
            'source'                => sanitize_text_field( $record['source'] ?? '' ),
            'is_imported'           => intval(1),
        ];

        // Decoded without instantiating objects; see decode_form_fields_info().
        $decoded = isset( $record['form_fields_info'] ) ? self::decode_form_fields_info( $record['form_fields_info'] ) : [];

        $filtered = apply_filters( 'better_payment/import/form_fields_info', $better_form_fields, $record, $decoded );

        return is_array( $filtered ) ? $filtered : $better_form_fields;
    }

    public function validate_csv_file( $file ){
        if ( empty( $file ) ){
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught in import_transactions(), which esc_html()s the message before storing it; escaping here too would double-escape.
            throw new Exception( __('Invalid File!', 'better-payment') );
        }

        $extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
        $allowed_extensions = ['csv'];

        if ( empty( $extension ) || ! in_array( $extension, $allowed_extensions ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caught in import_transactions(), which esc_html()s the message before storing it; escaping here too would double-escape.
            throw new Exception( __('Invalid File!', 'better-payment') );
        }

        return true;
    }

    public function bulk_insert_transactions($table, $rows) {
        global $wpdb;

        $headers = array_keys($rows[0]);
        asort($headers);
        $columnList = '`' . implode('`, `', $headers) . '`';

        $sql          = "INSERT INTO `$table` ($columnList) VALUES\n";
        $placeholders = array();
        $data         = array();
        
        foreach ($rows as $row) {
            ksort($row);
            $rowPlaceholders = array();
            foreach ($row as $value) {
                $data[]            = $value;
                // Bind by PHP type, never by is_numeric(): '%d' truncated a float amount (10.50
                // was stored as 10) and turned a numeric string such as an order id '0012'
                // into 12. MySQL converts a '%s' value for numeric columns itself.
                $rowPlaceholders[] = self::placeholder_for( $value );
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql .= implode(",\n", $placeholders);
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom table insert, nothing to cache; $sql holds only the table name from $wpdb->prefix, the column names from the fixed row keys above and %s/%d/%f placeholders; every value is bound by prepare().
        $rowsInserted = $wpdb->query($wpdb->prepare($sql, $data));
        return $rowsInserted;
    }
}