import { defineStore } from "pinia";
import { api, refreshAccessToken } from "@/utils/api";
import { router } from "@/router/index";
import { useAppStore } from "@/stores/appStore";
import { homePathFor } from "@/utils/homePath";
import { safeRedirect } from "@/utils/siteRoutes";
import { useTempAppStore } from "@/stores/tempAppStore";
import { useSettingStore } from "./settingStore";
import { usePartStore } from "./partStore";
import { decrypt } from "@/utils/encrypteData";
import { getAccessToken, removeAccessToken, setAccessToken } from "@/utils/accessToken";
import { getRefreshToken, removeRefreshToken, setRefreshToken } from "@/utils/refreshToken";

export const useAuthStore = defineStore("auth", {
  state: () => {
    const token = getAccessToken();
    return {
      id: null,
      isAuthenticated: !!token,
      user: null,
      accessToken: token || null,
      refreshToken: null,
      isTokenRefreshing: false,
      permissions: [],
      branches: [],
      company: null,
      isBootstrapped: false,
    }
  },
  actions: {
    async login(payload) {
      try {
        const response = await api.post("login", { ...payload });
        if (response.data.status) await this.startSession(response.data.data);
      } catch (error) {
        console.error("Login error:", error);
      }
    },
    // Student sign-up: the API creates the account and logs in right away.
    // Errors go back to the page, so it can show them under each field.
    async register(payload) {
      const response = await api.post("register", { ...payload }, { silent: true });
      if (response.data.status) await this.startSession(response.data.data);
    },
    // Save the tokens, load the user, then open the page that sent the user
    // here (e.g. a lesson's quiz) or their own home page.
    async startSession(data) {
      setAccessToken(data.access_token);
      setRefreshToken(data.refresh_token);

      this.$patch({
        isAuthenticated: true,
        accessToken: data.access_token,
        refreshToken: data.refresh_token,
        isTokenRefreshing: false,
      });

      useAppStore().getAllAppStore();

      await this.bootstrap(data.access_token);
      usePartStore().setSystemPart(data.default_part);
      useSettingStore().setBranchId(data.default_branch);

      const redirect = safeRedirect(router.currentRoute.value.query.redirect);
      router.push(redirect ?? homePathFor(data.default_part));
    },
    async token(payload) {
      try {
        const response = await api.post("change-user", {
          ...payload
        }, {               // Third argument: The config object
          headers: {
            'X-CLIENT-ID': import.meta.env.VITE_CLIENT_ID,
          }
        });
        if (response.data.status) {
          const data = response.data.data;

          // Switched-user tokens can't be refreshed; drop the old user's
          // refresh token so it can't bring the previous user back.
          setAccessToken(data.access_token);
          setRefreshToken(data.refresh_token);

          this.$patch({
            isAuthenticated: true,
            accessToken: data.access_token?.value ?? data.access_token,
            refreshToken: null,
            isTokenRefreshing: false,
          });

          await this.bootstrap();
          const defaultPart = response?.data?.data?.default_part;
          usePartStore().setSystemPart(defaultPart);
          useSettingStore().setBranchId(response?.data?.data?.branch_id);

          useAppStore().getAllAppStore();

          router.push(homePathFor(defaultPart));

        } else {
          console.error("Change user failed with status:", response.data.status);
        }
      } catch (error) {
        console.error("Change user error:", error);
      }
    },
    async bootstrap(accessToken = null) {
      const token = accessToken ?? getAccessToken();

      if (!token) {
        return this.unAuthenticated();
      }

      try {
        const response = await api.get('bootstrap');
        if (response.data.a) {
          const userData = decrypt(response.data.b);
          const branches = decrypt(response.data.c);
          const permissions = decrypt(response.data.d);
          const company = decrypt(response.data.e);

          this.$patch({
            id: userData.id,
            user: userData,
            branches: branches,
            permissions: permissions,
            company: company,
            isAuthenticated: true,
            accessToken: token, // Sync token just in case
          });
        }
      } catch (error) {
        if (error.code === 'ERR_CANCELED' || error.message === 'canceled') {
          return;
        }
        if (error.response?.status === 401) {
          await this.unAuthenticated();
        }
      } finally {
        this.isBootstrapped = true;
      }
    },
    // Get a new access token from the refresh token, then reload the user.
    // Returns true when the session is still alive.
    async refresh() {
      if (!getRefreshToken()) return false;

      this.isTokenRefreshing = true;
      try {
        await refreshAccessToken();
        await this.bootstrap();
        return true;
      } catch (error) {
        await this.unAuthenticated();
        return false;
      } finally {
        this.isTokenRefreshing = false;
      }
    },
    async logout() {
      try {
        const response = await api.post("logout");

        if (response.data.status) {

          useSettingStore().branch_id = null;
          useAppStore().clearAllAppStore();

          this.unAuthenticated();
        }
      } catch (error) {
        console.error("Server error:", error);
      }
    },
    async unAuthenticated() {
      // cached look-up lists belong to this user (e.g. a CCO's own team)
      useTempAppStore().clearAllTempAppStore();
      this.$patch({
        id: null,
        isAuthenticated: false,
        user: null,
        accessToken: null,
        refreshToken: null,
        isTokenRefreshing: false,
        permissions: [],
        branches: [],
        company: null,
        isBootstrapped: false,
      });

      removeAccessToken();
      removeRefreshToken();
      // On the public website a visitor can stay where they are
      if (!router.currentRoute.value.meta?.public) router.replace({ name: 'login' });
    },
  }
});
