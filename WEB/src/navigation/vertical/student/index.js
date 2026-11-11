import { computed } from "vue";
import { myLessonsRoute, myQuizzesRoute, studentDashboardRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/**
 * Student sidebar. Add an item here when its page is built
 * (PLAN.md: My Progress T28, My Writing T34).
 */
export const useStudentNavigation = () =>
  computed(() => [
    {
      title: "Dashboard",
      to: studentDashboardRoute(),
      icon: { icon: "tabler-layout-dashboard" },
    },
    {
      title: "My lessons",
      to: myLessonsRoute(),
      icon: { icon: "tabler-circle-check" },
    },
    {
      title: "My Quizzes",
      to: myQuizzesRoute(),
      icon: { icon: "tabler-checklist" },
    },
    {
      title: "All lessons",
      to: studentLessonsRoute(),
      icon: { icon: "tabler-books" },
    },
  ]);
