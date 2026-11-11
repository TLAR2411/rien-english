// This JavaScript code is identical to your TypeScript code
// and will work perfectly in Nuxt.
export default defineEventHandler(async (event) => {
    // 1. Get the secret domain from the server's .env file (NOT VITE_)
    // .env file should have: VITE_API_URL=https://sangkhum-api.test
    const apiDomain = process.env.VITE_API_URL;

    // 2. Get the path from the request (e.g., "users", "products/1")
    // We also add the "/api/web" prefix that was in your original baseURL
    const path = `${event.context.params.slug || ''}`;

    // 3. Build the REAL API URL (this is kept secret on the server)
    const realApiUrl = `${apiDomain}/${path}`;

    // 4. Get headers from the incoming request (from api.js)
    const headers = getRequestHeaders(event);

    // 5. Create a clean set of headers to forward
    const forwardedHeaders = {
        'Authorization': headers.authorization, // Forward the auth token
        'Content-Type': headers['content-type'],
        'Accept': headers.accept,
    };

    try {
        // 6. Call the REAL API from the server
        return await $fetch(realApiUrl, {
            method: event.node.req.method,       // Pass along the method (GET, POST, etc.)
            query: getQuery(event),              // Pass along any query parameters
            headers: forwardedHeaders,           // Pass along the important headers

            // Pass the body (with branch_id) for POST/PUT/PATCH requests
            body: event.node.req.method !== 'GET' ? await readBody(event) : undefined,
        });

    } catch (error) {
        // Pass along API errors back to the client
        setResponseStatus(event, error.statusCode || 500);
        return error.data;
    }
});