import { defineStore } from "pinia";
import { markRaw } from "vue";
import { api, onApiMutation } from "@/utils/api";
import { useSettingStore } from "@/stores/settingStore";

/*
 * Reference lists that rarely change: fetched once, then reused by every page
 * that asks for them (see services/dataService.js).
 *
 *   url        the "...-all" endpoint that returns the list
 *   resources  API paths whose save / delete makes the list stale: after a
 *              successful POST / PUT / PATCH / DELETE to one of them (e.g.
 *              "roles", "roles/12") the list is dropped and
 *              the next request fetches it again (api.js calls invalidateUrl)
 *   perBranch  the endpoint answers for the selected branch, so a copy is
 *              kept per branch instead of one shared copy
 *
 * Nothing is kept across a page reload or a logout (the store isn't
 * persisted, and authStore clears it when the user signs out).
 */
export const CACHED_LISTS = {
  // admin
  roles: { url: "roles-all", resources: ["roles"] },
  branches: { url: "branches-all", resources: ["branches"] },
  permissions: { url: "permissions-all", resources: ["permissions"] },

  getUsers: { url: "users-all", resources: ["users"], perBranch: true },
};

// Key a list is stored under: its name, plus the branch for perBranch lists
const cacheKey = (name) =>
  CACHED_LISTS[name]?.perBranch
    ? `${name}:${useSettingStore().branch_id ?? "*"}`
    : name;

// Requests in flight, by cache key: several pages asking for the same list
// at the same moment share one request instead of each sending their own
const pending = new Map();

export const useTempAppStore = defineStore("tempApp", {
  state: () => ({
    // cache key -> list; a key that is missing means "not loaded yet"
    lists: {},
  }),
  actions: {
    /** The list called `name` (see CACHED_LISTS); `force` fetches it again. */
    async getList(name, force = false) {
      const config = CACHED_LISTS[name];
      if (!config) {
        console.error(`tempAppStore: unknown list "${name}"`);
        return [];
      }

      const key = cacheKey(name);
      // a copy each time: a page that pushes / splices its list must not
      // change the list every other page gets
      if (!force && key in this.lists) return [...this.lists[key]];
      if (!force && pending.has(key)) return [...(await pending.get(key))];

      const request = (async () => {
        try {
          const response = await api.get(config.url);
          // markRaw: kept as plain data. The store never reacts to changes
          // inside a list, so making every row reactive here would only cost
          // memory and time (noticeable on phones with big lists like
          // users); the page that shows the list makes its copy reactive.
          this.lists[key] = markRaw(response.data.data ?? []);
        } catch (error) {
          // not stored, so the next request tries again
          console.error("Server error: ", error);
          return [];
        } finally {
          pending.delete(key);
        }
        return this.lists[key];
      })();

      pending.set(key, request);
      return [...(await request)];
    },

    /** Drop a list (every branch's copy), so the next request refetches it. */
    invalidate(name) {
      Object.keys(this.lists).forEach((key) => {
        if (key === name || key.startsWith(`${name}:`)) delete this.lists[key];
      });
    },

    /**
     * After a successful save / delete to `url`, drop the lists built from
     * that resource. Called by the api response interceptor.
     */
    invalidateUrl(url) {
      const path = String(url || "")
        .replace(/^\/+/, "")
        .split("?")[0];

      Object.entries(CACHED_LISTS).forEach(([name, config]) => {
        // a list without `resources` is only refreshed with `force`
        const touched = (config.resources || []).some(
          (resource) => path === resource || path.startsWith(`${resource}/`)
            || path.startsWith(`${resource}-`),
        );
        if (touched) this.invalidate(name);
      });
    },

    /** Forget everything (logout). */
    clearAllTempAppStore() {
      pending.clear();
      this.$patch((state) => {
        state.lists = {};
      });
    },
  },
});

// Every successful save / delete drops the lists built from that resource.
// Registered once, when this module loads; api.js calls it before handing
// the response back, so the page that saved re-reads fresh data.
onApiMutation((url) => useTempAppStore().invalidateUrl(url));
