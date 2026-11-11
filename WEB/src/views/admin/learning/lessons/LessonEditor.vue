<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import { useFitToScreen } from "@/composables/useFitToScreen";
import { getCourses } from "@/services/dataService";
import { requiredValidator } from "@/@core/utils/validators";
import { LESSON_TABS, PUBLISH_STATUSES, SKILLS } from "@/constants/learning";
import {
  GRAMMAR_SECTIONS,
  GRAMMAR_TEMPLATE_EN,
  GRAMMAR_TEMPLATE_KM,
  headingsIn,
} from "@/constants/lessonTemplates";
import { uploadImage } from "@/services/uploads";
import AudioField from "@/components/learning/AudioField.vue";
import RichTextEditor from "@/components/learning/RichTextEditor.vue";
import StatusChip from "@/components/learning/StatusChip.vue";

/**
 * Create (no lessonId) or edit a lesson.
 * Parts saved on their own (examples, quiz, preview) are extra tabs
 * (`tabs` = [{ value, title, icon }]) with a `tab-<value>` slot for each.
 * They are always visible, but open only once the lesson is saved.
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
  audio_url: "",
  status: "draft",
});

const form = ref(emptyForm());
const courses = ref([]);
const isLoading = ref(false);
const textTab = ref("en");
const { isSaving, save } = useCrud("admin/lessons");

const isEdit = computed(() => !!form.value.id);
const allTabs = computed(() => [
  { value: "lesson", title: "Lesson", icon: "tabler-notebook" },
  ...props.tabs,
]);
const isGrammar = computed(() => form.value.skill === "grammar");

const load = async () => {
  if (!props.lessonId) return (form.value = emptyForm());
  isLoading.value = true;
  try {
    const res = await api.get(`admin/lessons/${props.lessonId}`);
    form.value = { ...emptyForm(), ...res.data.data };
  } finally {
    isLoading.value = false;
  }
};

onMounted(async () => {
  courses.value = await getCourses();
});
watch(() => props.lessonId, load, { immediate: true });

const courseTitle = (course) => `${course.level?.code ?? ""} · ${course.title}`;

// ---- Grammar template guide ----
const foundHeadings = computed(() => headingsIn(form.value.content));
const sections = computed(() =>
  GRAMMAR_SECTIONS.map((section) => ({
    ...section,
    done: section.heading
      ? foundHeadings.value.includes(section.heading.toLowerCase())
      : null,
  })),
);

const insertTemplate = () => {
  if (!form.value.content) form.value.content = GRAMMAR_TEMPLATE_EN;
  if (!form.value.content_km) form.value.content_km = GRAMMAR_TEMPLATE_KM;
};

// ---- Save (AppCard gives the form validation) ----
const onSubmit = async (validation) => {
  const { valid } = (await validation) || { valid: true };
  if (!valid) return;

  const saved = await save({
    ...form.value,
    audio_url: form.value.audio_url || null,
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

    <template v-for="item in tabs" :key="item.value">
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
          <VCol cols="12" :lg="isGrammar ? 8 : 12">
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
                  label="Lesson audio (optional)"
                />
              </VCol>

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
                  </VTabs>
                  <VBtn
                    v-if="isGrammar && !form.content"
                    variant="tonal"
                    size="small"
                    prepend-icon="tabler-template"
                    @click="insertTemplate"
                  >
                    {{ t("Insert grammar template") }}
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
              </VCol>
            </VRow>
          </VCol>

          <!-- Guide: the Grammar Lesson Template (PLAN.md Appendix A) -->
          <VCol v-if="isGrammar" cols="12" lg="4">
            <VRow>
              <AppLabel title="Grammar lesson guide" />
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

.guide__hint {
  white-space: normal;
  -webkit-line-clamp: unset;
  line-height: 1.5;
}
</style>
