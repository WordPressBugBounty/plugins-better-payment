import { __ } from "@wordpress/i18n";
import { applyFilters } from "@wordpress/hooks";

import {
    InspectorPanel
} from "../../";

import Crown from "../../group-controls/components/assets/crown"
import ProBadge from "../../group-controls/components/assets/ProBadge"
import FormConditionalLogicsIcon from "./icon"

import './style.scss'

const FormCLUpgradeProContent = () => {
    return (
        <div className='eb_ie'>
            <FormConditionalLogicsIcon />
            <h3><a target='_blank' href="https://essential-blocks.com/docs/eb-form-block/">Conditional Logics <span class="dashicons dashicons-external"></span></a></h3>
            <p>Set conditions to trigger specific form actions based on user input</p>
            <a className='eb_upgrade_button' target='_blank' href={EssentialBlocksLocalize?.upgrade_pro_url}><Crown />Upgrade to PRO</a>
        </div>
    )
}

const FormConditionalLogics = (props) => {
    const { clientId, parentBlockId } = props

    return (
        <InspectorPanel.PanelBody
            title={__("Conditional Logics", "essential-blocks")}
            initialOpen={false}
            className={"eb-form-conditional-logics"}
            {...(EssentialBlocksLocalize?.is_pro_active === "false" && { icon: <ProBadge /> })}
        >
            {applyFilters("eb_form_conditional_logics", <FormCLUpgradeProContent />, { clientId, parentBlockId })}
        </InspectorPanel.PanelBody>
    );
};

export default FormConditionalLogics;
