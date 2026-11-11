<script setup>
import { useI18n } from "vue-i18n";
import { api } from "@/utils/api";
import { useCrud } from "@/composables/useCrud";
import { getCourses } from "@/services/dataService";
import { requiredValidator } from "@/@core/utils/validators";
import { PUBLISH_STATUSES, SKILLS } from "@/constants/learning";
import { GRAMMAR_SECTIONS, GRAMMAR_TEMPLATE_EN, GRAMMAR_TEMPLATE_KM, headingsIn } from "@/constants/lessonTemplates";
import { uploadImage } from "@/services/uploads";
import AudioField from "@/components/learning/AudioField.vue";
import RichTextEditor from "@/components/learning/RichTextEditor.vue";
import StatusChip from "@/components/learning/StatusChip.vue";

/**
 * Create (no lessonId) or edit a lesson.
 * Parts saved on their own (examples, quiz) are extra tabs: pass
 * `tabs` = [{ value, title, icon }] and a `tab-<value>` slot for each.
 * They are only shown once the lesson exists. The open tab is kept in ?tab=.
 */
const props = defineProps({
  lessonId: { type: [Number, String], default: null },
  tabs: { type: Array, default: () => [] },
});

const { t } = useI18n();
const router = useRouter();
const route = useRoute();

const tab = computed({
  get: () => route.query.tab || "lesson",
  set: (value) => router.replace({ query: { ...route.query, tab: value === "lesson" ? undefined : value } }),
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
const formRef = ref(null);
const textTab = ref("en");
const { isSaving, save } = useCrud("admin/lessons");

const isEdit = computed(() => !!form.value.id);
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

// ---- Save ----
const onSave = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  const saved = await save({ ...form.value, audio_url: form.value.audio_url || null });
  if (!saved) return;

  if (!isEdit.value) {
    // New lesson: open it, so examples and the quiz can be added
    router.replace({ name: "admin-lessons-id", params: { id: saved.id } });
  } else {
    form.value = { ...form.value, slug: saved.slug };
  }
};
</script>

<template>
  <div class="lesson-editor">
    <!-- Header: back, title, one main button -->
    <div class="d-flex align-center flex-wrap gap-3 mb-6">
      <IconBtn :to="{ name: 'admin-lessons' }" :aria-label="t('Back')">
        <VIcon icon="tabler-arrow-left" />
      </IconBtn>
      <div class="flex-grow-1 overflow-hidden">
        <h1 class="text-h5 font-weight-bold text-truncate mb-0">
          {{ isEdit ? form.title_en || t("Edit Lesson") : t("New Lesson") }}
        </h1>
        <StatusChip v-if="isEdit" :status="form.status" class="mt-1" />
      </div>
      <slot name="actions" :lesson="form" />
      <VBtn
        v-if="tab === 'lesson'"
        color="primary"
        prepend-icon="tabler-device-floppy"
        :loading="isSaving"
        :disabled="isLoading"
        @click="onSave"
      >
        {{ isEdit ? t("Save") : t("Create") }}
      </VBtn>
    </div>

    <VTabs v-if="isEdit && tabs.length" v-model="tab" class="mb-6">
      <VTab value="lesson" prepend-icon="tabler-notebook">{{ t("Lesson") }}</VTab>
      <VTab v-for="item in tabs" :key="item.value" :value="item.value" :prepend-icon="item.icon">
        {{ t(item.title) }}
      </VTab>
    </VTabs>

    <template v-for="item in tabs" :key="item.value">
      <slot v-if="isEdit && tab === item.value" :name="`tab-${item.value}`" :lesson="form" />
    </template>

    <VRow v-show="tab === 'lesson' || !isEdit">
      <VCol cols="12" :lg="isGrammar ? 8 : 12">
        <VForm ref="formRef" @submit.prevent="onSave">
          <!-- Details -->
          <VCard class="mb-6" :loading="isLoading">
            <VCardItem :title="t('Details')" />
            <VCardText>
              <VRow>
                <VCol cols="12" md="6">
                  <AppSelect
                    v-model="form.course_id"
                    :items="courses"
                    :item-title="courseTitle"
                    item-value="id"
                    :rules="[requiredValidator]"
                  >
                    <template #label>{{ t("Course") }} <span class="text-error">*</span></template>
                  </AppSelect>
                </VCol>
                <VCol cols="6" md="3">
                  <AppSelect
                    v-model="form.skill"
                    :items="SKILLS"
                    :item-title="(item) => t(item.title)"
                    item-value="value"
                    :rules="[requiredValidator]"
                  >
                    <template #label>{{ t("Skill") }} <span class="text-error">*</span></template>
                  </AppSelect>
                </VCol>
                <VCol cols="6" md="3">
                  <AppSelect
                    v-model="form.status"
                    :items="PUBLISH_STATUSES"
                    :item-title="(item) => t(item.title)"
                    item-value="value"
                    :rules="[requiredValidator]"
                  >
                    <template #label>{{ t("Status") }} <span class="text-error">*</span></template>
                  </AppSelect>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.title_en" placeholder="Present Simple" :rules="[requiredValidator]">
                    <template #label>{{ t("Title English") }} <span class="text-error">*</span></template>
                  </AppTextField>
                </VCol>
                <VCol cols="12" md="6">
                  <AppTextField v-model="form.title_km" placeholder="បច្ចុប្បន្នកាលសាមញ្ញ">
                    <template #label>{{ t("Title Khmer") }}</template>
                  </AppTextField>
                </VCol>
                <VCol cols="12">
                  <AppTextarea v-model="form.summary" rows="2" auto-grow :placeholder="t('One or two sentences shown in the lesson list')">
                    <template #label>{{ t("Summary") }}</template>
                  </AppTextarea>
                </VCol>
                <VCol cols="12">
                  <AudioField v-model="form.audio_url" :label="t('Lesson audio (optional)')" />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="form.slug"
                    placeholder="present-simple"
                    :hint="t('Web address name. Leave empty to make it from the title.')"
                    persistent-hint
                  >
                    <template #label>{{ t("Slug") }}</template>
                  </AppTextField>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <!-- Lesson text -->
          <VCard>
            <VCardItem :title="t('Lesson text')">
              <template #append>
                <VBtn
                  v-if="isGrammar && !form.content"
                  variant="text"
                  size="small"
                  prepend-icon="tabler-template"
                  @click="insertTemplate"
                >
                  {{ t("Insert grammar template") }}
                </VBtn>
              </template>
            </VCardItem>
            <VCardText>
              <VTabs v-model="textTab" class="mb-4">
                <VTab value="en">{{ t("English") }}</VTab>
                <VTab value="km">{{ t("Khmer") }}</VTab>
              </VTabs>
              <VWindow v-model="textTab">
                <VWindowItem value="en">
                  <RichTextEditor v-model="form.content" :upload-image="uploadImage" :placeholder="t('Write the lesson in simple English…')" />
                </VWindowItem>
                <VWindowItem value="km">
                  <RichTextEditor v-model="form.content_km" :upload-image="uploadImage" :placeholder="t('Khmer explanation…')" />
                </VWindowItem>
              </VWindow>
            </VCardText>
          </VCard>
        </VForm>
      </VCol>

      <!-- Guide: the Grammar Lesson Template -->
      <VCol v-if="isGrammar" cols="12" lg="4">
        <VCard class="guide">
          <VCardItem :title="t('Grammar lesson guide')" :subtitle="t('Every grammar lesson has these parts')" />
          <VList density="compact" class="pt-0">
            <VListItem v-for="(section, index) in sections" :key="section.title">
              <template #prepend>
                <VIcon
                  v-if="section.done !== null"
                  :icon="section.done ? 'tabler-circle-check-filled' : 'tabler-circle'"
                  :color="section.done ? 'success' : 'disabled'"
                  size="20"
                />
                <span v-else class="guide__number">{{ index + 1 }}</span>
              </template>
              <VListItemTitle class="font-weight-medium">{{ t(section.title) }}</VListItemTitle>
              <VListItemSubtitle class="guide__hint">
                {{ t(section.hint) }}
                <template v-if="section.where"> · {{ t(section.where) }}</template>
              </VListItemSubtitle>
            </VListItem>
          </VList>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss" scoped>
.guide {
  position: sticky;
  top: 80px;
}

.guide__number {
  display: inline-grid;
  place-items: center;
  width: 20px;
  height: 20px;
  margin-inline-end: 0;
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.guide__hint {
  white-space: normal;
  -webkit-line-clamp: unset;
  line-height: 1.5;
}
</style>
