const KEY = "token";

// Refresh this long before the real expiry, so a request never goes out
// with a token that dies on the way.
const EXPIRY_MARGIN = 30 * 1000;

const readToken = () => {
  const raw = localStorage.getItem(KEY);
  if (!raw) return null;

  try {
    const item = JSON.parse(raw);
    return typeof item === "string" ? { value: item, expiry: null } : item;
  } catch (error) {
    console.error("Token format invalid, clearing storage.");
    localStorage.removeItem(KEY);
    return null;
  }
};

export const getAccessToken = () => {
  const item = readToken();
  if (!item?.value) return null;

  if (item.expiry && Date.now() > item.expiry) {
    localStorage.removeItem(KEY);
    return null;
  }

  return item.value;
};

// True when there is no usable token, or it expires within the margin
export const isAccessTokenExpiring = () => {
  const item = readToken();
  if (!item?.value) return true;
  return Boolean(item.expiry) && Date.now() > item.expiry - EXPIRY_MARGIN;
};

// Accepts { value, expiry } or a plain token string
export const setAccessToken = (item) => {
  if (!item) return removeAccessToken();
  const data = typeof item === "string" ? { value: item, expiry: null } : item;
  localStorage.setItem(KEY, JSON.stringify(data));
};

export const removeAccessToken = () => {
  localStorage.removeItem(KEY);
};
