import { defineStore } from "pinia";
import { api, refreshAccessToken } from "@/utils/api";
import { router } from "@/router/index";
import { useAppStore } from "@/stores/appStore";
import { homePathFor } from "@/utils/homePath";
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
        const response = await api.post("login", {
          ...payload,
        },
          {               // Third argument: The config object
            headers: {
              'X-CLIENT-ID': import.meta.env.VITE_CLIENT_ID,
            }
          }
        );

        if (response.data.status) {

          const data = response.data.data;

          const token = data.access_token;
          const refreshToken = data.refresh_token;
          setAccessToken(token);
          setRefreshToken(refreshToken);

          this.$patch({
            isAuthenticated: true,
            accessToken: response.data.data.access_token,
            refreshToken: response.data.data.refresh_token,
            isTokenRefreshing: false,
          });

          useAppStore().getAllAppStore();

          await this.bootstrap(token);
          const defaultPart = response?.data?.data?.default_part;
          const defaultBranch = response?.data?.data?.default_branch;
          usePartStore().setSystemPart(defaultPart);
          useSettingStore().setBranchId(defaultBranch);

          router.push(homePathFor(defaultPart));

        } else {
          console.error("Login failed with status:", response.data.status);
        }
      } catch (error) {
        console.error("Login error:", error);
      }
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
      router.replace({ name: 'login' });
    },
  }
});
