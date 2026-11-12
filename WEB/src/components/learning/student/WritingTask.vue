<script setup>
import { useI18n } from "vue-i18n";
import { getMyWriting, submitWriting } from "@/services/site";
import { useAuthStore } from "@/stores/authStore";
import { formatLearningDate } from "@/utils/learningDate";
import { myWritingRoute } from "@/utils/studentRoutes";
import { wordCount, wordTargetText } from "@/utils/wordCount";

/**
 * The writing box of a writing lesson (student system).
 *  - Write: checklist to tick, text box with a live word counter, "Send to teacher".
 *    The text is kept in this browser until it is sent (a draft).
 *  - Sent: "Waiting for review", the student's text, and the sample answer
 *    (the API only gives it after the first writing).
 *  - Reviewed: the teacher's score and feedback, and "Write again".
 * `preview` (admin): the same box without loading or sending; `sampleAnswer` shows below it.
 */
const props = defineProps({
  lesson: { type: Object, required: true },
  preview: { type: Boolean, default: false },
  sampleAnswer: { type: String, default: null },
});

const { t, locale } = useI18n();
const auth = useAuthStore();

const isLoading = ref(!props.preview);
const submissions = ref([]);
const sample = ref(props.sampleAnswer);
const writingAgain = ref(false);

const latest = computed(() => submissions.value[0] ?? null);
const mode = computed(() => {
  if (props.preview || !latest.value || writingAgain.value) return "write";
  return latest.value.status === "pending" ? "waiting" : "reviewed";
});

const load = async () => {
  if (props.preview) return;
  isLoading.value = true;
  try {
    const data = await getMyWriting(props.lesson.slug);
    submissions.value = data.submissions ?? [];
    sample.value = data.sample_answer;
  } finally {
    isLoading.value = false;
  }
};
watch(() => props.lesson.slug, load, { immediate: true });
watch(
  () => props.sampleAnswer,
  (value) => props.preview && (sample.value = value),
);

// ---- Draft: kept in this browser until sent ----
const draftKey = computed(() => `rien-writing-${auth.user?.id ?? "guest"}-${props.lesson.id}`);
const readDraft = () => {
  try {
    return localStorage.getItem(draftKey.value) ?? "";
  } catch {
    return "";
  }
};
const text = ref(props.preview ? "" : readDraft());
watch(text, (value) => {
  if (props.preview) return;
  try {
    value ? localStorage.setItem(draftKey.value, value) : localStorage.removeItem(draftKey.value);
  } catch {
    /* private window: no draft */
  }
});

// ---- Word target ----
const min = computed(() => props.lesson.min_words || 0);
const max = computed(() => props.lesson.max_words || 0);
const words = computed(() => wordCount(text.value));
const target = computed(() => wordTargetText(t, min.value, max.value));
const fits = computed(() => words.value > 0 && words.value >= min.value && (!max.value || words.value <= max.value));
const counterColor = computed(() => {
  if (max.value && words.value > max.value) return "error";
  return fits.value ? "success" : "medium-emphasis";
});
// Bar fills up to the minimum (or the maximum when there is no minimum)
const progress = computed(() => {
  const goal = min.value || max.value;
  return goal ? Math.min(100, (words.value / goal) * 100) : 0;
});

const checklist = computed(() => props.lesson.checklist ?? []);
const ticked = ref([]);

// ---- Send ----
const isConfirmOpen = ref(false);
const isSending = ref(false);
const error = ref("");

const send = async () => {
  isSending.value = true;
  error.value = "";
  try {
    const data = await submitWriting(props.lesson.slug, text.value);
    submissions.value = [data.submission, ...submissions.value];
    sample.value = data.sample_answer;
    text.value = "";
    ticked.value = [];
    writingAgain.value = false;
    isConfirmOpen.value = false;
  } catch (e) {
    const response = e?.response?.data;
    error.value = response?.errors?.text?.[0] ?? response?.message ?? t("Something went wrong. Please try again.");
    isConfirmOpen.value = false;
  } finally {
    isSending.value = false;
  }
};

const date = (value) => formatLearningDate(value, locale.value);
</script>

<template>
  <section class="writing">
    <VProgressLinear v-if="isLoading" indeterminate color="primary" />

    <!-- ===== Write ===== -->
    <template v-else-if="mode === 'write'">
      <header class="writing__header">
        <h2 class="writing__title">{{ $t("Your writing") }}</h2>
        <p class="writing__lead">
          <template v-if="target">{{ target }} · </template>
          {{ $t("A teacher reads it and gives you a score and feedback.") }}
        </p>
      </header>

      <!-- Checklist: the student ticks it for themselves -->
      <div v-if="checklist.length" class="writing__checklist">
        <p class="writing__label">{{ $t("Before you send, check:") }}</p>
        <VCheckbox
          v-for="(item, index) in checklist"
          :key="index"
          v-model="ticked"
          :value="index"
          :label="item"
          color="success"
          density="comfortable"
          hide-details
        />
      </div>

      <VTextarea
        v-model="text"
        :placeholder="$t('Start writing here…')"
        persistent-placeholder
        :disabled="preview"
        rows="8"
        auto-grow
        variant="outlined"
        hide-details
        class="writing__box"
        :aria-label="$t('Your writing')"
      />
      <VProgressLinear
        v-if="min || max"
        :model-value="progress"
        :color="counterColor === 'medium-emphasis' ? 'primary' : counterColor"
        height="3"
        class="writing__bar"
      />

      <div class="writing__footer">
        <p class="writing__count" :class="`text-${counterColor}`" aria-live="polite">
          <VIcon v-if="fits" icon="tabler-circle-check-filled" size="18" />
          {{ $t("{count} words", { count: words }) }}
          <span v-if="target" class="text-medium-emphasis">/ {{ target }}</span>
        </p>
        <VBtn
          color="primary"
          size="large"
          prepend-icon="tabler-send"
          :disabled="preview || !fits"
          @click="isConfirmOpen = true"
        >
          {{ $t("Send to teacher") }}
        </VBtn>
      </div>

      <p v-if="error" class="writing__error" role="alert">
        <VIcon icon="tabler-alert-circle" size="18" />
        {{ error }}
      </p>
      <p v-else-if="preview" class="writing__hint">{{ $t("Preview — students write and send their writing here.") }}</p>
      <p v-else-if="text" class="writing__hint">
        <VIcon icon="tabler-device-floppy" size="16" />
        {{ $t("Saved on this device until you send it.") }}
      </p>
    </template>

    <!-- ===== Sent: waiting for the teacher ===== -->
    <template v-else-if="mode === 'waiting'">
      <div class="writing__status writing__status--waiting">
        <VIcon icon="tabler-clock-hour-4" size="28" />
        <div>
          <h2 class="writing__status-title">{{ $t("Waiting for review") }}</h2>
          <p>{{ $t("Sent on {date}. Your teacher will read it and give you a score and feedback.", { date: date(latest.created_at) }) }}</p>
        </div>
      </div>
    </template>

    <!-- ===== Reviewed: score + feedback ===== -->
    <template v-else>
      <div class="writing__status writing__status--reviewed">
        <div class="writing__score">
          <strong>{{ latest.score }}</strong>
          <span>/ 100</span>
        </div>
        <div class="flex-grow-1">
          <h2 class="writing__status-title">{{ $t("Your teacher's feedback") }}</h2>
          <p class="writing__feedback">{{ latest.feedback }}</p>
          <p class="writing__meta">{{ $t("Reviewed on {date}", { date: date(latest.reviewed_at) }) }}</p>
        </div>
      </div>
      <VBtn variant="tonal" color="primary" prepend-icon="tabler-pencil" class="mt-4" @click="writingAgain = true">
        {{ $t("Write again") }}
      </VBtn>
    </template>

    <!-- The student's text, after sending -->
    <div v-if="!isLoading && mode !== 'write'" class="writing__sent">
      <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
        <p class="writing__label mb-0">
          {{ $t("Your writing") }} · {{ $t("{count} words", { count: latest.word_count }) }}
        </p>
        <RouterLink :to="myWritingRoute()" class="writing__link">
          {{ $t("All my writing") }}
          <VIcon icon="tabler-arrow-right" size="16" />
        </RouterLink>
      </div>
      <p class="writing__text">{{ latest.text }}</p>
    </div>

    <!-- Sample answer: only after the first writing (or for the teacher's preview) -->
    <div v-if="sample && !isLoading && (mode !== 'write' || preview)" class="writing__sample">
      <p class="writing__label">{{ $t("Sample answer") }}</p>
      <p class="writing__sample-hint">{{ $t("Compare: what did you do well? What can you add next time?") }}</p>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="lesson-content" v-html="sample" />
    </div>

    <!-- Check before sending -->
    <VDialog v-model="isConfirmOpen" max-width="440">
      <VCard>
        <VCardText class="pa-6">
          <h3 class="text-h5 font-weight-bold mb-2">{{ $t("Send your writing?") }}</h3>
          <p class="text-body-1 text-medium-emphasis mb-0">
            {{ $t("You cannot change it after you send it. You will see a sample answer right away.") }}
          </p>
          <p v-if="checklist.length && ticked.length < checklist.length" class="text-body-2 text-warning mt-3 mb-0">
            {{ $t("You ticked {done} of {total} checklist items.", { done: ticked.length, total: checklist.length }) }}
          </p>
        </VCardText>
        <VCardActions class="px-6 pb-6 pt-0">
          <VSpacer />
          <VBtn variant="text" @click="isConfirmOpen = false">{{ $t("Keep writing") }}</VBtn>
          <VBtn color="primary" variant="flat" :loading="isSending" @click="send">{{ $t("Send") }}</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scoped>
$muted: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
$line: rgba(var(--v-border-color), var(--v-border-opacity));

.writing__header {
  margin-block-end: 1.25rem;
}

.writing__title,
.writing__status-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.3;
}

.writing__lead {
  margin: 0.375rem 0 0;
  color: $muted;
}

.writing__label {
  margin: 0 0 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: $muted;
}

.writing__checklist {
  margin-block-end: 1.25rem;
  padding: 1rem 1.25rem 0.75rem;
  border: 1px solid $line;
  border-radius: 8px;

  :deep(.v-label) {
    line-height: 1.6;
    opacity: 1;
  }
}

.writing__box :deep(textarea) {
  font-size: 1.0625rem;
  line-height: 1.8;
}

.writing__bar {
  margin-block-start: -3px;
  border-end-start-radius: 4px;
  border-end-end-radius: 4px;
}

.writing__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  margin-block-start: 1rem;
}

.writing__count {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  margin: 0;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.writing__error,
.writing__hint {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  margin: 0.75rem 0 0;
  font-size: 0.875rem;
}

.writing__error {
  color: rgb(var(--v-theme-error));
}

.writing__hint {
  color: $muted;
}

// Status cards
.writing__status {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-radius: 12px;

  p {
    margin: 0.375rem 0 0;
    line-height: 1.7;
  }

  &--waiting {
    background: rgba(var(--v-theme-primary), 0.06);

    > .v-icon {
      flex: 0 0 auto;
      color: rgb(var(--v-theme-primary));
    }
  }

  &--reviewed {
    border: 1px solid $line;
  }
}

.writing__score {
  display: flex;
  flex: 0 0 auto;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(var(--v-theme-success), 0.1);
  color: rgb(var(--v-theme-success));

  strong {
    font-size: 1.875rem;
    line-height: 1;
  }

  span {
    margin-block-start: 0.25rem;
    font-size: 0.8125rem;
  }
}

.writing__feedback {
  white-space: pre-line;
}

.writing__meta {
  font-size: 0.875rem;
  color: $muted;
}

.writing__sent,
.writing__sample {
  margin-block-start: 2rem;
}

.writing__link {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(var(--v-theme-primary));
  text-decoration: none;
}

.writing__text {
  margin: 0;
  padding: 1.25rem 1.5rem;
  border: 1px solid $line;
  border-radius: 8px;
  font-size: 1.0625rem;
  line-height: 1.8;
  white-space: pre-line;
}

.writing__sample {
  padding: 1.25rem 1.5rem;
  border-inline-start: 3px solid rgb(var(--v-theme-success));
  background: rgba(var(--v-theme-success), 0.04);
}

.writing__sample-hint {
  margin: 0 0 0.75rem;
  color: $muted;
}

@media (max-width: 599px) {
  .writing__status {
    flex-direction: column;
  }

  .writing__footer .v-btn {
    width: 100%;
  }
}
</style>
