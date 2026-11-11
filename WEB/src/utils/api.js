import axios from "axios";
import { useAuthStore } from "@/stores/authStore";
import { useSettingStore } from "@/stores/settingStore";
import { useDialog } from '@/composables/useDialog';
import { getAccessToken, isAccessTokenExpiring, setAccessToken } from "./accessToken";
import { getRefreshToken, setRefreshToken } from "./refreshToken";


const pendingRequests = new Map();

// Called with (url, method) after every successful POST / PUT / PATCH /
// DELETE; tempAppStore registers here to refresh its cached lists
const mutationListeners = new Set();
export const onApiMutation = (listener) => {
  mutationListeners.add(listener);
  return () => mutationListeners.delete(listener);
};

// Endpoints that should NOT be cancelled (e.g., save/update operations)
const NON_CANCELLABLE_ENDPOINTS = [
  '/save',
  '/update',
  '/create',
  '/delete',
  '/upload'
];

/**
 * Generates a unique key for the request.
 * Includes method, URL, and serialized params for better uniqueness.
 */
const getRequestKey = (config) => {
  const params = config.params ? JSON.stringify(config.params) : '';
  return `${config.method}::${config.url}::${params}`;
};

/**
 * Determines if a request should be cancellable. Only reads (GET) are: the key
 * ignores the body, so two different saves to the same URL (e.g. two HR
 * settings) would otherwise cancel each other and the first change is lost.
 */
const isCancellable = (config) => {
  if ((config.method || "get").toLowerCase() !== "get") return false;
  // "...-all" lookups (account types, chart accounts, users, ...) are plain
  // lists that several components may load at the same moment. Cancelling
  // one leaves that component with an empty dropdown and no error (the
  // dataService loaders swallow the cancel), so let them all finish.
  if (/-all(\?|$)/.test(config.url)) return false;
  return !NON_CANCELLABLE_ENDPOINTS.some(endpoint => config.url.includes(endpoint));
};

/**
 * Cleanup pending request from the map
 */
const cleanupRequest = (config) => {
  if (config) {
    const key = getRequestKey(config);
    pendingRequests.delete(key);
  }
};

// In Vite dev, call same-origin `/api/web` so the Vite proxy forwards to Laravel
// and the browser avoids cross-origin CORS issues (Postman is unaffected).
const apiBaseUrl = `${import.meta.env.VITE_API_URL}/api/web`;

export const api = axios.create({
  baseURL: apiBaseUrl,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
  timeout: 30000, // 30 second timeout
});

// ---- Refresh token ----------------------------------------------------------
// Calls that must never trigger a refresh themselves
const AUTH_ENDPOINTS = ["login", "refresh", "logout"];
const isAuthEndpoint = (url = "") =>
  AUTH_ENDPOINTS.some((name) => url.replace(/^\//, "") === name);

let refreshPromise = null;

/**
 * Swaps the refresh token for a new access + refresh token. Many requests can
 * hit an expired token at once; they all wait on this one shared call.
 * Resolves with the new access token, rejects if the session is over.
 */
export const refreshAccessToken = () => {
  if (refreshPromise) return refreshPromise;

  // One refresh at a time across ALL tabs: Passport cancels a refresh token
  // once it's used, so a second tab using the same one would fail and log
  // everyone out. The lock makes other tabs wait, then reuse the new token.
  const withTabLock = (fn) =>
    navigator.locks?.request
      ? navigator.locks.request("auth-token-refresh", fn)
      : fn();

  const tokenBefore = localStorage.getItem("token");

  refreshPromise = withTabLock(async () => {
    // Another tab saved a new token while we waited for the lock: use it
    if (localStorage.getItem("token") !== tokenBefore && getAccessToken()) {
      useAuthStore().$patch({ accessToken: getAccessToken(), isAuthenticated: true });
      return getAccessToken();
    }

    const refreshToken = getRefreshToken();
    if (!refreshToken) throw new Error("No refresh token");

    // Plain axios, not `api`, so this call skips our interceptors
    const res = await axios.post(
      `${apiBaseUrl}/refresh`,
      { refreshToken },
      {
        headers: {
          Accept: "application/json",
          "X-CLIENT-ID": import.meta.env.VITE_CLIENT_ID,
        },
        timeout: 15000,
      },
    );

    const data = res.data?.data;
    if (!res.data?.status || !data?.access_token) {
      throw new Error(res.data?.message || "Refresh failed");
    }

    setAccessToken(data.access_token);
    setRefreshToken(data.refresh_token);
    useAuthStore().$patch({
      accessToken: data.access_token.value ?? data.access_token,
      isAuthenticated: true,
    });

    return getAccessToken();
  }).finally(() => {
    refreshPromise = null;
  });

  return refreshPromise;
};

api.interceptors.request.use(async (config) => {
  const settingStore = useSettingStore();

  // Token about to expire: get a new one first instead of failing with 401
  if (!isAuthEndpoint(config.url) && isAccessTokenExpiring() && getRefreshToken()) {
    try {
      await refreshAccessToken();
    } catch {
      // Send without a token; the 401 below logs the user out
    }
  }

  const accessToken = getAccessToken();

  // --- CANCELLATION LOGIC ---
  if (isCancellable(config)) {
    const requestKey = getRequestKey(config);

    // Cancel existing duplicate request
    if (pendingRequests.has(requestKey)) {
      const existingController = pendingRequests.get(requestKey);
      existingController.abort();
      pendingRequests.delete(requestKey);
    }

    // Create new abort controller
    const controller = new AbortController();
    config.signal = controller.signal;
    pendingRequests.set(requestKey, controller);
  }

  // --- AUTH & BRANCH HEADERS ---
  if (accessToken) {
    config.headers.Authorization = `Bearer ${accessToken}`;
  }

  config.headers['X-CLIENT-ID'] = import.meta.env.VITE_CLIENT_ID;
  config.headers['X-Branch-Id'] = settingStore.branch_id || "*";
  config.headers['X-Branch-Abbr'] = settingStore.branch_abbr || "";

  // --- DATA FORMATTING ---
  if (config.data instanceof FormData) {
    // For FormData, add branch ID if available
    if (settingStore.branch_id) {
      config.data.append("branchId", settingStore.branch_id);
    }
  } else {
    // For regular JSON payloads
    config.data = config.data || {};

    // Preserve filter object if it exists
    if (config.data.filter && typeof config.data.filter === 'object') {
      config.data.filter = { ...config.data.filter };
    }
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

api.interceptors.response.use(
  (response) => {
    const { showDialog } = useDialog();
    // Cleanup completed request
    cleanupRequest(response.config);

    const { status, message } = response.data || {};

    // Handle success messages
    if (status === true && message) {
      showDialog({
        title: message,
        icon: 'success',
        isConfirm: false,
        timer: 3000
      });
    }
    // Handle API-level errors (when status is false or error codes)
    else if (status === false || [401, 404, 500].includes(response.data?.status)) {
      showDialog({
        title: message || "An error occurred",
        icon: 'error',
        isConfirm: false,
        timer: 5000
      });
      return Promise.reject(new Error(message || "Request failed"));
    }

    // A save / delete succeeded: tell the listeners (tempAppStore drops the
    // cached lists built from that resource). Run before the response goes
    // back, so a page that reloads its lists right after saving gets fresh
    // ones, not the old cached copy.
    const method = (response.config?.method || "get").toLowerCase();
    if (method !== "get") {
      mutationListeners.forEach((listener) => {
        try {
          listener(response.config.url, method);
        } catch (error) {
          console.error("Mutation listener failed:", error);
        }
      });
    }

    return response;
  },
  async (error) => {

    // Handle cancelled requests silently
    if (axios.isCancel(error)) {
      console.debug('Request cancelled:', error.message);
      return Promise.reject(error);
    }

    // Cleanup failed request
    cleanupRequest(error.config);

    const authStore = useAuthStore();
    const statusCode = error.response?.status;
    const errorMessage = error.response?.data?.message || error.message || "Connection Error";
    const original = error.config;

    // Token rejected: refresh once and replay the same request
    if (
      statusCode === 401 &&
      original &&
      !original._retried &&
      !isAuthEndpoint(original.url) &&
      getRefreshToken()
    ) {
      original._retried = true;
      try {
        await refreshAccessToken();
        return api(original); // request interceptor adds the new token
      } catch {
        // Refresh token expired or revoked: fall through to log out
      }
    }

    // Handle authentication errors
    if (statusCode === 401) {
      authStore.unAuthenticated();
    }

    // Show error dialog (skip for cancelled requests, and for requests sent
    // with { silent: true } — the page shows its own message)
    if (!axios.isCancel(error) && statusCode != 401 && !error.config?.silent) {
      const { showDialog } = useDialog();
      showDialog({
        title: errorMessage,
        icon: 'error',
        isConfirm: false,
        timer: 4000
      });
    }

    return Promise.reject(error);
  }
);

// Optional: Export cleanup function for manual use
export const cancelAllPendingRequests = () => {
  pendingRequests.forEach((controller) => {
    controller.abort();
  });
  pendingRequests.clear();
};