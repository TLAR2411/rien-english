<script setup>
import { useI18n } from "vue-i18n";
import { WRITINGS_URL } from "@/services/learningAdmin";
import { formatLearningDate } from "@/utils/learningDate";
import { wordTargetText } from "@/utils/wordCount";
import WritingStatusChip from "@/components/learning/WritingStatusChip.vue";

// Students' writings of one status ("pending" / "reviewed", null = all). "Review" opens the review page.
const props = defineProps({
  status: { type: String, default: null },
});

const { t, locale } = useI18n();
const router = useRouter();
const isLoading = ref(false);
const filter = ref({ search: null, status: props.status });
watch(
  () => props.status,
  (status) => (filter.value = { ...filter.value, status }),
);

const headers = computed(() => [
  { title: t("Student"), key: "student", visible: true },
  { title: t("Lesson"), key: "lesson", visible: true },
  // Hidden at first (wide); the column button can show it
  { title: t("Writing"), key: "excerpt", visible: false },
  { title: t("Words"), key: "word_count", align: "center", visible: true },
  { title: t("Sent"), key: "created_at", visible: true },
  { title: t("Status"), key: "status", visible: true },
  { title: t("Action"), key: "actions", align: "center", visible: true },
]);

const onView = (writing) => router.push({ name: "admin-writing-id", params: { id: writing.id } });

const date = (value) => formatLearningDate(value, locale.value);
const studentName = (student) => student?.name_en || student?.name_kh;
const fitsTarget = (item) =>
  (!item.lesson?.min_words || item.word_count >= item.lesson.min_words) &&
  (!item.lesson?.max_words || item.word_count <= item.lesson.max_words);
</script>

<template>
  <AppCardTable
    v-model:loading="isLoading"
    v-model:filters="filter"
    title="Writing Review"
    border=""
    is-full-height-tab
    title-icon="tabler-writing"
    :api-url="WRITINGS_URL"
    :headers="headers"
    save-header-name="header-writing-review-v2"
    enable-mobile-card
    is-filter
    is-view
    can-view="view-writing"
    :is-back="false"
    @on-view="onView"
  >
    <template #filter>
      <VRow class="justify-end">
        <VCol cols="12" sm="8" lg="4">
          <VTextField
            v-model="filter.search"
            :label="t('Search student or lesson')"
            prepend-inner-icon="tabler-search"
            clearable
            hide-details
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </template>

    <template #item.student="{ item }">
      <div class="py-2">
        <div class="font-weight-medium text-no-wrap">{{ studentName(item.student) }}</div>
        <div class="text-body-2 text-medium-emphasis">{{ item.student?.email }}</div>
      </div>
    </template>
    <template #item.lesson="{ item }">
      <span>
        <span v-if="item.lesson?.level" class="text-primary font-weight-medium">{{ item.lesson.level }} · </span>
        {{ item.lesson?.title_en }}
      </span>
    </template>
    <template #item.excerpt="{ item }">
      <span class="writing-excerpt text-medium-emphasis">{{ item.excerpt }}</span>
    </template>
    <template #item.word_count="{ item }">
      <span
        :class="fitsTarget(item) ? '' : 'text-error'"
        :title="wordTargetText(t, item.lesson?.min_words, item.lesson?.max_words)"
      >
        {{ item.word_count }}
      </span>
    </template>
    <template #item.created_at="{ item }">
      <span class="text-no-wrap">{{ date(item.created_at) }}</span>
    </template>
    <template #item.status="{ item }">
      <WritingStatusChip :status="item.status" :score="item.score" />
    </template>
  </AppCardTable>
</template>

<style scoped>
.writing-excerpt {
  display: -webkit-box;
  max-width: 340px;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>
