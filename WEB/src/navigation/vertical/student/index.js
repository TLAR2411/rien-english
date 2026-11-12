import { computed } from "vue";
import {
  myLessonsRoute,
  myProgressRoute,
  myQuizzesRoute,
  myWritingRoute,
  studentDashboardRoute,
  studentLessonsRoute,
} from "@/utils/studentRoutes";

/** Student sidebar. Add an item here when its page is built. */
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
      title: "My Writing",
      to: myWritingRoute(),
      icon: { icon: "tabler-writing" },
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
