import { defineStore } from "pinia";
import { ref } from "vue";

export const useAppSelectStore = defineStore("appSelect", () => {
    const activeId = ref(null);
    return { activeId };
});
