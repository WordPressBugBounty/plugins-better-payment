/**
 * `metaCondition` on a settings control: show it only while campaign meta matches.
 *
 * `{ bpc_some_key: [ 'a', '' ] }` — every key must hold one of its listed values
 * (an absent key reads as ''). The element-settings twin is `condition`
 * (`isControlVisible()` in RightSidebar.js); this one reads the CAMPAIGN's meta,
 * for controls that depend on a campaign-wide choice. Pro uses it to hide the
 * Donate Button's page-link settings while the campaign uses its popup form.
 *
 * @param {Object} control Settings control.
 * @param {Object} meta    Campaign meta.
 * @return {boolean} False when the control must be hidden.
 */
export function isVisibleForMeta( control, meta ) {
	const condition = control?.metaCondition;

	if ( ! condition || typeof condition !== 'object' ) {
		return true;
	}

	return Object.entries( condition ).every( ( [ key, allowed ] ) => {
		const values = ( Array.isArray( allowed ) ? allowed : [ allowed ] ).map(
			String
		);

		return values.includes( String( meta?.[ key ] ?? '' ) );
	} );
}
