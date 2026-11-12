<script setup>
import { getMyWritings } from "@/services/site";
import { formatLearningDate } from "@/utils/learningDate";
import { studentLessonsRoute, writingRoute } from "@/utils/studentRoutes";
import WritingStatusChip from "@/components/learning/WritingStatusChip.vue";

/** "My Writing": every writing the student sent, newest first, with the teacher's score. */
definePage({
  meta: {
    title: "My Writing",
    layout: "default",
    requiresAuth: true,
  },
});

const { locale } = useI18n({ useScope: "global" });

const writings = ref([]);
const meta = ref(null);
const page = ref(1);
const isLoading = ref(true);

const load = async () => {
  isLoading.value = true;
  try {
    const res = await getMyWritings({ page: page.value });
    writings.value = res.data;
    meta.value = res.meta;
  } finally {
    isLoading.value = false;
  }
};
watch(page, load, { immediate: true });

const date = (value) => formatLearningDate(value, locale.value);
const title = (lesson) => (locale.value === "km" && lesson?.title_km) || lesson?.title_en;
const waiting = computed(() => writings.value.filter((item) => item.status === "pending").length);
</script>

<template>
  <section>
    <header class="mb-6">
      <h1 class="text-h4 font-weight-bold">{{ $t("My Writing") }}</h1>
      <p class="text-body-1 text-medium-emphasis mb-0">
        <template v-if="meta">
          {{ $t("{count} writings sent", { count: meta.total }) }}
          <template v-if="waiting"> · {{ $t("{count} waiting for review", { count: waiting }) }}</template>
        </template>
        <template v-else>&nbsp;</template>
      </p>
    </header>

    <VProgressLinear v-if="isLoading && !writings.length" indeterminate color="primary" />

    <VCard v-else-if="!writings.length">
      <VCardText class="text-center py-10">
        <VIcon icon="tabler-writing" size="40" class="text-disabled mb-3" />
        <p class="text-body-1 mb-4">{{ $t("No writing yet. Open a writing lesson, write, and send it to your teacher.") }}</p>
        <VBtn :to="studentLessonsRoute({ skill: 'writing' })">{{ $t("Writing lessons") }}</VBtn>
      </VCardText>
    </VCard>

    <template v-else>
      <VCard>
        <VList class="py-0" lines="two">
          <template v-for="(writing, index) in writings" :key="writing.id">
            <VDivider v-if="index" />
            <VListItem :to="writingRoute(writing.id)" class="writing-row">
              <template #prepend>
                <VIcon
                  :icon="writing.status === 'reviewed' ? 'tabler-message-circle-check' : 'tabler-clock-hour-4'"
                  :color="writing.status === 'reviewed' ? 'success' : 'warning'"
                  size="24"
                  class="me-3"
                />
              </template>
              <VListItemTitle class="font-weight-medium">{{ title(writing.lesson) }}</VListItemTitle>
              <VListItemSubtitle>
                {{ $t("Sent on {date}", { date: date(writing.created_at) }) }} ·
                {{ $t("{count} words", { count: writing.word_count }) }}
              </VListItemSubtitle>
              <template #append>
                <WritingStatusChip :status="writing.status" :score="writing.score" class="me-1" />
                <VIcon icon="tabler-chevron-right" size="18" class="text-disabled" />
              </template>
            </VListItem>
          </template>
        </VList>
      </VCard>

      <VPagination
        v-if="meta?.last_page > 1"
        v-model="page"
        :length="meta.last_page"
        :total-visible="5"
        density="comfortable"
        class="mt-4"
      />
    </template>
  </section>
</template>

<style scoped>
.writing-row {
  min-height: 64px;
}
</style>
