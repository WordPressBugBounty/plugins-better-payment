/**
 * WordPress Dependencies
 */
import { __ } from "@wordpress/i18n";
import { useMemo, useEffect, useState } from "@wordpress/element";
import { Button, Modal } from "@wordpress/components";
import {
    BlockPreview,
    store as blockEditorStore
} from '@wordpress/block-editor';
import { select, dispatch, useSelect } from "@wordpress/data";
import { parse, createBlock } from '@wordpress/blocks';
import { times } from "lodash";

import NoticeComponent from '../NoticeComponent'
import './style.scss';
import { TemplateProIcon, InsertTemplateIcon, } from "./icon";

import loader from './loader.gif'

const BrowseTemplate = (props) => {
    const { clientId, name, patterns, attributes, setAttributes, handleBlank = false, setHandleBlank = () => { }, startTempateIndex } = props
    const [isChooseTemplateClicked, setChooseTemplateClicked] = useState(false)
    const [isTemplateInserted, setIsTemplateInserted] = useState(false)
    const { showBlockContent, blockId } = attributes

    const templates = useMemo(() => patterns)

    const { replaceBlock } = dispatch('core/block-editor');

    const wasBlockJustInserted = useSelect((select) => {
        return select(blockEditorStore).wasBlockJustInserted(clientId)
    })

    useEffect(() => {
        if (!blockId && wasBlockJustInserted) {
            setAttributes({ showBlockContent: false })
        }
        else if (showBlockContent === true) {
            setIsTemplateInserted(true)
        }
    }, [])

    useEffect(() => {
        if (isTemplateInserted) {
            setAttributes({ showBlockContent: true })
        }
    }, [isTemplateInserted])

    const chooseTemplate = () => {
        setChooseTemplateClicked(true)
    }

    /**
     * Function: InsertTemplate
     * @param {*} index
     */
    const insertTemplate = (index) => {
        if (!templates[index].content) {
            return;
        }
        const blockObj = parse(templates[index].content)
        blockObj[0].clientId = clientId //set clientId to current clientId
        blockObj[0].attributes.blockId = blockId //set blockId to current blockId
        replaceBlock(clientId, blockObj)
        setChooseTemplateClicked(false)
        setIsTemplateInserted(true)
    }

    /**
     * Function: startBlank
     */
    const startBlank = () => {
        handleBlank ? setHandleBlank(false) : setHandleBlank(true);

        if (startTempateIndex === undefined) {
            const blockObj = createBlock(name, attributes)
            blockObj.clientId = clientId //set clientId to current clientId
            replaceBlock(clientId, [blockObj])
            setIsTemplateInserted(true)
        } else {
            insertTemplate(startTempateIndex)
        }
    }

    const getPreviewUrl = (urlPath) => {
        const blockname = name.substring(name.lastIndexOf("/") + 1);
        const isPro = blockname.startsWith('pro-') ? true : false;
        const previewUrl = isPro ? `${EssentialBlocksProLocalize?.image_url}/${urlPath}` : `${EssentialBlocksLocalize?.image_url}/${urlPath}`
        return previewUrl;
    }

    // initialize Isotope
    useEffect(() => {
        const templateItemWrapper = document.querySelector(`.eb-choose-templates`);

        if (templateItemWrapper) {
            let iso;
            setTimeout(function () {
                imagesLoaded(templateItemWrapper, function () {
                    iso = new Isotope(templateItemWrapper, {
                        itemSelector: ".eb-choose-template",
                        percentPosition: true,
                        masonry: {
                            columnWidth: ".eb-choose-template",
                            horizontalOrder: true,
                        },
                    });
                });
            }, 2000);
        }

        const element = document.querySelector(`.template-preview-loader`);
        if (element) {
            setTimeout(() => {
                element.style.display = 'none';
            }, 3000)
        }
    }, [isChooseTemplateClicked]);

    return (
        <>
            {!isTemplateInserted && (
                <NoticeComponent {...props}>
                    <div className="browse-component-buttons">
                        <Button
                            onClick={() => chooseTemplate()}
                            variant="primary">Choose</Button>
                        <Button
                            onClick={() => startBlank()}
                            variant="secondary"
                        >Start Blank</Button>
                    </div>
                </NoticeComponent>
            )}

            {isChooseTemplateClicked && (
                <Modal
                    overlayClassName="block-library-query-pattern__selection-modal eb-templates-modal"
                    title={__('Choose a Template')}
                    onRequestClose={() => setChooseTemplateClicked(false)}
                    isFullScreen={true}
                >
                    {typeof templates === 'object' && templates.length > 0 && (
                        <>
                            <div className="template-preview-loader">
                                <img src={loader} />
                            </div>

                            <div className={`eb-choose-templates`}>
                                {times(templates.length, (index) => {
                                    return (
                                        <div
                                            key={index}
                                            className='eb-choose-template'
                                        >
                                            <div className="eb-choose-template-content">
                                                <>
                                                    {templates[index].hasOwnProperty('pro') ?
                                                        <>
                                                            <span className="eb-template-pro-badge"><TemplateProIcon /> PRO</span>
                                                            {
                                                                EssentialBlocksLocalize?.is_pro_active === "true" ?
                                                                    <button
                                                                        className="eb-template-insert"
                                                                        onClick={() => insertTemplate(index)}
                                                                    >
                                                                        <InsertTemplateIcon /> Click to Insert
                                                                    </button> :
                                                                    <a href="https://essential-blocks.com/#pricing" target="_blank" className="eb-template-pro-upgrade"><TemplateProIcon /> Upgrade To Pro</a>
                                                            }
                                                        </>
                                                        :
                                                        <button
                                                            className="eb-template-insert"
                                                            onClick={() => insertTemplate(index)}
                                                        >
                                                            <InsertTemplateIcon /> Click to Insert
                                                        </button>
                                                    }

                                                    {
                                                        templates[index].hasOwnProperty('templatePreview') && templates[index].hasOwnProperty('preview') ?
                                                            <div className="template-preview">
                                                                <img src={getPreviewUrl(templates[index].preview)} />
                                                                {/* <img src={templates[index].preview} /> */}
                                                            </div> :
                                                            <BlockPreview blocks={parse(templates[index].content)} viewportWidth={1100} />
                                                    }
                                                </>
                                            </div>

                                            <p className='eb-choose-template-title'>{templates[index].title}</p>
                                        </div>
                                    )
                                })
                                }
                            </div>
                        </>

                    )}
                </Modal >
            )}
        </>
    )
}

export default BrowseTemplate;
