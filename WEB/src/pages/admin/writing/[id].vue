<script setup>
import { useI18n } from "vue-i18n";
import { getWritingReview, saveWritingReview } from "@/services/learningAdmin";
import { formatLearningDate } from "@/utils/learningDate";
import { wordTargetText } from "@/utils/wordCount";
import hasPermission from "@/utils/hasPermission";
import WritingStatusChip from "@/components/learning/WritingStatusChip.vue";

/**
 * Review one writing: the student's text (with the lesson's task, checklist and
 * sample answer to compare), then a score 0–100 and feedback.
 * "Save and next" opens the oldest writing still waiting.
 */
definePage({
  meta: {
    title: "Review writing",
    layout: "default",
    subject: "Auth",
    requiresAuth: true,
    permissions: "view-writing",
  },
});

const route = useRoute();
const router = useRouter();
const { t, locale } = useI18n();

const writing = ref(null);
const isLoading = ref(true);
const notFound = ref(false);
const isSaving = ref(false);
const formRef = ref(null);
const form = ref({ score: null, feedback: "" });
const canReview = hasPermission("review-writing");

const load = async () => {
  isLoading.value = true;
  notFound.value = false;
  try {
    writing.value = await getWritingReview(route.params.id);
    form.value = { score: writing.value.score, feedback: writing.value.feedback ?? "" };
  } catch {
    notFound.value = true;
  } finally {
    isLoading.value = false;
  }
};
watch(() => route.params.id, load, { immediate: true });

const lesson = computed(() => writing.value?.lesson ?? {});
const student = computed(() => writing.value?.student ?? {});
const studentName = computed(() => student.value.name_en || student.value.name_kh);
const target = computed(() => wordTargetText(t, lesson.value.min_words, lesson.value.max_words));
const fitsTarget = computed(
  () =>
    (!lesson.value.min_words || writing.value.word_count >= lesson.value.min_words) &&
    (!lesson.value.max_words || writing.value.word_count <= lesson.value.max_words),
);
const isReviewed = computed(() => writing.value?.status === "reviewed");
const date = (value) => formatLearningDate(value, locale.value);
const reviewerName = (reviewer) => reviewer?.name_en || reviewer?.name_kh;

// Score: number box + slider (both 0–100)
const scoreRules = [
  (value) => (value !== null && value !== "") || t("Give a score"),
  (value) => (Number(value) >= 0 && Number(value) <= 100) || t("The score is from 0 to 100"),
];
const feedbackRules = [(value) => Boolean(value?.trim()) || t("Write some feedback")];
const QUICK_SCORES = [50, 60, 70, 80, 90, 100];

const save = async (goNext) => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  isSaving.value = true;
  try {
    const saved = await saveWritingReview(writing.value.id, {
      score: Number(form.value.score),
      feedback: form.value.feedback.trim(),
    });
    if (goNext && saved.next_pending_id) {
      router.push({ name: "admin-writing-id", params: { id: saved.next_pending_id } });
    } else {
      await load(); // fresh status, reviewer and waiting count
    }
  } finally {
    isSaving.value = false;
  }
};

const backToList = () => router.push({ name: "admin-writing" });
</script>

<template>
  <section class="review-page">
    <!-- Top bar: back, waiting count, next -->
    <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-4">
      <VBtn variant="text" prepend-icon="tabler-arrow-left" class="ms-n3" @click="backToList">
        {{ t("Writing Review") }}
      </VBtn>
      <div v-if="writing" class="d-flex align-center gap-3">
        <span class="text-body-2 text-medium-emphasis">
          {{ t("{count} waiting", { count: writing.pending_count ?? 0 }) }}
        </span>
        <VBtn
          v-if="writing.next_pending_id"
          variant="tonal"
          size="small"
          append-icon="tabler-arrow-right"
          :to="{ name: 'admin-writing-id', params: { id: writing.next_pending_id } }"
        >
          {{ t("Next waiting") }}
        </VBtn>
      </div>
    </div>

    <VProgressLinear v-if="isLoading" indeterminate color="primary" class="mb-4" />

    <VAlert v-if="notFound" type="warning" variant="tonal">{{ t("This writing was not found.") }}</VAlert>

    <VRow v-else-if="writing">
      <!-- ===== The student's writing ===== -->
      <VCol cols="12" lg="7">
        <VCard flat border>
          <VCardText class="pa-6">
            <div class="d-flex flex-wrap align-center gap-4 mb-5">
              <VAvatar color="primary" variant="tonal" size="48" class="text-h6 font-weight-bold">
                {{ studentName?.charAt(0)?.toUpperCase() }}
              </VAvatar>
              <div class="flex-grow-1">
                <h2 class="text-h5 font-weight-bold mb-0">{{ studentName }}</h2>
                <p class="text-body-2 text-medium-emphasis mb-0">{{ student.email }}</p>
              </div>
              <WritingStatusChip :status="writing.status" :score="writing.score" />
            </div>

            <p class="text-body-1 mb-1">
              <span v-if="lesson.level" class="text-primary font-weight-bold">{{ lesson.level }} · </span>
              <span class="font-weight-medium">{{ lesson.title_en }}</span>
            </p>
            <div class="d-flex flex-wrap gap-x-4 gap-y-1 text-body-2 text-medium-emphasis mb-5">
              <span>{{ t("Sent on {date}", { date: date(writing.created_at) }) }}</span>
              <span :class="fitsTarget ? 'text-success' : 'text-error'">
                {{ t("{count} words", { count: writing.word_count }) }}<template v-if="target"> / {{ target }}</template>
              </span>
            </div>

            <p class="review-text">{{ writing.text }}</p>
          </VCardText>

          <!-- The task, to compare with -->
          <VExpansionPanels variant="accordion" class="review-task" multiple>
            <VExpansionPanel v-if="lesson.content" :title="t('The task')">
              <VExpansionPanelText>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div class="lesson-content" v-html="lesson.content" />
              </VExpansionPanelText>
            </VExpansionPanel>
            <VExpansionPanel v-if="lesson.checklist?.length" :title="t('Checklist')">
              <VExpansionPanelText>
                <ul class="review-checklist">
                  <li v-for="(item, index) in lesson.checklist" :key="index">
                    <VIcon icon="tabler-square-check" size="18" class="text-disabled" />
                    {{ item }}
                  </li>
                </ul>
              </VExpansionPanelText>
            </VExpansionPanel>
            <VExpansionPanel v-if="lesson.sample_answer" :title="t('Sample answer')">
              <VExpansionPanelText>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div class="lesson-content" v-html="lesson.sample_answer" />
              </VExpansionPanelText>
            </VExpansionPanel>
          </VExpansionPanels>
        </VCard>

        <!-- Earlier writings of this student for this lesson -->
        <VCard v-if="writing.earlier?.length" flat border class="mt-6">
          <VCardItem>
            <VCardTitle>{{ t("Earlier writings for this lesson") }}</VCardTitle>
          </VCardItem>
          <VList class="py-0" lines="three">
            <template v-for="(item, index) in writing.earlier" :key="item.id">
              <VDivider v-if="index" />
              <VListItem :to="{ name: 'admin-writing-id', params: { id: item.id } }">
                <VListItemTitle class="font-weight-medium">
                  {{ date(item.created_at) }} · {{ t("{count} words", { count: item.word_count }) }}
                </VListItemTitle>
                <VListItemSubtitle>{{ item.feedback || item.excerpt }}</VListItemSubtitle>
                <template #append>
                  <WritingStatusChip :status="item.status" :score="item.score" />
                </template>
              </VListItem>
            </template>
          </VList>
        </VCard>
      </VCol>

      <!-- ===== Review form ===== -->
      <VCol cols="12" lg="5">
        <VCard flat border class="review-form">
          <VCardText class="pa-6">
            <h2 class="text-h5 font-weight-bold mb-1">{{ t(isReviewed ? "Your review" : "Review") }}</h2>
            <p v-if="isReviewed" class="text-body-2 text-medium-emphasis mb-5">
              {{ t("Reviewed by {name} on {date}", { name: reviewerName(writing.reviewer) ?? "—", date: date(writing.reviewed_at) }) }}
            </p>
            <p v-else class="text-body-2 text-medium-emphasis mb-5">
              {{ t("The student sees the score and feedback in My Writing.") }}
            </p>

            <VForm ref="formRef" :disabled="!canReview" @submit.prevent="save(false)">
              <div class="d-flex align-center gap-4 mb-2">
                <div class="review-score">
                  <AppTextField
                    v-model="form.score"
                    :label="t('Score (0–100)')"
                    type="number"
                    min="0"
                    max="100"
                    :rules="scoreRules"
                  />
                </div>
                <VSlider
                  :model-value="Number(form.score) || 0"
                  :min="0"
                  :max="100"
                  :step="5"
                  color="primary"
                  hide-details
                  class="flex-grow-1 mt-6"
                  @update:model-value="(value) => (form.score = value)"
                />
              </div>
              <div class="d-flex flex-wrap gap-2 mb-6">
                <VChip
                  v-for="value in QUICK_SCORES"
                  :key="value"
                  size="small"
                  :color="Number(form.score) === value ? 'primary' : undefined"
                  :variant="Number(form.score) === value ? 'flat' : 'outlined'"
                  @click="form.score = value"
                >
                  {{ value }}
                </VChip>
              </div>

              <AppTextarea
                v-model="form.feedback"
                :label="t('Feedback')"
                :placeholder="t('One thing done well, one thing to improve, and an example of a better sentence.')"
                rows="7"
                auto-grow
                :rules="feedbackRules"
              />

              <div v-if="canReview" class="d-flex flex-wrap justify-end gap-3 mt-6">
                <VBtn
                  v-if="writing.next_pending_id"
                  variant="tonal"
                  color="primary"
                  :loading="isSaving"
                  append-icon="tabler-arrow-right"
                  @click="save(true)"
                >
                  {{ t("Save and next") }}
                </VBtn>
                <VBtn type="submit" color="primary" prepend-icon="tabler-device-floppy" :loading="isSaving">
                  {{ t(isReviewed ? "Update review" : "Save review") }}
                </VBtn>
              </div>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style lang="scss" scoped>
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.review-text {
  margin: 0;
  padding: 1.25rem 1.5rem;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.03);
  font-size: 1.0625rem;
  line-height: 1.8;
  white-space: pre-line;
}

.review-task {
  border-block-start: 1px solid $line;

  :deep(.v-expansion-panel) {
    border-radius: 0 !important;
  }
}

.review-checklist {
  display: grid;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;

  li {
    display: flex;
    gap: 0.5rem;
  }
}

.review-score {
  flex: 0 0 128px;
}

// Big screens: the form stays in view while the writing scrolls
@media (min-width: 1280px) {
  .review-form {
    position: sticky;
    inset-block-start: 88px;
  }
}
</style>
