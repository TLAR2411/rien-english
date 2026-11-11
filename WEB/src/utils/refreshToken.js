const KEY = "refreshToken";

// Stored as { value, expiry } (expiry in ms). A plain string from an older
// login is still accepted, with no known expiry.
export const getRefreshToken = () => {
  const raw = localStorage.getItem(KEY);
  if (!raw) return null;

  try {
    const item = JSON.parse(raw);
    if (typeof item === "string") return item || null;

    if (item?.expiry && Date.now() > item.expiry) {
      localStorage.removeItem(KEY);
      return null;
    }

    return item?.value || null;
  } catch (error) {
    console.error("refreshToken format invalid, clearing storage.");
    localStorage.removeItem(KEY);
    return null;
  }
};

export const setRefreshToken = (item) => {
  if (!item) return removeRefreshToken();
  const data = typeof item === "string" ? { value: item, expiry: null } : item;
  localStorage.setItem(KEY, JSON.stringify(data));
};

export const removeRefreshToken = () => {
  localStorage.removeItem(KEY);
};
