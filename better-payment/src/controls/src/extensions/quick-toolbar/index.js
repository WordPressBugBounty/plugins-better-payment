import domReady from "@wordpress/dom-ready";
import { createRoot } from "react-dom";
import Sidebar from "./sidebar";
import { useEffect, useState } from "@wordpress/element";
import { select } from '@wordpress/data';

/**
 * SidebarManager React Component
 * Handles attaching, detaching, and toggling the sidebar based on editor state.
 */
const SidebarManager = () => {
    const [isSidebarVisible, setIsSidebarVisible] = useState(false);
    // Get the current editor state and determine if the sidebar should be visible
    const isFSE = window.location.pathname === "/wp-admin/site-editor.php";

    const isQuickToolbarEnabled =
        EssentialBlocksLocalize?.quickToolbar !== "false";

    useEffect(() => {
        if (!isQuickToolbarEnabled) return;

        if (isFSE) {
            const currentUrl = new URL(window.location.href);
            const isEditable = currentUrl.searchParams.get("canvas") === "edit";
            setIsSidebarVisible(isEditable);

            // For FSE, listen to navigation events to toggle sidebar visibility
            const handleNavigation = (e) => {
                const newUrl = new URL(e.destination.url);
                const shouldShowSidebar = newUrl.searchParams.get("canvas") === "edit";
                if (shouldShowSidebar) {
                    const skeletonInterval = setInterval(() => {
                        const skeleton = document.querySelector('.edit-site-editor__interface-skeleton') || document.querySelector('.editor-editor-interface.edit-site-editor__editor-interface'); // For Old user with 6.5 and below || For New user with 6.6 and above.
                        if (skeleton) {
                            if (!skeleton.classList.contains('is-loading')) {
                                clearInterval(skeletonInterval);
                                setIsSidebarVisible(true);
                            }
                        }
                    }
                        , 100);
                } else {
                    setIsSidebarVisible(false);
                    const container = document.querySelector('.eb-quick-toolbar');
                    if (container) {
                        container.parentElement.remove();
                    }
                }
            };

            if (window.navigation && window.navigation.addEventListener) {
                window.navigation.addEventListener("navigate", handleNavigation);
                return () => window.navigation.removeEventListener("navigate", handleNavigation);
            } else {
                console.warn("Navigation API not supported in this environment.");
            }
        } else {
            // In non-FSE editors, show the sidebar by default
            setIsSidebarVisible(true);
        }
    }, [isFSE, isQuickToolbarEnabled]);

    useEffect(() => {
        if (isSidebarVisible) {
            attachSidebar();
        } else {
            detachSidebar();
        }
    }, [isSidebarVisible]);

    return null; // This component doesn't render anything directly
};

/**
 * Attaches the sidebar to the DOM.
 */
const attachSidebar = () => {
    const interval = setInterval(() => {
        const rootElement = document.querySelector(
            ".interface-interface-skeleton__body"
        );
        if (!rootElement) return;

        const blockElement = document.querySelector('.eb_quick_toolbar__sidebar--blocks--block');
        if (blockElement) {
            clearInterval(interval);
            return;
        }

        clearInterval(interval);

        let container = rootElement.querySelector(".eb-quick-toolbar-container");
        if (!container) {
            const parentDiv = rootElement.insertBefore(
                document.createElement("div"),
                rootElement.firstChild
            );
            container = parentDiv.appendChild(document.createElement("div"));
            container.classList.add("eb-quick-toolbar-container");
        }

        const root = createRoot(container);
        root.render(<Sidebar />);
    }, 100);
};

/**
 * Detaches the sidebar from the DOM.
 */
const detachSidebar = () => {
    const container = document.querySelector(".eb-quick-toolbar-container");
    if (container) {
        container.remove();
    }
};

// Initialize the SidebarManager when the DOM is ready
domReady(() => {
    const blankDiv = document.createElement("div");
    document.body.appendChild(blankDiv);
    createRoot(blankDiv).render(<SidebarManager />);
});
