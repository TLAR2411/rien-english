<script setup>
import LessonView from "@/components/learning/student/LessonView.vue";
import { useLessonReader } from "@/composables/useLessonReader";
import { lessonsRoute, quizRoute, signUpRoute } from "@/utils/siteRoutes";
import { studentLessonRoute } from "@/utils/studentRoutes";
import { wordTargetText } from "@/utils/wordCount";

/**
 * Public lesson page (no login): the lesson text with a Khmer/English switch,
 * examples with sound, and "Take the quiz". The API never sends the quiz
 * questions here — the quiz needs login.
 * (Students read lessons inside their system: pages/student/lessons/[slug].vue)
 */
definePage({
  meta: {
    title: "Lesson",
    layout: "public",
    public: true,
  },
});

const route = useRoute();
const { t } = useI18n({ useScope: "global" });

const { lesson, notFound, language, hasKhmer, isLoggedIn, isStudent, isSaving, markCompleted, completedDate } =
  useLessonReader(() => route.params.slug);

// Take the quiz: a visitor signs up first, then comes back to this quiz
const quizLink = computed(() => {
  const quiz = quizRoute(lesson.value.slug);
  return isLoggedIn.value ? quiz : signUpRoute(quiz.path);
});

// Writing / matching game: students do it inside their system; a visitor signs up first
const isWriting = computed(() => lesson.value?.skill === "writing");
// Vocabulary lesson: the matching game needs login (in the student system)
const hasMatching = computed(() => lesson.value?.skill === "vocabulary" && (lesson.value?.words?.length ?? 0) >= 3);
const writeLink = computed(() =>
  isLoggedIn.value ? studentLessonRoute(lesson.value.slug) : signUpRoute(`/student/lessons/${lesson.value.slug}`),
);
const wordTarget = computed(() => wordTargetText(t, lesson.value?.min_words, lesson.value?.max_words));
</script>

<template>
  <div class="site-section lesson-page">
    <div class="site-container">
      <div class="lesson-page__bar">
        <RouterLink :to="lessonsRoute()" class="site-link">
          <VIcon icon="tabler-arrow-left" size="18" />
          {{ $t("All lessons") }}
        </RouterLink>

        <!-- Khmer explanation switch -->
        <div v-if="hasKhmer" class="lesson-page__lang" role="group" :aria-label="$t('Explanation language')">
          <button
            type="button"
            :class="{ 'is-on': language === 'en' }"
            :aria-pressed="language === 'en'"
            @click="language = 'en'"
          >
            English
          </button>
          <button
            type="button"
            :class="{ 'is-on': language === 'km' }"
            :aria-pressed="language === 'km'"
            @click="language = 'km'"
          >
            ខ្មែរ
          </button>
        </div>
      </div>

      <LessonView
        v-if="lesson"
        :lesson="lesson"
        :language="language"
        :fit-screen="false"
        :show-transcript="lesson.quiz_taken"
      >
        <template v-if="lesson.quiz || isStudent || isWriting || hasMatching" #default>
          <div v-if="hasMatching" class="lesson-quiz lesson-write">
            <h2 class="lesson-quiz__title">{{ $t("Match the words") }}</h2>
            <p class="lesson-quiz__facts">{{ $t("A quick game: match each word to its meaning. Your best score is saved.") }}</p>
            <RouterLink :to="writeLink" class="site-btn lesson-quiz__btn">
              {{ isLoggedIn ? $t("Play the matching game") : $t("Sign up free to play") }}
            </RouterLink>
            <p v-if="!isLoggedIn" class="lesson-quiz__hint">
              {{ $t("Sign up free to play. You come back here right after.") }}
            </p>
          </div>

          <div v-if="isWriting" class="lesson-quiz lesson-write">
            <h2 class="lesson-quiz__title">{{ $t("Write and get feedback") }}</h2>
            <p class="lesson-quiz__facts">
              <template v-if="wordTarget">{{ wordTarget }} · </template>
              {{ $t("A teacher reads it and gives you a score and feedback.") }}
            </p>
            <ul v-if="lesson.checklist?.length" class="lesson-write__list">
              <li v-for="(item, index) in lesson.checklist" :key="index">
                <VIcon icon="tabler-check" size="18" />
                {{ item }}
              </li>
            </ul>
            <RouterLink :to="writeLink" class="site-btn lesson-quiz__btn">
              {{ isLoggedIn ? $t("Write your answer") : $t("Sign up free to write") }}
            </RouterLink>
            <p v-if="!isLoggedIn" class="lesson-quiz__hint">
              {{ $t("Sign up free to send your writing. You come back here right after.") }}
            </p>
          </div>

          <div v-if="lesson.quiz" class="lesson-quiz">
            <h2 class="lesson-quiz__title">{{ $t("Check what you learned") }}</h2>
            <p class="lesson-quiz__facts">
              {{ $t("{count} questions", { count: lesson.quiz.questions_count ?? 0 }) }}
              · {{ $t("Pass score {score}%", { score: lesson.quiz.pass_score }) }}
            </p>
            <RouterLink :to="quizLink" class="site-btn lesson-quiz__btn">{{ $t("Take the quiz") }}</RouterLink>
            <p v-if="!isLoggedIn" class="lesson-quiz__hint">
              {{ $t("Sign up free to take the quiz. You come back here right after.") }}
            </p>
          </div>

          <div v-if="isStudent" class="lesson-done">
            <p v-if="lesson.completed_at" class="lesson-done__ok">
              <VIcon icon="tabler-circle-check-filled" size="22" />
              {{ $t("Completed on {date}", { date: completedDate }) }}
            </p>
            <button v-else type="button" class="lesson-done__btn" :disabled="isSaving" @click="markCompleted">
              <VIcon icon="tabler-check" size="20" />
              {{ $t("Mark as completed") }}
            </button>
          </div>
        </template>
      </LessonView>

      <div v-else-if="notFound" class="lesson-page__missing">
        <h1 class="site-h2">{{ $t("Lesson not found") }}</h1>
        <p class="site-lead">{{ $t("This lesson does not exist or is not published yet.") }}</p>
        <RouterLink :to="lessonsRoute()" class="site-btn lesson-page__missing-btn">{{ $t("See all lessons") }}</RouterLink>
      </div>

      <p v-else class="lesson-page__loading" aria-live="polite">{{ t("Loading...") }}</p>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.lesson-page {
  padding-block-start: clamp(20px, 4vw, 40px);
}

.lesson-page__bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  max-width: 1080px;
  margin: 0 auto 20px;
}

// Two-button switch: English | ខ្មែរ
.lesson-page__lang {
  display: inline-flex;
  padding: 3px;
  border-radius: 999px;
  background: var(--rien-bg-alt);

  button {
    min-width: 84px;
    min-height: 38px;
    padding: 0 16px;
    border: 0;
    border-radius: 999px;
    background: none;
    color: var(--rien-grey);
    font: inherit;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;

    &.is-on {
      background: #fff;
      color: var(--rien-ink);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }
  }
}

.lesson-quiz {
  padding: clamp(24px, 4vw, 32px);
  border-radius: var(--rien-radius);
  background: var(--rien-blue-soft);
}

.lesson-quiz__title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.lesson-quiz__facts {
  margin: 6px 0 0;
  color: var(--rien-grey);
}

.lesson-quiz__btn {
  margin-block-start: 20px;
}

.lesson-quiz__hint {
  margin: 12px 0 0;
  color: var(--rien-grey);
  font-size: 15px;
}

.lesson-write + .lesson-quiz {
  margin-block-start: 24px;
}

.lesson-write__list {
  display: grid;
  gap: 8px;
  margin: 20px 0 0;
  padding: 0;
  list-style: none;

  li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.6;
  }

  .v-icon {
    flex: 0 0 auto;
    margin-block-start: 4px;
    color: var(--rien-blue);
  }
}

.lesson-done {
  margin-block-start: 24px;
}

.lesson-done__ok {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  color: var(--rien-green);
  font-weight: 500;
}

.lesson-done__btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 48px;
  padding: 0 20px;
  border: 1.5px solid var(--rien-line);
  border-radius: 999px;
  background: #fff;
  color: var(--rien-ink);
  font: inherit;
  font-weight: 500;
  cursor: pointer;

  &:hover {
    border-color: var(--rien-green);
    color: var(--rien-green);
  }

  &:disabled {
    opacity: 0.6;
  }
}

.lesson-page__missing {
  max-width: 560px;
  margin: 48px auto;
  text-align: center;
}

.lesson-page__missing-btn {
  margin-block-start: 28px;
}

.lesson-page__loading {
  padding-block: 64px;
  color: var(--rien-grey);
  text-align: center;
}
</style>
