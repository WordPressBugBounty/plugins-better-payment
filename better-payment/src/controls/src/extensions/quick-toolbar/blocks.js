/**
 * Creates sidebar blocks.
 */
import { useState, useEffect } from "@wordpress/element";
import {
  DndContext,
  closestCenter,
  pointerWithin,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import {
  arrayMove,
  SortableContext,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import {
  restrictToVerticalAxis,
  restrictToParentElement,
} from "@dnd-kit/modifiers";
import { dispatch, useDispatch, select, useSelect } from "@wordpress/data";
import { Icon, Popover } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { saveQuickToolbarBlocks } from "../../";
import { createBlock, getBlockTypes } from "@wordpress/blocks";
// Component to handle individual sortable items
export const SortableItem = ({
  id,
  item,
  saveOptionToDatabase,
  setItems,
  defaultBlocks,
  setDefaultBlocks,
  isDragging,
}) => {
  // Use useSortable hook to enable dragging functionality
  const { attributes, listeners, setNodeRef, transform, transition } =
    useSortable({ id });

  const [hovering, setHovering] = useState(false);
  const handleMouseOver = () => {
    if (!isDragging) setHovering(true);
  };
  const handleMouseOut = () => setHovering(false);
  const { createNotice } = useDispatch("core/notices");
  const blocks = getBlockTypes();
  const create = (name) => {
    return createBlock(name);
  };
  const [uniqueId, setUniqueId] = useState(0);
  const removedNoticeID = `eb-quick-toolbar/eb-blocks/${uniqueId}`;

  const {
    blockInsertionPoint,
    getSelectedBlockClientId,
    getSelectedBlockAllowedBlocks,
    getBlockRootClientId,
  } = useSelect((select) => {
    const blockEditor = select("core/block-editor");
    const { index } = blockEditor.getBlockInsertionPoint();
    const clientId = blockEditor.getSelectedBlockClientId();
    const allowedBlocks = blockEditor.getAllowedBlocks(clientId);
    const rootClientId = blockEditor.getBlockRootClientId(clientId);
    return {
      blockInsertionPoint: index,
      getSelectedBlockClientId: clientId,
      getSelectedBlockAllowedBlocks: allowedBlocks || [],
      getBlockRootClientId: rootClientId,
    };
  });
  // Apply transform and transition styles for drag animation
  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    cursor: "grab",
  };

  // Removes the specified element from an array.
  const removeElementFromArray = (
    arrayFromWhichElementNeedToRemove,
    elementToRemove
  ) =>
    arrayFromWhichElementNeedToRemove.filter(
      (element) => element !== elementToRemove
    );

  const handleRemoveBlock = (elementToRemove) => {
    const updatedArray = removeElementFromArray(
      defaultBlocks,
      elementToRemove.name
    );

    const ebBlocks = updatedArray
      ?.map((blockName) => blocks.find((block) => block.name === blockName))
      .filter((block) => block !== undefined);

    ebBlocks.forEach((item, index) => {
      item.id = index + 1;
    });
    setItems(ebBlocks);
    saveOptionToDatabase(updatedArray);
    setDefaultBlocks(updatedArray);
    // Increment uniqueId when removing a block
    setUniqueId((prevUniqueId) => prevUniqueId + 1);
    createNotice(
      "success",
      sprintf(
        /* translators: abbreviation for units */ __(
          "%s Removed from Quick Action Toolbar.",
          "essential-blocks"
        ),
        elementToRemove.title
      ),
      {
        type: "snackbar",
        id: removedNoticeID,
        isDismissible: true,
      }
    );
    // Set a timeout to remove the notice after a specific duration (e.g., 600 milliseconds)
    setTimeout(() => {
      // Remove the notice by ID
      dispatch("core/notices").removeNotice(removedNoticeID);
    }, 1000);
  };

  useEffect(() => {
    setHovering(false);
    const popover = document.querySelectorAll(
      ".eb-quick-toolbar__sidebar--blocks--block"
    );
    if (isDragging) {
      if (popover) {
        popover.forEach((item) => {
          item.style.pointerEvents = "none";
        });
      }
    }

    return () => {
      setTimeout(() => {
        popover.forEach((item) => {
          item.style.pointerEvents = "auto";
        });
      }, 100);
    };
  }, [isDragging]);

  const hoverPopover = (
    <Popover
      placement="right"
      key={id}
      className="eb-quick-toolbar__sidebar--blocks--block--icon--name"
    >
      <div className="block-title">
        <div
          onClick={() => {
            handleRemoveBlock(item);
          }}
          className="eb-quick-toolbar__sidebar--blocks--block--name"
        >
          <svg
            className="block-title-svg"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            width="24"
            height="24"
            aria-hidden="true"
            focusable="false"
          >
            <path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
          </svg>
        </div>
        {item?.title}
      </div>
    </Popover>
  );

  const handleOnClick = (e, selectedBlock) => {
    let clientId = getBlockRootClientId || "";
    let insertionPoint = blockInsertionPoint;
    if (
      getSelectedBlockAllowedBlocks &&
      getSelectedBlockAllowedBlocks.includes(selectedBlock)
    ) {
      insertionPoint =
        select("core/block-editor").getSelectedBlock().innerBlocks.length;
      clientId = getSelectedBlockClientId;
    }
    if (e?.target?.classList?.contains("block-title-svg")) {
      return;
    }
    dispatch("core/block-editor").insertBlocks(
      create(selectedBlock.name),
      insertionPoint,
      clientId
    );
  };

  return (
    <div
      ref={setNodeRef} // Reference for the sortable item
      style={style}
      {...attributes} // Attributes required for accessibility
      {...listeners} // Event listeners for drag events
      className="eb-quick-toolbar__sidebar--blocks--block"
      onMouseOver={handleMouseOver}
      onMouseOut={handleMouseOut}
      // onFocus={handleMouseOver}
      // onBlur={handleMouseOut}
      onClick={(e) => {
        handleOnClick(e, item);
      }}
    >
      <div
        className={`eb-quick-toolbar__sidebar-icon__${
          item?.name?.split("/")[0]
        } eb-quick-toolbar__sidebar-icon__${item?.name?.split("/")[0]}--${
          item?.name?.split("/")[1]
        } eb-quick-toolbar__sidebar--blocks--block--icon`}
      >
        <Icon icon={item.icon.src ? item.icon.src : item.icon} />
      </div>
      {item?.name?.split("/")[0] === "essential-blocks" && (
        <div className="eb-quick-toolbar__sidebar--blocks--block--small-logo">
          <svg
            width="9"
            height="9"
            viewBox="0 0 9 9"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <g clipPath="url(#clip0_3706_1354)">
              <path
                d="M5.90606 0H0.5V5.64303H2.72175V5.53546H4.67972V3.35673H2.72175V1.9849H6.05148V5.58912C7.41246 5.33637 8.43996 4.14733 8.43996 2.71633V2.53353C8.43971 1.13513 7.30464 0 5.90606 0Z"
                fill="#616A70"
              />
              <path
                d="M6.05148 3.38353V6.907H2.72175V5.53517H4.67972V3.35645H0.5V8.90269H6.05148C7.38012 8.83649 8.43996 7.73572 8.43996 6.39047V6.1322C8.43996 4.72802 7.40168 3.57159 6.05148 3.38328V3.38353Z"
                fill="#6C3BFF"
              />
              <path
                d="M6.05044 6.90381H2.7207V8.90325H6.05044V6.90381Z"
                fill="#9975FF"
              />
              <path
                d="M6.05148 3.38353V6.907H2.72175V5.53517H4.67972V3.35645H0.5V8.90269H6.05148C7.38012 8.83649 8.43996 7.73572 8.43996 6.39047V6.1322C8.43996 4.72802 7.40168 3.57159 6.05148 3.38328V3.38353Z"
                fill="#7F888E"
              />
              <path
                d="M5.90526 0H2.7207V2.00672H6.05044V0.00426268C6.00255 0.00150447 5.95416 0 5.90526 0Z"
                fill="#7F888E"
              />
              <path
                d="M6.05044 6.90381H2.7207V8.90325H6.05044V6.90381Z"
                fill="#616A70"
              />
            </g>
            <defs>
              <clipPath id="clip0_3706_1354">
                <rect
                  width="8"
                  height="8.90323"
                  fill="white"
                  transform="translate(0.5)"
                />
              </clipPath>
            </defs>
          </svg>
        </div>
      )}
      {hovering && !isDragging && hoverPopover}
    </div>
  );
};

// Main drag and drop component
const Blocks = ({ initialItems, defaultBlocks, setDefaultBlocks }) => {
  const [items, setItems] = useState(initialItems);
  const [isDragging, setIsDragging] = useState(false);
  const blocks = getBlockTypes();
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 5, // Start dragging after moving 5px
      },
    })
  );

  const handleDragEnd = ({ active, over }) => {
    if (active.id !== over.id) {
      const oldIndex = items.findIndex((item) => item.id === active.id);
      const newIndex = items.findIndex((item) => item.id === over.id);
      const reorderedItems = arrayMove(items, oldIndex, newIndex);
      const namesArray = reorderedItems.map((item) => item.name);
      saveOptionToDatabase(namesArray);
      setDefaultBlocks(namesArray);
      setItems(reorderedItems);
    }
    setIsDragging(false);
  };

  useEffect(() => {
    const ebBlocks = defaultBlocks
      ?.map((blockName) => blocks.find((block) => block.name === blockName))
      .filter((block) => block !== undefined)
      .map((item, index) => ({
        ...item,
        id: item.id || index + 1, // Preserve existing IDs
      }));

    setItems(ebBlocks);
  }, [defaultBlocks]);

  const handleDragStart = (event) => {
    setIsDragging(true);
  };

  // Function to save the reordered items to the database
  const saveOptionToDatabase = (allowedBlocks) => {
    saveQuickToolbarBlocks(JSON.stringify(allowedBlocks));
  };

  return (
    <DndContext
      sensors={sensors}
      collisionDetection={pointerWithin}
      onDragEnd={handleDragEnd}
      onDragStart={handleDragStart}
      modifiers={[restrictToVerticalAxis, restrictToParentElement]}
    >
      <SortableContext
        items={items.map((item) => item.id)}
        strategy={verticalListSortingStrategy} // Use vertical list sorting strategy
      >
        <div className="eb-quick-toolbar__sidebar--blocks-sortable">
          {items.map((item, index) => (
            <SortableItem
              key={item.id}
              id={item.id}
              item={item}
              isLast={index === items.length - 1}
              saveOptionToDatabase={saveOptionToDatabase}
              setItems={setItems}
              defaultBlocks={defaultBlocks}
              setDefaultBlocks={setDefaultBlocks}
              isDragging={isDragging}
              setIsDragging={setIsDragging}
            />
          ))}
        </div>
      </SortableContext>
    </DndContext>
  );
};

export default Blocks;
