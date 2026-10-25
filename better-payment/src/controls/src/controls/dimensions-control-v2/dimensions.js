import { __ } from "@wordpress/i18n";
import { useState, useEffect, useRef } from "@wordpress/element";
import { WithResponsiveOptions } from "../../components";
export default function DimensionsControl({
    top = '',
    right = '',
    bottom = '',
    left = '',
    onChange,
    neededProps,
    disableLeftRight,
    disableLinkedButton,
    isTextField,
    setIsTextField,
    selectedUnit,
    useCustomValue
}) {
    const [dimensions, setDimensions] = useState({
        top,
        right,
        bottom,
        left,
    });

    const {
        baseLabel,
        resOption,
        forBorderRadius,
        setAttributes,
        dimensionIsLinked,
        controlName,
    } = neededProps;

    const [isLinked, setIsLinked] = useState(dimensionIsLinked);
    const prevUnitRef = useRef(selectedUnit);
    const isMount = useRef(false);
    const showTextField = isTextField || selectedUnit === '';
    const onButtonClick = () => setIsLinked(!isLinked);
    const onInputChange = (event) => {
        let { name, value } = event.target;
        if (isLinked) {
            setDimensions({
                top: value,
                right: value,
                bottom: value,
                left: value,
            });
        } else {
            setDimensions((prevDimensions) => ({ ...prevDimensions, [name]: value }));
        }
    };

    useEffect(() => {
        onChange(dimensions);
    }, [dimensions]);

    useEffect(() => {
        setAttributes({
            [`${controlName}isLinked`]: isLinked,
        });
        const { top: value } = dimensions;
        if (isLinked) {
            setDimensions({
                top: value,
                right: value,
                bottom: value,
                left: value,
            });
        }
    }, [isLinked]);

    useEffect(() => {
        if (isMount.current && useCustomValue) {
            const newUnit = selectedUnit;
            const oldUnit = prevUnitRef.current;

            const hasUnit = ['', 'px', 'em', '%'];
            if ((hasUnit.includes(oldUnit) && newUnit === "") || (oldUnit === "" && hasUnit.includes(newUnit))) {
                setDimensions({
                    top: "",
                    bottom: "",
                    right: "",
                    left: ""
                })
            }

            prevUnitRef.current = newUnit;
        }
        isMount.current = true;
    }, [selectedUnit])

    return (
        <div className="dimention-container">
            <WithResponsiveOptions
                label={baseLabel}
            >
                <div
                    className={`input-container ${disableLeftRight ? "left-right-disabled" : ""
                        }`}
                >
                    <div className="input-wrapper">
                        <input
                            type={showTextField ? "text" : "number"}
                            name="top"
                            value={dimensions.top}
                            onChange={onInputChange}
                        />
                        <label className="dimentions-input-label">
                            {forBorderRadius ? " " : "Top"}
                        </label>
                    </div>
                    {/* {!disableLeftRight && ( */}
                    <div className="input-wrapper">
                        <input
                            type={(disableLeftRight || showTextField) ? "text" : "number"}
                            name="right"
                            // value={disableLeftRight ? "auto" : dimensions.right}
                            value={disableLeftRight ? undefined : dimensions.right}
                            disabled={disableLeftRight ? true : false}
                            onChange={onInputChange}
                        />
                        <label className="dimentions-input-label">
                            {forBorderRadius ? " " : "Right"}
                        </label>
                    </div>
                    {/* )} */}
                    <div className="input-wrapper">
                        <input
                            type={showTextField ? "text" : "number"}
                            name="bottom"
                            value={dimensions.bottom}
                            onChange={onInputChange}
                        />
                        <label className="dimentions-input-label">
                            {forBorderRadius ? " " : "Bottom"}
                        </label>
                    </div>
                    {/* {!disableLeftRight && ( */}
                    <div className="input-wrapper">
                        <input
                            type={(disableLeftRight || showTextField) ? "text" : "number"}
                            name="left"
                            // value={disableLeftRight ? "auto" : dimensions.left}
                            value={disableLeftRight ? undefined : dimensions.left}
                            disabled={disableLeftRight ? true : false}
                            onChange={onInputChange}
                        />
                        <label className="dimentions-input-label">
                            {forBorderRadius ? " " : "Left"}
                        </label>
                    </div>
                    {/* )} */}
                    {!disableLinkedButton && (
                        <button
                            className={`linked-btn components-button is-button dashicons dashicons-${isLinked ? "admin-links is-primary" : "editor-unlink is-default"
                                }`}
                            onClick={onButtonClick}
                        />
                    )}

                </div>
                {isTextField && (
                    <p className="eb-help-text components-base-control__help">{__("Add custom value e.g. ", "essential-blocks")}{
                        <code>auto</code>}</p>
                )}
            </WithResponsiveOptions>
        </div>
    )
}
