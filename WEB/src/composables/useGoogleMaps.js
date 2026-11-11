let _loadingPromise;

export function useGoogleMaps() {
    const loadGoogleMaps = async (opts = {}) => {
        if (_loadingPromise) return _loadingPromise;

        const version = opts.version || "weekly";
        const libraries = Array.isArray(opts.libraries) && opts.libraries.length
            ? `&libraries=${opts.libraries.join(",")}`
            : "";

        // Use proxy in production, direct API in development
        const useProxy = import.meta.env.PROD;
        const baseUrl = useProxy
            ? '/api/maps'  // Proxy endpoint
            : 'https://maps.googleapis.com';

        const cbName = `__init_gmaps_${Math.random().toString(36).slice(2)}`;

        _loadingPromise = new Promise((resolve, reject) => {
            if (window.google && window.google.maps) return resolve(window.google.maps);

            window[cbName] = () => {
                delete window[cbName];
                resolve(window.google.maps);
            };

            const script = document.createElement("script");

            if (useProxy) {
                // Using proxy - no API key in client URL
                script.src = `${baseUrl}/maps/api/js?v=${encodeURIComponent(version)}${libraries}&callback=${cbName}`;
            } else {
                // Direct API call for development
                const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
                if (!apiKey) {
                    reject(new Error("Missing Google Maps API key for development"));
                    return;
                }
                script.src = `${baseUrl}/maps/api/js?key=${encodeURIComponent(apiKey)}&v=${encodeURIComponent(version)}${libraries}&callback=${cbName}`;
            }

            script.async = true;
            script.defer = true;
            script.onerror = () => {
                delete window[cbName];
                reject(new Error("Failed to load Google Maps JavaScript API"));
            };
            document.head.appendChild(script);
        });

        return _loadingPromise;
    };

    return { loadGoogleMaps };
}