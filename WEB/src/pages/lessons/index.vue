<script setup>
import { debounce } from "lodash";
import LessonCard from "@/components/site/LessonCard.vue";
import { SKILLS } from "@/constants/learning";
import { getSiteLessons, getSiteLevels } from "@/services/site";
import { lessonsRoute } from "@/utils/siteRoutes";

/**
 * Public lesson list (no login). The filters live in the address
 * (?skill=grammar&level=A1&search=…&page=2), so a filtered list can be shared.
 */
definePage({
  meta: {
    title: "Lessons",
    layout: "public",
    public: true,
  },
});

const route = useRoute();
const router = useRouter();
const { locale } = useI18n({ useScope: "global" });

// Current filters, read from the address
const filters = computed(() => ({
  skill: SKILLS.some((skill) => skill.value === route.query.skill) ? route.query.skill : null,
  level: typeof route.query.level === "string" ? route.query.level : null,
  search: typeof route.query.search === "string" ? route.query.search : "",
  page: Math.max(1, Number(route.query.page) || 1),
}));

/** Change some filters; any change except the page goes back to page 1. */
const setFilters = (changes) => {
  const next = { ...filters.value, page: 1, ...changes };
  const query = Object.fromEntries(Object.entries(next).filter(([key, value]) => value && !(key === "page" && value === 1)));
  router.replace(lessonsRoute(query));
};

const levels = ref([]);
const lessons = ref([]);
const meta = ref(null);
const isLoading = ref(true);

const load = async () => {
  isLoading.value = true;
  try {
    const { search, ...rest } = filters.value;
    const result = await getSiteLessons({ ...rest, search: search || null });
    lessons.value = result.data;
    meta.value = result.meta;
  } catch {
    lessons.value = [];
    meta.value = null;
  } finally {
    isLoading.value = false;
  }
};

watch(filters, load, { immediate: true, deep: true });
onMounted(async () => {
  try {
    levels.value = await getSiteLevels();
  } catch {
    levels.value = [];
  }
});

// Search box: type freely, the list follows after a short pause
const searchText = ref(filters.value.search);
const applySearch = debounce(() => setFilters({ search: searchText.value.trim() }), 350);
watch(() => filters.value.search, (value) => {
  if (value !== searchText.value.trim()) searchText.value = value;
});

const hasFilters = computed(() => Boolean(filters.value.skill || filters.value.level || filters.value.search));
const clearFilters = () => {
  searchText.value = "";
  router.replace(lessonsRoute());
};

const levelName = (level) => (locale.value === "km" && level.name_km ? level.name_km : level.name);

const goToPage = (page) => {
  setFilters({ ...filters.value, page });
  window.scrollTo({ top: 0, behavior: "smooth" });
};
</script>

<template>
  <div class="site-section lessons-page">
    <div class="site-container">
      <header class="site-section__head">
        <h1 class="site-h2">{{ $t("Lessons") }}</h1>
        <p class="site-lead">{{ $t("Read any lesson for free. Sign up to take the quizzes.") }}</p>
      </header>

      <!-- Filters -->
      <div class="lessons-filters">
        <label class="lessons-search">
          <VIcon icon="tabler-search" size="20" class="lessons-search__icon" />
          <input
            v-model="searchText"
            type="search"
            class="lessons-search__input"
            :placeholder="$t('Search lessons')"
            :aria-label="$t('Search lessons')"
            @input="applySearch"
          >
        </label>

        <div class="lessons-chips" role="group" :aria-label="$t('Skill')">
          <button
            type="button"
            class="lessons-chip"
            :class="{ 'lessons-chip--on': !filters.skill }"
            :aria-pressed="!filters.skill"
            @click="setFilters({ skill: null })"
          >
            {{ $t("All skills") }}
          </button>
          <button
            v-for="skill in SKILLS"
            :key="skill.value"
            type="button"
            class="lessons-chip"
            :class="{ 'lessons-chip--on': filters.skill === skill.value }"
            :aria-pressed="filters.skill === skill.value"
            @click="setFilters({ skill: skill.value })"
          >
            {{ $t(skill.title) }}
          </button>
        </div>

        <div v-if="levels.length" class="lessons-chips" role="group" :aria-label="$t('Level')">
          <button
            type="button"
            class="lessons-chip"
            :class="{ 'lessons-chip--on': !filters.level }"
            :aria-pressed="!filters.level"
            @click="setFilters({ level: null })"
          >
            {{ $t("All levels") }}
          </button>
          <button
            v-for="level in levels"
            :key="level.id"
            type="button"
            class="lessons-chip"
            :class="{ 'lessons-chip--on': filters.level === level.code }"
            :aria-pressed="filters.level === level.code"
            :title="levelName(level)"
            @click="setFilters({ level: level.code })"
          >
            <strong>{{ level.code }}</strong>
            <span class="lessons-chip__name">{{ levelName(level) }}</span>
          </button>
        </div>
      </div>

      <!-- Result -->
      <div class="lessons-result" :class="{ 'lessons-result--loading': isLoading }" aria-live="polite">
        <template v-if="lessons.length">
          <p class="lessons-count">
            {{ $t("{count} lessons", { count: meta?.total ?? lessons.length }, meta?.total ?? lessons.length) }}
          </p>
          <div class="lessons-grid">
            <LessonCard v-for="lesson in lessons" :key="lesson.id" :lesson="lesson" />
          </div>

          <nav v-if="meta && meta.last_page > 1" class="lessons-pages" :aria-label="$t('Pages')">
            <button type="button" class="site-link lessons-pages__btn" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
              <VIcon icon="tabler-arrow-left" size="18" />
              {{ $t("Previous") }}
            </button>
            <span class="lessons-pages__info">
              {{ $t("Page {page} of {pages}", { page: meta.current_page, pages: meta.last_page }) }}
            </span>
            <button type="button" class="site-link lessons-pages__btn" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
              {{ $t("Next") }}
              <VIcon icon="tabler-arrow-right" size="18" />
            </button>
          </nav>
        </template>

        <div v-else-if="!isLoading" class="lessons-empty">
          <p class="lessons-empty__title">{{ hasFilters ? $t("No lessons found.") : $t("No lessons yet. Please come back soon.") }}</p>
          <button v-if="hasFilters" type="button" class="site-link lessons-empty__clear" @click="clearFilters">
            {{ $t("Show all lessons") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.lessons-page {
  padding-block-start: clamp(40px, 7vw, 72px);
}

.lessons-filters {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-block-end: 32px;
}

.lessons-search {
  display: flex;
  align-items: center;
  gap: 10px;
  max-width: 480px;
  min-height: 48px;
  padding-inline: 16px;
  border-radius: 12px;
  background: var(--rien-bg-alt);
  cursor: text;

  &:focus-within {
    outline: 2px solid var(--rien-blue);
  }
}

.lessons-search__icon {
  color: var(--rien-grey);
}

.lessons-search__input {
  flex: 1 1 auto;
  min-width: 0;
  height: 48px;
  border: 0;
  background: none;
  color: var(--rien-ink);
  font: inherit;
  font-size: 16px; // 16px stops phones from zooming in
  outline: none;
}

// One row of filter buttons; on phones it scrolls sideways
.lessons-chips {
  display: flex;
  gap: 8px;
  margin-inline: -20px;
  padding-inline: 20px;
  overflow-x: auto;
  scrollbar-width: none;

  &::-webkit-scrollbar {
    display: none;
  }
}

.lessons-chip {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 6px;
  min-height: 40px;
  padding: 0 16px;
  border: 1px solid var(--rien-line);
  border-radius: 999px;
  background: #fff;
  color: var(--rien-ink);
  font: inherit;
  font-size: 15px;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s;

  &:hover {
    border-color: var(--rien-blue);
  }

  &--on {
    border-color: var(--rien-ink);
    background: var(--rien-ink);
    color: #fff;

    &:hover {
      border-color: var(--rien-ink);
    }

    .lessons-chip__name {
      color: rgba(255, 255, 255, 0.75);
    }
  }
}

.lessons-chip__name {
  color: var(--rien-grey);
}

.lessons-result {
  transition: opacity 0.15s;

  &--loading {
    opacity: 0.5;
  }
}

.lessons-count {
  margin-block-end: 16px;
  color: var(--rien-grey);
  font-size: 15px;
}

.lessons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.lessons-pages {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-block-start: 40px;
}

.lessons-pages__btn {
  padding: 0;
  border: 0;
  background: none;
  font: inherit;
  cursor: pointer;

  &:disabled {
    visibility: hidden;
  }
}

.lessons-pages__info {
  color: var(--rien-grey);
  font-size: 15px;
}

.lessons-empty {
  padding: 64px 24px;
  border-radius: var(--rien-radius);
  background: var(--rien-bg-alt);
  text-align: center;
}

.lessons-empty__title {
  font-size: 19px;
  font-weight: 600;
}

.lessons-empty__clear {
  margin-block-start: 8px;
  padding: 0;
  border: 0;
  background: none;
  font: inherit;
  cursor: pointer;
}
</style>
