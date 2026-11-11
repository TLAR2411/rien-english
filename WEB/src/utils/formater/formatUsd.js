// "120.5" -> "$120.50". No amount -> "-"
function formatUsd(value) {
  if (value === null || value === undefined || value === "") return "-";

  return `$${Number(value).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

export default formatUsd;
