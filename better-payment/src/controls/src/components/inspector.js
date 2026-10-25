import { createSlotFill, TabPanel, PanelBody } from "@wordpress/components";
import { InspectorControls } from "@wordpress/block-editor";
import { createContext, useContext, useEffect, useState } from "@wordpress/element";
import { select, dispatch } from '@wordpress/data';
import { AdvancedControls, useBlockAttributes } from "../index";
import SupportPanel from "./supportPanel";

const { Fill: GeneralFill, Slot: GeneralSlot } = createSlotFill("InspectorGeneral");
const { Fill: StyleFill, Slot: StyleSlot } = createSlotFill("InspectorStyle");
const { Fill: AdvancedFill, Slot: AdvancedSlot } = createSlotFill("InspectorAdvanced");

const TabNameContext = createContext('general')
/**
 * Render the Inspector Panel for a block.
 *
 * @param {Object} config - The configuration object.
 * @param {ReactNode} config.children - The children elements to render.
 * @param {Object} config.advancedControlProps - The props for the Advanced Controls component.
 * @param {string[]} config.hideTabs - The array of tab names to hide.
 * @returns {ReactNode} The rendered Inspector Panel.
 */
const InspectorPanel = ({ children, advancedControlProps, hideTabs = [], name }) => {
    const { blockId } = useBlockAttributes()
    const blockData = select('essential-blocks')?.getBlockData(blockId)
    const tabList = () => {
        let tabs = [];
        if (hideTabs.length > 0) {
            if (!hideTabs.includes('general')) {
                tabs.push({
                    name: "general",
                    title: "General",
                    className: "eb-tab general",
                })
            }
            if (!hideTabs.includes('styles')) {
                tabs.push({
                    name: "styles",
                    title: "Style",
                    className: "eb-tab styles",
                })
            }
            if (!hideTabs.includes('advanced')) {
                tabs.push({
                    name: "advance",
                    title: "Advanced",
                    className: "eb-tab advance",
                })
            }
            return tabs;
        }
        return [
            {
                name: "general",
                title: "General",
                className: "eb-tab general",
            },
            {
                name: "styles",
                title: "Style",
                className: "eb-tab styles",
            },
            {
                name: "advance",
                title: "Advanced",
                className: "eb-tab advance",
            },
        ]

    }
    const selectTag = (tab) => {
        const blockData = select('essential-blocks')?.getBlockData(blockId) || {}
        if (!blockData.tabName || (blockData.tabName && blockData.tabName !== tab)) {
            dispatch('essential-blocks')?.setBlockData(blockId, { tabName: tab })
        }
    }

    return (
        <>
            {children}
            <InspectorControls key={'controls'}>
                <div className="eb-panel-control">
                    <TabPanel
                        onSelect={(selected) => selectTag(selected)}
                        selectOnMove={true}
                        className="eb-parent-tab-panel"
                        activeClass="active-tab"
                        initialTabName={blockData?.tabName || 'general'}
                        tabs={tabList()}
                    >
                        {tab => (
                            <div className={"eb-tab-controls-" + tab.name}>
                                <TabNameContext.Provider value={tab.name}>
                                    {tab.name === "general" && <GeneralSlot />}
                                    {tab.name === "styles" && <StyleSlot />}
                                    {tab.name === "advance" && (
                                        <>
                                            <AdvancedControls {...advancedControlProps} />
                                            <AdvancedSlot />
                                        </>
                                    )}
                                </TabNameContext.Provider>
                            </div>
                        )}
                    </TabPanel>
                </div>

                <SupportPanel blockName={name} />
            </InspectorControls>
        </>
    );
};

const EBPanelBody = (props) => {
    const { blockId } = useBlockAttributes()
    const tabName = useContext(TabNameContext);

    const title = props.title || 'Panel'
    const panelID = `${tabName}-${title.toLowerCase()}`
    const blockData = select('essential-blocks')?.getBlockData(blockId)

    const [initialOpen, setInitialOpen] = useState(props.initialOpen || false);

    useEffect(() => {
        if (blockData?.panelName) {
            if (panelID === blockData.panelName) {
                setInitialOpen(true)
            }
            else {
                setInitialOpen(false)
            }
        }
    }, [blockData?.panelName])

    return (
        <PanelBody
            {...props}
            onToggle={(value) => value && dispatch('essential-blocks')?.setBlockData(blockId, { tabName: tabName, panelName: panelID })}
            initialOpen={initialOpen}
        >
            {props.children}
        </PanelBody>
    )
}

InspectorPanel.General = GeneralFill;
InspectorPanel.Style = StyleFill;
InspectorPanel.Advanced = AdvancedFill;
InspectorPanel.PanelBody = EBPanelBody;

export { InspectorPanel };
