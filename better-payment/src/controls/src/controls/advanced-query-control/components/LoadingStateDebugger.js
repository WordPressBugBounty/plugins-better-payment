/**
 * Loading State Debugger Component
 * 
 * A debug component to help identify and troubleshoot loading state issues
 * in the Advanced Query Control system.
 */

import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { Notice, Button, PanelBody } from "@wordpress/components";

/**
 * Loading State Debugger Component
 * 
 * @param {Object} props Component props
 * @param {boolean} props.isTaxonomyLoading Taxonomy loading state
 * @param {boolean} props.isQueryLoading Query loading state
 * @param {boolean} props.isParentLoading Parent component loading state
 * @param {Array} props.queryResults Current query results
 * @param {Object} props.queryData Current query data
 * @param {string} props.error Current error state
 * @param {boolean} props.enabled Whether debugging is enabled
 */
function LoadingStateDebugger({
    isTaxonomyLoading = false,
    isQueryLoading = false,
    isParentLoading = false,
    queryResults = [],
    queryData = {},
    error = null,
    enabled = false
}) {
    const [debugHistory, setDebugHistory] = useState([]);
    const [isExpanded, setIsExpanded] = useState(false);

    // Track state changes
    useEffect(() => {
        if (!enabled) return;

        const timestamp = new Date().toISOString();
        const newEntry = {
            timestamp,
            isTaxonomyLoading,
            isQueryLoading,
            isParentLoading,
            queryResultsCount: queryResults.length,
            hasQueryData: Object.keys(queryData).length > 0,
            hasError: !!error,
            errorMessage: error?.message || error
        };

        setDebugHistory(prev => {
            const updated = [...prev, newEntry];
            // Keep only last 20 entries
            return updated.slice(-20);
        });
    }, [enabled, isTaxonomyLoading, isQueryLoading, isParentLoading, queryResults.length, queryData, error]);

    // Clear debug history
    const clearHistory = () => {
        setDebugHistory([]);
    };

    // Check for potential issues
    const detectIssues = () => {
        const issues = [];
        
        // Check for stuck loading states
        const recentEntries = debugHistory.slice(-5);
        const stuckTaxonomyLoading = recentEntries.every(entry => entry.isTaxonomyLoading);
        const stuckQueryLoading = recentEntries.every(entry => entry.isQueryLoading);
        const stuckParentLoading = recentEntries.every(entry => entry.isParentLoading);

        if (stuckTaxonomyLoading && recentEntries.length >= 3) {
            issues.push("Taxonomy loading appears to be stuck");
        }
        
        if (stuckQueryLoading && recentEntries.length >= 3) {
            issues.push("Query loading appears to be stuck");
        }
        
        if (stuckParentLoading && recentEntries.length >= 3) {
            issues.push("Parent loading appears to be stuck");
        }

        // Check for conflicting states
        const latestEntry = debugHistory[debugHistory.length - 1];
        if (latestEntry) {
            if (latestEntry.isQueryLoading && latestEntry.queryResultsCount > 0) {
                issues.push("Query is loading but results already exist");
            }
            
            if (!latestEntry.isQueryLoading && !latestEntry.hasError && latestEntry.queryResultsCount === 0 && latestEntry.hasQueryData) {
                issues.push("Query completed but no results found");
            }
        }

        return issues;
    };

    const issues = detectIssues();

    if (!enabled) {
        return null;
    }

    return (
        <PanelBody
            title={__("🐛 Loading State Debugger", "essential-blocks")}
            initialOpen={false}
            className="eb-loading-state-debugger"
        >
            {/* Current State */}
            <div style={{ 
                padding: '12px', 
                backgroundColor: '#f8f9fa', 
                border: '1px solid #dee2e6',
                borderRadius: '4px',
                marginBottom: '12px'
            }}>
                <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>
                    {__("Current State", "essential-blocks")}
                </h4>
                <div style={{ fontSize: '12px', fontFamily: 'monospace' }}>
                    <div>🔄 Taxonomy Loading: <strong>{isTaxonomyLoading ? 'TRUE' : 'FALSE'}</strong></div>
                    <div>⚡ Query Loading: <strong>{isQueryLoading ? 'TRUE' : 'FALSE'}</strong></div>
                    <div>📊 Parent Loading: <strong>{isParentLoading ? 'TRUE' : 'FALSE'}</strong></div>
                    <div>📝 Query Results: <strong>{queryResults.length}</strong></div>
                    <div>🔧 Has Query Data: <strong>{Object.keys(queryData).length > 0 ? 'TRUE' : 'FALSE'}</strong></div>
                    <div>❌ Has Error: <strong>{error ? 'TRUE' : 'FALSE'}</strong></div>
                </div>
            </div>

            {/* Issues Detection */}
            {issues.length > 0 && (
                <Notice status="warning" isDismissible={false}>
                    <div>
                        <strong>{__("⚠️ Potential Issues Detected:", "essential-blocks")}</strong>
                        <ul style={{ margin: '8px 0 0 0', paddingLeft: '20px' }}>
                            {issues.map((issue, index) => (
                                <li key={index}>{issue}</li>
                            ))}
                        </ul>
                    </div>
                </Notice>
            )}

            {/* Debug History */}
            <div style={{ marginTop: '12px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                    <Button
                        isSecondary
                        isSmall
                        onClick={() => setIsExpanded(!isExpanded)}
                    >
                        {isExpanded ? __("Hide History", "essential-blocks") : __("Show History", "essential-blocks")}
                        {debugHistory.length > 0 && ` (${debugHistory.length})`}
                    </Button>
                    
                    <Button
                        isDestructive
                        isSmall
                        onClick={clearHistory}
                        disabled={debugHistory.length === 0}
                    >
                        {__("Clear", "essential-blocks")}
                    </Button>
                </div>

                {isExpanded && (
                    <div style={{ 
                        maxHeight: '200px', 
                        overflowY: 'auto',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        padding: '8px',
                        backgroundColor: '#fff',
                        fontSize: '11px',
                        fontFamily: 'monospace'
                    }}>
                        {debugHistory.length === 0 ? (
                            <div style={{ fontStyle: 'italic', color: '#666' }}>
                                {__("No debug history yet. State changes will appear here.", "essential-blocks")}
                            </div>
                        ) : (
                            debugHistory.slice().reverse().map((entry, index) => (
                                <div key={index} style={{ 
                                    marginBottom: '4px', 
                                    paddingBottom: '4px',
                                    borderBottom: index < debugHistory.length - 1 ? '1px solid #eee' : 'none'
                                }}>
                                    <div style={{ color: '#666', fontSize: '10px' }}>
                                        {new Date(entry.timestamp).toLocaleTimeString()}
                                    </div>
                                    <div>
                                        T:{entry.isTaxonomyLoading ? '✓' : '✗'} | 
                                        Q:{entry.isQueryLoading ? '✓' : '✗'} | 
                                        P:{entry.isParentLoading ? '✓' : '✗'} | 
                                        R:{entry.queryResultsCount} | 
                                        D:{entry.hasQueryData ? '✓' : '✗'} | 
                                        E:{entry.hasError ? '✓' : '✗'}
                                    </div>
                                    {entry.hasError && entry.errorMessage && (
                                        <div style={{ color: '#d63638', fontSize: '10px' }}>
                                            Error: {entry.errorMessage}
                                        </div>
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                )}
            </div>

            {/* Legend */}
            {isExpanded && (
                <div style={{ 
                    marginTop: '8px', 
                    padding: '8px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '4px',
                    fontSize: '10px',
                    color: '#666'
                }}>
                    <strong>{__("Legend:", "essential-blocks")}</strong> T=Taxonomy, Q=Query, P=Parent, R=Results, D=Data, E=Error | ✓=True, ✗=False
                </div>
            )}

            {/* Quick Actions */}
            <div style={{ marginTop: '12px', fontSize: '12px', color: '#666' }}>
                <strong>{__("Quick Fixes:", "essential-blocks")}</strong>
                <ul style={{ margin: '4px 0 0 0', paddingLeft: '16px' }}>
                    <li>{__("If loading is stuck: Check browser console for errors", "essential-blocks")}</li>
                    <li>{__("If results don't update: Verify onQueryChange callback", "essential-blocks")}</li>
                    <li>{__("If infinite loading: Check useEffect dependencies", "essential-blocks")}</li>
                </ul>
            </div>
        </PanelBody>
    );
}

export default LoadingStateDebugger;
