import React, { useRef, useState, useEffect } from "@wordpress/element";

/**
 * TransactionsSearchBar Component
 *
 * Search input with debounced search (100ms delay after user stops typing)
 *
 * @param {function} onSearch - Callback when search is triggered (debounced)
 * @param {string} value - Current search value from parent component (for controlled input)
 */
export function TransactionsSearchBar({ onSearch, value = '', placeholder = 'Search transactions...', debounceMs = 200, showIcon = true }) {
    const [active, setActive] = useState(false);
    const [searchValue, setSearchValue] = useState(value);
    const inputRef = useRef(null);
    const boxRef = useRef(null);
    const debounceTimerRef = useRef(null);

    // Sync internal state when parent value changes (e.g., when filters are reset)
    useEffect(() => {
        setSearchValue(value);
    }, [value]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (boxRef.current && !boxRef.current.contains(e.target)) {
                setActive(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    // Cleanup debounce timer on component unmount
    useEffect(() => {
        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    const handleSearchClick = () => {
        setActive(true);
        setTimeout(() => inputRef.current?.focus(), 100);
    };

    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchValue(value);

        // Clear existing debounce timer
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            onSearch?.(value);
        }, debounceMs);
    };

    return (
        <div
            ref={boxRef}
            className={`search-box ${active ? "active" : ""}`}
            onClick={handleSearchClick}
        >

            <input
                ref={inputRef}
                type="text"
                placeholder={placeholder}
                value={searchValue}
                onChange={handleSearchChange}
                onFocus={() => setActive(true)}
                className="bp-font_ibm"
            />

                { showIcon && (
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 18 18"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        className="search-icon"
                    >
                        <g clipPath="url(#clip0_6142_14265)">
                            <path
                                d="M3.30469 8.25C3.30469 8.93944 3.44048 9.62213 3.70432 10.2591C3.96816 10.896 4.35487 11.4748 4.84238 11.9623C5.32988 12.4498 5.90864 12.8365 6.5456 13.1004C7.18256 13.3642 7.86525 13.5 8.55469 13.5C9.24413 13.5 9.92682 13.3642 10.5638 13.1004C11.2007 12.8365 11.7795 12.4498 12.267 11.9623C12.7545 11.4748 13.1412 10.896 13.4051 10.2591C13.6689 9.62213 13.8047 8.93944 13.8047 8.25C13.8047 7.56056 13.6689 6.87787 13.4051 6.24091C13.1412 5.60395 12.7545 5.0252 12.267 4.53769C11.7795 4.05018 11.2007 3.66347 10.5638 3.39963C9.92682 3.1358 9.24413 3 8.55469 3C7.86525 3 7.18256 3.1358 6.5456 3.39963C5.90864 3.66347 5.32988 4.05018 4.84238 4.53769C4.35487 5.0252 3.96816 5.60395 3.70432 6.24091C3.44048 6.87787 3.30469 7.56056 3.30469 8.25Z"
                                stroke="#8F9AB0"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                            <path
                                d="M14.8594 14.25L12.3047 12"
                                stroke="#8F9AB0"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </g>
                        <defs>
                            <clipPath id="clip0_6142_14265">
                                <rect width="18" height="18" fill="white" />
                            </clipPath>
                        </defs>
                    </svg>
                ) }

        </div>
    );
};

