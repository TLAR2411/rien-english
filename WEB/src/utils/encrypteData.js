// encrypteData.js
import CryptoJS from "crypto-js";

const secretKey = import.meta.env.VITE_ENCRYPTION_SECRET;

/**
 * Encrypt data in Laravel-compatible format
 * @param {object|string} data - The data to encrypt
 * @returns {string} - The encrypted string in Laravel format
 */
export function encrypt(data) {
  try {
    // Convert data to JSON string if it's an object
    const jsonData = typeof data === 'string' ? data : JSON.stringify(data);

    // Generate a random IV (16 bytes)
    const iv = CryptoJS.lib.WordArray.random(16);

    // Encrypt the data using AES-256-CBC
    const encrypted = CryptoJS.AES.encrypt(
      jsonData,
      CryptoJS.enc.Base64.parse(secretKey),
      {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
      }
    );

    // Create the payload in Laravel's format
    const payload = {
      iv: CryptoJS.enc.Base64.stringify(iv),
      value: encrypted.toString(),
      mac: "",
    };

    // Calculate MAC exactly like Laravel does
    // Laravel: hash_hmac('sha256', hash('sha256', $payload, true), $this->key, true)
    const payloadJson = JSON.stringify(payload);
    const payloadBase64 = btoa(payloadJson);

    // Laravel creates MAC from: base64(json) + the serialized name (which is empty for encryptString)
    const macPayload = payloadBase64;

    // Create HMAC-SHA256 hash
    const mac = CryptoJS.HmacSHA256(
      macPayload,
      CryptoJS.enc.Base64.parse(secretKey)
    );

    payload.mac = mac.toString(CryptoJS.enc.Hex);

    // Return the base64 encoded JSON payload
    return btoa(JSON.stringify(payload));

  } catch (error) {
    console.error("Encryption failed:", error);
    return null;
  }
}

/**
 * Decrypt data encrypted by Laravel
 * @param {string} encryptedString - The encrypted string from Laravel
 * @returns {object|string} - The decrypted data
 */
export function decrypt(encryptedString) {
  try {
    // 1. Handle empty or undefined inputs to prevent atob crash
    if (!encryptedString || typeof encryptedString !== 'string') {
      console.warn("Decrypt received invalid input:", encryptedString);
      return null;
    }

    // 2. Fix URL-safe characters and standard padding if necessary
    // Replace '-' with '+', '_' with '/', and handle whitespace
    let safeString = encryptedString
      .replace(/-/g, '+')
      .replace(/_/g, '/');

    // 3. Decode Base64
    const jsonPayload = atob(safeString);
    const payload = JSON.parse(jsonPayload);

    // 4. Parse Key (Remove 'base64:' prefix if present)
    const key = CryptoJS.enc.Base64.parse(secretKey.replace('base64:', ''));
    const iv = CryptoJS.enc.Base64.parse(payload.iv);
    const encryptedData = payload.value;

    // 5. Decrypt
    const decrypted = CryptoJS.AES.decrypt(
      encryptedData,
      key, // Use the parsed key, not the raw string
      {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
      }
    );

    // 6. Return result
    const decryptedString = decrypted.toString(CryptoJS.enc.Utf8);

    // Handle empty result (decryption failure)
    if (!decryptedString) return null;

    try {
      return JSON.parse(decryptedString);
    } catch {
      return decryptedString;
    }

  } catch (error) {
    // Log the actual input to debug
    console.error("Decryption failed for input:", encryptedString, error);
    return null;
  }
}