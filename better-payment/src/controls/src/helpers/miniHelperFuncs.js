// helper Functions: function 'textInsideForEdit' is for setting the innertext depending on whether separator should be shown and which separator should be shown in the number-counter block
export const textInsideForEdit = (value, isShowSeparator, separator) =>
  isShowSeparator
    ? value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator)
    : value.toString();

// generateRandomNumber function is for generating a random number
export const generateRandomNumber = () =>
  Math.floor(Math.random() * 1000000000);

// hardMinifyCssStrings is for minifying the css which is in the style tag as a string  for view.js
export const hardMinifyCssStrings = (cssString) => {
  return (
    cssString
      .replace(/\s+/g, " ")
      .replace(/(?<=\:).+(?=\;)/g, function (match) {
        return match.trim().replace(/\s+/g, "__s_p_a_c_e__");
      })
      // .replace(/\s+(?!(?:[\w\d\.\-\#]+\{))/g, "")
      .replace(/\s+(?![\w\d\.\-\#]+\{)/g, "")
      .replace(/\s+/g, " ")
      .replace(/__s_p_a_c_e__/g, " ")
  );
};

// softMinifyCssStrings is for minifying the css which is in the style tag as a string  for view.js
export const softMinifyCssStrings = (cssString) => {
  if (!cssString || typeof cssString !== "string") {
    return "";
  }

  // const finalCss = cssString.replace(/\s+/g, " ").replace(/\.eb\-[\w\-\s\.\,\:\>\=\"\'\(\)\d\+\[\]\#\>]+\{[\s]+\}/g, "");
  // return finalCss;

  const finalCss = cssString
    .replace(/[^{}]+{\s*}/g, "") //Remove empty curly braces selectors
    .replace(/\n\s+/g, ""); // Remove newlines and preceding spaces
  /*.replace(/\s+{/g, '{') // Remove spaces before opening curly braces
        .replace(/\s+}/g, '}') // Remove spaces before closing curly braces
        .replace(/:\s+/g, ':') // Remove spaces after colons
        .replace(/;\s+/g, ';') // Remove spaces after semicolons;*/

  return removeEmptyCSSProperties(finalCss).replace(/\s*([{};,:])\s*/g, "$1");
};

export const removeEmptyCSSProperties = (cssString) => {
  // Split the CSS string into individual rules
  const cssRules = cssString.split("}");

  // Iterate through each rule and process it
  const filteredRules = cssRules
    .map((rule) => {
      // Split the rule into selector and properties
      const [selector, properties] = rule.split("{");
      if (properties) {
        // Split the properties into individual property declarations
        const propertyDeclarations = properties
          .split(";")
          .filter((declaration) => {
            // Remove any property with an empty value or "undefined" value
            const [property, value] = declaration.split(":");
            return (
              value &&
              value.trim() !== "" &&
              value.trim() !== "undefined" &&
              value.trim() !== "undefinedpx"
            );
          });
        // Rejoin the selector and filtered properties
        return propertyDeclarations.length > 0
          ? `${selector} { ${propertyDeclarations.join("; ")} }`
          : null;
      }
      return null;
    })
    .filter(Boolean);

  // Rejoin the filtered rules into a CSS string
  return filteredRules.join("");
};

// check if css string is empty or not.
export const isCssExists = (cssString) =>
  /.+(?=\:(?!hover)(?!focus))/.test(cssString);

// Capitalize First Letter
export const ucFirst = (string) =>
  string
    .split("_")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");

/**
 * Detect if a value is an SVG file URL
 * Accepts absolute, relative WP uploads, and querystring variants
 */
export const isSvgIconValue = (value) => {
  if (!value || typeof value !== "string") return false;
  // Quick allowlist for typical WordPress media URLs and any path that ends with .svg
  try {
    const hasSvgExt = /\.svg(\?|#|$)/i.test(value);
    return hasSvgExt;
  } catch (e) {
    return false;
  }
};

/**
 * Detect if a string contains inline SVG markup
 */
export const isInlineSvgMarkup = (value) => {
  return typeof value === "string" && /<svg[\s\S]*<\/svg>/i.test(value);
};

/**
 * Enhanced icon rendering with SVG support
 * @param {string} iconValue - The icon value
 * @param {string} className - Additional CSS class name
 * @returns {string} - The rendered icon HTML string
 */
export const EBRenderIconWithSVG = (iconValue, className = "") => {
    if (!iconValue) return "";

    // Get sanitization functions
    const sanitizeHTML = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeHTML)
        ? window.eb_frontend.sanitizeHTML
        : (html) => html;
    const sanitizeIconValue = (typeof window !== 'undefined' && window.eb_frontend && window.eb_frontend.sanitizeIconValue)
        ? window.eb_frontend.sanitizeIconValue
        : (icon) => icon;

    // Handle inline SVG markup
    if (isInlineSvgMarkup(iconValue)) {
        return '<span class="' + className + '">' + sanitizeHTML(iconValue) + '</span>';
    }

    // Handle SVG URLs - return a placeholder that will be replaced with actual SVG
    if (isSvgIconValue(iconValue)) {
        return '<span class="eb-svg-icon ' + className + '" data-svg-url="' + iconValue + '"></span>';
    }

    // Handle FontAwesome and Dashicon icons (existing behavior)
    return '<i aria-hidden="true" class="' + sanitizeIconValue(iconValue) + '"></i>';
};
