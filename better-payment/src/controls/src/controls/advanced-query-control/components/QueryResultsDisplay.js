/**
 * Query Results Display Component
 * 
 * A reusable component for displaying query results with loading states,
 * error handling, and result previews.
 */

import { __ } from "@wordpress/i18n";
import {
    PanelBody,
    Notice,
    Spinner,
    Button,
    BaseControl,
    __experimentalSpacer as Spacer,
} from "@wordpress/components";
import { memo } from "@wordpress/element";

/**
 * Query Results Display Component
 * 
 * @param {Object} props Component props
 * @param {Array} props.results Query results array
 * @param {boolean} props.isLoading Whether query is currently loading
 * @param {string|Object} props.error Error message or object
 * @param {string} props.lastExecuted Last execution timestamp
 * @param {Function} props.onRetry Retry callback function
 * @param {Function} props.onClear Clear results callback function
 * @param {Object} props.displayOptions Display configuration options
 * @param {string} props.title Panel title
 * @param {boolean} props.initialOpen Whether panel should be initially open
 * @param {string} props.className Additional CSS class
 */
function QueryResultsDisplay({
    results = [],
    isLoading = false,
    error = null,
    lastExecuted = null,
    onRetry = () => {},
    onClear = () => {},
    displayOptions = {},
    title = __("Query Results", "essential-blocks"),
    initialOpen = false,
    className = "eb-query-results-display"
}) {
    const {
        showPreview = true,
        maxPreviewItems = 5,
        showTimestamp = true,
        showControls = true,
        showPostIds = true,
        showPostDates = false,
        compactMode = false
    } = displayOptions;

    const hasResults = results && results.length > 0;
    const hasError = !!error;

    // Don't render if no results, loading, or error to show
    if (!hasResults && !isLoading && !hasError) {
        return null;
    }

    return (
        <PanelBody
            title={title}
            initialOpen={initialOpen}
            className={className}
        >
            {/* Loading State */}
            {isLoading && (
                <Notice status="info" isDismissible={false}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        <Spinner />
                        {__("Executing query...", "essential-blocks")}
                    </div>
                </Notice>
            )}

            {/* Error State */}
            {hasError && (
                <Notice status="error" isDismissible={true}>
                    <div>
                        <p>
                            {__("Query Error: ", "essential-blocks")}
                            {typeof error === 'string' ? error : error.message || 'Unknown error'}
                        </p>
                        {showControls && (
                            <Button
                                isSecondary
                                isSmall
                                onClick={onRetry}
                                style={{ marginTop: '8px' }}
                            >
                                {__("Retry Query", "essential-blocks")}
                            </Button>
                        )}
                    </div>
                </Notice>
            )}

            {/* Success State with Results */}
            {hasResults && !isLoading && !hasError && (
                <>
                    <Notice status="success" isDismissible={false}>
                        <div>
                            <p>
                                {__("Query executed successfully. Found ", "essential-blocks")}
                                <strong>{results.length}</strong>
                                {results.length === 1 ? __(" post.", "essential-blocks") : __(" posts.", "essential-blocks")}
                            </p>
                            {showTimestamp && lastExecuted && (
                                <p style={{ fontSize: '12px', marginTop: '4px', opacity: 0.7 }}>
                                    {__("Last updated: ", "essential-blocks")}
                                    {new Date(lastExecuted).toLocaleTimeString()}
                                </p>
                            )}
                        </div>
                    </Notice>

                    {/* Results Preview */}
                    {showPreview && (
                        <div className="eb-query-results-preview" style={{ marginTop: '12px' }}>
                            <BaseControl
                                label={__("Results Preview", "essential-blocks")}
                                className="eb-query-results-preview-control"
                            >
                                <div style={{ 
                                    border: '1px solid #ddd', 
                                    borderRadius: '4px', 
                                    padding: compactMode ? '8px' : '12px',
                                    backgroundColor: '#f9f9f9',
                                    maxHeight: compactMode ? '100px' : '200px',
                                    overflowY: 'auto'
                                }}>
                                    {compactMode ? (
                                        <p style={{ margin: 0, fontSize: '12px' }}>
                                            {results.slice(0, 3).map(post => 
                                                post.title?.rendered || post.title || __("Untitled", "essential-blocks")
                                            ).join(', ')}
                                            {results.length > 3 && ` ... +${results.length - 3} more`}
                                        </p>
                                    ) : (
                                        <ul style={{ 
                                            margin: 0, 
                                            paddingLeft: '16px', 
                                            fontSize: '12px'
                                        }}>
                                            {results.slice(0, maxPreviewItems).map((post, index) => (
                                                <li key={post.id || index} style={{ marginBottom: '4px' }}>
                                                    <strong>
                                                        {post.title?.rendered || post.title || __("Untitled", "essential-blocks")}
                                                    </strong>
                                                    {showPostIds && (
                                                        <span style={{ color: '#666', marginLeft: '8px' }}>
                                                            (ID: {post.id})
                                                        </span>
                                                    )}
                                                    {showPostDates && post.date && (
                                                        <span style={{ color: '#666', marginLeft: '8px' }}>
                                                            - {new Date(post.date).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                </li>
                                            ))}
                                            {results.length > maxPreviewItems && (
                                                <li style={{ fontStyle: 'italic', color: '#666', marginTop: '8px' }}>
                                                    {__("... and ", "essential-blocks")}
                                                    {results.length - maxPreviewItems}
                                                    {__(" more posts", "essential-blocks")}
                                                </li>
                                            )}
                                        </ul>
                                    )}
                                </div>
                            </BaseControl>
                        </div>
                    )}

                    {/* Control Buttons */}
                    {showControls && (
                        <div style={{ 
                            display: 'flex', 
                            gap: '8px', 
                            marginTop: '12px',
                            flexWrap: 'wrap'
                        }}>
                            <Button
                                isSecondary
                                isSmall
                                onClick={onClear}
                            >
                                {__("Clear Results", "essential-blocks")}
                            </Button>
                            
                            <Button
                                isSecondary
                                isSmall
                                onClick={onRetry}
                            >
                                {__("Refresh", "essential-blocks")}
                            </Button>
                        </div>
                    )}
                </>
            )}

            {/* No Results State */}
            {!hasResults && !isLoading && !hasError && (
                <Notice status="warning" isDismissible={false}>
                    <p>{__("No posts found with current query parameters.", "essential-blocks")}</p>
                </Notice>
            )}
        </PanelBody>
    );
}

/**
 * Compact Query Status Component
 * 
 * A minimal status indicator for query state
 */
export const QueryStatus = memo(({ 
    results = [], 
    isLoading = false, 
    error = null,
    className = "eb-query-status"
}) => {
    if (isLoading) {
        return (
            <div className={`${className} ${className}--loading`} style={{ 
                display: 'flex', 
                alignItems: 'center', 
                gap: '8px',
                padding: '8px',
                backgroundColor: '#f0f6fc',
                border: '1px solid #0073aa',
                borderRadius: '4px',
                fontSize: '12px'
            }}>
                <Spinner style={{ width: '16px', height: '16px' }} />
                {__("Loading...", "essential-blocks")}
            </div>
        );
    }

    if (error) {
        return (
            <div className={`${className} ${className}--error`} style={{ 
                padding: '8px',
                backgroundColor: '#fcf0f1',
                border: '1px solid #d63638',
                borderRadius: '4px',
                fontSize: '12px',
                color: '#d63638'
            }}>
                {__("Error: ", "essential-blocks")} {typeof error === 'string' ? error : error.message}
            </div>
        );
    }

    if (results.length > 0) {
        return (
            <div className={`${className} ${className}--success`} style={{ 
                padding: '8px',
                backgroundColor: '#f0f6fc',
                border: '1px solid #00a32a',
                borderRadius: '4px',
                fontSize: '12px',
                color: '#00a32a'
            }}>
                {results.length} {results.length === 1 ? __("post found", "essential-blocks") : __("posts found", "essential-blocks")}
            </div>
        );
    }

    return (
        <div className={`${className} ${className}--empty`} style={{ 
            padding: '8px',
            backgroundColor: '#fcf9e8',
            border: '1px solid #dba617',
            borderRadius: '4px',
            fontSize: '12px',
            color: '#dba617'
        }}>
            {__("No posts found", "essential-blocks")}
        </div>
    );
});

export default memo(QueryResultsDisplay);
