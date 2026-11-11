<script setup>
import LessonCard from "@/components/site/LessonCard.vue";
import { useSpeech } from "@/composables/useSpeech";
import { SKILLS } from "@/constants/learning";
import { getSiteHome } from "@/services/site";
import { lessonRoute, lessonsRoute, signUpRoute } from "@/utils/siteRoutes";

/**
 * Public landing page (no login). Real content only: a real example sentence,
 * a real quiz question, the latest lessons. Design: PLAN.md Appendix C.
 */
definePage({
  meta: {
    title: "Learn English, step by step.",
    layout: "public",
    public: true,
  },
});

const { locale } = useI18n({ useScope: "global" });
const speech = useSpeech();

const home = ref(null);
onMounted(async () => {
  try {
    home.value = await getSiteHome();
  } catch {
    home.value = { levels: [], skills: [], latest: [] };
  }
});

// One short line per skill, in SKILLS order
const SKILL_LINES = {
  grammar: "The rules of English, explained in Khmer.",
  reading: "Short texts with questions.",
  listening: "Listen and understand real English.",
  writing: "Write, then get feedback from a teacher.",
  vocabulary: "New words with sound and Khmer meaning.",
};

const skills = computed(() =>
  SKILLS.map((skill) => ({
    ...skill,
    line: SKILL_LINES[skill.value],
    count: home.value?.skills?.find((item) => item.value === skill.value)?.lessons_count ?? 0,
  })),
);

const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);

// Sample question: the visitor may pick an answer; checking it needs an account
// (quizzes are graded on the server only).
const picked = ref(null);
</script>

<template>
  <div class="landing">
    <!-- Welcome -->
    <section class="site-section landing-hero">
      <div class="site-container landing-hero__grid" :class="{ 'landing-hero__grid--single': home && !home.example }">
        <div>
          <h1 class="site-h1">Learn English,<br>step by step.</h1>
          <p class="landing-hero__km site-km">រៀនអង់គ្លេស មួយជំហានម្តងៗ</p>
          <p class="site-lead landing-hero__lead">
            {{ $t("Free lessons with Khmer explanations. Read any lesson — no account needed.") }}
          </p>
          <div class="landing-hero__actions">
            <RouterLink :to="lessonsRoute()" class="site-btn">{{ $t("Start learning") }}</RouterLink>
            <RouterLink :to="signUpRoute()" class="site-link">
              {{ $t("Sign up to take quizzes") }}
              <VIcon icon="tabler-arrow-right" size="18" />
            </RouterLink>
          </div>
        </div>

        <!-- A real example sentence -->
        <figure v-if="home?.example" class="landing-example">
          <figcaption class="landing-example__from">
            {{ $t("From the lesson") }}
            <RouterLink :to="lessonRoute(home.example.lesson.slug)">{{ home.example.lesson.title_en }}</RouterLink>
          </figcaption>
          <div class="landing-example__row">
            <div>
              <p class="landing-example__sentence">{{ home.example.sentence }}</p>
              <p v-if="home.example.translation_km" class="landing-example__km site-km">
                {{ home.example.translation_km }}
              </p>
            </div>
            <button
              v-if="speech.canPlay(home.example)"
              type="button"
              class="site-speak"
              :aria-label="$t('Listen')"
              @click="speech.toggle(home.example)"
            >
              <VIcon :icon="speech.isPlaying(home.example) ? 'tabler-player-stop' : 'tabler-volume'" size="22" />
            </button>
          </div>
        </figure>
      </div>
    </section>

    <!-- Choose a skill -->
    <section class="site-section landing-skills">
      <div class="site-container">
        <div class="site-section__head">
          <h2 class="site-h2">{{ $t("Choose a skill") }}</h2>
          <p class="site-lead">{{ $t("Start with the skill you need most.") }}</p>
        </div>

        <ul class="landing-list">
          <li v-for="skill in skills" :key="skill.value">
            <component
              :is="skill.count ? 'RouterLink' : 'div'"
              :to="skill.count ? lessonsRoute({ skill: skill.value }) : undefined"
              class="landing-list__row"
              :class="{ 'landing-list__row--soon': !skill.count }"
            >
              <VIcon :icon="skill.icon" size="26" class="landing-list__icon" />
              <span class="landing-list__text">
                <span class="landing-list__title">{{ $t(skill.title) }}</span>
                <span class="landing-list__line">{{ $t(skill.line) }}</span>
              </span>
              <span class="landing-list__count">
                {{ skill.count ? $t("{count} lessons", { count: skill.count }, skill.count) : $t("Coming soon") }}
              </span>
              <VIcon v-if="skill.count" icon="tabler-chevron-right" size="20" class="landing-list__chevron" />
            </component>
          </li>
        </ul>
      </div>
    </section>

    <!-- Choose a level -->
    <section class="site-section site-section--alt">
      <div class="site-container">
        <div class="site-section__head">
          <h2 class="site-h2">{{ $t("Choose your level") }}</h2>
          <p class="site-lead">{{ $t("From A1 (beginner) to C1 (advanced). Not sure? Start at A1.") }}</p>
        </div>

        <ol class="landing-levels">
          <li v-for="level in home?.levels ?? []" :key="level.id">
            <RouterLink :to="lessonsRoute({ level: level.code })" class="landing-levels__step">
              <span class="landing-levels__code">{{ level.code }}</span>
              <span class="landing-levels__name">{{ levelName(level) }}</span>
              <span class="landing-levels__count">
                {{ level.lessons_count ? $t("{count} lessons", { count: level.lessons_count }, level.lessons_count) : $t("Coming soon") }}
              </span>
            </RouterLink>
          </li>
        </ol>
      </div>
    </section>

    <!-- Latest lessons -->
    <section class="site-section">
      <div class="site-container">
        <div class="landing-latest__head">
          <h2 class="site-h2">{{ $t("Latest lessons") }}</h2>
          <RouterLink v-if="home?.latest?.length" :to="lessonsRoute()" class="site-link">
            {{ $t("See all lessons") }}
            <VIcon icon="tabler-arrow-right" size="18" />
          </RouterLink>
        </div>

        <div v-if="home?.latest?.length" class="landing-latest">
          <LessonCard v-for="lesson in home.latest" :key="lesson.id" :lesson="lesson" />
        </div>
        <p v-else-if="home" class="site-lead">{{ $t("No lessons yet. Please come back soon.") }}</p>
      </div>
    </section>

    <!-- A real quiz question -->
    <section v-if="home?.question" class="site-section site-section--alt">
      <div class="site-container landing-question">
        <div class="site-section__head">
          <h2 class="site-h2">{{ $t("Try a question") }}</h2>
          <p class="site-lead">
            {{ $t("From the lesson") }}
            <RouterLink :to="lessonRoute(home.question.lesson.slug)" class="landing-question__lesson">
              {{ home.question.lesson.title_en }}
            </RouterLink>
          </p>
        </div>

        <div class="landing-question__card">
          <p class="landing-question__prompt">{{ home.question.prompt }}</p>
          <div class="landing-question__options" role="radiogroup">
            <button
              v-for="option in home.question.options"
              :key="option.id"
              type="button"
              role="radio"
              :aria-checked="picked === option.id"
              class="landing-question__option"
              :class="{ 'landing-question__option--picked': picked === option.id }"
              @click="picked = option.id"
            >
              {{ option.text }}
            </button>
          </div>

          <Transition name="site-fade">
            <div v-if="picked" class="landing-question__check">
              <p>{{ $t("Sign up free to check your answer.") }}</p>
              <RouterLink :to="signUpRoute(lessonRoute(home.question.lesson.slug).path)" class="site-btn site-btn--small">
                {{ $t("Sign up free") }}
              </RouterLink>
            </div>
          </Transition>
        </div>
      </div>
    </section>

    <!-- Sign up -->
    <section class="site-section landing-cta">
      <div class="site-container">
        <h2 class="site-h2">{{ $t("Ready to test yourself?") }}</h2>
        <p class="site-lead">{{ $t("Reading is free for everyone. Sign up to take quizzes and keep your scores.") }}</p>
        <RouterLink :to="signUpRoute()" class="site-btn landing-cta__btn">{{ $t("Sign up to take quizzes") }}</RouterLink>
      </div>
    </section>
  </div>
</template>

<style lang="scss" scoped>
// ── Welcome ──
.landing-hero {
  padding-block: clamp(48px, 10vw, 120px) clamp(48px, 8vw, 96px);
}

.landing-hero__grid {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
  align-items: center;
  gap: 64px;

  &--single {
    grid-template-columns: minmax(0, 1fr);
  }

  @media (max-width: 899px) {
    grid-template-columns: minmax(0, 1fr);
    gap: 40px;
  }
}

.landing-hero__km {
  margin-block-start: 14px;
  color: var(--rien-ink);
  font-size: clamp(20px, 3vw, 24px);
  font-weight: 600;
}

.landing-hero__lead {
  max-width: 34rem;
  margin-block-start: 20px;
}

.landing-hero__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 28px;
  margin-block-start: 32px;
}

.landing-example {
  margin: 0;
  padding: clamp(24px, 4vw, 36px);
  border-radius: 20px;
  background: var(--rien-bg-alt);
}

.landing-example__from {
  color: var(--rien-grey);
  font-size: 14px;

  a {
    color: var(--rien-blue);
    font-weight: 500;
    text-decoration: none;
  }
}

.landing-example__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-block-start: 20px;
}

.landing-example__sentence {
  font-size: clamp(24px, 3.5vw, 32px);
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.landing-example__km {
  margin-block-start: 8px;
  color: var(--rien-grey);
  font-size: 17px;
}

// ── Skills: one clean list, not a grid of icon cards ──
.landing-list {
  margin: 0;
  padding: 0;
  list-style: none;
  border-block-start: 1px solid var(--rien-line);
}

.landing-list__row {
  display: flex;
  align-items: center;
  gap: 20px;
  min-height: 76px;
  padding-block: 14px;
  border-block-end: 1px solid var(--rien-line);
  text-decoration: none;

  &:not(.landing-list__row--soon):hover .landing-list__title {
    color: var(--rien-blue);
  }

  &--soon {
    color: var(--rien-grey);
  }
}

.landing-list__icon {
  color: var(--rien-blue);

  .landing-list__row--soon & {
    color: var(--rien-grey);
  }
}

.landing-list__text {
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  min-width: 0;
}

.landing-list__title {
  font-size: 19px;
  font-weight: 600;
}

.landing-list__line,
.landing-list__count {
  color: var(--rien-grey);
  font-size: 15px;
}

.landing-list__count {
  flex: 0 0 auto;
}

.landing-list__chevron {
  color: var(--rien-grey);
}

@media (max-width: 599px) {
  .landing-list__row {
    gap: 14px;
  }

  .landing-list__count {
    display: none;
  }
}

// ── Levels: a row of steps ──
.landing-levels {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 12px;
  margin: 0;
  padding: 0;
  list-style: none;

  @media (max-width: 767px) {
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
  }
}

.landing-levels__step {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px;
  border: 1px solid transparent;
  border-radius: var(--rien-radius);
  background: #fff;
  text-decoration: none;
  transition: border-color 0.15s;

  &:hover {
    border-color: var(--rien-blue);
  }

  @media (max-width: 767px) {
    display: grid;
    grid-template-columns: 56px minmax(0, 1fr) auto;
    align-items: center;
    padding: 14px 20px;
  }
}

.landing-levels__code {
  color: var(--rien-blue);
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.landing-levels__name {
  margin-block-start: 4px;
  font-weight: 600;

  @media (max-width: 767px) {
    margin: 0;
  }
}

.landing-levels__count {
  margin-block-start: 2px;
  color: var(--rien-grey);
  font-size: 14px;
}

// ── Latest lessons ──
.landing-latest__head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px 24px;
  margin-block-end: clamp(28px, 4vw, 40px);
}

.landing-latest {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

// ── Sample question ──
.landing-question {
  max-width: 760px;
}

.landing-question__lesson {
  color: var(--rien-blue);
  font-weight: 500;
  text-decoration: none;
}

.landing-question__card {
  padding: clamp(24px, 4vw, 36px);
  border-radius: 20px;
  background: #fff;
}

.landing-question__prompt {
  font-size: clamp(20px, 3vw, 24px);
  font-weight: 600;
  line-height: 1.35;
}

.landing-question__options {
  display: grid;
  gap: 10px;
  margin-block-start: 24px;
}

.landing-question__option {
  min-height: 52px;
  padding: 12px 18px;
  border: 1.5px solid var(--rien-line);
  border-radius: 12px;
  background: #fff;
  color: var(--rien-ink);
  font: inherit;
  font-size: 17px;
  text-align: start;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s;

  &:hover {
    border-color: var(--rien-blue);
  }

  &--picked {
    border-color: var(--rien-blue);
    background: var(--rien-blue-soft);
  }
}

.landing-question__check {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px 20px;
  margin-block-start: 24px;
  padding-block-start: 20px;
  border-block-start: 1px solid var(--rien-line);
  color: var(--rien-grey);
}

// ── Sign up ──
.landing-cta {
  text-align: center;

  .site-lead {
    max-width: 36rem;
    margin-inline: auto;
  }
}

.landing-cta__btn {
  margin-block-start: 28px;
}
</style>
