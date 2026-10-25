import './style.scss'

const NoticeComponent = (props) => {
    const { Icon, title, description, externalDocLink, externalDocText } = props

    return (
        <div className="eb_reusable_notice">
            {title && (
                <h4 className="title">
                    {Icon && (
                        <Icon />
                    )}
                    {title}
                </h4>
            )}
            {description && (
                <p className="content">{description} </p>
            )}
            {externalDocText && (
                <a
                    className="doc"
                    target="_blank"
                    href={externalDocLink || '#'}
                >
                    {externalDocText}
                </a>
            )
            }
            {props.children}
        </div>
    );
};

export default NoticeComponent;
