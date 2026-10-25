export const StyleOne = () => {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 1000 100"
            preserveAspectRatio="none"
        >
            <g clipPath="url(#eb-shape-divider-ocean-wave)">
                <path
                    className="eb-shape-divider-fill"
                    fill="#715AFF"
                    d="M0 97.79S101.82-.97 283.17 5.23c203.09 0 290.46 94.4 716.83 94.4V0H0v97.79Z"
                />
            </g>
            <defs>
                <clipPath id="eb-shape-divider-ocean-wave">
                    <path
                        fill="#fff"
                        className="eb-shape-divider-fill"
                        d="M0 0h1000v99.62H0z"
                    />
                </clipPath>
            </defs>
        </svg>
    );
};
