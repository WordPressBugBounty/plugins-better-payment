<?php

namespace Better_Payment\Lite\Classes;

use Better_Payment\Lite\Classes\Helper as ClassesHelper;
use Better_Payment\Lite\Controller;
use Better_Payment\Lite\Models\TransactionModel;
use Better_Payment\Lite\Traits\Helper;

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
class Export extends Controller{

    use Helper;

    /**
     * CSV escape character, passed to fputcsv() explicitly on every call.
     *
     * PHP 8.4 deprecates relying on fputcsv()'s default $escape, because that
     * default will change in a later release. Passing the current default (a
     * backslash) keeps the output byte-identical on PHP 7.4 through 8.5.
     */
    const CSV_ESCAPE = "\\";

    /**
     * Class constructor
     * 
     * @since 0.0.4
     */
    public function __construct() {
        
    }

    /**
     * Handle the admin CSV export request.
     *
     * This is the AJAX entry point and nothing else: it checks the nonce and the
     * capability, then hands off. Every step below it is callable without a request
     * context, which is what lets the MCP `transactions-export` ability return the
     * same CSV as a string instead of a browser download.
     *
     * @since 0.0.4
     */
    public function export_transactions() {
        $response = [];

		if (!isset($_REQUEST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'wp_rest')) {
			$response['message'] = __("Access Denied!", 'better-payment');
			wp_send_json_error($response);
		}

		if (!current_user_can('manage_options')) {
			$response['message'] = __("Access Denied!", 'better-payment');
			wp_send_json_error($response);
		}

        $is_filtered = isset($_REQUEST['filtered']) && $_REQUEST['filtered'] === '1';
        $export      = $this->build_export($this->collect_filters($_REQUEST), $is_filtered);

        $this->deliver_csv($export['csv'], $export['filename']);
    }

    /**
     * Write one CSV row. All CSV writes go through here so none can rely on the
     * deprecated default $escape.
     *
     * @param resource $handle    Open, writable stream.
     * @param array    $fields    Row values.
     * @param string   $delimiter Field delimiter.
     * @return int|false Bytes written, or false on failure.
     */
    public static function put_csv_row($handle, array $fields, $delimiter = ";") {
        $fields = array_map(array(__CLASS__, 'neutralize_csv_cell'), $fields);

        return fputcsv($handle, $fields, $delimiter, "\"", self::CSV_ESCAPE);
    }

    /**
     * Neutralise a spreadsheet formula (CSV / formula injection, OWASP).
     *
     * Exported cells carry donor-typed values (name, email, form fields). A cell
     * that starts with = + - @, a tab or a carriage return is evaluated as a
     * formula by Excel, LibreOffice and Google Sheets when the admin opens the
     * file, so it is prefixed with a single quote and read as text. A plain
     * number (e.g. a negative amount) is not a formula and is left as is.
     *
     * @param mixed $value Cell value.
     * @return mixed The value, prefixed with ' when it would start a formula.
     */
    public static function neutralize_csv_cell($value) {
        if (!is_string($value) || '' === $value || is_numeric($value)) {
            return $value;
        }

        if (false !== strpos("=+-@\t\r", $value[0])) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Undo neutralize_csv_cell() for a value read back from an exported file.
     *
     * Strips the leading quote only when it guards a formula character, so an
     * export re-imported through Import keeps the donor's original value.
     *
     * @param mixed $value Cell value.
     * @return mixed The value without the guard quote.
     */
    public static function restore_csv_cell($value) {
        if (!is_string($value) || strlen($value) < 2 || "'" !== $value[0]) {
            return $value;
        }

        if (false !== strpos("=+-@\t\r", $value[1])) {
            return substr($value, 1);
        }

        return $value;
    }

    /**
     * Translate an export request into TransactionModel filter arguments.
     *
     * `filtered=1` is what the React admin sends when the user exports the view in
     * front of them rather than the whole table. Reading the request here keeps the
     * request shape in one place: callers that have no request — the MCP ability —
     * build the same array themselves and never touch $_REQUEST.
     *
     * @since 2.5.0
     * @param mixed $request Raw request data.
     * @return array
     */
    public function collect_filters($request = []) {
        $request = is_array($request) ? $request : [];

        if (!isset($request['filtered']) || $request['filtered'] !== '1') {
            return [];
        }

        $filters = [];
        $keys    = ['search_text', 'payment_date_from', 'payment_date_to', 'source', 'currency', 'status'];

        foreach ($keys as $key) {
            if (!empty($request[$key])) {
                $filters[$key] = sanitize_text_field(wp_unslash($request[$key]));
            }
        }

        // One campaign's transactions (Transactions screen → Campaign filter). The CSV keeps its
        // nine columns: Import refuses a file whose header count differs from the export's.
        if (!empty($request['campaign_id'])) {
            $filters['campaign_id'] = TransactionCampaign::sanitize_filter(wp_unslash($request['campaign_id']));
        }

        return $filters;
    }

    /**
     * Build the export: the rows, the CSV text and the filename it would download as.
     *
     * Returns data. It sends no headers and it does not exit, so it is safe to call
     * from inside a REST request or an ability.
     *
     * @since 2.5.0
     * @param array     $filters   TransactionModel filter arguments.
     * @param bool|null $filtered  Whether this is a filtered export. Null derives it
     *                             from $filters, which is what a caller without a
     *                             request wants; the AJAX handler passes the flag the
     *                             client sent so the filename does not change for an
     *                             export filtered down to nothing.
     * @param string    $delimiter CSV delimiter.
     * @param bool      $include_customer_data Whether the payer name, email and
     *                             submitted form fields belong in the file. True for
     *                             the admin download, which is what an export is for.
     *                             False empties those columns without touching the
     *                             row count or any other column, for a caller that
     *                             ships the result off-site.
     * @return array{filename: string, csv: string, rows: int}
     */
    public function build_export($filters = [], $filtered = null, $delimiter = ';', $include_customer_data = true) {
        $filtered = null === $filtered ? !empty($filters) : (bool) $filtered;

        // A caller that set its own ceiling keeps it. Only the unbounded admin
        // download asks for every row.
        if ($filtered && !isset($filters['per_page'])) {
            $filters['per_page'] = '999999999';
        }

        $transaction_model = new TransactionModel();
        // The admin list's v2 tab buckets, so an export of a tab holds exactly the rows that tab
        // lists. The v1 path matched `completed` against the raw status `completed` only.
        $all_transactions  = $filtered ? $transaction_model->get_transactions($filters, 'v2') : $transaction_model->get_transactions($filters);

        if (!$include_customer_data) {
            $all_transactions = $this->without_customer_data($all_transactions);
        }

        $prefix = $filtered ? 'better-payment-transactions-filtered-' : 'better-payment-transactions-';

        return [
            'filename' => $prefix . gmdate('Y-m-d') . '.csv',
            'csv'      => $this->build_csv($all_transactions, $delimiter),
            'rows'     => count($all_transactions),
        ];
    }

    /**
     * Blank the personal columns on a set of rows.
     *
     * Name, email and the custom form fields are all derived from
     * `form_fields_info`, so clearing that one column empties all three and leaves
     * the amount, gateway, status and date intact. The rows are copied rather than
     * mutated: they come from a query another caller may still be holding.
     *
     * @since 2.5.0
     * @param array $rows Transaction rows.
     * @return array
     */
    private function without_customer_data($rows) {
        $out = [];

        foreach ($rows as $row) {
            if (is_object($row)) {
                $row = clone $row;
                $row->form_fields_info = '';
                $row->email = '';
            } elseif (is_array($row)) {
                $row['form_fields_info'] = '';
                $row['email'] = '';
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * Render transaction rows as CSV text.
     *
     * Split out of transactions_array_to_csv_download() so the formatting — the
     * status labels, the date format, the flattened form fields — has exactly one
     * implementation. A second copy inside the ability would drift from this one the
     * first time a column changed.
     *
     * @since 2.5.0
     * @param array  $array     Transaction rows.
     * @param string $delimiter CSV delimiter.
     * @return string
     */
    public function build_csv($array, $delimiter = ";") {
        $wp_date_format = get_option('date_format');
        $wp_time_format = get_option('time_format');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream for the CSV text; WP_Filesystem has no equivalent.
        $f = fopen('php://memory', 'w+');

        if (false === $f) {
            return '';
        }

        self::put_csv_row($f, $this->export_transactions_heading(), $delimiter);

        foreach ($array as $line) { 
            $line = (array)$line;
            unset($line['id']);
            unset($line['order_id']);
            unset($line['obj_id']);
            unset($line['refund_info']);
            unset($line['customer_info']);
            // unset($line['form_fields_info']);
            unset($line['referer']);


            // From the stored row (form_fields_info still serialized): an unconfirmed pre-2.4.0 free
            // checkout exports as Incomplete (Helper::transaction_status_type()).
            $line['status'] = esc_html( ClassesHelper::transaction_status_label( $line ) );
            $line['source'] = esc_html( ucfirst( $line['source'] ) );
            $line['payment_date'] = wp_date($wp_date_format.' '.$wp_time_format, strtotime($line['payment_date']));;

            // 'One Time' unless Pro labels the row (better_payment/transaction/payment_type).
            $line['payment_type'] = ClassesHelper::payment_type_label( maybe_unserialize( $line['form_fields_info'] ) );

            $line['form_fields_info'] = $this->form_fields_info_formatted($line['form_fields_info']);

            $line['form_fields_info_name'] = isset($line['form_fields_info']['customer_name']) ? $line['form_fields_info']['customer_name'] : '';
            $line['form_fields_info_email'] = isset($line['form_fields_info']['customer_email']) ? $line['form_fields_info']['customer_email'] : '';
            
            unset($line['form_fields_info']['customer_name']);
            unset($line['form_fields_info']['customer_email']);
            
            $line['form_fields_info'] = array_filter($line['form_fields_info'], function($value) {
                return !empty($value);
            });

            $line_formatted = array(
                $line['form_fields_info_name'],
                $line['form_fields_info_email'],
                $line['currency'] . ' ' . $line['amount'],
                $line['payment_type'],
                $line['transaction_id'],
                $line['source'],
                $line['status'],
                $line['payment_date'],
                ! empty( $line['form_fields_info'] ) ? maybe_serialize( $line['form_fields_info'] ) : '',
            );

            self::put_csv_row($f, $line_formatted, $delimiter);
        }

        // reset the file pointer to the start of the file
        fseek($f, 0);

        $csv = stream_get_contents($f);

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes the in-memory stream opened above.
        fclose($f);

        return false === $csv ? '' : $csv;
    }

    /**
     * Send CSV text to the browser as a download.
     *
     * The half of the old method that cannot run inside an ability: it sets headers
     * and exits.
     *
     * @since 2.5.0
     * @param string $csv      CSV text.
     * @param string $filename Download filename.
     * @return void
     */
    public function deliver_csv($csv, $filename = "export.csv") {
        // tell the browser it's going to be a csv file
        header('Content-Type: text/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="'.$filename.'";');

        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV body, not markup.
        exit;
    }

    public function transactions_array_to_csv_download($array, $filename = "export.csv", $delimiter=";") {
        $this->deliver_csv($this->build_csv($array, $delimiter), $filename);
    }  

    public function export_transactions_heading(){
        return array(
            esc_html('name'),
            esc_html('email'),
            esc_html('amount'),
            esc_html('payment_type'),
            esc_html('transaction_id'),
            esc_html('source'),
            esc_html('status'),
            esc_html('payment_date'),
            esc_html('form_fields_info'),
            // 'Order ID',
            // 'Customer Info',
            // 'Form Fields Info',
            // 'Currency',
            // 'Referer'
        );
    }

    /**
     * The fields a transaction contributes to the CSV: customer name and email, plus
     * whatever `better_payment/export/form_fields_info` adds.
     *
     * Pro adds the subscription keys its recurring and split checkouts store; a free
     * install exports name and email.
     *
     * @param mixed $form_fields_info Serialized or unserialized form_fields_info.
     * @return array
     */
    public function form_fields_info_formatted($form_fields_info){
        $bp_form_fields_info = maybe_unserialize($form_fields_info);

        $bp_transaction_customer_name = isset($bp_form_fields_info['primary_first_name']) ? sanitize_text_field($bp_form_fields_info['primary_first_name']) : '';
        $bp_transaction_customer_name .= ' ';
        $bp_transaction_customer_name .= isset($bp_form_fields_info['primary_last_name']) ? sanitize_text_field($bp_form_fields_info['primary_last_name']) : '';

        //legacy
        if( empty($bp_transaction_customer_name) || $bp_transaction_customer_name == ' ' ){
            $bp_transaction_customer_name = isset($bp_form_fields_info['first_name']) ? sanitize_text_field($bp_form_fields_info['first_name']) : '';
            $bp_transaction_customer_name .= ' ';
            $bp_transaction_customer_name .= isset($bp_form_fields_info['last_name']) ? sanitize_text_field($bp_form_fields_info['last_name']) : '';
        }

        $bp_transaction_customer_email = isset($bp_form_fields_info['primary_email']) ? sanitize_text_field($bp_form_fields_info['primary_email']) : '';
        //legacy
        if( empty($bp_transaction_customer_email) ){
            $bp_transaction_customer_email = isset($bp_form_fields_info['email']) ? sanitize_text_field($bp_form_fields_info['email']) : '';
        }

        $formatted = [
            'customer_name'             => $bp_transaction_customer_name,
            'customer_email'            => $bp_transaction_customer_email,
        ];

        $raw_fields = is_array( $bp_form_fields_info ) ? $bp_form_fields_info : [];

        $filtered = apply_filters( 'better_payment/export/form_fields_info', $formatted, $raw_fields );

        return is_array( $filtered ) ? $filtered : $formatted;
    }
    
}