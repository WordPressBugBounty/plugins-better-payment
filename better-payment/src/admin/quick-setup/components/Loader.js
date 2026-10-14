/**
 * Loader Component
 * 
 * Displays a loading indicator during async operations
 */

export default function Loader({ loading }) {
    if (!loading) return null;

    return (
        <div className="bp-loader-overlay">
            <div className="bp-loader">
                <div className="bp-loader__spinner"></div>
                <p className="bp-loader__text">Processing...</p>
            </div>
        </div>
    );
}

