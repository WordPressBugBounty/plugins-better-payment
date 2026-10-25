import { __ } from "@wordpress/i18n";
import { useState, useEffect, useRef, memo, Fragment } from "@wordpress/element";
import {
    BaseControl,
    PanelRow,
    Dropdown,
    TabPanel,
    GradientPicker,
    ColorIndicator,
    Button
} from "@wordpress/components";
import { select } from "@wordpress/data";
import { AdvancedColorPicker } from "../../helpers";
import { useBlockSetAttributes } from "../../hooks";

const ColorControl = ({
    label,
    color,
    attributeName,
    onChange,
    defaultColor,
    isGradient = false
}) => {
    const [bgColor, setBgColor] = useState(color ?? defaultColor ?? "");
    const setAttributes = useBlockSetAttributes();

    // Track source of last change to avoid writing back on prop-driven syncs
    const isFirstRenderRef = useRef(true);
    const lastChangeSourceRef = useRef(null);

    const handleSetColor = (val) => {
        lastChangeSourceRef.current = 'user';
        setBgColor(val);
    };

    useEffect(() => {
        // Prop-driven sync
        if (color !== undefined || defaultColor !== undefined) {
            lastChangeSourceRef.current = 'props';
            setBgColor(color ?? defaultColor ?? "");
        }
    }, [color, defaultColor]);

    useEffect(() => {
        // Only persist when user changes the color via UI
        if (isFirstRenderRef.current) {
            isFirstRenderRef.current = false;
            return;
        }
        if (lastChangeSourceRef.current !== 'user') {
            // Ignore prop-driven syncs
            return;
        }
        if (bgColor === undefined) return; // never write undefined back to attributes

        if (onChange) {
            onChange(bgColor);
        } else {
            setAttributes({ [attributeName]: bgColor });
        }
    }, [bgColor]);

    const themeGradientColors = select('core/block-editor')?.getSettings()?.gradients || [];
    const generateColorArray = (colors) => {
        const updatedColorArr = []
        if (!colors || colors.length === 0) {
            return updatedColorArr
        }
        colors.map((item) => {
            updatedColorArr.push({
                color: `var(${item?.var})`,
                name: item?.name,
                slug: item?.slug
            })
        })

        return updatedColorArr;
    };
    const [hasEBStore, setHasEBStore] = useState(false);
    useEffect(() => {
        // Detect if the EB data store exists by checking for a known selector
        try {
            const eb = select('essential-blocks');
            setHasEBStore(!!eb?.getGradientColors);
        } catch (e) {
            setHasEBStore(false);
        }
    }, []);

    const colorType = () => {
        if (typeof color !== 'string') {
            return 'solid'
        }
        if (color.includes('gradient') && isGradient) {
            return 'gradient'
        }
        return 'solid'
    }

    const modifiedGradientColors = hasEBStore ? generateColorArray(select("essential-blocks").getGradientColors()) : undefined;
    const modifiedCustomGradientColors = hasEBStore ? generateColorArray(select("essential-blocks").getCustomGradientColors()) : undefined;

    return (
        <BaseControl label={label || ""} className="eb-color-base">
            <Dropdown
                contentClassName={"eb-color-picker"}
                popoverProps={{ focusOnMount: false }}
                renderToggle={({ onToggle }) => (
                    <>
                        <ColorIndicator
                            className={'eb-color-control-indecator'}
                            colorValue={bgColor}
                            onClick={onToggle}
                            aria-label={bgColor || "default"}
                        />
                    </>
                )}
                renderContent={() => (
                    <div className={`eb-color-picker-content ${!isGradient ? 'eb-gradient-picker-disabled' : ''}`}>
                        <TabPanel
                            className="eb-colorpicker-tab-panel"
                            activeClass="active-tab"
                            initialTabName={colorType()}
                            tabs={[
                                {
                                    name: "solid",
                                    title: "Solid",
                                    className: "eb-tab solid",
                                },
                                {
                                    name: "gradient",
                                    title: "Gradient",
                                    className: "eb-tab gradient",
                                }
                            ]}
                        >
                            {(tab) => (
                                <div className={`eb-tab-controls ${tab.name}`}>
                                    {tab.name === "solid" && (
                                        <>
                                            <AdvancedColorPicker
                                                color={bgColor}
                                                setColor={handleSetColor}
                                            />
                                        </>
                                    )}
                                    {tab.name === "gradient" && isGradient && (
                                        <>
                                            <PanelRow>{__('Gradient Colors', 'essential-blocks')}</PanelRow>
                                            <GradientPicker
                                                value={bgColor || null}
                                                onChange={(color) => { handleSetColor(color) }}
                                                asButtons={true}
                                                clearable={true}
                                                gradients={themeGradientColors || []}
                                            />

                                            {modifiedGradientColors && modifiedGradientColors.length > 0 && (
                                                <>
                                                    <PanelRow>{__('Global Gradient Colors', 'essential-blocks')}</PanelRow>
                                                    <div className="eb-colorpicker-gradient-color-list__global">
                                                        {modifiedGradientColors.map((item, index) => (
                                                            <Fragment key={index}>
                                                                <ColorIndicator
                                                                    className={`eb-colorpicker-gradient-color ${bgColor === item.color ? 'current' : ''}`}
                                                                    colorValue={item?.color}
                                                                    onClick={() => handleSetColor(item.color)}
                                                                />
                                                            </Fragment>
                                                        ))}
                                                    </div>
                                                </>
                                            )}

                                            {modifiedCustomGradientColors && modifiedCustomGradientColors.length > 0 && (
                                                <>
                                                    <PanelRow>{__('Custom Gradient Colors', 'essential-blocks')}</PanelRow>
                                                    <div className="eb-colorpicker-gradient-color-list__global">
                                                        {modifiedCustomGradientColors.map((item, index) => (
                                                            <Fragment key={index}>
                                                                <ColorIndicator
                                                                    key={index}
                                                                    className={`eb-colorpicker-gradient-color ${bgColor === item.color ? 'current' : ''}`}
                                                                    colorValue={item?.color}
                                                                    onClick={() => handleSetColor(item.color)}
                                                                />
                                                            </Fragment>
                                                        ))}
                                                    </div>
                                                </>
                                            )}
                                        </>
                                    )}
                                </div>
                            )}
                        </TabPanel>
                    </div>
                )}
            />
            <Button
                size="small"
                className={`eb-color-undo ${!bgColor ? 'eb-disable-reset' : ''}`}
                icon="image-rotate"
                style={{
                    transform: "scaleX(-1) rotate(90deg)",
                }}
                onClick={() => {
                    handleSetColor(defaultColor || '');
                }}
                disabled={bgColor ? false : true}
            ></Button>
        </BaseControl>
    );
};

export default memo(ColorControl);
