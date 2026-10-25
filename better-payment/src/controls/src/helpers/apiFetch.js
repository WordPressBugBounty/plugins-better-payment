import { sanitizeHTML } from "./EBSanitization";
import { ebJsonStringCheck, isSvgIconValue } from "./index";
/**
 * fetchOptionsData
 * @param {*} key
 * @returns
 */
export const fetchOptionsData = (key) => {
  let data = new FormData();
  data.append("action", "eb_fetch_options_data");
  data.append("key", key);
  data.append("nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      const res = JSON.parse(data);
      if (ebJsonStringCheck(res.data)) {
        return JSON.parse(res.data);
      } else {
        return res.data;
      }
    })
    .catch((err) => console.log(err));
};

/**
 * saveOptionsData
 * @param {*} key
 * @param {*} value
 * @returns
 */
export const saveOptionsData = (key, value) => {
  let data = new FormData();
  data.append("action", "eb_save_options_data");
  data.append("key", key);
  data.append("value", JSON.stringify(value));
  data.append("nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return data;
    })
    .catch((err) => console.log(err));
};

/**
 * fetchEBSettingsData
 * @param {*} slug
 * @param {*} file
 */
export const fetchEBSettingsData = (key) => {
  let data = new FormData();
  data.append("action", "get_eb_admin_options");
  data.append("key", key);
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      const res = JSON.parse(data);
      if (res.success) {
        return res.data;
      }
    })
    .catch((err) => console.log(err));
};

/**
 * saveEBSettingsData
 * @param {string} key
 * @param {string} value
 * @param {string=} type
 */
export const saveEBSettingsData = (key, value, type = "settings") => {
  let data = new FormData();
  data.append("action", "save_eb_admin_options");
  data.append("type", type);

  data.append("value", value);
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);

  if (type === "settings") {
    data.append("key", key);
  }

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return data;
    })
    .catch((err) => console.log(err));
};

/**
 * resetEBSettingsData
 * @param {string} key
 * @param {string=} type
 */
export const resetEBSettingsData = (key, type = "settings") => {
  let data = new FormData();
  data.append("action", "reset_eb_admin_options");
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);
  data.append("type", type);

  if (type === "settings") {
    data.append("key", key);
  }

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return data;
    })
    .catch((err) => console.log(err));
};

/**
 * fetchFormData
 * @param {*} field
 * @returns
 */
export const fetchFormBlockData = (id, field = false) => {
  let data = new FormData();
  data.append("action", "eb_fetch_form_data");
  data.append("id", id);
  if (field) {
    data.append("field", field);
  }
  data.append("nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return JSON.parse(data)?.data;
    })
    .catch((err) => console.log(err));
};

/**
 * saveFormData
 * @param {string} id
 * @param {object} allFields
 * @param {object} allSettings
 * @returns
 */
export const saveFormBlockData = (
  id,
  title,
  allFields,
  formOptions,
  settings
) => {
  let data = new FormData();
  data.append("action", "eb_save_form_data");
  data.append("id", id);
  data.append("form_title", title);
  data.append("form_fields", JSON.stringify(allFields));
  data.append("form_options", JSON.stringify(formOptions));
  data.append("settings", JSON.stringify(settings));
  data.append("nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return JSON.parse(data);
    })
    .catch((err) => console.log(err));
};

/**
 * installPlugin
 * @param {*} slug
 * @param {*} file
 */
export const installPlugin = (slug, file) => {
  let data = new FormData();
  data.append("action", "plugin_installer");
  data.append("slug", slug);
  data.append("plugin_file", file);
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return data;
    })
    .catch((err) => console.log(err));
};
/**
 * deactivatePlugin
 * @param {*} slug
 * @param {*} file
 */
export const deactivatePlugin = (slug, file) => {
  let data = new FormData();
  data.append("action", "plugin_deactivater");
  data.append("slug", slug);
  data.append("plugin_file", file);
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      // console.log('data', data);

      return data;
    })
    .catch((err) => console.log(err));
};

/**
 * save quick toolbar block data
 * @param {string} value
 */
export const saveQuickToolbarBlocks = (value) => {
  let data = new FormData();
  data.append("action", "eb_save_quick_toolbar_blocks");

  data.append("value", value);
  data.append("admin_nonce", EssentialBlocksLocalize?.admin_nonce);

  return fetch(EssentialBlocksLocalize?.ajax_url, {
    method: "POST",
    body: data,
  }) // wrapped
    .then((res) => res.text())
    .then((data) => {
      return data;
    })
    .catch((err) => console.log(err));
};

/**
 * Fetch an SVG file and return sanitized inline SVG markup
 * Returns empty string on failure
 */
export const fetchSvgAsHTML = async (svgUrl) => {
  if (!isSvgIconValue(svgUrl)) return "";
  try {
    const res = await fetch(svgUrl, { credentials: "omit" });
    if (!res.ok) return "";
    const raw = await res.text();
    // Ensure we only inline the <svg ...>...</svg> fragment
    const match = raw.match(/<svg[\s\S]*?<\/svg>/i);
    const svgOnly = match ? match[0] : raw;
    return sanitizeHTML(svgOnly);
  } catch (e) {
    return "";
  }
};
