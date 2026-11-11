function setTableState(key, value, ttlMinutes = 30) {
  if (!key) return null;
  if (!value === '' && !value) return null; // Updated line
  const now = new Date();
  const item = {
    value,
    expiry: now.getTime() + ttlMinutes * 60 * 1000,
  };
  localStorage.setItem(`table-state-${key}`, JSON.stringify(item));
}

export default setTableState;