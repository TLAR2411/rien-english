import { computed } from "vue";
import { myLessonsRoute, myProgressRoute, myQuizzesRoute, studentDashboardRoute, studentLessonsRoute } from "@/utils/studentRoutes";

/**
 * Student sidebar. Add an item here when its page is built
 * (PLAN.md: My Writing T34).
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
      title: "My Progress",
      to: myProgressRoute(),
      icon: { icon: "tabler-chart-line" },
    },
    {
      title: "All lessons",
      to: studentLessonsRoute(),
      icon: { icon: "tabler-books" },
    },
  ]);
