import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * TRANSITIONAL — Lite's copy of the User Dashboard block preview's subscription rows, for
 * Better Payment Pro 1.4.6 and older.
 *
 * Those Pro releases render the Recurring, Split and Subscriptions-tab panels from rows Lite
 * hands them: the first page split into recurring and split payments, and the tab's page state.
 * Pro 1.4.7 builds all of it in its own panels from `blockData`, and declares
 * `user_dashboard_block_subscription_rows`, which turns
 * `betterPaymentBlockData.liteCopies.user_dashboard_block_subscription_rows` off (PHP:
 * `Classes\TransitionalProCopies`). A free install never has the flag on.
 *
 * The next Lite release, which raises MIN_PRO_VERSION to 1.4.7, deletes this file
 * (tests/Unit/ProHandoffTwoTest.php).
 */
export const LEGACY_FLAG = 'user_dashboard_block_subscription_rows';

/**
 * Whether the active Pro still needs Lite's subscription rows.
 *
 * @param {Object} blockData window.betterPaymentBlockData.
 * @return {boolean} True only beside Pro 1.4.6 and older.
 */
export const needsLegacySubscriptionProps = ( blockData ) =>
	blockData?.liteCopies?.[ LEGACY_FLAG ] === true;

/**
 * Split the first page of subscriptions the way Pro 1.4.6's two Dashboard cards expect.
 *
 * @param {Array} rows window.betterPaymentBlockData.userSubscriptions.
 * @return {{recurring: Array, split: Array}} The rows of each card.
 */
export const splitLegacySubscriptions = ( rows ) => {
	const list = Array.isArray( rows ) ? rows : [];
	const isSplit = ( tx ) =>
		!! parseInt( tx?.is_payment_split_payment || 0, 10 );

	return {
		recurring: list.filter( ( tx ) => ! isSplit( tx ) ),
		split: list.filter( isSplit ),
	};
};

/**
 * The props Pro 1.4.6 and older read, or {} for every other install.
 *
 * Always called (it holds state), whatever the flag says.
 *
 * @param {Object} blockData window.betterPaymentBlockData.
 * @param {string} restUrl   The user-transactions REST route.
 * @param {string} restNonce The REST nonce.
 * @return {{recurring?: Object, split?: Object, table?: Object}} Extra props per panel.
 */
export function useLegacySubscriptionProps( blockData, restUrl, restNonce ) {
	const enabled = needsLegacySubscriptionProps( blockData );
	const [ subRows, setSubRows ] = useState(
		enabled ? blockData?.userSubscriptions ?? [] : []
	);
	const [ subPage, setSubPage ] = useState( 1 );
	const [ subMeta, setSubMeta ] = useState(
		blockData?.userSubscriptionsMeta ?? { total: 0, pages: 1, perPage: 20 }
	);
	const mounted = useRef( false );

	const fetchPage = useCallback(
		( page ) => {
			if ( ! enabled || ! restUrl ) return;
			fetch(
				`${ restUrl }?tab=subscriptions&page=${ page }&per_page=${
					subMeta.perPage || 20
				}`,
				{ headers: { 'X-WP-Nonce': restNonce } }
			)
				.then( ( r ) => r.json() )
				.then( ( data ) => {
					setSubRows( data.transactions || [] );
					setSubPage( data.page || page );
					setSubMeta( {
						total: data.total,
						pages: data.pages,
						perPage: data.per_page,
					} );
				} );
		},
		[ enabled, restUrl, restNonce, subMeta.perPage ]
	);

	useEffect( () => {
		if ( ! mounted.current ) {
			mounted.current = true;
			return;
		}
		fetchPage( subPage );
	}, [ subPage ] ); // eslint-disable-line react-hooks/exhaustive-deps

	if ( ! enabled ) {
		return {};
	}

	const { recurring, split } = splitLegacySubscriptions(
		blockData?.userSubscriptions
	);

	return {
		recurring: { subscriptions: recurring },
		split: { subscriptions: split },
		table: { subscriptions: subRows, subPage, subMeta, setSubPage },
	};
}
