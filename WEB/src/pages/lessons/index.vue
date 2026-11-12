<script setup>
import LessonCard from "@/components/site/LessonCard.vue";
import { SKILLS } from "@/constants/learning";
import { useLessonList } from "@/composables/useLessonList";
import { homeRoute, lessonsRoute } from "@/utils/siteRoutes";

/**
 * Public lesson list (no login). Filters and pages: composables/useLessonList.js
 * (the student system has its own list page: pages/student/lessons/index.vue).
 */
definePage({
  meta: {
    title: "Lessons",
    layout: "public",
    public: true,
  },
});

const { filters, setFilters, levels, lessons, meta, isLoading, searchText, applySearch, hasFilters, clearFilters, levelName, goToPage } =
  useLessonList(lessonsRoute);
</script>

<template>
  <div class="site-section lessons-page">
    <div class="site-container">
      <RouterLink :to="homeRoute()" class="site-link lessons-back">
        <VIcon icon="tabler-arrow-left" size="18" />
        {{ $t("Home") }}
      </RouterLink>

      <header class="site-section__head">
        <h1 class="site-h2">{{ $t("Lessons") }}</h1>
        <p class="site-lead">{{ $t("Read any lesson for free. Sign up to take the quizzes.") }}</p>
      </header>

      <!-- Skill: simple tabs with a blue line under the chosen one -->
      <nav class="lessons-tabs" :aria-label="$t('Skill')">
        <button
          v-for="tab in [{ value: null, title: 'All skills' }, ...SKILLS]"
          :key="tab.value ?? 'all'"
          type="button"
          class="lessons-tabs__tab"
          :class="{ 'is-on': filters.skill === tab.value }"
          :aria-pressed="filters.skill === tab.value"
          @click="setFilters({ skill: tab.value })"
        >
          {{ $t(tab.title) }}
        </button>
      </nav>

      <!-- Level switch + search on one row -->
      <div class="lessons-tools">
        <div v-if="levels.length" class="lessons-levels" role="group" :aria-label="$t('Level')">
          <button
            v-for="level in [{ id: 'all', code: null }, ...levels]"
            :key="level.id"
            type="button"
            :class="{ 'is-on': filters.level === level.code }"
            :aria-pressed="filters.level === level.code"
            :title="level.code ? levelName(level) : $t('All levels')"
            @click="setFilters({ level: level.code })"
          >
            {{ level.code ?? $t("All") }}
          </button>
        </div>

        <label class="lessons-search">
          <VIcon icon="tabler-search" size="18" class="lessons-search__icon" />
          <input
            v-model="searchText"
            type="search"
            class="lessons-search__input"
            :placeholder="$t('Search lessons')"
            :aria-label="$t('Search lessons')"
            @input="applySearch"
          >
        </label>
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
  padding-block: clamp(32px, 5vw, 56px);

  .site-section__head {
    margin-block-end: 24px;
  }

  .site-lead {
    margin-block-start: 6px;
  }
}

.lessons-back {
  margin-block-end: 12px;
}

// ── Skill tabs ──
.lessons-tabs {
  display: flex;
  gap: 28px;
  border-block-end: 1px solid var(--rien-line);
  overflow-x: auto;
  scrollbar-width: none;

  &::-webkit-scrollbar {
    display: none;
  }

  // Phones: the tabs scroll sideways from edge to edge
  @media (max-width: 599px) {
    margin-inline: -20px;
    padding-inline: 20px;
  }
}

.lessons-tabs__tab {
  flex: 0 0 auto;
  min-height: 44px;
  margin-block-end: -1px;
  padding: 0;
  border: 0;
  border-block-end: 2px solid transparent;
  background: none;
  color: var(--rien-grey);
  font: inherit;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s;

  &:hover {
    color: var(--rien-ink);
  }

  &.is-on {
    border-block-end-color: var(--rien-blue);
    color: var(--rien-ink);
  }
}

// ── Level switch + search ──
.lessons-tools {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px 24px;
  margin-block: 20px 24px;
}

// Same look as the English | ខ្មែរ switch on the lesson page
.lessons-levels {
  display: inline-flex;
  max-width: 100%;
  padding: 3px;
  border-radius: 999px;
  background: var(--rien-bg-alt);
  overflow-x: auto;
  scrollbar-width: none;

  button {
    flex: 0 0 auto;
    min-width: 52px;
    min-height: 38px;
    padding: 0 14px;
    border: 0;
    border-radius: 999px;
    background: none;
    color: var(--rien-grey);
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;

    &:hover {
      color: var(--rien-ink);
    }

    &.is-on {
      background: #fff;
      color: var(--rien-ink);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }
  }
}

.lessons-search {
  display: flex;
  flex: 0 1 300px;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  padding-inline: 14px;
  border-radius: 999px;
  background: var(--rien-bg-alt);
  cursor: text;

  &:focus-within {
    outline: 2px solid var(--rien-blue);
  }

  @media (max-width: 599px) {
    flex-basis: 100%;
  }
}

.lessons-search__icon {
  color: var(--rien-grey);
}

.lessons-search__input {
  flex: 1 1 auto;
  min-width: 0;
  height: 44px;
  border: 0;
  background: none;
  color: var(--rien-ink);
  font: inherit;
  font-size: 16px; // 16px stops phones from zooming in
  outline: none;
}

.lessons-result {
  transition: opacity 0.15s;

  &--loading {
    opacity: 0.5;
  }
}

.lessons-count {
  margin-block-end: 12px;
  color: var(--rien-grey);
  font-size: 14px;
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
