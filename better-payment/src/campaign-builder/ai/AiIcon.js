/**
 * AI icon — a "sparkles" mark (the universal AI motif), used everywhere the
 * assistant is surfaced (panel header, sidebar tab, template card, collapse rail).
 *
 * Inherits its colour from the parent's `color` (fill: currentColor), so it
 * adapts to active/inactive tab states and gradient backgrounds without extra
 * styling. Size is controllable per placement.
 */
export default function AiIcon( { size = 18, className = '' } ) {
    return (
        <svg
            className={ `bp-ai-icon${ className ? ' ' + className : '' }` }
            width={ size }
            height={ size }
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true"
            focusable="false"
        >
            {/* Large four-point sparkle */}
            <path d="M11 2.25a.6.6 0 0 1 .57.41l1.34 3.9a3.5 3.5 0 0 0 2.18 2.18l3.9 1.34a.6.6 0 0 1 0 1.14l-3.9 1.34a3.5 3.5 0 0 0-2.18 2.18l-1.34 3.9a.6.6 0 0 1-1.14 0l-1.34-3.9a3.5 3.5 0 0 0-2.18-2.18l-3.9-1.34a.6.6 0 0 1 0-1.14l3.9-1.34a3.5 3.5 0 0 0 2.18-2.18l1.34-3.9A.6.6 0 0 1 11 2.25Z" />
            {/* Small accent sparkle */}
            <path d="M18.5 13.5a.45.45 0 0 1 .43.31l.53 1.53a1.8 1.8 0 0 0 1.12 1.12l1.53.53a.45.45 0 0 1 0 .86l-1.53.53a1.8 1.8 0 0 0-1.12 1.12l-.53 1.53a.45.45 0 0 1-.86 0l-.53-1.53a1.8 1.8 0 0 0-1.12-1.12l-1.53-.53a.45.45 0 0 1 0-.86l1.53-.53a1.8 1.8 0 0 0 1.12-1.12l.53-1.53a.45.45 0 0 1 .43-.31Z" />
        </svg>
    );
}
