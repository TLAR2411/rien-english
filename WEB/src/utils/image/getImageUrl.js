// getImageUrl.js
export default function getImageUrl(image) {
    const base = import.meta.env.VITE_API_URL.replace(/\/+$/, '')
    return `${base}/${String(image).replace(/^\/+/, '')}`
}

// getImageUrl.js
export function getThumbUrl(
    image,
    // Ensure 'inside' is the default so we see the whole image
    { w = 72, h = 72, q = 60, fmt = 'webp', fit = 'inside' } = {}
) {
    const base = import.meta.env.VITE_API_URL.replace(/\/+$/, '')
    const original = getImageUrl(image)

    // This sends the &fit=inside part to your PHP function above
    return `${base}/img?url=${encodeURIComponent(original)}&w=${w}&h=${h}&fmt=${fmt}&q=${q}&fit=${fit}`
}