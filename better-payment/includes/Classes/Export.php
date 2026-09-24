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
     * Class constructor
     * 
     * @since 0.0.4
     */
    public function __construct() {
        
    }

    public function export_transactions() {
        $response = [];

		if (!wp_verify_nonce($_REQUEST['nonce'], 'wp_rest')) {
			$response['message'] = __("Access Denied!", 'better-payment');
			wp_send_json_error($response);
		}

		if (!current_user_can('manage_options')) {
			$response['message'] = __("Access Denied!", 'better-payment');
			wp_send_json_error($response);
		}
		
		$transaction_model = new TransactionModel();
        
        // Check if this is a filtered export
        $is_filtered = isset($_REQUEST['filtered']) && $_REQUEST['filtered'] === '1';
        
        if ($is_filtered) {
            // Build filter arguments
            $filter_args = [];
            
            if (!empty($_REQUEST['search_text'])) {
                $filter_args['search_text'] = sanitize_text_field($_REQUEST['search_text']);
            }
            
            if (!empty($_REQUEST['payment_date_from'])) {
                $filter_args['payment_date_from'] = sanitize_text_field($_REQUEST['payment_date_from']);
            }
            
            if (!empty($_REQUEST['payment_date_to'])) {
                $filter_args['payment_date_to'] = sanitize_text_field($_REQUEST['payment_date_to']);
            }
            
            if (!empty($_REQUEST['source'])) {
                $filter_args['source'] = sanitize_text_field($_REQUEST['source']);
            }
            
            if (!empty($_REQUEST['currency'])) {
                $filter_args['currency'] = sanitize_text_field($_REQUEST['currency']);
            }
            
            if (!empty($_REQUEST['status'])) {
                $filter_args['status'] = sanitize_text_field($_REQUEST['status']);
            }
            
            $filter_args['per_page'] = '999999999';
            // The admin list's v2 tab buckets, so an export of a tab holds exactly the rows that tab
            // lists. The v1 path matched `completed` against the raw status `completed` only.
            $all_transactions = $transaction_model->get_transactions($filter_args, 'v2');
            $filename = 'better-payment-transactions-filtered-' . date('Y-m-d') . '.csv';
        } else {
            $all_transactions = $transaction_model->get_transactions();
            $filename = 'better-payment-transactions-' . date('Y-m-d') . '.csv';
        }

        return $this->transactions_array_to_csv_download($all_transactions, $filename);
    }

    public function transactions_array_to_csv_download($array, $filename = "export.csv", $delimiter=";") {
        $wp_date_format = get_option('date_format');
        $wp_time_format = get_option('time_format');

        $f = fopen('php://memory', 'w'); 

        fputcsv($f, $this->export_transactions_heading(), $delimiter);

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

            fputcsv($f, $line_formatted, $delimiter); 
        }
        
        // reset the file pointer to the start of the file
        fseek($f, 0);
        // tell the browser it's going to be a csv file
        header('Content-Type: text/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        // make php send the generated csv lines to the browser
        
        fpassthru($f);
        exit;
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
     * Pro adds the subscription keys its recurring and split checkouts store. Lite's
     * copy of that (ProSubscriptionTransactionData::export_fields()) serves only an
     * active Pro that does not (<= 1.4.3); a free install exports name and email.
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

        if ( ProSubscriptionTransactionData::lite_copy_applies() ) {
            $formatted = ProSubscriptionTransactionData::export_fields( $formatted, $raw_fields );
        }

        $filtered = apply_filters( 'better_payment/export/form_fields_info', $formatted, $raw_fields );

        return is_array( $filtered ) ? $filtered : $formatted;
    }
    
}