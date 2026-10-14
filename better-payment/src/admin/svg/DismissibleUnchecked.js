const DismissibleUnchecked = () => {
    return (
        <span className='bp-dismissible_list-item_icon'>
            <svg
                width="20"
                height="21"
                viewBox="0 0 20 21"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <rect
                    x="0.6"
                    y="1.1"
                    width="18.8"
                    height="18.8"
                    rx="9.4"
                    stroke="#6C3BFF"
                    strokeWidth="1.2"
                    strokeDasharray="4 2"
                />
                <g clipPath="url(#clip0_4129_12670)">
                    <path
                        d="M6.5 10.4286L9 12.9286L14 7.92859"
                        stroke="#6C3BFF"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </g>
                <defs>
                    <clipPath id="clip0_4129_12670">
                        <rect
                            width="12"
                            height="12"
                            fill="white"
                            transform="translate(4 4.5)"
                        />
                    </clipPath>
                </defs>
            </svg>
        </span>
    );
};

export default DismissibleUnchecked;