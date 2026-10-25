import { __ } from "@wordpress/i18n";
import { DndContext, closestCenter } from "@dnd-kit/core";
import {
    SortableContext,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import arrayMove from "array-move";
import { memo, useLayoutEffect, useRef, useState } from '@wordpress/element';
import { Button } from "@wordpress/components";
import SortableItem from "./sortable-item";
import { isEqual } from "lodash";

export const SortControl = memo(({
    items,
    labelKey,
    onSortEnd,
    hasDelete = true,
    onDeleteItem,
    hasSettings,
    settingsComponents,
    hasAddButton,
    onAddItem,
    addButtonText = 'Add Item',
    preserveLabels = false,
    labelPrefix = 'Item # ',
    defaultShowItemId = undefined // new prop
}) => {
    const [listItems, setListItems] = useState([]);
    const [views, setViews] = useState([]);
    const [showSettings, setShowSettings] = useState(defaultShowItemId);
    const initialList = useRef([]);

    useLayoutEffect(() => {
        if (items.length) {
            const all = items.map((each, i) => {
                if (preserveLabels && initialList.current.length) {
                    const found = initialList.current.filter((listItem) =>
                        isEqual(listItem.value, each)
                    );
                    if (found.length) {
                        return { id: found[0].id, value: each };
                    }
                }
                return { id: i + 1, value: each };
            });
            setListItems(all);
            if (!initialList.current.length) {
                initialList.current = all;
            }
        }
    }, [items]);

    // Update showSettings when defaultShowItemId changes
    useLayoutEffect(() => {
        setShowSettings(defaultShowItemId);
    }, [defaultShowItemId]);

    useLayoutEffect(() => {
        settingsComponents && setViews(settingsComponents);
    }, [settingsComponents]);

    const getIndex = (id) => listItems.findIndex((each) => each.id === id);
    const handleDragEnd = (event) => {
        const { active, over } = event;
        if (active && over) {
            const newList = arrayMove(
                listItems,
                getIndex(active.id),
                getIndex(over.id)
            );
            setListItems(newList);
            views
                ? setViews(arrayMove(views, getIndex(active.id), getIndex(over.id)))
                : null;
            onSortEnd(newList.map((each) => each.value));
        }
    };

    return items.length === 0 ? (
        <>
            <div className="eb-sortable-items-empty">
                {__("No items available", "essential-blocks-pro")}
            </div>
            {hasAddButton && (
                <Button
                    variant={"primary"}
                    size={"small"}
                    icon={"plus-alt"}
                    onClick={onAddItem}
                >
                    {addButtonText}
                </Button>
            )}
        </>
    ) : (
        <DndContext collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
            <SortableContext
                items={listItems}
                strategy={verticalListSortingStrategy}
            >
                <ul>
                    {listItems.map((each, index) => {
                        return (
                            <SortableItem
                                id={each.id}
                                key={`sti-${index}`}
                                index={index}
                                item={each.value}
                                hasDelete={hasDelete}
                                onDelete={onDeleteItem}
                                labelKey={labelKey}
                                hasSettings={hasSettings}
                                views={views}
                                showSettings={showSettings}
                                setShowSettings={setShowSettings}
                                labelPrefix={labelPrefix}
                            />
                        );
                    })}
                </ul>
                {hasAddButton && (
                    <Button
                        variant={"primary"}
                        size={"small"}
                        icon={"plus-alt"}
                        onClick={onAddItem}
                    >
                        {addButtonText}
                    </Button>
                )}
            </SortableContext>
        </DndContext>
    );

    // if (items.length === 0) {
    //   return (
    //     <>
    //       <div className="eb-sortable-items-empty">
    //         {__("No items available", "essential-blocks-pro")}
    //       </div>
    //       {hasAddButton && (
    //         <Button
    //           variant={"primary"}
    //           size={"small"}
    //           icon={"plus-alt"}
    //           onClick={onAddItem}
    //         >
    //           {addButtonText}
    //         </Button>
    //       )}
    //     </>
    //   );
    // } else {
    //   return (
    //     <DndContext
    //       collisionDetection={closestCenter}
    //       onDragEnd={handleDragEnd}
    //     >
    //       <SortableContext
    //         items={listItems}
    //         strategy={verticalListSortingStrategy}
    //       >
    //         <ul>
    //           {listItems.map((each, index) => {
    //             return (
    //               <SortableItem
    //                 id={each.id}
    //                 key={`sti-${index}`}
    //                 index={index}
    //                 item={each.value}
    //                 hasDelete={hasDelete}
    //                 onDelete={onDeleteItem}
    //                 labelKey={labelKey}
    //                 hasSettings={hasSettings}
    //                 views={views?.length > 0 ? views : []}
    //                 showSettings={showSettings}
    //                 setShowSettings={setShowSettings}
    //                 labelPrefix={labelPrefix}
    //               />
    //             );
    //           })}
    //         </ul>
    //         {hasAddButton && (
    //           <Button
    //             variant={"primary"}
    //             size={"small"}
    //             icon={"plus-alt"}
    //             onClick={onAddItem}
    //           >
    //             {addButtonText}
    //           </Button>
    //         )}
    //       </SortableContext>
    //     </DndContext>
    //   );
    // }
}
);
