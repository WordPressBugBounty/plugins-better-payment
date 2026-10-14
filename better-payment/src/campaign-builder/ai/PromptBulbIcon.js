/**
 * Prompt-idea icon — a lightbulb with a screw base and radiating spark rays.
 *
 * Used on the prompt-suggestion affordances (the "Prompt Suggestions" chip and
 * the round suggested-prompts toggle), replacing the flat `dashicons-lightbulb`.
 * Rounded glass, a reflection highlight, a divided screw cap + foot, and seven
 * rays give it the "bright idea" look.
 *
 * Stroke-based on `currentColor` (fill: none), so it inherits the parent's text
 * colour — matching the chip's default / hover / open-state tints for free.
 */
export default function PromptBulbIcon( { size = 18, className = '' } ) {
	return (
		<svg
			className={ `bp-prompt-bulb-icon${
				className ? ' ' + className : ''
			}` }
			width={ size }
			height={ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			focusable="false"
		>
			{ /* Spark rays — top, two upper diagonals, two horizontals, two lower diagonals */ }
			<path d="M12 2.7V0.9" />
			<path d="M15.67 3.76 16.82 2.12" />
			<path d="M8.33 3.76 7.18 2.12" />
			<path d="M18.4 9h2" />
			<path d="M5.6 9h-2" />
			<path d="M17.24 12.67 18.88 13.82" />
			<path d="M6.76 12.67 5.12 13.82" />
			{ /* Bulb glass */ }
			<path d="M9.6 14.8C7.7 13.6 6.5 11.5 6.5 9.2 6.5 6.1 9 3.6 12 3.6s5.5 2.5 5.5 5.6c0 2.3-1.2 4.4-3.1 5.6" />
			{ /* Reflection highlight */ }
			<path d="M9 8C9.1 6.7 10 5.8 11.2 5.6" />
			{ /* Screw cap + divider + foot */ }
			<path d="M9.7 15.2h4.6v2.7h-4.6z" />
			<path d="M9.7 16.6h4.6" />
			<path d="M10.9 17.9h2.2l-.35 2.2h-1.5z" />
		</svg>
	);
}
