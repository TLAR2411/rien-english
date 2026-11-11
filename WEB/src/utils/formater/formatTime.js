function formatTime(dateString) {
  // Check if dateString is valid
  if (!dateString || typeof dateString !== "string") {
    return "-";
  }

  const date = new Date(dateString);

  // Check if the date is valid
  if (isNaN(date.getTime())) {
    return "-";
  }

  // Get local hours and minutes
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");

  return `${hours}:${minutes}`;
}

export default formatTime;