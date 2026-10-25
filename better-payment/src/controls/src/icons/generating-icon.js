import React from 'react';

const GeneratingIcon = (props) => (
    <svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <circle cx="4" cy="12" r="2" fill={props.color || 'currentColor'}>
            <animate attributeName="opacity" values="0;1;0" dur="1.2s" repeatCount="indefinite" begin="0s" />
        </circle>
        <circle cx="12" cy="12" r="2" fill={props.color || 'currentColor'}>
            <animate attributeName="opacity" values="0;1;0" dur="1.2s" repeatCount="indefinite" begin="0.2s" />
        </circle>
        <circle cx="20" cy="12" r="2" fill={props.color || 'currentColor'}>
            <animate attributeName="opacity" values="0;1;0" dur="1.2s" repeatCount="indefinite" begin="0.4s" />
        </circle>
    </svg>
);

export default GeneratingIcon;
