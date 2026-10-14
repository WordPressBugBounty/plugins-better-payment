import apiFetch from '@wordpress/api-fetch';

// API base configuration
const API_NAMESPACE = 'better-payment/v1';

/**
 * Generic API request wrapper
 * @param {string} endpoint - API endpoint
 * @param {Object} options - Request options
 * @returns {Promise} API response
 */
const apiRequest = async ( endpoint, options = {} ) => {
	try {
		const response = await apiFetch( {
			path: `${ API_NAMESPACE }/${ endpoint }`,
			...options,
		} );
		return response;
	} catch ( error ) {
		// console.error('API Request Error:', error);
		// throw error;
	}
};

/**
 * Dashboard API methods
 */
export const dashboardAPI = {
	/**
	 * Get dashboard statistics
	 * @returns {Promise} Dashboard stats
	 */
	getStats: () => apiRequest( 'dashboard/stats' ),

	/**
	 * Get transactions for dashboard
	 * @param {Object} filters - Transaction filters
	 * @returns {Promise} Transactions list
	 */
	getTransactions: ( filters = {} ) => {
		const queryParams = new URLSearchParams( filters ).toString();
		return apiRequest( `transactions?${ queryParams }` );
	},

	/**
	 * Check if dismissible section should be shown
	 * @returns {Promise} Dismissible section data
	 */
	showDismissibleSection: () => apiRequest( 'dashboard/dismissible-section' ),

	/**
	 * Get dismissible section data
	 * @returns {Promise} Dismissible section data
	 */
	getDismissibleSectionData: () =>
		apiRequest( 'dashboard/dismissible-section-data' ),

	/**
	 * Dismiss dismissible section
	 * @returns {Promise} Dismiss result
	 */
	dismissDismissibleSection: () =>
		apiRequest( 'dashboard/dismissible-section-dismiss' ),

	/**
	 * Check if sale info is dismissed
	 * @returns {Promise} Dismiss result
	 */
	isSaleInfoDismissed: () => apiRequest( 'dashboard/sale-info-dismissed' ),

	/**
	 * Dismiss sale info
	 * @returns {Promise} Dismiss result
	 */
	dismissSaleInfo: () => apiRequest( 'dashboard/sale-info-dismiss' ),
};

/**
 * Transactions API methods
 */
export const transactionsAPI = {
	/**
	 * Get transaction count
	 * @returns {Promise} Transaction count
	 */
	getTransactionCount: () => apiRequest( 'transactions/count' ),

	/**
	 * Get transactions with filters
	 * @param {Object} filters - Filter parameters
	 * @returns {Promise} Transactions list
	 */
	getTransactions: ( filters = {} ) => {
		const queryParams = new URLSearchParams( filters ).toString();
		return apiRequest( `transactions?${ queryParams }` );
	},

	/**
	 * Get single transaction by ID
	 * @param {number} id - Transaction ID
	 * @returns {Promise} Transaction details
	 */
	getTransaction: ( id ) => apiRequest( `transactions/${ id }` ),

	/**
	 * Update transaction status
	 * @param {number} id - Transaction ID
	 * @param {string} status - New status
	 * @returns {Promise} Updated transaction
	 */
	updateTransactionStatus: ( id, status ) =>
		apiRequest( `transactions/${ id }/status`, {
			method: 'POST',
			data: { status },
		} ),

	/**
	 * Mark transaction as completed
	 * @param {number} id - Transaction ID
	 * @returns {Promise} Mark complete result
	 */
	markTransactionComplete: ( id ) =>
		apiRequest( `transactions/${ id }/mark-complete`, {
			method: 'POST',
		} ),

	/**
	 * Get transaction referer information
	 * @param {number} id - Transaction ID
	 * @returns {Promise} Referer data
	 */
	getTransactionReferer: ( id ) =>
		apiRequest( `transactions/${ id }/referer` ),

	/**
	 * Delete transaction
	 * @param {number} id - Transaction ID
	 * @returns {Promise} Deletion result
	 */
	deleteTransaction: ( id ) =>
		apiRequest( `transactions/${ id }`, {
			method: 'DELETE',
		} ),

	/**
	 * Export transactions
	 * @param {Object} filters - Export filters
	 * @returns {Promise} Export data
	 */
	exportTransactions: ( filters = {} ) =>
		apiRequest( 'transactions/export', {
			method: 'POST',
			data: filters,
		} ),

	/**
	 * Import transactions
	 * @param {FormData} formData - Import file data
	 * @returns {Promise} Import result
	 */
	importTransactions: ( formData ) =>
		apiRequest( 'transactions/import', {
			method: 'POST',
			body: formData,
		} ),
};

/**
 * Subscriptions API methods
 */
export const subscriptionsAPI = {
	/**
	 * Get subscription transactions with pagination
	 * @param {Object} filters - Filter parameters (paged, per_page)
	 * @returns {Promise} Subscriptions list
	 */
	getSubscriptions: ( filters = {} ) => {
		const queryParams = new URLSearchParams( filters ).toString();
		return apiRequest( `subscriptions?${ queryParams }` );
	},

	/**
	 * Get subscription summary counts for the stat cards
	 * @return {Promise} { success, count: { all, active, cancelled } }
	 */
	getSubscriptionCount: () => apiRequest( 'subscriptions/count' ),

	// The three single-subscription calls below use apiFetch directly, NOT
	// apiRequest: its catch swallows rejections into `undefined`, and the
	// details/cancel/delete flows must see the real error (404, "action not
	// available", …) to report it — same reason TransactionDetails.js
	// bypasses the wrapper.

	/**
	 * Get one subscription's details (with related orders)
	 * @param {number} id     - Subscription ID (within its source)
	 * @param {string} source - Source slug ('woo', 'fluentcart', …)
	 * @returns {Promise} Subscription detail
	 */
	getSubscription: ( id, source ) =>
		apiFetch( {
			path: `${ API_NAMESPACE }/subscriptions/${ id }?source=${ encodeURIComponent(
				source
			) }`,
		} ),

	/**
	 * Perform a status action on a subscription
	 * @param {number} id     - Subscription ID (within its source)
	 * @param {string} source - Source slug
	 * @param {string} action - 'cancel' | 'reactivate'
	 * @returns {Promise} Action result
	 */
	updateSubscriptionStatus: ( id, source, action ) =>
		apiFetch( {
			path: `${ API_NAMESPACE }/subscriptions/${ id }/status`,
			method: 'POST',
			data: { source, subscription_action: action },
		} ),

	/**
	 * Delete a subscription (cancels it first when still live, then removes
	 * its relation rows so it no longer lists)
	 * @param {number} id     - Subscription ID (within its source)
	 * @param {string} source - Source slug
	 * @returns {Promise} Deletion result
	 */
	deleteSubscription: ( id, source ) =>
		apiFetch( {
			path: `${ API_NAMESPACE }/subscriptions/${ id }?source=${ encodeURIComponent(
				source
			) }`,
			method: 'DELETE',
		} ),
};

/**
 * Settings API methods
 */
export const settingsAPI = {
	/**
	 * Get all settings
	 * @returns {Promise} Settings data
	 */
	getSettings: () => apiRequest( 'settings' ),

	/**
	 * Update settings
	 * @param {Object} settings - Settings data
	 * @returns {Promise} Updated settings
	 */
	updateSettings: ( settings ) =>
		apiRequest( 'settings', {
			method: 'POST',
			data: settings,
		} ),

	/**
	 * Get specific setting group
	 * @param {string} group - Setting group (general, paypal, stripe)
	 * @returns {Promise} Setting group data
	 */
	getSettingGroup: ( group ) => apiRequest( `settings/${ group }` ),

	/**
	 * Update specific setting group
	 * @param {string} group - Setting group
	 * @param {Object} data - Setting data
	 * @returns {Promise} Updated setting group
	 */
	updateSettingGroup: ( group, data ) =>
		apiRequest( `settings/${ group }`, {
			method: 'POST',
			data,
		} ),
};

/**
 * Campaigns API methods
 */
export const campaignsAPI = {
	getCampaigns: ( params = {} ) => {
		const queryParams = new URLSearchParams( params ).toString();
		return apiRequest( `campaigns?${ queryParams }` );
	},

	createCampaign: ( data = {} ) =>
		apiRequest( 'campaigns', { method: 'POST', data } ),

	deleteCampaign: ( id, force = false ) =>
		apiRequest( `campaigns/${ id }`, {
			method: 'DELETE',
			data: { force },
		} ),

	restoreCampaign: ( id ) =>
		apiRequest( `campaigns/${ id }/restore`, { method: 'POST' } ),
};

export const optionsAPI = {
	/**
	 * Get options
	 * @param {string} optionName - Option name
	 * @returns {Promise} Option data
	 */
	getOptions: ( optionName ) =>
		apiRequest( `options?option_name=${ optionName }` ),
};

/**
 * Utility functions for API responses
 */
export const apiUtils = {
	/**
	 * Handle API errors consistently
	 * @param {Error} error - API error
	 * @returns {Object} Formatted error
	 */
	handleError: ( error ) => {
		const defaultMessage =
			'An unexpected error occurred. Please try again.';

		if ( error.code === 'rest_forbidden' ) {
			return {
				message: 'You do not have permission to perform this action.',
				code: error.code,
			};
		}

		if ( error.code === 'rest_no_route' ) {
			return {
				message:
					'API endpoint not found. Please check your plugin version.',
				code: error.code,
			};
		}

		return {
			message: error.message || defaultMessage,
			code: error.code || 'unknown_error',
		};
	},

	/**
	 * Format currency values
	 * @param {number} amount - Amount to format
	 * @param {string} currency - Currency code
	 * @returns {string} Formatted currency
	 */
	formatCurrency: ( amount, currency = 'USD' ) => {
		return new Intl.NumberFormat( 'en-US', {
			style: 'currency',
			currency: currency,
		} ).format( amount );
	},

	/**
	 * Format date values
	 * @param {string} date - Date string
	 * @returns {string} Formatted date
	 */
	formatDate: ( date ) => {
		return new Intl.DateTimeFormat( 'en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} ).format( new Date( date ) );
	},
};

// Export default API object
export default {
	dashboard: dashboardAPI,
	transactions: transactionsAPI,
	subscriptions: subscriptionsAPI,
	settings: settingsAPI,
	options: optionsAPI,
	utils: apiUtils,
};
