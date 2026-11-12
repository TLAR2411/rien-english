import { completeLesson, getSiteLesson } from "@/services/site";
import { useAuthStore } from "@/stores/authStore";
import { formatLearningDate } from "@/utils/learningDate";

/**
 * One lesson to read — shared by the public lesson page and the student
 * system's lesson page: load by slug, English / ខ្មែរ explanation switch,
 * and "Mark as completed" for students.
 */
export function useLessonReader(slug) {
  const auth = useAuthStore();
  const { locale } = useI18n({ useScope: "global" });

  const lesson = ref(null);
  const notFound = ref(false);

  const load = async () => {
    lesson.value = null;
    notFound.value = false;
    try {
      lesson.value = await getSiteLesson(toValue(slug));
      document.title = `${lesson.value.title_en} — Rien English`;
    } catch {
      notFound.value = true;
    }
  };
  watch(() => toValue(slug), load, { immediate: true });

  // Explanation language: starts with the site language, the reader can switch
  const language = ref(locale.value === "km" ? "km" : "en");
  const hasKhmer = computed(() => Boolean(lesson.value?.content_km));

  const isLoggedIn = computed(() => auth.isAuthenticated && Boolean(auth.user));
  const isStudent = computed(() => isLoggedIn.value && auth.user?.default_part === "student");

  // Mark as completed: students only
  const isSaving = ref(false);
  const markCompleted = async () => {
    isSaving.value = true;
    try {
      lesson.value.completed_at = (await completeLesson(lesson.value.id)).completed_at;
    } finally {
      isSaving.value = false;
    }
  };
  const completedDate = computed(() => formatLearningDate(lesson.value?.completed_at, locale.value));

  return { lesson, notFound, language, hasKhmer, isLoggedIn, isStudent, isSaving, markCompleted, completedDate };
}
