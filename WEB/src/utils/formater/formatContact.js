function formatContact(dataContact) {
  if (!dataContact) return ``;

  const cleaned = dataContact.replace(/[\s\-_/]/g, '');

  if (cleaned.length >= 11) {
    // Format as XXX XXX XXXXX (11+ digits)
    return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 11)}`;
  } else if (cleaned.length >= 10) {
    // Format as XXX XXX XXXX (10 digits)
    return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 10)}`;
  } else if (cleaned.length >= 9) {
    // Format as XXX XXX XXX (9 digits)
    return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 9)}`;
  }

  return cleaned;
}

export default formatContact;