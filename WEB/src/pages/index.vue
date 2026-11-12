<script setup>
import { SKILLS, findOption } from "@/constants/learning";
import { getSiteHome } from "@/services/site";
import { lessonRoute, lessonsRoute, signUpRoute } from "@/utils/siteRoutes";

/**
 * Public landing page (no login). Four calm sections, real content only
 * (PLAN.md Appendix C): welcome → level path → latest lessons → try a
 * real quiz question.
 */
definePage({
  meta: {
    title: "Learn English, step by step.",
    layout: "public",
    public: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

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

const skillTitle = (value) => findOption(SKILLS, value)?.title ?? value;
// English mode shows no Khmer text; Khmer mode shows both
const isKhmer = computed(() => locale.value === "km");
const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);

// Sample question: the visitor may pick an answer; checking it needs an account
// (quizzes are graded on the server only).
const picked = ref(null);

// Decoration around the welcome (wide screens only): simple real sentences
// in speech bubbles, the same shape as the logo's bubble.
const BUBBLES = [
  { en: "Hello! How are you?", km: "សួស្តី! សុខសប្បាយទេ?", place: "top-left" },
  { en: "I am a student.", km: "ខ្ញុំជាសិស្ស។", place: "top-right", main: true },
  { en: "Where do you live?", km: "តើអ្នករស់នៅឯណា?", place: "bottom-left" },
  { en: "Nice to meet you.", km: "រីករាយដែលបានជួបអ្នក។", place: "bottom-right" },
];

// "Scroll down" arrow at the bottom of the welcome
const heroEl = ref(null);
const scrollDown = () => heroEl.value?.nextElementSibling?.scrollIntoView({ behavior: "smooth" });
</script>

<template>
  <div class="landing">
    <!-- 1. Welcome -->
    <section ref="heroEl" class="landing-hero">
      <div class="landing-bubbles" aria-hidden="true">
        <div
          v-for="(bubble, index) in BUBBLES"
          :key="bubble.en"
          class="landing-bubble"
          :class="[`landing-bubble--${bubble.place}`, { 'landing-bubble--main': bubble.main }]"
          :style="{ '--delay': `${0.5 + index * 0.15}s` }"
        >
          <span class="landing-bubble__en">{{ bubble.en }}</span>
          <span v-if="isKhmer" class="landing-bubble__km site-km">{{ bubble.km }}</span>
        </div>
      </div>

      <div class="site-container landing-hero__inner">
        <h1 class="landing-hero__title">Learn English,<br>step by step.</h1>
        <p v-if="isKhmer" class="landing-hero__km site-km">រៀនអង់គ្លេស មួយជំហានម្តងៗ</p>
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

      <button type="button" class="landing-hero__scroll" :aria-label="$t('Scroll down')" @click="scrollDown">
        <VIcon icon="tabler-chevron-down" size="24" />
      </button>
    </section>

    <!-- 2. Level path: A1 → C1 -->
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

    <!-- 3. Latest lessons -->
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
                <span v-if="isKhmer && lesson.title_km" class="latest__km site-km">{{ lesson.title_km }}</span>
              </span>
              <span class="latest__skill">{{ $t(skillTitle(lesson.skill)) }}</span>
              <VIcon icon="tabler-chevron-right" size="20" class="latest__arrow" />
            </RouterLink>
          </li>
        </ul>
        <p v-else-if="home" class="landing-head__lead">{{ $t("No lessons yet. Please come back soon.") }}</p>
      </div>
    </section>

    <!-- 4. Test yourself: a real quiz question + sign up -->
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
// Calm but compact: one idea per section, one blue, grey text, thin lines.

.landing-section {
  padding-block: clamp(40px, 5vw, 64px);


  &--alt {
    background: var(--rien-bg-alt);
  }
}

.landing-head {
  max-width: 600px;
  margin-block-end: clamp(24px, 3vw, 32px);

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
  font-size: clamp(26px, 3.4vw, 34px);
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.landing-head__lead {
  margin-block-start: 8px;
  color: var(--rien-grey);
  font-size: 17px;
}

// Khmer headings: a little lighter, they look heavy in bold
.site--km {
  .landing-head__title {
    font-weight: 600;
    letter-spacing: 0;
  }
}

// ── 1. Welcome ──
// The welcome fills the first screen: the next section shows after scrolling
.landing-hero {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: calc(100svh - var(--rien-header-height));
  padding-block: clamp(48px, 8vw, 88px) clamp(72px, 9vw, 104px);
  overflow: hidden;
  text-align: center;
}

// hero and the next white section: don't double the gap between them
.landing-hero + .landing-section:not(.landing-section--alt) {
  padding-block-start: 0;
}

.landing-hero__inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;

  // Soft entrance, one part after the other
  > * {
    animation: landing-rise 0.6s ease-out both;
  }

  @for $i from 2 through 5 {
    > :nth-child(#{$i}) {
      animation-delay: #{($i - 1) * 0.08}s;
    }
  }
}

.landing-hero__scroll {
  position: absolute;
  inset-block-end: 20px;
  inset-inline-start: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-inline-start: -22px;
  border: 0;
  border-radius: 50%;
  background: none;
  color: var(--rien-grey);
  cursor: pointer;
  animation: landing-nudge 2.4s ease-in-out 1.2s infinite;

  &:hover {
    color: var(--rien-blue);
  }
}

// ── Speech bubbles around the welcome ──
.landing-bubbles {
  position: absolute;
  inset: 0;
  max-width: 1360px;
  margin-inline: auto;
  pointer-events: none;

  // Only where there is room beside the headline
  @media (max-width: 1179px) {
    display: none;
  }
}

.landing-bubble {
  position: absolute;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 14px 18px;
  border-radius: 18px;
  background: var(--rien-bg-alt);
  text-align: start;
  animation:
    landing-pop 0.6s ease-out var(--delay) both,
    landing-float 7s ease-in-out calc(var(--delay) + 0.6s) infinite;

  // the tail, like the logo's bubble
  &::after {
    position: absolute;
    inset-block-end: -7px;
    width: 16px;
    height: 16px;
    border-radius: 0 0 4px;
    background: inherit;
    content: "";
    transform: rotate(45deg);
  }

  &--top-left {
    inset-block-start: 16%;
    inset-inline-start: 4%;
  }

  &--top-right {
    inset-block-start: 22%;
    inset-inline-end: 4%;
  }

  &--bottom-left {
    inset-block-end: 24%;
    inset-inline-start: 7%;
    animation-duration: 0.6s, 8s;
  }

  &--bottom-right {
    inset-block-end: 18%;
    inset-inline-end: 6%;
    animation-duration: 0.6s, 8s;
  }

  // tail on the side that faces the headline
  &--top-left::after,
  &--bottom-left::after {
    inset-inline-end: 24px;
  }

  &--top-right::after,
  &--bottom-right::after {
    inset-inline-start: 24px;
  }

  &--main {
    background: var(--rien-blue);
    color: #fff;

    .landing-bubble__km {
      color: rgba(255, 255, 255, 0.8);
    }
  }
}

.landing-bubble__en {
  font-size: 16px;
  font-weight: 600;
  white-space: nowrap;
}

.landing-bubble__km {
  color: var(--rien-grey);
  font-size: 14px;
  white-space: nowrap;
}

@keyframes landing-rise {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
}

@keyframes landing-pop {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.96);
  }
}

@keyframes landing-float {
  50% {
    transform: translateY(-8px);
  }
}

@keyframes landing-nudge {
  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(6px);
  }
}

// People who turn off motion on their device see everything still
@media (prefers-reduced-motion: reduce) {
  .landing-hero__inner > *,
  .landing-bubble,
  .landing-hero__scroll {
    animation: none;
  }
}

.landing-hero__title {
  font-size: clamp(38px, 6.5vw, 64px);
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
  max-width: 36rem;
  margin-block-start: 12px;
  text-wrap: balance;
}

.landing-hero__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px 28px;
  margin-block-start: 28px;
}

.landing-skills {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  margin-block-start: clamp(32px, 4vw, 44px);
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

// ── 2. Level path ──
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

// ── 3. Latest lessons: a clean list ──
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
  min-height: 68px;
  padding-block: 12px;
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
  font-size: 18px;
  font-weight: 600;
  line-height: 1.35;
  transition: color 0.15s;
}

.latest__km {
  color: var(--rien-grey);
  font-size: 15px;
  line-height: 1.5;
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

// ── 4. Test yourself ──
.test {
  display: grid;
  grid-template-columns: minmax(0, 0.85fr) minmax(0, 1fr);
  align-items: center;
  gap: clamp(32px, 5vw, 64px);

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
  margin-block-start: 20px;
}

.test__card {
  padding: clamp(20px, 3vw, 28px);
  border-radius: 16px;
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
  margin-block-start: 8px;
  font-size: clamp(19px, 2.2vw, 22px);
  font-weight: 600;
  line-height: 1.35;
}

.test__options {
  display: grid;
  gap: 8px;
  margin-block-start: 16px;
}

.test__option {
  min-height: 48px;
  padding: 10px 16px;
  border: 1.5px solid var(--rien-line);
  border-radius: 12px;
  background: #fff;
  color: var(--rien-ink);
  font: inherit;
  font-size: 16px;
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
