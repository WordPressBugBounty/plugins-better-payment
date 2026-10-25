/**
 * Creates a popover modal.
 */
import { useState } from "@wordpress/element";
import { Popover, SearchControl, Icon } from "@wordpress/components";
import { useDispatch, dispatch } from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";
import { getBlockTypes } from "@wordpress/blocks";
import { saveQuickToolbarBlocks } from "../../";

const PopoverModal = ({ closePopover, defaultBlocks, setDefaultBlocks }) => {
  const [searchTerm, setSearchTerm] = useState("");
  const { createNotice } = useDispatch("core/notices");
  const [uniqueId, setUniqueId] = useState(0);
  const addedNoticeID = `eb-quick-toolbar/eb-blocks/${uniqueId}`;
  const blocks = getBlockTypes();

  // Call the callback function in the parent
  const closePopup = () => closePopover(false);

  const handleSearchChange = (newSearchTerm) => setSearchTerm(newSearchTerm);

  const saveOptionToDatabase = (allowedBlocks) => {
    saveQuickToolbarBlocks(JSON.stringify(allowedBlocks));
  };

  const handleBlockClick = (selectedBlock) => {
    // You can handle the selected block here, e.g., add it to the state or perform other actions
    const allowedBlocks = [...defaultBlocks, selectedBlock.name];

    saveOptionToDatabase(allowedBlocks);
    setDefaultBlocks(allowedBlocks);

    // Increment uniqueId when removing a block
    setUniqueId((prevUniqueId) => prevUniqueId + 1);

    createNotice(
      "success",
      sprintf(
        /* translators: abbreviation for units */ __(
          "%s Added to Quick Action Toolbar.",
          "essential-blocks"
        ),
        selectedBlock.title
      ),
      {
        type: "snackbar",
        id: addedNoticeID,
        isDismissible: true,
      }
    );

    // Set a timeout to remove the notice after a specific duration (e.g., 600 milliseconds)
    setTimeout(() => {
      // Remove the notice by ID
      dispatch("core/notices").removeNotice(addedNoticeID);
    }, 1000);
    closePopup();
  };

  const filteredBlocks = blocks.filter((block) => {
    const titleMatches = block.title
      .toLowerCase()
      ?.includes(searchTerm.toLowerCase());

    if (EssentialBlocksLocalize?.is_pro_active === "false") {
      // Check if this is a pro block by checking its category or name
      const isPro =
        block.category === "essential-blocks-pro" ||
        (block.name && block.name.includes("essential-blocks/pro-"));

      // Only include blocks that match the search term AND are not pro blocks
      return titleMatches && !isPro;
    }

    // If pro plugin is active, just check the title match
    return titleMatches;
  });

  // Separate arrays for used and unused items
  const usedArray = [];
  const unusedArray = [];

  // Iterate.
  filteredBlocks.forEach((item) => {
    if (defaultBlocks?.includes(item.name)) {
      usedArray.push(item);
    } else {
      unusedArray.push(item);
    }
  });
  // Function to check if an item has either a parent or ancestor or both
  const noParentNoAncestor = (item) => {
    if (!item.parent && !item.ancestor) {
      return item;
    }
  };
  const addToSidebar = () => {
    return unusedArray.map(
      (item, index) =>
        noParentNoAncestor(item) && (
          <div
            key={index}
            className="eb-quick-toolbar-block-wrap"
            onClick={() => handleBlockClick(item)}
            style={{ cursor: "pointer" }}
          >
            <div className="eb-quick-toolbar__sidebar--blocks--block--icon">
              <Icon icon={item.icon?.src ? item.icon.src : item.icon} />
            </div>
            <div className="block-title">{item.title}</div>
          </div>
        )
    );
  };

  const alreadyPresentInSidebar = () => {
    return usedArray.map(
      (item, index) =>
        !item.parent && (
          <div key={index} className="eb-quick-toolbar-block-wrap">
            <div className="eb-quick-toolbar__sidebar--blocks--block--icon">
              <Icon icon={item.icon?.src ? item.icon.src : item.icon} />
            </div>
            <div className="block-title">{item.title}</div>
          </div>
        )
    );
  };

  return (
    <Popover
      onClose={closePopup}
      placement="right-start"
      className="eb-quick-toolbar-block-popover"
    >
      <SearchControl
        value={searchTerm}
        onChange={handleSearchChange}
        label={__("Search Blocks", "essential-blocks")}
        className="eb-quick-toolbar-block-popover-search"
      />
      <div className="eb-quick-toolbar-block-container">
        {unusedArray.some(
          (item) =>
            !item.parent &&
            item.title.toLowerCase().includes(searchTerm.toLowerCase())
        ) && (
          <div className="block-editor-inserter__panel-header eb-quick-toolbar-block-popover-header__add-to-quick-action-bar">
            <h2 className="block-editor-inserter__panel-title">
              {__("Add Blocks to Quick Action Toolbar", "essential-blocks")}
            </h2>
          </div>
        )}
        {addToSidebar()}
        {usedArray.some(
          (item) =>
            !item.parent &&
            item.title.toLowerCase().includes(searchTerm.toLowerCase())
        ) && (
          <div className="block-editor-inserter__panel-header eb-quick-toolbar-block-popover-header__already-present-in-quick-action-bar">
            <h2 className="block-editor-inserter__panel-title">
              {__(
                "Already Present in Quick Action Toolbar",
                "essential-blocks"
              )}
            </h2>
          </div>
        )}
        {alreadyPresentInSidebar()}
        {!unusedArray.some(
          (item) =>
            !item.parent &&
            item.title.toLowerCase().includes(searchTerm.toLowerCase())
        ) &&
          !usedArray.some(
            (item) =>
              !item.parent &&
              item.title.toLowerCase().includes(searchTerm.toLowerCase())
          ) && (
            <div className="block-editor-inserter__no-results">
              <p>{__("No results found.", "essential-blocks")}</p>
            </div>
          )}
      </div>
    </Popover>
  );
};

export default PopoverModal;
