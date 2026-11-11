import { defineStore } from "pinia";

export const usePartStore = defineStore("part", {
  state: () => ({
    system_part: null,
  }),

  getters: {
    getSystemPart() {
      return this.system_part
    }
  },

  actions: {
    setSystemPart(part) {
      this.system_part = part
    }
  },

  persist: {
    storage: sessionStorage, // Use sessionStorage instead of localStorage
  },
});
