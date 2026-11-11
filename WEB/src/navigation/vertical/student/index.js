import { computed } from "vue";
import { lessonsRoute } from "@/utils/siteRoutes";

/**
 * Student sidebar. Add an item here when its page is built
 * (PLAN.md: My Quizzes T26, My Progress T28, My Writing T34).
 */
export const useStudentNavigation = () =>
  computed(() => [
    {
      title: "Dashboard",
      to: { name: "student-dashboard" },
      icon: { icon: "tabler-layout-dashboard" },
    },
    {
      title: "My lessons",
      to: { name: "student-my-lessons" },
      icon: { icon: "tabler-circle-check" },
    },
    {
      title: "All lessons",
      to: lessonsRoute(),
      icon: { icon: "tabler-books" },
    },
  ]);
