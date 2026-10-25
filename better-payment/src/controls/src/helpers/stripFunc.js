/**
 * Remove HTML Tags from String
 * @param {*} string
 * @returns
 */
export const stripHtmlTags = (string) => {
    return string.replace(/(<([^>]+)>)/gi, "");
}

/**
 * Check is given string is valid html
 * @param {*} string
 * @returns
 */
export const isValidHtml = (string) => {
    var doc = document.createElement('div');
    doc.innerHTML = string;
    return (doc.innerHTML === string);
}
