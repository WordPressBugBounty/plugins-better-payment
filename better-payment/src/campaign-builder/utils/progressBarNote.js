import { __ } from '@wordpress/i18n';

/**
 * The note under the Progress Bar's settings, matched to what the bar shows.
 *
 * The renderer prints the Donated and Goal figures only when the campaign has a
 * goal, and the bar has nothing to fill without one. The note used to say the
 * opposite ("the goal amount displays as 0 and the donated percentage stays at
 * 0%"), which described a version that printed "Goal: $0".
 *
 * @param {Object} meta Campaign meta.
 * @return {string} Note text.
 */
export function progressBarNote( meta ) {
	const goal = parseFloat( meta?.bpc_goal_amount );

	if ( Number.isFinite( goal ) && goal > 0 ) {
		return __(
			'The bar fills as donations come in. Its goal is the Campaign Goal Amount in Settings → General Settings.',
			'better-payment'
		);
	}

	return __(
		'No campaign goal is set, so the bar stays empty and the Donated and Goal figures are hidden. Set a Campaign Goal Amount in Settings → General Settings to show progress.',
		'better-payment'
	);
}
