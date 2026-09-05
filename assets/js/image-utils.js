const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/';

export function getHybridImageUrl(value, size = 'w500', fallback = '') {
    const image = String(value || '').trim();
    if (!image) return fallback;
    if (/^(https?:\/|data:image\/|blob:)/i.test(image)) return image;

    // TMDB paths are short; Android's raw Base64 payloads are much longer.
    if (image.startsWith('/') && image.length < 100) {
        return `${TMDB_IMAGE_BASE}${size}${image}`;
    }

    const base64 = image.startsWith('/') ? image.slice(1) : image;
    // Accept padded and unpadded Base64 payloads (Android may omit padding).
    if (base64.length > 0 && /^[A-Za-z0-9+/]*={0,2}$/.test(base64) && base64.length % 4 !== 1) {
        return `data:image/jpeg;base64,${base64}`;
    }
    return fallback;
}

export function setImageWithFallback(image, value, fallback) {
    const fallbackElement = image.parentElement?.querySelector('[data-image-fallback]');
    image.src = getHybridImageUrl(value, 'w200', '');
    image.onerror = () => {
        image.onerror = null;
        image.removeAttribute('src');
        image.style.display = 'none';
        if (fallbackElement) fallbackElement.style.display = 'inline-flex';
        if (fallback) fallback();
    };
    if (fallbackElement) {
        fallbackElement.style.display = image.src ? 'none' : 'inline-flex';
    }
}
