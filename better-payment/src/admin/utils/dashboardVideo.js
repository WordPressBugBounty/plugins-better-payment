/**
 * The product video on the admin dashboard.
 *
 * It is a YouTube embed, so it is a third-party request, disclosed in
 * readme.txt's "External services" section (WordPress.org Guideline 6, issue
 * #147). Three rules keep it narrow:
 *
 * - **Only on click.** Hero.js sets the iframe `src` when the admin presses
 *   play, never on page load.
 * - **youtube-nocookie.com.** YouTube's privacy-enhanced mode sets no cookies
 *   until the video plays.
 * - **No tracking parameters.** No `si` share id, and no `enablejsapi`
 *   (nothing talks to the player; closing the popup clears the `src`).
 */
export const DASHBOARD_VIDEO_ID = 'jeXG-4SOlZE';

export const DASHBOARD_VIDEO_URL = `https://www.youtube-nocookie.com/embed/${ DASHBOARD_VIDEO_ID }?autoplay=1&rel=0`;
