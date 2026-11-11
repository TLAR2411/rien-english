import { defineStore } from "pinia";
import { api } from "@/utils/api";

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
    async getAllAppStore(forceUpdate = false) {
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
