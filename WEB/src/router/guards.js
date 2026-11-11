import { useAuthStore } from "@/stores/authStore";
import hasPermission from "@/utils/hasPermission";
import { getI18n } from '@/plugins/i18n';
import { usePartStore } from "@/stores/partStore";
import { getAccessToken, removeAccessToken } from "@/utils/accessToken";
import { getRefreshToken } from "@/utils/refreshToken";
import { homePathFor } from "@/utils/homePath";
import { SYSTEM_PART_SEGMENT_MAP } from "@/composables/useSystemParts";
// import { useCookie } from "#imports";
const { t } = getI18n().global;
export const setupGuards = (router) => {
  router.beforeEach(async (to) => {
    const auth = useAuthStore()
    const part = usePartStore()

    // 1. Determine the module (Admin, ...)
    const firstSegment = to.path.split("/")[1] || 'admin';
    part.setSystemPart(SYSTEM_PART_SEGMENT_MAP[firstSegment] || 'admin');

    // the page's own title, translated; pages without one show the brand
    // name as is (it is a name, not a translation key)
    document.title = to?.meta?.title
      ? t(to.meta.title)
      : import.meta.env.VITE_APP_TITLE || "Learning English";

    // 2. Get the token - use a fresh check
    let accessToken = getAccessToken();

    // Access token expired (e.g. app reopened after an hour) but the refresh
    // token is still good: renew the session instead of going to login.
    if (!accessToken && getRefreshToken() && !to.meta.public) {
      if (await auth.refresh()) accessToken = getAccessToken();
    }

    // 3. Handle Bootstrapping (Syncing Cookie to Pinia)
    if (accessToken && !auth.isBootstrapped) {
      try {
        await auth.bootstrap();
      } catch (error) {
        console.error("Bootstrap failed, likely invalid token:", error);
        removeAccessToken() // Clear invalid cookie
        return { name: 'login' };
      }
    }

    // 4. Determine Login Status
    const isLoggedIn = Boolean(accessToken) || auth.isAuthenticated;

    // 5. Public Route Logic
    if (to.meta.public) return true;

    // 6. Redirect Logic
    if (!isLoggedIn) {
      if (to.name !== 'login') {
        return { name: 'login' };
      }
      return true;
    }

    if (to.meta.unauthenticatedOnly && isLoggedIn) {
      return { name: 'root' };
    }

    // "/" itself is an empty page: send the user to their own dashboard.
    // This is where the installed app (PWA, start_url "/") opens, and after
    // a silent token refresh there is no login step to redirect from.
    if (to.path === "/") {
      return homePathFor(auth.user?.default_part ?? part.system_part);
    }

    // 7. Permissions
    if (to.meta.permissions && !hasPermission(to.meta.permissions)) {
      return { name: "not-authorized" };
    }

    return true;
  });
};