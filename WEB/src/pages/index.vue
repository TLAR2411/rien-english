<script setup>
import { useSpeech } from "@/composables/useSpeech";
import { SKILLS, findOption } from "@/constants/learning";
import { getSiteHome } from "@/services/site";
import { lessonRoute, lessonsRoute, signUpRoute } from "@/utils/siteRoutes";

/**
 * Public landing page (no login). Five calm sections, real content only
 * (PLAN.md Appendix C): welcome → lesson of the day → level path →
 * latest lessons → try a real quiz question.
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

// Skill quick links under the welcome; skills without lessons yet are shown as "soon"
const skills = computed(() =>
  SKILLS.map((skill) => ({
    ...skill,
    count: home.value?.skills?.find((item) => item.value === skill.value)?.lessons_count ?? 0,
  })),
);

const featured = computed(() => home.value?.featured ?? null);
const skillTitle = (value) => findOption(SKILLS, value)?.title ?? value;
const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);

// Sample question: the visitor may pick an answer; checking it needs an account
// (quizzes are graded on the server only).
const picked = ref(null);
</script>

<template>
  <div class="landing">
    <!-- 1. Welcome -->
    <section class="landing-hero">
      <div class="site-container landing-hero__inner">
        <h1 class="landing-hero__title">Learn English,<br>step by step.</h1>
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

        <nav class="landing-skills" :aria-label="$t('Skill')">
          <template v-for="skill in skills" :key="skill.value">
            <RouterLink v-if="skill.count" :to="lessonsRoute({ skill: skill.value })" class="landing-skills__item">
              <VIcon :icon="skill.icon" size="18" />
              {{ $t(skill.title) }}
            </RouterLink>
            <span v-else class="landing-skills__item landing-skills__item--soon">
              <VIcon :icon="skill.icon" size="18" />
              {{ $t(skill.title) }}
              <small>{{ $t("Soon") }}</small>
            </span>
          </template>
        </nav>
      </div>
    </section>

    <!-- 2. Lesson of the day -->
    <section v-if="featured" class="landing-section">
      <div class="site-container">
        <p class="landing-label">{{ $t("Lesson of the day") }}</p>

        <article class="featured">
          <div class="featured__text">
            <div class="featured__tags">
              <span v-if="featured.level" class="site-tag site-tag--level">{{ featured.level.code }}</span>
              <span class="site-tag">{{ $t(skillTitle(featured.skill)) }}</span>
            </div>
            <h2 class="featured__title">
              <RouterLink :to="lessonRoute(featured.slug)">{{ featured.title_en }}</RouterLink>
            </h2>
            <p v-if="featured.title_km" class="featured__km site-km">{{ featured.title_km }}</p>
            <p v-if="featured.summary" class="featured__summary">{{ featured.summary }}</p>
            <RouterLink :to="lessonRoute(featured.slug)" class="site-link featured__more">
              {{ $t("Read lesson") }}
              <VIcon icon="tabler-arrow-right" size="18" />
            </RouterLink>
          </div>

          <figure v-if="featured.example" class="featured__example">
            <figcaption class="featured__example-label">{{ $t("Example") }}</figcaption>
            <p class="featured__sentence">{{ featured.example.sentence }}</p>
            <p v-if="featured.example.translation_km" class="featured__translation site-km">
              {{ featured.example.translation_km }}
            </p>
            <button
              v-if="speech.canPlay(featured.example)"
              type="button"
              class="featured__listen"
              @click="speech.toggle(featured.example)"
            >
              <VIcon :icon="speech.isPlaying(featured.example) ? 'tabler-player-stop-filled' : 'tabler-volume'" size="20" />
              {{ $t("Listen") }}
            </button>
          </figure>
        </article>
      </div>
    </section>

    <!-- 3. Level path: A1 → C1 -->
    <section class="landing-section landing-section--alt">
      <div class="site-container">
        <div class="landing-head">
          <h2 class="landing-head__title">{{ $t("Learn step by step") }}</h2>
          <p class="landing-head__lead">{{ $t("New to English? Start at A1. Move up one level at a time.") }}</p>
        </div>

        <ol class="path">
          <li v-for="level in home?.levels ?? []" :key="level.id" class="path__step">
            <RouterLink :to="lessonsRoute({ level: level.code })" class="path__link">
              <span class="path__dot" :class="{ 'path__dot--open': level.lessons_count }">{{ level.code }}</span>
              <span class="path__text">
                <span class="path__name">{{ levelName(level) }}</span>
                <span class="path__count">
                  {{ level.lessons_count ? $t("{count} lessons", { count: level.lessons_count }, level.lessons_count) : $t("Coming soon") }}
                </span>
              </span>
            </RouterLink>
          </li>
        </ol>
      </div>
    </section>

    <!-- 4. Latest lessons -->
    <section class="landing-section">
      <div class="site-container">
        <div class="landing-head landing-head--row">
          <h2 class="landing-head__title">{{ $t("Latest lessons") }}</h2>
          <RouterLink v-if="home?.latest?.length" :to="lessonsRoute()" class="site-link">
            {{ $t("See all lessons") }}
            <VIcon icon="tabler-arrow-right" size="18" />
          </RouterLink>
        </div>

        <ul v-if="home?.latest?.length" class="latest">
          <li v-for="lesson in home.latest" :key="lesson.id">
            <RouterLink :to="lessonRoute(lesson.slug)" class="latest__row">
              <span class="latest__level">{{ lesson.level?.code }}</span>
              <span class="latest__text">
                <span class="latest__title">{{ lesson.title_en }}</span>
                <span v-if="lesson.title_km" class="latest__km site-km">{{ lesson.title_km }}</span>
              </span>
              <span class="latest__skill">{{ $t(skillTitle(lesson.skill)) }}</span>
              <VIcon icon="tabler-chevron-right" size="20" class="latest__arrow" />
            </RouterLink>
          </li>
        </ul>
        <p v-else-if="home" class="landing-head__lead">{{ $t("No lessons yet. Please come back soon.") }}</p>
      </div>
    </section>

    <!-- 5. Test yourself: a real quiz question + sign up -->
    <section class="landing-section landing-section--alt">
      <div class="site-container test" :class="{ 'test--single': !home?.question }">
        <div class="test__text">
          <h2 class="landing-head__title">{{ $t("Test yourself") }}</h2>
          <p class="landing-head__lead">
            {{ $t("Reading is free for everyone. Sign up to take quizzes and keep your scores.") }}
          </p>
          <RouterLink :to="signUpRoute()" class="site-btn test__btn">{{ $t("Sign up free") }}</RouterLink>
        </div>

        <div v-if="home?.question" class="test__card">
          <p class="test__from">
            {{ $t("From the lesson") }}
            <RouterLink :to="lessonRoute(home.question.lesson.slug)">{{ home.question.lesson.title_en }}</RouterLink>
          </p>
          <p class="test__prompt">{{ home.question.prompt }}</p>
          <div class="test__options" role="radiogroup" :aria-label="home.question.prompt">
            <button
              v-for="option in home.question.options"
              :key="option.id"
              type="button"
              role="radio"
              :aria-checked="picked === option.id"
              class="test__option"
              :class="{ 'test__option--picked': picked === option.id }"
              @click="picked = option.id"
            >
              {{ option.text }}
            </button>
          </div>
          <Transition name="site-fade">
            <p v-if="picked" class="test__check">
              {{ $t("Sign up free to check your answer.") }}
              <RouterLink :to="signUpRoute(lessonRoute(home.question.lesson.slug).path)">{{ $t("Sign up free") }}</RouterLink>
            </p>
          </Transition>
        </div>
      </div>
    </section>
  </div>
</template>

<style lang="scss" scoped>
// Calm and roomy: one idea per section, one blue, grey text, thin lines.

.landing-section {
  padding-block: clamp(56px, 8vw, 104px);

  &--alt {
    background: var(--rien-bg-alt);
  }
}

.landing-label {
  margin-block-end: 16px;
  color: var(--rien-grey);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.landing-head {
  max-width: 600px;
  margin-block-end: clamp(32px, 4vw, 48px);

  &--row {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px 24px;
    max-width: none;
  }
}

.landing-head__title {
  font-size: clamp(28px, 4vw, 40px);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.landing-head__lead {
  margin-block-start: 12px;
  color: var(--rien-grey);
  font-size: 18px;
}

// Khmer headings: a little lighter, they look heavy in bold
.site--km {
  .landing-head__title,
  .featured__title {
    font-weight: 600;
    letter-spacing: 0;
  }
}

// ── 1. Welcome ──
.landing-hero {
  padding-block: clamp(64px, 12vw, 136px) clamp(56px, 8vw, 96px);
  text-align: center;
}

.landing-hero__inner {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.landing-hero__title {
  font-size: clamp(40px, 8vw, 72px);
  font-weight: 700;
  line-height: 1.05;
  letter-spacing: -0.035em;
}

.landing-hero__km {
  margin-block-start: 16px;
  font-size: clamp(19px, 2.6vw, 23px);
  font-weight: 500;
}

.landing-hero__lead {
  max-width: 32rem;
  margin-block-start: 16px;
}

.landing-hero__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px 28px;
  margin-block-start: 36px;
}

.landing-skills {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-block-start: clamp(48px, 7vw, 72px);
}

.landing-skills__item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 18px;
  border: 1px solid var(--rien-line);
  border-radius: 999px;
  font-size: 15px;
  font-weight: 500;
  text-decoration: none;
  transition: border-color 0.15s, color 0.15s;

  .v-icon {
    color: var(--rien-blue);
  }

  &:hover {
    border-color: var(--rien-blue);
    color: var(--rien-blue);
  }

  &--soon {
    color: var(--rien-grey);

    .v-icon {
      color: inherit;
    }

    &:hover {
      border-color: var(--rien-line);
      color: var(--rien-grey);
    }

    small {
      font-size: 12px;
      opacity: 0.8;
    }
  }
}

// ── 2. Lesson of the day ──
.featured {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  overflow: hidden;
  border: 1px solid var(--rien-line);
  border-radius: 20px;

  @media (max-width: 767px) {
    grid-template-columns: minmax(0, 1fr);
  }
}

.featured__text {
  display: flex;
  flex-direction: column;
  padding: clamp(28px, 5vw, 48px);
}

.featured__tags {
  display: flex;
  gap: 8px;
  margin-block-end: 20px;
}

.featured__title {
  font-size: clamp(28px, 4vw, 36px);
  font-weight: 700;
  line-height: 1.15;
  letter-spacing: -0.02em;

  a {
    text-decoration: none;

    &:hover {
      color: var(--rien-blue);
    }
  }
}

.featured__km {
  margin-block-start: 6px;
  color: var(--rien-grey);
  font-size: 17px;
}

.featured__summary {
  margin-block-start: 16px;
  color: var(--rien-grey);
}

.featured__more {
  margin-block-start: auto;
  padding-block-start: 24px;
}

.featured__example {
  display: flex;
  flex-direction: column;
  justify-content: center;
  margin: 0;
  padding: clamp(28px, 5vw, 48px);
  background: var(--rien-blue-soft);
}

.featured__example-label {
  color: var(--rien-blue);
  font-size: 14px;
  font-weight: 600;
}

.featured__sentence {
  margin-block-start: 12px;
  font-size: clamp(24px, 3.4vw, 32px);
  font-weight: 600;
  line-height: 1.25;
  letter-spacing: -0.01em;
}

.featured__translation {
  margin-block-start: 8px;
  color: var(--rien-grey);
  font-size: 17px;
}

.featured__listen {
  display: inline-flex;
  align-items: center;
  align-self: flex-start;
  gap: 8px;
  min-height: 44px;
  margin-block-start: 24px;
  padding: 0 18px;
  border: 0;
  border-radius: 999px;
  background: #fff;
  color: var(--rien-blue);
  font: inherit;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;

  &:hover {
    background: #f8faff;
  }
}

// ── 3. Level path ──
.path {
  position: relative;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  margin: 0;
  padding: 0;
  list-style: none;

  // the line that joins the steps
  &::before {
    position: absolute;
    inset-block-start: 28px;
    inset-inline: 10% 10%;
    border-block-start: 2px solid var(--rien-line);
    content: "";
  }

  @media (max-width: 767px) {
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;

    &::before {
      inset-block: 28px 28px;
      inset-inline: 27px auto;
      border-block-start: 0;
      border-inline-start: 2px solid var(--rien-line);
    }
  }
}

.path__link {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 0 8px 8px;
  text-align: center;
  text-decoration: none;

  &:hover .path__name {
    color: var(--rien-blue);
  }

  @media (max-width: 767px) {
    flex-direction: row;
    gap: 18px;
    padding: 0;
    text-align: start;
  }
}

.path__dot {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border: 2px solid var(--rien-line);
  border-radius: 50%;
  background: #fff;
  color: var(--rien-grey);
  font-size: 17px;
  font-weight: 700;

  &--open {
    border-color: var(--rien-blue);
    background: var(--rien-blue);
    color: #fff;
  }
}

.path__text {
  display: flex;
  flex-direction: column;
}

.path__name {
  font-weight: 600;
  transition: color 0.15s;
}

.path__count {
  color: var(--rien-grey);
  font-size: 14px;
}

// ── 4. Latest lessons: a clean list ──
.latest {
  margin: 0;
  padding: 0;
  list-style: none;
  border-block-start: 1px solid var(--rien-line);
}

.latest__row {
  display: flex;
  align-items: center;
  gap: 20px;
  min-height: 80px;
  padding-block: 16px;
  border-block-end: 1px solid var(--rien-line);
  text-decoration: none;

  &:hover .latest__title {
    color: var(--rien-blue);
  }
}

.latest__level {
  flex: 0 0 44px;
  color: var(--rien-blue);
  font-weight: 700;
}

.latest__text {
  display: flex;
  flex: 1 1 auto;
  flex-direction: column;
  min-width: 0;
}

.latest__title {
  font-size: 19px;
  font-weight: 600;
  transition: color 0.15s;
}

.latest__km {
  color: var(--rien-grey);
  font-size: 15px;
}

.latest__skill {
  flex: 0 0 auto;
  color: var(--rien-grey);
  font-size: 15px;

  @media (max-width: 599px) {
    display: none;
  }
}

.latest__arrow {
  flex: 0 0 auto;
  color: var(--rien-grey);
}

// ── 5. Test yourself ──
.test {
  display: grid;
  grid-template-columns: minmax(0, 0.85fr) minmax(0, 1fr);
  align-items: center;
  gap: clamp(40px, 7vw, 96px);

  &--single {
    grid-template-columns: minmax(0, 1fr);
    text-align: center;

    .landing-head__lead {
      max-width: 34rem;
      margin-inline: auto;
    }
  }

  @media (max-width: 899px) {
    grid-template-columns: minmax(0, 1fr);
  }
}

.test__btn {
  margin-block-start: 28px;
}

.test__card {
  padding: clamp(24px, 4vw, 36px);
  border-radius: 20px;
  background: #fff;
}

.test__from {
  color: var(--rien-grey);
  font-size: 14px;

  a {
    color: var(--rien-blue);
    font-weight: 500;
    text-decoration: none;
  }
}

.test__prompt {
  margin-block-start: 12px;
  font-size: clamp(20px, 2.6vw, 24px);
  font-weight: 600;
  line-height: 1.35;
}

.test__options {
  display: grid;
  gap: 10px;
  margin-block-start: 24px;
}

.test__option {
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

.test__check {
  margin-block-start: 20px;
  color: var(--rien-grey);

  a {
    color: var(--rien-blue);
    font-weight: 600;
    text-decoration: none;
  }
}
</style>
