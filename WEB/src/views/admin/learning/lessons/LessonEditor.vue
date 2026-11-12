<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import { useFitToScreen } from "@/composables/useFitToScreen";
import { getCourses } from "@/services/dataService";
import { requiredValidator } from "@/@core/utils/validators";
import { LESSON_TABS, PUBLISH_STATUSES, SKILLS } from "@/constants/learning";
import { LESSON_GUIDES, headingsIn } from "@/constants/lessonTemplates";
import { uploadImage } from "@/services/uploads";
import { youtubeId, youtubeThumbnail, youtubeValidator } from "@/utils/youtube";
import AudioField from "@/components/learning/AudioField.vue";
import RichTextEditor from "@/components/learning/RichTextEditor.vue";
import StatusChip from "@/components/learning/StatusChip.vue";

/**
 * Create (no lessonId) or edit a lesson.
 * Parts saved on their own (examples, quiz, preview) are extra tabs
 * (`tabs` = [{ value, title, icon }]) with a `tab-<value>` slot for each.
 * They are always visible, but open only once the lesson is saved.
 * A tab with `skills` shows only for lessons of those skills (e.g. Words).
 * The open tab is kept in ?tab=.
 */
const props = defineProps({
  lessonId: { type: [Number, String], default: null },
  tabs: { type: Array, default: () => LESSON_TABS },
});

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const tab = computed({
  get: () => route.query.tab || "lesson",
  set: (value) =>
    router.replace({
      query: { ...route.query, tab: value === "lesson" ? undefined : value },
    }),
});

const emptyForm = () => ({
  course_id: null,
  skill: "grammar",
  title_en: "",
  title_km: "",
  slug: "",
  summary: "",
  content: "",
  content_km: "",
  transcript: "",
  checklist: [],
  min_words: null,
  max_words: null,
  sample_answer: "",
  audio_url: "",
  video_url: "",
  status: "draft",
});

const form = ref(emptyForm());
const courses = ref([]);
const isLoading = ref(false);
const textTab = ref("en");
const { isSaving, save } = useCrud("admin/lessons");

const isEdit = computed(() => !!form.value.id);
const skillTabs = computed(() =>
  props.tabs.filter((item) => !item.skills || item.skills.includes(form.value.skill)),
);
const allTabs = computed(() => [
  { value: "lesson", title: "Lesson", icon: "tabler-notebook" },
  ...skillTabs.value,
]);
// The open tab is hidden for this skill (e.g. Words after changing the skill)
watch(skillTabs, (list) => {
  if (tab.value !== "lesson" && !list.some((item) => item.value === tab.value)) tab.value = "lesson";
});
// Template guide of the lesson's skill (grammar, reading, listening); null = no guide
const guide = computed(() => LESSON_GUIDES[form.value.skill] ?? null);
const isListening = computed(() => form.value.skill === "listening");
const isWriting = computed(() => form.value.skill === "writing");
// Extra text tabs (Transcript, Sample answer) only for their skill
watch([isListening, isWriting], ([listening, writing]) => {
  if ((!listening && textTab.value === "transcript") || (!writing && textTab.value === "sample")) textTab.value = "en";
});

// ---- Writing task: checklist items ----
const CHECKLIST_MAX = 10; // API/config/learning.php → writing.checklist_max
const addChecklistItem = () => form.value.checklist.push("");
const removeChecklistItem = (index) => form.value.checklist.splice(index, 1);
const wordNumber = (value) => (value === "" || value === null ? null : Number(value));
const maxWordsRule = (value) =>
  !value || !form.value.min_words || Number(value) >= Number(form.value.min_words) || t("Must be at least the minimum");
const videoId = computed(() => youtubeId(form.value.video_url));
const videoRule = (value) => youtubeValidator(value) === true || t(youtubeValidator(value));

const load = async () => {
  if (!props.lessonId) return (form.value = emptyForm());
  isLoading.value = true;
  try {
    const res = await api.get(`admin/lessons/${props.lessonId}`);
    form.value = { ...emptyForm(), ...res.data.data };
    form.value.checklist = [...(form.value.checklist ?? [])];
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  courses.value = await getCourses();
});
watch(() => props.lessonId, load, { immediate: true });

const courseTitle = (course) => `${course.level?.code ?? ""} · ${course.title}`;

// ---- Template guide ----
const foundHeadings = computed(() => headingsIn(form.value.content));
const sections = computed(() =>
  (guide.value?.sections ?? []).map((section) => ({
    ...section,
    done: section.heading
      ? foundHeadings.value.includes(section.heading.toLowerCase())
      : section.field
        ? Boolean(String(form.value[section.field] ?? "").replace(/<[^>]+>/g, "").trim())
        : null,
  })),
);

const insertTemplate = () => {
  if (!form.value.content) form.value.content = guide.value.en;
  if (!form.value.content_km) form.value.content_km = guide.value.km;
};

// ---- Save (AppCard gives the form validation) ----
const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid) return;

  const saved = await save({
    ...form.value,
    audio_url: form.value.audio_url || null,
    video_url: form.value.video_url?.trim() || null,
    checklist: form.value.checklist.map((item) => item.trim()).filter(Boolean),
    min_words: wordNumber(form.value.min_words),
    max_words: wordNumber(form.value.max_words),
  });
  if (!saved) return;

  if (!isEdit.value) {
    // New lesson: open it, so examples and the quiz can be added
    router.replace({ name: "admin-lessons-id", params: { id: saved.id } });
  } else {
    form.value = { ...form.value, slug: saved.slug };
  }
};

// Big screens: the tabs, card title and Save bar stay still, only the form scrolls
const { box: formBox, isFit, height: formHeight } = useFitToScreen();
</script>

<template>
  <!-- One frame: full-width tabs on top, the open tab below (inner cards have no border) -->
  <VCard flat border class="lesson-tabs">
    <!-- Lesson · Examples · Quiz · Preview (the others open once the lesson is saved) -->
    <VTabs v-model="tab" grow stacked class="v-tabs--fixed">
      <VTab
        v-for="item in allTabs"
        :key="item.value"
        :value="item.value"
        :disabled="item.value !== 'lesson' && !isEdit"
      >
        <VIcon :icon="item.icon" class="mb-2" />
        <span>{{ t(item.title) }}</span>
      </VTab>
    </VTabs>
    <VDivider />

    <p
      v-if="!isEdit"
      class="d-flex align-center justify-center gap-1 text-body-2 text-medium-emphasis mt-4 mb-0 px-4"
    >
      <VIcon icon="tabler-info-circle" size="16" />
      {{ t("Create the lesson first, then add examples and a quiz.") }}
    </p>

    <template v-for="item in skillTabs" :key="item.value">
      <slot
        v-if="isEdit && tab === item.value"
        :name="`tab-${item.value}`"
        :lesson="form"
      />
    </template>

    <div
      v-show="tab === 'lesson' || !isEdit"
      ref="formBox"
      :class="{ 'fit-card': isFit }"
      :style="{ height: isFit ? formHeight : null }"
    >
      <AppCard
        border=""
        :is-full-height-tab="isFit"
        :title="isEdit ? 'Edit Lesson' : 'New Lesson'"
        title-icon="tabler-notebook"
        is-submit
        :submit-text="isEdit ? 'Save' : 'Create'"
        submit-icon="tabler-device-floppy"
        :loading="isLoading || isSaving"
        @on-submit="onSubmit"
      >
        <template #card-header>
          <StatusChip v-if="isEdit" :status="form.status" class="me-2" />
          <slot name="actions" :lesson="form" />
        </template>

        <VRow>
          <VCol cols="12" :lg="guide ? 8 : 12">
            <VRow>
              <!-- Details -->
              <AppLabel title="Details" />
              <VCol cols="12" md="6">
                <AppSelect
                  v-model="form.course_id"
                  label="Course"
                  :items="courses"
                  :item-title="courseTitle"
                  item-value="id"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="6" md="3">
                <AppSelect
                  v-model="form.skill"
                  label="Skill"
                  :items="SKILLS"
                  :item-title="(item) => t(item.title)"
                  item-value="value"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="6" md="3">
                <AppSelect
                  v-model="form.status"
                  label="Status"
                  :items="PUBLISH_STATUSES"
                  :item-title="(item) => t(item.title)"
                  item-value="value"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.title_en"
                  label="Title English"
                  :rules="[requiredValidator]"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.title_km" label="Title Khmer" />
              </VCol>
              <VCol cols="12">
                <AppTextarea
                  v-model="form.summary"
                  label="Summary"
                  rows="2"
                  auto-grow
                  :placeholder="
                    t('One or two sentences shown in the lesson list')
                  "
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.slug"
                  label="Slug"
                  placeholder="present-simple"
                  :hint="
                    t(
                      'Web address name. Leave empty to make it from the title.',
                    )
                  "
                  persistent-hint
                />
              </VCol>
              <VCol cols="12" md="6">
                <AudioField
                  v-model="form.audio_url"
                  :label="isListening ? 'Listening audio (needed to publish)' : 'Lesson audio (optional)'"
                />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField
                  v-model="form.video_url"
                  label="YouTube video (optional)"
                  placeholder="https://www.youtube.com/watch?v=…"
                  prepend-inner-icon="tabler-brand-youtube"
                  :rules="[videoRule]"
                  :hint="t('Paste the video link. Students watch it on the lesson page.')"
                  persistent-hint
                  clearable
                />
              </VCol>
              <VCol v-if="videoId" cols="12" md="6" class="d-flex align-end">
                <a
                  :href="form.video_url"
                  target="_blank"
                  rel="noopener"
                  class="video-preview"
                >
                  <img :src="youtubeThumbnail(videoId)" alt="" />
                  <span class="text-body-2">{{ t("Video added — open on YouTube") }}</span>
                </a>
              </VCol>

              <!-- Writing task: word target + checklist (the task itself is the lesson text) -->
              <template v-if="isWriting">
                <AppLabel title="Writing task" />
                <VCol cols="6" md="3">
                  <AppTextField
                    v-model="form.min_words"
                    label="Minimum words"
                    type="number"
                    min="1"
                    placeholder="50"
                  />
                </VCol>
                <VCol cols="6" md="3">
                  <AppTextField
                    v-model="form.max_words"
                    label="Maximum words"
                    type="number"
                    min="1"
                    placeholder="100"
                    :rules="[maxWordsRule]"
                  />
                </VCol>
                <VCol cols="12">
                  <p class="text-body-2 text-medium-emphasis mb-2">
                    {{ t("Checklist — students tick these before they send their writing.") }}
                  </p>
                  <div
                    v-for="(item, index) in form.checklist"
                    :key="index"
                    class="d-flex align-center gap-2 mb-2"
                  >
                    <VIcon icon="tabler-square-check" color="disabled" size="20" />
                    <AppTextField
                      v-model="form.checklist[index]"
                      :placeholder="t('Use the Present Simple: he works, she likes.')"
                      class="flex-grow-1"
                    />
                    <VBtn
                      icon
                      variant="text"
                      size="small"
                      color="error"
                      :aria-label="t('Remove')"
                      @click="removeChecklistItem(index)"
                    >
                      <VIcon icon="tabler-x" size="18" />
                    </VBtn>
                  </div>
                  <VBtn
                    v-if="form.checklist.length < CHECKLIST_MAX"
                    variant="text"
                    size="small"
                    prepend-icon="tabler-plus"
                    @click="addChecklistItem"
                  >
                    {{ t("Add checklist item") }}
                  </VBtn>
                </VCol>
              </template>

              <!-- Lesson text -->
              <AppLabel title="Lesson text" />
              <VCol cols="12">
                <div class="d-flex align-center flex-wrap gap-2 mb-3">
                  <VTabs
                    v-model="textTab"
                    density="compact"
                    class="flex-grow-1"
                  >
                    <VTab value="en">{{ t("English") }}</VTab>
                    <VTab value="km">{{ t("Khmer") }}</VTab>
                    <VTab v-if="isListening" value="transcript">{{ t("Transcript") }}</VTab>
                    <VTab v-if="isWriting" value="sample">{{ t("Sample answer") }}</VTab>
                  </VTabs>
                  <VBtn
                    v-if="guide && !form.content"
                    variant="tonal"
                    size="small"
                    prepend-icon="tabler-template"
                    @click="insertTemplate"
                  >
                    {{ t(guide.insert) }}
                  </VBtn>
                </div>
                <RichTextEditor
                  v-show="textTab === 'en'"
                  v-model="form.content"
                  :upload-image="uploadImage"
                  :placeholder="t('Write the lesson in simple English…')"
                />
                <RichTextEditor
                  v-show="textTab === 'km'"
                  v-model="form.content_km"
                  :upload-image="uploadImage"
                  :placeholder="t('Khmer explanation…')"
                />
                <RichTextEditor
                  v-if="isListening"
                  v-show="textTab === 'transcript'"
                  v-model="form.transcript"
                  :placeholder="t('Every word of the audio. Write each speaker in bold, e.g. Seller: Good morning!')"
                />
                <RichTextEditor
                  v-if="isWriting"
                  v-show="textTab === 'sample'"
                  v-model="form.sample_answer"
                  :placeholder="t('A model answer. Students see it right after they send their own writing.')"
                />
              </VCol>
            </VRow>
          </VCol>

          <!-- Guide: the template of the lesson's skill (constants/lessonTemplates.js) -->
          <VCol v-if="guide" cols="12" lg="4">
            <VRow>
              <AppLabel :title="guide.title" />
              <VCol cols="12">
                <VList density="compact" class="guide py-0">
                  <VListItem
                    v-for="(section, index) in sections"
                    :key="section.title"
                    class="px-0"
                  >
                    <template #prepend>
                      <VIcon
                        v-if="section.done !== null"
                        :icon="
                          section.done
                            ? 'tabler-circle-check-filled'
                            : 'tabler-circle'
                        "
                        :color="section.done ? 'success' : 'disabled'"
                        size="20"
                      />
                      <span v-else class="guide__number">{{ index + 1 }}</span>
                    </template>
                    <VListItemTitle class="font-weight-medium">{{
                      t(section.title)
                    }}</VListItemTitle>
                    <VListItemSubtitle class="guide__hint">
                      {{ t(section.hint) }}
                      <template v-if="section.where">
                        · {{ t(section.where) }}</template
                      >
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </AppCard>
    </div>
  </VCard>
</template>

<style lang="scss" scoped>
.guide {
  background: transparent;
}

.guide__number {
  display: inline-grid;
  place-items: center;
  width: 20px;
  height: 20px;
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.video-preview {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: inherit;
  text-decoration: none;

  img {
    width: 120px;
    aspect-ratio: 16 / 9;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}

.guide__hint {
  white-space: normal;
  -webkit-line-clamp: unset;
  line-height: 1.5;
}
</style>
