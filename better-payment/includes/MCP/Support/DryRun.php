<?php
/**
 * Preview mode for MCP write tools.
 *
 * @package Better_Payment
 */

namespace Better_Payment\Lite\MCP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The shared half of `dry_run`.
 *
 * Every write tool accepts a `dry_run` flag. With it set the tool does all of its
 * checking — permission, arguments, the allowlist, whether the record exists — and
 * then reports what it *would* change instead of changing it. It is the right first
 * move for a model and the right thing for an owner to ask for before saying yes.
 *
 * Two rules make the flag worth trusting:
 *
 * - **A dry run refuses whatever the real call would refuse.** A preview that succeeds
 *   where the real call fails is worse than no preview: it is a promise the plugin
 *   does not keep. So the flag is read *after* every guard, immediately before the
 *   write.
 * - **A dry run writes nothing at all** — no row, no option, no post, no e-mail, no
 *   gateway call.
 *
 * @since 2.5.0
 */
class DryRun {

	/**
	 * The input-schema property every write tool declares.
	 *
	 * @return array<string, mixed>
	 */
	public static function property() {
		return array(
			'type'        => 'boolean',
			'description' => 'Report what would change and write nothing. Every other check still runs, so a dry run fails wherever the real call would.',
			'default'     => false,
		);
	}

	/**
	 * Whether this input asked for a preview.
	 *
	 * @param mixed $input Tool arguments.
	 * @return bool
	 */
	public static function requested( $input ) {
		return is_array( $input ) && ! empty( $input['dry_run'] );
	}

	/**
	 * Shape a preview result.
	 *
	 * @param string $summary      One line a person can read.
	 * @param array  $would_change What the real call would do.
	 * @return array<string, mixed>
	 */
	public static function preview( $summary, array $would_change = array() ) {
		return array(
			'dry_run'      => true,
			'applied'      => false,
			'summary'      => (string) $summary,
			'would_change' => $would_change,
		);
	}
}
