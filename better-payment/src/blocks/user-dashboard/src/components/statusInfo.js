/**
 * Status label and colour of a User Dashboard transaction row, for the block's editor preview.
 *
 * Mirrors PHP Helper::transaction_status_type() / transaction_status_color() (v2 buckets).
 * Prefers the row's server-computed `status_type` (BlockManager's editor rows, UserAPI's REST
 * rows): a raw `no_payment_required` status cannot say whether a pre-2.4.0 free checkout was ever
 * confirmed, and an unconfirmed one reads Incomplete. A row without the field (an older API
 * response) is labelled from its raw status, as before.
 */

const STATUS_INFO = {
	completed: { label: 'Completed', color: '#0ECA86' },
	incomplete: { label: 'Incomplete', color: '#FFDA15' },
	refunded: { label: 'Refunded', color: '#FF0202' },
};

const COMPLETED_STATUSES = [
	'paid',
	'Completed',
	'completed',
	'success',
	'no_payment_required',
];

/**
 * @param {string|null} status     - Raw transaction status.
 * @param {string}      statusType - Server-computed bucket, when the row has one.
 * @return {{label: string, color: string}} Display label and background colour.
 */
export const getBpStatusInfo = ( status, statusType ) => {
	if ( Object.keys( STATUS_INFO ).includes( statusType ) ) {
		return { ...STATUS_INFO[ statusType ] };
	}

	const raw = status ?? '';

	if ( COMPLETED_STATUSES.includes( raw ) ) {
		return { ...STATUS_INFO.completed };
	}

	if ( raw === 'refunded' ) {
		return { ...STATUS_INFO.refunded };
	}

	// pending, Pending, unpaid, incomplete, Incomplete, failed, empty → Incomplete
	return { ...STATUS_INFO.incomplete };
};
