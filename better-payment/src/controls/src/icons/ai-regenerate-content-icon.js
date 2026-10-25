const RegenerateIcon = (props) => (
    <svg
        width="60"
        height="60"
        viewBox="0 0 60 60"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        {...props}
    >
        {/* Gradient similar to your original icon */}
        <defs>
            <linearGradient
                id="circleGradient"
                x1="0"
                y1="30"
                x2="60"
                y2="30"
                gradientUnits="userSpaceOnUse"
            >
                <stop stopColor="#EE9AFF" />
                <stop offset="0.555" stopColor="#9975FF" />
                <stop offset="1" stopColor="#6C3BFF" />
            </linearGradient>
        </defs>

        {/* Main circular arrow (refresh symbol) */}
        <path
            d="M30 6c-12.15 0-22 9.85-22 22s9.85 22 22 22 22-9.85 22-22h-4c0 10.837-8.163 19-18 19S12 36.837 12 26s8.163-19 18-19z"
            fill="url(#circleGradient)"
        />
        {/* Arrow head pointing clockwise */}
        <path
            d="M30 12v10l4-4-4-4z"
            fill="white"
        />

        {/* Overlay small icon at top right (like badge / icon) */}
        <g transform="translate(40, 10)">
            {/* Make the small icon similar to the original AiIcon but scaled down */}
            <svg width="16" height="16" viewBox="0 0 30 32" xmlns="http://www.w3.org/2000/svg">
                {/* Reuse the AiIcon SVG content with scaled viewBox */}
                {/* For simplicity, I will draw a simplified version similar to AiIcon */}
                {/* You can replace this with the actual AiIcon SVG code if preferred */}
                <defs>
                    <linearGradient
                        id="badgeGradient"
                        x1="15"
                        y1="0"
                        x2="15"
                        y2="32"
                        gradientUnits="userSpaceOnUse"
                    >
                        <stop stopColor="#EE9AFF" />
                        <stop offset="0.555" stopColor="#9975FF" />
                        <stop offset="1" stopColor="#6C3BFF" />
                    </linearGradient>
                </defs>
                <circle cx="15" cy="16" r="15" fill="url(#badgeGradient)" />
                {/* Optional: add simple features or omit for cleaner badge */}
            </svg>
        </g>
    </svg>
);

export default RegenerateIcon;
