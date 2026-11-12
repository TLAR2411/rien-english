import { defineStore } from "pinia";
import { api } from "@/utils/api";
import hasPermission from "@/utils/hasPermission";
import { PERMISSION_BY_PART, SYSTEM_PART } from "@/composables/useSystemParts";

const ADMIN_PART_PERMISSION = PERMISSION_BY_PART[SYSTEM_PART.ADMIN];

export const useAppStore = defineStore("app", {
  state: () => ({
    villages: [],
    communes: [],
    districts: [],
    provinces: [],
    isHaveData: false,
  }),
  actions: {
    async getProvinces() {
      try {
        const response = await api.get("provinces-all");

        this.$patch({
          provinces: response.data.data,
        });
      } catch (error) {
        console.error("Server error: ", error);
      }
    },
    async getDistricts() {
      try {
        const response = await api.get("districts-all");
        this.$patch({
          districts: response.data.data,
        });
      } catch (error) {
        console.error("Server error: ", error);
      }
    },
    async getCommunes() {
      try {
        const response = await api.get("communes-all");
        this.$patch({
          communes: response.data.data,
        });
      } catch (error) {
        console.error("Server error: ", error);
      }
    },
    async getVillages() {
      try {
        const response = await api.get("villages-all");
        this.$patch({
          villages: response.data.data,
        });
      } catch (error) {
        console.error("Server error: ", error);
      }
    },
    // Get All App Store
    // Address lists are for the admin system only (students get 403)
    async getAllAppStore(forceUpdate = false) {
      if (!hasPermission(ADMIN_PART_PERMISSION)) return;
      if (this.isHaveData === false || forceUpdate) {
        await this.getAppStore();
        this.isHaveData = true;
      }
    },
    async getAppStore() {
      this.getVillages();
      this.getCommunes();
      this.getDistricts();
      this.getProvinces();
    },
    async clearAllAppStore() {
      this.$patch({
        villages: [],
        communes: [],
        districts: [],
        provinces: [],
        isHaveData: false,
      });
    },
  },
  persist: {
    enabled: true,
    strategies: [
      {
        key: "app",
        storage: localStorage,
      },
    ],
  },
});
