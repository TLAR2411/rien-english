import { defineStore } from "pinia";
import { api } from "@/utils/api";
import { getSiteLevels } from "@/services/site";
import { useAuthStore } from "@/stores/authStore";

/**
 * The level a student chose in the top bar (saved on their account: users.level_id).
 * Their lesson list and dashboard show only that level.
 */
export const useStudentLevelStore = defineStore("studentLevel", {
  state: () => ({
    levels: [],
  }),
  getters: {
    levelId: () => useAuthStore().user?.level_id ?? null,
    level(state) {
      return state.levels.find((level) => level.id === this.levelId) ?? null;
    },
  },
  actions: {
    async loadLevels() {
      if (!this.levels.length) this.levels = await getSiteLevels();
    },
    async setLevel(levelId) {
      await api.put("student/level", { level_id: levelId });
      const auth = useAuthStore();
      auth.user = { ...auth.user, level_id: levelId };
    },
  },
});
