/**
 * Save the settings and report the outcome.
 *
 * Every way a save can fail — a rejected request (settingsAPI.updateSettings
 * rejects through apiMutation), a resolved `undefined`, or a body without
 * `success` — calls `onError`, and `setSaving( false )` always runs last. The
 * old inline handler read `response.success` on `undefined`: an uncaught
 * TypeError, no error toast, and a spinner that never went away.
 *
 * @param {Object}   settings          Settings to save.
 * @param {Object}   handlers
 * @param {Function} handlers.update    ( settings ) => Promise of the REST response.
 * @param {Function} handlers.onSuccess Called with the saved settings.
 * @param {Function} handlers.onError   Called with the error, if there was one.
 * @param {Function} handlers.setSaving Spinner state setter.
 * @return {Promise<boolean>} Whether the save succeeded.
 */
export const saveSettings = async (
	settings,
	{ update, onSuccess, onError, setSaving }
) => {
	setSaving( true );
	try {
		const response = await update( settings );
		if ( response?.success ) {
			onSuccess( settings );
			return true;
		}
		onError( null );
		return false;
	} catch ( error ) {
		onError( error );
		return false;
	} finally {
		setSaving( false );
	}
};
