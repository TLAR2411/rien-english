function formatGender(genderValue) {
    return genderValue === "male"
        ? "ប្រុស"
        : genderValue === "female"
            ? "ស្រី"
            : "Unknown";
}

export default formatGender;
