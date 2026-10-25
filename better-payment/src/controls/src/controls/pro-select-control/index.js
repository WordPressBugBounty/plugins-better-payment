import { SelectControl } from "@wordpress/components";
import { useState } from "@wordpress/element";
import Select2 from "react-select";

const ProSelectControl = (props) => {
    const {
        options,
        onChange,
        selectType = 'selectcontrol'
    } = props;

    const [isProPreset, setIspro] = useState(false);

    // Modify options to add lock icon for pro options when pro is not active
    const modifiedOptions = options.map(option => {
        if (option.isPro && EssentialBlocksLocalize?.is_pro_active === "false" && selectType === 'selectcontrol') {
            return {
                ...option,
                label: `🔒 ${option.label}`
            };
        }
        return option;
    });

    const changePreset = (selected) => {
        let filteredItem = '';

        if (typeof selected === 'object') {
            filteredItem = options.filter((item) => item.value === selected.value);
        }
        else {
            filteredItem = options.filter((item) => item.value === selected);
        }

        if (EssentialBlocksLocalize?.is_pro_active === "false" && filteredItem[0] && filteredItem[0].isPro) {
            setIspro(true);
        } else {
            setIspro(false);
            onChange(selected);
        }
    };

    return (
        <>
            {selectType === 'selectcontrol' && (
                <SelectControl {...props} options={modifiedOptions} onChange={(selected) => changePreset(selected)} />
            )}
            {selectType === 'select2' && (
                <Select2 {...props} onChange={(selected) => changePreset(selected)} />
            )}
            {isProPreset && (
                <>
                    <div className="eb-is-pro-message">
                        <span className="dashicons dashicons-lock"></span>
                        <span>
                            <a href={EssentialBlocksLocalize?.upgrade_pro_url} target="_blank">
                                Upgrade to pro
                            </a>{" "}
                            for this premium feature!
                        </span>
                    </div>
                </>
            )}
        </>
    );
};

export default ProSelectControl;
