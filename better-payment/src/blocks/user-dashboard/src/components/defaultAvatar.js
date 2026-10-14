/**
 * The placeholder avatar for the User Dashboard block preview.
 *
 * Used when the user has no avatar URL (the site has avatars switched off).
 * It is an inline SVG on purpose: the fallback used to be
 * `https://www.gravatar.com/avatar/?d=mm`, a third-party request the readme
 * did not disclose (WordPress.org Guideline 6, issue #147).
 */
export const DEFAULT_AVATAR =
	'data:image/svg+xml,' +
	encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">' +
			'<rect width="32" height="32" fill="#d8dce3"/>' +
			'<circle cx="16" cy="12.5" r="6" fill="#fff"/>' +
			'<path d="M5 30c1.5-6.5 6-10 11-10s9.5 3.5 11 10z" fill="#fff"/>' +
			'</svg>'
	);

/**
 * The avatar to show for a preview user.
 *
 * @param {Object} user Current user from the block's localized data.
 * @return {string} An avatar URL, or the local placeholder.
 */
export const avatarFor = ( user ) =>
	user?.user_avatar_url || user?.avatar || DEFAULT_AVATAR;
