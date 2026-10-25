/**
 * Essential Blocks Schema Configuration
 *
 * This schema maps Essential Blocks to their key attributes for content and basic styling.
 * Each attribute is defined with:
 * - type: The data type (string, boolean, number, array, object)
 * - isContent: Whether this attribute contains content for AI processing
 *
 * Includes only commonly-used blocks with content-related and basic styling attributes.
 * Excludes complex layout, advanced styling, and technical configuration attributes.
 */

const essentialBlocksSchema = {
    "essential-blocks/wrapper": {
        "wrapperAlign": {
            "type": "string",
            "isContent": false
        },
        "contentAlign": {
            "type": "string",
            "isContent": true
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },
    "essential-blocks/row": {
        "isLayoutSelected": {
            "type": "boolean",
            "value": true
        },
        "align": {
            "type": "string",
            "isContent": false
        },
        "rowWidthName": {
            "type": "string",
            "isContent": false
        },
        "rowHeightName": {
            "type": "string",
            "isContent": false
        },
        "rowAli": {
            "type": "string",
            "isContent": false
        },
        "clNum_Range": {
            "type": "number",
            "isContent": false
        },
        "TABclNum_Tablet": {
            "type": "number",
            "isContent": false
        },
        "MOBclNum_Mobile": {
            "type": "number",
            "isContent": false
        },
        "rColsGapDesktop": {
            "type": "number",
            "isContent": false
        },
        "rColsGapTablet": {
            "type": "number",
            "isContent": false
        },
        "rColsGapMobile": {
            "type": "number",
            "isContent": false
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/column": {
        "colAli": {
            "type": "string",
            "isContent": false
        },
        "wrapperWidthDesktop": {
            "type": "number",
            "isContent": false
        },
        "wrapperWidthTablet": {
            "type": "number",
            "isContent": false
        },
        "wrapperWidthMobile": {
            "type": "number",
            "isContent": false
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/accordion": {
        "displayIcon": {
            "type": "boolean",
            "isContent": false
        },
        "tabIcon": {
            "type": "string",
            "isContent": true
        },
        "expandedIcon": {
            "type": "string",
            "isContent": true
        },
        "contentAlign": {
            "type": "string",
            "isContent": false
        },
        "titleAlignment": {
            "type": "string",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "contentColor": {
            "type": "string",
            "isContent": false
        },
        "iconColor": {
            "type": "string",
            "isContent": false
        },
        "hoverTitleColor": {
            "type": "string",
            "isContent": false
        },
        "activeBgColor": {
            "type": "string",
            "isContent": false
        },
        "activeTitleColor": {
            "type": "string",
            "isContent": false
        },
        "activeIconColor": {
            "type": "string",
            "isContent": false
        },
        "tagName": {
            "type": "string",
            "isContent": false
        },
        "accordionChildCount": {
            "type": "number",
            "isContent": false
        },
        "faqSchema": {
            "type": "boolean",
            "isContent": true
        },
        "accordionLists": {
            "type": "array",
            "isContent": true
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/accordion-item": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "clickable": {
            "type": "boolean",
            "isContent": false
        },
        "expanded": {
            "type": "boolean",
            "isContent": false
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/advanced-tabs": {
        "layout": {
            "type": "string",
            "isContent": false
        },
        "tagName": {
            "type": "string",
            "isContent": false
        },
        "mediaPositon": {
            "type": "string",
            "isContent": false
        },
        "mediaAlign": {
            "type": "string",
            "isContent": false
        },
        "tabTitles": {
            "type": "array",
            "isContent": true,
            "example": [
                {
                    "text": "Tab Title 1",
                    "id": "1",
                    "media": "icon",
                    "icon": "fas fa-home",
                    "image": "",
                    "isExpanded": true,
                    "isDefault": true,
                    "customId": ""
                },
                {
                    "text": "Tab Title 2",
                    "id": "2",
                    "media": "icon",
                    "icon": "fas fa-home",
                    "image": "",
                    "isExpanded": false,
                    "isDefault": false,
                    "customId": ""
                }
            ]
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/tab": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "isActive": {
            "type": "boolean",
            "isContent": false
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/popup": {
        "displayCloseIcon": {
            "type": "boolean",
            "isContent": false
        },
        "useCloseIcon": {
            "type": "boolean",
            "isContent": false
        },
        "closeBtnText": {
            "type": "string",
            "isContent": true
        },
        "innerBlocks": {
            "type": "array",
            "isContent": false
        }
    },

    "essential-blocks/advanced-heading": {
        "titleText": {
            "type": "string",
            "isContent": true
        },
        "subtitleText": {
            "type": "string",
            "isContent": true
        },
        "tagName": {
            "type": "string",
            "isContent": false
        },
        "subtitleTagName": {
            "type": "string",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "subtitleColor": {
            "type": "string",
            "isContent": false
        },
        "displaySubtitle": {
            "type": "boolean",
            "isContent": false
        },
        "displaySeperator": {
            "type": "boolean",
            "isContent": false
        },
        "seperatorPosition": {
            "type": "string",
            "isContent": false
        },
        "seperatorType": {
            "type": "string",
            "isContent": false
        },
        "separatorColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/text": {
        "text": {
            "type": "string",
            "isContent": true
        },
        "tagName": {
            "type": "string",
            "isContent": false
        },
        "color": {
            "type": "string",
            "isContent": false
        },
        "align": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/button": {
        "buttonText": {
            "type": "string",
            "isContent": true
        },
        "buttonURL": {
            "type": "string",
            "isContent": false
        },
        "buttonAlign": {
            "type": "string",
            "isContent": false
        },
        "textColor": {
            "type": "string",
            "isContent": false
        },
        "addIcon": {
            "type": "boolean",
            "isContent": false
        },
        "icon": {
            "type": "string",
            "isContent": false
        },
        "iconPosition": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/icon": {
        "icon": {
            "type": "string",
            "isContent": false
        },
        "iconAlign": {
            "type": "string",
            "isContent": false
        },
        "iconPrimaryColor": {
            "type": "string",
            "isContent": false
        },
        "iconView": {
            "type": "string",
            "isContent": false
        },
        "iconShape": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/infobox": {
        "media": {
            "type": "string",
            "isContent": false
        },
        "selectedIcon": {
            "type": "string",
            "isContent": true
        },
        "title": {
            "type": "string",
            "isContent": true
        },
        "description": {
            "type": "string",
            "isContent": true
        },
        "buttonText": {
            "type": "string",
            "isContent": true
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "descriptionColor": {
            "type": "string",
            "isContent": false
        },
        "iconColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/team-member": {
        "name": {
            "type": "string",
            "isContent": true
        },
        "jobTitle": {
            "type": "string",
            "isContent": true
        },
        "description": {
            "type": "string",
            "isContent": true
        },
        "imageNewUrl": {
            "type": "string",
            "isContent": false
        },
        "showDescs": {
            "type": "boolean",
            "isContent": false
        },
        "showDesignation": {
            "type": "boolean",
            "isContent": false
        },
        "nameColor": {
            "type": "string",
            "isContent": false
        },
        "jobColor": {
            "type": "string",
            "isContent": false
        },
        "descColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/testimonial": {
        "userName": {
            "type": "string",
            "isContent": true
        },
        "companyName": {
            "type": "string",
            "isContent": true
        },
        "userDescription": {
            "type": "string",
            "isContent": true
        },
        "displayAvatar": {
            "type": "boolean",
            "isContent": false
        },
        "avatarAlign": {
            "type": "string",
            "isContent": false
        },
        "imageUrl": {
            "type": "string",
            "isContent": false
        },
        "userNameColor": {
            "type": "string",
            "isContent": false
        },
        "companyColor": {
            "type": "string",
            "isContent": false
        },
        "descriptionColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/call-to-action": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "description": {
            "type": "string",
            "isContent": true
        },
        "showIcon": {
            "type": "boolean",
            "isContent": false
        },
        "icon": {
            "type": "string",
            "isContent": false
        },
        "contentAlign": {
            "type": "string",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "descriptionColor": {
            "type": "string",
            "isContent": false
        },
        "iconColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/notice": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "text": {
            "type": "string",
            "isContent": true
        },
        "noticeType": {
            "type": "string",
            "isContent": false
        },
        "dismissible": {
            "type": "boolean",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "textColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/number-counter": {
        "target": {
            "type": "string",
            "isContent": false
        },
        "startValue": {
            "type": "string",
            "isContent": false
        },
        "counterTitle": {
            "type": "string",
            "isContent": true
        },
        "counterPrefix": {
            "type": "string",
            "isContent": true
        },
        "counterSuffix": {
            "type": "string",
            "isContent": true
        },
        "media": {
            "type": "string",
            "isContent": false
        },
        "selectedIcon": {
            "type": "string",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "numberColor": {
            "type": "string",
            "isContent": false
        },
        "iconColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/progress-bar": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "progress": {
            "type": "number",
            "isContent": false
        },
        "totalRange": {
            "type": "number",
            "isContent": false
        },
        "prefix": {
            "type": "string",
            "isContent": true
        },
        "suffix": {
            "type": "string",
            "isContent": true
        },
        "layout": {
            "type": "string",
            "isContent": false
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "progressColor": {
            "type": "string",
            "isContent": false
        },
        "trackColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/pricing-table": {
        "title": {
            "type": "string",
            "isContent": true
        },
        "subtitle": {
            "type": "string",
            "isContent": true
        },
        "showSubtitle": {
            "type": "boolean",
            "isContent": false
        },
        "price": {
            "type": "string",
            "isContent": true
        },
        "priceCurrency": {
            "type": "string",
            "isContent": true
        },
        "pricePeriod": {
            "type": "string",
            "isContent": true
        },
        "features": {
            "type": "array",
            "isContent": true
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "subtitleColor": {
            "type": "string",
            "isContent": false
        },
        "priceColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/flipbox": {
        "frontTitle": {
            "type": "string",
            "isContent": true
        },
        "frontContent": {
            "type": "string",
            "isContent": true
        },
        "backTitle": {
            "type": "string",
            "isContent": true
        },
        "backContent": {
            "type": "string",
            "isContent": true
        },
        "showFrontTitle": {
            "type": "boolean",
            "isContent": false
        },
        "showFrontContent": {
            "type": "boolean",
            "isContent": false
        },
        "showBackTitle": {
            "type": "boolean",
            "isContent": false
        },
        "showBackContent": {
            "type": "boolean",
            "isContent": false
        },
        "buttonText": {
            "type": "string",
            "isContent": true
        },
        "frontTitleColor": {
            "type": "string",
            "isContent": false
        },
        "frontContentColor": {
            "type": "string",
            "isContent": false
        },
        "backTitleColor": {
            "type": "string",
            "isContent": false
        },
        "backContentColor": {
            "type": "string",
            "isContent": false
        },
        "buttonColor": {
            "type": "string",
            "isContent": false
        },
        "buttonBackground": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/countdown": {
        "endDateTime": {
            "type": "string",
            "isContent": false
        },
        "daysLabel": {
            "type": "string",
            "isContent": true
        },
        "hoursLabel": {
            "type": "string",
            "isContent": true
        },
        "minutesLabel": {
            "type": "string",
            "isContent": true
        },
        "secondsLabel": {
            "type": "string",
            "isContent": true
        },
        "digitColor": {
            "type": "string",
            "isContent": false
        },
        "labelColor": {
            "type": "string",
            "isContent": false
        },
        "separatorColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/dual-button": {
        "buttonOneText": {
            "type": "string",
            "isContent": true
        },
        "buttonTwoText": {
            "type": "string",
            "isContent": true
        },
        "buttonOneURL": {
            "type": "string",
            "isContent": false
        },
        "buttonTwoURL": {
            "type": "string",
            "isContent": false
        },
        "connectorText": {
            "type": "string",
            "isContent": true
        },
        "buttonOneColor": {
            "type": "string",
            "isContent": false
        },
        "buttonTwoColor": {
            "type": "string",
            "isContent": false
        },
        "connectorColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/feature-list": {
        "features": {
            "type": "array",
            "isContent": true
        },
        "titleColor": {
            "type": "string",
            "isContent": false
        },
        "descriptionColor": {
            "type": "string",
            "isContent": false
        },
        "iconColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/typing-text": {
        "prefix": {
            "type": "string",
            "isContent": true
        },
        "typedText": {
            "type": "array",
            "isContent": true
        },
        "suffix": {
            "type": "string",
            "isContent": true
        },
        "prefixColor": {
            "type": "string",
            "isContent": false
        },
        "typedTextColor": {
            "type": "string",
            "isContent": false
        },
        "suffixTextColor": {
            "type": "string",
            "isContent": false
        },
        "typeSpeed": {
            "type": "number",
            "isContent": false
        },
        "backSpeed": {
            "type": "number",
            "isContent": false
        },
        "loop": {
            "type": "boolean",
            "isContent": false
        }
    },

    "essential-blocks/toggle-content": {
        "toggleText": {
            "type": "string",
            "isContent": true
        },
        "toggleContent": {
            "type": "string",
            "isContent": true
        },
        "initialToggleState": {
            "type": "boolean",
            "isContent": false
        },
        "toggleColor": {
            "type": "string",
            "isContent": false
        },
        "contentColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/interactive-promo": {
        "header": {
            "type": "string",
            "isContent": true
        },
        "content": {
            "type": "string",
            "isContent": true
        },
        "headerColor": {
            "type": "string",
            "isContent": false
        },
        "contentColor": {
            "type": "string",
            "isContent": false
        }
    },

    "essential-blocks/social": {
        "socialDetails": {
            "type": "array",
            "isContent": false
        },
        "iconsJustify": {
            "type": "string",
            "isContent": false
        }
    }
};

export default essentialBlocksSchema;
