const DismissibleChecked = () => {
    return (
        <span className='bp-dismissible_list-item_icon'>
            <svg
                width="22"
                height="22"
                viewBox="0 0 22 22"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <g filter="url(#filter0_dii_4129_12656)">
                    <rect x="1" y="0.5" width="20" height="20" rx="10" fill="#6C3BFF" />
                    <g clipPath="url(#clip0_4129_12656)">
                        <path
                            d="M7.5 10.4286L10 12.9286L15 7.92859"
                            stroke="white"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </g>
                </g>
                <defs>
                    <filter
                        id="filter0_dii_4129_12656"
                        x="0.5"
                        y="0"
                        width="21"
                        height="21.2"
                        filterUnits="userSpaceOnUse"
                        colorInterpolationFilters="sRGB"
                    >
                        <feFlood floodOpacity="0" result="BackgroundImageFix" />
                        <feColorMatrix
                            in="SourceAlpha"
                            type="matrix"
                            values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                            result="hardAlpha"
                        />
                        <feOffset dy="0.2" />
                        <feGaussianBlur stdDeviation="0.25" />
                        <feComposite in2="hardAlpha" operator="out" />
                        <feColorMatrix
                            type="matrix"
                            values="0 0 0 0 0.0977877 0 0 0 0 0 0 0 0 0 0.391151 0 0 0 1 0"
                        />
                        <feBlend
                            mode="normal"
                            in2="BackgroundImageFix"
                            result="effect1_dropShadow_4129_12656"
                        />
                        <feBlend
                            mode="normal"
                            in="SourceGraphic"
                            in2="effect1_dropShadow_4129_12656"
                            result="shape"
                        />
                        <feColorMatrix
                            in="SourceAlpha"
                            type="matrix"
                            values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                            result="hardAlpha"
                        />
                        <feOffset dy="0.5" />
                        <feGaussianBlur stdDeviation="0.5" />
                        <feComposite
                            in2="hardAlpha"
                            operator="arithmetic"
                            k2="-1"
                            k3="1"
                        />
                        <feColorMatrix
                            type="matrix"
                            values="0 0 0 0 0.221986 0 0 0 0 0.077545 0 0 0 0 0.655311 0 0 0 1 0"
                        />
                        <feBlend
                            mode="normal"
                            in2="shape"
                            result="effect2_innerShadow_4129_12656"
                        />
                        <feColorMatrix
                            in="SourceAlpha"
                            type="matrix"
                            values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
                            result="hardAlpha"
                        />
                        <feOffset dy="-0.5" />
                        <feGaussianBlur stdDeviation="0.5" />
                        <feComposite
                            in2="hardAlpha"
                            operator="arithmetic"
                            k2="-1"
                            k3="1"
                        />
                        <feColorMatrix
                            type="matrix"
                            values="0 0 0 0 0.605957 0 0 0 0 0.474609 0 0 0 0 1 0 0 0 1 0"
                        />
                        <feBlend
                            mode="normal"
                            in2="effect2_innerShadow_4129_12656"
                            result="effect3_innerShadow_4129_12656"
                        />
                    </filter>
                    <clipPath id="clip0_4129_12656">
                        <rect width="12" height="12" fill="white" transform="translate(5 4.5)" />
                    </clipPath>
                </defs>
            </svg>
        </span>
    )
};

export default DismissibleChecked;