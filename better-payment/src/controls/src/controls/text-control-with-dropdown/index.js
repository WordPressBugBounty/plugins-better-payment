/**
 * Enhanced TextControl with dropdown menu using Popover
 * Allows updating the TextControl value when selecting items from the dropdown
 */
import { useState, useRef } from '@wordpress/element';
import { TextControl, Popover, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';

const EBTextControlWithDropdown = (props) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const buttonRef = useRef(null);

  // Toggle menu open/closed
  const toggleMenu = () => {
    setIsMenuOpen(!isMenuOpen);
  };

  // Handle menu item click
  const handleMenuItemClick = (item) => {
    // Update the TextControl value if updateValueOnSelect is true
    if (props.updateValueOnSelect && props.onChange) {
      // If item has a value property, use it, otherwise use the item's label
      const newValue = item.value !== undefined ? item.value : item.label;

      // If insertMode is 'replace', replace the entire value
      // If insertMode is 'append', append to the existing value
      // If insertMode is 'template', replace {value} in the template with the current value
      if (props.insertMode === 'append') {
        props.onChange(props.value ? `${props.value},{${newValue}}` : `{${newValue}}`);
      } else if (props.insertMode === 'template') {
        // Replace {value} in the template with the current value
        const valueToInsert = props.value || '';
        props.onChange(newValue.replace('{value}', valueToInsert));
      } else {
        // Default is 'replace'
        props.onChange(newValue);
      }
    }

    // Call the original onMenuItemClick if provided
    if (props.onMenuItemClick) {
      props.onMenuItemClick(item);
    }

    setIsMenuOpen(false);
  };

  // Menu items - can be passed as props or use defaults
  const menuItems = props.menuItems || [
    { id: 'item1', label: 'Option 1', value: 'option1' },
    { id: 'item2', label: 'Option 2', value: 'option2' },
    { id: 'item3', label: 'Option 3', value: 'option3' },
  ];

  // Determine if we should show the menu button
  const showMenuButton = props.menuItems && props.menuItems.length > 0 && props.showMenuButton !== false;

  return (
    <div className="eb-enhanced-text-control">
      <div className="eb-enhanced-text-control-wrapper">
        <TextControl
          {...props}
          className={`eb-enhanced-text-control-input ${props.className || ''}`}
        />
        {showMenuButton && (
          <Button
            ref={buttonRef}
            className="eb-enhanced-text-control-menu-button"
            onClick={toggleMenu}
            aria-label={__('Show options', 'essential-blocks')}
            icon={moreVertical}
            size="small"
          />
        )}
      </div>

      {isMenuOpen && (
        <Popover
          position={props.menuPosition || "bottom right"}
          onClose={() => setIsMenuOpen(false)}
          anchor={buttonRef.current}
          className="eb-enhanced-text-control-popover"
          noArrow={false}
          offset={8}
        >
          <div className="eb-enhanced-text-control-menu">
            <ul>
              {menuItems.map((item) => (
                <li key={item.id}>
                  <button
                    type="button"
                    onClick={() => handleMenuItemClick(item)}
                  >
                    {item.label}
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </Popover>
      )}
    </div>
  );
};

export default EBTextControlWithDropdown;