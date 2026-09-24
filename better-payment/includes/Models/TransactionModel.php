<?php

namespace Better_Payment\Lite\Models;

use Better_Payment\Lite\Admin\DB;
use Better_Payment\Lite\Model;

/**
 * The Transaction handler database class
 * 
 * @since 0.0.4
 */
class TransactionModel extends Model {

    /**
     * Initialize the class
     * 
     * @since 0.0.4
     */
    public function __construct( ) {
        parent::__construct();
    }

    /**
     * Transactions for the CSV export.
     *
     * @param array  $args    DB::get_transactions() filters.
     * @param string $version 'v2' filters `status` by the admin list's tab buckets; 'v1' by raw status.
     * @return array
     */
    public function get_transactions($args = [], $version = 'v1') {
        // Set default per_page if not provided
        if (!isset($args['per_page'])) {
            $args['per_page'] = '999999999';
        }
        $all_transactions = DB::get_transactions($args, 0, $version);
        return $all_transactions;
    }
} 