import { useRef, useEffect } from '@wordpress/element';

/**
 * FilterDropdown Component
 * 
 * A reusable dropdown filter component with toggle and click-outside functionality
 * 
 * @param {string} label - The label to display for the filter
 * @param {string} value - The current selected value
 * @param {array} options - Array of option objects with { label, value } structure
 * @param {function} onChange - Callback when an option is selected
 * @param {boolean} isOpen - Whether the dropdown is currently open
 * @param {function} onToggle - Callback to toggle dropdown open/closed
 * @param {function} onClose - Callback to close the dropdown
 */
const FilterDropdown = ({
    label,
    value,
    options,
    onChange,
    isOpen,
    onToggle,
    onClose
}) => {
    const dropdownRef = useRef(null);

    // Handle click outside to close dropdown
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                onClose();
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
            return () => document.removeEventListener('mousedown', handleClickOutside);
        }
    }, [isOpen, onClose]);

    const handleOptionClick = (e, optionValue) => {
        e.stopPropagation();
        onChange(optionValue);
        onClose();
    };

    // Find the display label for the current value
    // If the selected option is "All", display the filter category name instead
    const selectedOption = options.find(opt => opt.value === value);
    const displayLabel = selectedOption?.label === 'All' ? label : (selectedOption?.label || label);

    // Check if filter has non-default values (not 'all' or empty string)
    const hasValue = value && value !== 'all' && value !== '';
    const isActive = isOpen || hasValue;

    return (
        <div
            ref={dropdownRef}
            className={`bp-flex align-items_center gap-8 bp-transactions_list-form-select${isActive ? ' active' : ''}`}
            onClick={onToggle}
        >
            <button
                className='bp-flex align-items_center gap-8 bp-filter-dropdown-trigger'
                type='button'
            >
                <span className='bp-font_ibm select-name'>{displayLabel}</span>
                <span className='line-height_0'>
                    <svg
                        width="11"
                        height="6"
                        viewBox="0 0 11 6"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        className={isOpen ? 'bp-dropdown-icon-open' : ''}
                    >
                        <path
                            d="M0.75 0.75L5.25 5.25L9.75 0.75"
                            stroke="#8F9AB0"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </span>
            </button>

            {isOpen && (
                <ul className='bp-transactions_list-form-select-dropdown dropdown-active'>
                    {options.map((option) => (
                        <li
                            key={option.value}
                            className={`fx-4 bp-font-dm bp-transactions_list-form-select-dropdown-item ${
                                value === option.value ? 'bp-selected' : ''
                            }`}
                            onClick={ (e) => handleOptionClick(e, option.value) }
                            role='option'
                            aria-selected={value === option.value}
                        >
                            {option.label}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
};

export default FilterDropdown;

