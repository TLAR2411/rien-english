// getImageUrl.js

// This function now returns a URL like: /api/proxy/storage/path/to/image.jpg
export default function getImageUrl(image) {
    // ⬇️ Base is now the relative proxy path
    const base = '/image';

    // Ensure image path doesn't start with / if it exists, handle null/undefined
    const imagePath = image ? String(image).replace(/^\/+/, '') : '';

    // Handle case where image might be null or empty
    if (!imagePath) {
        // Return a placeholder, default image, or empty string as needed
        return '/placeholder.png'; // Example placeholder
    }

    // Check if the image path already contains the expected storage prefix
    // Adjust 'storage/' if your actual image paths are different
    if (imagePath.startsWith('storage/')) {
        return `${base}/${imagePath}`;
    } else {
        // Assuming images are always under a 'storage' path on the backend
        // If not, adjust this logic
        return `${base}/storage/${imagePath}`;
    }
}

// This function now returns a URL like:
// /api/proxy/img?url=ENCODED(/api/proxy/storage/path/to/image.jpg)&w=...
export function getThumbUrl(
    image,
    { w = 72, h = 72, q = 60, fmt = 'webp', fit = 'cover' } = {}
) {
    // ⬇️ Base is now the relative proxy path
    const base = '/image';

    // ⬇️ This now gets the PROXIED URL (e.g., /api/proxy/storage/...)
    const proxiedOriginalUrl = getImageUrl(image);

    // Point to the proxied resizer route '/api/proxy/img'
    // The 'url' query parameter contains the proxied path to the original image
    return `${base}/img?url=${encodeURIComponent(proxiedOriginalUrl)}&w=${w}&h=${h}&fmt=${fmt}&q=${q}&fit=${fit}`;
}