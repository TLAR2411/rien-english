import { defineStore } from "pinia";

export const useSettingStore = defineStore("setting", {
  state: () => ({
    branch_id: null,
    branch_abbr: null,
    branch_province_code: null,
  }),

  getters: {
    getBranchId() {
      return this.branch_id;
    },
    getBranchAbbr() {
      return this.branch_abbr;
    },
    getBranchProvinceCode() {
      return this.branch_province_code;
    },
  },

  actions: {
    setBranchId(id) {
      this.branch_id = id;
    },
    setBranchAbbr(abbr) {
      this.branch_abbr = abbr;
    },
    setBranchProvinceCode(code) {
      this.branch_province_code = code;
    },
  },

  persist: {
    enabled: true,
    strategies: [
      {
        key: "setting",
        storage: localStorage,
      },
    ],
  },
});
