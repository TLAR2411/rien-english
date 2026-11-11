function formatNoneZero(data, abbr = null) {
    if (abbr != null) {
        return data != 0 ? `${parseFloat(data).toLocaleString("en-US")} ${abbr}` : '-';
    } else {
        return (data != 0 && data != null) ? parseFloat(data).toLocaleString("en-US") : '-';
    }

}

export default formatNoneZero;
