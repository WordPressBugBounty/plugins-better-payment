import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// ── Color math helpers ────────────────────────────────────────────────────────

function clamp( v, lo, hi ) { return Math.max( lo, Math.min( hi, v ) ); }

function hsvToRgb( h, s, v ) {
    const c = v * s;
    const x = c * ( 1 - Math.abs( ( ( h / 60 ) % 2 ) - 1 ) );
    const m = v - c;
    let r = 0, g = 0, b = 0;
    if ( h < 60 )       { r = c; g = x; }
    else if ( h < 120 ) { r = x; g = c; }
    else if ( h < 180 ) { g = c; b = x; }
    else if ( h < 240 ) { g = x; b = c; }
    else if ( h < 300 ) { r = x; b = c; }
    else                { r = c; b = x; }
    return [
        Math.round( ( r + m ) * 255 ),
        Math.round( ( g + m ) * 255 ),
        Math.round( ( b + m ) * 255 ),
    ];
}

function rgbToHsv( r, g, b ) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max( r, g, b ), min = Math.min( r, g, b ), d = max - min;
    let h = 0;
    const s = max === 0 ? 0 : d / max;
    const v = max;
    if ( d !== 0 ) {
        if ( max === r )      h = ( ( g - b ) / d + ( g < b ? 6 : 0 ) ) / 6;
        else if ( max === g ) h = ( ( b - r ) / d + 2 ) / 6;
        else                  h = ( ( r - g ) / d + 4 ) / 6;
    }
    return { h: h * 360, s, v };
}

function hexToRgb( hex ) {
    const c = hex.replace( '#', '' );
    const f = c.length === 3 ? c.split( '' ).map( x => x + x ).join( '' ) : c;
    return [
        parseInt( f.slice( 0, 2 ), 16 ),
        parseInt( f.slice( 2, 4 ), 16 ),
        parseInt( f.slice( 4, 6 ), 16 ),
    ];
}

function rgbToHex( r, g, b ) {
    return '#' + [ r, g, b ]
        .map( v => clamp( Math.round( v ), 0, 255 ).toString( 16 ).padStart( 2, '0' ) )
        .join( '' )
        .toUpperCase();
}

function hexToHsv( hex ) {
    try {
        const [ r, g, b ] = hexToRgb( hex );
        return rgbToHsv( r, g, b );
    } catch ( e ) {
        return { h: 0, s: 0, v: 0 };
    }
}

function hsvToHex( h, s, v ) {
    return rgbToHex( ...hsvToRgb( h, s, v ) );
}

function rgbToHsl( r, g, b ) {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max( r, g, b ), min = Math.min( r, g, b );
    const l = ( max + min ) / 2;
    let h = 0, s = 0;
    const d = max - min;
    if ( d !== 0 ) {
        s = d / ( 1 - Math.abs( 2 * l - 1 ) );
        if ( max === r )      h = ( ( g - b ) / d + ( g < b ? 6 : 0 ) ) / 6;
        else if ( max === g ) h = ( ( b - r ) / d + 2 ) / 6;
        else                  h = ( ( r - g ) / d + 4 ) / 6;
    }
    return { h: Math.round( h * 360 ), s: Math.round( s * 100 ), l: Math.round( l * 100 ) };
}

function hslToHex( h, s, l ) {
    s /= 100; l /= 100;
    const c = ( 1 - Math.abs( 2 * l - 1 ) ) * s;
    const x = c * ( 1 - Math.abs( ( h / 60 ) % 2 - 1 ) );
    const m = l - c / 2;
    let r = 0, g = 0, b = 0;
    if ( h < 60 )       { r = c; g = x; }
    else if ( h < 120 ) { r = x; g = c; }
    else if ( h < 180 ) { g = c; b = x; }
    else if ( h < 240 ) { g = x; b = c; }
    else if ( h < 300 ) { r = x; b = c; }
    else                { r = c; b = x; }
    return rgbToHex(
        Math.round( ( r + m ) * 255 ),
        Math.round( ( g + m ) * 255 ),
        Math.round( ( b + m ) * 255 ),
    );
}

// ── Constants ─────────────────────────────────────────────────────────────────

const PRESETS  = [ '#2B66D1', '#7BB8F0', '#5AA152', '#F0C030', '#E8613A', '#D4567A' ];
const CANVAS_W = 260;
const CANVAS_H = 130;

// ── Component ─────────────────────────────────────────────────────────────────

export default function ColorPicker( { value, defaultValue, onChange, onClose } ) {
    const safe    = /^#[0-9a-fA-F]{6}$/.test( value ) ? value : ( defaultValue || '#000000' );
    const initHsv = hexToHsv( safe );

    const [ hue,      setHue      ] = useState( initHsv.h );
    const [ sat,      setSat      ] = useState( initHsv.s );
    const [ val,      setVal      ] = useState( initHsv.v );
    const [ mode,     setMode     ] = useState( 'hex' );
    const [ hexInput, setHexInput ] = useState( safe.toUpperCase() );

    const canvasRef = useRef( null );
    const dragging  = useRef( false );

    const currentHex = hsvToHex( hue, sat, val );
    const [ r, g, b ] = hexToRgb( currentHex );
    const hsl         = rgbToHsl( r, g, b );

    // Draw SV gradient whenever hue changes.
    useEffect( () => {
        const canvas = canvasRef.current;
        if ( ! canvas ) return;
        const ctx = canvas.getContext( '2d' );
        const w = canvas.width, h = canvas.height;

        const gH = ctx.createLinearGradient( 0, 0, w, 0 );
        gH.addColorStop( 0, '#fff' );
        gH.addColorStop( 1, `hsl(${ hue },100%,50%)` );
        ctx.fillStyle = gH;
        ctx.fillRect( 0, 0, w, h );

        const gV = ctx.createLinearGradient( 0, 0, 0, h );
        gV.addColorStop( 0, 'rgba(0,0,0,0)' );
        gV.addColorStop( 1, '#000' );
        ctx.fillStyle = gV;
        ctx.fillRect( 0, 0, w, h );
    }, [ hue ] );

    const pickFromCanvas = useCallback( ( e ) => {
        const canvas = canvasRef.current;
        if ( ! canvas ) return;
        const rect = canvas.getBoundingClientRect();
        const x = clamp( ( e.clientX - rect.left ) / rect.width,  0, 1 );
        const y = clamp( ( e.clientY - rect.top  ) / rect.height, 0, 1 );
        const ns = x, nv = 1 - y;
        setSat( ns ); setVal( nv );
        const hex = hsvToHex( hue, ns, nv );
        setHexInput( hex );
        onChange( hex );
    }, [ hue, onChange ] );

    // Global mouse-move / up for canvas drag.
    useEffect( () => {
        const onMove = ( e ) => { if ( dragging.current ) pickFromCanvas( e ); };
        const onUp   = ()    => { dragging.current = false; };
        window.addEventListener( 'mousemove', onMove );
        window.addEventListener( 'mouseup',   onUp   );
        return () => {
            window.removeEventListener( 'mousemove', onMove );
            window.removeEventListener( 'mouseup',   onUp   );
        };
    }, [ pickFromCanvas ] );

    // Apply a hex string and sync all state.
    const applyHex = useCallback( ( hex ) => {
        const clean = hex.toUpperCase();
        const hsv   = hexToHsv( clean );
        setHue( hsv.h ); setSat( hsv.s ); setVal( hsv.v );
        setHexInput( clean );
        onChange( clean );
    }, [ onChange ] );

    const handleHueChange = ( e ) => {
        const h = parseFloat( e.target.value );
        setHue( h );
        const hex = hsvToHex( h, sat, val );
        setHexInput( hex );
        onChange( hex );
    };

    const handleHexInput = ( e ) => {
        const raw = e.target.value;
        setHexInput( raw );
        if ( /^#[0-9a-fA-F]{6}$/.test( raw ) ) {
            const hsv = hexToHsv( raw );
            setHue( hsv.h ); setSat( hsv.s ); setVal( hsv.v );
            onChange( raw.toUpperCase() );
        }
    };

    const handleRgbChange = ( channel ) => ( e ) => {
        const next = { r, g, b, [ channel ]: clamp( parseInt( e.target.value, 10 ) || 0, 0, 255 ) };
        applyHex( rgbToHex( next.r, next.g, next.b ) );
    };

    const handleHslChange = ( channel ) => ( e ) => {
        const limits = { h: [ 0, 360 ], s: [ 0, 100 ], l: [ 0, 100 ] };
        const [ lo, hi ] = limits[ channel ];
        const next = { ...hsl, [ channel ]: clamp( parseInt( e.target.value, 10 ) || 0, lo, hi ) };
        applyHex( hslToHex( next.h, next.s, next.l ) );
    };

    return (
        <div className="bp-color-picker">

            { /* Gradient canvas */ }
            <div className="bp-color-picker__canvas-wrap">
                <canvas
                    ref={ canvasRef }
                    width={ CANVAS_W }
                    height={ CANVAS_H }
                    className="bp-color-picker__canvas"
                    onMouseDown={ ( e ) => { dragging.current = true; pickFromCanvas( e ); } }
                />
                <div
                    className="bp-color-picker__cursor"
                    style={ { left: `${ sat * 100 }%`, top: `${ ( 1 - val ) * 100 }%` } }
                />
            </div>

            { /* Hue slider */ }
            <input
                type="range"
                className="bp-color-picker__hue"
                min="0" max="360" step="1"
                value={ Math.round( hue ) }
                onChange={ handleHueChange }
            />

            { /* Preview + mode input */ }
            <div className="bp-color-picker__preview-row">
                <div className="bp-color-picker__swatch-preview" style={ { background: currentHex } } />

                { mode === 'hex' && (
                    <input
                        type="text"
                        className="bp-color-picker__text-input"
                        value={ hexInput }
                        onChange={ handleHexInput }
                        onBlur={ () => setHexInput( currentHex ) }
                        maxLength={ 7 }
                        spellCheck={ false }
                    />
                ) }

                { mode === 'rgb' && (
                    <div className="bp-color-picker__channel-inputs">
                        { [ [ 'r', r ], [ 'g', g ], [ 'b', b ] ].map( ( [ ch, cv ] ) => (
                            <input key={ ch } type="number" min="0" max="255"
                                value={ cv } onChange={ handleRgbChange( ch ) } />
                        ) ) }
                    </div>
                ) }

                { mode === 'hsl' && (
                    <div className="bp-color-picker__channel-inputs">
                        { [ [ 'h', hsl.h, 360 ], [ 's', hsl.s, 100 ], [ 'l', hsl.l, 100 ] ].map( ( [ ch, cv, mx ] ) => (
                            <input key={ ch } type="number" min="0" max={ mx }
                                value={ cv } onChange={ handleHslChange( ch ) } />
                        ) ) }
                    </div>
                ) }
            </div>

            { /* Mode tabs */ }
            <div className="bp-color-picker__modes">
                { [ 'hex', 'rgb', 'hsl' ].map( ( m ) => (
                    <button key={ m } type="button"
                        className={ `bp-color-picker__mode-btn${ mode === m ? ' is-active' : '' }` }
                        onClick={ () => setMode( m ) }
                    >
                        { m.toUpperCase() }
                    </button>
                ) ) }
            </div>

            { /* Preset swatches */ }
            <div className="bp-color-picker__presets">
                { PRESETS.map( ( hex ) => (
                    <button key={ hex } type="button"
                        className="bp-color-picker__preset"
                        style={ { background: hex } }
                        onClick={ () => applyHex( hex ) }
                    />
                ) ) }
            </div>

            { /* Actions */ }
            <div className="bp-color-picker__actions">
                <button type="button" className="bp-color-picker__action-btn"
                    onClick={ () => applyHex( defaultValue || '#000000' ) }
                >
                    { __( 'Reset', 'better-payment' ) }
                </button>
                <button type="button" className="bp-color-picker__action-btn"
                    onClick={ onClose }
                >
                    { __( 'Close', 'better-payment' ) }
                </button>
            </div>
        </div>
    );
}
