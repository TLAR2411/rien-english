function toSentenceCase(str) {
    if (!str) return str; // Handle empty strings

    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}
export default toSentenceCase;