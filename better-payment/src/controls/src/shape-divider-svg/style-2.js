export const StyleTwo = () => {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 1000 100"
            preserveAspectRatio="none"
        >
            <g clipPath="url(#eb-shape-divider-triangle)">
                <path
                    className="eb-shape-divider-fill"
                    fill="#5A88FF"
                    d="m0 98.99 170.59-87.45S611.11 98.99 1000 98.99V0H0v98.99Z"
                />
            </g>
            <defs>
                <clipPath id="eb-shape-divider-triangle">
                    <path
                        fill="#fff"
                        className="eb-shape-divider-fill"
                        d="M0 0h1000v98.99H0z"
                    />
                </clipPath>
            </defs>
        </svg>
    );
};
